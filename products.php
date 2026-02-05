<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Handle product add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $stmt = $conn->prepare("INSERT INTO products (product_code, product_name, category_id, description, cost_price, selling_price, stock_quantity, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissdii", 
            $_POST['product_code'], 
            $_POST['product_name'], 
            $_POST['category_id'], 
            $_POST['description'], 
            $_POST['cost_price'], 
            $_POST['selling_price'], 
            $_POST['stock_quantity'], 
            $_POST['reorder_level']
        );
        $stmt->execute();
        header('Location: products.php?success=added');
        exit();
    } elseif (isset($_POST['edit_product'])) {
        $stmt = $conn->prepare("UPDATE products SET product_code=?, product_name=?, category_id=?, description=?, cost_price=?, selling_price=?, reorder_level=? WHERE product_id=?");
        $stmt->bind_param("ssissdii", 
            $_POST['product_code'], 
            $_POST['product_name'], 
            $_POST['category_id'], 
            $_POST['description'], 
            $_POST['cost_price'], 
            $_POST['selling_price'], 
            $_POST['reorder_level'],
            $_POST['product_id']
        );
        $stmt->execute();
        header('Location: products.php?success=updated');
        exit();
    } elseif (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'];
        $conn->query("UPDATE products SET status='inactive' WHERE product_id=$product_id");
        header('Location: products.php?success=deleted');
        exit();
    }
}

// Get products
$products = $conn->query("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
");

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - POS & Inventory System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Products</h1>
                <div class="header-actions">
                    <button class="btn btn-primary btn-sm" onclick="openAddModal()">+ Add Product</button>
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
                    Product <?php echo htmlspecialchars($_GET['success']); ?> successfully!
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>All Products</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Cost</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Reorder</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $products->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td>₱<?php echo number_format($product['cost_price'], 2); ?></td>
                                        <td>₱<?php echo number_format($product['selling_price'], 2); ?></td>
                                        <td><strong><?php echo $product['stock_quantity']; ?></strong></td>
                                        <td><?php echo $product['reorder_level']; ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">Edit</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $product['product_id']; ?>)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Product</h2>
                <button class="modal-close" onclick="closeProductModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="productForm">
                    <input type="hidden" name="product_id" id="productId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Code *</label>
                            <input type="text" class="form-control" name="product_code" id="productCode" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" class="form-control" name="product_name" id="productName" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control" name="category_id" id="categoryId">
                            <option value="">-- Select Category --</option>
                            <?php 
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cost Price *</label>
                            <input type="number" class="form-control" name="cost_price" id="costPrice" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Selling Price *</label>
                            <input type="number" class="form-control" name="selling_price" id="sellingPrice" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Initial Stock</label>
                            <input type="number" class="form-control" name="stock_quantity" id="stockQuantity" value="0" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Reorder Level *</label>
                            <input type="number" class="form-control" name="reorder_level" id="reorderLevel" value="10" min="0" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_product" id="submitBtn" class="btn btn-primary" style="width: 100%;">Add Product</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('productForm').reset();
            document.getElementById('submitBtn').name = 'add_product';
            document.getElementById('submitBtn').textContent = 'Add Product';
            document.getElementById('stockQuantity').disabled = false;
            document.getElementById('productModal').classList.add('active');
        }
        
        function openEditModal(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productId').value = product.product_id;
            document.getElementById('productCode').value = product.product_code;
            document.getElementById('productName').value = product.product_name;
            document.getElementById('categoryId').value = product.category_id || '';
            document.getElementById('description').value = product.description || '';
            document.getElementById('costPrice').value = product.cost_price;
            document.getElementById('sellingPrice').value = product.selling_price;
            document.getElementById('stockQuantity').value = product.stock_quantity;
            document.getElementById('stockQuantity').disabled = true;
            document.getElementById('reorderLevel').value = product.reorder_level;
            document.getElementById('submitBtn').name = 'edit_product';
            document.getElementById('submitBtn').textContent = 'Update Product';
            document.getElementById('productModal').classList.add('active');
        }
        
        function closeProductModal() {
            document.getElementById('productModal').classList.remove('active');
        }
        
        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_product" value="1">
                    <input type="hidden" name="product_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeProductModal();
            }
        });
    </script>
</body>
</html>
