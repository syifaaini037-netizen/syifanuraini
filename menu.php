<?php
session_start();
require_once __DIR__ . '/config/database.php';

 $is_logged_in = isset($_SESSION['user']);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ================= HANDLE CART =================
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['size'])) {
    if (!$is_logged_in) {
        header("Location: auth/login.php");
        exit;
    }
    $id = (int)$_GET['id'];
    $size = $_GET['size'];
    $qty = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
    $key = $id . '_' . $size;

    if ($_GET['action'] == 'add') {
        $_SESSION['cart'][$key] = ($_SESSION['cart'][$key] ?? 0) + $qty;
    }
    header("Location: menu.php");
    exit;
}

// ================= AMBIL DATA =================
 $stmt = $pdo->query("
    SELECT p.id, p.name, p.image, ps.size, ps.price
    FROM products p
    LEFT JOIN product_sizes ps ON p.id = ps.product_id
    ORDER BY p.id DESC
");
 $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $products = [];
foreach ($data as $row) {
    $id = $row['id'];
    if (!isset($products[$id])) {
        $products[$id] = [
            'id' => $id,
            'name' => $row['name'],
            'image' => $row['image'],
            'sizes' => []
        ];
    }
    if ($row['size']) {
        $products[$id]['sizes'][] = [
            'size' => $row['size'],
            'price' => (int)$row['price']
        ];
    }
}

 $cart_count = array_sum($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu - Coffie Kita</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: #e6d3b3;
    padding-bottom: 20px;
}

/* === PAGE TITLE === */
.page-title-bar {
    text-align: center;
    padding: 30px 20px 10px 20px;
}
.page-title-bar h1 {
    margin: 0;
    font-size: 28px;
    color: #4a2c2a;
    font-weight: 700;
}
.page-title-bar p {
    margin: 6px 0 0 0;
    font-size: 14px;
    color: #8b7355;
}

/* === GRID === */
.menu-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    padding: 25px 30px;
    max-width: 1200px;
    margin: 0 auto;
}

/* === CARD === */
.card {
    background: #c8ab8e;
    border-radius: 20px;
    padding: 18px 15px 15px 15px;
    text-align: center;
    position: relative;
    transition: 0.25s ease;
    cursor: default;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(74, 44, 42, 0.15);
}

.card-img-wrap {
    width: 110px;
    height: 110px;
    margin: 0 auto 12px auto;
    border-radius: 16px;
    overflow: hidden;
    background: #b89a78;
}
.card-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.card h3 {
    margin: 0 0 4px 0;
    font-size: 15px;
    font-weight: 600;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.card .card-price {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 600;
    color: rgba(255,255,255,0.9);
}

.btn-plus {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: #6b4f3b;
    color: white;
    border: none;
    border-radius: 12px;
    width: 38px;
    height: 38px;
    font-size: 22px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
    line-height: 1;
    padding: 0;
}
.btn-plus:hover {
    background: #4a2c2a;
    transform: scale(1.08);
}

/* ============================
   BOTTOM SHEET POPUP
   ============================ */
.sheet-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    z-index: 9999;
    justify-content: center;
    align-items: flex-end;
}
.sheet-overlay.active {
    display: flex;
}

.sheet-box {
    background: #fff;
    width: 100%;
    max-width: 500px;
    border-radius: 28px 28px 0 0;
    padding: 0 26px 30px 26px;
    animation: sheetUp 0.32s cubic-bezier(0.22, 1, 0.36, 1);
    box-shadow: 0 -12px 50px rgba(0, 0, 0, 0.2);
}
@keyframes sheetUp {
    from { transform: translateY(100%); }
    to   { transform: translateY(0); }
}

