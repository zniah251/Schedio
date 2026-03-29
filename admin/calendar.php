<?php
session_start();
require_once '../config/db.php';

// Kiểm tra quyền Admin/Staff
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    header("Location: login.php");
    exit;
}

// --- LẤY DỮ LIỆU LỊCH TỪ DB ---
$sql = "
    SELECT 
        s.id AS schedule_id,
        s.start_time, 
        s.end_time, 
        s.status AS schedule_status,
        o.id AS order_id, 
        o.title AS content_title,
        u.fullname AS customer_name,
        p.name AS package_name,
        pl.name AS platform_name,
        pl.type AS platform_type
    FROM schedules s
    JOIN post pt ON s.post_id = pt.id
    JOIN orders o ON pt.order_id = o.id
    JOIN users u ON o.user_id = u.id
    JOIN service_option so ON o.service_option_id = so.id
    JOIN package p ON so.package_id = p.id
    JOIN platform pl ON s.platform_id = pl.id
    WHERE s.status != 'cancelled' -- Không hiện lịch đã hủy (tuỳ chọn)
";

$result = $conn->query($sql);
$events = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Xác định màu sắc dựa trên trạng thái
        $className = 'evt-pending'; // Mặc định vàng
        if ($row['schedule_status'] == 'posted') $className = 'evt-completed'; // Xanh lá
        if ($row['schedule_status'] == 'scheduled') $className = 'bg-primary border-primary'; // Xanh dương (Đã lên lịch)

        // Tạo title hiển thị trên lịch (VD: Chloe - Gói 3 - FB)
        $title = $row['customer_name'] . ' - ' . $row['package_name'];

        $events[] = [
            'id' => $row['schedule_id'],
            'title' => $title,
            'start' => $row['start_time'],
            'end' => $row['end_time'], // FullCalendar cần end time để render độ dài
            'className' => $className,
            // Các dữ liệu phụ để hiện trong Modal
            'extendedProps' => [
                'customer' => $row['customer_name'],
                'package' => $row['package_name'],
                'platform' => $row['platform_name'],
                'content_title' => $row['content_title'],
                'orderId' => $row['order_id'],
                'status' => $row['schedule_status']
            ]
        ];
    }
}

