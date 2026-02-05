<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($input['items'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit();
}

$conn->begin_transaction();

try {
    // Generate invoice number
    $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert sale
    $stmt = $conn->prepare("
        INSERT INTO sales (invoice_number, user_id, customer_name, customer_phone, subtotal, tax, discount, total_amount, amount_paid, change_amount, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $user_id = $_SESSION['user_id'];
    $customer_name = $input['customer_name'] ?: null;
    $customer_phone = $input['customer_phone'] ?: null;
    $subtotal = $input['subtotal'];
    $tax = $input['tax'];
    $discount = $input['discount'];
    $total = $input['total'];
    $paid = $input['paid'];
    $change = $input['change'];
    $payment_method = $input['payment_method'];
    
    $stmt->bind_param("sissddddds", 
        $invoice_number, 
        $user_id, 
        $customer_name, 
        $customer_phone, 
        $subtotal, 
        $tax, 
        $discount, 
        $total, 
        $paid, 
        $change, 
        $payment_method
    );
    
    $stmt->execute();
    $sale_id = $conn->insert_id;
    
    // Insert sale items and update stock
    $stmt_item = $conn->prepare("
        INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, subtotal) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt_stock = $conn->prepare("
        UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?
    ");
    
    $stmt_movement = $conn->prepare("
        INSERT INTO stock_movements (product_id, movement_type, quantity, reference, user_id) 
        VALUES (?, 'out', ?, ?, ?)
    ");
    
    foreach ($input['items'] as $item) {
        // Insert sale item
        $stmt_item->bind_param("iisidd", 
            $sale_id, 
            $item['id'], 
            $item['name'], 
            $item['quantity'], 
            $item['price'], 
            $item['subtotal']
        );
        $stmt_item->execute();
        
        // Update stock
        $stmt_stock->bind_param("ii", $item['quantity'], $item['id']);
        $stmt_stock->execute();
        
        // Record stock movement
        $stmt_movement->bind_param("iisi", 
            $item['id'], 
            $item['quantity'], 
            $invoice_number, 
            $user_id
        );
        $stmt_movement->execute();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Sale completed successfully',
        'invoice' => $invoice_number,
        'sale_id' => $sale_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
