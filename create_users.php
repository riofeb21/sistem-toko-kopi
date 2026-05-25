<?php
require_once 'config/database.php';

// Insert or ignore Cashier
$sql = "INSERT INTO users (username, password, role, full_name) VALUES ('kasir', 'kasir123', 'kasir', 'Kasir Andalan') ON DUPLICATE KEY UPDATE full_name='Kasir Andalan'";
$conn->query($sql);

// Insert or ignore Barista
$sql2 = "INSERT INTO users (username, password, role, full_name) VALUES ('barista', 'barista123', 'barista', 'Barista Jagoan') ON DUPLICATE KEY UPDATE full_name='Barista Jagoan'";

if ($conn->query($sql2) === TRUE) {
    echo "<h1>User 'kasir' & 'barista' ready!</h1><p>kasir / kasir123<br>barista / barista123</p>";
} else {
    echo "Error: " . $conn->error;
}
?>

