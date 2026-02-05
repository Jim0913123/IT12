<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$sale_id = $_GET['sale_id'] ?? 0;

if (!$sale_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid sale ID']);
    exit();
}

// Get sale details
$sale_result = $conn->query("
    SELECT s.*, u.full_name 
    FROM sales s 
    LEFT JOIN users u ON s.user_id = u.user_id 
    WHERE s.sale_id = $sale_id
");

if ($sale_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit();
}

$sale = $sale_result->fetch_assoc();

// Get sale items
$items_result = $conn->query("
    SELECT * FROM sale_items WHERE sale_id = $sale_id
");

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

echo json_encode([
    'success' => true,
    'sale' => $sale,
    'items' => $items
]);
?>
