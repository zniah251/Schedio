<?php
session_start();
require_once '../config/db.php';
// require_once '../config/functions.php'; // Nếu bạn đã có file này thì bỏ comment, nếu chưa thì code bên dưới vẫn chạy tốt

// 1. KIỂM TRA QUYỀN ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'staff') {
        echo "<script>alert('Bạn không có quyền truy cập trang này!'); window.location.href='dashboard.php';</script>";
    } else {
        header("Location: login.php");
    }
    exit;
}

$msg = "";
$error = "";

// 2. XỬ LÝ: THÊM NHÂN VIÊN MỚI
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $role = 'staff'; 

    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = "Email này đã được sử dụng!";
    } else {
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, phonenumber, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        if ($stmt) {
            $stmt->bind_param("sssss", $fullname, $email, $hashed_pass, $role, $phone);
            if ($stmt->execute()) {
                $msg = "Đã thêm nhân viên mới thành công!";
            } else {
                $error = "Lỗi thực thi: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Lỗi SQL: " . $conn->error;
        }
    }
}

// 3. XỬ LÝ: KHÓA / MỞ KHÓA
if (isset($_GET['action']) && isset($_GET['id'])) {
    $uid = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($uid == $_SESSION['user_id']) {
        $error = "Bạn không thể khóa tài khoản của chính mình.";
    } else {
        $new_status = ($action == 'lock') ? 0 : 1;
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $new_status, $uid);
            if ($stmt->execute()) {
                $msg = ($new_status == 0) ? "Đã khóa tài khoản." : "Đã mở khóa tài khoản.";
            }
            $stmt->close();
        } else {
            $error = "Lỗi SQL Update: " . $conn->error; 
        }
    }
}

// 4. XỬ LÝ BỘ LỌC
$where = "1=1";
$params = [];
$types = "";

if (!empty($_GET['keyword'])) {
    $keyword = "%" . trim($_GET['keyword']) . "%";
    $where .= " AND (fullname LIKE ? OR email LIKE ? OR phonenumber LIKE ?)";
    $params[] = $keyword; $params[] = $keyword; $params[] = $keyword;
    $types .= "sss";
}

if (!empty($_GET['role'])) {
    $where .= " AND role = ?";
    $params[] = $_GET['role'];
    $types .= "s";
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where .= " AND is_active = ?";
    $params[] = $_GET['status'];
    $types .= "i";
}

// 5. TRUY VẤN
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$sql_count = "SELECT COUNT(*) as total FROM users WHERE $where";
$stmt = $conn->prepare($sql_count);
if (!$stmt) { die("Lỗi Hệ thống (SQL Prepare): " . $conn->error); }

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$stmt->close();

$sql = "SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT $offset, $limit";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng - Schedio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">

    <style>
    .custom-tooltip-rank {
        font-size: 0.9rem;
    }

    .cursor-help {
        cursor: help;
    }
    </style>
</head>

