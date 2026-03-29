<?php
session_start();
require_once '../config/db.php';

// Kiểm tra quyền (Chỉ Admin mới xem được báo cáo tài chính)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    // Nếu là Staff, chuyển hướng về Dashboard
    if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'staff') {
        echo "<script>alert('Bạn không có quyền truy cập báo cáo!'); window.location.href='dashboard.php';</script>";
    } else {
        header("Location: login.php");
    }
    exit;
}

// --- 1. LẤY SỐ LIỆU TỔNG QUAN (KPIs) ---
$current_month = date('m');
$current_year = date('Y');

// Doanh thu tháng này (Chỉ tính đơn 'completed' hoặc 'paid')
$sql_revenue = "SELECT SUM(price_at_purchase) as total FROM orders 
                WHERE status IN ('paid', 'completed') 
                AND MONTH(created_at) = $current_month AND YEAR(created_at) = $current_year";
$revenue = $conn->query($sql_revenue)->fetch_assoc()['total'] ?? 0;

// Đơn hoàn thành tháng này
$sql_completed = "SELECT COUNT(*) as count FROM orders 
                  WHERE status = 'completed' 
                  AND MONTH(created_at) = $current_month AND YEAR(created_at) = $current_year";
$completed_orders = $conn->query($sql_completed)->fetch_assoc()['count'];

// Khách hàng mới tháng này
$sql_new_customers = "SELECT COUNT(*) as count FROM users 
                      WHERE role = 'customer' 
                      AND MONTH(created_at) = $current_month AND YEAR(created_at) = $current_year";
$new_customers = $conn->query($sql_new_customers)->fetch_assoc()['count'];

// Đang xử lý (Tất cả thời gian)
$sql_processing = "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'design_review')";
$processing_orders = $conn->query($sql_processing)->fetch_assoc()['count'];


// --- 2. DỮ LIỆU BIỂU ĐỒ DOANH THU (12 THÁNG) ---
$revenue_data = []; // Mảng chứa 12 số liệu
for ($i = 1; $i <= 12; $i++) {
    $sql_month = "SELECT SUM(price_at_purchase) as total FROM orders 
                  WHERE status IN ('paid', 'completed') 
                  AND MONTH(created_at) = $i AND YEAR(created_at) = $current_year";
    $val = $conn->query($sql_month)->fetch_assoc()['total'] ?? 0;
    $revenue_data[] = $val / 1000000; // Đổi sang đơn vị Triệu VNĐ cho gọn
}
$revenue_json = json_encode($revenue_data);


// --- 3. DỮ LIỆU BIỂU ĐỒ GÓI DỊCH VỤ ---
$pkg_labels = [];
$pkg_data = [];
$pkg_colors = ['#e3f2fd', '#90caf9', '#1976d2', '#fdd835', '#ff9800', '#4caf50']; // Bảng màu

$sql_pkg = "SELECT p.name, COUNT(o.id) as count 
            FROM orders o 
            JOIN service_option so ON o.service_option_id = so.id
            JOIN package p ON so.package_id = p.id
            WHERE o.status != 'cancelled'
            GROUP BY p.name 
            ORDER BY count DESC LIMIT 5"; // Top 5 gói
$res_pkg = $conn->query($sql_pkg);
while($row = $res_pkg->fetch_assoc()) {
    $pkg_labels[] = $row['name'];
    $pkg_data[] = $row['count'];
}
$pkg_labels_json = json_encode($pkg_labels);
$pkg_data_json = json_encode($pkg_data);


// --- 4. DỮ LIỆU BIỂU ĐỒ KÊNH (PLATFORM) ---
$pl_labels = [];
$pl_data = [];
$sql_pl = "SELECT pl.name, COUNT(o.id) as count 
           FROM orders o 
           JOIN service_option so ON o.service_option_id = so.id
           JOIN platform pl ON so.platform_id = pl.id
           WHERE o.status != 'cancelled'
           GROUP BY pl.name";
$res_pl = $conn->query($sql_pl);
while($row = $res_pl->fetch_assoc()) {
    $pl_labels[] = $row['name'];
    $pl_data[] = $row['count'];
}
$pl_labels_json = json_encode($pl_labels);
$pl_data_json = json_encode($pl_data);


