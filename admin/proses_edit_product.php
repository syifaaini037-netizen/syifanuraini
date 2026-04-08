<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id          = (int)$_POST['id'];
    $name        = trim($_POST['name']);
    $desc        = trim($_POST['description']);
    $price_r     = $_POST['price_r'];
    $price_s     = $_POST['price_s'];
    $price_l     = $_POST['price_l'];
    $is_best     = isset($_POST['is_best_seller']) ? 1 : 0;
    $old_image   = $_POST['old_image'];

    $imageName   = $old_image; // Default pakai gambar lama

    // 1. Logika Upload Gambar Baru (Jika ada)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExt, $allowed)) {
            $newName  = "product_" . time() . "." . $fileExt;
            $uploadDir = __DIR__ . "/../uploads/";

            // Upload gambar baru
            if (move_uploaded_file($fileTmp, $uploadDir . $newName)) {
                $imageName = $newName;
                
                // Hapus gambar lama jika ada
                if (!empty($old_image) && file_exists($uploadDir . $old_image)) {
                    unlink($uploadDir . $old_image);
                }
            }
        }
    }

    try {
        $pdo->beginTransaction();

        // 2. Update tabel products
        $sql_product = "UPDATE products SET name = ?, description = ?, image = ?, is_best_seller = ? WHERE id = ?";
        $stmt_product = $pdo->prepare($sql_product);
        $stmt_product->execute([$name, $desc, $imageName, $is_best, $id]);

        // 3. Update Harga Ukuran
        // Strategi: Hapus semua ukuran lama untuk produk ini, lalu insert baru.
        // Ini mencegah duplikasi dan handle jika suatu ukuran dihapus harganya.
        $stmt_delete_sizes = $pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?");
        $stmt_delete_sizes->execute([$id]);

        // Insert ukuran baru
        $sql_size = "INSERT INTO product_sizes (product_id, size, price) VALUES (?, ?, ?)";
        $stmt_size = $pdo->prepare($sql_size);

        if($price_r > 0) $stmt_size->execute([$id, 'R', $price_r]);
        if($price_s > 0) $stmt_size->execute([$id, 'S', $price_s]);
        if($price_l > 0) $stmt_size->execute([$id, 'L', $price_l]);

        $pdo->commit();

        // Redirect kembali ke halaman produk dengan pesan sukses (opsional)
        header("Location: products.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Gagal update: " . $e->getMessage();
    }

} else {
    header("Location: products.php");
    exit;
}
?>