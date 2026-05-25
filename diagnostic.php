<?php
require_once 'config/database.php';

echo "Database Diagnostic\n";
echo "==================\n";

$tables = ['Products', 'Categories', 'Users', 'Transactions', 'Customers'];
foreach ($tables as $table) {
    $res = $conn->query("SELECT COUNT(*) as total FROM $table");
    if ($res) {
        echo "$table: " . $res->fetch_assoc()['total'] . " rows\n";
    } else {
        echo "$table: Error - " . $conn->error . "\n";
    }
}

$res = $conn->query("DESCRIBE Products");
echo "\nProducts Table Structure:\n";
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error describing Products: " . $conn->error . "\n";
}

$res = $conn->query("SELECT COUNT(*) as total FROM products WHERE is_available = 1 AND stock > 0");
if ($res) {
    echo "\nAvailable Products with stock: " . $res->fetch_assoc()['total'] . "\n";
} else {
    echo "\nQuery (Available Products) failed: " . $conn->error . "\n";
}
?>

