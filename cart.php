<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle aksi qty & hapus
if (isset($_GET['action'])) {
    if (isset($_GET['key']) && in_array($_GET['action'], ['plus', 'minus', 'remove'])) {
        $key = $_GET['key'];
        if (isset($_SESSION['cart'][$key])) {
            if ($_GET['action'] == 'plus') {
                $_SESSION['cart'][$key]++;
            } elseif ($_GET['action'] == 'minus') {
                if ($_SESSION['cart'][$key] > 1) {
                    $_SESSION['cart'][$key]--;
                } else {
                    unset($_SESSION['cart'][$key]);
                }
            } elseif ($_GET['action'] == 'remove') {
                unset($_SESSION['cart'][$key]);
            }
        }
        header("Location: cart.php");
        exit;
    }
    if ($_GET['action'] == 'clear') {
        $_SESSION['cart'] = [];
        header("Location: cart.php");
        exit;
    }
}

// Bangun data cart item dari database
 $cart_items = [];
 $grand_total = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = [];
    foreach (array_keys($_SESSION['cart']) as $key) {
        $parts = explode('_', $key);
        $product_ids[] = (int)$parts[0];
    }
    $unique_ids = array_values(array_unique($product_ids));

    if (!empty($unique_ids)) {
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

        foreach ($_SESSION['cart'] as $key => $qty) {
            if (isset($lookup[$key])) {
                $item = $lookup[$key];
                $item['qty'] = $qty;
                $item['subtotal'] = $item['price'] * $qty;
                $item['cart_key'] = $key;
                $cart_items[] = $item;
                $grand_total += $item['subtotal'];
            }
        }
    }
}

 $cart_count = array_sum($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keranjang - Coffie Kita</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: #f4f4f4;
    padding-bottom: 140px;
}

/* === HEADER === */
.cart-header {
    background: #fff;
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid #eee;
}
.cart-header h1 {
    margin: 0;
    font-size: 20px;
    color: #4a2c2a;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.cart-badge {
    background: #4a2c2a;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 12px;
    border-radius: 12px;
}