.sheet-handle {
    width: 42px;
    height: 5px;
    background: #e0e0e0;
    border-radius: 5px;
    margin: 12px auto 22px auto;
    cursor: pointer;
    transition: 0.2s;
}
.sheet-handle:hover { background: #ccc; }

.sheet-body {
    display: flex;
    gap: 22px;
    align-items: flex-start;
}

.sheet-img {
    width: 130px;
    height: 130px;
    border-radius: 20px;
    object-fit: cover;
    flex-shrink: 0;
    background: #f0f0f0;
}

.sheet-info {
    flex: 1;
    min-width: 0;
}

.sheet-name {
    font-size: 19px;
    font-weight: 700;
    color: #333;
    margin: 0 0 16px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Size Pills */
.sheet-sizes {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.sz-pill {
    padding: 8px 22px;
    border-radius: 22px;
    border: 2px solid #e0e0e0;
    background: #fff;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    color: #999;
    transition: 0.2s;
    font-family: 'Poppins', sans-serif;
}
.sz-pill:hover {
    border-color: #C8AB8E;
    color: #4a2c2a;
}
.sz-pill.picked {
    border-color: #e8863a;
    background: #e8863a;
    color: #fff;
}

/* Qty Row */
.sheet-qty {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 8px;
}
.sheet-qbtn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 2px solid #4a2c2a;
    background: #fff;
    color: #4a2c2a;
    font-size: 22px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
    font-family: 'Poppins', sans-serif;
    line-height: 1;
    padding: 0;
}
.sheet-qbtn:hover {
    background: #4a2c2a;
    color: #fff;
}
.sheet-qty-val {
    font-size: 20px;
    font-weight: 700;
    color: #333;
    min-width: 30px;
    text-align: center;
}

.sheet-price {
    font-size: 22px;
    font-weight: 700;
    color: #4a2c2a;
    margin: 10px 0 0 0;
}

/* Checkout Button */
.sheet-checkout {
    width: 100%;
    padding: 17px;
    border: none;
    border-radius: 16px;
    background: #e8863a;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 24px;
    transition: 0.2s;
    font-family: 'Poppins', sans-serif;
    letter-spacing: 0.5px;
}
.sheet-checkout:hover {
    background: #d4752e;
    transform: translateY(-1px);
}
.sheet-checkout:active {
    transform: scale(0.98);
}

/* ============================
   RESPONSIVE
   ============================ */
@media (max-width: 1024px) {
    .menu-grid {
        grid-template-columns: repeat(3, 1fr);
        padding: 20px;
    }
}
@media (max-width: 768px) {
    .menu-grid {
        grid-template-columns: repeat(2, 1fr);
        padding: 16px;
        gap: 14px;
    }
    .card-img-wrap {
        width: 90px;
        height: 90px;
    }
    .card h3 { font-size: 13px; }
    .card .card-price { font-size: 12px; }
}
@media (max-width: 400px) {
    .sheet-img { width: 100px; height: 100px; border-radius: 16px; }
    .sheet-name { font-size: 16px; }
    .sheet-price { font-size: 18px; }
    .sz-pill { padding: 6px 16px; font-size: 12px; }
    .sheet-checkout { font-size: 15px; padding: 15px; }
}
</style>
</head>

<body>

<?php include 'partials/navbar.php'; ?>

<div class="page-title-bar">
    <h1>Menu Kami</h1>
    <p>Pilih minuman favoritmu</p>
</div>

<div class="menu-grid">
<?php foreach ($products as $p):
    $imgPath = "uploads/" . $p['image'];
    if (empty($p['image']) || !file_exists($imgPath)) {
        $imgPath = "https://via.placeholder.com/110x110?text=Coffee";
    }
    $firstSize = !empty($p['sizes']) ? $p['sizes'][0] : ['size' => '-', 'price' => 0];
?>
    <div class="card">
        <div class="card-img-wrap">
            <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($p['name']) ?>">
        </div>
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p class="card-price">Rp <?= number_format($firstSize['price'], 0, ',', '.') ?> (<?= $firstSize['size'] ?>)</p>
        <button class="btn-plus" onclick="openSheet(<?= htmlspecialchars(json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS)) ?>)">+</button>
    </div>
<?php endforeach; ?>
</div>

<!-- ==================== BOTTOM SHEET POPUP ==================== -->
<div class="sheet-overlay" id="sheetOverlay">
    <div class="sheet-box" id="sheetBox">
        <div class="sheet-handle" onclick="closeSheet()"></div>
        <div class="sheet-body">
            <img id="sImg" src="" class="sheet-img" alt="">
            <div class="sheet-info">
                <h3 id="sName" class="sheet-name"></h3>
                <div id="sSizes" class="sheet-sizes"></div>
                <div class="sheet-qty">
                    <button class="sheet-qbtn" type="button" onclick="sheetQty(-1)">−</button>
                    <span id="sQty" class="sheet-qty-val">1</span>
                    <button class="sheet-qbtn" type="button" onclick="sheetQty(1)">+</button>
                </div>
                <p id="sPrice" class="sheet-price"></p>
            </div>
        </div>
        <button class="sheet-checkout" type="button" onclick="sheetAdd()">Checkout</button>
    </div>
</div>

<script>
var sheetProduct = null;
var sheetSize = null;
var sheetQtyVal = 1;
var isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;

function openSheet(product) {
    if (!isLoggedIn) {
        alert('Silakan login terlebih dahulu ☕');
        window.location.href = 'auth/login.php';
        return;
    }

    sheetProduct = product;
    sheetSize = product.sizes[0].size;
    sheetQtyVal = 1;

    var imgSrc = product.image ? ('uploads/' + product.image) : 'https://via.placeholder.com/130x130?text=Coffee';
    document.getElementById('sImg').src = imgSrc;
    document.getElementById('sName').textContent = product.name;

    var box = document.getElementById('sSizes');
    box.innerHTML = '';
    for (var i = 0; i < product.sizes.length; i++) {
        var s = product.sizes[i];
        var pill = document.createElement('div');
        pill.className = 'sz-pill' + (i === 0 ? ' picked' : '');
        pill.textContent = 'Size ' + s.size;
        pill.setAttribute('data-size', s.size);
        pill.setAttribute('data-price', s.price);

        pill.onclick = (function(el, sz) {
            return function() {
                sheetSize = sz;
                var all = el.parentElement.querySelectorAll('.sz-pill');
                for (var k = 0; k < all.length; k++) all[k].classList.remove('picked');
                el.classList.add('picked');
                refreshSheet();
            };
        })(pill, s.size);

        box.appendChild(pill);
    }

    refreshSheet();
    document.getElementById('sheetOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function refreshSheet() {
    var unitPrice = 0;
    for (var i = 0; i < sheetProduct.sizes.length; i++) {
        if (sheetProduct.sizes[i].size === sheetSize) {
            unitPrice = sheetProduct.sizes[i].price;
            break;
        }
    }
    var total = unitPrice * sheetQtyVal;
    document.getElementById('sQty').textContent = sheetQtyVal;
    document.getElementById('sPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

function sheetQty(d) {
    sheetQtyVal += d;
    if (sheetQtyVal < 1) sheetQtyVal = 1;
    refreshSheet();
}

function closeSheet() {
    document.getElementById('sheetOverlay').classList.remove('active');
    document.body.style.overflow = '';
}

function sheetAdd() {
    window.location.href = 'menu.php?action=add&id=' + sheetProduct.id + '&size=' + sheetSize + '&qty=' + sheetQtyVal;
}

document.getElementById('sheetOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeSheet();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSheet();
});
</script>

</body>
</html>