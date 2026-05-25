# BUKU PANDUAN SISTEM INFORMASI (USER MANUAL)
**Bellen Beans Coffee - Integrated System**

Dokumen ini adalah panduan lengkap seluruh fitur yang ada di dalam aplikasi web Bellen Beans Coffee. Panduan ini dibagi berdasarkan **JABATAN (ROLE)**, jadi Anda cukup membaca bagian yang sesuai dengan tugas Anda.

---

## 🔐 AKUN AKSES (LOGIN)
Sebelum masuk, pastikan Anda menggunakan akun yang sesuai.
*   **Alamat Web**: `localhost` (atau IP server toko)
*   **Browser Wajib**: Google Chrome (agar suara & printer lancar)

| Jabatan | Username | Password Default | Akses Utama |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `123` | Kontrol Penuh (User, Menu, Laporan) |
| **Manager** | `manajer` | `123` | Laporan Keuangan & Pengeluaran |
| **Kasir** | `kasir` | `123` | Penjualan (POS) & Member |
| **Barista** | `barista` | `123` | Layar Dapur (KDS) & Stok Harian |

---

## I. BAGIAN ADMIN (PENGELOLA SISTEM)
Sebagai Admin, Anda memiliki akses ke **semua menu**. Berikut detail fitur yang Anda kelola:

### 1. Dashboard (Halaman Utama)
Ini adalah pusat informasi cepat.
*   **Kartu Statistik**: Cek omset hari ini, total pengeluaran, dan estimasi laba bersih secara *real-time*.
*   **Grafik Penjualan**: Melihat tren apakah penjualan sedang naik atau turun minggu ini.
*   **Produk Terlaris**: Info menu apa yang paling laku (bisa buat strategi promo).

### 2. Kelola Stok/Produk
Menu ini adalah "jantung" database menu toko.
*   **Tambah Menu Baru**: Klik tombol "+ Tambah Produk". Isi nama, kategori, harga, dan upload foto yang menarik.
*   **Update Stok**: Anda bisa mengubah jumlah stok yang tersedia. Jika di-set `0`, menu akan otomatis hilang/abu-abu di layar Kasir.
*   **Edit/Hapus**: Ada tombol pensil (edit) dan sampah (hapus) di setiap baris menu.

### 3. Kelola Pengguna
Tempat membuat akun untuk karyawan baru.
*   **Buat Akun**: Masukkan Username, Nama Lengkap, Password, dan pilih Role (Jabatan).
*   **Reset Password**: Jika kasir lupa password, Anda bisa mengeditnya di sini.

### 4. Kelola Pelanggan
Database seluruh member toko.
*   Melihat daftar nama, nomor HP, dan **Total Poin** mereka.
*   Bisa edit data jika pelanggan ganti nomor HP.

### 5. Riwayat Transaksi
Laporan detail setiap struk yang keluar.
*   **Lihat Detail**: Klik tombol "Mata" untuk melihat isi pesanan dalam satu struk.
*   **Hapus Transaksi**: (Hati-hati!) Hanya Admin yang bisa menghapus data transaksi jika terjadi kesalahan fatal.
*   **Export Excel**: Klik tombol hijau di pojok kanan atas untuk mendownload laporan penjualan ke Excel (bisa per hari/bulan).

---

## II. BAGIAN MANAGER & OWNER
Fokus utama Anda adalah **Uang & Laporan**. Tampilan Anda mirip Admin, tapi lebih fokus ke angka.

### 1. Menu Pengeluaran (Expenses)
Ini fitur KRUSIAL untuk menghitung laba bersih.
*   **Cara Pakai**: Setiap kali ada uang keluar dari laci kasir untuk belanja operasional (beli es batu, beli susu, bayar listrik, uang kebersihan), **WAJIB** dicatat di sini.
*   **Kategori**: Pilih kategori (Bahan Baku, Operasional, Lain-lain).
*   **Efek**: Angka yang Anda input di sini akan otomatis **mengurangi** angka profit di Dashboard.

### 2. Laporan & Grafik
Di halaman depan (Dashboard), Anda bisa memantau:
*   **Omset Kotor**: Total uang hasil jualan.
*   **Net Profit**: Uang sisa setelah dikurangi pengeluaran di atas.
*   **Stok Menipis**: Peringatan jika ada bahan yang stoknya di bawah 5.

---

## III. BAGIAN KASIR (FRONT OFFICE)
Tugas Anda adalah melayani pelanggan dengan cepat. Halaman Anda disebut **POS (Point of Sales)**.

