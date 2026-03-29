<?php
// customer/order_detail.php

// 1. KHỞI TẠO SESSION AN TOÀN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

// 2. KIỂM TRA QUYỀN
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 3. KIỂM TRA ID ĐƠN HÀNG
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Không tìm thấy ID đơn hàng.");
}
$order_id = intval($_GET['id']);

// 4. TRUY VẤN CHI TIẾT ĐƠN HÀNG
$sql = "
    SELECT o.*, 
           p.name AS package_name, 
           pl.name AS platform_name,
           (SELECT start_time FROM schedules WHERE post_id IN (SELECT id FROM post WHERE order_id = o.id) ORDER BY start_time ASC LIMIT 1) as schedule_time
    FROM orders o
    JOIN service_option so ON o.service_option_id = so.id
    JOIN package p ON so.package_id = p.id
    JOIN platform pl ON so.platform_id = pl.id
    WHERE o.id = ? AND o.user_id = ? 
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<script>alert('Đơn hàng không tồn tại hoặc bạn không có quyền xem!'); window.location.href='account.php?tab=orders';</script>";
    exit;
}

// --- MỚI: LẤY DANH SÁCH CÁC BÀI ĐĂNG (SLOTS) ---
$sql_posts = "SELECT p.id, p.status, p.result_link, s.start_time 
              FROM post p 
              JOIN schedules s ON s.post_id = p.id 
              WHERE p.order_id = ? 
              ORDER BY s.start_time ASC";
$stmt_p = $conn->prepare($sql_posts);
$stmt_p->bind_param("i", $order_id);
$stmt_p->execute();
$posts_result = $stmt_p->get_result();
$posts = [];
while($row = $posts_result->fetch_assoc()) {
    $posts[] = $row;
}
// ------------------------------------------------

// 5. XỬ LÝ KHÁCH HÀNG PHẢN HỒI
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['customer_action'])) {
    $action = $_POST['customer_action'];
    
    if ($action == 'approve_demo') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'waiting_payment' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        header("Location: order_detail.php?id=$order_id");
        exit;
    } 
    elseif ($action == 'request_fix') {
        $fix_note = trim($_POST['fix_note']);
        $new_note = $order['note'] . "\n[Yêu cầu sửa " . date('d/m H:i') . "]: " . $fix_note;
        
        $stmt = $conn->prepare("UPDATE orders SET status = 'pending', note = ? WHERE id = ?");
        $stmt->bind_param("si", $new_note, $order_id);
        $stmt->execute();
        header("Location: order_detail.php?id=$order_id");
        exit;
    }
}

// Format dữ liệu hiển thị
$order_code = 'SCD-' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
$formatted_price = number_format($order['price_at_purchase'], 0, ',', '.') . ' đ';
$formatted_date = date('d/m/Y H:i', strtotime($order['created_at']));
$formatted_schedule = $order['schedule_time'] ? date('d/m/Y H:i', strtotime($order['schedule_time'])) : '<span class="text-muted">Chưa xếp lịch</span>';

