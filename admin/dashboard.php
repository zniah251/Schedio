<?php
session_start();
require_once '../config/db.php';

// 1. KIỂM TRA QUYỀN ADMIN/STAFF
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    header("Location: login.php");
    exit;
}

// 2. TRUY VẤN THỐNG KÊ (KPIs)
// -- Tổng đơn tuần này
$sql_week = "SELECT COUNT(*) as count FROM orders WHERE YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)";
$week_orders = $conn->query($sql_week)->fetch_assoc()['count'];

// -- Đơn chờ xử lý (pending + design_review)
$sql_pending = "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'design_review')";
$pending_orders = $conn->query($sql_pending)->fetch_assoc()['count'];

// -- Đơn hoàn thành
$sql_completed = "SELECT COUNT(*) as count FROM orders WHERE status = 'completed'";
$completed_orders = $conn->query($sql_completed)->fetch_assoc()['count'];


// 3. LẤY DANH SÁCH ĐƠN HÀNG MỚI (3 đơn gần nhất)
$sql_recent = "
    SELECT o.id, o.created_at, o.status, u.fullname, p.name as package_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN service_option so ON o.service_option_id = so.id
    JOIN package p ON so.package_id = p.id
    ORDER BY o.created_at DESC LIMIT 3
";
$recent_orders = $conn->query($sql_recent);


// 4. LẤY LỊCH ĐĂNG BÀI HÔM NAY
$today = date('Y-m-d');
$sql_schedule = "
    SELECT s.start_time, o.title, pl.name as platform_name, pl.type
    FROM schedules s
    JOIN post p ON s.post_id = p.id
    JOIN orders o ON p.order_id = o.id
    JOIN platform pl ON s.platform_id = pl.id
    WHERE DATE(s.start_time) = '$today'
    ORDER BY s.start_time ASC
";
$today_schedules = $conn->query($sql_schedule);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Schedio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>

<body>

    <div class="admin-wrapper">

        <?php include 'templates/sidebar.php'; ?>

        <div class="admin-content">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div></div>
                <div class="d-flex align-items-center">
                    <div class="me-3 text-end">
                        <div class="fw-bold"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></div>
                        <div class="small text-muted text-uppercase">
                            <?php echo $_SESSION['user_role'] ?? 'Quản trị viên'; ?></div>
                    </div>
                    <i class="bi bi-person-circle fs-1 text-primary"></i>
                </div>
            </div>

            <h2 class="fw-bold text-dark mb-4">Bảng điều khiển</h2>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="stat-card">
                        <p class="stat-title">Tổng đơn hàng trong tuần</p>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="stat-value"><?php echo $week_orders; ?></div>
                            <span class="trend-badge trend-up">↗ Tuần này</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <p class="stat-title">Bài đăng chờ xử lý</p>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="stat-value"><?php echo $pending_orders; ?></div>
                            <span class="trend-badge trend-down text-warning border-warning">Cần duyệt</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <p class="stat-title">Bài đăng đã hoàn thành</p>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="stat-value"><?php echo $completed_orders; ?></div>
                            <span class="trend-badge trend-up text-success border-success">Tổng cộng</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-card mb-5">
                <div class="card-header-custom">
                    <h5 class="fw-bold text-dark-blue mb-0">Đơn hàng mới</h5>
                    <a href="orders.php" class="text-decoration-none small fst-italic">Xem tất cả</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Gói dịch vụ</th>
                                <th>Ngày đăng ký</th>
                                <th>Trạng thái</th>
                                <th>Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_orders->num_rows > 0): ?>
                            <?php while ($row = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold">SCD-<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($row['package_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <?php
                                            // Map trạng thái sang badge
                                            $status_badges = [
                                                'pending' => '<span class="status-badge status-warning">Chờ xử lý</span>',
                                                'design_review' => '<span class="status-badge status-info">Duyệt Demo</span>',
                                                'waiting_payment' => '<span class="status-badge status-warning">Chờ thanh toán</span>',
                                                'paid' => '<span class="status-badge status-success">Đã thanh toán</span>',
                                                'completed' => '<span class="status-badge status-success">Hoàn thành</span>',
                                                'cancelled' => '<span class="status-badge bg-danger text-white">Đã hủy</span>'
                                            ];
                                            echo $status_badges[$row['status']] ?? '<span class="badge bg-secondary">Unknown</span>';
                                            ?>
                                </td>
                                <td>
                                    <a href="order_detail.php?id=<?php echo $row['id']; ?>" class="btn-view">
                                        <i class="bi bi-file-text"></i> Xem
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Chưa có đơn hàng nào.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-card">
                <div class="card-header-custom">
                    <h5 class="fw-bold text-dark-blue mb-0">Lịch đăng bài hôm nay - <?php echo date('d/m/Y'); ?></h5>
                    <a href="calendar.php" class="text-decoration-none small fst-italic">Xem lịch tháng</a>
                </div>
                <div class="p-4">
                    <?php if ($today_schedules->num_rows > 0): ?>
                    <?php while ($sch = $today_schedules->fetch_assoc()): ?>
                    <div class="schedule-item mb-3">
                        <div class="d-flex align-items-center">
                            <div class="me-4 fw-bold text-primary" style="min-width: 80px;">
                                <?php echo date('H:i A', strtotime($sch['start_time'])); ?>
                            </div>
                            <div class="border-start border-2 border-secondary ps-3 ms-2">
                                <div class="fw-bold"><?php echo htmlspecialchars($sch['title']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($sch['platform_name']); ?></small>
                            </div>
                        </div>
                        <?php
                                // Badge cho Platform type
                                $pl_badge = ($sch['type'] == 'group') ? 'bg-warning text-dark' : 'bg-primary text-white';
                                ?>
                        <span class="badge <?php echo $pl_badge; ?> rounded-pill border ms-auto">
                            <?php echo ucfirst($sch['type']); ?>
                        </span>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <div class="text-center text-muted py-3">Hôm nay không có lịch đăng bài nào.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>