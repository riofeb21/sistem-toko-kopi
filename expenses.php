<?php
session_start();
require_once 'config/database.php';

// Access Control: Only Admin, Manajer, Owner
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manajer', 'owner'])) {
    header("Location: dashboard.php");
    exit;
}

$msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $desc = trim($_POST['description']);
        $amount = $_POST['amount'];
        $category = $_POST['category'];
        $userId = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO expenses (description, amount, category, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdsi", $desc, $amount, $category, $userId);
        if ($stmt->execute()) {
            $msg = "Pengeluaran berhasil dicatat!";
        } else {
            $msg = "Gagal: " . $conn->error;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['expense_id'];
        $stmt = $conn->prepare("DELETE FROM expenses WHERE expense_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $msg = "Catatan pengeluaran dihapus!";
        }
    }
}

// Fetch Expenses
$expenses = $conn->query("SELECT e.*, u.full_name as creator FROM expenses e LEFT JOIN users u ON e.created_by = u.user_id ORDER BY e.expense_date DESC");

// Calculate total expense this month
$monthTotalQuery = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())");
$monthTotal = $monthTotalQuery->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengeluaran - Bellen Beans Coffee</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/mobile-nav.js" defer></script>
    <style>
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-mini-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            text-align: center;
        }
        .stat-mini-card h3 { color: var(--accent-red); font-size: 1.8rem; margin: 0.5rem 0; }
        .table-container { background: var(--bg-surface); border-radius: var(--radius-md); padding: 1.5rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; color: var(--text-main); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { color: var(--primary); }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-md); width: 100%; max-width: 500px; position: relative; border: 1px solid rgba(255,255,255,0.1); }
    </style>

    <?php @include 'includes/analytics.php'; ?>
</head>
<body>
    <div class="dashboard-layout" style="grid-template-columns: 260px 1fr;">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="flex justify-between items-center" style="margin-bottom: 2rem;">
                <h1>Manajemen Pengeluaran</h1>
                <button class="btn btn-primary" onclick="toggleModal(true)"><i class="fas fa-plus"></i> Catat Pengeluaran</button>
            </div>

            <div class="stats-summary">
                <div class="stat-mini-card">
                    <p style="color:var(--text-muted);">Total Pengeluaran Bulan Ini</p>
                    <h3>Rp <?= number_format($monthTotal, 0, ',', '.') ?></h3>
                </div>
            </div>

            <?php if($msg): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid rgba(16,185,129,0.2);">
                    <i class="fas fa-check-circle"></i> <?= $msg ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Kategori</th>
                            <th>Oleh</th>
                            <th>Jumlah (Rp)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $expenses->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['expense_date'])) ?></td>
                            <td><?= $row['description'] ?></td>
                            <td><span style="background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:4px; font-size:0.8rem;"><?= $row['category'] ?></span></td>
                            <td style="color:var(--text-muted); font-size:0.9rem;"><?= $row['creator'] ?></td>
                            <td class="bold" style="color:var(--accent-red);">- Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
                            <td>
                                <button onclick="deleteExpense(<?= $row['expense_id'] ?>)" class="btn btn-sm btn-outline" style="color:var(--accent-red);"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal Form -->
    <div class="modal" id="expenseModal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem;">Catat Pengeluaran Baru</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Keterangan / Deskripsi</label>
                    <input type="text" name="description" class="form-input" placeholder="Contoh: Beli Susu 10 Liter" required>
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Kategori</label>
                    <select name="category" class="form-input" style="background:#1a1a1a; color:white;">
                        <option value="Bahan Baku">Bahan Baku</option>
                        <option value="Operasional">Operasional</option>
                        <option value="Listrik/Air">Listrik/Air</option>
                        <option value="Gaji Karyawan">Gaji Karyawan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label>Jumlah (Rp)</label>
                    <input type="number" name="amount" class="form-input" placeholder="0" required>
                </div>
                <div style="display:flex; gap:1rem;">
                    <button type="button" class="btn btn-outline" onclick="toggleModal(false)" style="flex:1;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(show) {
            document.getElementById('expenseModal').classList.toggle('active', show);
        }
        function deleteExpense(id) {
            if(confirm('Hapus catatan pengeluaran ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="expense_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

