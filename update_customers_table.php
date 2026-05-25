<?php
require_once 'config/database.php';

echo "<h1>Updating Customers Table</h1>";

// Rename phone to phone_number if it exists
$res = $conn->query("SHOW COLUMNS FROM customers LIKE 'phone'");
if ($res->num_rows > 0) {
    if ($conn->query("ALTER TABLE Customers CHANGE COLUMN phone phone_number VARCHAR(20)")) {
        echo "✅ Renamed 'phone' to 'phone_number'.<br>";
    } else {
        echo "❌ Error renaming phone: " . $conn->error . "<br>";
    }
}

// Add loyalty_points if not exists
$res = $conn->query("SHOW COLUMNS FROM customers LIKE 'loyalty_points'");
if ($res->num_rows == 0) {
    if ($conn->query("ALTER TABLE Customers ADD COLUMN loyalty_points INT DEFAULT 0 AFTER phone_number")) {
        echo "✅ Added 'loyalty_points' column.<br>";
    } else {
        echo "❌ Error adding loyalty_points: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ 'loyalty_points' already exists.<br>";
}
?>

