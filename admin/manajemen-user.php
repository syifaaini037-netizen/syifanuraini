<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

 $current_page = basename($_SERVER['PHP_SELF']);

if(isset($_POST['tambah'])){
    $name = trim($_POST['name']); $phone = trim($_POST['phone']); $email = trim($_POST['email']);
    $role = $_POST['role']; $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, password, role) VALUES (?,?,?,?,?)");
    $stmt->execute([$name,$phone,$email,$password,$role]);
    header("Location: manajemen-user.php"); exit;
}

if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $check = $pdo->prepare("SELECT id, role FROM users WHERE id=?"); $check->execute([$id]); $user = $check->fetch(PDO::FETCH_ASSOC);
    if($user && $user['role'] != 'admin' && $user['id'] != $_SESSION['user_id']){
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?"); $stmt->execute([$id]);
    }
    header("Location: manajemen-user.php"); exit;
}

 $users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><title>Manajemen User</title>
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
.section{background:#a88867; padding:30px; border-radius:20px;}

/* TABLE */
table{width:100%; border-collapse:collapse; background:#c8ad8d;}
th,td{border:2px solid black; padding:12px; text-align:center;}
.role-badge{padding:6px 14px; border-radius:12px; color:white; font-size:13px;}
.admin{background:#c62828;} .petugas{background:#1565c0;} .user{background:#2e7d32;}
.btn{padding:6px 12px; border:none; border-radius:6px; cursor:pointer; color:white;}
.delete{background:#c62828;}
.add-btn{background:#4e342e; padding:10px 18px; border:none; border-radius:10px; color:white; cursor:pointer; margin-bottom:15px;}
form input, form select{padding:6px; margin:5px;}
.protected{color:gray; font-weight:bold;}

/* MOBILE HEADER & OVERLAY */
.mobile-header { display: none; position: fixed; top:0; left:0; right:0; height:60px; background:#6f4e37; align-items:center; justify-content:space-between; padding:0 20px; z-index: 999; color: white; }
.toggle-btn { background:none; border:none; color:white; cursor:pointer; font-size:24px; }
.overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:998; }

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); } .sidebar.show { transform: translateX(0); }
    .main { margin-left: 0; padding-top: 80px; }
    .mobile-header { display: flex; } .overlay.show { display: block; }
    table { display: block; overflow-x: auto; white-space: nowrap; }
    form { display: flex; flex-direction: column; gap: 10px; }
    .add-btn { width: 100%; }
}
</style>
</head>
<body>

<!-- MOBILE HEADER -->
<div class="mobile-header">
    <h3>Manajemen User</h3>
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
<h2>Manajemen User</h2>
<div class="section">
<form method="POST">
    <input type="text" name="name" placeholder="Nama Lengkap" required>
    <input type="text" name="phone" placeholder="No HP">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <select name="role">
        <option value="user">User</option><option value="petugas">Petugas</option><option value="admin">Admin</option>
    </select>
    <button type="submit" name="tambah" class="add-btn">Tambah User</button>
</form>
<br>
<table>
<tr><th>No</th><th>NAME</th><th>PHONE</th><th>EMAIL</th><th>ROLE</th><th>ACTION</th></tr>
<?php if(count($users) > 0): ?>
<?php $no=1; foreach($users as $row): ?>
<tr>
    <td><?= $no++ ?></td><td><?= htmlspecialchars($row['name']) ?></td><td><?= htmlspecialchars($row['phone']) ?></td><td><?= htmlspecialchars($row['email']) ?></td>
    <td><span class="role-badge <?= $row['role'] ?>"><?= ucfirst($row['role']) ?></span></td>
    <td>
        <?php if($row['role'] == 'admin' || $row['id'] == $_SESSION['user_id']): ?>
            <span class="protected">Protected</span>
        <?php else: ?>
            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus user?')" class="btn delete">Delete</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php else: ?><tr><td colspan="6">Belum ada data user</td></tr><?php endif; ?>
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