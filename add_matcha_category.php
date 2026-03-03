<?php
require_once 'includes/config.php';

echo "<h2>🍵 Adding Matcha Category</h2>";

try {
    // Add Matcha category
    $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $category_name, $description);
    $category_name = "Matcha";
    $description = "Matcha green tea beverages and drinks";
    $stmt->execute();
    $matcha_category_id = $conn->insert_id;
    echo "✅ Added 'Matcha' category (ID: $matcha_category_id)<br>";
    
    // Add sample Matcha products
    $matcha_products = [
        ['MAT001', 'Hot Matcha Latte', $matcha_category_id, 'Traditional matcha latte with steamed milk', 45.00, 85.00, 50, 15],
        ['MAT002', 'Iced Matcha Latte', $matcha_category_id, 'Cold matcha latte over ice', 45.00, 85.00, 45, 15],
        ['MAT003', 'Matcha Frappe', $matcha_category_id, 'Blended matcha with ice and milk', 50.00, 95.00, 40, 12],
        ['MAT004', 'Pure Matcha Tea', $matcha_category_id, 'Traditional whisked matcha tea', 40.00, 75.00, 60, 20],
        ['MAT005', 'Matcha Smoothie', $matcha_category_id, 'Matcha blended with fruits', 55.00, 105.00, 35, 10]
    ];
    
    foreach ($matcha_products as $product) {
        $stmt = $conn->prepare("INSERT INTO products (product_code, product_name, category_id, description, cost_price, selling_price, stock_quantity, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisdii", ...$product);
        $stmt->execute();
        echo "🍵 Added '{$product[1]}' to Matcha category<br>";
    }
    
    echo "<br><h3>🎉 Matcha Category Added Successfully!</h3>";
    echo "<p><strong>New Matcha Products:</strong></p>";
    echo "<ul>";
    echo "<li>🍵 Hot Matcha Latte - ₱85.00</li>";
    echo "<li>🧊 Iced Matcha Latte - ₱85.00</li>";
    echo "<li>🥤 Matcha Frappe - ₱95.00</li>";
    echo "<li>🍵 Pure Matcha Tea - ₱75.00</li>";
    echo "<li>🥤 Matcha Smoothie - ₱105.00</li>";
    echo "</ul>";
    
    echo "<p><strong>All Matcha products support 12oz and 16oz cup sizes!</strong></p>";
    
    echo "<br><p><a href='pos.php' class='btn btn-primary'>Test POS with Matcha</a></p>";
    echo "<p><a href='products.php' class='btn btn-secondary'>Manage Products</a></p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    .btn {
        display: inline-block;
        padding: 10px 20px;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin: 5px 5px 5px 0;
    }
    .btn-primary { background: #007bff; }
    .btn-secondary { background: #6c757d; }
    .btn:hover { opacity: 0.8; }
    h3 { margin-top: 20px; color: #333; }
    ul { margin: 10px 0; }
    li { margin: 5px 0; }
</style>
