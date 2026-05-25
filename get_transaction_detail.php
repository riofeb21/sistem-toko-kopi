<?php
require_once 'config/database.php';

if (!isset($_GET['id'])) exit('No ID');
$id = intval($_GET['id']);

// Get Details
$sql = "SELECT d.*, p.product_name 
        FROM transactiondetails d 
        JOIN products p ON d.product_id = p.product_id 
        WHERE d.transaction_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

echo '<table style="width:100%; border-collapse: collapse;">';
echo '<thead><tr style="border-bottom: 1px solid #444;"><th style="text-align:left; padding:0.5rem;">Produk</th><th style="text-align:center; padding:0.5rem;">Qty</th><th style="text-align:right; padding:0.5rem;">Harga Satuan</th><th style="text-align:right; padding:0.5rem;">Subtotal</th></tr></thead>';
echo '<tbody>';

$grandTotal = 0;
while ($row = $res->fetch_assoc()) {
    echo '<tr>';
    echo '<td style="padding:0.5rem;">' . htmlspecialchars($row['product_name']) . '</td>';
    echo '<td style="text-align:center; padding:0.5rem;">' . $row['quantity'] . '</td>';
    echo '<td style="text-align:right; padding:0.5rem;">Rp ' . number_format($row['unit_price'], 0, ',', '.') . '</td>';
    echo '<td style="text-align:right; padding:0.5rem;">Rp ' . number_format($row['subtotal'], 0, ',', '.') . '</td>';
    echo '</tr>';
    $grandTotal += $row['subtotal'];
}
echo '</tbody>';
echo '<tfoot>';
echo '<tr style="border-top: 1px solid #444; font-weight:bold;"><td colspan="3" style="text-align:right; padding:1rem;">Subtotal</td><td style="text-align:right; padding:1rem;">Rp ' . number_format($grandTotal, 0, ',', '.') . '</td></tr>';
$tax = $grandTotal * 0.1;
echo '<tr><td colspan="3" style="text-align:right; padding:0.5rem;">Pajak (10%)</td><td style="text-align:right; padding:0.5rem;">Rp ' . number_format($tax, 0, ',', '.') . '</td></tr>';
echo '<tr style="color: var(--primary); font-size: 1.1em;"><td colspan="3" style="text-align:right; padding:0.5rem;">TOTAL</td><td style="text-align:right; padding:0.5rem;">Rp ' . number_format($grandTotal + $tax, 0, ',', '.') . '</td></tr>';
echo '</tfoot>';
echo '</table>';
?>

