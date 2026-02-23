<aside class="sidebar">
    <div class="sidebar-brand">
        <h2>
            <span class="icon">🛒</span>
            POS System
        </h2>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <span class="icon">📊</span>
                Dashboard
            </a>
        </li>
        <li>
            <a href="pos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>">
                <span class="icon">💳</span>
                Point of Sale
            </a>
        </li>
        <li>
            <a href="inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                <span class="icon">📦</span>
                Inventory
            </a>
        </li>
        <li>
            <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                <span class="icon">🏷️</span>
                Products
            </a>
        </li>
        <li>
            <a href="sales.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">
                <span class="icon">💰</span>
                Sales History
            </a>
        </li>
        <?php if (isAdmin()): ?>
        <li>
            <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                <span class="icon">📑</span>
                Categories
            </a>
        </li>
        <li>
            <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <span class="icon">📈</span>
                Reports
            </a>
        </li>
        <li>
            <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <span class="icon">👥</span>
                Users
            </a>
        </li>
        <?php endif; ?>
    </ul>
</aside>
