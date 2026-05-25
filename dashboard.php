<?php
session_start();
// Auto-Logout 1 Jam (Security)
require_once 'config/session_manager.php';

error_reporting(0); // Production mode

require_once 'config/database.php';
require_once 'config/settings_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];

// Helper function to safely fetch single value
function safeFetch($conn, $sql, $field) {
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return $row[$field] ?? 0;
    }
    return 0;
}

// --- STATS VIEW (Admin, Manajer, Owner) ---
if (in_array($role, ['admin', 'manajer', 'owner'])) {
    // 1. Total Income Today
    $incomeToday = safeFetch($conn, "SELECT SUM(total_amount) as total FROM transactions WHERE DATE(transaction_date) = CURDATE() AND payment_status = 'paid'", 'total');

    // 2. Total Transactions Today
    $trxToday = safeFetch($conn, "SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()", 'total');

    // 3. Low Stock Items (< 10)
    $lowStock = safeFetch($conn, "SELECT COUNT(*) as total FROM products WHERE stock < 10", 'total');

    // 4. Total Registered Members (Pelanggan)
    $totalCust = safeFetch($conn, "SELECT COUNT(*) as total FROM customers", 'total');

    // Recent Critical Stock
    $criticalItems = $conn->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5");
    if (!$criticalItems) {
        echo "<!-- Query Warning: Products table might be missing or empty -->";
        // Dummy object to prevent loop error
        $criticalItems = new mysqli_result($conn); 
    }

    // --- CHART DATA (Last 7 Days) ---
    $chartData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $total = safeFetch($conn, "SELECT SUM(total_amount) as total FROM transactions WHERE DATE(transaction_date) = '$date' AND payment_status = 'paid'", 'total');
        $chartData[] = ['date' => date('d M', strtotime($date)), 'total' => (int)$total];
    }
    $chartLabels = json_encode(array_column($chartData, 'date'));
    $chartValues = json_encode(array_column($chartData, 'total'));

    // 5. Customer Insights (Dine-in vs Take-away) Today
    $dineInCount = safeFetch($conn, "SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_date) = CURDATE() AND order_type = 'dine_in'", 'total');
    $takeAwayCount = safeFetch($conn, "SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_date) = CURDATE() AND order_type = 'take_away'", 'total');

    // 6. Jam Sibuk (Busiest Hour Today)
    $peakHourQuery = $conn->query("
        SELECT HOUR(transaction_date) as hour, COUNT(*) as count 
        FROM transactions 
        WHERE DATE(transaction_date) = CURDATE() 
        GROUP BY HOUR(transaction_date) 
        ORDER BY count DESC LIMIT 1
    ");
    $peakHourData = $peakHourQuery && $peakHourQuery->num_rows > 0 ? $peakHourQuery->fetch_assoc() : ['hour' => '-', 'count' => 0];
    $peakHourStr = $peakHourData['hour'] !== '-' ? sprintf("%02d:00 - %02d:00", $peakHourData['hour'], $peakHourData['hour'] + 1) : "Belum ada";

    // 7. Total Daily Expenses (Pre-fetching for Stats)
    $expensesToday = safeFetch($conn, "SELECT SUM(amount) as total FROM expenses WHERE DATE(expense_date) = CURDATE()", 'total');
    
    // 6. Total Monthly Expenses
    $expensesMonth = safeFetch($conn, "SELECT SUM(amount) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())", 'total');

    // 7. Total Monthly Omset
    $revenueMonth = safeFetch($conn, "SELECT SUM(total_amount) as total FROM transactions WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE()) AND payment_status = 'paid'", 'total');
    
    $netProfitMonth = ($revenueMonth ?? 0) - ($expensesMonth ?? 0);

} else {
// --- KASIR/BARISTA DATA FETCHING ---
    // Fetch Categories
    $categories = $conn->query("SELECT * FROM categories");
    if (!$categories) {
       // Silent fail or empty array
    }

    // Fetch Products
    $products = $conn->query("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.is_available = 1 AND p.stock > 0");
    if (!$products) {
        die("Error: Gagal mengambil data Produk. Pastikan tabel 'Products' ada. <br>Error SQL: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getSetting('store_name') ?> - <?= ucfirst($role) ?></title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/mobile-nav.js" defer></script>
    <style>
        /* Admin Dashboard Widgets */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); display: flex; align-items: center; gap: 1rem; border: 1px solid rgba(255,255,255,0.05); }
        .stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: rgba(212, 163, 115, 0.1); color: var(--primary); }
        .stat-info h3 { font-size: 2rem; margin: 0; color: var(--text-main); font-family: 'Outfit', sans-serif; }
        .stat-info p { margin: 0; color: var(--text-muted); font-size: 0.9rem; }

        /* Chart Container */
        .chart-container {
            background: var(--bg-surface);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.05);
            height: 400px;
        }

        /* Kasir Specific Styles (if needed, adapting from original) */
        .top-bar {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            gap: 1rem;
        }
        .top-bar .search-bar {
            position: relative;
            flex: 1;
        }
        .top-bar .search-bar i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        .top-bar .search-bar input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            background: var(--bg-surface);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 50px;
            color: white;
            outline: none;
        }
        .categories {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        .categories::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        .categories .filter-btn {
            background: var(--bg-surface);
            color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.05);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .categories .filter-btn:hover {
            background: rgba(255,255,255,0.1);
            color: var(--text-main);
        }
        .categories .filter-btn.active {
            background: var(--primary);
            color: var(--text-dark);
            border-color: var(--primary);
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            min-height: 50vh; /* Ensure grid takes space even if few items */
            padding-bottom: 2rem;
            align-content: start; /* Prevent stretch */
        }
        .product-card {
            background: var(--bg-surface);
            border-radius: var(--radius-md);
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s ease;
            border: 1px solid rgba(255,255,255,0.05);
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-3px);
        }
        .product-img {
            width: 100%;
            height: 120px;
            background-color: #2a2a2a;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
            color: #444;
        }
        .product-info {
            padding: 0.75rem;
        }
        .product-info h3 {
            font-size: 1rem;
            margin: 0 0 0.25rem 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-info p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin: 0 0 0.5rem 0;
        }
        .product-info .price {
            font-weight: bold;
            color: var(--primary);
        }
        .cart-sidebar {
            width: 100%;
            max-width: 350px;
            background: var(--bg-sidebar);
            border-left: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            height: 100vh; /* Fixed height is crucial */
            overflow: hidden; /* Container shouldn't scroll */
            position: relative; /* Fix for absolute positioning inside */
        }
        .cart-header h2 {
            font-size: 1.5rem;
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--text-main);
        }
        .cart-items {
            flex-grow: 1;
            overflow-y: auto;
            margin-bottom: 1rem;
            min-height: 0; /* Allows shrinking in flex container */
            padding-right: 5px;
        }
        .cart-items::-webkit-scrollbar { width: 4px; }
        .cart-items::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
        .cart-summary {
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary);
            margin-top: 1rem;
            margin-bottom: 1.5rem;
        }
        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: var(--text-dark);
            border: none;
            border-radius: var(--radius-md);
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .checkout-btn:hover {
            background: var(--primary-dark);
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
        .user-profile .avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #000;
            flex-shrink: 0;
        }
        .user-profile .user-info {
            flex: 1;
            overflow: hidden;
        }
        .user-profile .username {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-profile .role {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .user-profile .logout-btn {
            color: var(--accent-red);
            font-size: 1.2rem;
            text-decoration: none;
            flex-shrink: 0;
        }
    </style>

    <?php @include 'includes/analytics.php'; ?>
</head>
<body>
    <!-- Coffee Drips Decoration -->
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0;">
        <div class="coffee-drip"></div>
        <div class="coffee-drip"></div>
        <div class="coffee-drip"></div>
        <div class="coffee-drip"></div>
    </div>

    <div class="dashboard-layout">
        <!-- Mobile Header (Visible only on mobile via CSS) -->
        <div class="mobile-header">
            <button class="icon-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div class="brand-mobile">Bellen Beans Coffee</div>
            <?php if ($role !== 'admin'): ?>
            <button class="icon-btn" onclick="toggleCart()">
                <i class="fas fa-shopping-basket"></i>
                <span class="badge-count" id="mobileCartCount">0</span>
            </button>
            <?php else: ?>
            <div style="width: 40px;"></div> <!-- Spacer -->
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <?php if (in_array($role, ['admin', 'manajer', 'owner'])): ?>
            <!-- TAMPILAN ADMIN/MANAJER/OWNER (STATISTIK) -->
            <main class="main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-chart-line"></i> Dashboard Overview</h1>
                    <p>Selamat datang, <?= ucfirst($role) ?>. Berikut ringkasan toko hari ini.</p>
                </div>

                <!-- Stats Grid -->
                <?php 
                // LOGIC DASHBOARD: MANAJER & OWNER (Full Stats) vs ADMIN (Limited)
                if ($role === 'manajer' || $role === 'owner'): 
                ?>
                    <!-- Stats Grid (Full) -->
                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-coins"></i></div>
                            <div class="stat-info">
                                <p>Omset (Bulan Ini)</p>
                                <h3>Rp <?= number_format($revenueMonth, 0, ',', '.') ?></h3>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="color:var(--accent-red); background:rgba(239, 68, 68, 0.1);"><i class="fas fa-arrow-down"></i></div>
                            <div class="stat-info">
                                <p>Pengeluaran</p>
                                <h3>Rp <?= number_format($expensesMonth, 0, ',', '.') ?></h3>
                            </div>
                        </div>
                        <div class="stat-card" style="border: 1px solid var(--primary);">
                            <div class="stat-icon" style="color:var(--primary); background:rgba(212,163,115,0.2);"><i class="fas fa-briefcase"></i></div>
                            <div class="stat-info">
                                <p>Profit Bersih (Estimasi)</p>
                                <h3 style="color:var(--primary); font-weight:800;">Rp <?= number_format($netProfitMonth, 0, ',', '.') ?></h3>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5;"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="stat-info">
                                <p>Stok Menipis</p>
                                <h3><?= $lowStock ?></h3>
                            </div>
                        </div>
                    </div>

                    <!-- Dashboard Middle Section -->
                    <div class="middle-section" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem; margin-bottom: 2.5rem;">
                        
                        <!-- Sales Chart (Left) - DYNAMIC -->
                        <div class="chart-section" style="margin-bottom: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <h2 class="section-title" style="margin:0;"><i class="fas fa-chart-area"></i> Statistik Penjualan</h2>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="export_report.php?period=monthly" target="_blank" class="btn btn-outline btn-sm export-btn" style="border-color: var(--primary); color: var(--primary); text-decoration:none; display:flex; align-items:center; gap:0.5rem; padding: 0.4rem 0.8rem;">
                                        <i class="fas fa-file-csv"></i> Export CSV
                                    </a>
                                    <select id="chartFilter" onchange="updateChart(this.value)" style="background: var(--bg-body); color: white; border: 1px solid rgba(255,255,255,0.1); padding: 0.5rem; border-radius: 8px; outline: none; cursor: pointer;">
                                        <option value="daily">Harian (7 Hari)</option>
                                        <option value="monthly">Bulanan (Tahun Ini)</option>
                                        <option value="yearly">Tahunan (5 Tahun)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="chart-container" style="height: 400px; margin-bottom: 0;">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>

                        <!-- Live Kitchen Monitor (Right) -->
                        <div class="monitor-section">
                            <div class="section-title" style="display:flex; justify-content:space-between; align-items:center;">
                                <span><i class="fas fa-eye"></i> Dapur Live</span>
                                <div style="font-size: 0.8rem; display:flex; gap:0.5rem;">
                                    <span class="badge badge-danger" id="mon-queue">0 Q</span>
                                    <span class="badge badge-warning" id="mon-proc">0 P</span>
                                    <span class="badge badge-success" id="mon-ready">0 R</span>
                                </div>
                            </div>
                            <div class="table-container" style="height: 400px; overflow-y: auto; background: var(--bg-surface); border: 1px solid rgba(255,255,255,0.05); border-radius: var(--radius-md);">
                                <div id="kitchen-monitor-grid" style="display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem;">
                                    <!-- Data injected via JS -->
                                    <p style="text-align: center; color: var(--text-muted); margin-top: 2rem;">Memuat data dapur...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <style>
                        @media (max-width: 1024px) {
                            .middle-section {
                                grid-template-columns: 1fr !important;
                            }
                            .chart-container, .table-container {
                                height: 350px !important;
                            }
                        }
                    </style>

                    <?php endif; ?>

                    <?php if ($role === 'admin'): ?>
                    <!-- Low Stock Alert Table -->
                    <div class="table-section">
                        <div class="table-container">
                            <div class="table-header">
                                <h3><i class="fas fa-box-open"></i> Perlu Restock Segera</h3>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th>Harga</th>
                                        <th>Sisa Stok</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($item = $criticalItems->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?= $item['product_name'] ?></strong>
                                        </td>
                                        <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge badge-danger"><?= $item['stock'] ?> tersisa</span>
                                        </td>
                                        <td>
                                            <a href="products.php" class="btn btn-primary btn-sm">
                                                <i class="fas fa-box"></i> Restock
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if ($criticalItems->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="4">
                                            <div class="empty-state">
                                                <i class="fas fa-check-circle"></i>
                                                <h3>Semua Stok Aman!</h3>
                                                <p>Tidak ada produk yang perlu di-restock saat ini</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($role === 'admin'): ?>
                    <!-- Quick Actions & Recent Data (Admin Only) -->
                    <div class="middle-section admin-panels" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.5rem; margin-bottom: 2rem;">
                        
                        <!-- Quick Actions -->
                        <div class="panel" style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.05);">
                            <h3 style="margin-top:0; margin-bottom: 1.5rem; font-size: 1.2rem;"><i class="fas fa-bolt"></i> Aksi Cepat</h3>
                            <div class="quick-actions-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <a href="products.php" class="action-card" style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 8px; text-align: center; color: white; text-decoration: none; border: 1px solid rgba(255,255,255,0.05); transition: all 0.2s;">
                                    <i class="fas fa-box" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem; display: block;"></i>
                                    <span style="font-size: 0.9rem;">Kelola Produk</span>
                                </a>
                                <a href="users.php" class="action-card" style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 8px; text-align: center; color: white; text-decoration: none; border: 1px solid rgba(255,255,255,0.05); transition: all 0.2s;">
                                    <i class="fas fa-user-shield" style="font-size: 1.5rem; color: var(--accent-green); margin-bottom: 0.5rem; display: block;"></i>
                                    <span style="font-size: 0.9rem;">Kelola User</span>
                                </a>
                                <a href="customers.php" class="action-card" style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 8px; text-align: center; color: white; text-decoration: none; border: 1px solid rgba(255,255,255,0.05); transition: all 0.2s;">
                                    <i class="fas fa-address-book" style="font-size: 1.5rem; color: #a78bfa; margin-bottom: 0.5rem; display: block;"></i>
                                    <span style="font-size: 0.9rem;">Kelola Pelanggan</span>
                                </a>
                                <a href="settings.php" class="action-card" style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 8px; text-align: center; color: white; text-decoration: none; border: 1px solid rgba(255,255,255,0.05); transition: all 0.2s;">
                                    <i class="fas fa-cog" style="font-size: 1.5rem; color: var(--text-muted); margin-bottom: 0.5rem; display: block;"></i>
                                    <span style="font-size: 0.9rem;">Pengaturan</span>
                                </a>
                            </div>
                        </div>

                        <!-- Recent Products -->
                        <div class="panel" style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                <h3 style="margin:0; font-size: 1.2rem;"><i class="fas fa-history"></i> Produk Terbaru</h3>
                                <a href="products.php" style="font-size: 0.85rem; color: var(--primary); text-decoration: none;">Lihat Semua</a>
                            </div>
                            <div class="table-responsive">
                                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                                    <thead style="border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--text-muted);">
                                        <tr>
                                            <th style="text-align: left; padding-bottom: 0.75rem;">Produk</th>
                                            <th style="padding-bottom: 0.75rem; text-align: right;">Harga</th>
                                            <th style="padding-bottom: 0.75rem; text-align: center;">Stok</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $rpQuery = $conn->query("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.product_id DESC LIMIT 5");
                                        while($rp = $rpQuery->fetch_assoc()):
                                        ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                            <td style="padding: 0.75rem 0; font-weight: 500;"><?= htmlspecialchars($rp['product_name']) ?></td>
                                            <td style="padding: 0.75rem 0; text-align: right;">Rp <?= number_format($rp['price'], 0, ',', '.') ?></td>
                                            <td style="padding: 0.75rem 0; text-align: center;">
                                                <span class="badge" style="background: rgba(212, 163, 115, 0.1); color: var(--primary); padding: 2px 6px; border-radius: 4px;"><?= $rp['stock'] ?></span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Customer & Traffic Insights -->
                    <div class="panel" style="background: var(--bg-surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.05); margin-bottom: 2rem;">
                        <h3 style="margin-top:0; margin-bottom: 1.5rem; font-size: 1.2rem; color: var(--text-main);">
                            <i class="fas fa-users" style="color: var(--primary);"></i> Wawasan Pengunjung (Hari Ini)
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                            <!-- Total Visitors -->
                            <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); text-align: center;">
                                <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">Total Transaksi / Mampir</div>
                                <div style="font-size: 2rem; font-weight: bold; color: white;"><?= $trxToday ?></div>
                            </div>
                            
                            <!-- Dine In vs Take Away -->
                            <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); text-align: center;">
                                <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">Dine-In vs Take-Away</div>
                                <div style="display: flex; justify-content: center; gap: 1rem; font-size: 1.1rem; font-weight: bold;">
                                    <span style="color: var(--accent-green);"><i class="fas fa-utensils"></i> <?= $dineInCount ?></span>
                                    <span style="color: #6b7280;">|</span>
                                    <span style="color: var(--primary);"><i class="fas fa-shopping-bag"></i> <?= $takeAwayCount ?></span>
                                </div>
                            </div>

                            <!-- Busiest Hour -->
                            <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); text-align: center;">
                                <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">Jam Paling Sibuk</div>
                                <div style="font-size: 1.5rem; font-weight: bold; color: #fbbf24;">
                                    <i class="fas fa-clock" style="font-size: 1.2rem; margin-right: 5px;"></i> <?= $peakHourStr ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;"><?= $peakHourData['count'] ?> Transaksi</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </main>
            
            <!-- Notification Sound -->
            <audio id="notifSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

            <!-- Kitchen Monitor Script -->
            <script>
                // Auto-refresh Monitor every 5 seconds
                setInterval(fetchKitchenMonitor, 5000);
                
                let lastOrderCount = 0;
                let isFirstLoad = true;

                fetchKitchenMonitor(); 

                function fetchKitchenMonitor() {
                    fetch('get_kds_orders.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                renderMonitor(data.orders);
                                checkNewOrders(data.orders);
                            }
                        })
                        .catch(err => console.error('Monitor Error:', err));
                }

                function checkNewOrders(orders) {
                    const currentCount = orders.length;
                    
                    // If order count increases (and NOT first load), play sound!
                    if (currentCount > lastOrderCount && !isFirstLoad) {
                        playSound();
                    }

                    lastOrderCount = currentCount;
                    isFirstLoad = false;
                }

                function playSound() {
                    const audio = document.getElementById('notifSound');
                    if (audio) {
                        audio.currentTime = 0;
                        audio.play().catch(e => console.log('Autoplay blocked interaction required first.'));
                    }
                }

                function renderMonitor(orders) {
                    const grid = document.getElementById('kitchen-monitor-grid');
                    const qCount = document.getElementById('mon-queue');
                        const pCount = document.getElementById('mon-proc');
                        const rCount = document.getElementById('mon-ready');

                        if (!grid) return;

                        let counts = { queue: 0, processing: 0, ready: 0 };
                        grid.innerHTML = '';

                        if (orders.length === 0) {
                            grid.innerHTML = `
                                <div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: var(--text-muted); opacity: 0.6;">
                                    <i class="fas fa-mug-hot" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                    <p>Dapur sedang santai (Tidak ada pesanan aktif)</p>
                                </div>`;
                        }

                        orders.forEach(order => {
                            if (counts[order.status] !== undefined) counts[order.status]++;

                            // Status Color
                            let statColor = 'var(--text-muted)';
                            let statIcon = 'clock';
                            let statLabel = 'Queue';
                            
                            if (order.status === 'processing') {
                                statColor = 'var(--accent-yellow)';
                                statIcon = 'fire';
                                statLabel = 'Making...';
                            } else if (order.status === 'ready') {
                                statColor = 'var(--accent-green)';
                                statIcon = 'check-circle';
                                statLabel = 'Ready!';
                            } else {
                                statColor = 'var(--accent-red)';
                            }

                            const card = `
                                <div style="background: var(--bg-surface); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); position: relative; overflow: hidden;">
                                    <div style="position: absolute; top:0; left:0; width: 4px; height: 100%; background: ${statColor};"></div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; padding-left: 0.5rem;">
                                        <strong style="color: white; font-size: 1rem;">#${order.invoice}</strong>
                                        <span style="font-size: 0.75rem; color: ${statColor}; border: 1px solid ${statColor}; padding: 1px 4px; border-radius: 4px;">
                                            <i class="fas fa-${statIcon}"></i> ${statLabel}
                                        </span>
                                    </div>
                                    <div style="padding-left: 0.5rem; margin-bottom: 0.5rem;">
                                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.2rem;"><i class="fas fa-user"></i> ${order.customer}</div>
                                        <div style="font-size: 0.8rem; opacity: 0.7;">${order.time}</div>
                                    </div>
                                    <div style="padding-left: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.85rem;">
                                        ${order.details.length} Items
                                    </div>
                                </div>
                            `;
                            grid.innerHTML += card;
                        });

                        // Update Badges
                        qCount.innerText = counts.queue;
                        pCount.innerText = counts.processing;
                        rCount.innerText = counts.ready;
                    }
                </script>
                <!-- Layout Filler Right Sidebar for Admin - Only if not Owner/Manager to keep full width -->
                <?php if ($role !== 'owner' && $role !== 'manajer'): ?>
                <aside style="background: var(--bg-sidebar); border-left: 1px solid rgba(255,255,255,0.05);"></aside>
                <?php endif; ?>

                <!-- Dynamic Chart Script -->
                <script>
                    let salesChart;
                    const ctx = document.getElementById('salesChart').getContext('2d');

                    function initChart() {
                        salesChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: [],
                                datasets: [{
                                    label: 'Omset',
                                    data: [],
                                    borderColor: '#D4A373',
                                    backgroundColor: 'rgba(212, 163, 115, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    fill: true,
                                    pointBackgroundColor: '#D4A373'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { labels: { color: 'white' } },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) label += ': ';
                                                if (context.parsed.y !== null) label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                                                return label;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                        ticks: { color: '#A0A0A0' }
                                    },
                                    x: {
                                        grid: { display: false },
                                        ticks: { color: '#A0A0A0' }
                                    }
                                }
                            }
                        });
                        // Load Initial Data
                        updateChart('daily');
                    }

                    function updateChart(period) {
                        // Update Export Link
                        const exportBtn = document.querySelector('.export-btn');
                        if(exportBtn) {
                            exportBtn.href = `export_report.php?period=${period}`;
                        }

                        fetch(`get_sales_stats.php?period=${period}`)
                            .then(res => res.json())
                            .then(data => {
                                salesChart.data.labels = data.labels;
                                salesChart.data.datasets[0].data = data.data;
                                salesChart.data.datasets[0].label = data.label;
                                salesChart.update();
                            })
                            .catch(console.error);
                    }

                    // Start Chart
                    initChart();
                </script>


        
        <?php elseif ($role === 'barista'): ?>
            <!-- TAMPILAN BARISTA (KDS - Kitchen Display System) -->
            <main class="main-content" style="padding-bottom: 2rem;">
                <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1><i class="fas fa-tasks"></i> Kitchen Display System</h1>
                        <p style="color: var(--text-muted);">Pantau dan kelola pesanan masuk secara real-time.</p>
                    </div>
                    <div id="prep-summary-container" style="display: flex; gap: 0.75rem; overflow-x: auto; padding: 0.5rem; background: rgba(255,255,255,0.03); border-radius: 50px; border: 1px solid rgba(255,255,255,0.05); max-width: 60%;">
                        <!-- Prep Summary Items injected here -->
                        <div style="padding: 0.4rem 1.25rem; border-right: 1px solid rgba(255,255,255,0.1); white-space: nowrap; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.75rem; color: var(--text-muted); text-transform:uppercase; letter-spacing: 0.5px;">Antrean</span>
                            <strong id="total-prep-items" style="font-size: 1.1rem; color: var(--primary);">0</strong>
                        </div>
                        <button id="btn-activate-sound" onclick="activateKDSAudio()" class="btn btn-sm" style="background: rgba(255,255,255,0.05); color: var(--text-muted); border-radius: 50px; padding: 0.2rem 0.8rem; font-size: 0.7rem; border: none; margin-left: 5px;">
                            <i class="fas fa-volume-mute"></i> Aktifkan Suara
                        </button>
                    </div>
                </div>

                <!-- Hidden Audio for Notifications: Genuine Kitchen Bell TING -->
                <audio id="notif-sound" src="https://assets.mixkit.co/active_storage/sfx/3005/3005-preview.mp3" preload="auto"></audio>

                <!-- KDS Columns -->
                <div class="kds-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    
                    <!-- COLUMN 1: QUEUE (Pesanan Masuk) -->
                    <div class="kds-column">
                        <div class="kds-header" style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: var(--radius-md) var(--radius-md) 0 0; border-bottom: 2px solid var(--accent-red); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin: 0; color: var(--accent-red);"><i class="fas fa-clock"></i> Pesanan Masuk</h3>
                            <span class="badge badge-danger" id="count-queue">0</span>
                        </div>
                        <div class="kds-items" id="list-queue" style="background: rgba(255,255,255,0.02); min-height: 200px; padding: 1rem; border-radius: 0 0 var(--radius-md) var(--radius-md); max-height: 70vh; overflow-y: auto;">
                            <!-- Order Cards Injected via JS -->
                            <p style="text-align: center; color: var(--text-muted); opacity: 0.5;">Memuat data...</p>
                        </div>
                    </div>

                    <!-- COLUMN 2: PROCESSING (Sedang Dibuat) -->
                    <div class="kds-column">
                        <div class="kds-header" style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: var(--radius-md) var(--radius-md) 0 0; border-bottom: 2px solid var(--accent-yellow); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin: 0; color: var(--accent-yellow);"><i class="fas fa-fire"></i> Sedang Dibuat</h3>
                            <span class="badge badge-warning" id="count-processing">0</span>
                        </div>
                        <div class="kds-items" id="list-processing" style="background: rgba(255,255,255,0.02); min-height: 200px; padding: 1rem; border-radius: 0 0 var(--radius-md) var(--radius-md); max-height: 70vh; overflow-y: auto;">
                            <!-- Order Cards Injected via JS -->
                        </div>
                    </div>

                    <!-- COLUMN 3: READY (Siap Disajikan/Diantar) -->
                    <div class="kds-column">
                        <div class="kds-header" style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: var(--radius-md) var(--radius-md) 0 0; border-bottom: 2px solid var(--accent-green); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin: 0; color: var(--accent-green);"><i class="fas fa-check-circle"></i> Siap Disajikan</h3>
                            <span class="badge badge-success" id="count-ready">0</span>
                        </div>
                        <div class="kds-items" id="list-ready" style="background: rgba(255,255,255,0.02); min-height: 200px; padding: 1rem; border-radius: 0 0 var(--radius-md) var(--radius-md); max-height: 70vh; overflow-y: auto;">
                           <!-- Order Cards Injected via JS -->
                        </div>
                    </div>

                </div>
            </main>

            <!-- KDS Script -->
            <script>
                // Auto-refresh KDS setiap 5 detik
                let lastOrderCount = -1;
                
                // Pre-load voices for better reliability
                window.speechSynthesis.getVoices();
                if (speechSynthesis.onvoiceschanged !== undefined) {
                    speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
                }

                setInterval(fetchKDSData, 5000);
                fetchKDSData(); // Initial load
                fetchProductsForKDS(); // Load sold out manager

                function speakNotification(text) {
                    const msg = new SpeechSynthesisUtterance(text);
                    const voices = window.speechSynthesis.getVoices();
                    
                    // Prioritaskan "Google Bahasa Indonesia" atau suara ID apapun yang natural
                    const indoVoice = voices.find(v => v.lang.includes('id') && v.name.includes('Google')) || 
                                     voices.find(v => v.lang.includes('id')) ||
                                     voices.find(v => v.name.includes('Indonesian'));
                    
                    if (indoVoice) msg.voice = indoVoice;
                    msg.lang = 'id-ID';
                    msg.rate = 0.85; // Sedikit lebih lambat agar jelas
                    msg.pitch = 1.0;
                    window.speechSynthesis.speak(msg);
                }

                function activateKDSAudio() {
                    const sound = document.getElementById('notif-sound');
                    const btn = document.getElementById('btn-activate-sound');
                    
                    // Play a short silent/low volume sound to unlock
                    sound.play().then(() => {
                        btn.innerHTML = '<i class="fas fa-volume-up"></i> Suara Aktif';
                        btn.style.color = 'var(--primary)';
                        btn.style.background = 'rgba(212, 163, 115, 0.1)';
                    }).catch(err => {
                        console.error("Audio activation failed:", err);
                        alert("Gagal mengaktifkan suara. Pastikan browser mengizinkan audio.");
                    });
                }

                function fetchKDSData() {
                    fetch('get_kds_orders.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Sound Notif Logic: TING + Natural Voice
                                if (lastOrderCount !== -1 && data.orders.length > lastOrderCount) {
                                    const sound = document.getElementById('notif-sound');
                                    sound.play().then(() => {
                                        setTimeout(() => {
                                            speakNotification('Ada pesanan masuk');
                                        }, 500); 
                                    }).catch(e => console.log('Audio blocked, click Aktifkan Suara'));
                                }
                                lastOrderCount = data.orders.length;
                                renderKDS(data.orders);
                            }
                        })
                        .catch(err => console.error('KDS Fetch Error:', err));
                }

                function renderKDS(orders) {
                    const containers = {
                        'queue': document.getElementById('list-queue'),
                        'processing': document.getElementById('list-processing'),
                        'ready': document.getElementById('list-ready')
                    };
                    const counts = {
                        'queue': document.getElementById('count-queue'),
                        'processing': document.getElementById('count-processing'),
                        'ready': document.getElementById('count-ready')
                    };

                    // Prep Summary Calculation
                    const prepSummary = {};
                    let totalItems = 0;

                    // Clear containers
                    for (let key in containers) containers[key].innerHTML = '';
                    
                    let countData = { queue: 0, processing: 0, ready: 0 };

                    if (orders.length === 0) {
                         containers['queue'].innerHTML = '<div class="empty-state" style="padding: 2rem;"><i class="fas fa-mug-hot" style="font-size: 2rem; opacity: 0.3; margin-bottom: 0.5rem;"></i><p>Tidak ada pesanan.</p></div>';
                    }

                    orders.forEach(order => {
                        if (countData[order.status] !== undefined) countData[order.status]++;
                        
                        // Aggregate Items for Summary (only for queue and processing)
                        if (order.status === 'queue' || order.status === 'processing') {
                            order.details.forEach(item => {
                                const qty = Number(item.qty);
                                prepSummary[item.name] = (prepSummary[item.name] || 0) + qty;
                                totalItems += qty;
                            });
                        }

                        // Calculate Urgency
                        const orderTime = new Date(order.transaction_date || new Date());
                        const now = new Date();
                        const diffMinutes = Math.floor((now - orderTime) / 60000);
                        let urgencyStyle = '';
                        let urgencyLabel = `${diffMinutes}m lalu`;

                        if (diffMinutes >= 15 && order.status !== 'ready') {
                            urgencyStyle = 'border: 2px solid var(--accent-red); animation: pulseRed 2s infinite;';
                            urgencyLabel = `<i class="fas fa-exclamation-circle"></i> TERLAMBAT (${diffMinutes}m)`;
                        } else if (diffMinutes >= 8 && order.status !== 'ready') {
                            urgencyStyle = 'border: 1px solid var(--accent-yellow);';
                        }

                        const itemsHtml = order.details.map(item => `
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem; font-size: 0.95rem;">
                                <span><strong style="color: var(--primary); font-size: 1.1rem;">${item.qty}x</strong> ${item.name}</span>
                            </div>
                        `).join('');

                        let badgeColor = order.type === 'dine_in' ? 'var(--primary)' : '#3b82f6';
                        let typeIcon = order.type === 'dine_in' ? 'utensils' : 'shopping-bag';
                        let typeLabel = order.type === 'dine_in' ? 'Dine In' : 'Take Away';

                        // Action Buttons based on Status
                        let actionBtn = '';
                        if (order.status === 'queue') {
                            actionBtn = `<button onclick="updateStatus(${order.id}, 'processing')" class="btn btn-sm" style="width: 100%; background: var(--accent-yellow); color: #3E2723; margin-top: 1rem; font-weight:800;"><i class="fas fa-fire"></i> TERIMA & BUAT</button>`;
                        } else if (order.status === 'processing') {
                            actionBtn = `<button onclick="updateStatus(${order.id}, 'ready')" class="btn btn-sm" style="width: 100%; background: var(--accent-green); color: #fff; margin-top: 1rem; font-weight:bold;"><i class="fas fa-check"></i> SIAP SAJI</button>`;
                        } else if (order.status === 'ready') {
                            actionBtn = `<button onclick="updateStatus(${order.id}, 'completed')" class="btn btn-sm" style="width: 100%; background: rgba(255,255,255,0.1); color: var(--text-muted); margin-top: 1rem;"><i class="fas fa-archive"></i> SELESAI / ARSIP</button>`;
                        }

                        const card = `
                            <div class="order-card" style="background: var(--bg-surface); padding: 1.2rem; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.05); margin-bottom: 1.2rem; animation: fadeInUp 0.3s ease; ${urgencyStyle}">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.8rem;">
                                    <div>
                                        <div style="font-weight: bold; font-size: 1.2rem; color:white;">#${order.invoice.split('/').pop()}</div>
                                        <div style="font-size: 0.9rem; color: var(--text-muted); font-weight:bold;">${order.customer}</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="badge" style="background: ${badgeColor}22; color: ${badgeColor}; border: 1px solid ${badgeColor}44; margin-bottom: 0.4rem; display: inline-block; font-size:0.7rem;">
                                            <i class="fas fa-${typeIcon}"></i> ${typeLabel.toUpperCase()}
                                        </span>
                                        <div style="font-size: 0.85rem; color: ${diffMinutes >= 15 ? 'var(--accent-red)' : 'var(--text-muted)'}; font-weight:bold;">${urgencyLabel}</div>
                                    </div>
                                </div>
                                <div class="order-items" style="margin-bottom: 0.5rem;">
                                    ${itemsHtml}
                                </div>
                                ${actionBtn}
                            </div>
                        `;

                        if (containers[order.status]) {
                            containers[order.status].innerHTML += card;
                        }
                    });

                    // Update Counters
                    for (let key in counts) counts[key].innerText = countData[key];

                    // Render Prep Summary Panel
                    renderPrepSummary(prepSummary, totalItems);
                }

                function renderPrepSummary(summary, total) {
                    const container = document.getElementById('prep-summary-container');
                    let html = `
                        <div style="padding: 0.4rem 1.25rem; border-right: 1px solid rgba(255,255,255,0.1); white-space: nowrap; display:flex; align-items:center; gap:0.5rem;">
                            <span style="font-size: 0.7rem; color: var(--text-muted); text-transform:uppercase; letter-spacing:1px;">TOTAL</span>
                            <strong style="font-size: 1.2rem; color: var(--primary);">${total}</strong>
                        </div>
                    `;
                    
                    for (let item in summary) {
                        html += `
                            <div style="padding: 0.4rem 1rem; border-right: 1px solid rgba(255,255,255,0.05); white-space: nowrap; display:flex; align-items:center; gap:0.5rem;">
                                <span style="font-size: 0.75rem; color: var(--text-muted);">${item}</span>
                                <strong style="font-size: 1rem; color: white; background: rgba(212,163,115,0.15); padding: 2px 8px; border-radius: 4px;">${summary[item]}</strong>
                            </div>
                        `;
                    }
                    
                    // Add the sound button back
                    html += `
                        <button id="btn-activate-sound" onclick="activateKDSAudio()" class="btn btn-sm" style="background: rgba(255,255,255,0.05); color: var(--text-muted); border-radius: 50px; padding: 0.2rem 0.8rem; font-size: 0.7rem; border: none; margin-left: 10px; align-self: center;">
                            <i class="fas fa-volume-up"></i> Suara Aktif
                        </button>
                    `;
                    
                    container.innerHTML = html;
                }

                function fetchProductsForKDS() {
                    fetch('get_sales_stats.php?action=list_products') // Assuming we add this or similar endpoint
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const container = document.getElementById('kds-product-list');
                                container.innerHTML = data.products.map(p => `
                                    <button onclick="toggleStockKDS(${p.id}, ${p.stock > 0 ? 0 : 50})" class="btn btn-sm ${p.stock > 0 ? 'btn-outline' : 'btn-danger'}" style="font-size:0.75rem; padding: 0.3rem 0.6rem;">
                                        ${p.name} ${p.stock > 0 ? '' : '(Habis)'}
                                    </button>
                                `).join('');
                            }
                        });
                }

                function toggleStockKDS(id, newStock) {
                    const formData = new FormData();
                    formData.append('action', 'quick_stock');
                    formData.append('product_id', id);
                    formData.append('stock', newStock);
                    
                    fetch('update_products_table.php', { method: 'POST', body: formData })
                        .then(() => fetchProductsForKDS());
                }

                function updateStatus(txId, newStatus) {
                    fetch('update_order_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: txId, status: newStatus })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            fetchKDSData(); // Refresh immediately
                        } else {
                            alert('Gagal update status: ' + data.message);
                        }
                    })
                    .catch(console.error);
                }
            </script>

        <?php else: ?>
            <!-- TAMPILAN KASIR (POS) -->
            <main class="main-content">
                <!-- Header -->
                <header class="top-bar">
                    <div class="search-bar" style="background: var(--bg-surface); padding: 0.5rem 1rem; border-radius: 50px; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-search" style="color: var(--text-muted);"></i>
                        <input type="text" id="searchInput" placeholder="Cari menu..." style="background: transparent; border: none; color: white; width: 100%; outline: none;" autocomplete="off">
                    </div>
                </header>

                <!-- Categories -->
                <div class="categories">
                    <button class="filter-btn active" onclick="filterCategory('all')">Semua</button>
                    <?php while($cat = $categories->fetch_assoc()): ?>
                        <button class="filter-btn" onclick="filterCategory('<?= strtolower($cat['category_name']) ?>')"><?= $cat['category_name'] ?></button>
                    <?php endwhile; ?>
                </div>

                <div class="product-grid">
                    <?php while($prod = $products->fetch_assoc()): ?>
                    <?php 
                        $isOut = $prod['stock'] <= 0;
                        $stockClass = 'badge-stock-high';
                        $stockLabel = $prod['stock'] . ' Ready';
                        if ($prod['stock'] <= 5) {
                            $stockClass = 'badge-stock-low';
                            $stockLabel = 'Hanya ' . $prod['stock'];
                        }
                        if ($isOut) {
                            $stockClass = 'badge-stock-empty';
                            $stockLabel = 'Habis';
                        }
                    ?>
                    <div class="product-card <?= $isOut ? 'out-of-stock' : '' ?>" 
                         data-category="<?= strtolower($prod['category_name']) ?>" 
                         onclick="addToCart(<?= $prod['product_id'] ?>, '<?= addslashes($prod['product_name']) ?>', <?= $prod['price'] ?>, <?= $prod['stock'] ?>, this)">
                        
                        <div class="product-badge <?= $stockClass ?>">
                            <?= $stockLabel ?>
                        </div>

                        <?php if ($prod['image_url']): ?>
                            <div class="product-img" style="background-image: url('<?= $prod['image_url'] ?>');"></div>
                        <?php else: ?>
                            <div class="product-img">
                                <i class="fas fa-coffee fa-3x" style="opacity: 0.2"></i>
                            </div>
                        <?php endif; ?>

                        <div class="product-info">
                            <h3><?= $prod['product_name'] ?></h3>
                            <p><?= $prod['category_name'] ?></p>
                            <div class="price">Rp <?= number_format($prod['price'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </main>

            <!-- Right Sidebar (Cart) -->
            <aside class="cart-sidebar" id="cartSidebar">
                <!-- Coffee Bubbles Decoration -->
                <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 100%; pointer-events: none; overflow: hidden;">
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                    <div class="bubble"></div>
                </div>
                
                <div class="cart-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="margin:0; font-size: 1.2rem;">Detail Pesanan</h2>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <!-- Mini Order Type Toggle -->
                        <div class="header-type-toggle" style="display: flex; background: rgba(255,255,255,0.05); border-radius: 8px; padding: 2px;">
                            <button onclick="selectOrderType('dine_in')" id="btn-dine-in" class="type-option active" style="padding: 4px 8px; font-size: 0.8rem; border-radius: 6px;">
                                <i class="fas fa-utensils"></i>
                            </button>
                            <button onclick="selectOrderType('take_away')" id="btn-take-away" class="type-option" style="padding: 4px 8px; font-size: 0.8rem; border-radius: 6px;">
                                <i class="fas fa-shopping-bag"></i>
                            </button>
                        </div>
                        <button class="icon-btn mobile-only" onclick="toggleCart()"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                
                <div class="cart-scroll-area" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;">
                    <div class="cart-items" id="cartItemsContainer">
                        <!-- List view items will be injected here -->
                    </div>

                    <div style="padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05);">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <div style="position: relative; flex: 1;">
                                <i class="fas fa-user" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem;"></i>
                                <input type="text" id="customerName" class="form-input" placeholder="Nama / Meja..." style="width: 100%; padding: 0.6rem 0.6rem 0.6rem 2.2rem; font-size: 0.85rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px;">
                            </div>
                            <button onclick="openMemberModal()" class="btn btn-sm btn-outline" style="padding: 0.6rem; border-radius: 8px; border-color: var(--primary); color: var(--primary);" title="Tambah Member Baru">
                                <i class="fas fa-user-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="cart-summary" style="padding: 1.5rem; background: rgba(0,0,0,0.3); backdrop-filter: blur(10px); border-top: 1px solid rgba(212, 163, 115, 0.2);">
                    <!-- Hidden State for Logic -->
                    <input type="hidden" id="paymentMethod" value="cash">
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: var(--text-muted); font-size: 0.8rem;">Item Belanja</span>
                        <span id="cartItemCountDisplay" style="font-size: 0.85rem; font-weight: 500;">0 Item</span>
                    </div>
                    <div class="summary-row" style="margin-bottom: 0.5rem; color: #aaa;">
            <span>Subtotal</span>
            <span id="cartSubtotal" style="color: white;">Rp 0</span>
        </div>
        
        <!-- Discount Section -->
        <div class="summary-row" style="margin-bottom: 0.5rem; align-items: center;">
            <span style="font-size: 0.9rem; color: var(--accent-red);"><i class="fas fa-tag"></i> Diskon (Rp)</span>
            <input type="number" id="inputDiscount" value="0" min="0" class="form-input" style="width: 100px; padding: 0.2rem 0.5rem; font-size: 0.9rem; text-align: right;" onchange="updateCartUI()">
        </div>

        <!-- Reward Points Toggle -->
        <div class="summary-row" style="margin-bottom: 0.5rem; align-items: center; display:none;" id="rewardSection">
            <span style="font-size: 0.9rem; color: #fbbf24;"><i class="fas fa-coins"></i> Tukar Poin (<span id="availPoints">0</span>)</span>
            <label class="switch">
                <input type="checkbox" id="usePoints" onchange="updateCartUI()">
                <span class="slider round"></span>
            </label>
        </div>

        <div class="summary-row" style="margin-bottom: 1rem; color: #aaa;">
            <span>Pajak (10%)</span>
            <span id="cartTax">Rp 0</span>
        </div>
        
        <div class="summary-row" id="rowPointsDed" style="display:none; color: #fbbf24; margin-bottom: 0.5rem;">
            <span>Potongan Poin</span>
            <span id="valPointsDed">-Rp 0</span>
        </div>

        <div class="summary-row" style="font-size: 1.25rem; font-weight: 700; color: var(--primary); padding-top: 1rem; border-top: 1px dashed rgba(255,255,255,0.2);">
            <span>TOTAL</span>
            <span id="cartTotal">Rp 0</span>
        </div>
                    <button class="checkout-btn" style="width: 100%; background: var(--primary); color: #000; font-weight: 800; padding: 1rem; border-radius: 12px; border: none; font-size: 1.1rem; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(212,163,115,0.4);">
                        PROSES <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
                    </button>
                </div>
            </aside>
            <!-- Only Load POS Script for Kasir -->
            <!-- Pass Settings to JS -->
            <script>
                const TAX_RATE = <?= getSetting('tax_rate') / 100 ?>;
            </script>
            <script src="assets/js/script.js"></script>
        <?php endif; ?>
    </div>
    <!-- Success Modal -->
    <div class="modal" id="successModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:2000; align-items:center; justify-content:center;">
        <div class="modal-content" style="background:var(--bg-surface); padding:2rem; border-radius:var(--radius-md); text-align:center; max-width:400px; width:90%;">
            <div style="font-size:4rem; color:var(--accent-green); margin-bottom:1rem;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 style="margin-bottom:0.5rem;">Transaksi Berhasil!</h2>
            <p style="color:var(--text-muted); margin-bottom:1.5rem;" id="successMessage">Detail transaksi telah disimpan.</p>
            
            <div style="display:flex; gap:1rem; justify-content:center;">
                <button class="btn btn-outline" onclick="location.reload()">Transaksi Baru</button>
                <button class="btn btn-primary" id="btnPrintStruk" onclick="">
                    <i class="fas fa-print"></i> Cetak Struk
                </button>
            </div>
        </div>
    </div>

    <!-- Payment Modal (Input Uang) -->
    <div class="modal" id="paymentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:2000; align-items:center; justify-content:center;">
        <div class="modal-content" style="background:var(--bg-surface); padding:2rem; border-radius:var(--radius-md); max-width:400px; width:90%; position:relative;">
            <button onclick="closePaymentModal()" style="position:absolute; top:1rem; right:1rem; background:none; border:none; color:var(--text-muted); font-size:1.2rem; cursor:pointer;"><i class="fas fa-times"></i></button>
            <h2 style="margin-bottom:1.5rem; text-align:center;">Konfirmasi Pembayaran</h2>

            <div class="method-selector" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
                <button type="button" onclick="changePaymentMethod('cash')" id="btn-meth-cash" class="method-btn active" style="flex:1; padding:0.75rem; border-radius:8px; border:1px solid var(--primary); background:rgba(212,163,115,0.1); color:var(--primary); cursor:pointer;">💵 Tunai</button>
                <button type="button" onclick="changePaymentMethod('qris')" id="btn-meth-qris" class="method-btn" style="flex:1; padding:0.75rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:white; cursor:pointer;">📱 QRIS</button>
                <button type="button" onclick="changePaymentMethod('debit')" id="btn-meth-debit" class="method-btn" style="flex:1; padding:0.75rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:white; cursor:pointer;">💳 Debit</button>
            </div>
            
            <div style="background:rgba(255,255,255,0.05); padding:1rem; border-radius:var(--radius-sm); margin-bottom:1.5rem; text-align:center;">
                <p style="margin:0; color:var(--text-muted); font-size:0.9rem;">Total Tagihan</p>
                <h1 style="margin:0.25rem 0 0; color:var(--primary); font-size:2rem;" id="modalGrandTotal">Rp 0</h1>
            </div>

            <div id="cash-input-section">
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Uang Diterima</label>
                    <div style="position:relative;">
                        <span style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted);">Rp</span>
                        <input type="number" id="inputCashReceived" class="form-input" placeholder="0" style="width:100%; padding:0.8rem 1rem 0.8rem 3rem; font-size:1.2rem; font-weight:bold;">
                    </div>
                </div>

                <div id="quick-cash-section" style="display:flex; gap:0.5rem; margin-bottom:1.5rem; flex-wrap:wrap;">
                    <button type="button" class="quick-cash btn btn-sm btn-outline" onclick="setCash(50000)">50.000</button>
                    <button type="button" class="quick-cash btn btn-sm btn-outline" onclick="setCash(100000)">100.000</button>
                    <button type="button" class="quick-cash btn btn-sm btn-outline" onclick="setCash('exact')">Uang Pas</button>
                </div>
            </div>

            <button class="btn btn-primary" id="btnConfirmPayment" style="width:100%; padding:1rem; font-size:1.1rem;">
                BAYAR SEKARANG <i class="fas fa-check-circle" style="margin-left:0.5rem;"></i>
            </button>
        </div>
    </div>

    <!-- Enhanced Cart CSS -->
    <style>
        /* FIX: Robust Cart Sidebar Layout */
        .cart-sidebar {
            position: sticky; /* Sticky is better than relative/fixed for grid layouts */
            top: 0;
            height: 100vh;
            background: var(--bg-sidebar);
            border-left: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Keep hidden to contain scrolls */
            z-index: 90;
        }

        /* Order Type Buttons High Contrast */
        .type-option {
            flex: 1;
            border: none;
            background: transparent;
            cursor: pointer;
            text-align: center;
            padding: 0.6rem;
            border-radius: 50px;
            font-size: 0.9rem;
            transition: all 0.3s;
            color: var(--text-muted);
            font-weight: 500;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .type-option:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }
        .type-option.active {
            background: var(--primary);
            color: #1a1a1a;
            font-weight: 700;
            box-shadow: 0 0 15px rgba(212, 163, 115, 0.4);
        }

        /* FIX CATEGORY BLUR */
        .filter-btn {
            opacity: 1 !important;
            filter: none !important;
            backdrop-filter: none !important;
            text-shadow: none !important;
            background: rgba(255, 255, 255, 0.08) !important; /* Sedikit lebih terang dari bg */
            color: #e0e0e0 !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
        }
        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.15) !important;
            color: white !important;
        }
        .filter-btn.active {
            background: var(--primary) !important;
            color: #1a1a1a !important;
            border-color: var(--primary) !important;
            font-weight: 700;
            box-shadow: 0 0 15px rgba(212, 163, 115, 0.3);
        }

        /* Cart Item Styling */
        .cart-item {
            background: rgba(255,255,255,0.03); 
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem; /* Slightly compact */
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }
        .cart-item:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(212, 163, 115, 0.2);
            transform: translateX(-2px);
        }
        .cart-item h4 {
            font-size: 0.95rem;
            margin: 0 0 0.25rem 0;
            color: var(--text-main);
        }
        .cart-item .price {
            font-size: 0.85rem;
            color: var(--primary);
            font-weight: 500;
        }
        .qty-controls {
            display: flex;
            align-items: center;
            background: rgba(0,0,0,0.2);
            border-radius: 50px;
            padding: 2px;
        }
        .qty-btn {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: none;
            background: var(--bg-surface);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: background 0.2s;
        }
        .qty-btn:hover { background: var(--primary); color: black; }
        .qty-val {
            min-width: 24px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0.25rem;
        }
        
        /* Summary Section Styling */
        /* Summary Section Styling - FIX: Use Flex flow instead of absolute */
        /* Summary Section Styling */
        .cart-summary {
            background: rgba(0,0,0,0.3);
            padding: 1.5rem;
            border-top: 1px solid rgba(212, 163, 115, 0.2);
            z-index: 10;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }

        /* Toggle Switch Styling */
        .switch {
            position: relative;
            display: inline-block;
            width: 46px; /* Slightly wider */
            height: 24px;
        }
        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(255,255,255,0.1);
            transition: .4s;
            border-radius: 34px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        input:checked + .slider:before {
            transform: translateX(22px);
            background-color: black; /* Contrast for active state */
        }
        
        /* Sidebar container adjustment */
        .cart-items {
            flex-grow: 1;
            overflow-y: auto;
            margin-bottom: 0;
            padding-bottom: 1rem;
            min-height: 0; 
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed rgba(255,255,255,0.1);
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 1.25rem;
        }

        /* Input Styling Fix */
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(212, 163, 115, 0.2);
            outline: none;
        }

        /* --- MOBILE RESPONSIVE FIX --- */
        @media (max-width: 1024px) {
            .dashboard-layout {
                display: block !important;
                height: 100vh;
                overflow-y: auto;
            }
            .main-content {
                padding: 1rem;
                padding-bottom: 80px; /* Space for accidental touches */
                height: auto;
                min-height: 100vh;
                overflow: visible;
            }
            .mobile-header {
                display: flex !important;
            }
            
            /* Off-Canvas Navigation Sidebar */
            .sidebar {
                position: fixed;
                left: -300px;
                top: 0;
                bottom: 0;
                z-index: 1500;
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                width: 260px;
                box-shadow: 5px 0 25px rgba(0,0,0,0.5);
            }
            .sidebar.active {
                left: 0;
            }
            /* Close button in sidebar mobile */
            .sidebar-header-mobile {
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                padding-bottom: 1.5rem;
                margin-bottom: 1rem;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }

            /* Off-Canvas Cart Sidebar */
            .cart-sidebar {
                position: fixed;
                right: -100%; /* Full hide */
                top: 0;
                bottom: 0;
                z-index: 1600;
                transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                width: 100%; /* Full screen on tiny phones */
                max-width: 380px;
                box-shadow: -5px 0 25px rgba(0,0,0,0.5);
                height: 100vh !important;
            }
            .cart-sidebar.active {
                right: 0;
            }
            /* Show toggle button inside cart */
            .mobile-only {
                display: inline-flex !important;
            }
            .desktop-only {
                display: none !important;
            }

            /* KDS Mobile Stack */
            .kds-container {
                grid-template-columns: 1fr !important;
            }
            .kds-column {
                min-height: auto;
                margin-bottom: 1.5rem;
            }
            
            /* Product Grid Mobile */
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 0.75rem;
            }
        }
    </style>

    <script>
        function toggleSidebar() {
            document.getElementById('mainSidebar').classList.toggle('active');
        }
        function toggleCart() {
            const cart = document.getElementById('cartSidebar');
            if (cart) cart.classList.toggle('active');
        }
        
        // Modal & Input Helper
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        function setCash(amount) {
            const input = document.getElementById('inputCashReceived');
            if (amount === 'exact') {
                // Get raw numeric value from H1 text
                const totalText = document.getElementById('modalGrandTotal').innerText;
                const totalVal = parseInt(totalText.replace(/[^0-9]/g, ''));
                input.value = totalVal;
            } else {
                input.value = amount;
            }
        }
    </script>
    <!-- Quick Member Modal -->
    <div class="modal" id="quickMemberModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:2000; align-items:center; justify-content:center;">
        <div class="modal-content" style="background:var(--bg-surface); padding:2rem; border-radius:var(--radius-md); max-width:400px; width:90%; position:relative; border: 1px solid rgba(255,255,255,0.1);">
            <button onclick="closeMemberModal()" style="position:absolute; top:1rem; right:1rem; background:none; border:none; color:var(--text-muted); font-size:1.2rem; cursor:pointer;"><i class="fas fa-times"></i></button>
            <h2 style="margin-bottom:1.5rem; text-align:center;"><i class="fas fa-user-plus"></i> Member Baru</h2>
            
            <form id="formQuickMember">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Nama Lengkap</label>
                    <input type="text" id="regName" class="form-input" required placeholder="Contoh: Budi Santoso">
                </div>
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label>No. WhatsApp</label>
                    <input type="text" id="regPhone" class="form-input" required placeholder="08xxxxxxxxxx">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:1rem;">
                    DAFTARKAN MEMBER
                </button>
            </form>
        </div>
    </div>

    <script>
        function openMemberModal() {
            document.getElementById('quickMemberModal').style.display = 'flex';
            document.getElementById('regName').focus();
        }
        function closeMemberModal() {
            document.getElementById('quickMemberModal').style.display = 'none';
        }

        document.getElementById('formQuickMember').onsubmit = async function(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerText;
            btn.innerText = 'Memproses...';
            btn.disabled = true;

            const name = document.getElementById('regName').value;
            const phone = document.getElementById('regPhone').value;

            try {
                const res = await fetch('quick_add_member.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ name, phone })
                });
                const data = await res.json();

                if (data.success) {
                    alert('Member Berhasil Didaftarkan!');
                    document.getElementById('customerName').value = name;
                    
                    // Enable Rewards UI
                    customerPointsGlobal = 0; // New member has 0
                    toggleRewardUI(name, 0);

                    closeMemberModal();
                    e.target.reset();
                } else {
                    alert('Gagal: ' + data.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        };
    </script>
</body>
</html>

