<?php
require_once 'includes/config.php';

echo "<h2>☕ Adding Hot Coffee & Ice Coffee Categories</h2>";

try {
    // Add Hot Coffee category
    $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $hot_name, $hot_desc);
    $hot_name = "Hot Coffee";
    $hot_desc = "Hot coffee beverages and espresso drinks";
    $stmt->execute();
    $hot_coffee_id = $conn->insert_id;
    echo "✅ Added 'Hot Coffee' category (ID: $hot_coffee_id)<br>";
    
    // Add Ice Coffee category
    $ice_name = "Ice Coffee";
    $ice_desc = "Cold coffee beverages and iced drinks";
    $stmt->bind_param("ss", $ice_name, $ice_desc);
    $stmt->execute();
    $ice_coffee_id = $conn->insert_id;
    echo "✅ Added 'Ice Coffee' category (ID: $ice_coffee_id)<br>";
    
    // Get the old Coffee Drinks category ID
    $old_category_result = $conn->query("SELECT category_id FROM categories WHERE category_name = 'Coffee Drinks'");
    $old_category = $old_category_result->fetch_assoc();
    $old_category_id = $old_category['category_id'];
    
    if ($old_category_id) {
        echo "📋 Found old 'Coffee Drinks' category (ID: $old_category_id)<br>";
        
        // Update hot coffee products to Hot Coffee category
        $hot_products = ['Espresso', 'Cappuccino', 'Latte', 'Americano', 'Mocha', 'Caramel Macchiato', 'Flat White'];
        foreach ($hot_products as $product) {
            $stmt = $conn->prepare("UPDATE products SET category_id = ? WHERE product_name = ?");
            $stmt->bind_param("is", $hot_coffee_id, $product);
            $stmt->execute();
            echo "☕ Moved '$product' to Hot Coffee category<br>";
        }
        
        // Update iced coffee products to Ice Coffee category
        $ice_products = ['Iced Coffee'];
        foreach ($ice_products as $product) {
            $stmt = $conn->prepare("UPDATE products SET category_id = ? WHERE product_name = ?");
            $stmt->bind_param("is", $ice_coffee_id, $product);
            $stmt->execute();
            echo "🧊 Moved '$product' to Ice Coffee category<br>";
        }
        
        // Optionally delete the old Coffee Drinks category if it's now empty
        $check_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = $old_category_id");
        $count = $check_products->fetch_assoc()['count'];
        
        if ($count == 0) {
            $conn->query("DELETE FROM categories WHERE category_id = $old_category_id");
            echo "🗑️ Removed old 'Coffee Drinks' category (empty)<br>";
        } else {
            echo "⚠️ Kept old 'Coffee Drinks' category (still has $count products)<br>";
        }
    }
    
    echo "<br><h3>🎉 Categories Added Successfully!</h3>";
    echo "<p><strong>New Categories:</strong></p>";
    echo "<ul>";
    echo "<li>☕ Hot Coffee - For hot coffee beverages</li>";
    echo "<li>🧊 Ice Coffee - For cold coffee drinks</li>";
    echo "</ul>";
    
    echo "<br><p><a href='pos.php' class='btn btn-primary'>Test POS with New Categories</a></p>";
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