<body>

    <div class="admin-wrapper">
        <?php include 'templates/sidebar.php'; ?>

        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-primary fw-bold mb-0">Quản lý Người dùng</h2>
                    <p class="text-muted small mb-0">Quản lý tài khoản Khách hàng và Nhân viên</p>
                </div>
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                    <i class="bi bi-person-plus-fill me-2"></i> Thêm nhân viên
                </button>
            </div>

            <?php if($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <form method="GET" class="filter-card">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="filter-label">Tìm kiếm</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="keyword" class="form-control form-control-search border-start-0"
                                value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>"
                                placeholder="Tên, Email hoặc SĐT...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">Vai trò</label>
                        <select class="form-select form-select-filter" name="role">
                            <option value="">Tất cả</option>
                            <option value="customer"
                                <?php if(isset($_GET['role']) && $_GET['role']=='customer') echo 'selected'; ?>>Khách
                                hàng</option>
                            <option value="staff"
                                <?php if(isset($_GET['role']) && $_GET['role']=='staff') echo 'selected'; ?>>Nhân viên
                            </option>
                            <option value="admin"
                                <?php if(isset($_GET['role']) && $_GET['role']=='admin') echo 'selected'; ?>>Quản trị
                                viên</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">Trạng thái</label>
                        <select class="form-select form-select-filter" name="status">
                            <option value="">Tất cả</option>
                            <option value="1"
                                <?php if(isset($_GET['status']) && $_GET['status']=='1') echo 'selected'; ?>>Hoạt động
                            </option>
                            <option value="0"
                                <?php if(isset($_GET['status']) && $_GET['status']=='0') echo 'selected'; ?>>Đã khóa
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100 fw-bold">Lọc</button>
                    </div>
                </div>
            </form>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Họ và Tên</th>
                                <th>Vai trò</th>
                                <th>Liên hệ</th>
                                <th>Ngày tham gia</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-end pe-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): ?>
                            <?php while($u = $users->fetch_assoc()): ?>
                            <?php 
                                    $avatar = !empty($u['avatar']) ? '../'.$u['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($u['fullname']).'&background=random&color=fff';
                                    
                                    $role_badge = match($u['role']) {
                                        'admin' => '<span class="badge bg-dark">Quản trị viên</span>',
                                        'staff' => '<span class="badge bg-primary bg-opacity-10 text-primary">Nhân viên</span>',
                                        default => '<span class="badge bg-light text-dark border">Khách hàng</span>',
                                    };

                                    $is_active = isset($u['is_active']) ? $u['is_active'] : 1; 

                                    // --- LOGIC TOOLTIP CHO KHÁCH HÀNG ---
                                    $tooltip_attr = "";
                                    if ($u['role'] == 'customer') {
                                        $rank = $u['rank_level'] ?? 'newbie';
                                        $spent = number_format($u['total_spent'] ?? 0, 0, ',', '.');
                                        
                                        // Định nghĩa màu sắc hiển thị cho đẹp
                                        $rank_colors = [
                                            'newbie' => '#6c757d', // Gray
                                            'underground' => '#0dcaf0', // Cyan
                                            'mainstream' => '#0d6efd', // Blue
                                            'superstar' => '#ffc107' // Yellow
                                        ];
                                        $rank_color = $rank_colors[$rank] ?? '#6c757d';
                                        
                                        // Nội dung HTML hiển thị trong Tooltip
                                        $tooltip_html = "<div class='text-start custom-tooltip-rank'>"
                                            . "Hạng: <b style='color:{$rank_color}'>" . ucfirst($rank) . "</b><br>"
                                            . "Chi tiêu: <b>{$spent} đ</b>"
                                            . "</div>";
                                        
                                        $tooltip_attr = "data-bs-toggle='tooltip' data-bs-html='true' title=\"$tooltip_html\" class='cursor-help'";
                                    }
                                    // ------------------------------------
                                    ?>
                            <tr class="<?php echo $is_active ? '' : 'table-light text-muted'; ?>">
                                <td class="ps-4">
                                    <div
                                        class="d-flex align-items-center <?php echo $is_active ? '' : 'opacity-75'; ?>">
                                        <img src="<?php echo $avatar; ?>"
                                            class="rounded-circle me-3 <?php echo $is_active ? '' : 'grayscale'; ?>"
                                            width="40" height="40" style="object-fit:cover;">
                                        <div>
                                            <div class="fw-bold text-dark" <?php echo $tooltip_attr; ?>>
                                                <?php echo htmlspecialchars($u['fullname']); ?>
                                                <?php if($u['role'] == 'customer' && isset($u['rank_level']) && $u['rank_level'] == 'superstar'): ?>
                                                <i class="bi bi-patch-check-fill text-warning ms-1"
                                                    title="Superstar"></i>
                                                <?php endif; ?>
                                            </div>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($u['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $role_badge; ?></td>
                                <td><small><?php echo htmlspecialchars($u['phonenumber'] ?? '--'); ?></small></td>
                                <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                <td class="text-center">
                                    <?php if($is_active): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3">Hoạt động</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3">Đã khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($u['role'] != 'admin'): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="dropdown"><i
                                                class="bi bi-three-dots-vertical"></i></button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item"
                                                    href="orders.php?keyword=<?php echo urlencode($u['email']); ?>"><i
                                                        class="bi bi-cart me-2"></i>Xem đơn hàng</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <?php if($is_active): ?>
                                            <li><a class="dropdown-item text-danger"
                                                    href="users.php?action=lock&id=<?php echo $u['id']; ?>"
                                                    onclick="return confirm('Bạn chắc chắn muốn KHÓA tài khoản này?');"><i
                                                        class="bi bi-lock me-2"></i>Khóa tài khoản</a></li>
                                            <?php else: ?>
                                            <li><a class="dropdown-item text-success"
                                                    href="users.php?action=unlock&id=<?php echo $u['id']; ?>"><i
                                                        class="bi bi-unlock me-2"></i>Mở khóa</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-light text-muted" disabled><i
                                            class="bi bi-shield-lock"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Không tìm thấy người dùng nào.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($total_pages > 1): ?>
                <div class="p-3 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted">Hiển thị <?php echo $users->num_rows; ?> / <?php echo $total_rows; ?> tài
                        khoản</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php for($i=1; $i<=$total_pages; $i++): ?>
                            <li class="page-item <?php echo ($i==$page) ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page'=>''])); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Thêm Nhân viên mới</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <input type="hidden" name="add_staff" value="1">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Họ và Tên <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email đăng nhập <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" minlength="6" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Số điện thoại</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Quyền hạn</label>
                            <input type="text" class="form-control bg-light" value="Staff (Nhân viên)" disabled>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-2">Tạo tài khoản</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    </script>
</body>

</html>