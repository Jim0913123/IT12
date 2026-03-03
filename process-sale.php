<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Get form data
$customer_name = $_POST['customer_name'] ?? '';
$customer_phone = $_POST['customer_phone'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cash';
$subtotal = floatval($_POST['subtotal'] ?? 0);
$tax = floatval($_POST['tax'] ?? 0);
$discount = floatval($_POST['discount'] ?? 0);
$total = floatval($_POST['total'] ?? 0);
$paid = floatval($_POST['paid'] ?? 0);
$change = floatval($_POST['change'] ?? 0);
$cart_items = json_decode($_POST['cart_items'] ?? '[]', true);

// Validate cart items
if (empty($cart_items)) {
    die('Error: Cart is empty');
}

// Process sale
$conn->begin_transaction();

try {
    // Generate invoice number
    $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert sale
    $stmt = $conn->prepare("
        INSERT INTO sales (invoice_number, user_id, customer_name, customer_phone, subtotal, tax, discount, total_amount, amount_paid, change_amount, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("sissdddddds", 
        $invoice_number, 
        $user['user_id'], 
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
    
    // Process sale items and update stock with cup tracking
    foreach ($cart_items as $item) {
        $product_id = $item['id'];
        $product_name = $item['name'];
        $quantity = $item['quantity'];
        $unit_price = $item['price'];
        $item_subtotal = $item['subtotal'];
        $cup_size = $item['cupSize'] ?? 'none';
        
        // Insert sale item with cup tracking
        $stmt_item = $conn->prepare("
            INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, subtotal, cup_size) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt_item->bind_param("iisidss", 
            $sale_id, 
            $product_id, 
            $product_name, 
            $quantity, 
            $unit_price, 
            $item_subtotal, 
            $cup_size
        );
        $stmt_item->execute();
        
        // Update product inventory
        $conn->query("UPDATE products SET stock_quantity = stock_quantity - {$item['quantity']} WHERE product_id = {$item['id']}");
        
        // Track cup usage if it's a drink with cup size
        if ($cup_size !== 'none') {
            // Record cup movement
            $cup_movement = $conn->prepare("
                INSERT INTO cup_movements (cup_size, movement_type, quantity, reference_id, reference_type, notes, user_id) 
                VALUES (?, 'sale', ?, ?, 'sale', ?, ?)
            ");
            $reference = $sale_id;
            $notes = "Used " . $quantity . " x " . $cup_size . " cups";
            $cup_movement->bind_param("sisis", $cup_size, $quantity, $reference, $notes, $user['user_id']);
            $cup_movement->execute();
        }
        
        // Record stock movement for product
        $product_movement = $conn->prepare("
            INSERT INTO stock_movements (product_id, movement_type, quantity, reference, notes, user_id) 
            VALUES (?, 'out', ?, ?, ?, ?)
        ");
        $product_notes = "Sold " . $quantity . " x " . $product_name . ($cup_size !== 'none' ? " (" . $cup_size . ")" : "");
        $product_movement->bind_param("iisss", $product_id, $quantity, $reference, $product_notes, $user['user_id']);
        $product_movement->execute();
    }
    
    $conn->commit();
    
    // Redirect to receipt
    header('Location: receipt.php?invoice=' . $invoice_number . '&success=1');
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    die('Error processing sale: ' . $e->getMessage());
}
?>
