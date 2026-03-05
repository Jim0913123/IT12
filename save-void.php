<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pos.php');
    exit();
}

// Get form data
$void_type = $conn->real_escape_string($_POST['void_type'] ?? 'individual_item');
$item_name = $conn->real_escape_string($_POST['item_name'] ?? '');
$item_price = (float)($_POST['item_price'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);
$subtotal = (float)($_POST['subtotal'] ?? 0);
$reason = $conn->real_escape_string($_POST['reason'] ?? '');
$cart_items = $conn->real_escape_string($_POST['cart_items'] ?? '[]');

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Insert into sale_voids table
    $insertVoid = "
        INSERT INTO sale_voids 
        (voided_by, void_reason, cart_items, total_amount, voided_at)
        VALUES 
        ({$user['user_id']}, '$reason', '$cart_items', $subtotal, NOW())
    ";
    
    if (!$conn->query($insertVoid)) {
        throw new Exception("Error inserting void: " . $conn->error);
    }
    
    $void_id = $conn->insert_id;
    
    // Commit transaction
    $conn->commit();
    
    // Success - redirect back to POS
    header("Location: pos.php?void_success=1&void_id=" . $void_id);
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    // Store error and redirect back with error message
    header('Location: pos.php?void_error=' . urlencode($e->getMessage()));
    exit();
}
?>
