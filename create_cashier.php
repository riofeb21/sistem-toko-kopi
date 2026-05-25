<?php
require_once 'config/database.php';

// Insert or ignore if exists
$sql = "INSERT INTO users (username, password, role, full_name) VALUES ('kasir', 'kasir123', 'kasir', 'Kasir Andalan') ON DUPLICATE KEY UPDATE full_name='Kasir Andalan'";

if ($conn->query($sql) === TRUE) {
    echo "<h1>User 'kasir' ready!</h1><p>Pass: kasir123</p>";
} else {
    echo "Error: " . $conn->error;
}
?>

