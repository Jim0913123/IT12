<?php 
$user = getCurrentUser();
$is_cashier = isset($user['role']) && $user['role'] === 'cashier';
$is_admin = isAdmin();
?>

<aside class="sidebar<?php echo $is_cashier ? ' sidebar-hidden' : ''; ?>">
    <div class="sidebar-brand">
        <h2>
            <span class="icon">🛒</span>
            POS System
        </h2>
    </div>
    
    <ul class="sidebar-menu">
        
        <?php if (!$is_cashier): ?>
        <li>
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <span class="icon">📊</span>
                Dashboard
            </a>
        </li>
        <?php endif; ?>

        <li>
            <a href="pos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>">
                <span class="icon">💳</span>
                Point of Sale
            </a>
        </li>

        <?php if (!$is_cashier): ?>
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

            <?php if ($is_admin): ?>
            <li>
                <a href="voids.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'voids.php' ? 'active' : ''; ?>">
                    <span class="icon">❌</span>
                    Voided Sales
                </a>
            </li>

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

        <?php endif; ?>  <!-- THIS WAS MISSING -->

    </ul>
</aside>