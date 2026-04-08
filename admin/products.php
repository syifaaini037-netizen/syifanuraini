<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

 $current_page = basename($_SERVER['PHP_SELF']);

function rupiah($angka){ return "Rp " . number_format($angka,0,",","."); }

 $stmt = $pdo->query("SELECT p.*, ps.price FROM products p LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ps.size = 'R' ORDER BY p.id DESC");
 $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
 $total_sales = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('paid','completed')")->fetchColumn();
 $total_sales = $total_sales ? $total_sales : 0;
 $total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><title>Products</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* GLOBAL & LAYOUT */
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
.main h2{margin:0 0 25px 0;}

/* COMPONENTS */
.stats{display:flex; gap:20px; margin-bottom:30px; flex-wrap: wrap;}
.stat-card{flex:1; min-width: 200px; background:#c8ad8d; padding:25px; border-radius:15px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.1);}
.stat-card h3 { margin:0; font-size:16px; } .stat-card h1 { margin:10px 0 0 0; } .stat-card p { margin:5px 0 0 0; font-size:12px; opacity:0.8; }

.product-container{background:#a88867; padding:30px; border-radius:25px; box-shadow:0 5px 15px rgba(0,0,0,0.2);}
.product-card{background:#d7b899; padding:20px; border-radius:20px; display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap: wrap; gap:15px;}
.product-info{display:flex; align-items:center; gap:20px; flex:1;}
.product-info img{width:90px; height:90px; object-fit:cover; border-radius:10px;}
.edit-btn{background:#6b4a3a; color:white; padding:8px 20px; border-radius:8px; text-decoration:none;}
.delete-btn{background:#c62828; color:white; padding:8px 20px; border-radius:8px; text-decoration:none; margin-left:5px;}
.add-btn-top{background:#7db3e6; padding:10px 20px; border-radius:10px; text-decoration:none; color:black; font-weight:500; display:inline-block; margin-bottom:20px;}

/* MOBILE HEADER & OVERLAY */
.mobile-header { display: none; position: fixed; top:0; left:0; right:0; height:60px; background:#6f4e37; align-items:center; justify-content:space-between; padding:0 20px; z-index: 999; color: white; }
.toggle-btn { background:none; border:none; color:white; cursor:pointer; font-size:24px; }
.overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:998; }

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); } .sidebar.show { transform: translateX(0); }
    .main { margin-left: 0; padding-top: 80px; }
    .mobile-header { display: flex; } .overlay.show { display: block; }
    .product-card { flex-direction: column; align-items: flex-start; }
    .product-info { width: 100%; }
    .btn-group { width: 100%; display: flex; justify-content: space-between; }
}
</style>
</head>
<body>

<!-- MOBILE HEADER -->
<div class="mobile-header">
    <h3>Products</h3>
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
</div>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="wrapper">
<div class="sidebar">
    <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><img src="../assets/img/admin/dashboard.png"> Dashboard</a>
    <a href="manajemen-user.php" class="<?= $current_page == 'manajemen-user.php' ? 'active' : '' ?>"><img src="../assets/img/admin/manajemen-user.png"> Manajemen User</a>
    <a href="transaction.php" class="<?= $current_page == 'transaction.php' ? 'active' : '' ?>"><img src="../assets/img/admin/transaction.png"> Transaction</a>
    <a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>"><img src="../assets/img/admin/products.png"> Products</a>
    <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>"><img src="../assets/img/admin/reports.png"> Reports</a>
    <a href="backup.php" class="<?= $current_page == 'backup.php' ? 'active' : '' ?>"><img src="../assets/img/admin/backup.png"> Backup</a>
    <a href="restore.php" class="<?= $current_page == 'restore.php' ? 'active' : '' ?>"><img src="../assets/img/admin/restore.png"> Restore</a>
    <a href="tracking.php"><img src="../assets/img/admin/tracking.png"> Tracking</a>
    <a href="../auth/logout.php"><img src="../assets/img/admin/logout.png"> Logout</a>
</div>

<div class="main">
<a href="add-product.php" class="add-btn-top">+ ADD PRODUCT</a>
<div class="stats">
    <div class="stat-card"><h3>Orders</h3><h1><?= $total_orders ?></h1><p>Total Orders</p></div>
    <div class="stat-card"><h3>Total Sales</h3><h2><?= rupiah($total_sales) ?></h2><p>Total Revenue</p></div>
    <div class="stat-card"><h3>Customers</h3><h1><?= $total_customers ?></h1><p>Total Users</p></div>
</div>

<div class="product-container">
<h2>Menu List</h2><br>
<?php if(count($products) > 0): ?>
    <?php foreach($products as $row): ?>
    <div class="product-card">
        <div class="product-info">
            <?php if(!empty($row['image']) && file_exists("../uploads/".$row['image'])): ?>
                <img src="../uploads/<?= htmlspecialchars($row['image']) ?>">
            <?php else: ?>
                <img src="https://via.placeholder.com/90x90?text=No+Img">
            <?php endif; ?>
            <div>
                <h3><?= htmlspecialchars($row['name']) ?></h3>
                <p><?= htmlspecialchars($row['description']) ?></p>
                <p><b><?= rupiah($row['price'] ?? 0) ?> (Size R)</b></p>
            </div>
        </div>
        <div class="btn-group">
            <a href="edit-product.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
            <a href="delete-product.php?id=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Yakin ingin hapus?')">Delete</a>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center;">Belum ada produk.</p>
<?php endif; ?>
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