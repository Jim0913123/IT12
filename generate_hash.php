<?php
// Generate correct password hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br>";

// Test verification
if (password_verify($password, $hash)) {
    echo "✓ Verification successful!";
} else {
    echo "✗ Verification failed!";
}

// SQL for insertion
echo "<br><br>SQL INSERT:<br>";
echo "INSERT INTO users (username, password, full_name, role) VALUES ('admin', '$hash', 'Administrator', 'admin');";
?>
