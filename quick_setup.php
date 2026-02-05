<?php
// Quick Setup - Create Database and Tables
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>
    <h1>🚀 Quick Setup</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
        try {
            // Connect to MySQL
            $conn = new mysqli('localhost', 'root', '');
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            echo "<p class='success'>✓ Connected to MySQL</p>";
            
            // Create database
            $conn->query("CREATE DATABASE IF NOT EXISTS pos_inventory");
            $conn->select_db('pos_inventory');
            echo "<p class='success'>✓ Database ready</p>";
            
            // Create tables with basic structure
            $tables = [
                "CREATE TABLE IF NOT EXISTS users (
                    user_id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    full_name VARCHAR(100) NOT NULL,
                    role ENUM('admin', 'cashier') DEFAULT 'cashier',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                "CREATE TABLE IF NOT EXISTS categories (
                    category_id INT AUTO_INCREMENT PRIMARY KEY,
                    category_name VARCHAR(100) NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                "CREATE TABLE IF NOT EXISTS products (
                    product_id INT AUTO_INCREMENT PRIMARY KEY,
                    product_code VARCHAR(50) UNIQUE NOT NULL,
                    product_name VARCHAR(200) NOT NULL,
                    category_id INT,
                    description TEXT,
                    cost_price DECIMAL(10, 2) NOT NULL,
                    selling_price DECIMAL(10, 2) NOT NULL,
                    stock_quantity INT DEFAULT 0,
                    reorder_level INT DEFAULT 10,
                    barcode VARCHAR(100),
                    image_url VARCHAR(255),
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
                )",
                
                "CREATE TABLE IF NOT EXISTS sales (
                    sale_id INT AUTO_INCREMENT PRIMARY KEY,
                    invoice_number VARCHAR(50) UNIQUE NOT NULL,
                    user_id INT,
                    customer_name VARCHAR(100),
                    customer_phone VARCHAR(20),
                    subtotal DECIMAL(10, 2) NOT NULL,
                    tax DECIMAL(10, 2) DEFAULT 0,
                    discount DECIMAL(10, 2) DEFAULT 0,
                    total_amount DECIMAL(10, 2) NOT NULL,
                    amount_paid DECIMAL(10, 2) NOT NULL,
                    change_amount DECIMAL(10, 2) DEFAULT 0,
                    payment_method ENUM('cash', 'card', 'online') DEFAULT 'cash',
                    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
                )",
                
                "CREATE TABLE IF NOT EXISTS sale_items (
                    sale_item_id INT AUTO_INCREMENT PRIMARY KEY,
                    sale_id INT,
                    product_id INT,
                    quantity INT NOT NULL,
                    unit_price DECIMAL(10, 2) NOT NULL,
                    subtotal DECIMAL(10, 2) NOT NULL,
                    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
                )"
            ];
            
            foreach ($tables as $sql) {
                if ($conn->query($sql)) {
                    echo "<p class='success'>✓ Table created</p>";
                } else {
                    echo "<p class='error'>✗ Table error: " . $conn->error . "</p>";
                }
            }
            
            // Insert admin user
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
            $conn->query("DELETE FROM users WHERE username = 'admin'");
            $conn->query("INSERT INTO users (username, password, full_name, role) VALUES ('admin', '$passwordHash', 'Administrator', 'admin')");
            echo "<p class='success'>✓ Admin user created</p>";
            
            // Insert sample categories
            $conn->query("DELETE FROM categories");
            $categories = [
                "('Coffee Drinks', 'Hot and cold coffee beverages')",
                "('Pastries', 'Fresh baked goods and desserts')",
                "('Burgers', 'Gourmet burgers and sandwiches')",
                "('Other Beverages', 'Tea, juices, and specialty drinks')",
                "('Snacks', 'Light snacks and sides')"
            ];
            foreach ($categories as $cat) {
                $conn->query("INSERT INTO categories (category_name, description) VALUES $cat");
            }
            echo "<p class='success'>✓ Coffee shop categories added</p>";
            
            // Insert sample products
            $conn->query("DELETE FROM products");
            $products = [
                "('COF001', 'Espresso', 1, 'Rich and bold espresso shot', 25.00, 45.00, 100, 20, 'COF001', '', 'active')",
                "('COF002', 'Cappuccino', 1, 'Classic Italian cappuccino with foam', 35.00, 65.00, 80, 15, 'COF002', '', 'active')",
                "('COF003', 'Latte', 1, 'Smooth and creamy latte with milk', 40.00, 75.00, 90, 20, 'COF003', '', 'active')",
                "('COF004', 'Americano', 1, 'Espresso with hot water', 30.00, 55.00, 85, 15, 'COF004', '', 'active')",
                "('COF005', 'Mocha', 1, 'Chocolate espresso drink', 45.00, 85.00, 70, 10, 'COF005', '', 'active')",
                "('PAS001', 'Croissant', 2, 'Buttery French croissant', 20.00, 35.00, 50, 10, 'PAS001', '', 'active')",
                "('PAS002', 'Muffin', 2, 'Fresh baked muffin (blueberry/chocolate)', 15.00, 28.00, 60, 15, 'PAS002', '', 'active')",
                "('PAS003', 'Danish', 2, 'Sweet pastry with fruit filling', 25.00, 45.00, 40, 8, 'PAS003', '', 'active')",
                "('BUR001', 'Classic Burger', 3, 'Beef patty with lettuce and tomato', 60.00, 120.00, 30, 5, 'BUR001', '', 'active')",
                "('BUR002', 'Cheese Burger', 3, 'Classic burger with cheese', 70.00, 140.00, 25, 5, 'BUR002', '', 'active')"
            ];
            foreach ($products as $product) {
                $conn->query("INSERT INTO products (product_code, product_name, category_id, description, cost_price, selling_price, stock_quantity, reorder_level, barcode, image_url, status) VALUES $product");
            }
            echo "<p class='success'>✓ Sample products added</p>";
            
            echo "<h2 class='success'>🎉 Setup Complete!</h2>";
            echo "<p><strong>Login:</strong> admin / admin123</p>";
            echo "<p><a href='login.php' class='btn btn-success'>Go to Login</a></p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        }
    } else {
        ?>
        <div class="error">
            <h3>⚠️ System Not Setup</h3>
            <p>Your database tables are missing. Click below to set up everything quickly.</p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="setup" value="1">
            <button type="submit" class="btn btn-success">🚀 Quick Setup Database</button>
        </form>
        
        <h3>What this does:</h3>
        <ul>
            <li>✅ Creates database and all tables</li>
            <li>✅ Creates admin user (admin/admin123)</li>
            <li>✅ Adds sample coffee shop data</li>
            <li>✅ Fixes all database issues</li>
        </ul>
        <?php
    }
    ?>
</body>
</html>
