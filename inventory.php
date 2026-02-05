<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    $product_id = $_POST['product_id'];
    $movement_type = $_POST['movement_type'];
    $quantity = $_POST['quantity'];
    $notes = $_POST['notes'];
    
    if ($movement_type === 'in') {
        $conn->query("UPDATE products SET stock_quantity = stock_quantity + $quantity WHERE product_id = $product_id");
    } else {
        $conn->query("UPDATE products SET stock_quantity = stock_quantity - $quantity WHERE product_id = $product_id");
    }
    
    $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, notes, user_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isisi", $product_id, $movement_type, $quantity, $notes, $user['user_id']);
    $stmt->execute();
    
    header('Location: inventory.php?success=1');
    exit();
}

// Get products with low stock or all products
$products = $conn->query("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.status = 'active'
    ORDER BY p.stock_quantity ASC
");

// Get recent stock movements
$movements = $conn->query("
    SELECT sm.*, p.product_name, u.full_name 
    FROM stock_movements sm 
    LEFT JOIN products p ON sm.product_id = p.product_id 
    LEFT JOIN users u ON sm.user_id = u.user_id 
    ORDER BY sm.movement_date DESC 
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - POS & Inventory System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Inventory Management</h1>
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
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 24px; background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;">
                    Stock updated successfully!
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Current Inventory</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Cost Price</th>
                                    <th>Selling Price</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $products->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><strong><?php echo $product['stock_quantity']; ?></strong></td>
                                        <td><?php echo $product['reorder_level']; ?></td>
                                        <td>₱<?php echo number_format($product['cost_price'], 2); ?></td>
                                        <td>₱<?php echo number_format($product['selling_price'], 2); ?></td>
                                        <td>
                                            <?php if ($product['stock_quantity'] == 0): ?>
                                                <span class="badge badge-danger">Out of Stock</span>
                                            <?php elseif ($product['stock_quantity'] <= $product['reorder_level']): ?>
                                                <span class="badge badge-warning">Low Stock</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">In Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick="openStockModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                Adjust Stock
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Recent Stock Movements</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reference</th>
                                    <th>Notes</th>
                                    <th>User</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($movement = $movements->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                                        <td>
                                            <?php if ($movement['movement_type'] === 'in'): ?>
                                                <span class="badge badge-success">Stock In</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Stock Out</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo $movement['quantity']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($movement['reference']); ?></td>
                                        <td><?php echo htmlspecialchars($movement['notes']); ?></td>
                                        <td><?php echo htmlspecialchars($movement['full_name']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($movement['movement_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stock Adjustment Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Adjust Stock</h2>
                <button class="modal-close" onclick="closeStockModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="adjust_stock" value="1">
                    <input type="hidden" name="product_id" id="modalProductId">
                    
                    <div class="form-group">
                        <label>Product</label>
                        <input type="text" class="form-control" id="modalProductName" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Stock</label>
                        <input type="text" class="form-control" id="modalCurrentStock" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Movement Type</label>
                        <select class="form-control" name="movement_type" required>
                            <option value="in">Stock In (Add)</option>
                            <option value="out">Stock Out (Remove)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" class="form-control" name="quantity" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Stock</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openStockModal(product) {
            document.getElementById('modalProductId').value = product.product_id;
            document.getElementById('modalProductName').value = product.product_name;
            document.getElementById('modalCurrentStock').value = product.stock_quantity;
            document.getElementById('stockModal').classList.add('active');
        }
        
        function closeStockModal() {
            document.getElementById('stockModal').classList.remove('active');
        }
        
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeStockModal();
            }
        });
    </script>
</body>
</html>
