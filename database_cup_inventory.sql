-- Cup Inventory System
-- Create cup inventory table to track cup stock
CREATE TABLE IF NOT EXISTS cup_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cup_size ENUM('12oz', '16oz') NOT NULL,
    stock_quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 50,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cup_size (cup_size)
);

-- Insert initial cup inventory data
INSERT INTO cup_inventory (cup_size, stock_quantity, reorder_level) VALUES 
('12oz', 100, 50),
('16oz', 100, 50)
ON DUPLICATE KEY UPDATE 
stock_quantity = VALUES(stock_quantity),
reorder_level = VALUES(reorder_level);

-- Create cup movement tracking table
CREATE TABLE IF NOT EXISTS cup_movements (
    movement_id INT AUTO_INCREMENT PRIMARY KEY,
    cup_size ENUM('12oz', '16oz') NOT NULL,
    movement_type ENUM('sale', 'restock', 'adjustment', 'waste') NOT NULL,
    quantity INT NOT NULL,
    reference_id INT NULL, -- Can reference sale_id or other transaction
    reference_type VARCHAR(50) NULL, -- 'sale', 'manual', etc.
    notes TEXT NULL,
    user_id INT NULL,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Create trigger to automatically reduce cup inventory when sale is made
DELIMITER //

CREATE TRIGGER reduce_cup_inventory_on_sale
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
END//

-- Create trigger to restore cup inventory when sale is deleted/voided
CREATE TRIGGER restore_cup_inventory_on_sale_delete
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
END//

DELIMITER ;
