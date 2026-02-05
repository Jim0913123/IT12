<?php
// Clear Users Script
?>
<!DOCTYPE html>
<html>
<head>
    <title>Clear Users</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { 
            background: #dc3545; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn-safe { 
            background: #28a745; 
        }
    </style>
</head>
<body>
    <h1>Clear POS Inventory Users</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
        try {
            require_once 'includes/config.php';
            
            // Delete all users from the table
            $result = $conn->query("DELETE FROM users");
            
            if ($result) {
                $affectedRows = $conn->affected_rows;
                echo "<p class='success'>✓ Successfully deleted $affectedRows user(s) from the database</p>";
                echo "<p class='warning'>⚠ All users have been removed. You will need to create new admin users manually.</p>";
                echo "<p><a href='login.php' class='btn btn-safe'>Go to Login</a></p>";
            } else {
                echo "<p class='error'>✗ Error deleting users: " . $conn->error . "</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        }
    } else {
        ?>
        <div class="warning">
            <h3>⚠ Warning: This action cannot be undone!</h3>
            <p>This will permanently delete ALL users from the POS inventory system.</p>
            <p>You will lose access to the system and need to create new admin users.</p>
        </div>
        
        <form method="POST">
            <p>
                <label>
                    <input type="checkbox" name="confirm" required>
                    I understand this will delete all users and I want to proceed
                </label>
            </p>
            <p>
                <button type="submit" class="btn">Delete All Users</button>
                <a href="index.php" class="btn btn-safe">Cancel</a>
            </p>
        </form>
        <?php
    }
    ?>
</body>
</html>
