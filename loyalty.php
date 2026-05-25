<?php
require_once 'config/database.php';

$search = $_GET['phone'] ?? '';
$customer = null;
$error = '';

if ($search) {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE phone_number = ? OR customer_name LIKE ? LIMIT 1");
    $param_search = "%$search%";
    $stmt->bind_param("ss", $search, $param_search);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
    } else {
        $error = "Data member tidak ditemukan. Pastikan nomor HP benar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Portal - Bellen Beans Coffee</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #D4A373;
            --primary-dark: #A67C52;
            --bg-dark: #0f0f0f;
            --bg-surface: #1a1a1a;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --gold: #ffd700;
            --silver: #c0c0c0;
            --bronze: #cd7f32;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .loyalty-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 2rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
        }

        .brand-logo {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 700; letter-spacing: -0.5px; }
        p.subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem; }

        .search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-input {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 1rem 1rem 1rem 3rem;
            border-radius: 1rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 0 4px rgba(212, 163, 115, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .btn-check {
            width: 100%;
            background: var(--primary);
            color: #000;
            border: none;
            padding: 1rem;
            border-radius: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-check:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Profile Section */
        .profile-result {
            margin-top: 2rem;
            animation: fadeInUp 0.5s ease-out;
        }

        .rank-badge {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
        }

        .points-display {
            font-size: 3rem;
            font-weight: 900;
            color: var(--primary);
            margin: 1rem 0;
            display: block;
        }

        .progress-container {
            background: rgba(255,255,255,0.05);
            height: 8px;
            border-radius: 10px;
            margin: 1.5rem 0 0.5rem;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), #fff);
            border-radius: 10px;
            transition: width 1s ease-in-out;
        }

        .next-rank-info {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 1rem;
            border-radius: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        /* Decorations */
        .bg-glow {
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(212, 163, 115, 0.15) 0%, transparent 70%);
            z-index: 1;
            top: -100px;
            right: -100px;
            pointer-events: none;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reward-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 1rem;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: left;
        }

        .reward-card i {
            font-size: 1.5rem;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="bg-glow" style="bottom: -100px; left: -100px; top: auto; right: auto;"></div>

    <div class="loyalty-container">
        <!-- Brand -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-mug-hot brand-logo"></i>
            <div style="font-weight: 800; letter-spacing: 2px; color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase;">Bellen Beans Coffee</div>
        </div>

        <div class="glass-card">
            <?php if (!$customer): ?>
                <h1>Cek Poin & Rank</h1>
                <p class="subtitle">Masukkan nomor HP atau nama kamu untuk melihat keuntungan member.</p>

                <form action="" method="GET">
                    <div class="search-box">
                        <i class="fas fa-phone"></i>
                        <input type="text" name="phone" class="search-input" placeholder="Contoh: 08123xxx" required value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="btn-check">LIHAT POIN SAYA</button>
                </form>

                <?php if ($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
                <?php endif; ?>

            <?php else: 
                $pts = $customer['loyalty_points'] ?? 0;
                $rank = 'Bronze';
                $rankColor = '#cd7f32';
                $nextRank = 'Silver';
                $target = 200;
                
                if ($pts > 500) { 
                    $rank = 'Gold'; 
                    $rankColor = '#ffd700'; 
                    $nextRank = 'MAX';
                    $target = $pts;
                } elseif ($pts > 200) { 
                    $rank = 'Silver'; 
                    $rankColor = '#c0c0c0'; 
                    $nextRank = 'Gold';
                    $target = 500;
                }

                $progress = ($pts / $target) * 100;
                if ($progress > 100) $progress = 100;
            ?>
                <!-- Result View -->
                <div class="profile-result">
                    <span class="rank-badge" style="background: <?= $rankColor ?>15; color: <?= $rankColor ?>; border: 1px solid <?= $rankColor ?>33;">
                        <i class="fas fa-crown"></i> Member <?= $rank ?>
                    </span>
                    
                    <h2 style="margin: 0;"><?= htmlspecialchars($customer['customer_name']) ?></h2>
                    <span class="points-display"><?= number_format($pts, 0, ',', '.') ?> <small style="font-size: 1rem; color: var(--text-muted); font-weight: 400;">Poin</small></span>
                    
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= $progress ?>%; background: <?= $rankColor ?>;"></div>
                    </div>
                    
                    <div class="next-rank-info flex justify-between">
                        <?php if ($nextRank !== 'MAX'): ?>
                            <span>Butuh <?= number_format($target - $pts, 0, ',', '.') ?> poin lagi untuk <b><?= $nextRank ?></b></span>
                        <?php else: ?>
                            <span>Selamat! Anda sudah mencapai Rank Tertinggi.</span>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1.5rem;">
                        <div class="reward-card">
                            <i class="fas fa-gift"></i>
                            <div>
                                <div style="font-weight: bold; font-size: 0.9rem;">Promo Spesial <?= $rank ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Gunakan poinmu di kasir hari ini!</div>
                            </div>
                        </div>
                        
                        <a href="loyalty.php" style="display: block; margin-top: 1.5rem; color: var(--primary); text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                            <i class="fas fa-arrow-left"></i> Kembali Cari Nomor
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Footer -->
        <div style="text-align: center; margin-top: 2rem; font-size: 0.75rem; color: var(--text-muted); opacity: 0.5;">
            &copy; 2025 Bellen Beans Coffee. Selalu Ada Rasa di Setiap Seduhan.
        </div>
    </div>
</body>
</html>

