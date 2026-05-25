<?php
require_once 'config/database.php';
$res = $conn->query("SELECT COUNT(*) as c FROM transactions");
$row = $res->fetch_assoc();
echo "Transactions: " . $row['c'] . "\n";
