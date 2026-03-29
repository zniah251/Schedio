<?php
session_start();
require_once '../config/db.php';

// Nếu đã đăng nhập là admin/staff rồi thì vào thẳng dashboard
if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'staff')) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập email và mật khẩu.";
    } else {
        // Chỉ tìm user có role là admin hoặc staff
        $sql = "SELECT id, fullname, password, role, avatar FROM users WHERE email = ? AND role IN ('admin', 'staff')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $fullname, $hashed_password, $role, $avatar);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // Đăng nhập thành công -> Lưu session
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $fullname;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_avatar'] = $avatar;

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Mật khẩu không đúng.";
            }
        } else {
            $error = "Tài khoản không tồn tại hoặc bạn không có quyền truy cập.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/admin-style.css" rel="stylesheet">
</head>

<body class="admin-auth-body">

    <div class="admin-auth-card">
        <div class="auth-brand">SCHEDIO</div>
        <p class="text-center text-muted mb-4">Hệ thống quản trị dành cho Admin</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 text-center small mb-3"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">Email</label>
                <input type="email" name="email" class="form-control py-2" placeholder="admin@schedio.vn" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary">Mật khẩu</label>
                <input type="password" name="password" class="form-control py-2" placeholder="••••••••" required>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary py-2 fw-bold"
                    style="background-color: #191970; border: none;">
                    Đăng nhập Dashboard
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="../index.php" class="text-decoration-none text-muted small">← Quay về trang chủ</a>
        </div>
    </div>

</body>

</html>