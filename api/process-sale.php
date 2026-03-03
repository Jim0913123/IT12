<?php
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';

requireLogin();
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

    // Start transaction
    $conn->begin_transaction();

    // Generate invoice number
    $invoice = 'INV-' . date('YmdHis');

    $customer_name = $conn->real_escape_string($data['customer_name'] ?? '');
    $payment_method = $conn->real_escape_string($data['payment_method']);
    $subtotal = (float)$data['subtotal'];
    $tax = (float)$data['tax'];
    $discount = (float)$data['discount'];
    $total = (float)$data['total'];

    // Insert into sales table
    $insertSale = "
        INSERT INTO sales 
        (invoice_number, customer_name, user_id, subtotal, tax, discount, total_amount, payment_method, sale_date)
        VALUES 
        ('$invoice', '$customer_name', {$user['user_id']}, $subtotal, $tax, $discount, $total, '$payment_method', NOW())
    ";

    if (!$conn->query($insertSale)) {
        throw new Exception("Error inserting sale: " . $conn->error);
    }

    $sale_id = $conn->insert_id;

    // Insert sale items and update stock
    foreach ($data['items'] as $item) {

        $product_id = (int)$item['id'];
        $quantity = (int)$item['quantity'];
        $price = (float)$item['price'];
        $item_subtotal = (float)$item['subtotal'];

        // Insert into sale_items
        $insertItem = "
            INSERT INTO sale_items 
            (sale_id, product_id, quantity, unit_price, subtotal)
            VALUES
            ($sale_id, $product_id, $quantity, $price, $item_subtotal)
        ";

        if (!$conn->query($insertItem)) {
            throw new Exception("Error inserting sale item: " . $conn->error);
        }

        // Update product stock
        $updateStock = "
            UPDATE products 
            SET stock_quantity = stock_quantity - $quantity
            WHERE product_id = $product_id
        ";

        if (!$conn->query($updateStock)) {
            throw new Exception("Error updating stock: " . $conn->error);
        }

        // Log stock movement (deduction)
        $logMovement = "
            INSERT INTO stock_movements 
            (product_id, movement_type, quantity, reference_id, notes, user_id, movement_date)
            VALUES
            ($product_id, 'sale', $quantity, $sale_id, 'Sale deduction', {$user['user_id']}, NOW())
        ";

        if (!$conn->query($logMovement)) {
            throw new Exception("Error logging stock movement: " . $conn->error);
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        "success" => true,
        "invoice" => $invoice
    ]);

} catch (Exception $e) {

    // Rollback if something fails
    if ($conn->errno) {
        $conn->rollback();
    }

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

exit;
