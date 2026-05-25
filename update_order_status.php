<?php
header('Content-Type: application/json');
require_once 'config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$id = intval($input['id']);
$status = $conn->real_escape_string($input['status']);

// Validasi status enum
$allowed = ['queue', 'processing', 'ready', 'completed'];
if (!in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$sql = "UPDATE transactions SET order_status = '$status' WHERE transaction_id = $id";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>

