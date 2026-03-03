-- Add Cup Size Tracking for Coffee Products
-- Add cup_size column to products table
ALTER TABLE products ADD COLUMN cup_size ENUM('12oz', '16oz', 'none') DEFAULT 'none' AFTER category_id;

-- Add cup_size column to sale_items table
ALTER TABLE sale_items ADD COLUMN cup_size ENUM('12oz', '16oz', 'none') DEFAULT 'none' AFTER product_name;

-- Create coffee cup sizes table for pricing variations
CREATE TABLE IF NOT EXISTS coffee_cup_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    cup_size ENUM('12oz', '16oz') NOT NULL,
    price_adjustment DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Update existing coffee products to have cup sizes
UPDATE products SET cup_size = '12oz' WHERE category_id IN (SELECT category_id FROM categories WHERE category_name LIKE '%Coffee%');

-- Insert sample cup size pricing for coffee
-- This assumes you have coffee products, adjust product_id values as needed
INSERT INTO coffee_cup_sizes (product_id, cup_size, price_adjustment) VALUES 
(1, '12oz', 0.00),    -- 12oz base price
(1, '16oz', 15.00),   -- 16oz costs ₱15 more
(2, '12oz', 0.00),    -- Adjust these product_ids based on your actual coffee products
(2, '16oz', 15.00);   -- ₱15 extra for 16oz
