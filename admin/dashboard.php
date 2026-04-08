<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

function rupiah($angka){ return "Rp " . number_format($angka,0,",","."); }

 $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
 $total_sales = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('paid','completed')")->fetchColumn();
 $total_sales = $total_sales ? $total_sales : 0;
 $total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

 $stmt = $pdo->query("SELECT p.*, ps.price, ps.size FROM products p LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ps.size = 'R' WHERE p.is_best_seller = 1 LIMIT 3");
 $best_sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
 $current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

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
.card-container{display:flex; gap:20px; margin-bottom:35px; flex-wrap: wrap;}
.card{flex:1; min-width: 200px; background:#c8ad8d; padding:25px; border-radius:15px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.1);}
.card h3{margin-top:10px; font-size:24px;}
.section{background:#a88867; padding:25px; border-radius:20px;}
.product{background:#d9c2a3; padding:15px; border-radius:15px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;}
.btn{background:#6f4e37; color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer; font-size:14px;}
.btn:hover{background:#4e342e;}

/* MODAL */
.modal{display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000; padding:20px;}
.modal-content{background:#ffffff; width:340px; border-radius:20px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.25); text-align:center; position:relative; animation: slideUp 0.3s ease-out;}
@keyframes slideUp {from{transform:translateY(50px); opacity:0;} to{transform:translateY(0); opacity:1;}}
#m_image{width:100%; height:200px; object-fit:cover; display:block; background-color:#f0f0f0;}
.modal-body{padding:20px;}
#m_name{margin:0 0 5px 0; font-size:20px; font-weight:700; color:#3e2723;}
#m_price{font-size:18px; font-weight:600; color:#6f4e37; margin-bottom:10px;}
#m_size{font-size:14px; color:#888; margin-bottom:15px; display:inline-block; background:#f4f4f4; padding:2px 8px; border-radius:10px;}
#m_desc{font-size:13px; color:#555; line-height:1.5; text-align:left; margin-bottom:25px; min-height:40px;}
.btn-modal-close{display:block; width:85%; margin:0 auto 20px auto; padding:12px; background:#e7d8bd; color:#4e342e; border:none; border-radius:15px; font-weight:600; cursor:pointer; transition:0.2s;}
.btn-modal-close:hover{background:#d7c4a5;}

/* MOBILE HEADER & OVERLAY */
.mobile-header { display: none; position: fixed; top:0; left:0; right:0; height:60px; background:#6f4e37; align-items:center; justify-content:space-between; padding:0 20px; z-index: 999; color: white; }
.toggle-btn { background:none; border:none; color:white; cursor:pointer; font-size:24px; }
.overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:998; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.show { transform: translateX(0); }
    .main { margin-left: 0; padding-top: 80px; }
    .mobile-header { display: flex; }
    .overlay.show { display: block; }
    .product { flex-direction: column; align-items: flex-start; gap: 10px; }
    .btn { width: 100%; }
}
</style>
</head>
<body>

<!-- MOBILE HEADER -->
<div class="mobile-header">
    <h3>Dashboard</h3>
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
</div>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="wrapper">
<div class="sidebar">
    <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><img src="../assets/img/admin/dashboard.png"> Dashboard</a>
    <a href="manajemen-user.php" class="<?= $current_page == 'manajemen-user.php' ? 'active' : '' ?>"><img src="../assets/img/admin/manajemen-user.png"> Manajemen User</a>
    <a href="transaction.php"><img src="../assets/img/admin/transaction.png"> Transaction</a>
    <a href="products.php"><img src="../assets/img/admin/products.png"> Products</a>
    <a href="reports.php"><img src="../assets/img/admin/reports.png"> Reports</a>
    <a href="backup.php"><img src="../assets/img/admin/backup.png"> Backup</a>
    <a href="restore.php"><img src="../assets/img/admin/restore.png"> Restore</a>
    <a href="tracking.php"><img src="../assets/img/admin/tracking.png"> Tracking</a>
    <a href="../auth/logout.php"><img src="../assets/img/admin/logout.png"> Logout</a>
</div>

<div class="main">
<h2>Dashboard</h2>
<div class="card-container">
    <div class="card">Orders <h3><?= $total_orders ?></h3></div>
    <div class="card">Total Sales <h3><?= rupiah($total_sales) ?></h3></div>
    <div class="card">Customers <h3><?= $total_customers ?></h3></div>
</div>

<div class="section">
<h3>Best Seller Menu</h3>
<?php if(count($best_sellers) > 0): ?>
<?php foreach($best_sellers as $row): ?>
    <?php 
    $imgPath = "../uploads/" . $row['image'];
    if(empty($row['image']) || !file_exists(__DIR__ . "/../uploads/" . $row['image'])){
        $imgPath = "https://via.placeholder.com/340x200?text=No+Image";
    }
    ?>
<div class="product">
    <div>
        <strong><?= $row['name']; ?></strong><br>
        <small><?= rupiah($row['price'] ?? 0); ?> (<?= $row['size'] ?? '-'; ?>)</small>
    </div>
    <button class="btn" onclick="openModal('<?= $row['name']; ?>','<?= rupiah($row['price'] ?? 0); ?>','<?= $row['size'] ?? '-'; ?>',`<?= $row['description']; ?>`,'<?= $imgPath ?>')">Details</button>
</div>
<?php endforeach; ?>
<?php else: ?>
<p>Tidak ada produk best seller.</p>
<?php endif; ?>
</div>
</div>
</div>

<div class="modal" id="modal">
    <div class="modal-content">
        <img id="m_image" src="" alt="Product Image">
        <div class="modal-body">
            <h2 id="m_name"></h2>
            <div id="m_price"></div>
            <div id="m_size"></div>
            <p id="m_desc"></p>
            <button class="btn-modal-close" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('overlay').classList.toggle('show');
}

function openModal(name, price, size, desc, image){
    document.getElementById('m_name').innerText=name;
    document.getElementById('m_price').innerText=price;
    document.getElementById('m_size').innerText="Size: "+size;
    document.getElementById('m_desc').innerText=desc;
    document.getElementById('m_image').src=image;
    document.getElementById('modal').style.display='flex';
}
function closeModal(){ document.getElementById('modal').style.display='none'; }
window.onclick = function(event) {
    if (event.target == document.getElementById('modal')) { closeModal(); }
}
</script>
</body>
</html>