<?php
// Check Users Script
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Users</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { 
            background: #007bff; 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
    </style>
</head>
<body>
    <h1>Database Users Check</h1>
    
    <?php
    try {
        require_once 'includes/config.php';
        
        echo "<h2>Database Connection</h2>";
        echo "<p class='success'>✓ Connected to database: " . DB_NAME . "</p>";
        
        echo "<h2>Users Table Status</h2>";
        
        // Check if users table exists
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows > 0) {
            echo "<p class='success'>✓ Users table exists</p>";
            
            // Count users
            $count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
            echo "<p class='info'>Total users: $count</p>";
            
            if ($count > 0) {
                // Show users
                $users = $conn->query("SELECT user_id, username, full_name, role, created_at FROM users");
                
                echo "<h3>Current Users:</h3>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Created</th></tr>";
                
                while ($user = $users->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $user['user_id'] . "</td>";
                    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                    echo "<td>" . $user['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Test login functionality
                echo "<h2>Login Test</h2>";
                
                // Test admin login
                $testUser = $conn->prepare("SELECT * FROM users WHERE username = 'admin'");
                $testUser->execute();
                $adminUser = $testUser->get_result()->fetch_assoc();
                
                if ($adminUser) {
                    echo "<p class='success'>✓ Admin user found</p>";
                    echo "<p>Username: " . htmlspecialchars($adminUser['username']) . "</p>";
                    echo "<p>Role: " . htmlspecialchars($adminUser['role']) . "</p>";
                    
                    // Test password verification
                    if (password_verify('admin123', $adminUser['password'])) {
                        echo "<p class='success'>✓ Password verification works</p>";
                        echo "<p class='info'>You should be able to login with: admin / admin123</p>";
                    } else {
                        echo "<p class='error'>✗ Password verification failed</p>";
                        echo "<p class='error'>The password hash doesn't match 'admin123'</p>";
                    }
                } else {
                    echo "<p class='error'>✗ Admin user not found</p>";
                }
                
                // Test cashier login
                $testCashier = $conn->prepare("SELECT * FROM users WHERE username = 'cashier'");
                $testCashier->execute();
                $cashierUser = $testCashier->get_result()->fetch_assoc();
                
                if ($cashierUser) {
                    echo "<p class='success'>✓ Cashier user found</p>";
                    if (password_verify('admin123', $cashierUser['password'])) {
                        echo "<p class='success'>✓ Cashier password verification works</p>";
                    } else {
                        echo "<p class='error'>✗ Cashier password verification failed</p>";
                    }
                } else {
                    echo "<p class='error'>✗ Cashier user not found</p>";
                }
                
            } else {
                echo "<p class='error'>✗ No users found in database</p>";
                echo "<p class='info'>You need to run the installation script first.</p>";
                echo "<p><a href='install.php' class='btn'>Run Installation</a></p>";
            }
            
        } else {
            echo "<p class='error'>✗ Users table doesn't exist</p>";
            echo "<p class='info'>You need to run the installation script first.</p>";
            echo "<p><a href='install.php' class='btn'>Run Installation</a></p>";
        }
        
        echo "<h2>Quick Actions</h2>";
        echo "<p><a href='login.php' class='btn'>Try Login</a></p>";
        echo "<p><a href='install.php' class='btn'>Reinstall Database</a></p>";
        echo "<p><a href='clear_users.php' class='btn' style='background: #dc3545;'>Clear Users</a></p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        echo "<p>Please check your database connection in includes/config.php</p>";
    }
    ?>
</body>
</html>
