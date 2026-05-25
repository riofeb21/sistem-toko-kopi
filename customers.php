<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = trim($_POST['customer_name']);
        $phone = trim($_POST['phone_number']);
        
        $stmt = $conn->prepare("INSERT INTO customers (customer_name, phone_number) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $phone);
        if ($stmt->execute()) {
            $msg = "Pelanggan berhasil ditambahkan!";
        } else {
            $msg = "Gagal: " . $conn->error;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['customer_id'];
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $msg = "Pelanggan berhasil dihapus!";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['customer_id'];
        $name = trim($_POST['customer_name']);
        $phone = trim($_POST['phone_number']);

        $stmt = $conn->prepare("UPDATE customers SET customer_name=?, phone_number=? WHERE customer_id=?");
        $stmt->bind_param("ssi", $name, $phone, $id);
        if ($stmt->execute()) {
            $msg = "Data pelanggan berhasil diperbarui!";
        }
    }
}

$customers = $conn->query("SELECT * FROM customers ORDER BY customer_name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelanggan - Bellen Beans Coffee</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/mobile-nav.js" defer></script>
    <style>
        .table-container { background: var(--bg-surface); border-radius: var(--radius-md); padding: 1.5rem; margin-top: 2rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; color: var(--text-main); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { color: var(--primary); font-weight: 600; }
        tr:hover { background: rgba(255,255,255,0.02); }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-md); width: 100%; max-width: 500px; position: relative; border: 1px solid rgba(255,255,255,0.1); }
        .close-modal { position: absolute; top: 1rem; right: 1rem; color: var(--text-muted); cursor: pointer; font-size: 1.5rem; }
    </style>

    <?php @include 'includes/analytics.php'; ?>
</head>
<body>
    <!-- Floating Coffee Beans Decoration -->
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0;">
        <div class="coffee-bean"></div>
        <div class="coffee-bean"></div>
        <div class="coffee-bean"></div>
        <div class="coffee-bean"></div>
    </div>

    <div class="dashboard-layout" style="grid-template-columns: 260px 1fr;">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="flex justify-between items-center" style="gap: 2rem;">
                <h1>Data Pelanggan (Member)</h1>
                <div class="search-box" style="flex: 1; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="text" id="custSearch" placeholder="Cari nama atau telepon..." style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; background: var(--bg-surface); border: 1px solid rgba(255,255,255,0.1); border-radius: 50px; color: white;">
                </div>
                <button class="btn btn-primary" onclick="openModal('add')"><i class="fas fa-plus"></i> Tambah Pelanggan</button>
            </div>

            <?php if ($msg): ?>
                <div style="background: rgba(16, 185, 129, 0.2); color: #86efac; padding: 1rem; margin-top: 1rem; border-radius: var(--radius-sm);">
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>No. Telepon</th>
                            <th>Poin Loyalty</th>
                            <th>Status Rank</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="custTableBody">
                        <?php while($row = $customers->fetch_assoc()): 
                            $pts = $row['loyalty_points'] ?? 0;
                            $rank = 'Bronze';
                            $rankColor = '#cd7f32';
                            if ($pts > 500) { $rank = 'Gold'; $rankColor = '#ffd700'; }
                            elseif ($pts > 200) { $rank = 'Silver'; $rankColor = '#c0c0c0'; }
                        ?>
                        <tr>
                            <td><i class="fas fa-user-circle" style="margin-right:0.5rem; opacity:0.5;"></i> <?= $row['customer_name'] ?></td>
                            <td><?= $row['phone_number'] ?></td>
                            <td class="bold" style="color: var(--primary);"><?= number_format($pts, 0, ',', '.') ?> Pts</td>
                            <td><span style="padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; background: <?= $rankColor ?>22; color: <?= $rankColor ?>; border: 1px solid <?= $rankColor ?>44; font-weight: bold;"><?= strtoupper($rank) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline" onclick="openModal('edit', <?= htmlspecialchars(json_encode($row)) ?>)"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline" style="color:var(--accent-red)" onclick="deleteCust(<?= $row['customer_id'] ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
        
        <aside style="background: var(--bg-surface); border-left: 1px solid rgba(255,255,255,0.05);"></aside>
    </div>

    <!-- Modal -->
    <div class="modal" id="customerModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle" style="margin-bottom: 1.5rem;">Tambah Pelanggan</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="customer_id" id="customerId">
                
                <div class="form-group">
                    <label>Nama Pelanggan</label>
                    <input type="text" name="customer_name" id="customerName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>No. Telepon / WA</label>
                    <input type="text" name="phone_number" id="phoneNumber" class="form-input">
                </div>

                <button type="submit" class="btn btn-primary w-full">Simpan</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(mode, data = null) {
            const modal = document.getElementById('customerModal');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            const idInput = document.getElementById('customerId');
            const nameInput = document.getElementById('customerName');
            const phoneInput = document.getElementById('phoneNumber');

            if (mode === 'edit' && data) {
                title.innerText = 'Edit Pelanggan';
                action.value = 'edit';
                idInput.value = data.customer_id;
                nameInput.value = data.customer_name;
                phoneInput.value = data.phone_number;
            } else {
                title.innerText = 'Tambah Pelanggan';
                action.value = 'add';
                idInput.value = '';
                nameInput.value = '';
                phoneInput.value = '';
            }
            
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('customerModal').classList.remove('active');
        }

        function deleteCust(id) {
            if (confirm('Hapus data pelanggan ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="customer_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Real-time Search
        document.getElementById('custSearch').addEventListener('input', function(e) {
            const q = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#custTableBody tr');
            
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    </script>
</body>
</html>

