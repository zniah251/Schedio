<?php
// admin/order_detail.php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

// 1. KIỂM TRA QUYỀN
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    header("Location: login.php");
    exit;
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id == 0) die("Không tìm thấy ID đơn hàng.");

// 2. XỬ LÝ FORM
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- XỬ LÝ 1: GỬI DEMO ---
    if ($action == 'send_demo') {
        $demo_link = trim($_POST['admin_demo_img']);
        $msg = trim($_POST['admin_message']);

        $stmt = $conn->prepare("UPDATE orders SET status = 'design_review', admin_feedback_files = ?, admin_feedback_content = ? WHERE id = ?");
        $stmt->bind_param("ssi", $demo_link, $msg, $order_id);
        $stmt->execute();

        // Thông báo
        $u_query = $conn->query("SELECT user_id FROM orders WHERE id = $order_id");
        $u_id = $u_query->fetch_assoc()['user_id'];
        sendNotification($conn, $u_id, $order_id, "Đã có bản Demo!", "Admin đã gửi demo. Vui lòng kiểm tra và phản hồi.", "warning");

        echo "<script>alert('Đã gửi demo!'); window.location.href='order_detail.php?id=$order_id';</script>";
        exit;
    }

    // --- XỬ LÝ 2: XÁC NHẬN THANH TOÁN ---
    elseif ($action == 'confirm_payment') {
        $conn->query("UPDATE orders SET status = 'paid' WHERE id = $order_id");
        echo "<script>alert('Đã xác nhận thanh toán!'); window.location.href='order_detail.php?id=$order_id';</script>";
        exit;
    }

    // --- XỬ LÝ 3: CẬP NHẬT LINK CHO TỪNG BÀI ĐĂNG (MỚI) ---
    elseif ($action == 'update_post_link') {
        $post_id = intval($_POST['post_id']);
        $link = trim($_POST['post_link']);

        if (empty($link)) {
            echo "<script>alert('Vui lòng nhập link bài đăng!'); window.history.back();</script>";
            exit;
        }

        // 1. Cập nhật bài post này
        $stmt = $conn->prepare("UPDATE post SET status = 'posted', result_link = ? WHERE id = ?");
        $stmt->bind_param("si", $link, $post_id);
        $stmt->execute();

        // Cập nhật schedule tương ứng
        $conn->query("UPDATE schedules SET status = 'posted' WHERE post_id = $post_id");

        // 2. KIỂM TRA XEM CÒN BÀI NÀO CHƯA ĐĂNG KHÔNG?
        $check = $conn->query("SELECT COUNT(*) as count FROM post WHERE order_id = $order_id AND status != 'posted'");
        $remaining = $check->fetch_assoc()['count'];

        if ($remaining == 0) {
            // --- TẤT CẢ ĐÃ XONG -> HOÀN TẤT ĐƠN HÀNG ---

            // a. Update trạng thái đơn hàng
            // Lưu link bài cuối cùng vào result_links của order để backup (hoặc có thể nối chuỗi các link lại nếu muốn)
            $conn->query("UPDATE orders SET status = 'completed', result_links = '$link' WHERE id = $order_id");

            // b. Cộng tiền & Update Rank
            $q_info = $conn->query("SELECT user_id, price_at_purchase FROM orders WHERE id = $order_id");
            $d_info = $q_info->fetch_assoc();
            $uid = $d_info['user_id'];
            $price = $d_info['price_at_purchase'];

            $conn->query("UPDATE users SET total_spent = total_spent + $price WHERE id = $uid");

            // Tính rank
            $q_user = $conn->query("SELECT total_spent FROM users WHERE id = $uid");
            $total = $q_user->fetch_assoc()['total_spent'];
            $new_rank = 'Level 1';
            if ($total >= 10000000) $new_rank = 'Level 4';
            elseif ($total >= 3000000) $new_rank = 'Level 3';
            elseif ($total >= 1000000) $new_rank = 'Level 2';
            $conn->query("UPDATE users SET rank_level = '$new_rank' WHERE id = $uid");

            // c. Thông báo hoàn tất
            sendNotification($conn, $uid, $order_id, "Đơn hàng hoàn tất", "Tất cả các bài đăng của đơn #$order_id đã hoàn thành. Bạn được cộng " . number_format($price) . "đ.", "success");

            echo "<script>alert('Đã cập nhật bài đăng cuối cùng! Đơn hàng ĐÃ HOÀN TẤT.'); window.location.href='order_detail.php?id=$order_id';</script>";
        } else {
            // Vẫn còn bài chưa đăng -> Set trạng thái là In Progress
            $conn->query("UPDATE orders SET status = 'in_progress' WHERE id = $order_id AND status = 'paid'");

            echo "<script>alert('Đã cập nhật link bài đăng này. Còn $remaining bài nữa.'); window.location.href='order_detail.php?id=$order_id';</script>";
        }
        exit;
    }
}

