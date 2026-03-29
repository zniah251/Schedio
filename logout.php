<?php
// 1. Khởi động session để có thể thao tác với nó
session_start();

// 2. Xóa tất cả các biến trong session (user_id, user_role,...)
$_SESSION = array();

// 3. Hủy cookie của session (Để đảm bảo đăng xuất sạch sẽ hoàn toàn)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Hủy session trên server
session_destroy();

// 5. Chuyển hướng người dùng về trang chủ (hoặc trang login.php tùy bạn)
header("Location: index.php");
exit;