/* === SELECT ALL BAR === */
.select-all-bar {
    max-width: 600px;
    margin: 16px auto 0 auto;
    padding: 0 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.select-all-bar label {
    font-size: 14px;
    font-weight: 500;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* === CUSTOM CHECKBOX === */
.cb-custom {
    appearance: none;
    -webkit-appearance: none;
    width: 22px;
    height: 22px;
    border: 2px solid #ccc;
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    flex-shrink: 0;
    transition: 0.2s;
    background: #fff;
}
.cb-custom:hover { border-color: #4a2c2a; }
.cb-custom:checked {
    background: #4a2c2a;
    border-color: #4a2c2a;
}
.cb-custom:checked::after {
    content: '';
    position: absolute;
    left: 6px;
    top: 2px;
    width: 6px;
    height: 11px;
    border: solid #fff;
    border-width: 0 2.5px 2.5px 0;
    transform: rotate(45deg);
}

/* === LIST === */
.cart-list {
    max-width: 600px;
    margin: 12px auto;
    padding: 0 16px;
}

/* === ITEM CARD === */
.cart-item {
    background: #fff;
    border-radius: 16px;
    padding: 16px 16px 16px 12px;
    margin-bottom: 12px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: relative;
    animation: fadeUp 0.3s ease;
    transition: 0.2s;
}
.cart-item.unselected {
    opacity: 0.45;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.cart-item-img {
    width: 85px;
    height: 85px;
    border-radius: 14px;
    object-fit: cover;
    flex-shrink: 0;
    background: #eee;
}

.cart-item-info {
    flex: 1;
    min-width: 0;
}

.cart-item-name {
    font-size: 15px;
    font-weight: 600;
    color: #333;
    margin: 0 0 6px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding-right: 28px;
}

.cart-item-size {
    display: inline-block;
    background: #C8AB8E;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 10px;
    margin-bottom: 12px;
}

.cart-item-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* === QTY CONTROL === */
.cart-qty {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cart-qty-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid #4a2c2a;
    background: #fff;
    color: #4a2c2a;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: 0.2s;
    line-height: 1;
}
.cart-qty-btn:hover {
    background: #4a2c2a;
    color: #fff;
}
.cart-qty-num {
    font-size: 16px;
    font-weight: 700;
    color: #333;
    min-width: 20px;
    text-align: center;
}

.cart-item-price {
    font-size: 16px;
    font-weight: 700;
    color: #4a2c2a;
}

/* === DELETE BTN === */
.cart-item-delete {
    position: absolute;
    top: 16px;
    right: 16px;
    background: none;
    border: none;
    color: #ccc;
    font-size: 22px;
    cursor: pointer;
    transition: 0.2s;
    padding: 0;
    line-height: 1;
}
.cart-item-delete:hover {
    color: #e74c3c;
    transform: scale(1.2);
}

/* === EMPTY STATE === */
.empty-cart {
    text-align: center;
    padding: 80px 20px;
}
.empty-icon {
    font-size: 90px;
    margin-bottom: 16px;
    opacity: 0.5;
}
.empty-cart h2 {
    color: #999;
    font-size: 22px;
    margin: 0 0 8px 0;
    font-weight: 600;
}
.empty-cart p {
    color: #bbb;
    font-size: 14px;
    margin: 0 0 30px 0;
}
.btn-back {
    display: inline-block;
    padding: 14px 36px;
    background: #4a2c2a;
    color: #fff;
    text-decoration: none;
    border-radius: 14px;
    font-weight: 600;
    font-size: 15px;
    transition: 0.2s;
}
.btn-back:hover {
    background: #3e2723;
    transform: translateY(-2px);
}

/* === CHECKOUT BAR (FIXED BOTTOM) === */
.checkout-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    padding: 14px 20px;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 500;
}
.checkout-info {}
.checkout-selected-label {
    font-size: 12px;
    color: #999;
    margin-bottom: 2px;
}
.checkout-total {
    font-size: 22px;
    font-weight: 700;
    color: #4a2c2a;
    margin: 0;
}
.btn-checkout {
    padding: 14px 36px;
    background: #4a2c2a;
    color: #fff;
    border: none;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    text-decoration: none;
    display: inline-block;
    font-family: 'Poppins', sans-serif;
}
.btn-checkout:hover {
    background: #3e2723;
    transform: translateY(-2px);
}
.btn-checkout.disabled {
    background: #ccc;
    cursor: not-allowed;
    pointer-events: none;
}

/* ============================
   POPUP CHECKOUT (CENTER)
   ============================ */
.pop-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    padding: 20px;
}
.pop-overlay.active {
    display: flex;
}
.pop-box {
    background: #fff;
    border-radius: 24px;
    width: 100%;
    max-width: 420px;
    overflow: hidden;
    animation: popIn 0.3s ease;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}
@keyframes popIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.pop-head {
    background: #4a2c2a;
    color: #fff;
    padding: 20px 24px;
    text-align: center;
}
.pop-head h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}
.pop-head p {
    margin: 6px 0 0 0;
    font-size: 13px;
    opacity: 0.8;
}

.pop-body {
    padding: 20px 24px;
    max-height: 320px;
    overflow-y: auto;
}
.pop-body::-webkit-scrollbar { width: 4px; }
.pop-body::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

.pop-item {
    display: flex;
    gap: 14px;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}
.pop-item:last-child { border-bottom: none; }
.pop-item-img {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
    background: #f0f0f0;
}
.pop-item-info { flex: 1; min-width: 0; }
.pop-item-name {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin: 0 0 3px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pop-item-meta {
    font-size: 11px;
    color: #999;
    margin: 0;
}
.pop-item-price {
    font-size: 14px;
    font-weight: 700;
    color: #4a2c2a;
    flex-shrink: 0;
}

.pop-footer {
    padding: 16px 24px 24px 24px;
    border-top: 2px solid #f5f5f5;
}
.pop-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}
.pop-total-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}
.pop-total-val {
    font-size: 22px;
    font-weight: 700;
    color: #4a2c2a;
}
.pop-actions {
    display: flex;
    gap: 10px;
}
.pop-btn-cancel {
    flex: 1;
    padding: 14px;
    border: 2px solid #e0e0e0;
    border-radius: 14px;
    background: #fff;
    color: #666;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    font-family: 'Poppins', sans-serif;
}
.pop-btn-cancel:hover {
    border-color: #ccc;
    background: #f9f9f9;
}
.pop-btn-go {
    flex: 2;
    padding: 14px;
    border: none;
    border-radius: 14px;
    background: #4a2c2a;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    font-family: 'Poppins', sans-serif;
}
.pop-btn-go:hover {
    background: #3e2723;
}

