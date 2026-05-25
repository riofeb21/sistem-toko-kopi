<?php
session_start();
require_once 'config/database.php';

// Access Control: Only Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $fullname = trim($_POST['full_name']);
        $role = $_POST['role'];
        
        // Secure duplicate check with Prepared Statement
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $msg = "Username sudah ada!";
        } else {
            // Hash password for security
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashedPassword, $fullname, $role);
            if ($stmt->execute()) {
                $msg = "Pengguna berhasil ditambahkan!";
            } else {
                $msg = "Gagal: " . $conn->error;
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['user_id'];
        if ($id == $_SESSION['user_id']) {
            $msg = "Tidak bisa menghapus akun sendiri!";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $msg = "Pengguna berhasil dihapus!";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $fullname = trim($_POST['full_name']);
        $role = $_POST['role'];

        if (!empty($password)) {
            // Hash new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, full_name=?, role=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $username, $hashedPassword, $fullname, $role, $id);
        } else {
            // Don't update password if empty
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, role=? WHERE user_id=?");
            $stmt->bind_param("sssi", $username, $fullname, $role, $id);
        }
        
        if ($stmt->execute()) {
            $msg = "Pengguna berhasil diperbarui!";
        }
    }
}

// Fetch Users
$users = $conn->query("SELECT * FROM users ORDER BY role ASC, full_name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/mobile-nav.js" defer></script>
    <style>
        /* Reuse styles FROM products.php */
        .table-container {
            background: var(--bg-surface);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-top: 2rem;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; color: var(--text-main); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { color: var(--primary); font-weight: 600; }
        tr:hover { background: rgba(255,255,255,0.02); }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-md); width: 100%; max-width: 500px; position: relative; border: 1px solid rgba(255,255,255,0.1); }
        .close-modal { position: absolute; top: 1rem; right: 1rem; color: var(--text-muted); cursor: pointer; font-size: 1.5rem; }
    </style>
</head>
</head>
<body>
    <!-- Floating Coffee Beans Decoration -->
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0;">
        <div class="coffee-bean"></div>
        <div class="coffee-bean"></div>
    </div>

    <div class="dashboard-layout" style="grid-template-columns: 260px 1fr;">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="flex justify-between items-center">
                <h1>Daftar Pengguna</h1>
                <button class="btn btn-primary" onclick="openModal('add')"><i class="fas fa-user-plus"></i> Tambah Pengguna</button>
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
                            <th>No</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td>
                                <span style="background: <?= $row['role'] == 'admin' ? 'var(--primary)' : '#555' ?>; padding: 4px 8px; border-radius: 4px; color: #121212; font-weight: bold; font-size: 0.8rem;">
                                    <?= strtoupper(htmlspecialchars($row['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-outline" style="padding: 0.5rem;" onclick='openModal("edit", <?= json_encode($row) ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus pengguna ini?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                    <button type="submit" class="btn btn-outline" style="padding: 0.5rem; color: var(--accent-red); border-color: var(--accent-red);">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
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
    <div class="modal" id="userModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle" style="margin-bottom: 1.5rem;">Tambah Pengguna</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="user_id" id="userId">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" id="fullName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="role" class="form-input" style="background: rgba(255,255,255,0.03); color: white;">
                        <option value="kasir" style="background: #333;">Kasir</option>
                        <option value="admin" style="background: #333;">Admin</option>
                        <option value="barista" style="background: #333;">Barista</option>
                        <option value="manajer" style="background: #333;">Manajer</option>
                        <option value="owner" style="background: #333;">Owner</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="passwordLabel">Password</label>
                    <input type="password" name="password" id="password" class="form-input" placeholder="Isi untuk ubah/set password">
                </div>

                <button type="submit" class="btn btn-primary w-full">Simpan</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(mode, data = null) {
            const modal = document.getElementById('userModal');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            
            // Inputs
            const idInput = document.getElementById('userId');
            const userInput = document.getElementById('username');
            const nameInput = document.getElementById('fullName');
            const roleInput = document.getElementById('role');
            const passInput = document.getElementById('password');

            if (mode === 'edit' && data) {
                title.innerText = 'Edit Pengguna';
                action.value = 'edit';
                idInput.value = data.user_id;
                userInput.value = data.username;
                nameInput.value = data.full_name;
                roleInput.value = data.role;
                passInput.required = false; 
                passInput.placeholder = "Kosongkan jika tidak ingin ganti password";
            } else {
                title.innerText = 'Tambah Pengguna';
                action.value = 'add';
                idInput.value = '';
                userInput.value = '';
                nameInput.value = '';
                roleInput.value = 'kasir';
                passInput.required = true;
                passInput.placeholder = "";
            }
            
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }
    </script>
</body>
</html>