// 3. LẤY DỮ LIỆU ĐƠN HÀNG
$sql = "SELECT o.*, 
               u.fullname AS customer_name, u.email AS customer_email,
               p.name AS package_name, 
               pl.name AS platform_name,
               (SELECT start_time FROM schedules WHERE post_id IN (SELECT id FROM post WHERE order_id = o.id) ORDER BY start_time ASC LIMIT 1) as schedule_time
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN service_option so ON o.service_option_id = so.id
        JOIN package p ON so.package_id = p.id
        JOIN platform pl ON so.platform_id = pl.id
        WHERE o.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) die("Không tìm thấy đơn hàng.");

// 4. LẤY DANH SÁCH CÁC SLOT (POSTS)
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
while ($row = $posts_result->fetch_assoc()) {
    $posts[] = $row;
}

$order_code = 'SCD-' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
$formatted_price = number_format($order['price_at_purchase'], 0, ',', '.') . ' đ';
$formatted_date = date('d/m/Y H:i', strtotime($order['created_at']));
$formatted_schedule = $order['schedule_time'] ? date('d/m/Y H:i', strtotime($order['schedule_time'])) : '<span class="text-muted">Chưa xếp lịch</span>';

// Map trạng thái
$status_map = [
    'pending' => ['Chờ xử lý', 'bg-warning text-dark'],
    'design_review' => ['Duyệt Demo', 'bg-info text-white'],
    'waiting_payment' => ['Chờ thanh toán', 'bg-primary text-white'],
    'paid' => ['Đã thanh toán', 'bg-success text-white'],
    'in_progress' => ['Đang thực hiện', 'bg-primary-subtle text-primary'],
    'completed' => ['Hoàn thành', 'bg-success text-white'],
    'cancelled' => ['Đã hủy', 'bg-danger text-white']
];
$status_info = $status_map[$order['status']] ?? ['Không xác định', 'bg-secondary'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?php echo $order_code; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'templates/sidebar.php'; ?>
        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="orders.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left"></i>
                        Quay lại</a>
                    <h2 class="text-primary fw-bold mb-0 mt-1">Chi tiết đơn hàng #<?php echo $order_code; ?></h2>
                </div>
                <div>
                    <span class="badge <?php echo $status_info[1]; ?> fs-6 px-3 py-2 rounded-pill">
                        <?php echo $status_info[0]; ?>
                    </span>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold py-3">Thông tin yêu cầu</div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4 text-muted">Khách hàng:</div>
                                <div class="col-md-8 fw-bold">
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                    <small
                                        class="fw-normal text-muted">(<?php echo htmlspecialchars($order['customer_email']); ?>)</small>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 text-muted">Tiêu đề nội dung:</div>
                                <div class="col-md-8"><?php echo htmlspecialchars($order['title']); ?></div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 text-muted">Link Drive (Source):</div>
                                <div class="col-md-8">
                                    <a href="<?php echo htmlspecialchars($order['content_url']); ?>" target="_blank"
                                        class="btn btn-outline-success btn-sm px-3 rounded-pill">
                                        <i class="bi bi-google"></i> Mở Google Drive
                                    </a>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 text-muted">Link Sản phẩm:</div>
                                <div class="col-md-8">
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
                                    }
                                    ?>
                                    <a href="<?php echo $prod_link; ?>" target="_blank"
                                        class="btn <?php echo $btn_color; ?> btn-sm px-3 rounded-pill">
                                        <i class="bi <?php echo $icon_class; ?>"></i> <?php echo $btn_text; ?>
                                    </a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 text-muted">Ghi chú của khách:</div>
                                <div class="col-md-8 fst-italic text-danger">
                                    <?php echo !empty($order['note']) ? nl2br(htmlspecialchars($order['note'])) : 'Không có ghi chú'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($order['status'] != 'cancelled'): ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white fw-bold py-3">
                                <i class="bi bi-tools me-2"></i> Xử lý đơn hàng
                            </div>
                            <div class="card-body p-4">

                                <form method="POST" action="">
                                    <div class="mb-4 pb-4 border-bottom">
                                        <label class="form-label fw-bold">1. Cập nhật bản thiết kế (Demo)</label>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text">Link ảnh/video</span>
                                            <input type="text" class="form-control" name="admin_demo_img"
                                                value="<?php echo htmlspecialchars($order['admin_feedback_files'] ?? ''); ?>"
                                                placeholder="Dán link ảnh demo vào đây..."
                                                <?php echo ($order['status'] == 'paid') ? 'required' : ''; ?>>
                                        </div>
                                        <textarea class="form-control" name="admin_message" rows="2"
                                            placeholder="Lời nhắn cho khách hàng..."><?php echo htmlspecialchars($order['admin_feedback_content'] ?? ''); ?></textarea>

                                        <button type="submit" name="action" value="send_demo"
                                            class="btn btn-sm btn-info text-white mt-2"
                                            onclick="return confirm('Gửi Demo cho khách duyệt?')">
                                            <i class="bi bi-send"></i> Gửi Demo
                                        </button>
                                    </div>
                                </form>

                                <form method="POST" action="">
                                    <div class="mb-4 pb-4 border-bottom">
                                        <label class="form-label fw-bold">2. Trạng thái thanh toán</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if ($order['status'] == 'paid' || $order['status'] == 'in_progress' || $order['status'] == 'completed'): ?>
                                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Đã thanh
                                                    toán</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Chưa thanh toán</span>
                                                <?php if ($order['status'] == 'waiting_payment'): ?>
                                                    <button type="submit" name="action" value="confirm_payment"
                                                        class="btn btn-sm btn-success"
                                                        onclick="return confirm('Xác nhận ĐÃ NHẬN TIỀN?')">
                                                        <i class="bi bi-check-circle"></i> Xác nhận đã nhận tiền
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>

                                <div>
                                    <label class="form-label fw-bold mb-3">3. Lịch đăng & Trả Link bài viết</label>

                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 25%">Thời gian đăng</th>
                                                    <th style="width: 15%">Trạng thái</th>
                                                    <th>Link kết quả</th>
                                                    <th style="width: 10%"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($posts as $post): ?>
                                                    <tr>
                                                        <td class="fw-bold text-dark-blue">
                                                            <?php echo date('H:i d/m/Y', strtotime($post['start_time'])); ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($post['status'] == 'posted'): ?>
                                                                <span class="badge bg-success">Đã đăng</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark">Chờ đăng</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($post['status'] == 'posted'): ?>
                                                                <a href="<?php echo htmlspecialchars($post['result_link']); ?>"
                                                                    target="_blank" class="text-truncate d-inline-block"
                                                                    style="max-width: 250px;">
                                                                    <?php echo htmlspecialchars($post['result_link']); ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <form method="POST" id="form-post-<?php echo $post['id']; ?>"
                                                                    class="d-flex">
                                                                    <input type="hidden" name="action" value="update_post_link">
                                                                    <input type="hidden" name="post_id"
                                                                        value="<?php echo $post['id']; ?>">
                                                                    <input type="text" name="post_link"
                                                                        class="form-control form-control-sm"
                                                                        placeholder="Dán link bài viết..." required
                                                                        <?php echo ($order['status'] != 'paid' && $order['status'] != 'in_progress' && $order['status'] != 'completed') ? 'disabled' : ''; ?>>
                                                                </form>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php if ($post['status'] != 'posted'): ?>
                                                                <button form="form-post-<?php echo $post['id']; ?>"
                                                                    class="btn btn-primary btn-sm"
                                                                    <?php echo ($order['status'] != 'paid' && $order['status'] != 'in_progress' && $order['status'] != 'completed') ? 'disabled' : ''; ?>>
                                                                    <i class="bi bi-check2"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <?php if ($order['status'] != 'paid' && $order['status'] != 'in_progress' && $order['status'] != 'completed'): ?>
                                        <small class="text-danger mt-2 d-block">* Cần xác nhận thanh toán trước khi trả
                                            bài.</small>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold py-3">Gói dịch vụ</div>
                        <div class="card-body">
                            <h4 class="text-primary fw-bold mb-1">
                                <?php echo htmlspecialchars($order['package_name']); ?></h4>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($order['platform_name']); ?>
                            </p>
                            <ul class="list-group list-group-flush mb-3 small">
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>Ngày đăng ký:</span> <span
                                        class="fw-bold"><?php echo $formatted_date; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>Lịch đăng (dự kiến):</span> <span
                                        class="fw-bold text-danger"><?php echo $formatted_schedule; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>Tổng tiền:</span> <span
                                        class="fw-bold text-primary fs-5"><?php echo $formatted_price; ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>