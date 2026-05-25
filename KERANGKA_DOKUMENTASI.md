# KERANGKA DOKUMENTASI SISTEM INFORMASI (MASTER PLAN)
## Bellen Beans Coffee - Integrated System

Dokumen ini adalah **Pondasi & Struktur** untuk menyusun Laporan Akhir atau Dokumentasi Teknis proyek ini. Silakan kembangkan setiap poin di bawah ini sesuai kebutuhan skripsi/proyek Anda.

---

### BAB 1: PENDAHULUAN (OVERVIEW)
*Bagian ini menjelaskan "Mengapa" sistem ini dibuat.*

1.  **Latar Belakang**:
    *   Masalah awal: Pencatatan manual, stok sering selisih, antrian lama, owner susah pantau omset.
    *   Solusi: Sistem POS Web-based yang terintegrasi (Kasir - Dapur - Gudang).
2.  **Tujuan Sistem**:
    *   Mempercepat transaksi (30 detik/struk).
    *   Mengamankan kebocoran uang (Sistem Diskon & Poin).
    *   Real-time reporting (Laba rugi bisa dilihat detik itu juga).
3.  **Ruang Lingkup**:
    *   Pengguna: Admin, Manajer, Kasir, Barista.
    *   Fitur: POS, KDS (Dapur), Stok, Member/Loyalty, Laporan Keuangan.

---

### BAB 2: PERANCANGAN SISTEM & DATABASE (BACKEND)
*Bagian ini menjelaskan "Jeroan/Teknis" sistem.*

1.  **Arsitektur Teknologi**:
    *   Bahasa: PHP Native (Kinerja cepat, mudah dimodifikasi).
    *   Database: MySQL / MariaDB.
    *   Frontend: HTML5, CSS3 (Glassmorphism UI), JavaScript Pure (Tanpa Framework berat).
2.  **Struktur Database (ERD)**:
    *   *Jelaskan 7 Tabel Utama*:
        *   `Users`: Untuk login & hak akses.
        *   `Products`: Data menu & stok.
        *   `Customers`: Data member & poin loyalty.
        *   `Transactions`: Header struk (Total, Diskon, Pajak).
        *   `TransactionDetails`: Rincian item per struk.
        *   `Expenses`: Pengeluaran operasional (biar Net Profit akurat).
        *   `Settings`: Pengaturan dinamis (Nama toko, PPN, dll).

---

### BAB 3: IMPLEMENTASI FITUR (FRONTEND & LOGIC)
*Bagian ini menjelaskan "Apa Saja Fiturnya".*

1.  **Modul Kasir (POS) - The Core**:
    *   Fitur *Fast Checkout* (Tunai/QRIS).
    *   Fitur *Diskon Manual* & *Tukar Poin* (Sudah diamankan validator).
    *   Struk Belanja Thermal 80mm.
2.  **Modul Dapur (KDS)**:
    *   Pengganti printer dapur.
    *   Sistem Timer (Hijau/Kuning/Merah) untuk memacu kecepatan Barista.
    *   Voice Notification (Google Voice) saat order masuk.
3.  **Modul Laporan (Reporting)**:
    *   Grafik Penjualan Mingguan.
    *   Export Excel (Lengkap dengan kolom Diskon & Poin).
    *   Hitungan Laba Bersih Otomatis.

---

### BAB 4: KEAMANAN & PENGUJIAN (SECURITY)
*Bagian ini penting untuk menunjukkan sistem ini "Bukan Kaleng-kaleng".*

1.  **Keamanan Akun**:
    *   Enkripsi Password (`password_hash` PHP).
    *   Auto-Logout 1 jam (Session Management).
2.  **Validasi Transaksi**:
    *   Stock Locking (Stok dikunci saat checkout).
    *   Mencegah input diskon minus atau ngawur.
    *   Validasi "Dine In" vs "Take Away" agar data akurat.

---

### BAB 5: PANDUAN PENGGUNA (USER GUIDE)
*Ambil referensi dari file `USER_MANUAL.md` yang sudah ada.*

1.  Cara Login & Ganti Shift.
2.  Cara Input Transaksi & Member.
3.  Cara Tutup Buku (Lihat Laporan).
4.  Troubleshooting Ringan (Printer macet, dll).

---

### LAMPIRAN
1.  Screenshot Antarmuka (UI).
2.  Contoh Struk Belanja.
3.  Contoh Laporan Excel.
