<?php
session_start();
require_once 'config/database.php';

// Cek akses: Hanya admin, manajer, atau barista yang boleh update stok cepat
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manajer', 'barista', 'owner'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_stock') {
    $product_id = intval($_POST['product_id']);
    $new_stock = intval($_POST['stock']);

    $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE product_id = ?");
    $stmt->bind_param("ii", $new_stock, $product_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>

