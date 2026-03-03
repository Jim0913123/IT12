-- POS and Inventory System Database Schema
-- Create Database
CREATE DATABASE IF NOT EXISTS pos_inventory;
USE pos_inventory;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'cashier') DEFAULT 'cashier',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    category_id INT,
    description TEXT,
    cost_price DECIMAL(10, 2) NOT NULL,
    selling_price DECIMAL(10, 2) NOT NULL,
    size VARCHAR(20),
    stock_quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    barcode VARCHAR(100),
    image_url VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- Sales Table
CREATE TABLE IF NOT EXISTS sales (
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
);

-- Sale Items Table
CREATE TABLE IF NOT EXISTS sale_items (
    sale_item_id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT,
    product_id INT,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
);

-- Voided Sales Audit Table (records automated cart cancellations)
CREATE TABLE IF NOT EXISTS sale_voids (
    void_id INT AUTO_INCREMENT PRIMARY KEY,
    voided_at DATETIME NOT NULL,
    voided_by INT,
    requested_by INT,
    void_reason TEXT NOT NULL,
    cart_items TEXT,
    FOREIGN KEY (voided_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Stock Movements Table
CREATE TABLE IF NOT EXISTS stock_movements (
    movement_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    movement_type ENUM('in', 'out', 'adjustment', 'sale', 'void') NOT NULL,
    quantity INT NOT NULL,
    reference_id INT,
    notes TEXT,
    user_id INT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);
);

-- Insert Default Admin User (password: admin123)
INSERT INTO users (username, password, full_name, role) VALUES 
('admin', '$2y$10$uHm2WQdwmsWtwJBg3MdOQ.UxAOoySjsZwDqIXG4zM9F6DWdIjiUM2', 'Administrator', 'admin'),
('cashier', '$2y$10$uHm2WQdwmsWtwJBg3MdOQ.UxAOoySjsZwDqIXG4zM9F6DWdIjiUM2', 'Cashier User', 'cashier');

-- Insert Sample Categories
INSERT INTO categories (category_name, description) VALUES 
('Coffee Drinks', 'Hot and cold coffee beverages'),
('Pastries', 'Fresh baked goods and desserts'),
('Burgers', 'Gourmet burgers and sandwiches'),
('Other Beverages', 'Tea, juices, and specialty drinks'),
('Snacks', 'Light snacks and sides');

-- Insert Sample Products
INSERT INTO products (product_code, product_name, category_id, description, cost_price, selling_price, stock_quantity, reorder_level) VALUES 
('COF001', 'Espresso', 1, 'Rich and bold espresso shot', 25.00, 45.00, 100, 20),
('COF002', 'Cappuccino', 1, 'Classic Italian cappuccino with foam', 35.00, 65.00, 80, 15),
('COF003', 'Latte', 1, 'Smooth and creamy latte with milk', 40.00, 75.00, 90, 20),
('COF004', 'Americano', 1, 'Espresso with hot water', 30.00, 55.00, 85, 15),
('COF005', 'Mocha', 1, 'Chocolate espresso drink', 45.00, 85.00, 70, 10),
('COF006', 'Iced Coffee', 1, 'Cold brewed coffee over ice', 35.00, 60.00, 75, 15),
('COF007', 'Caramel Macchiato', 1, 'Espresso with caramel and milk', 50.00, 90.00, 60, 10),
('COF008', 'Flat White', 1, 'Smooth microfoam coffee', 40.00, 70.00, 65, 12),

('PAS001', 'Croissant', 2, 'Buttery French croissant', 20.00, 35.00, 50, 10),
('PAS002', 'Muffin', 2, 'Fresh baked muffin (blueberry/chocolate)', 15.00, 28.00, 60, 15),
('PAS003', 'Danish', 2, 'Sweet pastry with fruit filling', 25.00, 45.00, 40, 8),
('PAS004', 'Bagel', 2, 'Fresh baked bagel with cream cheese', 18.00, 32.00, 45, 10),
('PAS005', 'Cinnamon Roll', 2, 'Sweet cinnamon roll with icing', 22.00, 40.00, 35, 8),
('PAS006', 'Donut', 2, 'Glazed or filled donuts', 12.00, 25.00, 70, 20),
('PAS007', 'Cookie', 2, 'Fresh baked chocolate chip cookie', 10.00, 18.00, 80, 25),
('PAS008', 'Brownie', 2, 'Rich chocolate brownie', 20.00, 35.00, 30, 6),

('BUR001', 'Classic Burger', 3, 'Beef patty with lettuce and tomato', 60.00, 120.00, 30, 5),
('BUR002', 'Cheese Burger', 3, 'Classic burger with cheese', 70.00, 140.00, 25, 5),
('BUR003', 'Bacon Burger', 3, 'Burger with crispy bacon', 85.00, 160.00, 20, 4),
('BUR004', 'Chicken Burger', 3, 'Grilled chicken breast burger', 75.00, 145.00, 22, 5),
('BUR005', 'Veggie Burger', 3, 'Vegetarian burger patty', 65.00, 125.00, 18, 4),

('BEV001', 'Hot Chocolate', 4, 'Rich hot chocolate drink', 20.00, 35.00, 80, 15),
('BEV002', 'Green Tea', 4, 'Fresh brewed green tea', 25.00, 40.00, 60, 12),
('BEV003', 'Orange Juice', 4, 'Fresh squeezed orange juice', 30.00, 50.00, 40, 10),
('BEV004', 'Lemonade', 4, 'Fresh homemade lemonade', 25.00, 45.00, 50, 12),

('SNK001', 'French Fries', 5, 'Crispy golden french fries', 15.00, 30.00, 100, 20),
('SNK002', 'Onion Rings', 5, 'Breaded and fried onion rings', 20.00, 40.00, 40, 8),
('SNK003', 'Chicken Wings', 5, 'Crispy fried chicken wings', 45.00, 85.00, 25, 5);
