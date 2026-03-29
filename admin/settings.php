<?php
session_start();
require_once '../config/db.php';

// 1. KIỂM TRA QUYỀN ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    // Nếu là Staff, chuyển hướng về Dashboard
    if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'staff') {
        echo "<script>alert('Bạn không có quyền truy cập cài đặt hệ thống!'); window.location.href='dashboard.php';</script>";
    } else {
        header("Location: login.php");
    }
    exit;
}

$msg = "";
$error = "";

// 2. XỬ LÝ LƯU CẤU HÌNH
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Danh sách các key cần lưu (trừ logo xử lý riêng)
    $keys = [
        'site_name', 'site_description', 
        'contact_email', 'contact_hotline', 'contact_address',
        'bank_name', 'bank_account', 'bank_owner',
        'social_facebook', 'social_tiktok',
        'maintenance_mode'
    ];

    // Xử lý Checkbox Maintenance (nếu không check thì post không gửi lên, nên phải gán mặc định 0)
    $_POST['maintenance_mode'] = isset($_POST['maintenance_mode']) ? 1 : 0;

    // Lưu từng key vào DB
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $val = trim($_POST[$key]);
            // Dùng ON DUPLICATE KEY UPDATE để Insert hoặc Update
            $stmt = $conn->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->bind_param("sss", $key, $val, $val);
            $stmt->execute();
        }
    }

    // Xử lý Upload Logo
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
        $target_dir = "../uploads/settings/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES["site_logo"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($ext, $allowed)) {
            $new_name = "logo_" . time() . "." . $ext;
            $target_file = $target_dir . $new_name;
            
            if (move_uploaded_file($_FILES["site_logo"]["tmp_name"], $target_file)) {
                $path = "uploads/settings/" . $new_name;
                $conn->query("UPDATE settings SET value = '$path' WHERE key_name = 'site_logo'");
            }
        } else {
            $error = "Chỉ chấp nhận file ảnh (JPG, PNG, GIF).";
        }
    }

    if (empty($error)) {
        $msg = "Đã lưu cấu hình hệ thống thành công!";
    }
}

// 3. LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$settings = [];
$result = $conn->query("SELECT * FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['key_name']] = $row['value'];
}

// Helper function để lấy value an toàn
function get_setting($key, $data) {
    return isset($data[$key]) ? htmlspecialchars($data[$key]) : '';
}

// Logo mặc định nếu chưa có
$logo_src = !empty($settings['site_logo']) ? '../' . $settings['site_logo'] : '../assets/images/logo-placeholder.png';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Cài đặt hệ thống - Schedio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <style>
    .logo-preview-box {
        width: 150px;
        height: 150px;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        overflow: hidden;
        background: #f8f9fa;
    }

    .logo-preview-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .logo-preview-box:hover {
        border-color: var(--admin-primary);
        background: #e9ecef;
    }
    </style>
</head>

