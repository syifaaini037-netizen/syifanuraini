<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

 $current_page = basename($_SERVER['PHP_SELF']);

if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    $allowed = ['paid', 'delivering', 'completed', 'cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);
    }
    header("Location: tracking.php");
    exit;
}

 $stmt = $pdo->query("SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");
 $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Pesanan - Sipa Coffee</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin: 0; padding: 0; height: 100%;
            font-family: 'Poppins', sans-serif;
            background: #e7d8bd; color: #333;
            overflow-x: hidden;
        }
        .wrapper { display: flex; min-height: 100vh; position: relative; }

        .sidebar {
            position: fixed; top: 0; left: 0;
            width: 260px; height: 100vh;
            background: #6f4e37;
            display: flex; flex-direction: column;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        .sidebar a {
            display: flex; align-items: center; gap: 12px;
            padding: 16px 20px; color: white;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            transition: 0.3s; font-size: 15px;
        }
        .sidebar a:hover { background: #5a3d2b; padding-left: 25px; }
        .sidebar a.active { background: #4e342e; font-weight: 600; border-left: 5px solid #e7d8bd; }
        .sidebar img { width: 28px; height: 28px; object-fit: contain; }

        .main {
            flex: 1; padding: 30px; margin-left: 260px;
            transition: margin-left 0.3s ease; min-height: 100vh;
        }
        .main h2 { margin: 0 0 25px 0; color: #4e342e; font-weight: 600; }

        .tracking-container {
            background: #a88867; padding: 30px; border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        table {
            width: 100%; border-collapse: collapse;
            background: #fdfdfd; border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th {
            background: #d2b48c; color: #3e2723;
            font-weight: 600; text-transform: uppercase;
            font-size: 13px; letter-spacing: 1px;
        }
        tr:hover { background-color: #fcfcfc; }

        .status-badge {
            padding: 6px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
            color: white; display: inline-block;
        }
        .status-paid       { background-color: #3498db; }
        .status-delivering { background-color: #f39c12; }
        .status-completed  { background-color: #2e8b57; }
        .status-cancelled  { background-color: #c0392b; }

        .action-btn {
            padding: 6px 12px; border-radius: 8px;
            text-decoration: none; font-size: 12px; font-weight: 500;
            margin-right: 5px; transition: 0.2s; display: inline-block;
        }
        .btn-view  { background: #6f4e37; color: white; }
        .btn-view:hover  { background: #5a3d2b; }
        .btn-track { background: #e67e22; color: white; }
        .btn-track:hover { background: #d35400; }

        .address-text {
            font-size: 12px; color: #555;
            max-width: 220px; 
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .payment-badge {
            font-size: 11px; color: #666;
            margin-top: 3px;
        }

        .mobile-header {
            display: none; position: fixed;
            top: 0; left: 0; right: 0; height: 60px;
            background: #6f4e37; align-items: center;
            justify-content: space-between; padding: 0 20px;
            z-index: 999; color: white;
        }
        .mobile-header h3 { margin: 0; font-size: 18px; }
        .toggle-btn {
            background: none; border: none;
            color: white; cursor: pointer; font-size: 24px;
        }
        .overlay {
            display: none; position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 998;
        }

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

<div class="mobile-header">
    <h3>Tracking</h3>
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
</div>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="wrapper">
    <div class="sidebar" id="sidebar">
        <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <img src="../assets/img/admin/dashboard.png" alt="Dashboard"> Dashboard
        </a>
        <a href="manajemen-user.php" class="<?= $current_page == 'manajemen-user.php' ? 'active' : '' ?>">
            <img src="../assets/img/admin/manajemen-user.png" alt="Users"> Manajemen User
        </a>
        <a href="transaction.php" class="<?= $current_page == 'transaction.php' ? 'active' : '' ?>">
            <img src="../assets/img/admin/transaction.png" alt="Transaction"> Transaction
        </a>
        <a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>">
            <img src="../assets/img/admin/products.png" alt="Products"> Products
        </a>
        <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">
            <img src="../assets/img/admin/reports.png" alt="Reports"> Reports
        </a>
        <a href="backup.php" class="<?= $current_page == 'backup.php' ? 'active' : '' ?>">
            <img src="../assets/img/admin/backup.png" alt="Backup"> Backup
        </a>
        <a href="restore.php" class="<?= $current_page == 'restore.php' ? 'active' : '' ?>">
            <img src="../assets/img/admin/restore.png" alt="Restore"> Restore
        </a>
        <a href="tracking.php" class="<?= $current_page == 'tracking.php' ? 'active' : '' ?>">
            <img src="../assets/img/admin/tracking.png" alt="Tracking"> Tracking
        </a>
        <a href="../auth/logout.php">
            <img src="../assets/img/admin/logout.png" alt="Logout"> Logout
        </a>
    </div>

    <div class="main">
        <h2>Tracking Pengiriman</h2>
        <div class="tracking-container">
            <?php if (count($orders) == 0): ?>
                <p style="text-align:center; color:white; padding:20px;">Belum ada data pesanan untuk dilacak.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Order ID</th>
                            <th>Pelanggan</th>
                            <th>Alamat Pengiriman</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($orders as $row): 
                            $payLabel = ($row['payment_method'] == 'transfer') ? 'Transfer BCA' : 'COD';
                            if ($row['payment_method'] == 'transfer' && !empty($row['virtual_account'])) {
                                $va = $row['virtual_account'];
                                $va_fmt = substr($va,0,4).' '.substr($va,4,4).' '.substr($va,8,4).' '.substr($va,12,4);
                                $payLabel .= '<br><span style="font-size:10px;color:#1565C0;font-family:monospace;">'.$va_fmt.'</span>';
                            }
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong>#<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($row['customer_name']) ?></strong><br>
                                <span style="font-size:11px;color:#999;"><?= htmlspecialchars($row['phone'] ?? '-') ?></span>
                            </td>
                            <td>
                                <div class="address-text" title="<?= htmlspecialchars($row['address'] ?? '-') ?>">
                                    📍 <?= htmlspecialchars($row['address'] ?? 'Tidak ada alamat') ?>
                                </div>
                            </td>
                            <td>
                                <span class="payment-badge"><?= $payLabel ?></span>
                            </td>
                            <td>
                                <?php
                                if ($row['status'] == 'paid') {
                                    $statusClass = 'status-paid';
                                    $statusLabel = 'Menunggu';
                                } elseif ($row['status'] == 'delivering') {
                                    $statusClass = 'status-delivering';
                                    $statusLabel = 'Dalam Pengiriman';
                                } elseif ($row['status'] == 'completed') {
                                    $statusClass = 'status-completed';
                                    $statusLabel = 'Selesai';
                                } elseif ($row['status'] == 'cancelled') {
                                    $statusClass = 'status-cancelled';
                                    $statusLabel = 'Dibatalkan';
                                } else {
                                    $statusClass = 'status-paid';
                                    $statusLabel = 'Pending';
                                }
                                ?>
                                <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'paid'): ?>
                                    <a href="?id=<?= $row['id'] ?>&status=delivering" class="action-btn btn-track" onclick="return confirm('Kirim pesanan ini?')">Kirim</a>
                                <?php elseif ($row['status'] == 'delivering'): ?>
                                    <a href="?id=<?= $row['id'] ?>&status=completed" class="action-btn btn-track" style="background-color:#2e8b57;" onclick="return confirm('Tandai pesanan sampai?')">Selesai</a>
                                <?php endif; ?>
                                <a href="transaction-detail.php?id=<?= $row['id'] ?>" class="action-btn btn-view">Detail</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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