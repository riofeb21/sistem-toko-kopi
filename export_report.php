<?php
require_once 'config/database.php';
require_once 'config/settings_helper.php';

session_start();
// Access Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manajer', 'owner'])) {
    die("Akses Ditolak");
}

// Get Filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$format = isset($_GET['format']) ? $_GET['format'] : 'excel'; // excel (csv) or print (html layout)

// Query Data
$sql = "SELECT t.*, u.full_name as cashier_name 
        FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.user_id 
        WHERE DATE(t.transaction_date) BETWEEN ? AND ? 
        ORDER BY t.transaction_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$storeName = getSetting('store_name');

if ($format === 'excel') {
    // FIX: Bersihkan output buffer supaya tidak ada spasi/error nyasar di dalam file Excel
    if (ob_get_length()) ob_clean();
    
    // Export to Excel (.xls HTML format for styling)
    $filename = "Laporan_Penjualan_" . $startDate . "_sd_" . $endDate . ".xls";
    
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Output HTML Table
    echo '<!DOCTYPE html>';
    echo '<html><head><meta charset="UTF-8">';
    echo '<style>
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #FFEEBA; border: 1px solid #000; padding: 10px; text-align: center; }
            td { border: 1px solid #000; padding: 5px; vertical-align: middle; }
            .num { mso-number-format:"\#\,\#\#0"; text-align: right; }
            .text { mso-number-format:"\@"; }
          </style>';
    echo '</head><body>';
    
    echo '<h2 style="text-align:center;">LAPORAN PENJUALAN - ' . strtoupper($storeName) . '</h2>';
    echo '<p style="text-align:center;">Periode: ' . $startDate . ' s/d ' . $endDate . '</p>';
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>
            <th width="180">No Invoice</th>
            <th width="140">Tanggal</th>
            <th width="130">Kasir</th>
            <th width="130">Pelanggan</th>
            <th width="100">Tipe Order</th>
            <th width="100">Metode Bayar</th>
            <th width="120">Subtotal</th>
            <th width="100">Diskon (-Rp)</th>
            <th width="100">Poin (-Rp)</th>
            <th width="100">Pajak</th>
            <th width="140">Total (Rp)</th>
          </tr>';
    echo '</thead><tbody>';
    
    $totalIncome = 0;
    while ($row = $result->fetch_assoc()) {
        // Safe null checks
        $discount = isset($row['discount_amount']) ? $row['discount_amount'] : 0;
        $pointsVal = isset($row['points_used']) ? ($row['points_used'] * 100) : 0;
        
        // Logic Re-check to display base Subtotal correctly
        // NetTotal = Subtotal - Disc - Points + Tax
        // We want to show "Gross Subtotal" before discount? Or Net?
        // Let's show: Revenue before Tax/Disc.
        // Reverse calc: Taxable = Total - Tax. 
        // GrossSubtotal = Taxable + Disc + Points.
        
        $taxRate = getSetting('tax_rate') / 100;
        // Total = Taxable * (1+Rate)
        $taxable = $row['total_amount'] / (1 + $taxRate);
        $tax = $row['total_amount'] - $taxable;
        $grossSubtotal = $taxable + $discount + $pointsVal;

        $totalIncome += $row['total_amount'];
        
        echo '<tr>';
        echo '<td class="text">' . $row['invoice_code'] . '</td>';
        echo '<td class="text">' . date('d/m/Y H:i', strtotime($row['transaction_date'])) . '</td>';
        echo '<td>' . $row['cashier_name'] . '</td>';
        echo '<td>' . ($row['customer_name'] ?: 'Guest') . '</td>';
        echo '<td>' . ($row['order_type'] === 'dine_in' ? 'Dine In' : 'Take Away') . '</td>';
        echo '<td>' . strtoupper($row['payment_method']) . '</td>';
        echo '<td class="num">' . $grossSubtotal . '</td>';
        echo '<td class="num" style="color:red;">' . ($discount > 0 ? -1*$discount : 0) . '</td>';
        echo '<td class="num" style="color:red;">' . ($pointsVal > 0 ? -1*$pointsVal : 0) . '</td>';
        echo '<td class="num">' . $tax . '</td>';
        echo '<td class="num" style="font-weight:bold;">' . $row['total_amount'] . '</td>';
        echo '</tr>';
    }
    
    echo '<tr><td colspan="11" style="background:#000; height:2px;"></td></tr>';
    echo '<tr>
            <td colspan="10" style="text-align:right; font-weight:bold; background:#eee;">TOTAL PENDAPATAN BERSIH</td>
            <td class="num" style="font-weight:bold; background:#FFEEBA;">' . $totalIncome . '</td>
          </tr>';
    echo '</tbody></table>';
    echo '</body></html>';
    
    exit;

} else {
    // Format Cetak / PDF (HTML View) with same logic for safety check
    // Re-query needed? No, PHP pointer is at end.
    // Actually while loop consumed the result set. We need to reset pointer or re-query.
    $result->data_seek(0); 
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Penjualan <?= $startDate ?> - <?= $endDate ?></title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 13px; }
            th { background-color: #f4f4f4; }
            .total-row { font-weight: bold; background-color: #fafafa; }
            .text-right { text-align: right; }
            @media print {
                .no-print { display: none; }
                body { padding: 0; }
            }
        </style>
    </head>
    <body onload="window.print()">
        <div class="no-print" style="margin-bottom: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Cetak Laporan</button>
            <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">Tutup</button>
        </div>

        <div class="header">
            <h1 style="margin: 0;"><?= strtoupper($storeName) ?></h1>
            <p style="margin: 5px 0;">LAPORAN PENJUALAN HARIAN</p>
            <p style="margin: 5px 0; font-size: 14px;">Periode: <?= date('d M Y', strtotime($startDate)) ?> s/d <?= date('d M Y', strtotime($endDate)) ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Tipe</th>
                    <th>Metode</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">Disc/Poin</th>
                    <th class="text-right">Pajak</th>
                    <th class="text-right">Total (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $grandTotal = 0;
                while($row = $result->fetch_assoc()): 
                    $grandTotal += $row['total_amount'];
                    
                    $discount = isset($row['discount_amount']) ? $row['discount_amount'] : 0;
                    $pointsVal = isset($row['points_used']) ? ($row['points_used'] * 100) : 0;
                    $totalReduction = $discount + $pointsVal;
                    
                    $taxRate = getSetting('tax_rate') / 100;
                    $taxable = $row['total_amount'] / (1 + $taxRate);
                    $tax = $row['total_amount'] - $taxable;
                    $grossSubtotal = $taxable + $totalReduction;
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $row['invoice_code'] ?></td>
                    <td><?= date('d/m H:i', strtotime($row['transaction_date'])) ?></td>
                    <td><?= $row['customer_name'] ?: 'Guest' ?></td>
                    <td><?= $row['order_type'] === 'dine_in' ? 'Dine In' : 'Take Away' ?></td>
                    <td><?= strtoupper($row['payment_method']) ?></td>
                    <td class="text-right"><?= number_format($grossSubtotal, 0, ',', '.') ?></td>
                    <td class="text-right" style="color:<?= $totalReduction > 0 ? 'red' : 'inherit' ?>;">
                        <?= $totalReduction > 0 ? '-' . number_format($totalReduction, 0, ',', '.') : '-' ?>
                    </td>
                    <td class="text-right"><?= number_format($tax, 0, ',', '.') ?></td>
                    <td class="text-right" style="font-weight:bold;"><?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="9" class="text-right">TOTAL PENDAPATAN BERSIH</td>
                    <td class="text-right">Rp <?= number_format($grandTotal, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 50px; display: flex; justify-content: flex-end;">
            <div style="text-align: center; width: 200px;">
                <p>Dicetak pada: <?= date('d/m/Y H:i') ?></p>
                <div style="height: 80px;"></div>
                <p style="border-top: 1px solid #000; padding-top: 5px;">Manajer / Owner</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>

