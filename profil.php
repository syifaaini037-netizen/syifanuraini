<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

 $success_msg = '';
 $error_msg = '';

// Ambil data user
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

// Path foto
 $upload_dir = __DIR__ . '/uploads/profiles/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Cek apakah aksi upload foto atau update data
    $is_upload = isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($is_upload) {
        // ===== UPLOAD FOTO =====
        $file = $_FILES['profile_photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_msg = "Gagal upload foto.";
        } elseif (!in_array($file['type'], $allowed_types)) {
            $error_msg = "Format foto harus JPG, PNG, GIF, atau WebP.";
        } elseif ($file['size'] > $max_size) {
            $error_msg = "Ukuran foto maksimal 2MB.";
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $new_name;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Hapus foto lama (bukan default)
                if (!empty($user['profile_photo'])) {
                    $old_file = $upload_dir . $user['profile_photo'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }

                $stmt = $pdo->prepare("UPDATE users SET profile_photo=? WHERE id=?");
                $stmt->execute([$new_name, $_SESSION['user_id']]);

                $_SESSION['profile_photo'] = $new_name;

                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $success_msg = "Foto profil berhasil diperbarui!";
            } else {
                $error_msg = "Gagal menyimpan foto.";
            }
        }
    } else {
        // ===== UPDATE DATA =====
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name) || empty($email)) {
            $error_msg = "Nama dan email wajib diisi!";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, email=?, address=? WHERE id=?");
                $stmt->execute([$name, $phone, $email, $address, $_SESSION['user_id']]);
                $_SESSION['username'] = $name;

                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $success_msg = "Profil berhasil diperbarui!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error_msg = "Email sudah digunakan pengguna lain!";
                } else {
                    $error_msg = "Terjadi kesalahan, coba lagi.";
                }
            }
        }
    }
}

 $cart_count = array_sum($_SESSION['cart'] ?? []);

