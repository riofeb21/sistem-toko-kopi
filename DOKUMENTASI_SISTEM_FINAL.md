# DOKUMENTASI TEKNIS & OPERASIONAL SISTEM
## SISTEM INFORMASI POINT OF SALES (POS) TERINTEGRASI
**Bellen Beans Coffee**

---

**Tanggal Penyusunan**: 28 Desember 2025
**Versi Dokumen**: 1.0 (Final Release)
**Pengembang**: Tim IT 

---

## DAFTAR ISI
1.  [BAB I: PENDAHULUAN](#bab-i-pendahuluan)
2.  [BAB II: ARSITEKTUR & DESAIN DATABASE](#bab-ii-arsitektur--desain-database)
3.  [BAB III: SPESIFIKASI FITUR & LOGIKA BISNIS](#bab-iii-spesifikasi-fitur--logika-bisnis)
4.  [BAB IV: KEAMANAN SISTEM](#bab-iv-keamanan-sistem)
5.  [BAB V: PANDUAN PENGGUNA (RINGKASAN)](#bab-v-panduan-pengguna-ringkasan)
6.  [LAMPIRAN: DATA & FOTO](#lampiran)

---

## BAB I: PENDAHULUAN

### 1.1 Latar Belakang
Operasional Bellen Beans Coffee membutuhkan sistem yang mampu menangani transaksi *high-speed* sekaligus pencatatan keuangan yang presisi. Sistem manual memiliki risiko kesalahan hitung (human error), selisih stok, dan kesulitan dalam pelacakan loyalitas pelanggan. Oleh karena itu, dibangunlah sistem POS Web-based yang terintegrasi dari Kasir hingga Dapur.

### 1.2 Tujuan Sistem
1.  **Kecepatan Transaksi**: Memangkas waktu order hingga < 30 detik per pelanggan.
2.  **Akurasi Keuangan**: Menghilangkan selisih perhitungan diskon, pajak, dan kembalian.
3.  **Loyalitas Pelanggan**: Mengelola database member dan sistem poin secara otomatis.
4.  **Efisiensi Dapur**: Menggantikan komunikasi teriakan/kertas dengan layar KDS (Kitchen Display System).

### 1.3 Ruang Lingkup
Sistem ini mencakup modul:
*   **Front Office**: Point of Sales (Kasir) & Member Management.
*   **Back Office**: Manajemen Stok, Laporan Keuangan, Pengaturan Toko.
*   **Kitchen**: Layar Antrian Pesanan (KDS).

---

## BAB II: ARSITEKTUR & DESAIN DATABASE

### 2.1 Teknologi yang Digunakan
*   **Backend**: PHP Native (Versi 7.4/8.0+) - Dipilih karena stabilitas dan kemudahan maintenance.
*   **Database**: MySQL / MariaDB - Menyimpan ribuan transaksi dengan relasi kuat.
*   **Frontend**: HTML5, CSS3 Modern (Glassmorphism), JavaScript Vanilla (Tanpa framework berat agar ringan).
*   **Server Environment**: XAMPP (Apache).

### 2.2 Struktur Database (ERD)
Sistem ini dibangun di atas pondasi database relasional yang kuat. Berikut adalah tabel-tabel intinya:

**[MASUKKAN GAMBAR ERD / SKEMA DATABASE DISINI]**

#### A. Tabel Utama
1.  **`Users`**
    *   Fungsi: Menyimpan data karyawan dan hak akses.
    *   Kolom Kunci: `user_id` (PK), `username`, `password_hash`, `role` (admin/kasir/barista/manajer).
2.  **`Products`**
    *   Fungsi: Katalog menu dan stok gudang.
    *   Kolom Kunci: `product_id`, `product_name`, `price`, `stock`, `category`.
3.  **`Customers`**
    *   Fungsi: Database pelanggan member.
    *   Kolom Kunci: `customer_id`, `customer_name`, `phone_number`, `loyalty_points` (Saldo Poin).

#### B. Tabel Transaksi
4.  **`Transactions` (Header)**
    *   Fungsi: Mencatat kepala struk belanja.
    *   Kolom Kunci: `transaction_id`, `invoice_code` (INV/YYYYMMDD/...), `total_amount`, `discount_amount`, `points_used`, `payment_method`, `payment_status`.
5.  **`TransactionDetails` (Detail)**
    *   Fungsi: Rincian barang apa saja yang dibeli dalam satu struk.
    *   Kolom Kunci: `detail_id`, `transaction_id` (FK), `product_id`, `qty`, `subtotal`.

#### C. Tabel Pendukung
6.  **`Expenses`**: Mencatat pengeluaran operasional (Es batu, Listrik, dll).
7.  **`Settings`**: Menyimpan konfigurasi dinamis (Nama Toko, Alamat, Pajak PPN).

---

### 2.3 Spesifikasi Lingkungan Sistem
Agar sistem berjalan optimal, berikut adalah rekomendasi spesifikasi minimum:

**A. Perangkat Keras (Hardware)**
*   **Server/PC Kasir**: Processor Intel i3/setara, RAM 4GB, SSD 128GB.
*   **Monitor**: Resolusi 1366x768 (Optimal 1920x1080).
*   **Printer**: Thermal Printer 80mm/58mm (USB/LAN Interface).

**B. Perangkat Lunak (Software)**
*   **Sistem Operasi**: Windows 10/11, Linux, atau macOS.
*   **Web Server**: XAMPP / LAMP Stack (PHP 7.4+, MySQL 5.7+).
*   **Browser**: Google Chrome (Wajib) atau Microsoft Edge.

**C. Jaringan (Network)**
*   Koneksi LAN/WiFi stabil untuk komunikasi KDS Dapur dan Kasir (Opsional internet untuk backup cloud).

---

## BAB III: SPESIFIKASI FITUR & LOGIKA BISNIS

### 3.1 Modul Kasir (POS)
Halaman ini adalah jantung operasional. Didesain dengan antarmuka gelap (Dark Mode) agar nyaman di mata kasir.

**[MASUKKAN FOTO TAMPILAN KASIR (POS) DISINI]**

*   **Fitur Keranjang**: Kalkulasi realtime (Subtotal).
*   **Diskon Manual**:
    *   *Logika*: Input nominal Rupiah -> Validasi tidak boleh > subtotal -> Potong Total -> Hitung Pajak dari sisa.
*   **Tukar Poin (Loyalty)**:
    *   *Logika*: Cek saldo poin pelanggan -> Konversi 1 Poin = Rp 100 -> Potong Total Belanja -> Kurangi Saldo Poin di Database.

### 3.2 Modul Dapur (KDS - Kitchen Display System)
Layar ini dipasang di area barista/koki.

**[MASUKKAN FOTO LAYAR DAPUR (KDS) DISINI]**

*   **Indikator Waktu**:
    *   < 10 menit: Hijau (Aman).
    *   10 - 20 menit: Kuning (Waspada).
    *   > 20 menit: Merah (Prioritas Tinggi).
*   **Integrasi Suara**: Menggunakan Google Voice API untuk notifikasi "Ada Pesanan Baru".

### 3.3 Modul Laporan & Keuangan
Sistem menyediakan transparansi keuangan penuh.

**[MASUKKAN FOTO HALAMAN LAPORAN / EXCEL DISINI]**

*   **Laporan Excel**:
    *   Otomatis digenerate dengan format `.xls`.
    *   Kolom terpisah untuk: **Omset Kotor**, **Diskon Diberikan**, **Poin Terpakai**, **Pajak**, dan **Omset Bersih**.
*   **Net Profit Dashboard**:
    *   Rumus: `(Total Penjualan - Diskon - Modal HPP - Pengeluaran Operasional)`.

---

## BAB IV: KEAMANAN SISTEM

### 4.1 Session Timeout (Auto-Logout)
Untuk mencegah penyalahgunaan saat kasir meninggalkan meja, sistem menerapkan **Inactivity Timer**.
*   **Durasi**: 60 Menit (1 Jam).
*   **Mekanisme**: Jika tidak ada pergerakan mouse/keyboard, sesi dihancurkan dan user dilempar ke Login Page.

### 4.2 Validasi Input (Anti-Curang)
*   **Anti-Minus**: Sistem menolak input barang dengan jumlah negatif.
*   **Stok Kunci**: Barang yang stoknya 0 tidak bisa dimasukkan ke keranjang.
*   **Diskon Limit**: Diskon tidak bisa melebihi total belanja (Mencegah total minus).

### 4.3 Hak Akses (Role Base)
*   User **Kasir** DI-BLOKIR dari halaman Laporan Keuangan & Edit Produk.
*   User **Admin** memiliki akses mutlak ("God Mode").

---

## BAB V: PANDUAN PENGGUNA (RINGKASAN)

### 5.1 Alur Transaksi Standar
1.  Buka halaman **Kasir**.
2.  Pilih menu yang dipesan (Klik gambar).
3.  Tentukan "Dine In" atau "Take Away".
4.  Input Nama Pelanggan (Jika Member, poin akan muncul).
5.  Klik **PROSES BAYAR**.
6.  Pilih Metode (Tunai/QRIS). Cek kembalian.
7.  Selesai -> Struk Tercetak -> Dapur Bunyi.

### 5.2 Cara Tutup Buku (Closing)
1.  Masuk ke menu **Riwayat Transaksi**.
2.  Filter tanggal hari ini.
3.  Klik tombol **Export Excel**.
4.  Cocokkan "Total Pendapatan Bersih" di Excel dengan Uang di Laci + Bukti Transfer QRIS.

---

## LAMPIRAN
*(Bagian ini disediakan untuk menyisipkan bukti foto nyata)*

1.  **Foto Struk Fisik**: [TEMPEL FOTO STRUK DISINI]
2.  **Foto Tampilan Mobile**: [TEMPEL FOTO TAMPILAN HP DISINI]

---
*Dokumen ini dibuat otomatis oleh Sistem Bellen Beans  sebagai referensi teknis yang valid.*
