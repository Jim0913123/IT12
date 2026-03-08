<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'coffee_shop_pos');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed. Please try again later.");
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>
