<?php
// 1. Start session & Koneksi Database
session_start();
require_once __DIR__ . '/config/database.php';

// ================= CEK LOGIN (BARU) =================
 $is_logged_in = isset($_SESSION['user']);

// ================= LOGIKA KERANJANG (UPDATED FOR SIZE) =================
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Aksi Tambah/Kurang
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['size'])) {
    
    // 🔐 SECURITY: WAJIB LOGIN UNTUK AKSI
    if (!$is_logged_in) {
        header("Location: auth/login.php");
        exit;
    }

    $id = (int)$_GET['id'];
    $size = $_GET['size']; // Ambil size dari parameter URL
    
    // Buat key unik: contoh "1_R" (ID 1, Size R)
    $cart_key = $id . '_' . $size;

    if ($_GET['action'] == 'add') {
        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]++;
        } else {
            $_SESSION['cart'][$cart_key] = 1;
        }
    } 
    elseif ($_GET['action'] == 'remove') {
        if (isset($_SESSION['cart'][$cart_key])) {
            if ($_SESSION['cart'][$cart_key] > 1) {
                $_SESSION['cart'][$cart_key]--;
            } else {
                unset($_SESSION['cart'][$cart_key]);
            }
        }
    }
    
    header("Location: index.php");
    exit;
}

// Hitung total item di keranjang (opsional, untuk badge navbar)
 $cart_count = array_sum($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Coffie Kita - Home</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<style>
/* ==================== BASE RESET ==================== */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: #f4f4f4;
    min-height: 100vh;
}

.container {
    max-width: 1200px;
    margin: auto;
    padding: 0 20px;
}

/* ==================== HERO ==================== */
.hero-welcome {
    background-color: #fff;
    text-align: center;
    padding: 60px 20px;
    margin-bottom: 40px;
}

.hero-welcome h1 {
    font-size: 2.5em;
    color: #4a2c2a;
    margin-bottom: 15px;
}

.hero-welcome p {
    font-size: 1em;
    color: #666;
    max-width: 500px;
    margin: 0 auto 30px auto;
}

/* ==================== MENU SECTION ==================== */
.menu-section {
    padding: 50px 20px 60px 20px;
    background-color: #b47963;
}

.section-title {
    text-align: center;
    font-size: 2em;
    color: #4a2c2a;
    margin-bottom: 40px;
    font-weight: 700;
}

/* ==================== SCROLLABLE LIST VIEW ==================== */
.menu-grid {
    display: flex;
    flex-direction: column;
    gap: 14px;
    max-width: 1000px;
    margin: 0 auto;
    max-height: 620px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 8px;
    scroll-behavior: smooth;
}

/* Custom Scrollbar */
.menu-grid::-webkit-scrollbar {
    width: 6px;
}
.menu-grid::-webkit-scrollbar-track {
    background: rgba(74, 44, 42, 0.15);
    border-radius: 10px;
}
.menu-grid::-webkit-scrollbar-thumb {
    background: rgba(74, 44, 42, 0.5);
    border-radius: 10px;
}
.menu-grid::-webkit-scrollbar-thumb:hover {
    background: rgba(74, 44, 42, 0.8);
}

/* ==================== PRODUCT CARD (LIST / HORIZONTAL) ==================== */
.menu-card {
    background-color: #C8AB8E;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.15);
    padding: 14px 18px;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 18px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
    flex-shrink: 0;
}

.menu-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

/* === IMAGE === */
.card-image-wrapper {
    width: 90px;
    height: 90px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
}

.card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* === CONTENT (MIDDLE) === */
.card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    gap: 4px;
}

.product-name {
    font-size: 16px;
    font-weight: 600;
    color: #ffffff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-desc {
    font-size: 12px;
    color: rgba(255,255,255,0.85);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-price {
    font-size: 17px;
    font-weight: 700;
    color: #ffffff;
}

/* === SIZE DROPDOWN === */
.size-select {
    padding: 5px 10px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.4);
    background: rgba(255,255,255,0.2);
    color: white;
    font-family: 'Poppins', sans-serif;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    outline: none;
    width: fit-content;
    transition: background 0.2s;
}

.size-select:hover {
    background: rgba(255,255,255,0.3);
}

.size-select option {
    background: #4a2c2a;
    color: white;
    padding: 6px;
}

/* === ACTIONS (RIGHT SIDE) === */
.product-actions {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
    min-width: 150px;
}

.qty-control {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    background: rgba(255,255,255,0.15);
    padding: 8px 14px;
    border-radius: 30px;
    border: 1px solid rgba(255,255,255,0.3);
    width: 100%;
}

.btn-qty {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #3e2723;
    color: #ffffff;
    border: none;
    cursor: pointer;
    font-weight: bold;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: background 0.2s, color 0.2s, transform 0.15s;
    line-height: 1;
}

.btn-qty:hover {
    background-color: #ffffff;
    color: #3e2723;
    transform: scale(1.12);
}

