<?php
// Bắt đầu session để kiểm tra đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'templates/header.php';
?>

<section class="container my-5 py-5">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="display-4 fw-bold mb-3 text-dark-blue">Dịch vụ booking truyền thông âm nhạc
            </h1>
            <p class="lead text-muted mb-4">Schedio giúp bạn sắp xếp và tối ưu nội dung nhanh chóng, giúp bạn tiết kiệm
                thời gian và tăng hiệu quả.</p>
            <a href="./services.php" class="btn btn-warning btn-lg me-3 schedio-btn-yellow">Xem giá</a>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="./register.php" class="btn btn-outline-dark btn-lg schedio-btn-outline">Đăng ký ngay</a>
            <?php endif; ?>
        </div>
        <div class="col-md-6 text-center">
            <img src="assets/images/a.png" alt="Lên lịch bài đăng" class="img-fluid">
        </div>
    </div>
</section>

<section class="container my-5 py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-dark-blue">Vì sao nên chọn Schedio?</h2>
        <p class="text-muted">Giải pháp truyền thông tối ưu dành cho nghệ sĩ Underground/Indie.</p>
    </div>

    <div class="row text-center">
        <div class="col-md-4">
            <div class="p-3">
                <i class="bi bi-calendar-check-fill text-primary display-4 mb-3"></i>
                <h5 class="fw-bold">Đăng lịch hệ thống</h5>
                <p class="text-muted">Lên lịch và quản lý nội dung đăng tải trực quan, dễ dàng.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3">
                <i class="bi bi-clock-fill text-primary display-4 mb-3"></i>
                <h5 class="fw-bold">Tự động hóa tiết kiệm thời gian</h5>
                <p class="text-muted">Quy trình tự động từ đặt lịch đến thanh toán, giúp bạn tập trung sáng tạo.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3">
                <i class="bi bi-bar-chart-fill text-primary display-4 mb-3"></i>
                <h5 class="fw-bold">Hiệu quả truyền thông cao</h5>
                <p class="text-muted">Tiếp cận đúng đối tượng khán giả yêu thích Rap/Hip-hop trên các cộng đồng lớn.</p>
            </div>
        </div>
    </div>
</section>

<section class="process-section py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark-blue">Quy trình Booking đơn giản</h2>
            <p class="text-muted">Chỉ với 4 bước để đưa âm nhạc của bạn đến với khán giả</p>
        </div>

        <div class="row text-center g-4 relative position-relative">
            <div class="d-none d-lg-block position-absolute top-50 start-0 w-100 translate-middle-y border-top border-2 border-warning z-0"
                style="max-width: 80%; left: 10%;"></div>

            <div class="col-lg-3 col-md-6 position-relative z-1">
                <div class="process-step bg-white p-4 rounded-3 shadow-sm h-100">
                    <div
                        class="step-icon-box bg-warning text-dark mb-3 mx-auto rounded-circle d-flex align-items-center justify-content-center shadow">
                        <i class="bi bi-bag-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">1. Chọn gói</h5>
                    <p class="text-muted small mb-0">Lựa chọn các gói dịch vụ phù hợp với ngân sách và thêm vào giỏ
                        hàng.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 position-relative z-1">
                <div class="process-step bg-white p-4 rounded-3 shadow-sm h-100">
                    <div
                        class="step-icon-box bg-primary text-white mb-3 mx-auto rounded-circle d-flex align-items-center justify-content-center shadow">
                        <i class="bi bi-calendar-week-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">2. Chọn lịch</h5>
                    <p class="text-muted small mb-0">Chủ động chọn khung giờ vàng để đăng bài cho từng gói dịch vụ.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 position-relative z-1">
                <div class="process-step bg-white p-4 rounded-3 shadow-sm h-100">
                    <div
                        class="step-icon-box bg-info text-white mb-3 mx-auto rounded-circle d-flex align-items-center justify-content-center shadow">
                        <i class="bi bi-file-earmark-text-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">3. Gửi nội dung</h5>
                    <p class="text-muted small mb-0">Điền thông tin sản phẩm, link nhạc và gửi yêu cầu thiết kế cho
                        Admin.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 position-relative z-1">
                <div class="process-step bg-white p-4 rounded-3 shadow-sm h-100">
                    <div
                        class="step-icon-box bg-success text-white mb-3 mx-auto rounded-circle d-flex align-items-center justify-content-center shadow">
                        <i class="bi bi-qr-code-scan fs-2"></i>
                    </div>
                    <h5 class="fw-bold">4. Duyệt & Thanh toán</h5>
                    <p class="text-muted small mb-0">Duyệt bản demo từ Admin, quét mã QR thanh toán và tận hưởng kết
                        quả.</p>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="text-center mt-5 pt-3">
                <a href="./services.php" class="btn btn-schedio-primary btn-lg px-5 shadow-sm fw-bold rounded-pill">
                    Bắt đầu chiến dịch ngay <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>