<body>

    <div class="admin-wrapper">
        <?php include 'templates/sidebar.php'; ?>

        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-primary fw-bold mb-0">Cài đặt hệ thống</h2>
                    <p class="text-muted small mb-0">Quản lý thông tin website và cấu hình thanh toán</p>
                </div>
                <button type="submit" form="settingsForm" class="btn btn-success px-4 shadow-sm">
                    <i class="bi bi-save me-2"></i> Lưu thay đổi
                </button>
            </div>

            <?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $msg; ?><button
                    type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="settings-card shadow-sm rounded overflow-hidden">
                <div class="row g-0">
                    <div class="col-md-3 border-end bg-white">
                        <div class="py-3">
                            <div class="nav flex-column nav-pills settings-nav" id="v-pills-tab" role="tablist">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#v-pills-general">
                                    <i class="bi bi-globe me-2"></i> Thông tin chung
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#v-pills-payment">
                                    <i class="bi bi-credit-card-2-front me-2"></i> Tài khoản thanh toán
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#v-pills-social">
                                    <i class="bi bi-share me-2"></i> Mạng xã hội
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#v-pills-advanced">
                                    <i class="bi bi-sliders me-2"></i> Cấu hình khác
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-9 bg-white">
                        <div class="p-4 p-lg-5">
                            <form id="settingsForm" method="POST" enctype="multipart/form-data">
                                <div class="tab-content" id="v-pills-tabContent">

                                    <div class="tab-pane fade show active" id="v-pills-general">
                                        <h5 class="fw-bold text-primary mb-4">Thông tin Website</h5>
                                        <div class="row mb-4">
                                            <div class="col-md-4 text-center">
                                                <label class="form-label fw-bold d-block text-start">Logo
                                                    Website</label>
                                                <div class="logo-preview-box mx-auto ms-md-0"
                                                    onclick="document.getElementById('siteLogo').click()">
                                                    <img src="<?php echo $logo_src; ?>" id="logoPreview" alt="Logo">
                                                </div>
                                                <input type="file" class="d-none" id="siteLogo" name="site_logo"
                                                    accept="image/*" onchange="previewImage(this)">
                                                <div class="form-text small text-start">Click ảnh để thay đổi. (PNG,
                                                    JPG)</div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Tên Website</label>
                                                    <input type="text" class="form-control" name="site_name"
                                                        value="<?php echo get_setting('site_name', $settings); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Mô tả ngắn (SEO)</label>
                                                    <textarea class="form-control" name="site_description"
                                                        rows="3"><?php echo get_setting('site_description', $settings); ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="text-muted my-4">

                                        <h5 class="fw-bold text-primary mb-4">Thông tin Liên hệ (Footer)</h5>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Email hỗ trợ</label>
                                                <input type="email" class="form-control" name="contact_email"
                                                    value="<?php echo get_setting('contact_email', $settings); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Hotline</label>
                                                <input type="text" class="form-control" name="contact_hotline"
                                                    value="<?php echo get_setting('contact_hotline', $settings); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-bold">Địa chỉ văn phòng</label>
                                                <input type="text" class="form-control" name="contact_address"
                                                    value="<?php echo get_setting('contact_address', $settings); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="v-pills-payment">
                                        <h5 class="fw-bold text-primary mb-4">Cấu hình Tài khoản Ngân hàng (VietQR)</h5>
                                        <div class="alert alert-info d-flex align-items-center">
                                            <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                                            <div>Thông tin này sẽ được dùng để tạo mã QR Code tự động cho khách hàng.
                                            </div>
                                        </div>

                                        <div class="card bg-light border-0 p-3 mb-3">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Ngân hàng (Mã)</label>
                                                <select class="form-select" name="bank_name">
                                                    <option value="MB"
                                                        <?php if(get_setting('bank_name', $settings) == 'MB') echo 'selected'; ?>>
                                                        MB Bank (Quân Đội)</option>
                                                    <option value="VCB"
                                                        <?php if(get_setting('bank_name', $settings) == 'VCB') echo 'selected'; ?>>
                                                        Vietcombank</option>
                                                    <option value="TCB"
                                                        <?php if(get_setting('bank_name', $settings) == 'TCB') echo 'selected'; ?>>
                                                        Techcombank</option>
                                                    <option value="ICB"
                                                        <?php if(get_setting('bank_name', $settings) == 'ICB') echo 'selected'; ?>>
                                                        VietinBank</option>
                                                    <option value="BIDV"
                                                        <?php if(get_setting('bank_name', $settings) == 'BIDV') echo 'selected'; ?>>
                                                        BIDV</option>
                                                </select>
                                                <div class="form-text">Chọn đúng mã ngân hàng để VietQR hoạt động.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Số tài khoản</label>
                                                <input type="text" class="form-control font-monospace"
                                                    name="bank_account"
                                                    value="<?php echo get_setting('bank_account', $settings); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Tên chủ tài khoản</label>
                                                <input type="text" class="form-control text-uppercase" name="bank_owner"
                                                    value="<?php echo get_setting('bank_owner', $settings); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="v-pills-social">
                                        <h5 class="fw-bold text-primary mb-4">Liên kết Mạng xã hội</h5>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text bg-white"><i
                                                    class="bi bi-facebook text-primary"></i></span>
                                            <input type="text" class="form-control" name="social_facebook"
                                                placeholder="Link Fanpage Facebook"
                                                value="<?php echo get_setting('social_facebook', $settings); ?>">
                                        </div>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text bg-white"><i
                                                    class="bi bi-tiktok text-dark"></i></span>
                                            <input type="text" class="form-control" name="social_tiktok"
                                                placeholder="Link kênh TikTok"
                                                value="<?php echo get_setting('social_tiktok', $settings); ?>">
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="v-pills-advanced">
                                        <h5 class="fw-bold text-primary mb-4">Cấu hình nâng cao</h5>
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                                            <div>
                                                <div class="fw-bold">Chế độ bảo trì</div>
                                                <small class="text-muted">Khi bật, chỉ Admin mới truy cập được
                                                    website.</small>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="maintenance_mode"
                                                    style="width: 3em; height: 1.5em;"
                                                    <?php echo (get_setting('maintenance_mode', $settings) == '1') ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Script xem trước ảnh khi chọn file
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logoPreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>

</body>

</html>