// Chuyển mảng PHP sang JSON để JS dùng
$json_events = json_encode($events);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch đăng bài - Schedio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <style>
        #calendar {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            min-height: 800px;
        }

        .fc-toolbar-title {
            font-size: 1.5rem !important;
            color: var(--admin-primary);
            font-weight: 700;
        }

        .fc-button-primary {
            background-color: var(--admin-primary) !important;
            border-color: var(--admin-primary) !important;
        }

        .fc-day-today {
            background-color: #fffdf5 !important;
        }

        /* Màu sắc trạng thái */
        .evt-pending {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        .evt-completed {
            background-color: #198754;
            border-color: #198754;
            color: #fff;
        }

        .evt-scheduled {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .fc-event {
            cursor: pointer;
            padding: 4px;
            font-size: 0.85rem;
            border-radius: 4px;
        }

        .fc-event-time {
            font-weight: 700;
            margin-right: 5px;
        }
    </style>
</head>

<body>

    <div class="admin-wrapper">
        <?php include 'templates/sidebar.php'; ?>

        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary fw-bold mb-0">Lịch đăng bài tổng thể</h2>
                <div>
                    <span class="badge bg-warning text-dark me-2">● Chờ duyệt</span>
                    <span class="badge bg-primary me-2">● Đã lên lịch</span>
                    <span class="badge bg-success me-2">● Đã đăng</span>
                </div>
            </div>

            <div id="calendar"></div>
        </div>
    </div>

    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Chi tiết bài đăng</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small text-muted fw-bold">TIÊU ĐỀ BÀI VIẾT</label>
                        <div class="fs-5 fw-bold text-dark" id="modalTitle">...</div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small text-muted fw-bold">KHÁCH HÀNG</label>
                            <div id="modalCustomer">...</div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small text-muted fw-bold">GÓI DỊCH VỤ</label>
                            <div id="modalPackage">...</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-bold">KÊNH ĐĂNG</label>
                        <div class="text-primary fw-bold" id="modalPlatform">...</div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-bold">THỜI GIAN</label>
                        <div class="text-danger fw-bold" id="modalTime">...</div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="#" id="btnViewOrder" class="btn btn-outline-primary btn-sm">Xem đơn hàng gốc</a>

                        <button type="button" id="btnMarkPosted" class="btn btn-success btn-sm">
                            <i class="bi bi-check2"></i> Đánh dấu Đã đăng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));

            // Nút bấm trong Modal
            var btnMarkPosted = document.getElementById('btnMarkPosted');
            var currentEvent = null; // Biến lưu sự kiện đang được click

            // Nhận dữ liệu JSON từ PHP (đã code ở bước trước)
            var dbEvents = <?php echo $json_events; ?>;

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                locale: 'vi',
                buttonText: {
                    today: 'Hôm nay',
                    month: 'Tháng',
                    week: 'Tuần',
                    list: 'Danh sách'
                },
                navLinks: true,
                dayMaxEvents: true,
                events: dbEvents,

                // XỬ LÝ KHI CLICK VÀO SỰ KIỆN
                eventClick: function(info) {
                    var props = info.event.extendedProps;
                    currentEvent = info.event; // Lưu lại để dùng khi update

                    // 1. Điền thông tin vào Modal
                    document.getElementById('modalTitle').innerText = props.content_title ||
                        'Chưa có tiêu đề';
                    document.getElementById('modalCustomer').innerText = props.customer;
                    document.getElementById('modalPackage').innerText = props.package;
                    document.getElementById('modalPlatform').innerText = props.platform;

                    var timeOptions = {
                        hour: '2-digit',
                        minute: '2-digit',
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    };
                    document.getElementById('modalTime').innerText = info.event.start.toLocaleString(
                        'vi-VN', timeOptions);
                    document.getElementById('btnViewOrder').href = 'order_detail.php?id=' + props
                        .orderId;

                    // 2. Xử lý nút "Đánh dấu Đã đăng"
                    // Nếu trạng thái là 'posted' -> Ẩn nút, ngược lại -> Hiện nút
                    if (props.status === 'posted') {
                        btnMarkPosted.style.display = 'none';
                    } else {
                        btnMarkPosted.style.display = 'inline-block';
                        // Gán ID lịch vào nút để dùng khi click
                        btnMarkPosted.setAttribute('data-schedule-id', info.event.id);
                    }

                    eventModal.show();
                }
            });

            calendar.render();

            // --- SỰ KIỆN CLICK NÚT "ĐÁNH DẤU ĐÃ ĐĂNG" ---
            btnMarkPosted.addEventListener('click', function() {
                var scheduleId = this.getAttribute('data-schedule-id');

                if (!scheduleId) return;

                // Hiệu ứng loading
                var originalText = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';
                this.disabled = true;

                // Gửi AJAX
                fetch('update_schedule_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'id=' + scheduleId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // 1. Cập nhật giao diện trên Lịch (Đổi màu sang xanh)
                            if (currentEvent) {
                                currentEvent.setProp('classNames', ['evt-completed']); // Class màu xanh
                                currentEvent.setExtendedProp('status', 'posted'); // Cập nhật data ngầm
                            }

                            // 2. Ẩn Modal & Reset nút
                            eventModal.hide();
                            alert("Cập nhật thành công!");
                        } else {
                            alert("Lỗi: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("Có lỗi xảy ra khi kết nối server.");
                    })
                    .finally(() => {
                        // Trả lại trạng thái nút cũ
                        this.innerHTML = originalText;
                        this.disabled = false;
                    });
            });
        });
    </script>

</body>

</html>