include '../templates/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="account.php?tab=orders" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left"></i> Quay lại danh sách
            </a>
            <h2 class="text-dark-blue fw-bold mb-0 mt-1">Chi tiết đơn hàng #<?php echo $order_code; ?></h2>
        </div>

        <?php
        $status_labels = [
            'pending' => ['Chờ xử lý', 'warning'],
            'design_review' => ['Duyệt Demo', 'info'],
            'waiting_payment' => ['Chờ thanh toán', 'primary'],
            'paid' => ['Đã thanh toán', 'success'],
            'in_progress' => ['Đang thực hiện', 'primary'],
            'completed' => ['Hoàn thành', 'success'],
            'cancelled' => ['Đã hủy', 'danger']
        ];
        $stt = $status_labels[$order['status']] ?? ['Không xác định', 'secondary'];
        ?>
        <span class="badge bg-<?php echo $stt[1]; ?> fs-5 px-4 py-2 rounded-pill"><?php echo $stt[0]; ?></span>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3">Thông tin dịch vụ</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <small class="text-muted">Gói dịch vụ:</small>
                            <div class="fw-bold"><?php echo htmlspecialchars($order['package_name']); ?></div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <small class="text-muted">Kênh truyền thông:</small>
                            <div class="fw-bold"><?php echo htmlspecialchars($order['platform_name']); ?></div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <small class="text-muted">Giá tiền:</small>
                            <div class="fw-bold text-primary"><?php echo $formatted_price; ?></div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <small class="text-muted">Lịch đăng (dự kiến):</small>
                            <div class="fw-bold text-danger"><?php echo $formatted_schedule; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3">Nội dung yêu cầu</div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong class="d-block mb-1">Tiêu đề:</strong>
                        <span class="fs-5"><?php echo htmlspecialchars($order['title']); ?></span>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong class="d-block mb-2">Link Source (Tài nguyên):</strong>
                            <a href="<?php echo $order['content_url']; ?>" target="_blank"
                                class="btn btn-outline-success btn-sm px-3 rounded-pill">
                                <i class="bi bi-google"></i> Mở Google Drive
                            </a>
                        </div>
                        <div class="col-md-6">
                            <strong class="d-block mb-2">Link Sản phẩm:</strong>
                            <?php 
                                $prod_link = $order['product_link'];
                                $icon_class = 'bi-link-45deg';
                                $btn_text = 'Truy cập liên kết';
                                $btn_color = 'btn-outline-primary';

                                if (strpos($prod_link, 'youtube') !== false || strpos($prod_link, 'youtu.be') !== false) {
                                    $icon_class = 'bi-youtube';
                                    $btn_text = 'Xem trên YouTube';
                                    $btn_color = 'btn-outline-danger';
                                } elseif (strpos($prod_link, 'facebook') !== false || strpos($prod_link, 'fb.com') !== false) {
                                    $icon_class = 'bi-facebook';
                                    $btn_text = 'Xem trên Facebook';
                                } elseif (strpos($prod_link, 'tiktok') !== false) {
                                    $icon_class = 'bi-tiktok';
                                    $btn_text = 'Xem trên TikTok';
                                    $btn_color = 'btn-outline-dark';
                                } elseif (strpos($prod_link, 'soundcloud') !== false) {
                                    $icon_class = 'bi-soundwave';
                                    $btn_text = 'Nghe trên SoundCloud';
                                    $btn_color = 'btn-outline-warning text-dark';
                                }
                            ?>
                            <a href="<?php echo $prod_link; ?>" target="_blank"
                                class="btn <?php echo $btn_color; ?> btn-sm px-3 rounded-pill">
                                <i class="bi <?php echo $icon_class; ?>"></i> <?php echo $btn_text; ?>
                            </a>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border border-light">
                        <strong class="d-block mb-1 text-muted small">Ghi chú:</strong>
                        <div class="fst-italic">
                            <?php echo !empty($order['note']) ? nl2br(htmlspecialchars($order['note'])) : 'Không có ghi chú thêm.'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($order['status'] == 'design_review'): ?>
            <div class="card border-0 shadow-sm mb-4 border-info">
                <div class="card-header bg-info text-white fw-bold py-3">
                    <i class="bi bi-palette-fill me-2"></i> Duyệt thiết kế (Demo)
                </div>
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-3">Admin đã gửi bản demo cho bạn:</h5>

                    <?php if (!empty($order['admin_feedback_files'])): ?>
                    <div class="mb-3">
                        <?php if (filter_var($order['admin_feedback_files'], FILTER_VALIDATE_URL) && strpos($order['admin_feedback_files'], 'drive.google') !== false): ?>
                        <a href="<?php echo $order['admin_feedback_files']; ?>" target="_blank"
                            class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-google"></i> Xem Demo trên Drive
                        </a>
                        <?php else: ?>
                        <img src="<?php echo $order['admin_feedback_files']; ?>"
                            class="img-fluid rounded shadow-sm border" style="max-height: 400px;">
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <p class="fst-italic text-muted">"<?php echo htmlspecialchars($order['admin_feedback_content']); ?>"
                    </p>
                    <hr>

                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <button class="btn btn-outline-danger px-4" type="button" data-bs-toggle="collapse"
                            data-bs-target="#fixForm">
                            <i class="bi bi-pencil"></i> Yêu cầu sửa
                        </button>

                        <form method="POST">
                            <input type="hidden" name="customer_action" value="approve_demo">
                            <button type="submit" class="btn btn-success px-4"
                                onclick="return confirm('Bạn xác nhận ưng ý với bản thiết kế này?');">
                                <i class="bi bi-check-lg"></i> Duyệt & Thanh toán
                            </button>
                        </form>
                    </div>

                    <div class="collapse mt-3" id="fixForm">
                        <form method="POST" class="text-start bg-light p-3 rounded">
                            <input type="hidden" name="customer_action" value="request_fix">
                            <label class="form-label fw-bold">Nhập yêu cầu chỉnh sửa:</label>
                            <textarea name="fix_note" class="form-control mb-2" rows="3" required
                                placeholder="Ví dụ: Đổi màu chữ sang màu đỏ, làm logo to hơn..."></textarea>
                            <button type="submit" class="btn btn-primary btn-sm">Gửi yêu cầu</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($order['status'] == 'paid' || $order['status'] == 'in_progress' || $order['status'] == 'completed'): ?>
            <div class="card border-0 shadow-sm mb-4 border-success">
                <div class="card-header bg-success text-white fw-bold py-3">
                    <i class="bi bi-check-circle-fill me-2"></i> Tiến độ & Kết quả đăng bài
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Thời gian đăng</th>
                                    <th>Trạng thái</th>
                                    <th>Kết quả</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td class="fw-bold text-dark-blue">
                                        <?php echo date('H:i d/m/Y', strtotime($post['start_time'])); ?>
                                    </td>
                                    <td>
                                        <?php if($post['status'] == 'posted'): ?>
                                        <span class="badge bg-success">Đã đăng</span>
                                        <?php else: ?>
                                        <span class="badge bg-warning text-dark">Chờ đăng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($post['status'] == 'posted' && !empty($post['result_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($post['result_link']); ?>" target="_blank"
                                            class="btn btn-sm btn-outline-success rounded-pill">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> Xem bài viết
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted small fst-italic">-- Chưa có --</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($order['status'] == 'completed'): ?>
                    <div class="alert alert-success mt-3 text-center border-0 bg-success-subtle">
                        <strong><i class="bi bi-check-all"></i> Tất cả bài đăng đã hoàn tất!</strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <div class="col-lg-4">
            <?php if ($order['status'] == 'waiting_payment'): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white fw-bold py-3 text-center">THANH TOÁN</div>
                <div class="card-body text-center">
                    <p class="text-muted small">Vui lòng quét mã QR bên dưới để thanh toán.</p>
                    <?php 
                        $bank_id = 'MB'; 
                        $account_no = '0344377104';
                        $account_name = 'TRAN ANH DUC';
                        $amount = $order['price_at_purchase'];
                        $memo = "SCD" . $order['id'];
                        $qr_url = "https://img.vietqr.io/image/$bank_id-$account_no-compact2.png?amount=$amount&addInfo=$memo&accountName=".urlencode($account_name);
                    ?>
                    <img src="<?php echo $qr_url; ?>" class="img-fluid mb-3 border rounded" style="width: 200px;">
                    <h5 class="fw-bold text-primary"><?php echo $formatted_price; ?></h5>
                    <div class="alert alert-warning small text-start mt-3">
                        <i class="bi bi-info-circle"></i> <strong>Lưu ý:</strong>
                        <br>- Nội dung CK: <b><?php echo $memo; ?></b>
                        <br>- Hệ thống sẽ tự động xác nhận sau khi Admin kiểm tra biến động số dư.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold">Cần hỗ trợ?</h6>
                    <p class="small text-muted mb-3">Nếu có vấn đề gì về đơn hàng, vui lòng liên hệ hotline.</p>
                    <a href="tel:084123456789" class="btn btn-light w-100 border fw-bold">
                        <i class="bi bi-telephone-fill me-2"></i> 084 123 456 789
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>