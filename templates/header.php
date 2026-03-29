<?php
// templates/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kết nối DB nếu chưa có
if (!isset($conn)) {
    $db_path = __DIR__ . '/../config/db.php';
    if (file_exists($db_path)) {
        require_once $db_path;
    }
}

$current_page = basename($_SERVER['SCRIPT_NAME']);
$base_url = '/Schedio';

// --- LOGIC LẤY THÔNG BÁO MỚI NHẤT (TOAST) ---
$toast_notif = null;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = $_SESSION['user_id'];
    $sql_toast = "SELECT * FROM notifications WHERE user_id = $uid AND is_read = 0 ORDER BY created_at DESC LIMIT 1";
    $res_toast = $conn->query($sql_toast);
    if ($res_toast && $res_toast->num_rows > 0) {
        $toast_notif = $res_toast->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedio - Dịch vụ Booking Bài đăng</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">

    <style>
        .user-avatar-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }

        .user-avatar-btn:hover .user-avatar-img {
            transform: scale(1.05);
            border-color: var(--schedio-primary, #fdd03b);
        }

        .btn-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body class="schedio-light-bg">

    <header class="py-3">
        <nav class="navbar navbar-expand-lg navbar-light bg-transparent">
            <div class="container">
                <a class="navbar-brand fw-bold" href="<?php echo $base_url; ?>/index.php">Schedio</a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-center" id="main-nav">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a
                                class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>"
                                href="<?php echo $base_url; ?>/index.php">TRANG CHỦ</a></li>
                        <li class="nav-item"><a
                                class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>"
                                href="<?php echo $base_url; ?>/about.php">GIỚI THIỆU</a></li>
                        <li class="nav-item"><a
                                class="nav-link <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>"
                                href="<?php echo $base_url; ?>/services.php">BẢNG GIÁ</a></li>
                        <li class="nav-item"><a
                                class="nav-link <?php echo ($current_page == 'clients.php') ? 'active' : ''; ?>"
                                href="<?php echo $base_url; ?>/clients.php">KHÁCH HÀNG TIÊU BIỂU</a></li>
                        <li class="nav-item"><a
                                class="nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>"
                                href="<?php echo $base_url; ?>/contact.php">LIÊN HỆ</a></li>
                    </ul>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $avatar_url = !empty($_SESSION['user_avatar']) ? $base_url . '/' . htmlspecialchars($_SESSION['user_avatar']) : 'https://images.icon-icons.com/1378/PNG/512/avatardefault_92824.png';
                    ?>
                    <div class="dropdown">
                        <a href="#" class="btn p-0 border-0 user-avatar-btn" data-bs-toggle="dropdown">
                            <img src="<?php echo $avatar_url; ?>" alt="Avatar" class="user-avatar-img">
                            <?php if ($toast_notif): ?>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                    <span class="visually-hidden">New alerts</span>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3">
                            <li>
                                <h6 class="dropdown-header">Xin chào,
                                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Khách'); ?></h6>
                            </li>
                            <li><a class="dropdown-item py-2"
                                    href="<?php echo $base_url; ?>/customer/account.php?tab=profile"><i
                                        class="bi bi-person me-2"></i> Hồ sơ cá nhân</a></li>
                            <li><a class="dropdown-item py-2"
                                    href="<?php echo $base_url; ?>/customer/account.php?tab=orders"><i
                                        class="bi bi-bag me-2"></i> Đơn mua</a></li>
                            <li>
                                <a class="dropdown-item py-2 d-flex justify-content-between align-items-center"
                                    href="<?php echo $base_url; ?>/customer/account.php?tab=notifications">
                                    <span><i class="bi bi-bell me-2"></i> Thông báo</span>
                                    <?php if ($toast_notif): ?><span
                                            class="badge bg-danger rounded-pill">New</span><?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?php echo $base_url; ?>/logout.php"><i
                                        class="bi bi-box-arrow-right me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>/login.php" class="btn btn-outline-dark btn-circle"
                        title="Đăng nhập"><i class="bi bi-person"></i></a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <?php if ($toast_notif): ?>
        <?php
        $bg_header = match ($toast_notif['type']) {
            'success' => 'bg-success',
            'danger' => 'bg-danger',
            'warning' => 'bg-warning text-dark',
            default => 'bg-primary'
        };
        $text_header = ($toast_notif['type'] == 'warning') ? 'text-dark' : 'text-white';
        $btn_close_class = ($toast_notif['type'] == 'warning') ? '' : 'btn-close-white';
        ?>

        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 10001;">
            <div id="latestToast" class="toast show border-0 shadow-lg" role="alert" aria-live="assertive"
                aria-atomic="true" data-bs-autohide="false">
                <div class="toast-header <?php echo "$bg_header $text_header"; ?>">
                    <i class="bi bi-bell-fill me-2"></i>
                    <strong class="me-auto">Thông báo mới</strong>
                    <small>Vừa xong</small>

                    <button type="button" class="btn-close <?php echo $btn_close_class; ?>"
                        onclick="closeToast(<?php echo $toast_notif['id']; ?>)"></button>
                </div>
                <div class="toast-body bg-white text-dark rounded-bottom">
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($toast_notif['title']); ?></h6>
                    <p class="mb-2 small text-secondary"><?php echo htmlspecialchars($toast_notif['message']); ?></p>

                    <?php if ($toast_notif['order_id']): ?>
                        <div class="mt-2 pt-2 border-top text-end">
                            <a href="<?php echo $base_url; ?>/customer/order_detail.php?id=<?php echo $toast_notif['order_id']; ?>"
                                onclick="closeToast(<?php echo $toast_notif['id']; ?>)"
                                class="btn btn-sm btn-outline-primary stretched-link">
                                Xem chi tiết
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            function closeToast(notifId) {
                // 1. Ẩn Toast ngay lập tức
                var toastEl = document.getElementById('latestToast');
                if (toastEl) {
                    toastEl.classList.remove('show');
                    toastEl.classList.add('hide');
                }

                // 2. Gửi AJAX đánh dấu đã đọc
                var formData = new FormData();
                formData.append('id', notifId);

                fetch('<?php echo $base_url; ?>/customer/mark_read.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        console.log('Đã đánh dấu đã đọc:', data);
                    })
                    .catch(error => {
                        console.error('Lỗi cập nhật trạng thái:', error);
                    });
            }
        </script>
    <?php endif; ?>

    <main>