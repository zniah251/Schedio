<?php
// admin/get_package.php
require_once '../config/db.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $pkg_id = intval($_GET['id']);

    // 1. Lấy thông tin gói chính
    $stmt = $conn->prepare("SELECT * FROM package WHERE id = ?");
    $stmt->bind_param("i", $pkg_id);
    $stmt->execute();
    $package = $stmt->get_result()->fetch_assoc();

    if (!$package) {
        echo json_encode(['error' => 'Package not found']);
        exit;
    }

    // 2. Lấy danh sách giá theo từng Platform
    $prices = [];
    $sql_price = "SELECT platform_id, price FROM service_option WHERE package_id = $pkg_id";
    $res_price = $conn->query($sql_price);

    while ($row = $res_price->fetch_assoc()) {
        // Trả về dạng key-value: [platform_id] => price
        $prices[$row['platform_id']] = $row['price'];
    }

    echo json_encode([
        'package' => $package,
        'prices' => $prices
    ]);
} else {
    echo json_encode(['error' => 'No ID provided']);
}
