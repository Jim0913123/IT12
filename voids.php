<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Only admins can view voided sales
if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

// pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// filter by date
$where = "";
$filter_params = "";

if (!empty($_GET['date'])) {
    $date = $conn->real_escape_string($_GET['date']);
    $where = "WHERE DATE(sv.voided_at) = '$date'";
    $filter_params = "&date=" . urlencode($_GET['date']);
}

// total count
$countQuery = "SELECT COUNT(*) as count FROM sale_voids sv $where";
$total_result = $conn->query($countQuery);
if (!$total_result) {
    die("Query error: " . $conn->error);
}
$total = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total / $limit);

// Debug output
error_log("Voids count: $total");
error_log("Query: $countQuery");

// main query - query from dedicated sale_voids audit table
$query = "
    SELECT sv.*, 
           ua.full_name AS admin_name,
           ur.full_name AS requester_name
    FROM sale_voids sv
    LEFT JOIN users ua ON sv.voided_by = ua.user_id
    LEFT JOIN users ur ON sv.voided_by = ur.user_id
    $where
    ORDER BY sv.voided_at DESC
    LIMIT $limit OFFSET $offset
";

$voids = $conn->query($query);

// Debug output
error_log("Query executed: $query");
error_log("Query result: " . ($voids ? $voids->num_rows . " rows" : "false"));

// Add debug display at top of page
$debug_info = "";
if ($voids) {
    $debug_info = "<div style='background: #f0f8ff; padding: 10px; margin: 10px; border: 1px solid #0066cc;'>";
    $debug_info .= "<strong>DEBUG INFO:</strong><br>";
    $debug_info .= "Total voids: " . $total . "<br>";
    $debug_info .= "Query result rows: " . $voids->num_rows . "<br>";
    $debug_info .= "Query: " . htmlspecialchars($query) . "<br>";
    $debug_info .= "</div>";
} else {
    $debug_info = "<div style='background: #ffe6e6; padding: 10px; margin: 10px; border: 1px solid #cc0000;'>";
    $debug_info .= "<strong>ERROR:</strong> Query failed: " . $conn->error . "<br>";
    $debug_info .= "Query: " . htmlspecialchars($query) . "<br>";
    $debug_info .= "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voided Sales - POS & Inventory System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .pagination-container {
            margin-top: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .pagination-controls {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .pagination-controls a,
        .pagination-controls span {
            padding: 6px 10px;
            border: 1px solid var(--border);
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s ease;
        }
        
        .pagination-controls a {
            background: var(--bg-secondary);
            color: var(--text-primary);
            cursor: pointer;
        }
        
        .pagination-controls a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination-controls a.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination-controls span.dots {
            border: none;
            color: var(--text-secondary);
        }
        
        .void-item-list {
            font-size: 12px;
            margin: 0;
            padding-left: 16px;
        }
        
        .void-item-list li {
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h1>Voided Sales Audit Log</h1>
                <div class="header-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
                        <div class="user-details">
                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <p><?php echo ucfirst($user['role']); ?></p>
                        </div>
                    </div>
                    <a href="logout.php" class="btn btn-logout btn-sm">Logout</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Cancelled Carts & Voids</h3>
                    <div style="display: flex; gap: 8px;">
                        <input type="date" class="form-control" id="dateFilter" style="width: auto;" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
                        <button class="btn btn-primary btn-sm" onclick="filterByDate()">Filter</button>
                        <?php if (isset($_GET['date'])): ?>
                            <a href="voids.php" class="btn btn-secondary btn-sm">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php echo $debug_info; ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Requested By</th>
                                    <th>Authorized By</th>
                                    <th>Reason</th>
                                    <th>Cart Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($voids && $voids->num_rows > 0): ?>
                                    <?php while ($row = $voids->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($row['voided_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['requester_name'] ?: 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($row['admin_name'] ?: 'Unknown Admin'); ?></td>
                                            <td><?php echo htmlspecialchars($row['void_reason']); ?></td>
                                            <td style="font-size:12px;">
                                                <?php
                                                if (!empty($row['cart_items'])) {
                                                    $items = json_decode($row['cart_items'], true);
                                                    if (is_array($items) && count($items) > 0) {
                                                        echo '<ul class="void-item-list">';
                                                        foreach ($items as $item) {
                                                            if (isset($item['name']) && isset($item['quantity'])) {
                                                                echo '<li>' . htmlspecialchars($item['name']) . ' ×' . intval($item['quantity']) . '</li>';
                                                            }
                                                        }
                                                        echo '</ul>';
                                                    } else {
                                                        echo '<em style="color:#999;">empty cart</em>';
                                                    }
                                                } else {
                                                    echo '<em style="color:#999;">n/a</em>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding:24px; color: var(--text-secondary);">
                                            No cancelled sales records found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <div class="pagination-controls">
                                <?php if ($page > 1): ?>
                                    <a href="?page=1<?php echo $filter_params; ?>">« First</a>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $filter_params; ?>">‹ Previous</a>
                                <?php endif; ?>
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                if ($start_page > 1) {
                                    echo '<a href="?page=1' . $filter_params . '">1</a>';
                                    if ($start_page > 2) {
                                        echo '<span class="dots">...</span>';
                                    }
                                }
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    if ($i == $page) {
                                        echo '<a class="active">' . $i . '</a>';
                                    } else {
                                        echo '<a href="?page=' . $i . $filter_params . '">' . $i . '</a>';
                                    }
                                }
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<span class="dots">...</span>';
                                    }
                                    echo '<a href="?page=' . $total_pages . $filter_params . '">' . $total_pages . '</a>';
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function filterByDate() {
        const d = document.getElementById('dateFilter').value;
        if (d) {
            window.location = 'voids.php?date=' + encodeURIComponent(d);
        }
    }
    </script>
</body>
</html>