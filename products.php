<?php
session_start();
require_once 'config/database.php';

// Access Control: Only Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Handle Form Submission (Add/Edit)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // File Upload Handler
    $image_path = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = uniqid('prod_') . '.' . $ext;
            $destination = 'assets/uploads/' . $filename;
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $destination)) {
                $image_path = $destination;
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = $_POST['product_name'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category = $_POST['category_id'];
        
        $stmt = $conn->prepare("INSERT INTO products (product_name, price, stock, category_id, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdiis", $name, $price, $stock, $category, $image_path);
        if ($stmt->execute()) {
            $msg = "Produk berhasil ditambahkan!";
        } else {
            $msg = "Gagal menambah produk: " . $conn->error;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['product_id'];
        // Optional: Delete image file from server
        $res = $conn->query("SELECT image_url FROM products WHERE product_id = $id");
        if ($row = $res->fetch_assoc()) {
            if ($row['image_url'] && file_exists($row['image_url'])) unlink($row['image_url']);
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $msg = "Produk berhasil dihapus!";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['product_id'];
        $name = $_POST['product_name'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category = $_POST['category_id'];

        if ($image_path) {
            // Unlink/Delete old image if exists
            $q = $conn->query("SELECT image_url FROM products WHERE product_id = $id");
            if ($r = $q->fetch_assoc()) {
                if ($r['image_url'] && file_exists($r['image_url'])) {
                    unlink($r['image_url']);
                }
            }

            // Update with new image
             $stmt = $conn->prepare("UPDATE products SET product_name=?, price=?, stock=?, category_id=?, image_url=? WHERE product_id=?");
             $stmt->bind_param("sdiisi", $name, $price, $stock, $category, $image_path, $id);
        } else {
            // Keep old image
            $stmt = $conn->prepare("UPDATE products SET product_name=?, price=?, stock=?, category_id=? WHERE product_id=?");
            $stmt->bind_param("sdiii", $name, $price, $stock, $category, $id);
        }
        
        if ($stmt->execute()) {
            $msg = "Produk berhasil diperbarui!";
        }
    }
}

// Fetch Data
$products = $conn->query("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.product_id DESC");
$categories = $conn->query("SELECT * FROM categories");
$cats = [];
while($c = $categories->fetch_assoc()) $cats[] = $c;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Bellen Beans Coffee</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/mobile-nav.js" defer></script>
    <style>
        /* Extra styles for table and modal */
        .table-container {
            background: var(--bg-surface);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-top: 2rem;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-main);
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        th {
            color: var(--primary);
            font-weight: 600;
        }
        tr:hover {
            background: rgba(255,255,255,0.02);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: var(--bg-surface);
            padding: 2rem;
            border-radius: var(--radius-md);
            width: 100%;
            max-width: 500px;
            position: relative;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .close-modal {
            position: absolute;
            top: 1rem; right: 1rem;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.5rem;
        }
    </style>

    <?php @include 'includes/analytics.php'; ?>
</head>
<body>
    <!-- Floating Coffee Beans Decoration -->
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0;">
        <div class="coffee-bean"></div>
        <div class="coffee-bean"></div>
        <div class="coffee-bean"></div>
    </div>

    <div class="dashboard-layout" style="grid-template-columns: 260px 1fr;">
        <!-- Reusing Sidebar Structure -->
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content" style="overflow-y: auto;">
            <div class="flex justify-between items-center" style="gap: 2rem;">
                <h1>Daftar Produk</h1>
                <div class="search-box" style="flex: 1; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="text" id="prodSearch" placeholder="Cari nama produk atau kategori..." style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; background: var(--bg-surface); border: 1px solid rgba(255,255,255,0.1); border-radius: 50px; color: white;">
                </div>
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Tambah Produk
                </button>
            </div>

            <?php if ($msg): ?>
                <div style="background: rgba(16, 185, 129, 0.2); color: #86efac; padding: 1rem; margin-top: 1rem; border-radius: var(--radius-sm);">
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="prodTableBody">
                        <?php $no=1; while($row = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div style="width: 50px; height: 50px; background: #333; overflow: hidden; border-radius: 4px;">
                                    <?php if($row['image_url']): ?>
                                        <img src="<?= $row['image_url'] ?>" alt="Img" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-image" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #555;"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= $row['product_name'] ?></td>
                            <td><?= $row['category_name'] ?></td>
                            <td>Rp <?= number_format($row['price'], 0, ',', '.') ?></td>
                            <td><?= $row['stock'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline" style="padding: 0.5rem;" onclick='openModal("edit", <?= json_encode($row) ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline" style="padding: 0.5rem; color: var(--accent-red); border-color: var(--accent-red);" onclick="deleteProduct(<?= $row['product_id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
        
        <!-- Empty aside to maintain layout or just fix grid in CSS for this page if needed, but keeping structure is easier -->
        <aside style="background: var(--bg-surface); border-left: 1px solid rgba(255,255,255,0.05);"></aside>
    </div>

    <!-- Modal Form -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle" style="margin-bottom: 1.5rem;">Tambah Produk</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="product_id" id="productId">
                
                <div style="text-align: center; margin-bottom: 1rem;">
                    <div id="imagePreview" style="width: 100%; height: 150px; background: #2a2a2a; border-radius: var(--radius-sm); margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <i class="fas fa-image fa-3x" style="color: #444;"></i>
                    </div>
                    <label for="productImage" class="btn btn-outline" style="cursor: pointer; font-size: 0.8rem;">
                        <i class="fas fa-upload"></i> Pilih Gambar
                    </label>
                    <input type="file" name="product_image" id="productImage" class="hidden" accept="image/*" onchange="previewImage(this)">
                </div>

                <div class="form-group">
                    <label>Nama Produk</label>
                    <input type="text" name="product_name" id="productName" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category_id" id="categoryId" class="form-input" required style="background: rgba(255,255,255,0.03); color: white;">
                        <?php foreach($cats as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" style="background: #333;"><?= $cat['category_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex gap-4">
                    <div class="form-group w-full">
                        <label>Harga (Rp)</label>
                        <input type="number" name="price" id="productPrice" class="form-input" required>
                    </div>
                    <div class="form-group w-full">
                        <label>Stok</label>
                        <input type="number" name="stock" id="productStock" class="form-input" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full">Simpan</button>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function openModal(mode, data = null) {
            const modal = document.getElementById('productModal');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            const preview = document.getElementById('imagePreview');
            
            // Inputs
            const idInput = document.getElementById('productId');
            const nameInput = document.getElementById('productName');
            const catInput = document.getElementById('categoryId');
            const priceInput = document.getElementById('productPrice');
            const stockInput = document.getElementById('productStock');
            const fileInput = document.getElementById('productImage');

            // Reset File Input
            fileInput.value = '';

            if (mode === 'edit' && data) {
                title.innerText = 'Edit Produk';
                action.value = 'edit';
                idInput.value = data.product_id;
                nameInput.value = data.product_name;
                catInput.value = data.category_id;
                priceInput.value = data.price;
                stockInput.value = data.stock;

                if (data.image_url) {
                    preview.innerHTML = `<img src="${data.image_url}" style="width: 100%; height: 100%; object-fit: cover;">`;
                } else {
                    preview.innerHTML = `<i class="fas fa-image fa-3x" style="color: #444;"></i>`;
                }
            } else {
                title.innerText = 'Tambah Produk';
                action.value = 'add';
                idInput.value = '';
                nameInput.value = '';
                priceInput.value = '';
                stockInput.value = '';
                catInput.selectedIndex = 0;
                preview.innerHTML = `<i class="fas fa-image fa-3x" style="color: #444;"></i>`;
            }
            
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
        }

        function deleteProduct(id) {
            if (confirm('Hapus produk ini? Semua data stok akan hilang!')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="product_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Real-time Search
        document.getElementById('prodSearch').addEventListener('input', function(e) {
            const q = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#prodTableBody tr');
            
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    </script>
</body>
</html>

