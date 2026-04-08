<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

 $cart_count = array_sum($_SESSION['cart']);

// Ambil semua order user
 $stmt = $pdo->prepare("
    SELECT o.* 
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.id DESC
");
 $stmt->execute([$_SESSION['user_id']]);
 $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil items untuk setiap order
 $order_items_map = [];
if (!empty($orders)) {
    $order_ids = array_column($orders, 'id');
    $ph = implode(',', array_fill(0, count($order_ids), '?'));
    
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id IN ($ph)
    ");
    $stmt->execute($order_ids);
    $all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_items as $item) {
        $order_items_map[$item['order_id']][] = $item;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Coffie Kita</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: #f4f4f4;
            min-height: 100vh;
            padding-bottom: 40px;
        }

        .orders-header {
            background: #4a2c2a;
            color: white;
            padding: 25px 20px;
            text-align: center;
        }
        .orders-header h1 {
            margin: 0 0 3px 0;
            font-size: 20px;
        }
        .orders-header p {
            margin: 0;
            opacity: 0.7;
            font-size: 12px;
        }

        .orders-container {
            max-width: 650px;
            margin: -20px auto 0 auto;
            padding: 0 16px;
            position: relative;
            z-index: 10;
        }

        .btn-back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #4a2c2a;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        .btn-back-link:hover { color: #e67e22; }

        /* SUCCESS BANNER */
        .success-banner {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            text-align: center;
            animation: fadeUp 0.4s ease;
        }
        .success-banner h2 {
            font-size: 18px;
            margin: 0 0 4px 0;
        }
        .success-banner p {
            font-size: 12px;
            opacity: 0.85;
            margin: 0;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ORDER CARD */
        .order-card {
            background: white;
            border-radius: 18px;
            margin-bottom: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            animation: fadeUp 0.3s ease;
        }

        .order-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 18px;
            border-bottom: 1px solid #f5f0ea;
        }
        .order-id {
            font-size: 14px;
            font-weight: 700;
            color: #4a2c2a;
        }
        .order-date {
            font-size: 11px;
            color: #999;
            margin-top: 2px;
        }

        .status-badge {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: white;
        }
        .status-paid { background: #3498db; }
        .status-delivering { background: #f39c12; }
        .status-completed { background: #2e8b57; }
        .status-cancelled { background: #c0392b; }

        .order-card-body {
            padding: 14px 18px;
        }

        .order-item {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #faf7f3;
        }
        .order-item:last-child { border-bottom: none; }
        .order-item-img {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            object-fit: cover;
            background: #f0ece6;
            flex-shrink: 0;
        }
        .order-item-info { flex: 1; min-width: 0; }
        .order-item-name {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin: 0 0 2px 0;
        }
        .order-item-meta {
            font-size: 11px;
            color: #999;
            margin: 0;
        }
        .order-item-price {
            font-size: 13px;
            font-weight: 700;
            color: #4a2c2a;
            flex-shrink: 0;
        }

        .order-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            background: #fdf8f3;
            border-top: 1px solid #f0ece6;
        }
        .order-total {
            font-size: 16px;
            font-weight: 700;
            color: #4a2c2a;
        }
        .order-payment {
            font-size: 11px;
            color: #999;
        }

        /* TOGGLE DETAIL */
        .detail-toggle {
            width: 100%;
            padding: 12px;
            background: none;
            border: none;
            border-top: 1px solid #f0ece6;
            color: #4a2c2a;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: 0.2s;
        }
        .detail-toggle:hover { background: #fdf8f3; }
        .detail-toggle .arrow { transition: 0.2s; display: inline-block; }
        .detail-toggle.open .arrow { transform: rotate(180deg); }

        .detail-panel {
            display: none;
            padding: 16px 18px;
            border-top: 1px solid #f0ece6;
            background: #fdfcfa;
        }
        .detail-panel.show { display: block; }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
            font-size: 12px;
        }
        .detail-label {
            width: 110px;
            flex-shrink: 0;
            color: #999;
            font-weight: 500;
        }
        .detail-value {
            flex: 1;
            color: #333;
            font-weight: 500;
        }

        .va-box-mini {
            background: linear-gradient(135deg, #1565C0, #0d47a1);
            color: white;
            border-radius: 10px;
            padding: 14px;
            margin-top: 12px;
        }
        .va-mini-label {
            font-size: 10px;
            opacity: 0.7;
        }
        .va-mini-bank {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .va-mini-number {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1.5px;
            font-family: 'Courier New', monospace;
        }

        /* EMPTY */
        .empty-orders {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 18px;
        }
        .empty-icon { font-size: 70px; margin-bottom: 12px; opacity: 0.4; }
        .empty-orders h2 {
            color: #999;
            font-size: 18px;
            margin: 0 0 6px 0;
        }
        .empty-orders p {
            color: #bbb;
            font-size: 13px;
            margin: 0 0 24px 0;
        }
        .btn-menu {
            display: inline-block;
            padding: 12px 30px;
            background: #4a2c2a;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s;
        }
        .btn-menu:hover { background: #3e2723; }

        @media (max-width: 480px) {
            .order-card-top { padding: 12px 14px; }
            .order-card-body { padding: 10px 14px; }
            .order-card-footer { padding: 12px 14px; }
        }
    </style>
</head>

<body>

<?php include 'partials/navbar.php'; ?>

<div class="orders-header">
    <h1>📦 Pesanan Saya</h1>
    <p>Pantau status pesanan kamu</p>
</div>

<div class="orders-container">
    <a href="index.php" class="btn-back-link">← Kembali ke Menu</a>

    <?php if (isset($_GET['success'])): ?>
    <div class="success-banner">
        <h2>✅ Pesanan Berhasil Dibuat!</h2>
        <p>Pesanan kamu sedang diproses. Silakan cek detail di bawah.</p>
    </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
    <div class="empty-orders">
        <div class="empty-icon">📦</div>
        <h2>Belum Ada Pesanan</h2>
        <p>Yuk, mulai pesan kopi favoritmu!</p>
        <a href="index.php" class="btn-menu">Pesan Sekarang</a>
    </div>
    <?php else: ?>
        <?php foreach ($orders as $order):
            $items = $order_items_map[$order['id']] ?? [];
            
            $statusClass = 'status-paid';
            $statusLabel = 'Menunggu';
            if ($order['status'] == 'paid') {
                $statusClass = 'status-paid';
                $statusLabel = 'Menunggu';
            } elseif ($order['status'] == 'delivering') {
                $statusClass = 'status-delivering';
                $statusLabel = 'Dalam Pengiriman';
            } elseif ($order['status'] == 'completed') {
                $statusClass = 'status-completed';
                $statusLabel = 'Selesai';
            } elseif ($order['status'] == 'cancelled') {
                $statusClass = 'status-cancelled';
                $statusLabel = 'Dibatalkan';
            }

            $paymentLabel = ($order['payment_method'] == 'transfer') ? 'Transfer BCA' : 'COD';

            $va_raw = $order['virtual_account'] ?? '';
            $va_fmt = '';
            if (!empty($va_raw) && strlen($va_raw) >= 14) {
                $va_fmt = substr($va_raw, 0, 4) . ' ' . substr($va_raw, 4, 4) . ' ' . substr($va_raw, 8, 4) . ' ' . substr($va_raw, 12, 4);
            }
        ?>
        <div class="order-card">
            <div class="order-card-top">
                <div>
                    <div class="order-id">#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></div>
                    <div class="order-date"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
                </div>
                <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
            </div>

            <div class="order-card-body">
                <?php foreach ($items as $item):
                    $imgPath = "uploads/" . $item['image'];
                    if (empty($item['image']) || !file_exists($imgPath)) {
                        $imgPath = "https://via.placeholder.com/48x48?text=Coffee";
                    }
                ?>
                <div class="order-item">
                    <img src="<?= $imgPath ?>" class="order-item-img" alt="">
                    <div class="order-item-info">
                        <p class="order-item-name"><?= htmlspecialchars($item['name']) ?></p>
                        <p class="order-item-meta">Size <?= $item['size'] ?> × <?= $item['quantity'] ?></p>
                    </div>
                    <div class="order-item-price">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="order-card-footer">
                <div>
                    <div class="order-total">Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></div>
                    <div class="order-payment">💳 <?= $paymentLabel ?></div>
                </div>
            </div>

            <button class="detail-toggle" onclick="toggleDetail(this)">
                Detail Pesanan <span class="arrow">▼</span>
            </button>

            <div class="detail-panel">
                <div class="detail-row">
                    <span class="detail-label">Penerima</span>
                    <span class="detail-value"><?= htmlspecialchars($order['phone'] ?? '-') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Alamat</span>
                    <span class="detail-value"><?= htmlspecialchars($order['address'] ?? '-') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Pembayaran</span>
                    <span class="detail-value"><?= $paymentLabel ?></span>
                </div>

                <?php if ($order['payment_method'] == 'transfer' && !empty($va_fmt)): ?>
                <div class="va-box-mini">
                    <div class="va-mini-label">Nomor Virtual Account</div>
                    <div class="va-mini-bank">Bank BCA</div>
                    <div class="va-mini-number"><?= $va_fmt ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleDetail(btn) {
    const panel = btn.nextElementSibling;
    panel.classList.toggle('show');
    btn.classList.toggle('open');
}
</script>

</body>
</html>