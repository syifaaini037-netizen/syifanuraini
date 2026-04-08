<?php
session_start();
require_once __DIR__ . '/../config/database.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

 $current_page = basename($_SERVER['PHP_SELF']);
 $message = "";
if(isset($_GET['status']) && $_GET['status'] == 'success') {
    $message = "Product added successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product - Sipa Coffee</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* GLOBAL RESET */
        html,body{margin:0;padding:0;height:100%;font-family:'Poppins',sans-serif;background:#e7d8bd; overflow-x: hidden;}
        
        /* WRAPPER & LAYOUT */
        .wrapper{display:flex;min-height:100vh; position: relative;}

        /* SIDEBAR (Fixed & Responsive) */
        .sidebar{
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background:#6f4e37;
            display:flex;
            flex-direction:column;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar a{
            display:flex;
            align-items:center;
            gap:12px;
            padding:16px 20px;
            color:white;
            text-decoration:none;
            border-bottom:1px solid rgba(255,255,255,0.08);
            transition:0.3s;
        }

        .sidebar a:hover{background:#5a3d2b;padding-left:25px;}
        .sidebar a.active{background:#4e342e;font-weight:600; border-left: 5px solid #e7d8bd;}
        .sidebar img{width:28px; height:28px; object-fit:contain;}

        /* MAIN CONTENT */
        .main{
            flex:1;
            padding:30px;
            margin-left: 260px; /* Space for sidebar desktop */
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }

        /* FORM STYLES */
        .form-container{background:#a88867;padding:40px;border-radius:25px;box-shadow:0 8px 15px rgba(0,0,0,0.2);max-width:800px;}
        .form-container h2{margin-bottom:30px;color:white;}
        .form-group{margin-bottom:20px;}
        label{display:block;margin-bottom:8px;color:white;font-weight:500;}
        input, textarea, select{width:100%;padding:12px;border:none;border-radius:12px;outline:none;font-size:14px; box-sizing: border-box;}
        .price-grid{display:grid;grid-template-columns:repeat(3, 1fr);gap:20px;}
        .btn-group{margin-top:25px;display:flex;gap:15px;}
        .btn{padding:10px 25px;border:none;border-radius:10px;cursor:pointer;font-weight:500;text-decoration:none;text-align:center;color:white;}
        .btn-save{background:#4CAF50;}
        .btn-cancel{background:#c0392b;}
        .alert-success{background:#2e8b57;color:white;padding:15px;border-radius:10px;margin-bottom:20px;}

        /* MOBILE HEADER & OVERLAY */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 60px;
            background: #6f4e37;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 999;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .mobile-header h3 { color: white; margin: 0; font-size: 18px; }
        .toggle-btn {
            background: none; border: none; color: white; cursor: pointer; font-size: 24px;
        }
        .overlay {
            display: none;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 998;
        }

        /* RESPONSIVE */
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
    <h3>Sipa Coffee Admin</h3>
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
</div>

<!-- OVERLAY -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="wrapper">
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
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
            <h2>Add New Product (Multi-Size)</h2>

            <?php if($message): ?>
                <div class="alert-success"><?= $message ?></div>
            <?php endif; ?>

            <form action="proses_add_product.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Product Name (Required)</label>
                    <input type="text" name="name" placeholder="e.g: Cappuccino" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Taste notes..."></textarea>
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*">
                </div>

                <label>Price per Size (Enter 0 if not available)</label>
                <div class="price-grid">
                    <div class="form-group">
                        <label>Size R (Regular)</label>
                        <input type="number" name="price_r" placeholder="18000" required>
                    </div>
                    <div class="form-group">
                        <label>Size S (Small)</label>
                        <input type="number" name="price_s" placeholder="15000" required>
                    </div>
                    <div class="form-group">
                        <label>Size L (Large)</label>
                        <input type="number" name="price_l" placeholder="25000" required>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="is_best_seller" value="1" style="width:auto; transform:scale(1.2);">
                        Mark as Best Seller
                    </label>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-save">Save Product</button>
                    <a href="products.php" class="btn btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}
</script>

</body>
</html>