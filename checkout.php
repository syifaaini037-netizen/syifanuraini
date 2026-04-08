<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

// Ambil keys yang dipilih dari URL
 $selected_keys = isset($_GET['keys']) ? explode(',', $_GET['keys']) : [];

// Jika tidak ada keys, ambil semua cart
if (empty($selected_keys)) {
    $selected_keys = array_keys($_SESSION['cart']);
}

// Filter: pastikan keys ada di cart
 $selected_keys = array_filter($selected_keys, function($k) {
    return isset($_SESSION['cart'][$k]);
});
 $selected_keys = array_values($selected_keys);

if (empty($selected_keys)) {
    header("Location: cart.php");
    exit;
}

// Ambil data user (termasuk alamat tersimpan)
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Bangun data item yang dipilih
 $checkout_items = [];
 $grand_total = 0;

 $product_ids = [];
foreach ($selected_keys as $key) {
    $parts = explode('_', $key);
    $product_ids[] = (int)$parts[0];
}
 $unique_ids = array_values(array_unique($product_ids));

 $placeholders = implode(',', array_fill(0, count($unique_ids), '?'));
 $stmt = $pdo->prepare("
    SELECT p.id, p.name, p.image, ps.size, ps.price
    FROM products p
    JOIN product_sizes ps ON p.id = ps.product_id
    WHERE p.id IN ($placeholders)
");
 $stmt->execute($unique_ids);
 $all_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $lookup = [];
foreach ($all_rows as $r) {
    $lookup[$r['id'] . '_' . $r['size']] = $r;
}

foreach ($selected_keys as $key) {
    if (isset($lookup[$key]) && isset($_SESSION['cart'][$key])) {
        $item = $lookup[$key];
        $qty = $_SESSION['cart'][$key];
        $item['qty'] = $qty;
        $item['subtotal'] = $item['price'] * $qty;
        $item['cart_key'] = $key;
        $checkout_items[] = $item;
        $grand_total += $item['subtotal'];
    }
}

if (empty($checkout_items)) {
    header("Location: cart.php");
    exit;
}

// Generate Virtual Account Number (BCA Style)
 $virtual_account = '880088' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
 $va_display = substr($virtual_account, 0, 4) . ' ' . substr($virtual_account, 4, 4) . ' ' . substr($virtual_account, 8, 4) . ' ' . substr($virtual_account, 12, 4);

 $error_msg = '';

// Handle POST checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $va_hidden = $_POST['virtual_account'] ?? '';

    if (empty($name) || empty($phone) || empty($address)) {
        $error_msg = "Nama, telepon, dan alamat wajib diisi!";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Simpan order
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, status, address, phone, payment_method, virtual_account) 
                VALUES (?, ?, 'paid', ?, ?, ?, ?)
            ");
            $va_to_save = ($payment_method == 'transfer') ? $va_hidden : null;
            $stmt->execute([$_SESSION['user_id'], $grand_total, $address, $phone, $payment_method, $va_to_save]);
            $order_id = $pdo->lastInsertId();

            // 2. Simpan order items
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, size, quantity, price) 
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($checkout_items as $item) {
                $parts = explode('_', $item['cart_key']);
                $size = $parts[1] ?? 'R';
                $stmt->execute([$order_id, $item['id'], $size, $item['qty'], $item['price']]);
            }

            // 3. Hapus item yang di-checkout dari cart
            foreach ($selected_keys as $key) {
                unset($_SESSION['cart'][$key]);
            }

            // 4. Auto-save alamat ke profil user
            $stmt = $pdo->prepare("UPDATE users SET address=?, phone=? WHERE id=?");
            $stmt->execute([$address, $phone, $_SESSION['user_id']]);

            $pdo->commit();
            header("Location: orders.php?success=1");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Terjadi kesalahan saat memproses pesanan. Coba lagi.";
        }
    }
}

 $cart_count = array_sum($_SESSION['cart'] ?? []);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Coffie Kita</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #e8d4be;
            min-height: 100vh;
            padding-bottom: 30px;
        }

        .checkout-top {
            background: #4a2c2a;
            color: white;
            padding: 25px 20px;
            text-align: center;
        }
        .checkout-top h1 {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 3px 0;
        }
        .checkout-top p {
            font-size: 12px;
            opacity: 0.7;
            margin: 0;
        }

        .checkout-container {
            max-width: 900px;
            margin: -20px auto 0 auto;
            padding: 0 16px;
            position: relative;
            z-index: 10;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .card {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.07);
        }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: #4a2c2a;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .field {
            margin-bottom: 14px;
        }
        .field label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #6d4c41;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .field input,
        .field textarea {
            width: 100%;
            padding: 11px 13px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            border: 2px solid #e8e0d6;
            border-radius: 10px;
            background: #faf8f5;
            color: #333;
            outline: none;
            transition: 0.2s;
            box-sizing: border-box;
        }
        .field input:focus,
        .field textarea:focus {
            border-color: #4a2c2a;
            background: #fff;
        }
        .field textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* ORDER ITEMS */
        .checkout-item {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0ece6;
        }
        .checkout-item:last-child { border-bottom: none; }
        .checkout-item-img {
            width: 55px;
            height: 55px;
            border-radius: 10px;
            object-fit: cover;
            background: #f0ece6;
            flex-shrink: 0;
        }
        .checkout-item-info { flex: 1; min-width: 0; }
        .checkout-item-name {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin: 0 0 2px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .checkout-item-meta {
            font-size: 11px;
            color: #999;
            margin: 0;
        }
        .checkout-item-price {
            font-size: 13px;
            font-weight: 700;
            color: #4a2c2a;
            flex-shrink: 0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 2px solid #4a2c2a;
            margin-top: 8px;
        }
        .total-label {
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }
        .total-val {
            font-size: 20px;
            font-weight: 700;
            color: #4a2c2a;
        }

        /* PAYMENT */
        .payment-card {
            grid-column: 1 / -1;
        }

        .payment-options {
            display: flex;
            gap: 12px;
            margin-bottom: 0;
            flex-wrap: wrap;
        }
        .payment-option {
            flex: 1;
            min-width: 200px;
            border: 2px solid #e8e0d6;
            border-radius: 14px;
            padding: 16px;
            cursor: pointer;
            transition: 0.2s;
            position: relative;
        }
        .payment-option:hover {
            border-color: #C8AB8E;
        }
        .payment-option.selected {
            border-color: #4a2c2a;
            background: #fdf8f3;
        }
        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .payment-option .po-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }
        .po-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .po-icon.cod { background: #e8f5e9; }
        .po-icon.transfer { background: #e3f2fd; }
        .po-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        .po-desc {
            font-size: 11px;
            color: #999;
            margin: 0;
        }

        /* VA BOX */
        .va-box {
            display: none;
            margin-top: 16px;
            background: linear-gradient(135deg, #1565C0, #0d47a1);
            border-radius: 14px;
            padding: 20px;
            color: white;
        }
        .va-box.show { display: block; }
        .va-label {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 4px;
        }
        .va-bank {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .va-number {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 8px;
            font-family: 'Courier New', monospace;
        }
        .va-hint {
            font-size: 11px;
            opacity: 0.7;
        }
        .va-copy {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
            padding: 6px 16px;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            color: white;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
        }
        .va-copy:hover { background: rgba(255,255,255,0.3); }

        /* SUBMIT */
        .submit-section {
            grid-column: 1 / -1;
            margin-top: 4px;
        }

        .btn-checkout {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: 0.2s;
            letter-spacing: 0.5px;
        }
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(230,126,34,0.35);
        }

        .alert-msg {
            width: 100%;
            padding: 12px;
            margin-bottom: 16px;
            border-radius: 10px;
            text-align: center;
            font-size: 13px;
            font-weight: 500;
        }
        .alert-danger {
            background: #fbe9e7;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        @media (max-width: 700px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            .va-number { font-size: 18px; }
        }
    </style>
</head>

<body>

<?php include 'partials/navbar.php'; ?>

<div class="checkout-top">
    <h1>Order Confirmation</h1>
    <p>Periksa kembali pesanan kamu sebelum checkout</p>
</div>

<div class="checkout-container">
    <?php if($error_msg): ?>
        <div class="alert-msg alert-danger" style="margin-bottom:16px;"><?= $error_msg ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="checkout-grid">

            <!-- KIRI: INFO PENGIRIM -->
            <div class="card">
                <div class="card-title">📍 Info Pengirim</div>

                <div class="field">
                    <label>Nama Penerima</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user_data['name']) ?>" required>
                </div>

                <div class="field">
                    <label>No. Telepon</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx" required>
                </div>

                <div class="field">
                    <label>Alamat Pengiriman</label>
                    <textarea name="address" required placeholder="Masukkan alamat lengkap..."><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- KANAN: RINGKASAN PESANAN -->
            <div class="card">
                <div class="card-title">🛒 Ringkasan Pesanan</div>

                <?php foreach ($checkout_items as $item):
                    $imgPath = "uploads/" . $item['image'];
                    if (empty($item['image']) || !file_exists($imgPath)) {
                        $imgPath = "https://via.placeholder.com/55x55?text=Coffee";
                    }
                ?>
                <div class="checkout-item">
                    <img src="<?= $imgPath ?>" class="checkout-item-img" alt="">
                    <div class="checkout-item-info">
                        <p class="checkout-item-name"><?= htmlspecialchars($item['name']) ?></p>
                        <p class="checkout-item-meta">Size <?= $item['size'] ?> × <?= $item['qty'] ?></p>
                    </div>
                    <div class="checkout-item-price">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></div>
                </div>
                <?php endforeach; ?>

                <div class="total-row">
                    <span class="total-label">Total</span>
                    <span class="total-val">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                </div>
            </div>

            <!-- PAYMENT -->
            <div class="card payment-card">
                <div class="card-title">💳 Metode Pembayaran</div>

                <div class="payment-options">
                    <label class="payment-option selected" id="opt-cod" onclick="selectPayment('cod')">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <div class="po-header">
                            <div class="po-icon cod">💵</div>
                            <div>
                                <div class="po-title">Cash On Delivery</div>
                                <p class="po-desc">Bayar saat pesanan sampai</p>
                            </div>
                        </div>
                    </label>

                    <label class="payment-option" id="opt-transfer" onclick="selectPayment('transfer')">
                        <input type="radio" name="payment_method" value="transfer">
                        <div class="po-header">
                            <div class="po-icon transfer">🏦</div>
                            <div>
                                <div class="po-title">Transfer Bank BCA</div>
                                <p class="po-desc">Bayar via transfer bank</p>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- VIRTUAL ACCOUNT -->
                <div class="va-box" id="vaBox">
                    <div class="va-label">Nomor Virtual Account</div>
                    <div class="va-bank">Bank BCA</div>
                    <div class="va-number" id="vaNumber"><?= $va_display ?></div>
                    <div class="va-hint">Transfer sesuai nominal total. Verifikasi otomatis 1×24 jam.</div>
                    <button type="button" class="va-copy" onclick="copyVA()">📋 Salin Nomor</button>
                </div>
            </div>

            <!-- SUBMIT -->
            <div class="submit-section">
                <button type="submit" class="btn-checkout">CHECKOUT</button>
            </div>

        </div>

        <!-- Hidden VA -->
        <input type="hidden" name="virtual_account" value="<?= $virtual_account ?>">
    </form>
</div>

<script>
function selectPayment(method) {
    document.getElementById('opt-cod').classList.toggle('selected', method === 'cod');
    document.getElementById('opt-transfer').classList.toggle('selected', method === 'transfer');
    document.getElementById('vaBox').classList.toggle('show', method === 'transfer');

    document.querySelector('input[name="payment_method"][value="cod"]').checked = (method === 'cod');
    document.querySelector('input[name="payment_method"][value="transfer"]').checked = (method === 'transfer');
}

function copyVA() {
    const vaText = document.getElementById('vaNumber').innerText.replace(/\s/g, '');
    navigator.clipboard.writeText(vaText).then(() => {
        const btn = document.querySelector('.va-copy');
        btn.innerText = '✅ Tersalin!';
        setTimeout(() => { btn.innerText = '📋 Salin Nomor'; }, 2000);
    });
}
</script>

</body>
</html>