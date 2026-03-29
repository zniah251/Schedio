<?php
// 1. TẮT BÁO LỖI RÁC (Để không làm hỏng định dạng JSON trả về)
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../config/db.php';
require_once '../config/functions.php'; // File chứa hàm tính giá và xếp hạng

header('Content-Type: application/json');

// 2. KIỂM TRA ĐẦU VÀO
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if (!isset($_POST['common_info']) || !isset($_POST['packages'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu gửi lên không hợp lệ']);
    exit;
}

$user_id = $_SESSION['user_id'];
$info = $_POST['common_info'];
$packages = $_POST['packages'];

// 3. LẤY RANK HIỆN TẠI CỦA USER
$user_rank = 'newbie';
$r_query = $conn->query("SELECT rank_level FROM users WHERE id = $user_id");
if ($r_query && $r_row = $r_query->fetch_assoc()) {
    $user_rank = $r_row['rank_level'];
}

$conn->begin_transaction();

try {
    foreach ($packages as $pkg) {
        $pkg_name = $pkg['name'];
        $plt_name = $pkg['platform'];
        $slots = $pkg['slots'];

        // --- BƯỚC 1: TÌM ID GÓI (PACKAGE) ---
        $s1 = $conn->prepare("SELECT id FROM package WHERE name = ? LIMIT 1");
        $s1->bind_param("s", $pkg_name);
        $s1->execute();
        $res1 = $s1->get_result();
        if ($res1->num_rows === 0) throw new Exception("Không tìm thấy gói: $pkg_name");
        $pid = $res1->fetch_assoc()['id'];
        $s1->close();

        // --- BƯỚC 2: TÌM ID KÊNH (PLATFORM) - CÓ CƠ CHẾ DỰ PHÒNG ---
        $plid = 0;

        // Cách 1: Tìm chính xác theo tên
        $s2 = $conn->prepare("SELECT id FROM platform WHERE name = ? LIMIT 1");
        $s2->bind_param("s", $plt_name);
        $s2->execute();
        $res2 = $s2->get_result();

        if ($res2->num_rows > 0) {
            $plid = $res2->fetch_assoc()['id'];
        } else {
            // Cách 2: Tìm theo từ khóa (Fix lỗi sai lệch tên/encoding)
            // Lưu ý: Các ID ở đây (1,2,3,4) phải khớp với Database của bạn
            if (strpos($plt_name, 'GROUP') !== false || strpos($plt_name, 'UNDERGROUND') !== false) {
                $plid = 3; // ID của Group Cộng Đồng
            } elseif (strpos($plt_name, 'THÁNG 9') !== false) {
                $plid = 1; // ID của Page Tháng 9
            } elseif (strpos($plt_name, 'THÁM THÍNH') !== false) {
                $plid = 2; // ID của Page Thám Thính
            } elseif (strpos($plt_name, 'TIKTOK') !== false) {
                $plid = 4; // ID Tiktok (nếu có)
            } else {
                throw new Exception("Không tìm thấy kênh: " . $plt_name);
            }
        }
        $s2->close();

        // --- BƯỚC 3: LẤY GIÁ GỐC ---
        $s3 = $conn->prepare("SELECT id, price FROM service_option WHERE package_id=? AND platform_id=? LIMIT 1");
        $s3->bind_param("ii", $pid, $plid);
        $s3->execute();
        $res3 = $s3->get_result();
        if ($res3->num_rows === 0) throw new Exception("Chưa cấu hình giá cho $pkg_name trên kênh này.");
        $opt = $res3->fetch_assoc();
        $s3->close();

        // --- BƯỚC 4: TÍNH GIÁ ĐÃ GIẢM ---
        $original_price = $opt['price'];
        // Ép kiểu (int) để đảm bảo không lỗi SQL khi lưu
        $final_price = (int) calculateDiscountedPrice($original_price, $user_rank);

        // --- BƯỚC 5: TẠO ĐƠN HÀNG (ORDER) ---
        $stmt = $conn->prepare("INSERT INTO orders (user_id, service_option_id, price_at_purchase, title, product_link, content_url, note, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

        // Bind param: 'i' cho final_price (số nguyên)
        $stmt->bind_param("iiissss", $user_id, $opt['id'], $final_price, $info['title'], $info['prod'], $info['drive'], $info['note']);

        if (!$stmt->execute()) {
            throw new Exception("Lỗi tạo đơn hàng: " . $stmt->error);
        }
        $order_id = $conn->insert_id;
        $stmt->close();

        // --- BƯỚC 6: TẠO LỊCH (SLOTS) ---
        foreach ($slots as $iso) {
            // Tạo Post
            $conn->query("INSERT INTO post (order_id, status) VALUES ($order_id, 'pending')");
            $post_id = $conn->insert_id;

            // Tạo Schedule
            $start = date('Y-m-d H:i:s', strtotime($iso));
            $end = date('Y-m-d H:i:s', strtotime($iso) + 3600); // Mặc định 1 slot = 1 tiếng
            $s4 = $conn->prepare("INSERT INTO schedules (post_id, platform_id, start_time, end_time, status) VALUES (?, ?, ?, ?, 'pending')");
            $s4->bind_param("iiss", $post_id, $plid, $start, $end);

            if (!$s4->execute()) {
                // Nếu trùng lịch (Duplicate entry) thì báo lỗi
                throw new Exception("Trùng lịch tại " . date('H:i d/m', strtotime($start)));
            }
            $s4->close();
        }
    }

    // Xóa giỏ hàng sau khi đặt xong
    unset($_SESSION['cart']);

    $conn->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $conn->rollback();
    // Trả về lỗi chuẩn JSON để Javascript hiển thị alert()
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
