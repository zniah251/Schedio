<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {

    // Lấy ID từ dữ liệu gửi lên
    $data = json_decode(file_get_contents("php://input"), true);
    $notif_id = isset($data['id']) ? intval($data['id']) : 0;

    if ($notif_id > 0) {
        $user_id = $_SESSION['user_id'];

        // Cập nhật trạng thái thành Đã đọc (is_read = 1)
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    }
}