// Hitung order per status
 $stmt_counts = $pdo->prepare("
    SELECT status, COUNT(*) as cnt 
    FROM orders 
    WHERE user_id = ? 
    GROUP BY status
");
 $stmt_counts->execute([$_SESSION['user_id']]);
 $status_counts = [];
while ($row = $stmt_counts->fetch(PDO::FETCH_ASSOC)) {
    $status_counts[$row['status']] = (int)$row['cnt'];
}
 $cnt_paid = $status_counts['paid'] ?? 0;
 $cnt_delivering = $status_counts['delivering'] ?? 0;
 $cnt_completed = $status_counts['completed'] ?? 0;
 $total_orders = array_sum($status_counts);

// Foto path
 $photo_path = '';
if (!empty($user['profile_photo']) && file_exists($upload_dir . $user['profile_photo'])) {
    $photo_path = 'uploads/profiles/' . $user['profile_photo'];
}

// Format phone
 $phone_display = $user['phone'] ?? '';
if (!empty($phone_display) && strlen($phone_display) >= 10) {
    if (substr($phone_display, 0, 1) === '0') {
        $phone_display = '(+62) ' . substr($phone_display, 1, 3) . ' - ' . substr($phone_display, 4, 4) . ' - ' . substr($phone_display, 8);
    } elseif (substr($phone_display, 0, 2) === '62') {
        $phone_display = '(+62) ' . substr($phone_display, 2, 3) . ' - ' . substr($phone_display, 5, 4) . ' - ' . substr($phone_display, 9);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Coffie Kita</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: #f5ebe0;
            min-height: 100vh;
            padding-bottom: 30px;
        }

        /* ===== STATUS TABS ===== */
        .status-tabs {
            background: white;
            padding: 22px 10px 18px 10px;
            display: flex;
            justify-content: space-around;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 16px;
        }
        .status-tab {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            color: #8d6e63;
            cursor: pointer;
            transition: 0.2s;
            position: relative;
            padding: 4px 8px;
        }
        .status-tab:hover { color: #4a2c2a; }
        .status-tab-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: 0.2s;
        }
        .tab-unpaid .status-tab-icon { background: #fff3e0; color: #ef6c00; }
        .tab-packed .status-tab-icon { background: #e3f2fd; color: #1565c0; }
        .tab-shipped .status-tab-icon { background: #fff8e1; color: #f9a825; }
        .tab-done .status-tab-icon { background: #e8f5e9; color: #2e7d32; }
        .tab-rating .status-tab-icon { background: #fce4ec; color: #c62828; }

        .status-tab-label {
            font-size: 10px;
            font-weight: 500;
            text-align: center;
            line-height: 1.2;
        }
        .status-tab-count {
            position: absolute;
            top: -2px; right: 0;
            background: #e74c3c;
            color: white;
            font-size: 9px;
            font-weight: 700;
            width: 17px; height: 17px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ===== CONTENT ===== */
        .profile-content {
            max-width: 480px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .alert-msg {
            width: 100%;
            padding: 10px 14px;
            margin-bottom: 14px;
            border-radius: 10px;
            text-align: center;
            font-size: 12px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-danger { background: #fbe9e7; color: #c62828; border: 1px solid #ef9a9a; }

        /* ===== USER CARD ===== */
        .user-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 16px;
        }
        .user-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        /* AVATAR + UPLOAD */
        .avatar-wrapper {
            position: relative;
            flex-shrink: 0;
            cursor: pointer;
        }
        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            background: linear-gradient(135deg, #8d6e63, #5d4037);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
            overflow: hidden;
            border: 3px solid #f0ece6;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-badge {
            position: absolute;
            bottom: -4px;
            right: -4px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            border: 2.5px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            transition: 0.2s;
        }
        .avatar-wrapper:hover .avatar-badge {
            transform: scale(1.15);
        }
        .avatar-input {
            display: none;
        }

        .user-name-area {
            flex: 1;
            margin-left: 14px;
            min-width: 0;
        }
        .user-name {
            font-size: 17px;
            font-weight: 700;
            color: #3e2723;
            margin: 0 0 3px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-email {
            font-size: 12px;
            color: #999;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-edit-profile {
            background: none;
            border: 2px solid #e0d5ca;
            border-radius: 10px;
            padding: 8px 16px;
            color: #5d4037;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
        }
        .btn-edit-profile:hover {
            border-color: #5d4037;
            background: #fdf8f3;
        }

        /* INFO ROWS */
        .info-rows {
            display: flex;
            flex-direction: column;
        }
        .info-row {
            display: flex;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid #f5f0ea;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            margin-right: 14px;
        }
        .icon-phone { background: #e3f2fd; color: #1565c0; }
        .icon-email { background: #fff3e0; color: #ef6c00; }
        .icon-address { background: #e8f5e9; color: #2e7d32; }
        .icon-orders { background: #fce4ec; color: #c62828; }
        .icon-logout { background: #fbe9e7; color: #c62828; }

        .info-row-content { flex: 1; min-width: 0; }
        .info-row-label {
            font-size: 10px;
            color: #b0a090;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 3px;
        }
        .info-row-value {
            font-size: 13px;
            color: #3e2723;
            font-weight: 500;
            word-break: break-word;
        }
        .info-row-value.empty {
            color: #c0b0a0;
            font-style: italic;
        }
        .info-row-arrow {
            color: #ccc;
            font-size: 14px;
            margin-left: 8px;
            flex-shrink: 0;
            padding-top: 8px;
        }

        .info-row-link {
            display: flex;
            align-items: flex-start;
            text-decoration: none;
            cursor: pointer;
            width: 100%;
        }
        .info-row-link:hover .info-row-value { color: #e67e22; }
        .info-row-link:hover .info-row-arrow { color: #e67e22; }

        /* ===== EDIT MODAL ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: flex-end;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white;
            width: 100%;
            max-width: 480px;
            border-radius: 24px 24px 0 0;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        .modal-handle {
            width: 40px;
            height: 4px;
            background: #ddd;
            border-radius: 4px;
            margin: 12px auto 0 auto;
        }
        .modal-header {
            padding: 16px 24px 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-title {
            font-size: 17px;
            font-weight: 700;
            color: #3e2723;
            margin: 0;
        }
        .modal-close {
            width: 32px; height: 32px;
            border-radius: 50%;
            border: none;
            background: #f5f0ea;
            color: #999;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .modal-close:hover { background: #e8e0d6; color: #333; }
        .modal-body { padding: 20px 24px 30px 24px; }

        .modal-field { margin-bottom: 16px; }
        .modal-field label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #6d4c41;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .modal-field input,
        .modal-field textarea {
            width: 100%;
            padding: 11px 14px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            border: 2px solid #e8e0d6;
            border-radius: 12px;
            background: #faf8f5;
            color: #333;
            outline: none;
            transition: 0.2s;
            box-sizing: border-box;
        }
        .modal-field input:focus,
        .modal-field textarea:focus {
            border-color: #5d4037;
            background: #fff;
        }
        .modal-field textarea { min-height: 90px; resize: vertical; }

        .btn-save-modal {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 6px;
        }
        .btn-save-modal:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(230,126,34,0.3);
        }

        /* ===== PHOTO PREVIEW MODAL ===== */
        .photo-preview-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .photo-preview-overlay.active { display: flex; }
        .photo-preview-box {
            position: relative;
            max-width: 400px;
            width: 100%;
            animation: popIn 0.25s ease;
        }
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .photo-preview-img {
            width: 100%;
            border-radius: 20px;
            object-fit: cover;
            max-height: 70vh;
        }
        .photo-preview-close {
            position: absolute;
            top: -12px;
            right: -12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: white;
            color: #333;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: 0.2s;
        }
        .photo-preview-close:hover { transform: scale(1.1); }

        @media (min-width: 600px) {
            .modal-overlay { align-items: center; }
            .modal-box { border-radius: 24px; }
        }
    </style>
</head>

<body>

<?php include 'partials/navbar.php'; ?>

<!-- ===== STATUS TABS ===== -->
<div class="status-tabs">
    <a href="orders.php?tab=unpaid" class="status-tab tab-unpaid">
        <?php if ($cnt_paid > 0): ?><span class="status-tab-count"><?= $cnt_paid ?></span><?php endif; ?>
        <div class="status-tab-icon"><i class="fas fa-wallet"></i></div>
        <span class="status-tab-label">Belum<br>Dibayar</span>
    </a>
    <a href="orders.php?tab=packed" class="status-tab tab-packed">
        <div class="status-tab-icon"><i class="fas fa-box"></i></div>
        <span class="status-tab-label">Dikemas</span>
    </a>
    <a href="orders.php?tab=shipped" class="status-tab tab-shipped">
        <?php if ($cnt_delivering > 0): ?><span class="status-tab-count"><?= $cnt_delivering ?></span><?php endif; ?>
        <div class="status-tab-icon"><i class="fas fa-truck"></i></div>
        <span class="status-tab-label">Dikirim</span>
    </a>
    <a href="orders.php?tab=done" class="status-tab tab-done">
        <?php if ($cnt_completed > 0): ?><span class="status-tab-count"><?= $cnt_completed ?></span><?php endif; ?>
        <div class="status-tab-icon"><i class="fas fa-check-circle"></i></div>
        <span class="status-tab-label">Selesai</span>
    </a>
    <a href="orders.php?tab=rating" class="status-tab tab-rating">
        <div class="status-tab-icon"><i class="fas fa-star"></i></div>
        <span class="status-tab-label">Rating</span>
    </a>
</div>

<!-- ===== CONTENT ===== -->
<div class="profile-content">

    <?php if($success_msg): ?>
        <div class="alert-msg alert-success"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div class="alert-msg alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>

    <!-- USER CARD -->
    <div class="user-card">
        <div class="user-card-header">
            <!-- AVATAR + UPLOAD -->
            <form method="POST" action="" enctype="multipart/form-data" id="photoForm">
                <div class="avatar-wrapper" onclick="document.getElementById('photoInput').click()">
                    <div class="user-avatar" id="avatarDisplay" onclick="event.stopPropagation(); previewPhoto()">
                        <?php if (!empty($photo_path)): ?>
                            <img src="<?= $photo_path ?>" alt="Profile" id="avatarImg">
                        <?php else: ?>
                            <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-badge">
                        <i class="fas fa-camera"></i>
                    </div>
                    <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/gif,image/webp" class="avatar-input" id="photoInput" onchange="submitPhoto(this)">
                </div>
            </form>

            <div class="user-name-area">
                <h2 class="user-name"><?= htmlspecialchars($user['name']) ?></h2>
                <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <button class="btn-edit-profile" onclick="openModal()">
                <i class="fas fa-pen"></i> Edit
            </button>
        </div>

        <div class="info-rows">
            <div class="info-row">
                <div class="info-row-icon icon-phone"><i class="fas fa-phone"></i></div>
                <div class="info-row-content">
                    <div class="info-row-label">No. Telepon</div>
                    <div class="info-row-value <?= empty($user['phone']) ? 'empty' : '' ?>">
                        <?= !empty($phone_display) ? $phone_display : 'Belum diisi' ?>
                    </div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-row-icon icon-email"><i class="fas fa-envelope"></i></div>
                <div class="info-row-content">
                    <div class="info-row-label">Email</div>
                    <div class="info-row-value"><?= htmlspecialchars($user['email']) ?></div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-row-icon icon-address"><i class="fas fa-map-marker-alt"></i></div>
                <div class="info-row-content">
                    <div class="info-row-label">Alamat Pengiriman</div>
                    <div class="info-row-value <?= empty($user['address']) ? 'empty' : '' ?>">
                        <?= !empty($user['address']) ? htmlspecialchars($user['address']) : 'Belum diisi' ?>
                    </div>
                </div>
            </div>

            <a href="orders.php" class="info-row-link info-row">
                <div class="info-row-icon icon-orders"><i class="fas fa-receipt"></i></div>
                <div class="info-row-content">
                    <div class="info-row-label">Pesanan Saya</div>
                    <div class="info-row-value"><?= $total_orders ?> pesanan</div>
                </div>
                <div class="info-row-arrow"><i class="fas fa-chevron-right"></i></div>
            </a>

            <a href="auth/logout.php" class="info-row-link info-row" style="margin-top:4px;">
                <div class="info-row-icon icon-logout"><i class="fas fa-sign-out-alt"></i></div>
                <div class="info-row-content">
                    <div class="info-row-label">Keluar</div>
                    <div class="info-row-value" style="color:#c62828;">Logout</div>
                </div>
                <div class="info-row-arrow"><i class="fas fa-chevron-right"></i></div>
            </a>
        </div>
    </div>

</div>

<!-- ===== EDIT MODAL ===== -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-handle"></div>
        <div class="modal-header">
            <h3 class="modal-title">Edit Profil</h3>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="editForm">
                <div class="modal-field">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="modal-field">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="modal-field">
                    <label>No. Telepon</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                </div>
                <div class="modal-field">
                    <label>Alamat Pengiriman</label>
                    <textarea name="address" placeholder="Masukkan alamat lengkap..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    <div style="font-size:10px;color:#b0a090;margin-top:4px;">* Akan otomatis terisi saat checkout</div>
                </div>
                <button type="submit" class="btn-save-modal">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

<!-- ===== PHOTO PREVIEW ===== -->
<div class="photo-preview-overlay" id="photoPreview" onclick="closePreview()">
    <div class="photo-preview-box">
        <button class="photo-preview-close" onclick="closePreview()"><i class="fas fa-times"></i></button>
        <img src="" class="photo-preview-img" id="previewImg" alt="Preview">
    </div>
</div>

<script>
// Upload foto langsung saat pilih file
function submitPhoto(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        var allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        var maxSize = 2 * 1024 * 1024;

        if (!allowed.includes(file.type)) {
            alert('Format foto harus JPG, PNG, GIF, atau WebP.');
            input.value = '';
            return;
        }
        if (file.size > maxSize) {
            alert('Ukuran foto maksimal 2MB.');
            input.value = '';
            return;
        }

        // Tampilkan preview singkat sebelum upload
        var reader = new FileReader();
        reader.onload = function(e) {
            var avatar = document.getElementById('avatarDisplay');
            avatar.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">';
        };
        reader.readAsDataURL(file);

        // Submit form
        document.getElementById('photoForm').submit();
    }
}

// Preview foto fullscreen (klik pada avatar, bukan badge kamera)
function previewPhoto() {
    <?php if (!empty($photo_path)): ?>
    document.getElementById('previewImg').src = '<?= $photo_path ?>';
    document.getElementById('photoPreview').classList.add('active');
    document.body.style.overflow = 'hidden';
    <?php endif; ?>
}

function closePreview() {
    document.getElementById('photoPreview').classList.remove('active');
    document.body.style.overflow = '';
}

// Edit modal
function openModal() {
    document.getElementById('editModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('editModal').classList.remove('active');
    document.body.style.overflow = '';
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closePreview();
    }
});
</script>

</body>
</html>