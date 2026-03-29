<?php
// api/test_models.php
header('Content-Type: application/json');

// --- DÁN API KEY CỦA BẠN VÀO ĐÂY ---
$apiKey = "AIzaSyCkzCD6dSscOmJ9lSOJN_RzchAUbRTRTOM";

// URL lấy danh sách model
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "<h1>Danh sách Model khả dụng cho Key của bạn:</h1>";
if (isset($data['models'])) {
    echo "<ul>";
    foreach ($data['models'] as $model) {
        // Chỉ hiện các model hỗ trợ generateContent (Chat)
        if (in_array("generateContent", $model['supportedGenerationMethods'])) {
            echo "<li><strong>" . $model['name'] . "</strong></li>";
        }
    }
    echo "</ul>";
} else {
    echo "Lỗi: " . $response;
}
