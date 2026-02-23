<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Get statistics
$stats = [];

// Total Products
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
$stats['total_products'] = $result->fetch_assoc()['count'];

// Low Stock Products
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= reorder_level AND status = 'active'");
$stats['low_stock'] = $result->fetch_assoc()['count'];

// Today's Sales
$result = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
$today = $result->fetch_assoc();
$stats['today_sales'] = $today['total'];
$stats['today_transactions'] = $today['count'];

// Total Inventory Value
$result = $conn->query("SELECT COALESCE(SUM(stock_quantity * cost_price), 0) as value FROM products WHERE status = 'active'");
$stats['inventory_value'] = $result->fetch_assoc()['value'];

// Recent Sales with pagination
$page = $_GET['sales_page'] ?? 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Get total recent sales count
$total_recent_sales = $conn->query("SELECT COUNT(*) as count FROM sales")->fetch_assoc()['count'];
$total_sales_pages = ceil($total_recent_sales / $limit);

$recent_sales = $conn->query("
    SELECT s.*, u.full_name 
    FROM sales s 
    LEFT JOIN users u ON s.user_id = u.user_id 
    ORDER BY s.sale_date DESC 
    LIMIT $limit OFFSET $offset
");

// Low Stock Products
$low_stock_products = $conn->query("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.stock_quantity <= p.reorder_level AND p.status = 'active'
    ORDER BY p.stock_quantity ASC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - POPRIE</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
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
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon primary">
                            📦
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Total Products</h3>
                        <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon success">
                            💰
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Today's Sales</h3>
                        <div class="stat-value">₱<?php echo number_format($stats['today_sales'], 2); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon warning">
                            ⚠️
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Low Stock Items</h3>
                        <div class="stat-value"><?php echo number_format($stats['low_stock']); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon danger">
                            📊
                        </div>
                    </div>
                    <div class="stat-card-body">
                        <h3>Inventory Value</h3>
                        <div class="stat-value">₱<?php echo number_format($stats['inventory_value'], 2); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Recent Sales</h3>
                    <a href="sales.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Cashier</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_sales->num_rows > 0): ?>
                                    <?php while ($sale = $recent_sales->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($sale['invoice_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in'); ?></td>
                                            <td><?php echo htmlspecialchars($sale['full_name']); ?></td>
                                            <td><strong>₱<?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                                            <td><span class="badge badge-primary"><?php echo ucfirst($sale['payment_method']); ?></span></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-secondary);">No sales yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Recent Sales Pagination -->
                    <?php if ($total_sales_pages > 1): ?>
                        <div class="pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 16px;">
                            <?php if ($page > 1): ?>
                                <a href="?sales_page=<?php echo $page - 1; ?>" class="btn btn-secondary btn-sm">« Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_sales_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="btn btn-primary btn-sm"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?sales_page=<?php echo $i; ?>" class="btn btn-secondary btn-sm"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_sales_pages): ?>
                                <a href="?sales_page=<?php echo $page + 1; ?>" class="btn btn-secondary btn-sm">Next »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 12px; color: var(--text-secondary); font-size: 12px;">
                        Showing <?php echo ($offset + 1) . ' - ' . min($offset + $limit, $total_recent_sales); ?> of <?php echo $total_recent_sales; ?> recent sales
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Low Stock Alert</h3>
                    <a href="inventory.php" class="btn btn-warning btn-sm">Manage Inventory</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($low_stock_products->num_rows > 0): ?>
                                    <?php while ($product = $low_stock_products->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                            <td><strong><?php echo $product['stock_quantity']; ?></strong></td>
                                            <td><?php echo $product['reorder_level']; ?></td>
                                            <td>
                                                <?php if ($product['stock_quantity'] == 0): ?>
                                                    <span class="badge badge-danger">Out of Stock</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Low Stock</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-secondary);">All products are well stocked!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
