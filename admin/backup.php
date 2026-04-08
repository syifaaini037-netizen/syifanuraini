<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

 $current_page = basename($_SERVER['PHP_SELF']);

if(isset($_GET['backup'])){
    $folder = __DIR__ . "/../backup/";
    if(!is_dir($folder)){ mkdir($folder, 0777, true); }
    $filename = "backup_" . date("Y-m-d_H-i-s") . ".sql";
    $path = $folder . $filename;
    $command = "mysqldump --user=root --password= --host=localhost coffee_sipa > \"$path\"";
    system($command);
    header("Location: backup.php");
    exit;
}

 $backup_dir = __DIR__ . "/../backup/";
 $files = [];
if(is_dir($backup_dir)){
    $files = array_diff(scandir($backup_dir), ['.','..']);
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Backup Data</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
/* GLOBAL & LAYOUT */
html,body{margin:0;font-family:Poppins;background:#e7d8bd; height:100%; overflow-x: hidden;}
.wrapper{display:flex; min-height:100vh; position: relative;}

/* SIDEBAR */
.sidebar{
    position: fixed;
    top: 0; left: 0;
    width: 260px;
    height: 100vh;
    background:#6f4e37;
    display:flex;
    flex-direction:column;
    z-index: 1000;
    transition: transform 0.3s ease;
}
.sidebar a{
    display:flex; align-items:center; gap:12px; padding:16px 20px;
    color:white; text-decoration:none; border-bottom:1px solid rgba(255,255,255,0.08); transition:0.3s;
}
.sidebar a:hover{background:#5a3d2b; padding-left:25px;}
.sidebar a.active{background:#4e342e; font-weight:600; border-left: 5px solid #e7d8bd;}
.sidebar img{width:28px; height:28px; object-fit:contain;}

/* MAIN */
.main{flex:1; padding:30px; margin-left: 260px; transition: margin-left 0.3s ease; min-height: 100vh;}
.card{background:#bfa07c; padding:25px; border-radius:20px; box-shadow:0 8px 20px rgba(0,0,0,0.2);}
.generate-btn{background:#6f4e37; color:white; padding:10px 20px; border-radius:8px; text-decoration:none; display:inline-block;}
table{width:100%; border-collapse:collapse; margin-top:20px;}
th,td{padding:12px; text-align:center; border-bottom:1px solid #000;}
th{background:#d2b48c;}
.download-btn{background:#e6d5b8; padding:6px 15px; border-radius:20px; text-decoration:none; color:black;}

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
    table { display: block; overflow-x: auto; white-space: nowrap; }
}
</style>
</head>
<body>

<!-- MOBILE HEADER -->
<div class="mobile-header">
    <h3>Backup Data</h3>
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
<div class="card">
<h2>Backup Database</h2>
<a href="?backup=true" class="generate-btn">Generate Backup</a>

<table>
<tr><th>No</th><th>Backup Date</th><th>File Size</th><th>Action</th></tr>
<?php if(count($files)>0): ?>
<?php $no=1; foreach($files as $file): ?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= date("d M Y H:i", filemtime($backup_dir.$file)) ?></td>
    <td><?= round(filesize($backup_dir.$file)/1024/1024,2) ?> MB</td>
    <td><a href="../backup/<?= $file ?>" class="download-btn" download>Download</a></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="4">Belum ada file backup</td></tr>
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