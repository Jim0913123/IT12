<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Test data
$test_sale_data = [
    'customer_name' => 'Test Customer',
    'customer_phone' => '',
    'payment_method' => 'cash',
    'subtotal' => 100.00,
    'tax' => 12.00,
    'discount' => 0.00,
    'total' => 112.00,
    'paid' => 120.00,
    'change' => 8.00,
    'items' => [
        [
            'id' => 1,
            'name' => 'Test Product',
            'quantity' => 1,
            'price' => 100.00,
            'subtotal' => 100.00
        ]
    ]
];

echo "<h2>Sale Processing Debug Test</h2>";
echo "<h3>Input Data:</h3>";
echo "<pre>" . print_r($test_sale_data, true) . "</pre>";

// Test the sale processing
$conn->begin_transaction();

try {
    // Generate invoice number
    $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    echo "<h3>Invoice Number: $invoice_number</h3>";
    
    // Insert sale
    $stmt = $conn->prepare("
        INSERT INTO sales (invoice_number, user_id, customer_name, customer_phone, subtotal, tax, discount, total_amount, amount_paid, change_amount, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $user_id = $_SESSION['user_id'];
    $customer_name = $test_sale_data['customer_name'];
    $customer_phone = $test_sale_data['customer_phone'];
    $subtotal = $test_sale_data['subtotal'];
    $tax = $test_sale_data['tax'];
    $discount = $test_sale_data['discount'];
    $total = $test_sale_data['total'];
    $paid = $test_sale_data['paid'];
    $change = $test_sale_data['change'];
    $payment_method = $test_sale_data['payment_method'];
    
    echo "<h3>Binding Parameters:</h3>";
    echo "<pre>";
    echo "invoice_number: $invoice_number\n";
    echo "user_id: $user_id\n";
    echo "customer_name: $customer_name\n";
    echo "customer_phone: $customer_phone\n";
    echo "subtotal: $subtotal\n";
    echo "tax: $tax\n";
    echo "discount: $discount\n";
    echo "total: $total\n";
    echo "paid: $paid\n";
    echo "change: $change\n";
    echo "payment_method: $payment_method\n";
    echo "</pre>";
    
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
    
    if ($stmt->execute()) {
        echo "<h3 style='color: green;'>✓ Sale inserted successfully</h3>";
        $sale_id = $conn->insert_id;
        echo "<p>Sale ID: $sale_id</p>";
    } else {
        echo "<h3 style='color: red;'>✗ Sale insertion failed</h3>";
        echo "<p>Error: " . $stmt->error . "</p>";
    }
    
    $conn->rollback(); // Rollback for testing
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<h3 style='color: red;'>✗ Exception occurred</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Test database connection
echo "<h3>Database Connection Test:</h3>";
if ($conn->ping()) {
    echo "<p style='color: green;'>✓ Database connection is active</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection lost</p>";
}

// Test if tables exist
echo "<h3>Table Existence Test:</h3>";
$tables = ['sales', 'sale_items', 'products', 'stock_movements'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' missing</p>";
    }
}

// Test if user is logged in
echo "<h3>Session Test:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✓ User logged in: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='color: red;'>✗ User not logged in</p>";
}
?>

<p><a href="pos.php">← Back to POS</a></p>
