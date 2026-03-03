<?php
/**
 * Secure Void Item Endpoint
 * Purpose: Handle authorized void requests with admin password verification
 * Security: Uses password_verify(), prepared statements, session validation
 * Audit: Logs void operations with admin ID, reason, and timestamp
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Validate session and user is logged in
requireLogin();
$currentUser = getCurrentUser();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['sale_item_id']) || !isset($input['admin_password']) || !isset($input['void_reason'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: sale_item_id, admin_password, void_reason'
    ]);
    exit;
}

$sale_item_id = intval($input['sale_item_id']);
$admin_password = $input['admin_password'];
$void_reason = trim($input['void_reason']);

// Server-side validation of void reason (required)
if (empty($void_reason)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Void reason is required']);
    exit;
}

// Validate void reason length (prevent abuse)
if (strlen($void_reason) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Void reason is too long (max 500 characters)']);
    exit;
}

// If sale_item_id is 0 we are only validating admin credentials (e.g., void entire sale)
// otherwise fetch the sale_item record for validation
$sale_item = null;
if ($sale_item_id !== 0) {
    // Fetch the sale item to verify it exists and is not already voided
    $stmt = $conn->prepare("SELECT si.*, s.user_id as sale_cashier_id FROM sale_items si
                            JOIN sales s ON si.sale_id = s.sale_id 
                            WHERE si.sale_item_id = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }
    $stmt->bind_param('i', $sale_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sale_item = $result->fetch_assoc();
    $stmt->close();

    // Verify the sale item exists and is not already voided
    if (!$sale_item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Sale item not found']);
        exit;
    }
    if ($sale_item['is_voided'] == 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Item is already voided']);
        exit;
    }
}

// Fetch all admin users from database
$admin_result = $conn->query("SELECT user_id, password FROM users WHERE role = 'admin'");

if (!$admin_result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error fetching admins']);
    exit;
}

// Verify the provided password against admin accounts using password_verify()
$admin_verified = false;
$verified_admin_id = null;

while ($admin_user = $admin_result->fetch_assoc()) {
    // Use password_verify() for secure password verification
    if (password_verify($admin_password, $admin_user['password'])) {
        $admin_verified = true;
        $verified_admin_id = $admin_user['user_id'];
        break;
    }
}

// If password verification failed, reject the void operation
if (!$admin_verified) {
    // Log failed attempt for security audit
    error_log("SECURITY: Failed void authorization attempt by user_id=" . $currentUser['user_id'] . 
              " for sale_item_id=" . $sale_item_id . " at " . date('Y-m-d H:i:s'));
    
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid admin password']);
    exit;
}

// Admin password verified - proceed with void operation
$void_timestamp = date('Y-m-d H:i:s');

if ($sale_item_id !== 0) {
    // regular item void
    $void_stmt = $conn->prepare("UPDATE sale_items SET is_voided = 1, voided_by = ?, void_reason = ?, voided_at = ?
                                 WHERE sale_item_id = ?");
    if (!$void_stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during void operation']);
        exit;
    }
    $void_stmt->bind_param('issi', $verified_admin_id, $void_reason, $void_timestamp, $sale_item_id);
    if (!$void_stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to void item']);
        exit;
    }
    $void_stmt->close();

    // Restore stock when item is voided
    $restoreStock = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
    $restore_stmt = $conn->prepare($restoreStock);
    if ($restore_stmt) {
        $restore_stmt->bind_param('ii', $sale_item['quantity'], $sale_item['product_id']);
        $restore_stmt->execute();
        $restore_stmt->close();

        // Log stock movement (void restoration)
        $logMovement = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_id, notes, user_id, movement_date)
                        VALUES (?, 'void', ?, ?, 'Item void restoration', ?, NOW())";
        $log_stmt = $conn->prepare($logMovement);
        if ($log_stmt) {
            $log_stmt->bind_param('iiii', $sale_item['product_id'], $sale_item['quantity'], $sale_item_id, $verified_admin_id);
            $log_stmt->execute();
            $log_stmt->close();
        }
    }
} else {
    // sale-level authorization (user cancels current cart). Record audit entry.
    $cart_json = null;
    if (isset($input['cart_items'])) {
        // attempt to store a JSON representation of the cart
        $cart_json = json_encode($input['cart_items']);
    }

    $void_stmt = $conn->prepare("INSERT INTO sale_voids 
        (voided_by, requested_by, void_reason, voided_at, cart_items) 
        VALUES (?, ?, ?, ?, ?)");
    if ($void_stmt) {
        $void_stmt->bind_param('iisss', $verified_admin_id, $currentUser['user_id'], $void_reason, $void_timestamp, $cart_json);
        $void_stmt->execute();
        $void_stmt->close();
    }
    // NOTE: we don't fail the request if the audit insert fails; logging could capture later
}

// Audit log: Record successful void operation
error_log("AUDIT: Item voided - sale_item_id=" . $sale_item_id . 
          ", authorized_by_admin_id=" . $verified_admin_id . 
          ", requested_by_user_id=" . $currentUser['user_id'] . 
          ", reason=" . substr($void_reason, 0, 50) . "... at " . $void_timestamp);

// Return success response with void details
echo json_encode([
    'success' => true,
    'message' => 'Item voided successfully',
    'sale_item_id' => $sale_item_id,
    'voided_by' => $verified_admin_id,
    'voided_at' => $void_timestamp
]);
exit;
