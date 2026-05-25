<?php
date_default_timezone_set('Asia/Jakarta');
// Konfigurasi Database - Otomatis mendeteksi Localhost vs Hosting
$whitelistLocal = array(
    '127.0.0.1',
    '::1',
    'localhost'
);

$isLocal = false;
if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $whitelistLocal)) {
    $isLocal = true;
} elseif (php_sapi_name() === 'cli' && !getenv('VERCEL')) {
    $isLocal = true;
}

if ($isLocal) {
    // KONEKSI LOKAL (XAMPP)
    $host = 'localhost';
    $username = 'root';
    $password = ''; 
    $database = 'toko_kopi_db';
} else {
    // KONEKSI HOSTING (CLEVER CLOUD)
    $host = 'bfcg8egvywyx2pzwnd4n-mysql.services.clever-cloud.com';
    $username = 'uric2rgxkblbu15t';     
    $password = 'g3O0aa1N6LNbao4ZFHKB'; 
    $database = 'bfcg8egvywyx2pzwnd4n';   
}

$conn = @new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    // Fallback ke Mock Data jika koneksi database gagal
    require_once __DIR__ . '/mock_database.php';
    $conn = new MockMySQLi();
}

// FIX TIMEZONE: Force MySQL to use +07:00 (WIB) regardless of server time
$conn->query("SET time_zone = '+07:00'");

