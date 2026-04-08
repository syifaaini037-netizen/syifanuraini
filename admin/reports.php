<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

 $current_page = basename($_SERVER['PHP_SELF']);

 $totalTransaction = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
 $totalSales = $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn();
 $totalSales = $totalSales ? $totalSales : 0;
 $totalProduct = $pdo->query("SELECT SUM(quantity) FROM order_items")->fetchColumn();
 $totalProduct = $totalProduct ? $totalProduct : 0;

if(isset($_POST['generate_report'])){
    $type = $_POST['report_type'];
    $stmt = $pdo->prepare("INSERT INTO reports (report_type) VALUES (?)");
    $stmt->execute([$type]);
    header("Location: reports.php"); exit;
}
 $reports = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

function rupiah($angka){ return "Rp " . number_format($angka,0,",","."); }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports - Sipa Coffee</title>
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
        .main {flex:1; padding:30px; margin-left: 260px; transition: margin-left 0.3s ease; min-height: 100vh;}
        .main h2 {margin:0 0 25px 0;}
        .summary {display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;}
        .card {flex: 1; min-width: 200px; background: #c8ad8d; padding: 25px; border-radius: 20px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.2);}
        .card h3 {font-size: 18px; margin-bottom: 10px;}
        .card h1 {font-size: 32px; margin: 0;}
        .card p {margin: 5px 0 0 0; font-size: 12px;}
        .report-box {background: #a88867; padding: 25px; border-radius: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);}
        table {width: 100%; border-collapse: collapse; margin-top: 20px;}
        table th {background: #d2b48c; padding: 12px; text-align: center; border: 1px solid #000;}
        table td {padding: 12px; border: 1px solid #000; text-align: center;}
        .btn {padding: 6px 15px; border: none; border-radius: 8px; cursor: pointer; color: white;}
        .btn-export {background-color: #4CAF50;} .btn-delete {background-color: #c0392b;}

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
    <h3>Reports</h3>
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
    <h2>Reports Management</h2>
    <div class="summary">
        <div class="card"><h3>Total Transaction</h3><h1><?= $totalTransaction ?></h1><p>Orders</p></div>
        <div class="card"><h3>Total Sales</h3><h1><?= rupiah($totalSales) ?></h1><p>Revenue</p></div>
        <div class="card"><h3>Product Sold</h3><h1><?= $totalProduct ?></h1><p>Items</p></div>
    </div>

    <div class="report-box">
        <h3>Report History</h3>
        <table>
            <tr><th>No</th><th>Report Type</th><th>Generated Date</th><th>Action</th></tr>
            <?php if(count($reports) > 0): ?>
                <?php $no=1; foreach($reports as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['report_type']) ?></td>
                    <td><?= date('d F Y, H:i A', strtotime($row['created_at'])) ?></td>
                    <td><button class="btn btn-delete" onclick="alert('Fitur hapus belum diimplementasikan')">Delete</button></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">Belum ada riwayat laporan.</td></tr>
            <?php endif; ?>
        </table>
        <div style="margin-top:20px; text-align:right;">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="report_type" value="Daily Sales Report">
                <button type="submit" name="generate_report" class="btn btn-export">Export Daily Report</button>
            </form>
        </div>
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