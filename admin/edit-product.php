<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

 $current_page = basename($_SERVER['PHP_SELF']);
 $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

 $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
 $stmt->execute([$id]);
 $product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) { die("Produk tidak ditemukan."); }

 $stmt_sizes = $pdo->prepare("SELECT size, price FROM product_sizes WHERE product_id = ?");
 $stmt_sizes->execute([$id]);
 $sizes = $stmt_sizes->fetchAll(PDO::FETCH_KEY_PAIR);

 $price_r = isset($sizes['R']) ? $sizes['R'] : 0;
 $price_s = isset($sizes['S']) ? $sizes['S'] : 0;
 $price_l = isset($sizes['L']) ? $sizes['L'] : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product - Sipa Coffee</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        html,body{margin:0;padding:0;height:100%;font-family:'Poppins',sans-serif;background:#e7d8bd; overflow-x: hidden;}
        .wrapper{display:flex;min-height:100vh; position: relative;}
        
        /* SIDEBAR */
        .sidebar{position: fixed; top:0; left:0; width:260px; height:100vh; background:#6f4e37; display:flex; flex-direction:column; z-index:1000; transition: transform 0.3s ease;}
        .sidebar a{display:flex; align-items:center; gap:12px; padding:16px 20px; color:white; text-decoration:none; border-bottom:1px solid rgba(255,255,255,0.08); transition:0.3s;}
        .sidebar a:hover{background:#5a3d2b; padding-left:25px;}
        .sidebar a.active{background:#4e342e; font-weight:600; border-left: 5px solid #e7d8bd;}
        .sidebar img{width:28px; height:28px; object-fit:contain;}

        /* MAIN */
        .main{flex:1; padding:30px; margin-left: 260px; transition: margin-left 0.3s ease; min-height: 100vh;}
        .form-container{background:#a88867;padding:40px;border-radius:25px;box-shadow:0 8px 15px rgba(0,0,0,0.2);max-width:800px;}
        .form-container h2{margin-bottom:30px;color:white;}
        .form-group{margin-bottom:20px;}
        label{display:block;margin-bottom:8px;color:white;font-weight:500;}
        input, textarea, select{width:100%;padding:12px;border:none;border-radius:12px;outline:none;font-size:14px; box-sizing:border-box;}
        .price-grid{display:grid;grid-template-columns:repeat(3, 1fr);gap:20px;}
        .btn-group{margin-top:25px;display:flex;gap:15px;}
        .btn{padding:10px 25px;border:none;border-radius:10px;cursor:pointer;font-weight:500;text-decoration:none;text-align:center;color:white;}
        .btn-save{background:#4CAF50;}
        .btn-cancel{background:#c0392b;}
        .current-img{width:100px;height:100px;object-fit:cover;border-radius:10px;margin-top:10px;border:2px solid white;}

        /* MOBILE HEADER & OVERLAY */
        .mobile-header { display: none; position: fixed; top:0; left:0; right:0; height:60px; background:#6f4e37; align-items:center; justify-content:space-between; padding:0 20px; z-index: 999; color: white; }
        .toggle-btn { background:none; border:none; color:white; cursor:pointer; font-size:24px; }
        .overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:998; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main { margin-left: 0; padding-top: 80px; }
            .mobile-header { display: flex; }
            .overlay.show { display: block; }
            .price-grid { grid-template-columns: 1fr; }
            .form-container { padding: 20px; }
        }
    </style>
</head>
<body>

<!-- MOBILE HEADER -->
<div class="mobile-header">
    <h3>Edit Product</h3>
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
</div>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="wrapper">
    <div class="sidebar">
        <a href="dashboard.php"><img src="../assets/img/admin/dashboard.png"> Dashboard</a>
        <a href="manajemen-user.php"><img src="../assets/img/admin/manajemen-user.png"> Manajemen User</a>
        <a href="transaction.php"><img src="../assets/img/admin/transaction.png"> Transaction</a>
        <a href="products.php" class="active"><img src="../assets/img/admin/products.png"> Products</a>
        <a href="reports.php"><img src="../assets/img/admin/reports.png"> Reports</a>
        <a href="backup.php"><img src="../assets/img/admin/backup.png"> Backup</a>
        <a href="restore.php"><img src="../assets/img/admin/restore.png"> Restore</a>
        <a href="tracking.php"><img src="../assets/img/admin/tracking.png"> Tracking</a>
        <a href="../auth/logout.php"><img src="../assets/img/admin/logout.png"> Logout</a>
    </div>

    <div class="main">
        <div class="form-container">
            <h2>Edit Product: <?= htmlspecialchars($product['name']) ?></h2>
            <form action="proses_edit_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <input type="hidden" name="old_image" value="<?= $product['image'] ?>">
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Product Image</label>
                    <?php if(!empty($product['image'])): ?>
                        <div><img src="../uploads/<?= $product['image'] ?>" class="current-img"><p style="font-size:12px; color:#ddd;">*Biarkan kosong jika tidak ingin ganti gambar</p></div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*">
                </div>
                <label>Price per Size</label>
                <div class="price-grid">
                    <div class="form-group"><label>Size R (Regular)</label><input type="number" name="price_r" value="<?= $price_r ?>" required></div>
                    <div class="form-group"><label>Size S (Small)</label><input type="number" name="price_s" value="<?= $price_s ?>" required></div>
                    <div class="form-group"><label>Size L (Large)</label><input type="number" name="price_l" value="<?= $price_l ?>" required></div>
                </div>
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_best_seller" value="1" style="width:auto; transform:scale(1.2);" <?= $product['is_best_seller'] ? 'checked' : '' ?>>
                        Mark as Best Seller
                    </label>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-save">Update Product</button>
                    <a href="products.php" class="btn btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
</body>
</html>