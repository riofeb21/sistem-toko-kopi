<?php
// backup_database.php - OTOMATIS LOWERCASE UNTUK HOSTING / LINUX
require_once 'config/database.php';

$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$sqlScript = "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $originalTable) {
    // FIX: Paksa nama tabel jadi HURUF KECIL (Lowercase)
    // Ini agar kompatibel dengan Hosting (Linux) yang Case Sensitive
    $table = strtolower($originalTable); 
    
    // 1. Struktur Tabel
    $sqlScript .= "-- Struktur untuk tabel `$table` (Asli: $originalTable) --\n";
    $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
    
    // Ambil Create Table asli dari DB
    $row2 = $conn->query("SHOW CREATE TABLE `$originalTable`")->fetch_row();
    $createStmt = $row2[1];
    
    // MAGIC: Ubah nama tabel di dalam query CREATE TABLE menjadi lowercase
    // Regex mencari: CREATE TABLE `NamaTabel` -> diganti CREATE TABLE `namatabel`
    $createStmt = preg_replace('/CREATE TABLE\s+`?'.$originalTable.'`?/i', "CREATE TABLE `$table`", $createStmt);
    
    $sqlScript .= $createStmt . ";\n\n";

    // 2. Isi Data (Insert)
    $sqlScript .= "-- Dumping data untuk tabel `$table` --\n";
    $result3 = $conn->query("SELECT * FROM `$originalTable`");
    
    if ($result3->num_rows > 0) {
        $sqlScript .= "INSERT INTO `$table` VALUES ";
        $rows = [];
        while ($row = $result3->fetch_row()) {
            $values = [];
            foreach ($row as $val) {
                if (is_null($val)) {
                    $values[] = "NULL";
                } else {
                    $val = $conn->real_escape_string($val);
                    $values[] = "'$val'";
                }
            }
            $rows[] = "(" . implode(", ", $values) . ")";
        }
        $sqlScript .= implode(",\n", $rows) . ";\n";
    }
    $sqlScript .= "\n";
}

$sqlScript .= "SET FOREIGN_KEY_CHECKS = 1;\n";

// Nama file output
$backupFile = 'DATABASE_SIAP_HOSTING.sql';
file_put_contents($backupFile, $sqlScript);

echo "<h1>BACKUP BERHASIL (LOWERCASE FIXED)! ✅</h1>";
echo "<p>File siap upload: <b>$backupFile</b> ({$backupFile})</p>";
echo "<p>Script ini otomatis mengubah semua nama tabel (Customers, Products, dll) menjadi <b>huruf kecil</b> (customers, products).</p>";
echo "<p><b>Silakan IMPORT file ini ke Database Hosting Anda sekarang!</b></p>";
echo "<h3><a href='$backupFile' download>Klik Disini Untuk Download SQL</a></h3>";
?>