### Fitur-Fitur di Layar Kasir:
1.  **Pencarian Menu**:
    *   Ketik nama menu, misal "Latte", di kolom atas.
    *   Atau klik tombol filter: "Coffee", "Non-Coffee", "Snack".
2.  **Keranjang Belanja (Kanan)**:
    *   **Jenis Pesanan**: Di atas keranjang, ada tombol **Dine In** (Makan Sini) atau **Take Away** (Bungkus). Pastikan dipilih sesuai permintaan tamu.
    *   **Edit Jumlah**: Tekan `+` atau `-` untuk ubah jumlah porsi.
3.  **Member Cepat (Quick Add)**:
    *   Di sebelah kolom nama pelanggan, ada **tombol kecil (+) orange**.
    *   Klik itu untuk mendaftarkan member baru dalam hitungan detik (cukup Nama & No WA).
    *   *Keuntungan*: Pelanggan langsung dapat poin dari transaksi saat itu juga.
4.  **Pembayaran (Checkout)**:
    *   Tekan tombol **PROSES** (Besar di bawah).
    *   **Metode Pembayaran**:
        *   **Tunai**: Ketik uang yang diterima. Sistem hitung kembalian.
        *   **QRIS / Debit**: Pilih opsi ini jika pakai EDC/QR.
    *   **Cek Struk**: Setelah sukses, tombol "Cetak Struk" akan muncul.
93: 5.  **Diskon & Tukar Poin (PROMO)**:
94:     *   **Diskon Manual**: Ada kolom "Diskon (Rp)" di keranjang. Ketik nominal potongan jika ada promo manual.
95:     *   **Tukar Poin**:
96:         *   Ketik nama pelanggan member di kolom "Nama".
97:         *   Jika punya poin, tombol **"Tukar Poin"** akan muncul otomatis.
98:         *   **Geser Saklar** poin ke posisi ON ("Emas") untuk memotong total belanja dengan poin.

---

## IV. BAGIAN BARISTA (DAPUR)
Layar Anda adalah **KDS (Kitchen Display System)**. Tujuannya menggantikan kertas bon dapur.

### Tampilan Layar Dapur:
1.  **Header Ringkasan (Atas)**:
    *   Ada daftar menu yang sedang antre (misal: "Barista: 3 Kopi, 2 Makanan"). Ini membantu Anda menyiapkan alat/bahan sekaligus.
    *   **Tombol Stok Cepat**: Nama-nama menu di atas bisa DIKLIK. Jika diklik, menu tersebut akan ditandai **HABIS** di Kasir. Klik lagi untuk adakan stok.
2.  **3 Kolom Status**:
    *   **Order Masuk (Kiri)**: Pesanan baru. Ada timer detiknya.
        *   *Warna Putih*: Baru masuk (<7 menit).
        *   *Warna Kuning*: Sudah agak lama (8-14 menit).
        *   *Warna Merah Kedip*: **DARURAT** (>15 menit). Prioritaskan!
    *   **Sedang Dibuat (Tengah)**: Pesanan yang sedang Anda kerjakan.
    *   **Siap Saji (Kanan)**: Pesanan yang sudah jadi dan menunggu diantar waiter.
3.  **Suara Notifikasi**:
    *   Setiap ada pesanan baru, sistem akan bunyi "TING!" dan suara Google bicara "Ada pesanan masuk".
    *   **Syarat**: Tombol "Aktifkan Suara" di pojok kanan harus diklik sekali saat awal buka toko.

---

## V. BAGIAN PELANGGAN (LOYALTY)
Website khusus untuk pelanggan cek poin (Bisa diakses via QR Code di meja).

*   **Halaman Cek Poin**: Pelanggan memasukkan Nomor HP.
*   **Info Member**: Menampilkan Level (Bronze/Silver/Gold) dan sisa poin untuk naik level.
*   Ini fitur mandiri, pelanggan tidak perlu login.

---

## VI. BANTUAN TEKNIS (TROUBLESHOOTING)

**1. Printer tidak merespon?**
*   Cek kabel USB printer.
*   Coba tekan `CTRL + P` di keyboard.
*   Pastikan driver printer sudah terinstall di Windows.

**2. Suara di dapur tidak bunyi?**
*   Browser Google Chrome memblokir suara otomatis (autoplay).
*   Solusi: Refresh halaman (`F5`), lalu KLIK tombol **"Aktifkan Suara"** di pojok kanan atas KDS.

**3. Salah input transaksi?**
*   Lapor ke Manager/Admin. Hanya mereka yang punya akses hapus data di menu Riwayat Transaksi.

---
*Dokumen ini diperbarui terakhir pada: 28 Des 2025*
