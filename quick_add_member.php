<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['name']) && isset($input['phone'])) {
    $name = trim($input['name']);
    $phone = trim($input['phone']);

    if (empty($name) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Nama dan Nomor HP wajib diisi!']);
        exit;
    }

    // Check if phone number already exists
    $check = $conn->prepare("SELECT customer_id FROM customers WHERE phone_number = ?");
    $check->bind_param("s", $phone);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Nomor HP sudah terdaftar sebagai member!']);
        exit;
    }

    // Insert new customer
    $stmt = $conn->prepare("INSERT INTO customers (customer_name, phone_number) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $phone);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
}
?>

