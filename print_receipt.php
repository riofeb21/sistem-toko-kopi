<?php
require_once 'config/database.php';
require_once 'config/settings_helper.php';

if (!isset($_GET['id'])) {
    die("Transaksi tidak ditemukan.");
}

$invoiceCode = $_GET['id'];
$storeName = getSetting('store_name') ?: 'Toko Kopi';
$storeAddress = getSetting('store_address') ?: 'Jl. Kopi No. 1';
$storePhone = getSetting('store_phone') ?: '';
$footerNote = getSetting('footer_note') ?: 'Terima Kasih!';

// Fetch Transaction
$stmt = $conn->prepare("SELECT t.*, u.full_name as cashier_name FROM transactions t LEFT JOIN users u ON t.user_id = u.user_id WHERE t.invoice_code = ?");
$stmt->bind_param("s", $invoiceCode);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction) {
    die("Transaksi tidak ditemukan.");
}

// Fetch Details
$stmt_det = $conn->prepare("SELECT d.*, p.product_name FROM transactiondetails d JOIN products p ON d.product_id = p.product_id WHERE d.transaction_id = ?");
$stmt_det->bind_param("i", $transaction['transaction_id']);
$stmt_det->execute();
$details = $stmt_det->get_result();

// Calculate Tax dynamically based on grand total in DB
$taxRateRaw = getSetting('tax_rate');
$taxRate = $taxRateRaw / 100;
// Subtotal = Total / (1 + Rate)
$subtotal = $transaction['total_amount'] / (1 + $taxRate);
$taxAmount = $transaction['total_amount'] - $subtotal;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk <?= $invoiceCode ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 80mm; /* Standar kertas thermal */
            margin: 0 auto;
            color: #000;
            background: #fff;
            padding: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        table { width: 100%; font-size: 12px; }
        td { vertical-align: top; }
        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            background: #333;
            color: #fff;
            text-align: center;
            text-decoration: none;
            margin-bottom: 20px;
            cursor: pointer;
            border: none;
        }
        @media print {
            .btn-print { display: none; }
            body { margin: 0; padding: 0; width: auto; }
        }
    </style>

    <?php @include 'includes/analytics.php'; ?>
</head>
<body onload="window.print()">
    
    <button class="btn-print" onclick="window.print()">Cetak Struk</button>

    <div class="text-center">
        <h2 style="margin:0;"><?= $storeName ?></h2>
        <p style="margin:5px 0; font-size:12px;"><?= $storeAddress ?></p>
        <p style="margin:0; font-size:12px;"><?= $storePhone ?></p>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td>No. Inv</td>
            <td class="text-right"><?= $transaction['invoice_code'] ?></td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td class="text-right"><?= date('d/m/Y H:i', strtotime($transaction['transaction_date'])) ?></td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td class="text-right"><?= $transaction['cashier_name'] ?></td>
        </tr>
        <tr>
            <td>Pelanggan</td>
            <td class="text-right"><?= $transaction['customer_name'] ?: 'Guest' ?></td>
        </tr>
        <tr>
            <td>Jenis Pesanan</td>
            <td class="text-right"><?= $transaction['order_type'] === 'dine_in' ? 'Makan di Tempat' : 'Bawa Pulang' ?></td>
        </tr>
         <tr>
            <td>Metode</td>
            <td class="text-right"><?= strtoupper($transaction['payment_method']) ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <?php 
        $calcSubtotal = 0;
        while($row = $details->fetch_assoc()): 
            $calcSubtotal += $row['subtotal'];
        ?>
        <tr>
            <td colspan="2" class="bold"><?= $row['product_name'] ?></td>
        </tr>
        <tr>
            <td><?= $row['quantity'] ?> x <?= number_format($row['unit_price'], 0, ',', '.') ?></td>
            <td class="text-right"><?= number_format($row['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="divider"></div>

    <!-- Get Discount & Point Data (Safe Fallback) -->
    <?php
    $discount = isset($transaction['discount_amount']) ? $transaction['discount_amount'] : 0;
    $pointsVal = isset($transaction['points_used']) ? ($transaction['points_used'] * 100) : 0; // Assuming 100 rate
    
    // Tax Calculation (Backwards from Grand Total or Forward from taxable?)
    // Tax = (Subtotal - Div - Points) * Rate
    // Let's rely on Math: TaxAmount = GrandTotal - (GrandTotal / (1+Rate)) ? No because of discounts.
    // Simplest: Tax = Total - (TaxableBase)
    // TaxableBase = Subtotal - Disc - Points
    $taxableBase = max(0, $calcSubtotal - $discount - $pointsVal);
    $realTax = $transaction['total_amount'] - $taxableBase;
    
    // Fallback if tax is slightly off due to rounding, just display Total - Taxable
    ?>

    <table>
        <tr>
            <td>Subtotal</td>
            <td class="text-right">Rp <?= number_format($calcSubtotal, 0, ',', '.') ?></td>
        </tr>
        
        <?php if ($discount > 0): ?>
        <tr>
            <td>Diskon</td>
            <td class="text-right" style="color:red;">-Rp <?= number_format($discount, 0, ',', '.') ?></td>
        </tr>
        <?php endif; ?>

        <?php if ($pointsVal > 0): ?>
        <tr>
            <td>Poin (<?= $transaction['points_used'] ?>)</td>
            <td class="text-right" style="color:red;">-Rp <?= number_format($pointsVal, 0, ',', '.') ?></td>
        </tr>
        <?php endif; ?>

        <tr>
            <td>Pajak (<?= $taxRateRaw ?>%)</td>
            <td class="text-right">Rp <?= number_format($realTax, 0, ',', '.') ?></td>
        </tr>
        <tr class="bold" style="font-size: 14px;">
            <td>TOTAL</td>
            <td class="text-right">Rp <?= number_format($transaction['total_amount'], 0, ',', '.') ?></td>
        </tr>
        <?php if ($transaction['payment_method'] === 'cash'): ?>
        <tr>
            <td>Bayar</td>
            <td class="text-right">Rp <?= number_format($transaction['cash_received'], 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="text-right">Rp <?= number_format($transaction['change_amount'], 0, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="divider"></div>

    <div class="text-center">
        <p style="font-size:12px; margin-top:10px;"><?= $footerNote ?></p>
        <p style="font-size:10px;">Powered by Bellen Beans System</p>
    </div>

</body>
</html>

