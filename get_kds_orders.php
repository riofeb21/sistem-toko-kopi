<?php
header('Content-Type: application/json');
require_once 'config/database.php';

// Ambil pesanan yang belum completed (kecuali baru payment_status = paid)
// Status urutan: queue -> processing -> ready -> completed
$sql = "SELECT t.transaction_id, t.invoice_code, t.customer_name, t.order_type, t.order_status, t.transaction_date,
               td.quantity, p.product_name
        FROM transactions t
        JOIN transactiondetails td ON t.transaction_id = td.transaction_id
        JOIN products p ON td.product_id = p.product_id
        WHERE t.payment_status = 'paid' 
        AND t.order_status != 'completed'
        ORDER BY t.transaction_date ASC";

$result = $conn->query($sql);

$ordersMap = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $trxId = $row['transaction_id'];
        
        if (!isset($ordersMap[$trxId])) {
            // Hitung waktu berlalu
            $trxTime = strtotime($row['transaction_date']);
            $now = time();
            $diffParams = abs($now - $trxTime);
            $mins = floor($diffParams / 60);
            $timeStr = $mins < 1 ? 'Just now' : $mins . ' min ago';

            $ordersMap[$trxId] = [
                'id' => $trxId,
                'invoice' => substr($row['invoice_code'], -6), // Short invoice
                'customer' => $row['customer_name'] ?: 'Guest',
                'type' => $row['order_type'],
                'status' => $row['order_status'],
                'time' => $timeStr,
                'details' => []
            ];
        }

        $ordersMap[$trxId]['details'][] = [
            'name' => $row['product_name'],
            'qty' => $row['quantity']
        ];
    }
}

echo json_encode(['success' => true, 'orders' => array_values($ordersMap)]);
?>

