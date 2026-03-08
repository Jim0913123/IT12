<?php
/**
 * Void Operations Functions
 * Handles secure void operations with admin authorization and inventory restoration
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/inventory_functions.php';

/**
 * Verify admin credentials for void authorization
 * Returns admin user_id if valid, false otherwise
 */
function verifyAdminForVoid(string $password): ?int {
    try {
        $pdo = getPDO();
        
        // Check rate limiting
        if (checkRateLimit('void', 10, 5)) {
            return null; // Too many attempts
        }
        
        // Get all admin users
        $stmt = $pdo->prepare("SELECT user_id, username, password FROM users WHERE role = 'admin' AND status = 'active'");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            if (password_verify($password, $admin['password'])) {
                // Record successful attempt and reset rate limit
                recordAttempt('void', true, $admin['username']);
                resetRateLimit('void');
                return (int)$admin['user_id'];
            }
        }
        
        // Record failed attempt
        recordAttempt('void', false);
        return null;
        
    } catch (Exception $e) {
        error_log("Verify Admin Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Void a single sale item
 * Restores cups, ingredients, and product stock
 */
function voidSaleItem(int $saleItemId, int $adminId, int $requesterId, string $reason): array {
    try {
        $pdo = getPDO();
        
        // Get the sale item with product info
        $stmt = $pdo->prepare("
            SELECT si.*, s.invoice_number, s.user_id as cashier_id, p.requires_cup, p.product_name
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.sale_id
            LEFT JOIN products p ON si.product_id = p.product_id
            WHERE si.sale_item_id = ?
        ");
        $stmt->execute([$saleItemId]);
        $saleItem = $stmt->fetch();
        
        if (!$saleItem) {
            return ['success' => false, 'error' => 'Sale item not found'];
        }
        
        if ($saleItem['is_voided']) {
            return ['success' => false, 'error' => 'Item is already voided'];
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Mark item as voided
        $updateStmt = $pdo->prepare("
            UPDATE sale_items 
            SET is_voided = 1, voided_at = NOW(), voided_by = ?, void_reason = ?
            WHERE sale_item_id = ?
        ");
        $updateStmt->execute([$adminId, $reason, $saleItemId]);
        
        // Restore inventory
        $cupsRestored = false;
        $ingredientsRestored = false;
        
        // Restore product stock
        if ($saleItem['product_id']) {
            restoreProductStock($saleItem['product_id'], $saleItem['quantity'], $saleItem['sale_id'], $adminId);
        }
        
        // Restore cup stock if applicable
        if ($saleItem['cup_id']) {
            restoreCupStock($saleItem['cup_id'], $saleItem['quantity'], $saleItem['sale_id'], $saleItemId, $adminId);
            $cupsRestored = true;
        }
        
        // Restore ingredients if product requires them
        if ($saleItem['product_id'] && $saleItem['requires_cup']) {
            restoreIngredients($saleItem['product_id'], $saleItem['cup_id'], $saleItem['quantity'], $saleItem['sale_id'], $saleItemId, $adminId);
            $ingredientsRestored = true;
        }
        
        // Record in voided_orders table
        $voidStmt = $pdo->prepare("
            INSERT INTO voided_orders 
            (sale_id, sale_item_id, invoice_number, product_name, cup_size, quantity, unit_price, total_amount, 
             cashier_id, admin_id, void_reason, cups_restored, ingredients_restored)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $voidStmt->execute([
            $saleItem['sale_id'],
            $saleItemId,
            $saleItem['invoice_number'],
            $saleItem['product_name'],
            $saleItem['cup_size'],
            $saleItem['quantity'],
            $saleItem['unit_price'],
            $saleItem['subtotal'],
            $requesterId,
            $adminId,
            $reason,
            $cupsRestored ? 1 : 0,
            $ingredientsRestored ? 1 : 0
        ]);
        
        // Update sale totals
        updateSaleTotals($saleItem['sale_id']);
        
        $pdo->commit();
        
        // Log activity
        logActivity(
            'item_voided',
            "Voided item: {$saleItem['product_name']} x{$saleItem['quantity']} from invoice {$saleItem['invoice_number']}. Reason: $reason",
            $adminId,
            'sale_items',
            $saleItemId
        );
        
        return [
            'success' => true,
            'message' => 'Item voided successfully',
            'cups_restored' => $cupsRestored,
            'ingredients_restored' => $ingredientsRestored
        ];
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Void Sale Item Error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error during void operation'];
    }
}

/**
 * Void entire sale (all items)
 */
function voidEntireSale(int $saleId, int $adminId, int $requesterId, string $reason): array {
    try {
        $pdo = getPDO();
        
        // Get sale info
        $saleStmt = $pdo->prepare("SELECT * FROM sales WHERE sale_id = ?");
        $saleStmt->execute([$saleId]);
        $sale = $saleStmt->fetch();
        
        if (!$sale) {
            return ['success' => false, 'error' => 'Sale not found'];
        }
        
        if ($sale['status'] === 'voided') {
            return ['success' => false, 'error' => 'Sale is already voided'];
        }
        
        // Get all non-voided items
        $itemsStmt = $pdo->prepare("
            SELECT si.*, p.requires_cup, p.product_name
            FROM sale_items si
            LEFT JOIN products p ON si.product_id = p.product_id
            WHERE si.sale_id = ? AND si.is_voided = 0
        ");
        $itemsStmt->execute([$saleId]);
        $items = $itemsStmt->fetchAll();
        
        if (empty($items)) {
            return ['success' => false, 'error' => 'No items to void'];
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        $cupsRestored = false;
        $ingredientsRestored = false;
        
        // Void each item
        foreach ($items as $item) {
            // Mark as voided
            $updateStmt = $pdo->prepare("
                UPDATE sale_items 
                SET is_voided = 1, voided_at = NOW(), voided_by = ?, void_reason = ?
                WHERE sale_item_id = ?
            ");
            $updateStmt->execute([$adminId, $reason, $item['sale_item_id']]);
            
            // Restore product stock
            if ($item['product_id']) {
                restoreProductStock($item['product_id'], $item['quantity'], $saleId, $adminId);
            }
            
            // Restore cups
            if ($item['cup_id']) {
                restoreCupStock($item['cup_id'], $item['quantity'], $saleId, $item['sale_item_id'], $adminId);
                $cupsRestored = true;
            }
            
            // Restore ingredients
            if ($item['product_id'] && $item['requires_cup']) {
                restoreIngredients($item['product_id'], $item['cup_id'], $item['quantity'], $saleId, $item['sale_item_id'], $adminId);
                $ingredientsRestored = true;
            }
            
            // Record in voided_orders
            $voidStmt = $pdo->prepare("
                INSERT INTO voided_orders 
                (sale_id, sale_item_id, invoice_number, product_name, cup_size, quantity, unit_price, total_amount, 
                 cashier_id, admin_id, void_reason, cups_restored, ingredients_restored)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $voidStmt->execute([
                $saleId,
                $item['sale_item_id'],
                $sale['invoice_number'],
                $item['product_name'],
                $item['cup_size'],
                $item['quantity'],
                $item['unit_price'],
                $item['subtotal'],
                $requesterId,
                $adminId,
                $reason,
                $cupsRestored ? 1 : 0,
                $ingredientsRestored ? 1 : 0
            ]);
        }
        
        // Update sale status
        $statusStmt = $pdo->prepare("UPDATE sales SET status = 'voided' WHERE sale_id = ?");
        $statusStmt->execute([$saleId]);
        
        $pdo->commit();
        
        // Log activity
        logActivity(
            'sale_voided',
            "Voided entire sale: {$sale['invoice_number']}. Reason: $reason",
            $adminId,
            'sales',
            $saleId
        );
        
        return [
            'success' => true,
            'message' => 'Sale voided successfully',
            'items_voided' => count($items),
            'cups_restored' => $cupsRestored,
            'ingredients_restored' => $ingredientsRestored
        ];
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Void Entire Sale Error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error during void operation'];
    }
}

/**
 * Void cart before checkout (no sale record yet)
 * Just records the audit trail
 */
function voidCart(array $cartItems, int $adminId, int $requesterId, string $reason, float $totalAmount = 0): array {
    try {
        $pdo = getPDO();
        
        // Record in sale_voids table
        $cartJson = json_encode($cartItems);
        
        $stmt = $pdo->prepare("
            INSERT INTO sale_voids 
            (voided_by, requested_by, void_reason, cart_items, total_amount)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $requesterId, $reason, $cartJson, $totalAmount]);
        
        $voidId = $pdo->lastInsertId();
        
        // Log activity
        logActivity(
            'cart_voided',
            "Voided cart with " . count($cartItems) . " items. Total: ₱" . number_format($totalAmount, 2) . ". Reason: $reason",
            $adminId,
            'sale_voids',
            (int)$voidId
        );
        
        return [
            'success' => true,
            'message' => 'Cart voided successfully',
            'void_id' => $voidId
        ];
        
    } catch (Exception $e) {
        error_log("Void Cart Error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error during void operation'];
    }
}

/**
 * Update sale totals after voiding items
 */
function updateSaleTotals(int $saleId): bool {
    try {
        $pdo = getPDO();
        
        // Calculate new totals from non-voided items
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(subtotal), 0) as new_subtotal
            FROM sale_items 
            WHERE sale_id = ? AND is_voided = 0
        ");
        $stmt->execute([$saleId]);
        $result = $stmt->fetch();
        
        $newSubtotal = (float)$result['new_subtotal'];
        
        // Get sale tax rate
        $saleStmt = $pdo->prepare("SELECT tax_rate FROM sales WHERE sale_id = ?");
        $saleStmt->execute([$saleId]);
        $sale = $saleStmt->fetch();
        $taxRate = $sale['tax_rate'] ?? 12.00;
        
        $newTax = $newSubtotal * ($taxRate / 100);
        $newTotal = $newSubtotal + $newTax;
        
        // Update sale
        $updateStmt = $pdo->prepare("
            UPDATE sales 
            SET subtotal = ?, tax = ?, total_amount = ?
            WHERE sale_id = ?
        ");
        $updateStmt->execute([$newSubtotal, $newTax, $newTotal, $saleId]);
        
        // Check if all items are voided
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as remaining FROM sale_items WHERE sale_id = ? AND is_voided = 0");
        $checkStmt->execute([$saleId]);
        $check = $checkStmt->fetch();
        
        if ($check['remaining'] == 0) {
            $statusStmt = $pdo->prepare("UPDATE sales SET status = 'voided' WHERE sale_id = ?");
            $statusStmt->execute([$saleId]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Update Sale Totals Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get voided orders with pagination (for admin page)
 */
function getVoidedOrders(int $page = 1, int $limit = 5, ?string $dateFilter = null, ?string $search = null): array {
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT vo.*, 
            ua.full_name as admin_name, 
            uc.full_name as cashier_name
            FROM voided_orders vo
            LEFT JOIN users ua ON vo.authorized_by = ua.user_id
            LEFT JOIN users uc ON vo.voided_by = uc.user_id
            WHERE 1=1";
    
    $params = [];
    
    if ($dateFilter) {
        $sql .= " AND DATE(vo.created_at) = ?";
        $params[] = $dateFilter;
    }
    
    if ($search) {
        $sql .= " AND vo.void_reason LIKE ?";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
    }
    
    $sql .= " ORDER BY vo.created_at DESC LIMIT $limit OFFSET $offset";
    
    return dbFetchAll($sql, $params);
}

/**
 * Get cart voids (pre-checkout cancellations) with pagination
 */
function getCartVoids(int $page = 1, int $limit = 5, ?string $dateFilter = null): array {
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT sv.*, 
            ua.full_name as admin_name, 
            ur.full_name as requester_name
            FROM sale_voids sv
            LEFT JOIN users ua ON sv.authorized_by = ua.user_id
            LEFT JOIN users ur ON sv.voided_by = ur.user_id
            WHERE 1=1";
    
    $params = [];
    
    if ($dateFilter) {
        $sql .= " AND DATE(sv.created_at) = ?";
        $params[] = $dateFilter;
    }
    
    $sql .= " ORDER BY sv.created_at DESC LIMIT $limit OFFSET $offset";
    
    return dbFetchAll($sql, $params);
}

/**
 * Count voided orders for pagination
 */
function countVoidedOrders(?string $dateFilter = null, ?string $search = null): int {
    $sql = "SELECT COUNT(*) as total FROM voided_orders WHERE 1=1";
    $params = [];
    
    if ($dateFilter) {
        $sql .= " AND DATE(created_at) = ?";
        $params[] = $dateFilter;
    }
    
    if ($search) {
        $sql .= " AND void_reason LIKE ?";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
    }
    
    $result = dbFetchOne($sql, $params);
    return $result['total'] ?? 0;
}

/**
 * Count cart voids for pagination
 */
function countCartVoids(?string $dateFilter = null): int {
    $sql = "SELECT COUNT(*) as total FROM sale_voids WHERE 1=1";
    $params = [];
    
    if ($dateFilter) {
        $sql .= " AND DATE(created_at) = ?";
        $params[] = $dateFilter;
    }
    
    $result = dbFetchOne($sql, $params);
    return $result['total'] ?? 0;
}

/**
 * Get void statistics for dashboard
 */
function getVoidStatistics(?string $period = 'today'): array {
    $dateCondition = "";
    
    switch ($period) {
        case 'today':
            $dateCondition = "DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        default:
            $dateCondition = "1=1";
    }
    
    // Count voided items
    $itemsSql = "SELECT COUNT(*) as count, COALESCE(SUM(original_total), 0) as amount 
                 FROM voided_orders WHERE $dateCondition";
    $itemsResult = dbFetchOne($itemsSql);
    
    // Count cart voids (use voided_orders with void_type = 'cart')
    $cartsSql = "SELECT COUNT(*) as count, COALESCE(SUM(original_total), 0) as amount 
                 FROM voided_orders WHERE void_type = 'cart' AND $dateCondition";
    $cartsResult = dbFetchOne($cartsSql);
    
    return [
        'voided_items' => [
            'count' => $itemsResult['count'] ?? 0,
            'amount' => $itemsResult['amount'] ?? 0
        ],
        'voided_carts' => [
            'count' => $cartsResult['count'] ?? 0,
            'amount' => $cartsResult['amount'] ?? 0
        ],
        'total_count' => ($itemsResult['count'] ?? 0) + ($cartsResult['count'] ?? 0),
        'total_amount' => ($itemsResult['amount'] ?? 0) + ($cartsResult['amount'] ?? 0)
    ];
}
