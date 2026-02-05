<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin(); // Only admin can manage categories
$user = getCurrentUser();

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $_POST['category_name'], $_POST['description']);
        $stmt->execute();
        header('Location: categories.php?success=added');
        exit();
    } elseif (isset($_POST['edit_category'])) {
        $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
        $stmt->bind_param("ssi", $_POST['category_name'], $_POST['description'], $_POST['category_id']);
        $stmt->execute();
        header('Location: categories.php?success=updated');
        exit();
    } elseif (isset($_POST['delete_category'])) {
        $category_id = $_POST['category_id'];
        $conn->query("DELETE FROM categories WHERE category_id=$category_id");
        header('Location: categories.php?success=deleted');
        exit();
    }
}

$categories = $conn->query("
    SELECT c.*, COUNT(p.product_id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.category_id = p.category_id AND p.status = 'active'
    GROUP BY c.category_id 
    ORDER BY c.category_name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - POS & Inventory System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Categories</h1>
                <div class="header-actions">
                    <button class="btn btn-primary btn-sm" onclick="openAddModal()">+ Add Category</button>
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
                    Category <?php echo htmlspecialchars($_GET['success']); ?> successfully!
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>All Categories</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                                        <td><span class="badge badge-primary"><?php echo $category['product_count']; ?> products</span></td>
                                        <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm" onclick='openEditModal(<?php echo json_encode($category); ?>)'>Edit</button>
                                            <?php if ($category['product_count'] == 0): ?>
                                                <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $category['category_id']; ?>)">Delete</button>
                                            <?php endif; ?>
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
    
    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Category</h2>
                <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="categoryForm">
                    <input type="hidden" name="category_id" id="categoryId">
                    
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" class="form-control" name="category_name" id="categoryName" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" name="add_category" id="submitBtn" class="btn btn-primary" style="width: 100%;">Add Category</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Category';
            document.getElementById('categoryForm').reset();
            document.getElementById('submitBtn').name = 'add_category';
            document.getElementById('submitBtn').textContent = 'Add Category';
            document.getElementById('categoryModal').classList.add('active');
        }
        
        function openEditModal(category) {
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('categoryId').value = category.category_id;
            document.getElementById('categoryName').value = category.category_name;
            document.getElementById('description').value = category.description || '';
            document.getElementById('submitBtn').name = 'edit_category';
            document.getElementById('submitBtn').textContent = 'Update Category';
            document.getElementById('categoryModal').classList.add('active');
        }
        
        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.remove('active');
        }
        
        function deleteCategory(id) {
            if (confirm('Are you sure you want to delete this category?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_category" value="1">
                    <input type="hidden" name="category_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeCategoryModal();
            }
        });
    </script>
</body>
</html>