// --- 5. TOP KHÁCH HÀNG ---
$top_customers = $conn->query("
    SELECT u.fullname, COUNT(o.id) as total_orders, SUM(o.price_at_purchase) as total_spent 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status IN ('paid', 'completed')
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê - Schedio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <div class="admin-wrapper">
        <?php include 'templates/sidebar.php'; ?>

        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-primary fw-bold mb-0">Báo cáo thống kê</h2>
                    <p class="text-muted small mb-0">Tổng hợp số liệu kinh doanh năm <?php echo $current_year; ?></p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> In báo
                        cáo</button>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="stat-card border-start border-4 border-primary">
                        <p class="stat-title mb-1">Doanh thu (T<?php echo $current_month; ?>)</p>
                        <h3 class="fw-bold text-dark"><?php echo number_format($revenue, 0, ',', '.'); ?>đ</h3>
                        <small class="text-muted">Đơn hàng đã thanh toán</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-4 border-success">
                        <p class="stat-title mb-1">Đơn hoàn thành (T<?php echo $current_month; ?>)</p>
                        <h3 class="fw-bold text-dark"><?php echo $completed_orders; ?></h3>
                        <small class="text-muted">Đã đăng bài xong</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-4 border-warning">
                        <p class="stat-title mb-1">Khách hàng mới (T<?php echo $current_month; ?>)</p>
                        <h3 class="fw-bold text-dark"><?php echo $new_customers; ?></h3>
                        <small class="text-muted">Đăng ký trong tháng</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-4 border-info">
                        <p class="stat-title mb-1">Đang xử lý</p>
                        <h3 class="fw-bold text-dark"><?php echo $processing_orders; ?></h3>
                        <small class="text-muted">Cần duyệt gấp</small>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between">
                            <span>Biểu đồ doanh thu năm <?php echo $current_year; ?> (Triệu VNĐ)</span>
                            <i class="bi bi-graph-up-arrow text-primary"></i>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="120"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold py-3">Tỷ lệ gói dịch vụ (Top 5)</div>
                        <div class="card-body d-flex flex-column justify-content-center">
                            <canvas id="packageChart" style="max-height: 250px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold py-3">Hiệu quả theo Kênh truyền thông</div>
                        <div class="card-body">
                            <canvas id="platformChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-bold py-3">Top Khách hàng chi tiêu cao</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Khách hàng</th>
                                            <th>Số đơn</th>
                                            <th class="text-end pe-4">Tổng chi tiêu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($top_customers->num_rows > 0): ?>
                                        <?php while($cus = $top_customers->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($cus['fullname']); ?>
                                            </td>
                                            <td><?php echo $cus['total_orders']; ?></td>
                                            <td class="text-end pe-4 text-success fw-bold">
                                                <?php echo number_format($cus['total_spent'], 0, ',', '.'); ?>đ
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">Chưa có dữ liệu.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // 1. Biểu đồ Doanh thu (Line)
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'line',
        data: {
            labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
            datasets: [{
                label: 'Doanh thu (Triệu VNĐ)',
                data: <?php echo $revenue_json; ?>, // Dữ liệu từ PHP
                borderColor: '#191970',
                backgroundColor: 'rgba(25, 25, 112, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // 2. Biểu đồ Gói dịch vụ (Doughnut)
    const ctxPackage = document.getElementById('packageChart').getContext('2d');
    new Chart(ctxPackage, {
        type: 'doughnut',
        data: {
            labels: <?php echo $pkg_labels_json; ?>,
            datasets: [{
                data: <?php echo $pkg_data_json; ?>,
                backgroundColor: ['#1976d2', '#42a5f5', '#90caf9', '#e3f2fd', '#ffca28'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // 3. Biểu đồ Kênh (Bar)
    const ctxPlatform = document.getElementById('platformChart').getContext('2d');
    new Chart(ctxPlatform, {
        type: 'bar',
        data: {
            labels: <?php echo $pl_labels_json; ?>,
            datasets: [{
                label: 'Số lượng đơn',
                data: <?php echo $pl_data_json; ?>,
                backgroundColor: ['#191970', '#0d6efd', '#ffc107'],
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    </script>

</body>

</html>