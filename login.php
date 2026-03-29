<?php
// login.php
session_start();
require_once 'config/db.php'; // Kết nối CSDL

$error = '';

// XỬ LÝ LOGIC ĐĂNG NHẬP
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập email và mật khẩu.";
    } else {
        // 1. Tìm user trong database theo email
        $stmt = $conn->prepare("SELECT id, fullname, password, role, avatar FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        // 2. Nếu tìm thấy email
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $fullname, $hashed_password, $role, $avatar);
            $stmt->fetch();

            // 3. Kiểm tra mật khẩu (So khớp hash)
            if (password_verify($password, $hashed_password)) {
                // Đăng nhập thành công -> Lưu Session
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $fullname;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_avatar'] = $avatar;

                // 4. Điều hướng dựa trên quyền (Role)
                if ($role == 'admin' || $role == 'staff') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $error = "Mật khẩu không đúng.";
            }
        } else {
            $error = "Email này chưa được đăng ký.";
        }
        $stmt->close();
    }
}

include 'templates/header.php';
?>

<div class="container auth-container">
    <div class="text-center">
        <h2 class="auth-heading">Đăng nhập</h2>
        <p class="auth-subheading">Đăng nhập vào tài khoản thành viên của bạn</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger text-center"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label-schedio">Email*</label>
            <input type="email" class="form-control form-control-schedio" id="email" name="email" required
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="mb-3">
            <label for="password" class="form-label-schedio">Nhập mật khẩu</label>
            <input type="password" class="form-control form-control-schedio" id="password" name="password" required>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="rememberMe" name="remember">
                <label class="form-check-label" for="rememberMe">
                    Ghi nhớ đăng nhập
                </label>
            </div>
            <a href="#" class="text-decoration-none small fw-bold">Quên mật khẩu?</a>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-schedio-primary">Đăng nhập</button>
        </div>

        <div class="text-center mb-3 position-relative">
            <hr class="hr-text" data-content="HOẶC">
        </div>

        <div class="d-grid mb-4">
            <a href="#" class="btn btn-google">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" width="20" class="me-2">
                Đăng nhập bằng Google
            </a>
        </div>

        <div class="text-center">
            <p>Bạn chưa có tài khoản? <a href="register.php" class="text-decoration-none fw-bold">Đăng ký</a></p>
        </div>
    </form>
</div>

<?php include 'templates/footer.php'; ?>