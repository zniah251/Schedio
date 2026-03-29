<?php
session_start();
require_once '../config/db.php';

// 1. KIỂM TRA QUYỀN ADMIN/STAFF
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    header("Location: login.php");
    exit;
}

// --- CODE MỚI: LOGIC ĐẾM ĐƠN VÀ THÔNG BÁO ---
// A. Đếm số đơn chờ xử lý (Pending) để hiển thị Badge
$sql_count_pending = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
$res_pending = $conn->query($sql_count_pending);
$pending_count = $res_pending->fetch_assoc()['total'];

// B. Kiểm tra đơn hàng mới nhất để hiện Toast (trong vòng 30 phút qua)
$show_toast = false;
$new_order_data = [];

$sql_recent = "SELECT id, title, created_at, u.fullname 
               FROM orders o 
               JOIN users u ON o.user_id = u.id
               WHERE o.status = 'pending' 
               AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) 
               ORDER BY o.created_at DESC LIMIT 1";
$res_recent = $conn->query($sql_recent);

if ($res_recent && $res_recent->num_rows > 0) {
    $recent_order = $res_recent->fetch_assoc();
    
    // Logic Session để không hiện lại thông báo cũ đã xem
    // Nếu ID đơn mới nhất khác ID đã lưu trong session -> Là đơn mới -> Hiện Toast
    if (!isset($_SESSION['last_toast_order_id']) || $_SESSION['last_toast_order_id'] != $recent_order['id']) {
        $show_toast = true;
        $new_order_data = $recent_order;
        // Cập nhật session
        $_SESSION['last_toast_order_id'] = $recent_order['id'];
    }
}
// ------------------------------------------------

// 2. XỬ LÝ BỘ LỌC & PHÂN TRANG (GIỮ NGUYÊN)
$where = "1=1"; 
$params = [];
$types = "";

if (!empty($_GET['keyword'])) {
    $keyword = "%" . trim($_GET['keyword']) . "%";
    $where .= " AND (u.fullname LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $params[] = $keyword; $params[] = $keyword; $params[] = $keyword;
    $types .= "sss";
}

if (!empty($_GET['status'])) {
    $where .= " AND o.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

if (!empty($_GET['date'])) {
    $where .= " AND DATE(o.created_at) = ?";
    $params[] = $_GET['date'];
    $types .= "s";
}

if (!empty($_GET['platform'])) {
    $where .= " AND pl.id = ?";
    $params[] = $_GET['platform'];
    $types .= "i";
}

if (!empty($_GET['package'])) {
    $where .= " AND p.id = ?";
    $params[] = $_GET['package'];
    $types .= "i";
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 3. TRUY VẤN DỮ LIỆU
$sql_count = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.id JOIN service_option so ON o.service_option_id = so.id JOIN package p ON so.package_id = p.id JOIN platform pl ON so.platform_id = pl.id WHERE $where";
$stmt = $conn->prepare($sql_count);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT o.id, o.created_at, o.status, u.fullname AS customer_name, p.name AS package_name, pl.name AS platform_name, (SELECT start_time FROM schedules WHERE post_id IN (SELECT id FROM post WHERE order_id = o.id) ORDER BY start_time ASC LIMIT 1) as schedule_time FROM orders o JOIN users u ON o.user_id = u.id JOIN service_option so ON o.service_option_id = so.id JOIN package p ON so.package_id = p.id JOIN platform pl ON so.platform_id = pl.id WHERE $where ORDER BY o.created_at DESC LIMIT $offset, $limit";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$platforms = $conn->query("SELECT * FROM platform");
$packages = $conn->query("SELECT * FROM package");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Schedio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>

<body>

    <div class="admin-wrapper">
        <?php include 'templates/sidebar.php'; ?>

        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary fw-bold mb-0">Quản lý đơn hàng</h2>
                <div class="d-flex align-items-center">
                    <span class="me-2 fw-bold small"><?php echo $_SESSION['user_name']; ?></span>
                    <i class="bi bi-person-circle fs-2 text-primary"></i>
                </div>
            </div>

            <form method="GET" class="filter-card">
                <div class="mb-3">
                    <input type="text" class="form-control form-control-search" name="keyword"
                        value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>"
                        placeholder="Tìm theo mã đơn (số ID), tên khách hàng, email...">
                </div>
                <div class="col-12 text-end mt-3">
                    <button type="submit" class="btn btn-filter px-4">Lọc</button>
                    <a href="orders.php" class="btn btn-reset">Xóa lọc</a>
                </div>
            </form>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" class="form-check-input"></th>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Gói dịch vụ</th>
                                <th>Ngày đăng ký</th>
                                <th>Lịch đăng</th>
                                <th>Kênh truyền thông</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><input type="checkbox" class="form-check-input"></td>
                                <td class="fw-bold text-dark">
                                    SCD-<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><span
                                        class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['package_name']); ?></span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <?php if (!empty($row['schedule_time'])) { echo date('d/m/Y H:i', strtotime($row['schedule_time'])); } else { echo '<span class="text-muted small">--</span>'; } ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['platform_name']); ?></td>
                                <td>
                                    <?php
                                            $stt_map = [
                                                'pending' => ['bg-warning text-dark', 'Chờ xử lý'],
                                                'design_review' => ['bg-info text-white', 'Duyệt Demo'],
                                                'waiting_payment' => ['bg-primary text-white', 'Chờ thanh toán'],
                                                'paid' => ['bg-success text-white', 'Đã thanh toán'],
                                                'in_progress' => ['bg-primary-subtle text-primary', 'Đang đăng'],
                                                'completed' => ['bg-success text-white', 'Hoàn thành'],
                                                'cancelled' => ['bg-danger text-white', 'Đã hủy']
                                            ];
                                            $s = $stt_map[$row['status']] ?? ['bg-secondary', $row['status']];
                                            ?>
                                    <span class="status-badge <?php echo $s[0]; ?>"><?php echo $s[1]; ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="order_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-view"><i
                                            class="bi bi-file-text"></i> Xem</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">Không tìm thấy đơn hàng nào.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_pages > 1): ?>
                <div class="p-3 d-flex justify-content-between align-items-center border-top">
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if($show_toast): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="newOrderToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-primary text-white">
                <i class="bi bi-bell-fill me-2"></i>
                <strong class="me-auto">Đơn hàng mới!</strong>
                <small>Vừa xong</small>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Khách hàng <strong><?php echo htmlspecialchars($new_order_data['fullname']); ?></strong> vừa đặt đơn
                hàng mới:
                <strong><?php echo htmlspecialchars($new_order_data['title']); ?></strong>
                <div class="mt-2 pt-2 border-top">
                    <a href="order_detail.php?id=<?php echo $new_order_data['id']; ?>"
                        class="btn btn-sm btn-primary w-100">Xử lý ngay</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>