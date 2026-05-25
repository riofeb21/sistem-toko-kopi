<?php
header('Content-Type: application/json');
require_once 'config/database.php';
require_once 'config/settings_helper.php';

// --- NEW: KDS PRODUCT LIST ACTION ---
if (isset($_GET['action']) && $_GET['action'] === 'list_products') {
    $res = $conn->query("SELECT product_id as id, product_name as name, stock FROM products ORDER BY product_name ASC");
    $products = [];
    while($row = $res->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode(['success' => true, 'products' => $products]);
    exit;
}

$period = $_GET['period'] ?? 'daily'; // daily, monthly, yearly
$data = [];
$labels = [];

if ($period === 'daily') {
    // 7 Hari Terakhir
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $query = $conn->query("SELECT SUM(total_amount) as total FROM transactions WHERE DATE(transaction_date) = '$date' AND payment_status = 'paid'");
        $total = $query->fetch_assoc()['total'] ?? 0;
        
        $data[] = (int)$total;
        $labels[] = date('d M', strtotime($date));
    }
} elseif ($period === 'monthly') {
    // 12 Bulan Tahun Ini
    $currentYear = date('Y');
    for ($m = 1; $m <= 12; $m++) {
        $query = $conn->query("SELECT SUM(total_amount) as total FROM transactions WHERE YEAR(transaction_date) = '$currentYear' AND MONTH(transaction_date) = '$m' AND payment_status = 'paid'");
        $total = $query->fetch_assoc()['total'] ?? 0;
        
        $data[] = (int)$total;
        $labels[] = date('M', mktime(0, 0, 0, $m, 1));
    }
} elseif ($period === 'yearly') {
    // 5 Tahun Terakhir
    $currentYear = date('Y');
    for ($i = 4; $i >= 0; $i--) {
        $year = $currentYear - $i;
        $query = $conn->query("SELECT SUM(total_amount) as total FROM transactions WHERE YEAR(transaction_date) = '$year' AND payment_status = 'paid'");
        $total = $query->fetch_assoc()['total'] ?? 0;
        
        $data[] = (int)$total;
        $labels[] = $year;
    }
}

echo json_encode([
    'labels' => $labels,
    'data' => $data,
    'label' => $period === 'daily' ? 'Omset Harian (7 Hari)' : ($period === 'monthly' ? 'Omset Bulanan (Tahun Ini)' : 'Omset Tahunan')
]);
?>