.qty-number {
    font-size: 17px;
    font-weight: 700;
    min-width: 28px;
    text-align: center;
    color: #ffffff;
    display: inline-block;
}

.action-buttons-row {
    display: flex;
    gap: 8px;
    width: 100%;
}

.btn-action-mini {
    flex: 1;
    font-size: 12px;
    padding: 9px 6px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    transition: background 0.2s, transform 0.15s;
}

.btn-cart-mini {
    background-color: #ffffff;
    color: #4a2c2a;
    border: 1px solid #ffffff;
}
.btn-cart-mini:hover {
    background-color: #f0e6dc;
    transform: translateY(-1px);
}

.btn-checkout-mini {
    background-color: #4a2c2a;
    color: #fff;
    border: 1px solid #4a2c2a;
}
.btn-checkout-mini:hover {
    background-color: #6d4c41;
    transform: translateY(-1px);
}

.icon-btn {
    height: 15px;
    width: auto;
    vertical-align: middle;
}

/* ==================== EMPTY STATE ==================== */
.empty-state {
    text-align: center;
    color: rgba(255,255,255,0.7);
    padding: 40px 20px;
    font-size: 15px;
}

/* ==================== MOBILE RESPONSIVE ==================== */
@media (max-width: 700px) {
    .hero-welcome h1 {
        font-size: 1.7em;
    }

    .menu-section {
        padding: 35px 14px 50px 14px;
    }

    .section-title {
        font-size: 1.5em;
        margin-bottom: 25px;
    }

    .menu-grid {
        max-height: 500px;
        gap: 12px;
        padding-right: 4px;
    }

    .menu-card {
        flex-wrap: wrap;
        padding: 12px 14px;
        gap: 12px;
    }

    .card-image-wrapper {
        width: 70px;
        height: 70px;
        border-radius: 10px;
    }

    .card-content {
        flex: 1 1 calc(100% - 88px);
        min-width: 0;
    }

    .product-name {
        font-size: 14px;
    }

    .product-desc {
        font-size: 11px;
    }

    .product-price {
        font-size: 15px;
    }

    .product-actions {
        flex-direction: row;
        min-width: unset;
        width: 100%;
        gap: 10px;
    }

    .qty-control {
        flex: 1;
        padding: 6px 10px;
        gap: 10px;
    }

    .action-buttons-row {
        flex: 1;
        gap: 6px;
    }

    .btn-action-mini {
        font-size: 11px;
        padding: 8px 4px;
    }

    .btn-qty {
        width: 28px;
        height: 28px;
        font-size: 16px;
    }

    .qty-number {
        font-size: 15px;
    }
}

@media (max-width: 420px) {
    .menu-card {
        padding: 10px 12px;
        gap: 10px;
    }

    .card-image-wrapper {
        width: 60px;
        height: 60px;
    }

    .product-actions {
        flex-wrap: wrap;
    }

    .action-buttons-row {
        flex: 1 1 100%;
    }
}
</style>
</head>

<body>

<?php include 'partials/navbar.php'; ?>

<div class="container">
    <div class="hero-welcome">
        <h1>Selamat Datang di Coffie Kita</h1>
        <p>Nikmati cita rasa kopi autentik. Pesan sekarang untuk pengalaman kopi terbaik.</p>
    </div>
</div>

