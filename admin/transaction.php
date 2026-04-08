<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

 $current_page = basename($_SERVER['PHP_SELF']);

function rupiah($angka){ return "IDR " . number_format($angka,0,",","."); }

if(isset($_GET['status']) && isset($_GET['id'])){
    $id = $_GET['id']; $status = $_GET['status'];
    $allowed = ['done','process','cancel', 'paid', 'pending', 'completed', 'cancelled'];
    if($status == 'done') $status = 'completed'; if($status == 'cancel') $status = 'cancelled';
    if(in_array($status, $allowed)){
        $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?"); $stmt->execute([$status,$id]);
    }
    header("Location: transaction.php"); exit;
}

 $stmt = $pdo->query("SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");
 $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><title>Transaction</title>
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
.section{background:#bfa07c; padding:30px; border-radius:25px; box-shadow:0 8px 20px rgba(0,0,0,0.2);}

/* TABLE */
table{width:100%; border-collapse:collapse; text-align:center;}
th,td{border:2px solid black; padding:14px;}
th{background:#d2b48c; font-size:16px;}
.status-btn{padding:6px 15px; border-radius:8px; color:white; text-decoration:none; font-size:13px;}
.completed{background:#2e8b57;} .process{background:#f4c542; color:black;} .cancelled{background:#e74c3c;} .pending{background:#7f8c8d;} .paid{background:#3498db;}
.action-btn{background:#5dade2; padding:6px 12px; border-radius:8px; text-decoration:none; color:black; margin:3px; display:inline-block;}

/* MOBILE HEADER & OVERLAY */
.mobile-header { display: none; position: fixed; top:0; left:0; right:0; height:60px; background:#6f4e37; align-items:center; justify-content:space-between; padding:0 20px; z-index: 999; color: white; }
.toggle-btn { background:none; border:none; color:white; cursor:pointer; font-size:24px; }
.overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:998; }

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); } .sidebar.show { transform: translateX(0); }
    .main { margin-left: 0; padding-top: 80px; }
    .mobile-header { display: flex; } .overlay.show { display: block; }
    table { display: block; overflow-x: auto; white-space: nowrap; }
}
</style>
</head>
<body>

<!-- MOBILE HEADER -->
<div class="mobile-header">
    <h3>Transaction</h3>
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
    <a href="../auth/logout.php"><img src="../assets/img/admin/logout.png"> LogOut</a>
</div>

<div class="main">
<h2>Transaction</h2>
<div class="section">
<table>
<tr><th>No</th><th>DATE</th><th>CUSTOMER NAME</th><th>TOTAL</th><th>STATUS</th><th>ACTION</th></tr>
<?php if(count($transactions) > 0): ?>
<?php $no=1; foreach($transactions as $row): ?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
    <td><?= htmlspecialchars($row['customer_name']) ?></td>
    <td><?= rupiah($row['total_amount']) ?></td>
    <td>
        <?php if($row['status'] == 'completed'): ?>
            <span class="status-btn completed">COMPLETED</span>
        <?php elseif($row['status'] == 'paid'): ?>
            <a class="status-btn paid" href="?id=<?= $row['id'] ?>&status=completed">PAID (Process)</a>
        <?php elseif($row['status'] == 'cancelled'): ?>
            <span class="status-btn cancelled">CANCELLED</span>
        <?php else: ?>
            <a class="status-btn pending" href="?id=<?= $row['id'] ?>&status=paid">PENDING</a>
        <?php endif; ?>
    </td>
    <td>
        <a href="transaction-detail.php?id=<?= $row['id'] ?>" class="action-btn">See</a>
        <a href="print-transaction.php?id=<?= $row['id'] ?>" class="action-btn">Print</a>
    </td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6">Belum ada transaksi</td></tr>
<?php endif; ?>
</table>
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