@media (max-width: 480px) {
    .btn-checkout { padding: 12px 22px; font-size: 14px; }
    .checkout-total { font-size: 18px; }
    .pop-total-val { font-size: 19px; }
}
</style>
</head>

<body>

<?php include 'partials/navbar.php'; ?>

<div class="cart-header">
    <h1>🛒 Keranjang <span class="cart-badge"><?= count($cart_items) ?> item</span></h1>
</div>

<?php if (!empty($cart_items)): ?>
<!-- SELECT ALL BAR -->
<div class="select-all-bar">
    <input type="checkbox" class="cb-custom" id="selectAll" checked onchange="toggleAll(this)">
    <label for="selectAll">Pilih Semua</label>
</div>
<?php endif; ?>

<div class="cart-list">
    <?php if (empty($cart_items)): ?>
    <div class="empty-cart">
        <div class="empty-icon">🧋</div>
        <h2>Keranjang Masih Kosong</h2>
        <p>Yuk, pilih menu favoritmu sekarang!</p>
        <a href="index.php" class="btn-back">Kembali ke Menu</a>
    </div>
    <?php else: ?>
        <?php foreach ($cart_items as $item):
            $imgPath = "uploads/" . $item['image'];
            if (empty($item['image']) || !file_exists($imgPath)) {
                $imgPath = "https://via.placeholder.com/85x85?text=Coffee";
            }
        ?>
        <div class="cart-item" id="item-<?= $item['cart_key'] ?>">
            <input type="checkbox" class="cb-custom item-cb" data-key="<?= $item['cart_key'] ?>" data-price="<?= $item['subtotal'] ?>" checked onchange="onCheckChange()">
            <img src="<?= $imgPath ?>" class="cart-item-img" alt="<?= htmlspecialchars($item['name']) ?>">
            <div class="cart-item-info">
                <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                <div class="cart-item-size">Size <?= htmlspecialchars($item['size']) ?></div>
                <div class="cart-item-bottom">
                    <div class="cart-qty">
                        <a href="?action=minus&key=<?= $item['cart_key'] ?>" class="cart-qty-btn">−</a>
                        <span class="cart-qty-num"><?= $item['qty'] ?></span>
                        <a href="?action=plus&key=<?= $item['cart_key'] ?>" class="cart-qty-btn">+</a>
                    </div>
                    <div class="cart-item-price">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></div>
                </div>
            </div>
            <button class="cart-item-delete" onclick="if(confirm('Hapus item ini?'))window.location='?action=remove&key=<?= $item['cart_key'] ?>'">&times;</button>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!empty($cart_items)): ?>
<div class="checkout-bar">
    <div class="checkout-info">
        <div class="checkout-selected-label" id="selectedLabel"><?= count($cart_items) ?> item dipilih</div>
        <p class="checkout-total" id="barTotal">Rp <?= number_format($grand_total, 0, ',', '.') ?></p>
    </div>
    <button class="btn-checkout" id="btnOpenPopup" onclick="openCheckoutPopup()">Checkout</button>
</div>
<?php endif; ?>

