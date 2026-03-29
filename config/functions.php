<?php

// Hàm gửi thông báo 
function sendNotification($conn, $user_id, $order_id, $title, $message, $type = 'info')
{
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, order_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $order_id, $title, $message, $type);
    $stmt->execute();
    $stmt->close();
}

// --- MEMBERSHIP): CẤU HÌNH HẠNG THÀNH VIÊN ---
function getMembershipConfig()
{
    return [
        'Level 1' => [
            'name' => 'Level 1',
            'min_spent' => 0,
            'discount_percent' => 0,
            'badge_color' => 'secondary',
            'icon' => 'bi-person'
        ],
        'Level 2' => [
            'name' => 'Level 2',
            'min_spent' => 2000000, // 2 triệu
            'discount_percent' => 5, // Giảm 5%
            'badge_color' => 'info',
            'icon' => 'bi-music-note-beamed'
        ],
        'Level 3' => [
            'name' => 'Level 3',
            'min_spent' => 5000000, // 5 triệu
            'discount_percent' => 10, // Giảm 10%
            'badge_color' => 'primary',
            'icon' => 'bi-star-fill'
        ],
        'Level 4' => [
            'name' => 'Level 4',
            'min_spent' => 10000000, // 10 triệu
            'discount_percent' => 15, // Giảm 15%
            'badge_color' => 'warning',
            'icon' => 'bi-trophy-fill'
        ]
    ];
}

// ---  TÍNH GIÁ ĐÃ GIẢM ---
function calculateDiscountedPrice($originalPrice, $rankLevel)
{
    $config = getMembershipConfig();
    // Nếu rank không tồn tại trong config (ví dụ data cũ), mặc định là Level 1
    $discount = isset($config[$rankLevel]) ? $config[$rankLevel]['discount_percent'] : 0;

    // Tính giá sau giảm
    return $originalPrice * (1 - ($discount / 100));
}

// --- CẬP NHẬT HẠNG THÀNH VIÊN ---
function updateUserRank($conn, $user_id)
{
    // 1. Tính tổng tiền các đơn đã hoàn thành (completed)
    $sql = "SELECT SUM(price_at_purchase) as total FROM orders WHERE user_id = ? AND status = 'completed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalSpent = $row['total'] ?? 0;
    $stmt->close();

    // 2. Xác định hạng mới
    $newRank = 'Level 1';
    $config = getMembershipConfig();

    // Duyệt qua config để xem user đạt mốc nào cao nhất
    foreach ($config as $key => $level) {
        if ($totalSpent >= $level['min_spent']) {
            $newRank = $key;
        }
    }

    // 3. Cập nhật vào DB
    $updateStmt = $conn->prepare("UPDATE users SET total_spent = ?, rank_level = ? WHERE id = ?");
    $updateStmt->bind_param("isi", $totalSpent, $newRank, $user_id);
    $updateStmt->execute();
    $updateStmt->close();
}
