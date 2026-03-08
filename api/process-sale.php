<?php
/**
 * Process Sale Endpoint
 * Features:
 * - Full inventory integration (cups, ingredients, products)
 * - Stock validation before checkout
 * - Prepared statements for SQL injection prevention
 * - Activity logging
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/inventory_functions.php';

// Security checks
requireLogin();

// Verify CSRF token for API request
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verifyCSRFToken($csrfToken)) {
    logActivity('api_csrf_violation', 'Invalid CSRF token in process-sale API');
    jsonError('Invalid security token. Please refresh the page.', 403);
}

// Require permission to create sales
if (!hasPermission('create_sales')) {
    logActivity('unauthorized_sale_attempt', 'User attempted sale without permission');
    jsonError('You do not have permission to process sales.', 403);
}

$user = getCurrentUser();

try {
    // Get JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        throw new Exception("Invalid sale data received.");
    }

    if (empty($data['items'])) {
        throw new Exception("Cart is empty.");
    }

    $pdo = getPDO();
    
    // Validate stock availability before processing
    $stockErrors = [];
    foreach ($data['items'] as $item) {
        $productId = sanitizeInt($item['id']);
        $cupId = isset($item['cup_id']) ? sanitizeInt($item['cup_id']) : null;
        $quantity = sanitizeInt($item['quantity']);
        
        $errors = checkCartItemAvailability($productId, $cupId, $quantity);
        if (!empty($errors)) {
            $stockErrors = array_merge($stockErrors, $errors);
        }
    }
    
    if (!empty($stockErrors)) {
        throw new Exception("Stock unavailable: " . implode("; ", $stockErrors));
    }

    // Start transaction
    $pdo->beginTransaction();

    // Generate invoice number
    $invoice = 'INV-' . date('YmdHis');

    $customer_name = sanitize($data['customer_name'] ?? '');
    $payment_method = sanitize($data['payment_method'] ?? 'cash');
    $subtotal = sanitizeFloat($data['subtotal']);
    $tax = sanitizeFloat($data['tax']);
    $discount = sanitizeFloat($data['discount'] ?? 0);
    $total = sanitizeFloat($data['total']);
    $amountPaid = sanitizeFloat($data['amount_paid'] ?? $total);
    $change = sanitizeFloat($data['change'] ?? 0);

    // Insert into sales table
    $saleStmt = $pdo->prepare("
        INSERT INTO sales 
        (invoice_number, customer_name, user_id, subtotal, tax, discount, total_amount, 
         amount_paid, change_amount, payment_method, sale_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $saleStmt->execute([
        $invoice, $customer_name, $user['user_id'], $subtotal, $tax, $discount, 
        $total, $amountPaid, $change, $payment_method
    ]);

    $sale_id = (int)$pdo->lastInsertId();

    // Process each item
    foreach ($data['items'] as $item) {
        $product_id = sanitizeInt($item['id']);
        $quantity = sanitizeInt($item['quantity']);
        $price = sanitizeFloat($item['price']);
        $item_subtotal = sanitizeFloat($item['subtotal']);
        $cup_id = isset($item['cup_id']) ? sanitizeInt($item['cup_id']) : null;
        $cup_size = sanitize($item['cup_size'] ?? '');
        $product_name = sanitize($item['name'] ?? '');
        
        // Get product info
        $productStmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $productStmt->execute([$product_id]);
        $product = $productStmt->fetch();
        
        if (!$product) {
            throw new Exception("Product not found: $product_id");
        }
        
        // Use product name from DB if not provided
        if (empty($product_name)) {
            $product_name = $product['product_name'];
        }
        
        // Check if product requires cup (fallback to is_drink if requires_cup doesn't exist)
        $requiresCup = isset($product['requires_cup']) ? $product['requires_cup'] : ($product['is_drink'] ?? false);

        // Insert into sale_items
        $itemStmt = $pdo->prepare("
            INSERT INTO sale_items 
            (sale_id, product_id, product_name, cup_id, cup_size, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $itemStmt->execute([$sale_id, $product_id, $product_name, $cup_id, $cup_size, $quantity, $price, $item_subtotal]);
        
        $sale_item_id = (int)$pdo->lastInsertId();

        // Deduct product stock (for non-beverage items)
        if (!$requiresCup) {
            deductProductStock($product_id, $quantity, $sale_id, $user['user_id']);
        }

        // Deduct cup stock if applicable
        if ($cup_id && $requiresCup) {
            if (!deductCupStock($cup_id, $quantity, $sale_id, $sale_item_id, $user['user_id'])) {
                throw new Exception("Failed to deduct cup stock for: " . $product_name);
            }
        }

        // Deduct ingredients if product uses them
        if ($requiresCup) {
            deductIngredients($product_id, $cup_id, $quantity, $sale_id, $sale_item_id, $user['user_id']);
        }
    }

    // Commit transaction
    $pdo->commit();

    // Log the sale
    logActivity('sale_completed', "Sale completed: $invoice, Total: ₱" . number_format($total, 2), $user['user_id'], 'sales', $sale_id);

    echo json_encode([
        "success" => true,
        "invoice" => $invoice,
        "sale_id" => $sale_id,
        "message" => "Sale completed successfully"
    ]);

} catch (Exception $e) {
    // Rollback if something fails
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Process Sale Error: " . $e->getMessage());

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

exit;
