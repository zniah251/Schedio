<?php
// register.php
session_start();
require_once 'config/db.php'; // Đảm bảo bạn đã có file kết nối CSDL

$error = '';
$success = '';

// XỬ LÝ LOGIC KHI NGƯỜI DÙNG BẤM NÚT ĐĂNG KÝ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Lấy dữ liệu từ form
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 2. Gộp họ và tên
    $fullname = $last_name . ' ' . $first_name;

    // 3. Kiểm tra dữ liệu đầu vào
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Vui lòng điền đầy đủ thông tin.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Định dạng email không hợp lệ.";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu nhập lại không khớp.";
    } else {
        // 4. Kiểm tra xem email đã tồn tại trong CSDL chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email này đã được sử dụng. Vui lòng chọn email khác.";
        } else {
            // 5. Đăng ký thành công -> Lưu vào CSDL
            // Mã hóa mật khẩu trước khi lưu (Rất quan trọng!)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'customer'; // Mặc định là khách hàng

            // Câu lệnh INSERT
            $insert_stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $fullname, $email, $hashed_password, $role);

            if ($insert_stmt->execute()) {
                $success = "Đăng ký thành công! Đang chuyển hướng...";
                // Chuyển hướng sang trang login sau 2 giây
                header("refresh:2;url=login.php");
            } else {
                $error = "Có lỗi xảy ra: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}

include 'templates/header.php';
?>

<div class="container auth-container">
    <div class="text-center">
        <h2 class="auth-heading">Đăng ký</h2>
        <p class="auth-subheading">Tạo tài khoản để đăng ký các gói dịch vụ</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger text-center"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success text-center"><?php echo $success; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="firstName" class="form-label-schedio">Họ*</label>
                <input type="text" class="form-control form-control-schedio" id="firstName" name="first_name" required
                    value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="lastName" class="form-label-schedio">Tên*</label>
                <input type="text" class="form-control form-control-schedio" id="lastName" name="last_name" required
                    value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
            </div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label-schedio">Email*</label>
            <input type="email" class="form-control form-control-schedio" id="email" name="email" required
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="mb-3">
            <label for="password" class="form-label-schedio">Nhập mật khẩu</label>
            <input type="password" class="form-control form-control-schedio" id="password" name="password" required>
        </div>

        <div class="mb-3">
            <label for="confirmPassword" class="form-label-schedio">Nhập lại mật khẩu</label>
            <input type="password" class="form-control form-control-schedio" id="confirmPassword"
                name="confirm_password" required>
        </div>

        <div class="d-grid mb-3 mt-4">
            <button type="submit" class="btn btn-schedio-primary">Đăng ký</button>
        </div>

        <div class="text-center mb-3 position-relative">
            <hr class="hr-text" data-content="HOẶC">
        </div>

        <div class="d-grid mb-4">
            <a href="#" class="btn btn-google">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" width="20" class="me-2">
                Đăng ký bằng Google
            </a>
        </div>

        <div class="text-center">
            <p>Bạn đã có tài khoản? <a href="login.php" class="text-decoration-none fw-bold">Đăng nhập</a></p>
        </div>
    </form>
</div>

<?php include 'templates/footer.php'; ?>