<!-- ==================== POPUP CHECKOUT ==================== -->
<div class="pop-overlay" id="popOverlay">
    <div class="pop-box">
        <div class="pop-head">
            <h2>Konfirmasi Pesanan</h2>
            <p>Periksa kembali pesanan kamu</p>
        </div>
        <div class="pop-body" id="popBody">
            <!-- Diisi JS -->
        </div>
        <div class="pop-footer">
            <div class="pop-total-row">
                <span class="pop-total-label">Total Pembayaran</span>
                <span class="pop-total-val" id="popTotal">Rp 0</span>
            </div>
            <div class="pop-actions">
                <button class="pop-btn-cancel" onclick="closeCheckoutPopup()">Batal</button>
                <button class="pop-btn-go" onclick="goCheckout()">Lanjut Checkout</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== DATA UNTUK JS ==================== -->
<?php if (!empty($cart_items)): ?>
<script>
var cartData = <?php echo json_encode($cart_items, JSON_HEX_TAG | JSON_HEX_APOS); ?>;
</script>
<?php endif; ?>

<script>
// ====== SELECT / DESELECT LOGIC ======
function toggleAll(el) {
    var cbs = document.querySelectorAll('.item-cb');
    for (var i = 0; i < cbs.length; i++) {
        cbs[i].checked = el.checked;
    }
    onCheckChange();
}

function onCheckChange() {
    var cbs = document.querySelectorAll('.item-cb');
    var total = 0;
    var count = 0;
    var allChecked = true;

    for (var i = 0; i < cbs.length; i++) {
        var card = document.getElementById('item-' + cbs[i].getAttribute('data-key'));
        if (cbs[i].checked) {
            total += parseInt(cbs[i].getAttribute('data-price'));
            count++;
            card.classList.remove('unselected');
        } else {
            allChecked = false;
            card.classList.add('unselected');
        }
    }

    document.getElementById('selectAll').checked = allChecked;
    document.getElementById('selectedLabel').textContent = count + ' item dipilih';
    document.getElementById('barTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');

    var btn = document.getElementById('btnOpenPopup');
    if (count === 0) {
        btn.classList.add('disabled');
    } else {
        btn.classList.remove('disabled');
    }
}

// ====== POPUP LOGIC ======
function getSelectedKeys() {
    var cbs = document.querySelectorAll('.item-cb:checked');
    var keys = [];
    for (var i = 0; i < cbs.length; i++) {
        keys.push(cbs[i].getAttribute('data-key'));
    }
    return keys;
}

function openCheckoutPopup() {
    var keys = getSelectedKeys();
    if (keys.length === 0) return;

    var body = document.getElementById('popBody');
    body.innerHTML = '';
    var grandTotal = 0;

    for (var i = 0; i < keys.length; i++) {
        var item = null;
        for (var j = 0; j < cartData.length; j++) {
            if (cartData[j].cart_key === keys[i]) {
                item = cartData[j];
                break;
            }
        }
        if (!item) continue;

        var imgSrc = item.image ? ('uploads/' + item.image) : 'https://via.placeholder.com/52x52?text=Coffee';
        grandTotal += item.subtotal;

        var div = document.createElement('div');
        div.className = 'pop-item';
        div.innerHTML =
            '<img src="' + imgSrc + '" class="pop-item-img" alt="">' +
            '<div class="pop-item-info">' +
                '<p class="pop-item-name">' + item.name + '</p>' +
                '<p class="pop-item-meta">Size ' + item.size + ' &bull; ' + item.qty + 'x</p>' +
            '</div>' +
            '<div class="pop-item-price">Rp ' + item.subtotal.toLocaleString('id-ID') + '</div>';
        body.appendChild(div);
    }

    document.getElementById('popTotal').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    document.getElementById('popOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCheckoutPopup() {
    document.getElementById('popOverlay').classList.remove('active');
    document.body.style.overflow = '';
}

function goCheckout() {
    var keys = getSelectedKeys().join(',');
    window.location.href = 'checkout.php?keys=' + encodeURIComponent(keys);
}

// Klik luar popup = tutup
document.getElementById('popOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeCheckoutPopup();
});

// ESC = tutup
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeCheckoutPopup();
});
</script>

</body>
</html>