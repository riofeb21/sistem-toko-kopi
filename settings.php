<?php
session_start();
require_once 'config/database.php';
require_once 'config/settings_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$msg = '';
$msgType = 'success'; // success or error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Handle Change Password
    if (isset($_POST['update_password'])) {
        $newPass = trim($_POST['new_password']);
        $confirmPass = trim($_POST['confirm_password']);
        
        if ($newPass !== $confirmPass) {
            $msg = "Konfirmasi password tidak cocok!";
            $msgType = 'error';
        } else {
            $userId = $_SESSION['user_id'];
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $newPass, $userId);
            if ($stmt->execute()) {
                $msg = "Password berhasil diubah!";
            } else {
                $msg = "Gagal mengubah password.";
                $msgType = 'error';
            }
        }
    }

    // 2. Handle Store Settings (Only Admin)
    if (isset($_POST['update_store']) && $_SESSION['role'] === 'admin') {
        $newSettings = [
            'store_name' => $_POST['store_name'],
            'store_address' => $_POST['store_address'],
            'store_phone' => $_POST['store_phone'],
            'tax_rate' => floatval($_POST['tax_rate']),
            'footer_note' => $_POST['footer_note'],
            'theme_color' => $_POST['theme_color'],
            'ga_measurement_id' => $_POST['ga_measurement_id'] ?? ''
        ];
        
        if (file_put_contents('config/app_settings.json', json_encode($newSettings, JSON_PRETTY_PRINT))) {
            $msg = "Pengaturan toko berhasil disimpan!";
            // Reload settings immediately
            $appSettings = array_merge($appSettings, $newSettings);
        } else {
            $msg = "Gagal menyimpan pengaturan toko.";
            $msgType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Bellen Beans Coffee</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/mobile-nav.js" defer></script>
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .settings-card {
            background: var(--bg-surface);
            padding: 2rem;
            border-radius: var(--radius-md);
            border: 1px solid rgba(255,255,255,0.05);
        }
        .settings-card h2 {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 1rem;
            font-size: 1.25rem;
            color: var(--primary);
        }
        .color-picker {
            width: 100%; height: 40px; padding: 0.2rem; background: var(--bg-body); border: 1px solid #444; border-radius: var(--radius-sm);
        }
    </style>

    <?php @include 'includes/analytics.php'; ?>
</head>
<body>
    <!-- Coffee Steam and Bubbles Decoration -->
    <div style="position: fixed; top: 0; right: 0; width: 200px; height: 200px; pointer-events: none; z-index: 0;">
        <div class="steam"></div>
    </div>
    <div style="position: fixed; bottom: 0; left: 0; width: 100%; height: 200px; pointer-events: none; z-index: 0; overflow: hidden;">
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <div class="dashboard-layout" style="grid-template-columns: 260px 1fr;">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <h1>Pengaturan Sistem</h1>

            <?php if ($msg): ?>
                <div style="background: <?= $msgType == 'success' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)' ?>; color: <?= $msgType == 'success' ? '#86efac' : '#fca5a5' ?>; padding: 1rem; margin-top: 1rem; border-radius: var(--radius-sm);">
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <div class="settings-grid">
                
                <!-- 1. Pengaturan Toko (Hanya Admin) -->
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="settings-card">
                    <h2><i class="fas fa-store"></i> Profil & Konfigurasi Toko</h2>
                    <form method="POST">
                        <input type="hidden" name="update_store" value="1">
                        
                        <div class="form-group">
                            <label>Nama Toko</label>
                            <input type="text" name="store_name" value="<?= getSetting('store_name') ?>" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Alamat Toko</label>
                            <input type="text" name="store_address" value="<?= getSetting('store_address') ?>" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label>No. Telepon Struk</label>
                            <input type="text" name="store_phone" value="<?= getSetting('store_phone') ?>" class="form-input">
                        </div>

                        <div class="flex gap-4">
                            <div class="form-group w-full">
                                <label>Pajak (%)</label>
                                <input type="number" step="0.1" name="tax_rate" value="<?= getSetting('tax_rate') ?>" class="form-input">
                            </div>
                            <div class="form-group w-full">
                                <label>Warna Tema</label>
                                <input type="color" name="theme_color" value="<?= getSetting('theme_color') ?>" class="color-picker">
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-chart-line" style="color: var(--primary);"></i> Google Analytics (Measurement ID)</label>
                            <input type="text" name="ga_measurement_id" value="<?= getSetting('ga_measurement_id') ?>" placeholder="Contoh: G-XXXXXXXXXX" class="form-input">
                            <small style="color: var(--text-muted);">Biarkan kosong jika tidak digunakan.</small>
                        </div>

                        <div class="form-group">
                            <label>Catatan Kaki Struk</label>
                            <textarea name="footer_note" class="form-input" rows="3"><?= getSetting('footer_note') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">Simpan Konfigurasi</button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- 2. Pengaturan Akun (Semua User) -->
                <div class="settings-card">
                    <h2><i class="fas fa-user-lock"></i> Keamanan Akun</h2>
                    <div style="margin-bottom: 2rem; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px;">
                        <p style="color: var(--text-muted); font-size: 0.9rem;">Anda login sebagai:</p>
                        <strong style="font-size: 1.1rem;"><?= $_SESSION['full_name'] ?></strong>
                        <span style="background: var(--primary); color: #000; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; margin-left: 0.5rem;"><?= strtoupper($_SESSION['role']) ?></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="update_password" value="1">
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" class="form-input" required placeholder="Minimal 6 karakter">
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="confirm_password" class="form-input" required placeholder="Ulangi password baru">
                        </div>
                        <button type="submit" class="btn btn-outline w-full">Update Password</button>
                    </form>
                </div>

            </div>
        </main>
        
        <aside style="background: var(--bg-surface); border-left: 1px solid rgba(255,255,255,0.05);"></aside>
    </div>
</body>
</html>

