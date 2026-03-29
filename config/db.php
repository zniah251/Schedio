<?php
/*
 * config/db.php
 * File kết nối cơ sở dữ liệu MySQL
 */
date_default_timezone_set('Asia/Ho_Chi_Minh');
// Cấu hình thông tin kết nối (Mặc định của XAMPP)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'booking');

// Tạo kết nối
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    // Nếu lỗi thì dừng chương trình và báo lỗi
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập bảng mã UTF-8 để hiển thị tiếng Việt không bị lỗi font
$conn->set_charset("utf8mb4");