<div class="menu-section">
    <h2 class="section-title">Best Seller Menu</h2>
    <div class="menu-grid">

        <?php
        // Query JOIN untuk ambil produk dan semua ukurannya
        $stmt = $pdo->query("
            SELECT p.id, p.name, p.description, p.image, ps.size, ps.price 
            FROM products p 
            LEFT JOIN product_sizes ps ON p.id = ps.product_id
            ORDER BY p.is_best_seller DESC, p.id DESC, FIELD(ps.size, 'S', 'R', 'L')
        ");
        $raw_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // === LOGIKA PENGGABUNGAN (ANTI DOUBLE) ===
        $products = [];
        foreach ($raw_products as $row) {
            $id = $row['id'];
            if (!isset($products[$id])) {
                $products[$id] = [
                    'id' => $id,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'image' => $row['image'],
                    'sizes' => []
                ];
            }
            if ($row['price'] !== null) {
                $products[$id]['sizes'][] = [
                    'size' => $row['size'],
                    'price' => $row['price']
                ];
            }
        }

        // === CEK APAKAH ADA PRODUK ===
        if (empty($products)): ?>
            <div class="empty-state">Belum ada menu tersedia saat ini.</div>
        <?php endif; ?>

        <?php
        // === TAMPILKAN PRODUK ===
        foreach($products as $row):
            $imagePath = "uploads/" . $row['image'];
            if(empty($row['image']) || !file_exists($imagePath)) {
                $imagePath = "https://via.placeholder.com/100x100?text=Coffee";
            }
            
            $defaultSize = !empty($row['sizes']) ? $row['sizes'][0]['size'] : 'R';
            $defaultPrice = !empty($row['sizes']) ? $row['sizes'][0]['price'] : 0;
            $defaultKey = $row['id'] . '_' . $defaultSize;
            $current_qty = isset($_SESSION['cart'][$defaultKey]) ? $_SESSION['cart'][$defaultKey] : 0;
            
            $link_remove = "?action=remove&id={$row['id']}&size={$defaultSize}"; 
            $link_add = "?action=add&id={$row['id']}&size={$defaultSize}";
        ?>
        
        <div class="menu-card">
            <!-- GAMBAR -->
            <div class="card-image-wrapper">
                <img src="<?= $imagePath ?>" class="card-image" alt="<?= htmlspecialchars($row['name']) ?>">
            </div>

            <!-- KONTEN TENGAH -->
            <div class="card-content">
                <div class="product-name"><?= htmlspecialchars($row['name']) ?></div>
                <div class="product-desc"><?= htmlspecialchars($row['description']) ?></div>
                <div class="product-price">Rp <span class="price-val"><?= number_format($defaultPrice, 0, ',', '.') ?></span></div>
                
                <?php if(!empty($row['sizes'])): ?>
                <select class="size-select" onchange="updateCardDisplay(this, <?= $row['id'] ?>)">
                    <?php foreach($row['sizes'] as $s): ?>
                        <option value="<?= $s['size'] ?>" data-price="<?= $s['price'] ?>">
                            Size <?= $s['size'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
            
            <!-- AKSI KANAN -->
            <div class="product-actions">
                <div class="qty-control">
                    <a href="<?= $is_logged_in ? $link_remove : 'auth/login.php' ?>" 
                       class="btn-qty btn-minus" data-key="<?= $defaultKey ?>"
                       <?= !$is_logged_in ? 'onclick="alert(\'Silakan login terlebih dahulu ☕\')"' : '' ?>>−</a>
                    
                    <span class="qty-number"><?= $current_qty ?></span>
                    
                    <a href="<?= $is_logged_in ? $link_add : 'auth/login.php' ?>" 
                       class="btn-qty btn-plus" data-key="<?= $defaultKey ?>"
                       <?= !$is_logged_in ? 'onclick="alert(\'Silakan login terlebih dahulu ☕\')"' : '' ?>>+</a>
                </div>

                <div class="action-buttons-row">
                    <a href="<?= $is_logged_in ? 'cart.php' : 'auth/login.php' ?>" 
                       class="btn-action-mini btn-cart-mini"
                       <?= !$is_logged_in ? 'onclick="alert(\'Silakan login terlebih dahulu ☕\')"' : '' ?>>
                        <img src="assets/img/keranjang.png" class="icon-btn"> 
                    </a>
                    
                    <a href="<?= $is_logged_in ? 'checkout.php' : 'auth/login.php' ?>" 
                       class="btn-action-mini btn-checkout-mini"
                       <?= !$is_logged_in ? 'onclick="alert(\'Silakan login terlebih dahulu ☕\')"' : '' ?>>
                        Checkout
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<?php include 'partials/footer.php'; ?>

<!-- ==================== JAVASCRIPT ==================== -->
<script>
    const cartData = <?php echo json_encode($_SESSION['cart']); ?>;
    const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

    function updateCardDisplay(selectElement, productId) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const size = selectedOption.value;
        const price = selectedOption.getAttribute('data-price');
        const uniqueKey = productId + '_' + size;

        // Update Harga
        const cardContent = selectElement.closest('.card-content');
        const priceDisplay = cardContent.querySelector('.price-val');
        priceDisplay.innerText = new Intl.NumberFormat('id-ID').format(price);

        // Update Tombol Link
        const cardActions = selectElement.closest('.menu-card').querySelector('.product-actions');
        const btnMinus = cardActions.querySelector('.btn-minus');
        const btnPlus = cardActions.querySelector('.btn-plus');
        const qtyDisplay = cardActions.querySelector('.qty-number');

        if (isLoggedIn) {
            const baseUrl = window.location.pathname;
            btnMinus.href = `${baseUrl}?action=remove&id=${productId}&size=${size}`;
            btnPlus.href = `${baseUrl}?action=add&id=${productId}&size=${size}`;
            btnMinus.removeAttribute('onclick');
            btnPlus.removeAttribute('onclick');
        } else {
            btnMinus.href = 'auth/login.php';
            btnPlus.href = 'auth/login.php';
            btnMinus.setAttribute('onclick', "alert('Silakan login terlebih dahulu ☕')");
            btnPlus.setAttribute('onclick', "alert('Silakan login terlebih dahulu ☕')");
        }

        // Update Qty
        const currentQty = cartData[uniqueKey] || 0;
        qtyDisplay.innerText = currentQty;
    }

    // Login guard untuk tombol Cart & Checkout
    document.addEventListener("DOMContentLoaded", function() {
        if (!isLoggedIn) {
            const buttons = document.querySelectorAll('.btn-checkout-mini, .btn-cart-mini');
            buttons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    alert("Silakan login terlebih dahulu untuk berbelanja ☕");
                });
            });
        }
    });
</script>

</body>
</html>