<section class="container my-5 py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-dark-blue">Các gói dịch vụ nổi bật</h2>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100 p-4 schedio-card">
                <div class="card-body d-flex flex-column">
                    <h4 class="fw-bold mb-3">Gói 3</h4>
                    <ul class="list-unstyled text-muted flex-grow-1">
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>1 poster sản phẩm</li>
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>1 post trích lyrics
                            highlight</li>
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>1 video highlight</li>
                    </ul>
                    <a href="./package-detail.php?id=goi-3" class="btn btn-warning schedio-btn-yellow mt-3 w-100">Xem
                        chi tiết</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100 p-4 schedio-card">
                <div class="card-body d-flex flex-column">
                    <h4 class="fw-bold mb-3">Gói 4</h4>
                    <ul class="list-unstyled text-muted flex-grow-1">
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>1 poster sản phẩm</li>
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>1 post trích lyrics
                            highlight</li>
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>1 video highlight</li>
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>1 post bình luận về sản
                            phẩm</li>
                    </ul>
                    <a href="./package-detail.php?id=goi-4" class="btn btn-warning schedio-btn-yellow mt-3 w-100">Xem
                        chi tiết</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100 p-4 schedio-card">
                <div class="card-body d-flex flex-column">
                    <h4 class="fw-bold mb-3">Gói 5</h4>
                    <ul class="list-unstyled text-muted flex-grow-1">
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>1 poster sản phẩm</li>
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>1 post trích lyrics
                            highlight</li>
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>1 video highlight</li>
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>2 bài đăng về tin tức/meme
                        </li>
                        <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>2 tuần ghim bài đăng trên
                            page</li>
                    </ul>
                    <a href="./package-detail.php?id=goi-5" class="btn btn-warning schedio-btn-yellow mt-3 w-100">Xem
                        chi tiết</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container my-5 pt-3 pb-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-dark-blue">Hạng thành viên & Ưu đãi độc quyền</h2>
        <p class="text-muted">Hệ thống tự động tích lũy chi tiêu. Thăng hạng càng cao, ưu đãi càng lớn!</p>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm text-center py-4 position-relative overflow-hidden">
                <div class="card-body">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="bi bi-person text-secondary fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-secondary">Level 1</h5>
                    <p class="small text-muted mb-3">Thành viên mới</p>
                    <hr class="w-50 mx-auto opacity-25">
                    <div class="text-start px-3">
                        <p class="mb-1 small"><i class="bi bi-check-circle-fill text-secondary me-2"></i>Chi tiêu:
                            <strong>0đ</strong>
                        </p>
                        <p class="mb-0 small"><i class="bi bi-tag-fill text-secondary me-2"></i>Ưu đãi:
                            <strong>0%</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow text-center py-4 position-relative overflow-hidden">
                <div class="card-body">
                    <div class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="bi bi-mic-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Level 2</h5>
                    <p class="small text-muted mb-3">Tích cực hoạt động</p>
                    <hr class="w-50 mx-auto opacity-25">
                    <div class="text-start px-3">
                        <p class="mb-1 small"><i class="bi bi-check-circle-fill text-dark me-2"></i>Chi tiêu: <strong>>
                                1 Triệu</strong></p>
                        <p class="mb-0 small"><i class="bi bi-tag-fill text-warning me-2"></i>Giảm giá: <strong
                                class="text-danger">5%</strong></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-primary border-2 shadow-sm text-center py-4 position-relative overflow-hidden"
                style="background: #f0f8ff;">
                <div class="card-body">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="bi bi-vinyl-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-primary">Level 3</h5>
                    <p class="small text-muted mb-3">Khách hàng thân thiết</p>
                    <hr class="w-50 mx-auto opacity-25">
                    <div class="text-start px-3">
                        <p class="mb-1 small"><i class="bi bi-check-circle-fill text-primary me-2"></i>Chi tiêu:
                            <strong>> 3 Triệu</strong>
                        </p>
                        <p class="mb-0 small"><i class="bi bi-tag-fill text-warning me-2"></i>Giảm giá: <strong
                                class="text-danger">10%</strong></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-warning border-2 shadow-lg text-center py-4 position-relative overflow-hidden"
                style="background: #fffae6;">
                <div class="position-absolute top-0 end-0 bg-danger text-white px-3 py-1 small fw-bold"
                    style="border-bottom-left-radius: 10px;">VIP</div>
                <div class="card-body">
                    <div class="rounded-circle bg-warning text-dark d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px; box-shadow: 0 0 15px rgba(253, 208, 59, 0.6);">
                        <i class="bi bi-trophy-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Level 4</h5>
                    <p class="small text-muted mb-3">Đẳng cấp ngôi sao</p>
                    <hr class="w-50 mx-auto opacity-25">
                    <div class="text-start px-3">
                        <p class="mb-1 small"><i class="bi bi-check-circle-fill text-dark me-2"></i>Chi tiêu: <strong>>
                                10 Triệu</strong></p>
                        <p class="mb-0 small"><i class="bi bi-tag-fill text-danger me-2"></i>Giảm giá: <strong
                                class="text-danger fs-5">15%</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <p class="fst-italic text-muted small">* Mức giảm giá áp dụng tự động cho mọi đơn hàng khi bạn đạt đủ hạn mức
            chi tiêu.</p>
    </div>
</section>

<?php if (!isset($_SESSION['user_id'])): ?>
    <section class="container my-5 py-5 text-center">
        <h2 class="fw-bold mb-4 text-dark-blue">Bạn đã sẵn sàng tối ưu quy trình quản lý nội dung của mình chưa?</h2>
        <a href="./register.php" class="btn btn-warning btn-lg schedio-btn-yellow">Đăng ký ngay</a>
    </section>
<?php endif; ?>

<?php include 'templates/footer.php'; ?>