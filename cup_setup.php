<?php
require_once 'includes/config.php';

echo "<h2>🥤 Setting up Cup Inventory System...</h2>";

try {
    // Create cup_inventory table
    $sql1 = "CREATE TABLE IF NOT EXISTS cup_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cup_size ENUM('12oz', '16oz') NOT NULL,
        stock_quantity INT DEFAULT 0,
        reorder_level INT DEFAULT 50,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_cup_size (cup_size)
    )";
    
    if ($conn->query($sql1)) {
        echo "✅ Cup inventory table created/verified<br>";
    } else {
        echo "❌ Error creating cup inventory table: " . $conn->error . "<br>";
    }
    
    // Create cup_movements table
    $sql2 = "CREATE TABLE IF NOT EXISTS cup_movements (
        movement_id INT AUTO_INCREMENT PRIMARY KEY,
        cup_size ENUM('12oz', '16oz') NOT NULL,
        movement_type ENUM('sale', 'restock', 'adjustment', 'waste') NOT NULL,
        quantity INT NOT NULL,
        reference_id INT NULL,
        reference_type VARCHAR(50) NULL,
        notes TEXT NULL,
        user_id INT NULL,
        movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    )";
    
    if ($conn->query($sql2)) {
        echo "✅ Cup movements table created/verified<br>";
    } else {
        echo "❌ Error creating cup movements table: " . $conn->error . "<br>";
    }
    
    // Insert initial cup inventory data
    $sql3 = "INSERT INTO cup_inventory (cup_size, stock_quantity, reorder_level) VALUES 
        ('12oz', 100, 50),
        ('16oz', 100, 50)
        ON DUPLICATE KEY UPDATE 
        stock_quantity = VALUES(stock_quantity),
        reorder_level = VALUES(reorder_level)";
    
    if ($conn->query($sql3)) {
        echo "✅ Initial cup inventory data inserted<br>";
    } else {
        echo "❌ Error inserting initial data: " . $conn->error . "<br>";
    }
    
    // Drop existing triggers if they exist
    $conn->query("DROP TRIGGER IF EXISTS reduce_cup_inventory_on_sale");
    $conn->query("DROP TRIGGER IF EXISTS restore_cup_inventory_on_sale_delete");
    
    // Create trigger to automatically reduce cup inventory when sale is made
    $sql4 = "CREATE TRIGGER reduce_cup_inventory_on_sale
        AFTER INSERT ON sale_items
        FOR EACH ROW
        BEGIN
            -- Only reduce cup inventory if cup_size is not 'none'
            IF NEW.cup_size != 'none' THEN
                -- Update cup inventory
                UPDATE cup_inventory 
                SET stock_quantity = stock_quantity - NEW.quantity,
                    last_updated = NOW()
                WHERE cup_size = NEW.cup_size;
                
                -- Record cup movement
                INSERT INTO cup_movements (cup_size, movement_type, quantity, reference_id, reference_type, user_id)
                VALUES (NEW.cup_size, 'sale', NEW.quantity, NEW.sale_id, 'sale', 
                        (SELECT user_id FROM sales WHERE sale_id = NEW.sale_id));
            END IF;
        END";
    
    if ($conn->query($sql4)) {
        echo "✅ Sale trigger created<br>";
    } else {
        echo "❌ Error creating sale trigger: " . $conn->error . "<br>";
    }
    
    // Create trigger to restore cup inventory when sale is deleted/voided
    $sql5 = "CREATE TRIGGER restore_cup_inventory_on_sale_delete
        AFTER DELETE ON sale_items
        FOR EACH ROW
        BEGIN
            -- Only restore cup inventory if cup_size is not 'none'
            IF OLD.cup_size != 'none' THEN
                -- Update cup inventory
                UPDATE cup_inventory 
                SET stock_quantity = stock_quantity + OLD.quantity,
                    last_updated = NOW()
                WHERE cup_size = OLD.cup_size;
                
                -- Record cup movement (negative quantity for restoration)
                INSERT INTO cup_movements (cup_size, movement_type, quantity, reference_id, reference_type, notes, user_id)
                VALUES (OLD.cup_size, 'sale', -OLD.quantity, OLD.sale_id, 'void', 'Sale voided - cups restored', 
                        (SELECT user_id FROM sales WHERE sale_id = OLD.sale_id));
            END IF;
        END";
    
    if ($conn->query($sql5)) {
        echo "✅ Void trigger created<br>";
    } else {
        echo "❌ Error creating void trigger: " . $conn->error . "<br>";
    }
    
    echo "<br><h3>🎉 Cup Inventory System Setup Complete!</h3>";
    echo "<p>You can now access the cup inventory from the sidebar menu.</p>";
    echo "<p><a href='cup_inventory.php' class='btn btn-primary'>Go to Cup Inventory</a></p>";
    
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
        background: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 20px;
    }
    .btn:hover {
        background: #0056b3;
    }
</style>
