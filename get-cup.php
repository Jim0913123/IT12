<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $cup_id = $_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM cups_inventory WHERE cup_id = ?");
    $stmt->bind_param("i", $cup_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Cup not found']);
    }
} else {
    echo json_encode(['error' => 'No cup ID provided']);
}
?>
