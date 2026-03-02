<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// filter by date
$where = "WHERE s.is_voided = 1";
$filter_params = "";

if (!empty($_GET['date'])) {
    $date = $conn->real_escape_string($_GET['date']);
    $where .= " AND DATE(s.voided_at) = '$date'";
    $filter_params = "&date=" . urlencode($_GET['date']);
}

// total count
$countQuery = "SELECT COUNT(*) as count FROM sales s $where";
$total = $conn->query($countQuery)->fetch_assoc()['count'];
$total_pages = ceil($total / $limit);

// main query
$query = "
    SELECT s.*, 
           ua.full_name AS admin_name,
           ur.full_name AS requester_name
    FROM sales s
    LEFT JOIN users ua ON s.voided_by = ua.user_id
    LEFT JOIN users ur ON s.user_id = ur.user_id
    $where
    ORDER BY s.voided_at DESC
    LIMIT $limit OFFSET $offset
";

$voids = $conn->query($query);
?>