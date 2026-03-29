<?php
// api/chat_gemini.php
header('Content-Type: application/json');
session_start();
$apiKey = "AIzaSyCkzCD6dSscOmJ9lSOJN_RzchAUbRTRTOM";

// 1. Nhận dữ liệu
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Lỗi: Tin nhắn rỗng.']);
    exit;
}

// 2. System Prompt
$systemContext = "
Bạn là nhân viên tư vấn của Schedio (Dịch vụ booking truyền thông Rap/Hip-hop).
Phong cách: Thân thiện, ngắn gọn, đậm chất Underground/Hiphop.
Thông tin gói dịch vụ:
- Gói 1A (100k): 1 Poster lên Page Grab Fan Tháng 9.
- Gói 1B (100k): 1 Video highlight lên Page Grab Fan Tháng 9.
- Gói 1C (100k): Share link sản phẩm từ Youtube/Soundcloud.
- Gói 2 (200k): Combo Poster + Video lên Page Tháng 9 & Thám Thính.
- Gói 3 (300k): Đăng Group Cộng Đồng Grab Việt Underground.
- Gói 5 (700k): Gói Superstar (Full kênh + Ghim bài 2 tuần).
Lưu ý: Chỉ trả lời về dịch vụ của Schedio. Nếu khách hỏi số tài khoản, hướng dẫn họ đặt lịch trên web.
";

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $apiKey;
// 4. Cấu trúc dữ liệu
$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $systemContext . "\n\nKhách hàng hỏi: " . $userMessage]
            ]
        ]
    ]
];

// 5. Gửi CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6. Xử lý kết quả
if ($httpCode == 200) {
    $decoded = json_decode($response, true);
    if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
        $botReply = $decoded['candidates'][0]['content']['parts'][0]['text'];
    } else {
        $botReply = "Yo, hệ thống đang lag nhẹ. Hỏi lại nhé homie!";
    }
    echo json_encode(['reply' => $botReply]);
} elseif ($httpCode == 429) {
    // Xử lý riêng lỗi quá hạn ngạch
    echo json_encode(['reply' => "Hệ thống đang quá tải (Lỗi 429). Vui lòng đợi 1 phút rồi thử lại nhé!"]);
} else {
    // Báo lỗi chi tiết khác
    $jsonErr = json_decode($response, true);
    $msg = $jsonErr['error']['message'] ?? 'Lỗi không xác định';
    echo json_encode(['reply' => "Lỗi kết nối ($httpCode): $msg"]);
}