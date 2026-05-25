<?php
// AUTO-LOGOUT SYSTEM
// Durasi Timeout: 3600 detik = 1 Jam
$timeout_duration = 3600; 

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    // Kalau terakhir aktivitas sudah lebih lama dari durasi yg ditentukan
    session_unset();     // Hapus variabel session
    session_destroy();   // Hancurkan session
    
    // Redirect ke Login dengan pesan
    header("Location: index.php?timeout=1");
    exit;
}

// Update waktu aktivitas terakhir setiap kali user refresh/buka halaman
$_SESSION['LAST_ACTIVITY'] = time();
?>

