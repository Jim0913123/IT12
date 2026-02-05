<?php
// Comprehensive System Check
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .warning { color: orange; background: #fff3cd; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .info { color: blue; background: #d1ecf1; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
    </style>
</head>
<body>
    <h1>🔍 System Diagnostic Tool</h1>
    
    <div class="section">
        <h2>1. PHP Configuration</h2>
        <?php
        echo "<p class='info'>PHP Version: " . PHP_VERSION . "</p>";
        echo "<p class='info'>Memory Limit: " . ini_get('memory_limit') . "</p>";
        echo "<p class='info'>Max Execution Time: " . ini_get('max_execution_time') . "s</p>";
        echo "<p class='info'>Error Reporting: " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";
        
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            echo "<p class='error'>⚠ PHP version should be 7.4 or higher</p>";
        } else {
            echo "<p class='success'>✓ PHP version is compatible</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>2. Database Connection</h2>
        <?php
        try {
            require_once 'includes/config.php';
            echo "<p class='success'>✓ Database connection successful</p>";
            echo "<p class='info'>Host: " . DB_HOST . "</p>";
            echo "<p class='info'>Database: " . DB_NAME . "</p>";
            echo "<p class='info'>User: " . DB_USER . "</p>";
            
            // Test basic query
            $result = $conn->query("SELECT 1");
            if ($result) {
                echo "<p class='success'>✓ Database query test passed</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. Required Files Check</h2>
        <?php
        $requiredFiles = [
            'includes/config.php' => 'Database configuration',
            'includes/auth.php' => 'Authentication functions',
            'login.php' => 'Login page',
            'index.php' => 'Dashboard',
            'pos.php' => 'Point of Sale',
            'css/style.css' => 'Stylesheet',
            'js/pos.js' => 'POS JavaScript',
            'database.sql' => 'Database schema'
        ];
        
        foreach ($requiredFiles as $file => $description) {
            if (file_exists($file)) {
                echo "<p class='success'>✓ $file - $description</p>";
            } else {
                echo "<p class='error'>✗ Missing: $file - $description</p>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>4. Database Tables</h2>
        <?php
        try {
            require_once 'includes/config.php';
            $tables = ['users', 'categories', 'products', 'sales', 'sale_items'];
            
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows > 0) {
                    $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
                    echo "<p class='success'>✓ Table '$table' exists ($count records)</p>";
                } else {
                    echo "<p class='error'>✗ Table '$table' missing</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error checking tables: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>5. User Authentication Test</h2>
        <?php
        try {
            require_once 'includes/config.php';
            require_once 'includes/auth.php';
            
            // Check if users exist
            $users = $conn->query("SELECT username, full_name, role FROM users");
            if ($users->num_rows > 0) {
                echo "<p class='success'>✓ Users found in database</p>";
                echo "<table><tr><th>Username</th><th>Full Name</th><th>Role</th></tr>";
                while ($user = $users->fetch_assoc()) {
                    echo "<tr><td>" . htmlspecialchars($user['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['role']) . "</td></tr>";
                }
                echo "</table>";
                
                // Test password verification
                $adminUser = $conn->prepare("SELECT password FROM users WHERE username = 'admin'");
                $adminUser->execute();
                $adminHash = $adminUser->get_result()->fetch_assoc()['password'] ?? '';
                
                if ($adminHash && password_verify('admin123', $adminHash)) {
                    echo "<p class='success'>✓ Admin password verification works</p>";
                } else {
                    echo "<p class='error'>✗ Admin password verification failed</p>";
                }
                
            } else {
                echo "<p class='error'>✗ No users found in database</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error testing authentication: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>6. Session Configuration</h2>
        <?php
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo "<p class='success'>✓ Session is active</p>";
        } else {
            echo "<p class='warning'>⚠ Session not started</p>";
        }
        
        echo "<p class='info'>Session Save Path: " . session_save_path() . "</p>";
        echo "<p class='info'>Session Name: " . session_name() . "</p>";
        ?>
    </div>
    
    <div class="section">
        <h2>7. File Permissions</h2>
        <?php
        $paths = [
            '.' => 'Project root',
            'includes/' => 'Includes directory',
            'css/' => 'CSS directory',
            'js/' => 'JavaScript directory'
        ];
        
        foreach ($paths as $path => $description) {
            if (is_readable($path)) {
                echo "<p class='success'>✓ $path is readable</p>";
            } else {
                echo "<p class='error'>✗ $path is not readable</p>";
            }
            
            if (is_writable($path)) {
                echo "<p class='success'>✓ $path is writable</p>";
            } else {
                echo "<p class='warning'>⚠ $path is not writable</p>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>🔧 Quick Actions</h2>
        <p><a href="login.php" class="btn">Try Login</a></p>
        <p><a href="check_users.php" class="btn">Check Users</a></p>
        <p><a href="generate_hash.php" class="btn">Generate Hash</a></p>
        <p><a href="install.php" class="btn">Reinstall Database</a></p>
    </div>
    
    <div class="section">
        <h2>📋 Common Issues & Solutions</h2>
        <div class="warning">
            <h3>If login fails:</h3>
            <ul>
                <li>Check if database tables exist</li>
                <li>Verify password hash is correct</li>
                <li>Clear browser cookies and cache</li>
                <li>Check XAMPP/MySQL is running</li>
                <li>Verify database name in config.php</li>
            </ul>
        </div>
    </div>
</body>
</html>
