<?php
// customer/checkout.php
session_start();
require_once '../config/db.php';
include '../templates/header.php'; 

// --- 1. CẤU HÌNH TÀI KHOẢN NHẬN TIỀN ---
$MY_BANK_ID = 'MB'; 
$MY_ACCOUNT_NO = '0344377104'; 
$MY_ACCOUNT_NAME = 'TRAN ANH DUC'; 

// 2. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 3. Lấy Order ID
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id == 0) {
    echo '<div class="container my-5"><div class="alert alert-danger">Đơn hàng không hợp lệ.</div></div>';
    include '../templates/footer.php';
    exit;
}

// 4. Lấy thông tin đơn hàng
$sql = "
    SELECT 
        o.id, o.title, o.price_at_purchase, o.created_at, o.status,
        p.name AS package_name, 
        pl.name AS platform_name,
        u.fullname, u.email, u.phonenumber,
        (SELECT start_time FROM schedules WHERE post_id IN (SELECT id FROM post WHERE order_id = o.id) ORDER BY start_time ASC LIMIT 1) as first_schedule
    FROM orders o
    JOIN service_option so ON o.service_option_id = so.id
    JOIN package p ON so.package_id = p.id
    JOIN platform pl ON so.platform_id = pl.id
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo '<div class="container my-5"><div class="alert alert-danger">Không tìm thấy đơn hàng.</div></div>';
    include '../templates/footer.php';
    exit;
}

$order = $result->fetch_assoc();
$stmt->close();

// Format dữ liệu
$booking_date = $order['first_schedule'] ? date('d/m/Y', strtotime($order['first_schedule'])) : 'N/A';
$booking_time = $order['first_schedule'] ? date('h:i A', strtotime($order['first_schedule'])) : 'N/A';
$price_display = number_format($order['price_at_purchase'], 0, ',', '.') . ' đ';

// QR Code
$amount_int = (int)$order['price_at_purchase'];
$transfer_content = "SCD" . $order_id; 
$qr_url = "https://img.vietqr.io/image/{$MY_BANK_ID}-{$MY_ACCOUNT_NO}-compact2.png?amount={$amount_int}&addInfo={$transfer_content}&accountName=" . urlencode($MY_ACCOUNT_NAME);

// Kiểm tra trạng thái thanh toán
$is_paid = ($order['status'] == 'paid' || $order['status'] == 'in_progress' || $order['status'] == 'completed');
?>

<div class="container my-5">
    <h1 class="display-5 fw-bold text-dark-blue mb-5">Thanh toán</h1>

    <div class="row g-4">

        <div class="col-lg-6">

            <div class="card border-0 schedio-card-bg p-4 mb-4">
                <h5 class="fw-bold text-dark-blue mb-4">Thông tin người đặt</h5>
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold">Tên:</span>
                    <span><?php echo htmlspecialchars($order['fullname']); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold">Email:</span>
                    <span><?php echo htmlspecialchars($order['email']); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold">Số điện thoại:</span>
                    <span><?php echo htmlspecialchars($order['phonenumber']); ?></span>
                </div>
            </div>

            <div class="card border-0 schedio-card-bg p-4">
                <h5 class="fw-bold text-dark-blue mb-4">Phương thức thanh toán</h5>

                <?php if (!$is_paid): ?>
                <div class="text-center bg-white p-4 rounded shadow-sm">
                    <p class="text-primary fw-bold mb-3">Quét mã để thanh toán ngay</p>

                    <img src="<?php echo $qr_url; ?>" alt="QR Code Thanh Toán" class="img-fluid mb-3"
                        style="max-width: 300px;">

                    <div class="alert alert-warning small text-start d-inline-block">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Vui lòng giữ nguyên nội dung chuyển khoản: <br>
                        <strong
                            class="fs-5 text-dark text-center d-block mt-1"><?php echo $transfer_content; ?></strong>
                    </div>
                    <div class="mt-2 text-muted small">
                        Hệ thống sẽ tự động cập nhật sau ít phút.
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center bg-white p-5 rounded shadow-sm">
                    <div class="mb-3">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h3 class="fw-bold text-success mb-3">Thanh toán thành công!</h3>
                    <p class="text-muted">Cảm ơn bạn đã sử dụng dịch vụ.</p>
                    <div class="p-3 bg-light rounded d-inline-block mt-2">
                        <p class="mb-0 small text-muted">Mã đơn hàng: <strong>#SCD<?php echo $order_id; ?></strong></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <div class="col-lg-6">
            <div class="card border-0 schedio-card-bg p-4 h-100 d-flex flex-column">
                <h5 class="fw-bold text-dark-blue mb-3">Thông tin đơn hàng</h5>
                <p class="text-primary fw-bold mb-4 text-uppercase">
                    <?php echo htmlspecialchars($order['package_name']); ?></p>

                <div class="bg-light-gray p-3 rounded mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold small">Ngày bắt đầu:</span>
                        <span class="small"><?php echo $booking_date; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold small">Giờ:</span>
                        <span class="small"><?php echo $booking_time; ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold small">Kênh:</span>
                        <span class="small"><?php echo htmlspecialchars($order['platform_name']); ?></span>
                    </div>
                </div>

                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold text-dark-blue">Trạng thái:</span>
                    <?php if ($is_paid): ?>
                    <span class="badge bg-success rounded-pill px-3">Đã thanh toán</span>
                    <?php else: ?>
                    <span class="badge bg-warning text-dark rounded-pill px-3">Chờ thanh toán</span>
                    <?php endif; ?>
                </div>

                <hr class="my-4 text-muted">

                <div class="d-flex justify-content-between mb-5">
                    <span class="fw-bold text-dark-blue fs-5">Tổng cộng:</span>
                    <span class="fw-bold text-dark-blue fs-4 text-primary"><?php echo $price_display; ?></span>
                </div>

                <div class="mt-auto">
                    <?php if (!$is_paid): ?>
                    <div class="text-center py-3 bg-white rounded shadow-sm">
                        <div class="spinner-border text-primary mb-2" role="status" style="width: 2rem; height: 2rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mb-0 fw-bold text-primary animate-pulse">Đang chờ xác nhận thanh toán...</p>
                        <small class="text-muted">Vui lòng không tắt trình duyệt</small>
                    </div>
                    <?php else: ?>
                    <a href="order_detail.php?id=<?php echo $order['id']; ?>"
                        class="btn btn-schedio-primary w-100 py-3 text-uppercase fw-bold shadow">
                        Xem chi tiết đơn hàng <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderId = <?php echo $order_id; ?>;
    const isPaid = <?php echo ($is_paid) ? 'true' : 'false'; ?>;

    // Chỉ chạy kiểm tra khi chưa thanh toán
    if (!isPaid) {
        console.log("Đang lắng nghe giao dịch...");

        const checkInterval = setInterval(function() {
            // Gọi file kiểm tra Casso
            fetch('check_payment_casso.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        clearInterval(checkInterval); // Dừng kiểm tra

                        // Reload trang để PHP hiển thị giao diện thành công
                        location.reload();
                    }
                })
                .catch(err => console.error('Waiting...', err));

        }, 3000); // 3 giây/lần
    }
});
</script>

<style>
@keyframes pulse {
    0% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }

    100% {
        opacity: 1;
    }
}

.animate-pulse {
    animation: pulse 1.5s infinite;
}
</style>

<?php include '../templates/footer.php'; ?>