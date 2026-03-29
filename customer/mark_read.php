<?php
// customer/mark_read.php
session_start();
require_once '../config/db.php';

if (isset($_POST['id']) && isset($_SESSION['user_id'])) {
    $notif_id = intval($_POST['id']);
    $user_id = $_SESSION['user_id'];

    // Cập nhật trạng thái thành đã đọc (1)
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $user_id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
}
?>