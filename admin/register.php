<?php
session_start();
require_once '../config/db.php';

// --- CẤU HÌNH MÃ BÍ MẬT ---
// Chỉ ai biết mã này mới được tạo tài khoản Admin
$SECRET_KEY = "SCHEDIO2025";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $input_key = $_POST['secret_key'];

    if (empty($fullname) || empty($email) || empty($password) || empty($input_key)) {
        $error = "Vui lòng điền đầy đủ thông tin.";
    } elseif ($input_key !== $SECRET_KEY) {
        $error = "Mã bí mật không đúng! Bạn không có quyền tạo tài khoản Admin.";
    } else {
        // 1. Kiểm tra email đã tồn tại chưa
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email này đã được sử dụng.";
        } else {
            // 2. Tạo tài khoản mới
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'admin'; // Mặc định là Admin

            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = "Đăng ký thành công! Đang chuyển hướng...";
                echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
            } else {
                $error = "Lỗi hệ thống: " . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/admin-style.css" rel="stylesheet">
</head>

<body class="admin-auth-body">

    <div class="admin-auth-card">
        <div class="auth-brand">SCHEDIO</div>
        <p class="text-center text-muted mb-4">Tạo tài khoản quản trị mới</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 text-center small"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success py-2 text-center small"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">Họ và Tên</label>
                <input type="text" name="fullname" class="form-control py-2" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">Email</label>
                <input type="email" name="email" class="form-control py-2" placeholder="admin@schedio.vn" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">Mật khẩu</label>
                <input type="password" name="password" class="form-control py-2" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-danger">Mã bí mật (Secret Key)</label>
                <input type="password" name="secret_key" class="form-control py-2" placeholder="Nhập mã: SCHEDIO2025"
                    required>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary py-2 fw-bold"
                    style="background-color: #191970; border: none;">
                    Tạo tài khoản
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="login.php" class="text-decoration-none text-muted small">Đã có tài khoản? Đăng nhập</a>
        </div>
    </div>

</body>

</html>