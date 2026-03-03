<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

// Get current page and pagination
$page = $_GET['page'] ?? 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Date filter
$date_filter = $_GET['date'] ?? '';
$where_clause = '';
$params = [];

if (!empty($date_filter)) {
    $where_clause = " WHERE DATE(s.sale_date) = ?";
}

// Get total sales count with date filter
$count_query = "SELECT COUNT(*) as count FROM sales s" . $where_clause;
if (!empty($date_filter)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("s", $date_filter);
    $count_stmt->execute();
    $total_sales = $count_stmt->get_result()->fetch_assoc()['count'];
} else {
    $total_sales = $conn->query($count_query)->fetch_assoc()['count'];
}
$total_pages = ceil($total_sales / $limit);

// Get recent sales with date filter
$sales_query = "
    SELECT s.*, u.full_name,
           (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.sale_id) as items_count
    FROM sales s 
    LEFT JOIN users u ON s.user_id = u.user_id 
    $where_clause
    ORDER BY s.sale_date DESC 
    LIMIT $limit OFFSET $offset
";

if (!empty($date_filter)) {
    $sales_stmt = $conn->prepare($sales_query);
    $sales_stmt->bind_param("s", $date_filter);
    $sales_stmt->execute();
    $sales_result = $sales_stmt->get_result();
} else {
    $sales_result = $conn->query($sales_query);
}

$sales = [];
while ($sale = $sales_result->fetch_assoc()) {
    $sales[] = [
        'sale_id' => $sale['sale_id'],
        'invoice_number' => $sale['invoice_number'],
        'customer_name' => $sale['customer_name'],
        'full_name' => $sale['full_name'],
        'subtotal' => $sale['subtotal'],
        'tax' => $sale['tax'],
        'discount' => $sale['discount'],
        'total_amount' => $sale['total_amount'],
        'payment_method' => $sale['payment_method'],
        'sale_date' => $sale['sale_date'],
        'items_count' => $sale['items_count']
    ];
}

// Calculate statistics with date filter
$today = date('Y-m-d');
$today_sales_query = "SELECT SUM(total_amount) as today_total FROM sales WHERE DATE(sale_date) = '$today'";
$today_result = $conn->query($today_sales_query);
$today_total = $today_result->fetch_assoc()['today_total'] ?? 0;

$total_sales_query = "SELECT SUM(total_amount) as total_sales, AVG(total_amount) as average_sale FROM sales" . $where_clause;
if (!empty($date_filter)) {
    $total_stmt = $conn->prepare($total_sales_query);
    $total_stmt->bind_param("s", $date_filter);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_sales_amount = $total_result->fetch_assoc()['total_sales'] ?? 0;
    $average_sale = $total_result->fetch_assoc()['average_sale'] ?? 0;
} else {
    $total_result = $conn->query($total_sales_query);
    $total_sales_amount = $total_result->fetch_assoc()['total_sales'] ?? 0;
    $average_sale = $total_result->fetch_assoc()['average_sale'] ?? 0;
}

// Prepare response
$response = [
    'success' => true,
    'sales' => $sales,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_sales,
        'current_start' => $offset + 1,
        'current_end' => min($offset + $limit, $total_sales)
    ],
    'stats' => [
        'today_total' => $today_total,
        'total_sales' => $total_sales_amount,
        'average_sale' => $average_sale
    ],
    'new_sales' => $sales, // For notification purposes
    'date_filter' => $date_filter
];

echo json_encode($response);
?>
