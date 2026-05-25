<?php
date_default_timezone_set('Asia/Jakarta');
// Konfigurasi Database - Otomatis mendeteksi Localhost vs Hosting
$whitelistLocal = array(
    '127.0.0.1',
    '::1',
    'localhost'
);

if (php_sapi_name() === 'cli' || in_array($_SERVER['REMOTE_ADDR'], $whitelistLocal) || in_array($_SERVER['HTTP_HOST'], $whitelistLocal)) {
    // KONEKSI LOKAL (XAMPP)
    $host = 'localhost';
    $username = 'root';
    $password = ''; 
    $database = 'toko_kopi_db';
} else {
    // KONEKSI HOSTING (INFINITEFREE)
    // Sesuai screenshot user
    $host = 'sql310.infinityfree.com';
    $username = 'if0_40780482';     
    $password = 'Riofeb211'; 
    $database = 'if0_40780482_toko_kopi_db';   
}

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    // Tampilkan pesan error yang lebih rapi jika gagal connect
    die("
    <div style='font-family: sans-serif; padding: 20px; text-align: center; border: 1px solid #f87171; background: #fee2e2; color: #b91c1c; border-radius: 8px; margin: 20px;'>
        <h3>Gagal Terhubung ke Database</h3>
        <p>JIKA DI HOSTING: Pastikan Anda sudah mengubah detail di file <b>config/database.php</b> bagian 'KONEKSI HOSTING'.</p>
        <p>Detail Error: " . $conn->connect_error . "</p>
    </div>
    ");
}

// FIX TIMEZONE: Force MySQL to use +07:00 (WIB) regardless of server time
$conn->query("SET time_zone = '+07:00'");

