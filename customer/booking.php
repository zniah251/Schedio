<?php
// customer/booking.php
session_start();
require_once '../config/db.php'; 
require_once '../config/functions.php'; 

// Kiểm tra giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: ../services.php");
    exit;
}

// --- LẤY THÔNG TIN RANK CỦA USER ---
$user_id = $_SESSION['user_id'] ?? 0;
$user_rank = 'Level 1'; // Mặc định
if($user_id > 0) {
    $stmt = $conn->prepare("SELECT rank_level FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        $user_rank = $row['rank_level'];
    }
    $stmt->close();
}
$rankConfig = getMembershipConfig()[$user_rank];
// ---------------------------------------------

// Chuyển đổi dữ liệu giỏ hàng để dùng trong JS
$cart_items = array_values($_SESSION['cart']);
$json_cart = json_encode($cart_items);

include '../templates/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-6 fw-bold text-dark-blue">
                Thiết lập lịch đăng
                <span class="badge bg-<?php echo $rankConfig['badge_color']; ?> fs-6 align-middle ms-2">
                    <i class="bi <?php echo $rankConfig['icon']; ?>"></i> <?php echo $rankConfig['name']; ?>
                </span>
            </h1>

            <?php if($rankConfig['discount_percent'] > 0): ?>
            <p class="text-success fw-bold mb-1">
                <i class="bi bi-gift-fill"></i> Bạn đang được giảm <?php echo $rankConfig['discount_percent']; ?>% trên
                tổng đơn hàng!
            </p>
            <?php else: ?>
            <p class="text-muted">Tích lũy thêm để nhận ưu đãi thành viên.</p>
            <?php endif; ?>
        </div>
        <button class="btn btn-schedio-primary btn-lg px-5 shadow-sm" id="btn-pre-check">
            Hoàn tất & Điền thông tin <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3 text-uppercase small text-muted border-bottom">
                    Danh sách gói cần đặt
                </div>
                <div class="list-group list-group-flush" id="package-list-group">
                </div>
            </div>

            <div class="alert alert-info mt-3 small border-0 bg-light-blue">
                <i class="bi bi-info-circle-fill me-1"></i>
                Click vào từng gói để chọn lịch tương ứng trên bảng bên phải. Các ô màu đỏ là giờ đã có người đặt.
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card p-4 shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div>
                        <h4 class="fw-bold text-primary mb-0" id="current-package-title">Loading...</h4>
                        <small class="text-muted" id="current-platform-name">...</small>
                    </div>
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill" id="slot-counter">0 / 0
                        slot</span>
                </div>

                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="infoModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content schedio-modal-content p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark-blue mb-0">Thông tin chiến dịch</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                <div>
                    Bạn đã chọn đủ lịch cho tất cả các gói. <br>
                    Vui lòng cung cấp thông tin sản phẩm để chúng tôi chuẩn bị nội dung.
                </div>
            </div>

            <form id="multiBookingForm">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold text-dark-blue">Tiêu đề nội dung / Tên bài hát <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-modal" id="contentTitle" required
                            placeholder="VD: MV Em Của Ngày Hôm Qua - Sơn Tùng M-TP">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-dark-blue">Link Drive (Source) <span
                                class="text-danger">*</span></label>
                        <input type="url" class="form-control form-control-modal" id="driveLink" required
                            placeholder="https://drive.google.com/...">
                        <div class="form-text small">Chứa hình ảnh, video, assets cần thiết.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-dark-blue">Link Sản phẩm (Public)</label>
                        <input type="url" class="form-control form-control-modal" id="productLink" required
                            placeholder="Youtube, Spotify, SoundCloud...">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold text-dark-blue">Ghi chú chung cho Admin</label>
                        <textarea class="form-control form-control-modal" id="orderNote" rows="3"
                            placeholder="VD: Tông màu chủ đạo là đen đỏ, vui lòng đăng đúng giờ..."></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                    <button type="button" class="btn btn-white px-4 py-2" data-bs-dismiss="modal">Quay lại chỉnh
                        lịch</button>
                    <button type="submit" class="btn btn-schedio-primary px-5 py-2 fw-bold" id="btn-confirm-all">
                        XÁC NHẬN ĐẶT TẤT CẢ
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- KHỞI TẠO DỮ LIỆU ---
    let cartData = <?php echo $json_cart; ?>;
    let bookings = {};
    cartData.forEach((item, index) => {
        bookings[index] = [];
    });

    let currentIndex = 0;
    const calendarEl = document.getElementById('calendar');
    const infoModal = new bootstrap.Modal(document.getElementById('infoModal'));

    // --- RENDER SIDEBAR ---
    const renderPackageList = () => {
        const listEl = document.getElementById('package-list-group');
        listEl.innerHTML = '';

        if (cartData.length === 0) {
            alert("Giỏ hàng trống. Vui lòng quay lại chọn gói.");
            window.location.href = "../services.php";
            return;
        }

        cartData.forEach((item, index) => {
            const isSelected = index === currentIndex;
            const currentCount = bookings[index] ? bookings[index].length : 0;
            const maxCount = parseInt(item.slots_count);
            const isFull = currentCount === maxCount;

            let itemClass = 'list-group-item list-group-item-action py-3 position-relative';
            let badgeClass = 'bg-secondary';

            if (isSelected) {
                itemClass += ' active-package border-start border-5 border-primary bg-light';
                badgeClass = 'bg-primary';
            }
            if (isFull) {
                badgeClass = 'bg-success';
            }

            const html = `
                <div class="${itemClass}" onclick="switchPackage(${index})" style="cursor: pointer;">
                    <div class="d-flex w-100 justify-content-between align-items-center mb-1 pe-4">
                        <h6 class="mb-0 fw-bold ${isSelected ? 'text-primary' : 'text-dark'}">${item.name}</h6>
                        <span class="badge ${badgeClass} rounded-pill">${currentCount}/${maxCount}</span>
                    </div>
                    <small class="text-muted d-block text-truncate" style="max-width: 85%;">${item.platform}</small>
                    
                    <button class="btn btn-sm text-danger position-absolute top-50 end-0 translate-middle-y me-2 border-0" 
                            onclick="removePackage(event, ${index})" title="Xóa gói này">
                        <i class="bi bi-trash3-fill fs-5"></i>
                    </button>
                </div>
            `;
            listEl.innerHTML += html;
        });

        updateHeaderInfo();
    };

    const updateHeaderInfo = () => {
        if (cartData.length === 0) return;
        if (currentIndex >= cartData.length) currentIndex = 0;

        const currentItem = cartData[currentIndex];
        document.getElementById('current-package-title').innerText = currentItem.name;
        document.getElementById('current-platform-name').innerText = currentItem.platform;

        const count = bookings[currentIndex] ? bookings[currentIndex].length : 0;
        const max = currentItem.slots_count;
        const badge = document.getElementById('slot-counter');
        badge.innerText = `${count} / ${max} slot`;
        badge.className = count === parseInt(max) ?
            "badge bg-success fs-6 px-3 py-2 rounded-pill" :
            "badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill";
    }

    // --- XÓA GÓI ---
    window.removePackage = function(event, index) {
        event.stopPropagation();
        const itemToRemove = cartData[index];

        if (!confirm(`Bạn có chắc chắn muốn xóa gói "${itemToRemove.name}" này không?`)) return;

        $.ajax({
            url: 'cart_action.php',
            type: 'POST',
            data: {
                action: 'remove',
                id: itemToRemove.id,
                platform: itemToRemove.platform
            },
            success: function(res) {
                cartData.splice(index, 1);
                let newBookings = {};
                let oldBookingsArr = Object.values(bookings);
                oldBookingsArr.splice(index, 1);
                oldBookingsArr.forEach((slots, i) => {
                    newBookings[i] = slots;
                });
                bookings = newBookings;

                if (cartData.length === 0) {
                    alert('Giỏ hàng trống. Quay về trang Bảng giá.');
                    window.location.href = '../services.php';
                } else {
                    if (index === currentIndex) currentIndex = 0;
                    else if (index < currentIndex) currentIndex--;
                    renderPackageList();
                    calendar.refetchEvents();
                }
            }
        });
    }

    // --- CẤU HÌNH FULLCALENDAR ---
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'vi',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek'
        },
        slotMinTime: '07:00:00',
        slotMaxTime: '23:00:00',
        slotDuration: '00:30:00',
        allDaySlot: false,
        nowIndicator: true,
        height: '650px',

        // 1. TẢI EVENT (SLOT BẬN + SLOT ĐANG CHỌN)
        events: function(fetchInfo, successCallback, failureCallback) {
            if (cartData.length === 0) {
                successCallback([]);
                return;
            }

            const currentPkg = cartData[currentIndex];
            const platformName = currentPkg.platform;

            // Gọi API lấy slot bận từ DB
            $.ajax({
                url: 'get_booked_slots.php',
                data: {
                    platform: platformName
                },
                dataType: 'json',
                success: function(bookedSlots) {
                    // Lấy slot đang chọn (màu xanh) từ JS local
                    const mySelectedSlots = (bookings[currentIndex] || []).map(
                        isoDate => ({
                            start: isoDate,
                            end: new Date(new Date(isoDate).getTime() + 30 *
                                60000).toISOString(),
                            display: 'block',
                            color: '#0d6efd',
                            title: 'Đang chọn',
                            classNames: ['my-selected-slot']
                        }));

                    // Gộp 2 mảng lại
                    const allEvents = bookedSlots.concat(mySelectedSlots);
                    successCallback(allEvents);
                },
                error: function() {
                    console.error('Lỗi tải dữ liệu lịch.');
                    failureCallback();
                }
            });
        },

        // 2. XỬ LÝ CLICK CHỌN SLOT
        dateClick: function(info) {
            if (cartData.length === 0) return;
            const max = parseInt(cartData[currentIndex].slots_count);

            // Check thời gian (Cách 12h)
            if (info.date < new Date(Date.now() + 12 * 3600 * 1000)) {
                alert('Vui lòng chọn giờ cách hiện tại ít nhất 12 tiếng.');
                return;
            }

            // --- QUAN TRỌNG: CHECK TRÙNG VỚI SLOT BẬN ---
            const allEvents = calendar.getEvents();
            const clickStart = info.date;
            const clickEnd = new Date(clickStart.getTime() + 30 * 60000); // +30 phút

            let isBusy = false;
            for (let ev of allEvents) {
                // Chỉ check overlap với slot background (slot đã booked)
                if (ev.display === 'background') {
                    if (clickStart < ev.end && clickEnd > ev.start) {
                        isBusy = true;
                        break;
                    }
                }
            }

            if (isBusy) {
                alert('Khung giờ này đã có người đặt. Vui lòng chọn giờ khác.');
                return;
            }
            // ------------------------------------------------

            // Check số lượng
            if (bookings[currentIndex].length >= max) {
                const nextIndex = cartData.findIndex((item, i) => (bookings[i] ? bookings[i]
                    .length : 0) < parseInt(item.slots_count));
                if (nextIndex !== -1 && confirm(
                        `Gói này đã đủ slot. Bạn có muốn chuyển sang chọn lịch cho "${cartData[nextIndex].name}" không?`
                    )) {
                    switchPackage(nextIndex);
                } else {
                    alert('Gói này đã chọn đủ slot.');
                }
                return;
            }

            // Thêm vào mảng và reload lịch
            bookings[currentIndex].push(info.dateStr);
            calendar.refetchEvents();
            renderPackageList();
        },

        // 3. XỬ LÝ CLICK BỎ CHỌN
        eventClick: function(info) {
            // Chỉ cho xóa slot "Đang chọn" (title match)
            if (info.event.title === 'Đang chọn') {
                if (confirm('Bỏ chọn slot này?')) {
                    const clickTime = info.event.start.getTime();
                    bookings[currentIndex] = bookings[currentIndex].filter(iso => new Date(iso)
                        .getTime() !== clickTime);
                    calendar.refetchEvents();
                    renderPackageList();
                }
            }
        }
    });

    calendar.render();
    renderPackageList();

    window.switchPackage = function(index) {
        currentIndex = index;
        renderPackageList();
        calendar.refetchEvents(); // Quan trọng: Load lại event cho platform mới
    };

    // --- NÚT PRE-CHECK ---
    $('#btn-pre-check').click(function() {
        let notFullPackage = null;
        for (let i = 0; i < cartData.length; i++) {
            const current = bookings[i] ? bookings[i].length : 0;
            if (current < parseInt(cartData[i].slots_count)) {
                notFullPackage = cartData[i].name;
                switchPackage(i);
                break;
            }
        }

        if (notFullPackage) {
            alert(`Bạn chưa chọn đủ lịch cho gói "${notFullPackage}".`);
        } else {
            infoModal.show();
        }
    });

    // --- SUBMIT FORM ---
    $('#multiBookingForm').on('submit', function(e) {
        e.preventDefault();
        const title = $('#contentTitle').val().trim();
        const drive = $('#driveLink').val().trim();
        const prod = $('#productLink').val().trim();
        const note = $('#orderNote').val().trim();

        if (!drive.includes('drive.google.com')) {
            alert('Link Drive không hợp lệ (Phải là Google Drive).');
            return;
        }

        const submitBtn = $('#btn-confirm-all');
        submitBtn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...');

        $.ajax({
            url: 'process_booking.php',
            type: 'POST',
            data: {
                common_info: {
                    title,
                    drive,
                    prod,
                    note
                },
                packages: cartData.map((pkg, i) => ({
                    name: pkg.name,
                    platform: pkg.platform,
                    slots: bookings[i]
                }))
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    alert('Đặt thành công tất cả các gói! Vui lòng chờ Admin phản hồi.');
                    window.location.href = 'account.php?tab=orders';
                } else {
                    alert('Lỗi: ' + res.message);
                    submitBtn.prop('disabled', false).text('XÁC NHẬN ĐẶT TẤT CẢ');
                }
            },
            error: function() {
                alert('Lỗi kết nối server.');
                submitBtn.prop('disabled', false).text('XÁC NHẬN ĐẶT TẤT CẢ');
            }
        });
    });
});
</script>