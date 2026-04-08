<?php
session_start();
// UBAH 'koneksi.php' MENJADI 'database.php'
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Validasi Input
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price_r = $_POST['price_r'];
    $price_s = $_POST['price_s'];
    $price_l = $_POST['price_l'];
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;

    if (empty($name)) {
        die("Product name is required.");
    }

    // 2. Proses Upload Gambar
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExt, $allowed)) {
            $newName  = "product_" . time() . "." . $fileExt;
            $uploadDir = __DIR__ . "/../uploads/";
            
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            if (move_uploaded_file($fileTmp, $uploadDir . $newName)) {
                $imageName = $newName;
            }
        }
    }

    try {
        $pdo->beginTransaction();

        // 3. Insert ke tabel products
        $sqlProduct = "INSERT INTO products (name, description, image, is_best_seller) VALUES (?, ?, ?, ?)";
        $stmtProduct = $pdo->prepare($sqlProduct);
        $stmtProduct->execute([$name, $desc, $imageName, $is_best_seller]);

        $lastId = $pdo->lastInsertId();

        // 4. Insert ke tabel product_sizes
        $sqlSize = "INSERT INTO product_sizes (product_id, size, price) VALUES (?, ?, ?)";
        $stmtSize = $pdo->prepare($sqlSize);

        if($price_r > 0) $stmtSize->execute([$lastId, 'R', $price_r]);
        if($price_s > 0) $stmtSize->execute([$lastId, 'S', $price_s]);
        if($price_l > 0) $stmtSize->execute([$lastId, 'L', $price_l]);

        $pdo->commit();

        header("Location: add-product.php?status=success");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Failed to save product: " . $e->getMessage();
    }

} else {
    header("Location: add-product.php");
    exit;
}
?>