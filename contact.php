<?php
// contact.php
include 'templates/header.php'; 

// Xử lý gửi mail
$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    // Ở đây bạn có thể tích hợp PHPMailer để gửi thật.
    // Hiện tại mình sẽ giả lập gửi thành công.
    
    // Ví dụ logic gửi mail đơn giản (thường bị chặn nếu host chưa cấu hình):
    // $to = "grft9.contact@gmail.com";
    // $subject = "Liên hệ mới từ $first_name $last_name";
    // $body = "Email: $email\n\nNội dung:\n$message";
    // mail($to, $subject, $body);

    $message_sent = true;
}
?>

<div class="container my-5">
    <div class="row">

        <div class="col-lg-6 mb-5 mb-lg-0">
            <div class="contact-info-wrapper">
                <h1 class="contact-heading">
                    <i class="bi bi-envelope-paper-fill me-3"></i>
                    Liên hệ Schedio
                </h1>

                <p class="lead text-muted mb-4">
                    Chúng tôi luôn sẵn sàng hỗ trợ bạn – từ thắc mắc về gói dịch vụ đến hợp tác truyền thông.
                </p>

                <div class="contact-details">
                    <p>Hãy để lại thông tin hoặc gửi tin nhắn cho chúng tôi, đội ngũ Schedio sẽ phản hồi trong thời gian
                        sớm nhất. Bạn cũng có thể liên hệ trực tiếp qua:</p>
                    <ul class="list-unstyled mt-3">
                        <li class="mb-2"><i class="bi bi-envelope-fill me-2 text-primary"></i> <strong>Email:</strong>
                            grft9.contact@gmail.com</li>
                        <li class="mb-2"><i class="bi bi-telephone-fill me-2 text-primary"></i>
                            <strong>Hotline:</strong> 0344 377 104</li>
                        <li class="mb-2"><i class="bi bi-clock-fill me-2 text-primary"></i> <strong>Giờ làm
                                việc:</strong> 9:00 – 18:00 (Thứ Hai – Thứ Sáu)</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="contact-form-wrapper p-4 p-md-5 shadow-sm rounded">
                <h3 class="fw-bold mb-4">Gửi tin nhắn cho chúng tôi</h3>

                <?php if ($message_sent): ?>
                <div class="alert alert-success">
                    Cảm ơn bạn! Tin nhắn của bạn đã được gửi thành công. Chúng tôi sẽ liên hệ lại sớm nhất.
                </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstName" class="form-label-schedio">Tên</label>
                            <input type="text" class="form-control form-control-schedio" id="firstName"
                                name="first_name" placeholder="Ví dụ: Lan" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastName" class="form-label-schedio">Họ</label>
                            <input type="text" class="form-control form-control-schedio" id="lastName" name="last_name"
                                placeholder="Ví dụ: Nguyễn" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label-schedio">Địa chỉ Email</label>
                        <input type="email" class="form-control form-control-schedio" id="email" name="email"
                            placeholder="name@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label-schedio">Nội dung tin nhắn</label>
                        <textarea class="form-control form-control-schedio" id="message" name="message" rows="5"
                            placeholder="Nhập câu hỏi hoặc nội dung cần hỗ trợ..." required></textarea>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-schedio-primary">Gửi tin nhắn</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="map-wrapper mt-5 shadow-sm rounded text-center">
            <h3 class="fw-bold mb-4">Vị trí của chúng tôi</h3>
            <p class="text-muted mb-4"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Hàn Thuyên, khu phố 6, P.Linh
                Trung, Thủ Đức, Thành phố Hồ Chí Minh</p>
            <div class="map-responsive">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3918.231240416692!2d106.8004791748063!3d10.870008889284588!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317527587e9ad5bf%3A0xafa66f9c8be3c91!2zVHLGsOG7nW5nIMSQ4bqhaSBo4buNYyBDw7RuZyBuZ2jhu4cgVGjDtG5nIHRpbiAtIMSQSFFHIFRQLkhDTQ!5e0!3m2!1svi!2s!4v1715485000000!5m2!1svi!2s"
                    width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>

    </div>
</div>

<?php include 'templates/footer.php'; ?>