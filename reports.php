<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Check if user is admin for reports access
if (!isAdmin()) {
    header('Location: index.php');
    exit();
}

// Get date range (default to today)
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get daily sales data
$daily_sales = [];
$total_sales = 0;
$total_transactions = 0;
$total_items = 0;

$sales_query = "
    SELECT 
        DATE(sale_date) as sale_date,
        COUNT(*) as transaction_count,
        SUM(total_amount) as total_sales,
        SUM(amount_paid) as total_paid,
        SUM(discount) as total_discount,
        SUM(tax) as total_tax
    FROM sales 
    WHERE DATE(sale_date) BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY sale_date DESC
";

$stmt = $conn->prepare($sales_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $daily_sales[] = $row;
    $total_sales += $row['total_sales'];
    $total_transactions += $row['transaction_count'];
}

// Get top selling products
$top_products = [];
$products_query = "
    SELECT 
        p.product_name,
        c.category_name,
        SUM(si.quantity) as total_quantity,
        SUM(si.subtotal) as total_revenue
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.sale_id
    JOIN products p ON si.product_id = p.product_id
    JOIN categories c ON p.category_id = c.category_id
    WHERE DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY si.product_id, p.product_name, c.category_name
    ORDER BY total_quantity DESC
    LIMIT 10
";

$stmt = $conn->prepare($products_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $top_products[] = $row;
    $total_items += $row['total_quantity'];
}

// Get payment method breakdown
$payment_methods = [];
$payment_query = "
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(total_amount) as total
    FROM sales 
    WHERE DATE(sale_date) BETWEEN ? AND ?
    GROUP BY payment_method
    ORDER BY total DESC
";

$stmt = $conn->prepare($payment_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $payment_methods[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Reports - POPRIE</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Daily Reports</h1>
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
            
            <!-- Date Range Filter -->
            <div class="card">
                <div class="card-header">
                    <h3>Date Range</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-row" style="display: flex; gap: 12px; align-items: end;">
                        <div class="form-group" style="flex: 1;">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="form-control" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                        <a href="reports.php" class="btn btn-secondary">Today</a>
                    </form>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon success">
                            💰
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Total Sales</h3>
                        <div class="stat-value">₱<?php echo number_format($total_sales, 2); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon primary">
                            📊
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Transactions</h3>
                        <div class="stat-value"><?php echo number_format($total_transactions); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon warning">
                            🛍️
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Items Sold</h3>
                        <div class="stat-value"><?php echo number_format($total_items); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon info">
                            📈
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Avg Transaction</h3>
                        <div class="stat-value">₱<?php echo $total_transactions > 0 ? number_format($total_sales / $total_transactions, 2) : '0.00'; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Daily Sales Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Daily Sales Breakdown</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transactions</th>
                                    <th>Gross Sales</th>
                                    <th>Discount</th>
                                    <th>Tax</th>
                                    <th>Net Sales</th>
                                    <th>Avg Sale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($daily_sales)): ?>
                                    <?php foreach ($daily_sales as $day): ?>
                                        <tr>
                                            <td><strong><?php echo date('M d, Y', strtotime($day['sale_date'])); ?></strong></td>
                                            <td><?php echo $day['transaction_count']; ?></td>
                                            <td>₱<?php echo number_format($day['total_sales'], 2); ?></td>
                                            <td>₱<?php echo number_format($day['total_discount'], 2); ?></td>
                                            <td>₱<?php echo number_format($day['total_tax'], 2); ?></td>
                                            <td><strong>₱<?php echo number_format($day['total_sales'], 2); ?></strong></td>
                                            <td>₱<?php echo number_format($day['total_sales'] / $day['transaction_count'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-secondary);">No sales data found for selected period</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Top Products and Payment Methods -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
                <!-- Top Selling Products -->
                <div class="card">
                    <div class="card-header">
                        <h3>Top Selling Products</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Quantity</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($top_products)): ?>
                                        <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                                <td><?php echo $product['total_quantity']; ?></td>
                                                <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; color: var(--text-secondary);">No sales data found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Methods -->
                <div class="card">
                    <div class="card-header">
                        <h3>Payment Methods</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Transactions</th>
                                        <th>Total Amount</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($payment_methods)): ?>
                                        <?php foreach ($payment_methods as $method): ?>
                                            <tr>
                                                <td><strong><?php echo ucfirst($method['payment_method']); ?></strong></td>
                                                <td><?php echo $method['count']; ?></td>
                                                <td>₱<?php echo number_format($method['total'], 2); ?></td>
                                                <td><?php echo $total_sales > 0 ? number_format(($method['total'] / $total_sales) * 100, 1) : 0; ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; color: var(--text-secondary);">No payment data found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
