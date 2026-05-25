<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Simple filter by date
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Query Transactions
$sql = "SELECT t.*, u.full_name as cashier_name 
        FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.user_id 
        WHERE DATE(t.transaction_date) BETWEEN ? AND ? 
        ORDER BY t.transaction_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$transactions = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Bellen Beans Coffee</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/mobile-nav.js" defer></script>
    <style>
         .history-container {
            background: var(--bg-surface);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        .filter-box {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: flex-end;
            background: rgba(255,255,255,0.02);
            padding: 1rem;
            border-radius: var(--radius-sm);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { color: var(--primary); }
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #86efac; }
        .detail-btn { cursor: pointer; color: var(--primary); text-decoration: underline; }
        
        /* Modal for Details */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--bg-surface);
            padding: 2rem;
            border-radius: var(--radius-md);
            width: 100%;
            max-width: 600px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }
    </style>

    <?php @include 'includes/analytics.php'; ?>
</head>
<body>
    <!-- Coffee Steam Decoration -->
    <div style="position: fixed; bottom: 0; left: 0; width: 100%; height: 300px; pointer-events: none; z-index: 0; overflow: hidden;">
        <div style="position: absolute; bottom: 20%; left: 10%; width: 100px; height: 150px;">
            <div class="steam"></div>
        </div>
        <div style="position: absolute; bottom: 15%; right: 15%; width: 100px; height: 150px;">
            <div class="steam"></div>
        </div>
    </div>

    <!-- Override grid for 2 columns only -->
    <div class="dashboard-layout" style="grid-template-columns: 260px 1fr;">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <h1>Riwayat Transaksi</h1>
            
            <div class="history-container">
                <form method="GET" class="filter-box">
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">Mulai Tanggal</label>
                        <input type="date" name="start_date" value="<?= $startDate ?>" class="form-input" style="padding: 0.5rem;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?= $endDate ?>" class="form-input" style="padding: 0.5rem;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem;"><i class="fas fa-filter"></i> Filter</button>
                    
                    <?php if (in_array($_SESSION['role'], ['admin', 'manajer', 'owner'])): ?>
                    <div style="display: flex; gap: 0.5rem; margin-left: auto;">
                        <button type="button" class="btn btn-outline" onclick="exportReport('excel')" style="border-color: #22c55e; color: #22c55e;">
                            <i class="fas fa-file-excel"></i> Ekspor Excel
                        </button>
                        <button type="button" class="btn btn-outline" onclick="exportReport('print')">
                            <i class="fas fa-print"></i> Cetak PDF
                        </button>
                    </div>
                    <?php endif; ?>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Tanggal & Jam</th>
                            <th>Pelanggan</th>
                            <th>Kasir</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions->num_rows > 0): ?>
                            <?php 
                            $currentDate = '';
                            while($row = $transactions->fetch_assoc()): 
                                $rowDate = date('Y-m-d', strtotime($row['transaction_date']));
                                if ($rowDate !== $currentDate): 
                                    $currentDate = $rowDate;
                            ?>
                                <tr style="background: rgba(212, 163, 115, 0.1);">
                                    <td colspan="7" style="font-weight: bold; color: var(--primary);">
                                        <i class="fas fa-calendar-alt"></i> <?= date('l, d F Y', strtotime($rowDate)) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td><?= $row['invoice_code'] ?></td>
                                <td><?= date('H:i', strtotime($row['transaction_date'])) ?></td>
                                <td><?= htmlspecialchars($row['customer_name'] ?? '-') ?></td>
                                <td><?= $row['cashier_name'] ?></td>
                                <td>Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                                <td><span class="badge badge-success"><?= strtoupper($row['payment_status']) ?></span></td>
                                <td>
                                    <span class="detail-btn" onclick="showDetail(<?= $row['transaction_id'] ?>, '<?= $row['invoice_code'] ?>')">Lihat Detail</span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">Tidak ada transaksi pada periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
        
        <!-- Dummy Right Sidebar or empty to keep layout grid -->
        <aside style="background: var(--bg-surface); border-left: 1px solid rgba(255,255,255,0.05);"></aside>
    </div>

    <!-- Detail Modal -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <span class="close-modal" onclick="document.getElementById('detailModal').classList.remove('active')" style="position: absolute; right: 1rem; top: 1rem; cursor: pointer; font-size: 1.5rem;">&times;</span>
            <h2 style="margin-bottom: 1rem;">Detail Transaksi <span id="modalInvoice" style="color: var(--primary); font-size: 0.8em;"></span></h2>
            <div id="detailContent">Loading...</div>
        </div>
    </div>

    <script>
        async function showDetail(id, invoice) {
            document.getElementById('detailModal').classList.add('active');
            document.getElementById('modalInvoice').innerText = invoice;
            document.getElementById('detailContent').innerHTML = 'Loading...';

            try {
                const res = await fetch('get_transaction_detail.php?id=' + id);
                const html = await res.text();
                document.getElementById('detailContent').innerHTML = html;
            } catch (e) {
                document.getElementById('detailContent').innerHTML = 'Gagal memuat detail.';
            }
        }

        function exportReport(format) {
            const start = document.querySelector('input[name="start_date"]').value;
            const end = document.querySelector('input[name="end_date"]').value;
            const url = `export_report.php?start_date=${start}&end_date=${end}&format=${format}`;
            
            if (format === 'print') {
                window.open(url, '_blank');
            } else {
                window.location.href = url;
            }
        }
    </script>
</body>
</html>

