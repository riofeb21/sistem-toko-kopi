<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (isset($_GET['name'])) {
    $name = trim($_GET['name']); // Could be name or phone
    
    // Search by name or phone
    $stmt = $conn->prepare("SELECT loyalty_points FROM customers WHERE customer_name = ? OR phone_number = ? LIMIT 1");
    $stmt->bind_param("ss", $name, $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'points' => $row['loyalty_points']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not found']);
    }
} else {
    echo json_encode(['success' => false]);
}
?>

