<?php
// customer/get_booked_slots.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$platform_name = isset($_GET['platform']) ? $_GET['platform'] : '';

if (empty($platform_name)) {
    echo json_encode([]);
    exit;
}

// 1. Get Platform ID from name
$stmt = $conn->prepare("SELECT id FROM platform WHERE name = ?");
$stmt->bind_param("s", $platform_name);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo json_encode([]);
    exit;
}

$row = $res->fetch_assoc();
$platform_id = $row['id'];
$stmt->close();

// 2. Fetch booked slots
// We join schedules -> post -> orders to optionally get user info (to distinguish my slots vs others)
// Filter by platform_id and exclude cancelled schedules.
$query = "
    SELECT s.start_time, s.end_time, o.user_id
    FROM schedules s
    JOIN post p ON s.post_id = p.id
    JOIN orders o ON p.order_id = o.id
    WHERE s.platform_id = ? AND s.status != 'cancelled'
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $platform_id);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $is_my_slot = ($row['user_id'] == $current_user_id);

    $events[] = [
        'id' => 'booked_slot_' . $row['start_time'], // Unique ID helps with rendering
        'start' => $row['start_time'],
        'end' => $row['end_time'],
        'display' => 'background', // Critical: Renders as a background color, not a block event
        'color' => $is_my_slot ? '#198754' : '#dc3545', // Green for mine, Red for others
        'title' => $is_my_slot ? 'Bạn đã đặt' : 'Đã bận',
        'overlap' => false, // Critical: Prevents other events from being dragged/resized over this
        'classNames' => $is_my_slot ? ['my-booked-slot'] : ['other-booked-slot']
    ];
}

echo json_encode($events);
$stmt->close();
$conn->close();
