<?php
// customer/cart_action.php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'add') {
    $id = $_POST['id'];
    $platform = $_POST['platform'];
    $price = $_POST['price'];
    $name = $_POST['name'];
    $slots = $_POST['slots'];

    // Key duy nhất cho mỗi gói trên mỗi nền tảng
    $key = $id . '_' . md5($platform);

    $_SESSION['cart'][$key] = [
        'id' => $id,
        'name' => $name,
        'platform' => $platform,
        'price' => $price,
        'slots_count' => $slots
    ];

    echo json_encode(['status' => 'success', 'count' => count($_SESSION['cart'])]);
}

if ($action == 'clear') {
    unset($_SESSION['cart']);
    echo json_encode(['status' => 'success']);
}

if ($action == 'remove') {
    $id = $_POST['id'];
    $platform = $_POST['platform'];

    // Tạo lại key để tìm đúng gói cần xóa
    $key = $id . '_' . md5($platform);

    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($_SESSION['cart'])
    ]);
}