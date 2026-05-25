<?php
require_once 'config/database.php';

// SQL to add column order_status
$sql = "ALTER TABLE Transactions ADD COLUMN IF NOT EXISTS order_status ENUM('queue', 'processing', 'ready', 'completed') NOT NULL DEFAULT 'queue' AFTER payment_status";

if ($conn->query($sql) === TRUE) {
    echo "<h1>✅ Database Updated Successfully!</h1>";
    echo "<p>Column 'order_status' added to 'Transactions' table.</p>";
} else {
    if ($conn->errno == 1060) {
         echo "<h1>✅ Column already exists!</h1>";
    } else {
        echo "<h1>❌ Error updating database: " . $conn->error . "</h1>";
    }
}
?>

