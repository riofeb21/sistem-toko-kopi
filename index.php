<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // WARNING: In production, use password_verify with hashed passwords. 
    // For this simple prototype with dummy data, we check direct strings or md5/hash if stored.
    // The dummy data 'admin123' is stored as plain text in the SQL provided previously.
    
    $stmt = $conn->prepare("SELECT user_id, full_name, role, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Check if password matches (works with hashed passwords)
        // Transitional check: supports both hashed and plain-text (update all users to hash ASAP)
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } else {
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bellen Beans Coffee</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <?php @include 'includes/analytics.php'; ?>
</head>
<body>
    <div class="login-container">
        <!-- ========== COFFEE DECORATIVE ELEMENTS - MERIAH! ========== -->
        
        <!-- Multiple Steam Effects - Lebih Banyak! -->
        <div style="position: absolute; bottom: 20%; left: 10%; width: 150px; height: 200px; pointer-events: none;">
            <div class="steam"></div>
            <div class="steam" style="animation-delay: 1s;"></div>
        </div>
        <div style="position: absolute; bottom: 25%; right: 15%; width: 120px; height: 180px; pointer-events: none;">
            <div class="steam"></div>
            <div class="steam" style="animation-delay: 2s;"></div>
        </div>
        <div style="position: absolute; top: 15%; left: 30%; width: 100px; height: 150px; pointer-events: none;">
            <div class="steam"></div>
        </div>
        <div style="position: absolute; top: 20%; right: 25%; width: 110px; height: 160px; pointer-events: none;">
            <div class="steam"></div>
            <div class="steam" style="animation-delay: 1.5s;"></div>
        </div>
        
        <!-- Floating Coffee Beans - Banyak! -->
        <div class="coffee-bean" style="top: 5%; left: 8%;"></div>
        <div class="coffee-bean" style="top: 15%; right: 12%;"></div>
        <div class="coffee-bean" style="top: 35%; left: 5%;"></div>
        <div class="coffee-bean" style="top: 45%; right: 8%;"></div>
        <div class="coffee-bean" style="bottom: 25%; left: 15%;"></div>
        <div class="coffee-bean" style="bottom: 15%; right: 10%;"></div>
        <div class="coffee-bean" style="top: 60%; left: 20%;"></div>
        <div class="coffee-bean" style="top: 70%; right: 18%;"></div>
        
        <!-- Coffee Cups Animation -->
        <div style="position: absolute; top: 10%; left: 5%; width: 60px; height: 60px; opacity: 0.15; pointer-events: none; animation: floatBean 8s ease-in-out infinite;">
            <i class="fas fa-coffee" style="font-size: 60px; color: var(--primary);"></i>
        </div>
        <div style="position: absolute; bottom: 10%; right: 5%; width: 70px; height: 70px; opacity: 0.12; pointer-events: none; animation: floatBean2 9s ease-in-out infinite;">
            <i class="fas fa-mug-hot" style="font-size: 70px; color: var(--primary);"></i>
        </div>
        <div style="position: absolute; top: 50%; left: 3%; width: 50px; height: 50px; opacity: 0.1; pointer-events: none; animation: floatBean 7s ease-in-out infinite; animation-delay: 2s;">
            <i class="fas fa-coffee" style="font-size: 50px; color: var(--primary);"></i>
        </div>
        
        <!-- Bubbles Rising -->
        <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 100%; pointer-events: none; overflow: hidden;">
            <div class="bubble" style="left: 10%;"></div>
            <div class="bubble" style="left: 25%; animation-delay: 1s;"></div>
            <div class="bubble" style="left: 40%; animation-delay: 2.5s;"></div>
            <div class="bubble" style="left: 55%; animation-delay: 1.5s;"></div>
            <div class="bubble" style="left: 70%; animation-delay: 3s;"></div>
            <div class="bubble" style="left: 85%; animation-delay: 0.5s;"></div>
        </div>
        
        <!-- Coffee Particles/Sparkles -->
        <div style="position: absolute; top: 20%; left: 15%; width: 5px; height: 5px; background: var(--primary); border-radius: 50%; opacity: 0.6; animation: twinkle 2s ease-in-out infinite; pointer-events: none;"></div>
        <div style="position: absolute; top: 30%; right: 20%; width: 4px; height: 4px; background: var(--primary); border-radius: 50%; opacity: 0.5; animation: twinkle 2.5s ease-in-out infinite; animation-delay: 0.5s; pointer-events: none;"></div>
        <div style="position: absolute; bottom: 35%; left: 25%; width: 6px; height: 6px; background: var(--primary); border-radius: 50%; opacity: 0.7; animation: twinkle 3s ease-in-out infinite; animation-delay: 1s; pointer-events: none;"></div>
        <div style="position: absolute; bottom: 25%; right: 15%; width: 5px; height: 5px; background: var(--primary); border-radius: 50%; opacity: 0.6; animation: twinkle 2.2s ease-in-out infinite; animation-delay: 1.5s; pointer-events: none;"></div>
        <div style="position: absolute; top: 40%; left: 10%; width: 4px; height: 4px; background: var(--primary); border-radius: 50%; opacity: 0.5; animation: twinkle 2.8s ease-in-out infinite; animation-delay: 0.8s; pointer-events: none;"></div>
        <div style="position: absolute; top: 55%; right: 12%; width: 5px; height: 5px; background: var(--primary); border-radius: 50%; opacity: 0.6; animation: twinkle 3.2s ease-in-out infinite; animation-delay: 2s; pointer-events: none;"></div>
        
        <!-- Animated Background Circles -->
        <div style="position: absolute; top: -50px; left: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(212, 163, 115, 0.1) 0%, transparent 70%); border-radius: 50%; animation: pulse 4s ease-in-out infinite; pointer-events: none;"></div>
        <div style="position: absolute; bottom: -80px; right: -80px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(212, 163, 115, 0.08) 0%, transparent 70%); border-radius: 50%; animation: pulse 5s ease-in-out infinite; animation-delay: 1s; pointer-events: none;"></div>
        <div style="position: absolute; top: 30%; right: -60px; width: 180px; height: 180px; background: radial-gradient(circle, rgba(212, 163, 115, 0.06) 0%, transparent 70%); border-radius: 50%; animation: pulse 6s ease-in-out infinite; animation-delay: 2s; pointer-events: none;"></div>
        
        <div class="login-card">
            <!-- Mini Steam Effect on Coffee Icon -->
            <div style="position: absolute; top: 30px; left: 50%; transform: translateX(-50%); width: 80px; height: 100px; pointer-events: none; z-index: 1;">
                <div class="steam" style="width: 30px; height: 50px; left: 50%; transform: translateX(-50%);"></div>
            </div>
            
            <div class="login-header">
                <div style="position: relative; display: inline-block;">
                    <i class="fas fa-coffee fa-3x" style="color: var(--primary); margin-bottom: 1rem; filter: drop-shadow(0 0 20px rgba(212, 163, 115, 0.5)); animation: swirl 3s ease-in-out infinite;"></i>
                    <!-- Small particles around icon -->
                    <div style="position: absolute; top: -5px; right: -5px; width: 8px; height: 8px; background: var(--primary); border-radius: 50%; animation: twinkle 1.5s ease-in-out infinite;"></div>
                    <div style="position: absolute; bottom: 10px; left: -8px; width: 6px; height: 6px; background: var(--primary); border-radius: 50%; animation: twinkle 2s ease-in-out infinite; animation-delay: 0.5s;"></div>
                </div>
                <h1 style="text-shadow: 0 0 30px rgba(212, 163, 115, 0.3);">Bellen Beans Coffee</h1>
                <p style="color: var(--text-muted)">Silakan masuk untuk melanjutkan</p>
            </div>
            
            <?php if ($error): ?>
                <div style="background: rgba(239, 68, 68, 0.2); color: #faa; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                    <?= $error ?>
                </div>
            <?php elseif (isset($_GET['timeout'])): ?>
                <div style="background: rgba(212, 163, 115, 0.2); color: var(--primary); padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                    <i class="fas fa-clock"></i> Sesi habis (1 Jam). Silakan login lagi.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-input with-icon" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-input with-icon" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-full pulse-effect">
                    Masuk <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
                </button>
            </form>
            <div class="login-footer">
                <a href="loyalty.php" style="display: block; margin-bottom: 1rem; color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem;">
                    <i class="fas fa-gift"></i> Cek Poin Member di Sini
                </a>
                <div style="opacity: 0.5;">&copy; 2025 Bellen Beans Coffee</div>
            </div>
        </div>
    </div>
    
    <script>
        // Array of friendly error messages in English - Coffee themed & Clear TTS!
        const roastMessages = [
            "Oops! Wrong password!",
            "Password incorrect, try again!",
            "Wrong password, please retry!",
            "Invalid password!",
            "Password doesn't match!",
            "Access denied, wrong password!",
            "Your password is not brewing right!",
            "This password tastes wrong!",
            "Password too bitter, incorrect!",
            "Your coffee and password don't match!",
            "Password not brewed correctly!",
            "This password needs more sugar!",
            "Password is under-extracted, wrong!",
            "Wrong blend of password!",
            "Password has too much foam!"
        ];
        
        // Function untuk TTS dengan random message - NOW IN CLEAR ENGLISH!
        function speakRoast() {
            // Check if browser supports speech synthesis
            if ('speechSynthesis' in window) {
                // Random pick message
                const randomMessage = roastMessages[Math.floor(Math.random() * roastMessages.length)];
                
                // Create speech synthesis utterance
                const utterance = new SpeechSynthesisUtterance(randomMessage);
                
                // Set properties for CLEAR English voice
                utterance.lang = 'en-US'; // English US - Much clearer!
                utterance.rate = 0.9; // Slower for clarity
                utterance.pitch = 1.0; // Normal pitch
                utterance.volume = 1.0; // Max volume
                
                // Speak it!
                window.speechSynthesis.speak(utterance);
                
                // Console log untuk debug
                console.log('🔊 Speaking:', randomMessage);
            } else {
                console.log('⚠️ Speech synthesis not supported');
            }
        }
        
        // Function untuk shake animation
        function shakeLoginCard() {
            const loginCard = document.querySelector('.login-card');
            loginCard.style.animation = 'shake 0.5s ease-in-out';
            
            // Reset animation after it's done
            setTimeout(() => {
                loginCard.style.animation = 'fadeInUp 0.8s ease-out';
            }, 500);
        }
        
        // Check if there's an error and trigger TTS + shake
        <?php if ($error): ?>
            // Wait a bit for page to fully load
            window.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    speakRoast();
                    shakeLoginCard();
                }, 300);
            });
        <?php endif; ?>
    </script>
</body>
</html>

