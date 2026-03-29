<?php
// admin/update_schedule_status.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// 1. Kiểm tra quyền
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 2. Nhận dữ liệu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $schedule_id = intval($_POST['id']);
    
    // 3. Cập nhật trạng thái trong bảng SCHEDULES
    $sql = "UPDATE schedules SET status = 'posted' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    
    if ($stmt->execute()) {
        // (Tuỳ chọn) Cập nhật luôn bảng POST nếu cần
        // $conn->query("UPDATE post SET status = 'posted' WHERE id = (SELECT post_id FROM schedules WHERE id = $schedule_id)");

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>