<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>
<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header-mobile">
        <div class="brand">Bellen Beans Coffee</div>
        <button class="icon-btn" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
    </div>
    <div class="brand desktop-only">
        <i class="fas fa-mug-hot"></i> Bellen Beans Coffee 
    </div>
    
    <ul class="nav-links">
        <?php if (in_array($role, ['admin', 'manajer', 'owner'])): ?>
            <li class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            </li>
            <?php if ($role === 'admin'): ?>
            <li class="nav-item <?= $current_page == 'products.php' ? 'active' : '' ?>">
                <a href="products.php"><i class="fas fa-box"></i> Kelola Stok/Produk</a>
            </li>
            <li class="nav-item <?= $current_page == 'users.php' ? 'active' : '' ?>">
                <a href="users.php"><i class="fas fa-users-cog"></i> Kelola Pengguna</a>
            </li>
            <?php endif; ?>
        <?php elseif ($role === 'barista'): ?>
            <!-- Menu Khusus Barista (Fokus KDS) -->
            <li class="nav-item active">
                <a href="dashboard.php"><i class="fas fa-tv"></i> Kitchen Display</a>
            </li>
        <?php else: ?>
            <!-- Menu Kasir -->
            <li class="nav-item active">
                <a href="dashboard.php"><i class="fas fa-th-large"></i> Menu Order</a>
            </li>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'manajer', 'kasir', 'owner'])): ?>
        <li class="nav-item <?= $current_page == 'history.php' ? 'active' : '' ?>">
            <a href="history.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
        </li>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'manajer', 'kasir', 'owner'])): ?>
        <li class="nav-item <?= $current_page == 'customers.php' ? 'active' : '' ?>">
            <a href="customers.php"><i class="fas fa-users"></i> Pelanggan</a>
        </li>
        <li class="nav-item">
            <a href="loyalty.php" target="_blank" style="color: var(--primary);"><i class="fas fa-crown"></i> Portal Member</a>
        </li>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'manajer', 'owner'])): ?>
        <li class="nav-item <?= $current_page == 'expenses.php' ? 'active' : '' ?>">
            <a href="expenses.php"><i class="fas fa-wallet"></i> Pengeluaran</a>
        </li>
        <li class="nav-item <?= $current_page == 'settings.php' ? 'active' : '' ?>">
            <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="user-profile">
        <div class="avatar">
            <?= isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'U' ?>
        </div>
        <div class="user-info">
            <div class="username"><?= $_SESSION['full_name'] ?? 'User' ?></div>
            <div class="role"><?= ucfirst($role) ?></div>
        </div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</aside>
