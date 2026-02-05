<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Get sales with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$total_sales = $conn->query("SELECT COUNT(*) as count FROM sales")->fetch_assoc()['count'];
$total_pages = ceil($total_sales / $limit);

$sales = $conn->query("
    SELECT s.*, u.full_name 
    FROM sales s 
    LEFT JOIN users u ON s.user_id = u.user_id 
    ORDER BY s.sale_date DESC 
    LIMIT $limit OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - POS & Inventory System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Sales History</h1>
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
                    <h3>All Sales Transactions</h3>
                    <div style="display: flex; gap: 8px;">
                        <input type="date" class="form-control" id="dateFilter" style="width: auto;">
                        <button class="btn btn-primary btn-sm" onclick="filterByDate()">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Cashier</th>
                                    <th>Items</th>
                                    <th>Subtotal</th>
                                    <th>Tax</th>
                                    <th>Discount</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($sale = $sales->fetch_assoc()): ?>
                                    <?php
                                    $items_count = $conn->query("SELECT COUNT(*) as count FROM sale_items WHERE sale_id = {$sale['sale_id']}")->fetch_assoc()['count'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($sale['invoice_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in'); ?></td>
                                        <td><?php echo htmlspecialchars($sale['full_name']); ?></td>
                                        <td><?php echo $items_count; ?> item(s)</td>
                                        <td>₱<?php echo number_format($sale['subtotal'], 2); ?></td>
                                        <td>₱<?php echo number_format($sale['tax'], 2); ?></td>
                                        <td>₱<?php echo number_format($sale['discount'], 2); ?></td>
                                        <td><strong>₱<?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                                        <td><span class="badge badge-primary"><?php echo ucfirst($sale['payment_method']); ?></span></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick="viewSaleDetails(<?php echo $sale['sale_id']; ?>)">View</button>
                                            <a href="receipt.php?invoice=<?php echo $sale['invoice_number']; ?>" target="_blank" class="btn btn-success btn-sm">Receipt</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div style="margin-top: 24px; display: flex; justify-content: center; gap: 8px;">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" class="btn <?php echo $page == $i ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sale Details Modal -->
    <div id="saleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Sale Details</h2>
                <button class="modal-close" onclick="closeSaleModal()">&times;</button>
            </div>
            <div class="modal-body" id="saleDetails">
                Loading...
            </div>
        </div>
    </div>
    
    <script>
        async function viewSaleDetails(saleId) {
            document.getElementById('saleModal').classList.add('active');
            
            try {
                const response = await fetch(`api/get-sale-details.php?sale_id=${saleId}`);
                const data = await response.json();
                
                if (data.success) {
                    const sale = data.sale;
                    const items = data.items;
                    
                    let html = `
                        <div style="margin-bottom: 24px;">
                            <p><strong>Invoice:</strong> ${sale.invoice_number}</p>
                            <p><strong>Customer:</strong> ${sale.customer_name || 'Walk-in'}</p>
                            <p><strong>Cashier:</strong> ${sale.full_name}</p>
                            <p><strong>Date:</strong> ${new Date(sale.sale_date).toLocaleString()}</p>
                        </div>
                        
                        <h4 style="margin-bottom: 12px;">Items</h4>
                        <table style="width: 100%; margin-bottom: 24px;">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    items.forEach(item => {
                        html += `
                            <tr>
                                <td>${item.product_name}</td>
                                <td>${item.quantity}</td>
                                <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                                <td>₱${parseFloat(item.subtotal).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                            </tbody>
                        </table>
                        
                        <div style="border-top: 2px solid var(--border); padding-top: 16px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span>Subtotal:</span>
                                <strong>₱${parseFloat(sale.subtotal).toFixed(2)}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span>Tax:</span>
                                <strong>₱${parseFloat(sale.tax).toFixed(2)}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span>Discount:</span>
                                <strong>₱${parseFloat(sale.discount).toFixed(2)}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 2px solid var(--border); font-size: 18px;">
                                <span><strong>Grand Total:</strong></span>
                                <strong style="color: var(--primary);">₱${parseFloat(sale.total_amount).toFixed(2)}</strong>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('saleDetails').innerHTML = html;
                } else {
                    document.getElementById('saleDetails').innerHTML = '<p>Error loading sale details</p>';
                }
            } catch (error) {
                document.getElementById('saleDetails').innerHTML = '<p>Error loading sale details</p>';
            }
        }
        
        function closeSaleModal() {
            document.getElementById('saleModal').classList.remove('active');
        }
        
        function filterByDate() {
            const date = document.getElementById('dateFilter').value;
            if (date) {
                window.location.href = '?date=' + date;
            }
        }
        
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeSaleModal();
            }
        });
    </script>
</body>
</html>
