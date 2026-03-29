<?php
session_start();
require_once '../config/db.php';
include '../templates/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Đánh dấu tất cả là đã đọc khi vào trang này
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");

// Lấy danh sách thông báo
$sql = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<div class="container my-5">
    <h2 class="fw-bold text-dark-blue mb-4">Thông báo của bạn</h2>

    <div class="list-group shadow-sm">
        <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <?php 
                    // Icon tùy theo loại thông báo
                    $icon = 'bi-info-circle';
                    $bg_class = '';
                    if($row['type'] == 'success') { $icon = 'bi-check-circle-fill text-success'; $bg_class = 'bg-success bg-opacity-10'; }
                    if($row['type'] == 'warning') { $icon = 'bi-star-fill text-warning'; $bg_class = 'bg-warning bg-opacity-10'; }
                    if($row['type'] == 'danger') { $icon = 'bi-exclamation-triangle-fill text-danger'; $bg_class = 'bg-danger bg-opacity-10'; }
                ?>
        <a href="order_detail.php?id=<?php echo $row['order_id']; ?>"
            class="list-group-item list-group-item-action p-4 d-flex align-items-start gap-3 <?php echo $row['is_read'] == 0 ? 'bg-light fw-bold border-start border-5 border-primary' : ''; ?>">
            <div class="fs-4 mt-1"><i class="bi <?php echo $icon; ?>"></i></div>
            <div class="w-100">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1 <?php echo $row['type'] == 'success' ? 'text-success' : 'text-dark-blue'; ?>">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </h5>
                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></small>
                </div>
                <p class="mb-1 text-secondary"><?php echo htmlspecialchars($row['message']); ?></p>
                <small class="text-primary">Xem chi tiết <i class="bi bi-arrow-right"></i></small>
            </div>
        </a>
        <?php endwhile; ?>
        <?php else: ?>
        <div class="text-center p-5">
            <i class="bi bi-bell-slash fs-1 text-muted"></i>
            <p class="mt-3 text-muted">Bạn chưa có thông báo nào.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../templates/footer.php'; ?>