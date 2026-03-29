<?php
if (!isset($base_url)) {
    $base_url = '/Schedio'; // Đảm bảo đường dẫn này đúng với project của bạn
}
?>

</main>
<footer class="py-5 schedio-footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-5 col-md-12 mb-4">
                <h5 class="fw-bold fs-3 mb-3">Schedio</h5>
                <p class="text-muted">Email: grft9.contact@gmail.com</p>
                <p class="text-muted">Hotline: 0344 377 104</p>
                <div>
                    <a href="#" class="text-muted fs-4 me-3"><i class="bi bi-tiktok"></i></a>
                    <a href="#" class="text-muted fs-4"><i class="bi bi-facebook"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="fw-bold text-uppercase mb-3">Về Schedio</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo $base_url; ?>/about.php"
                            class="text-muted text-decoration-none">Giới thiệu</a></li>
                    <li class="mb-2"><a href="<?php echo $base_url; ?>/services.php"
                            class="text-muted text-decoration-none">Bảng giá</a></li>
                    <li class="mb-2"><a href="<?php echo $base_url; ?>/contact.php"
                            class="text-muted text-decoration-none">Liên hệ</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <h6 class="fw-bold text-uppercase mb-3">Các kênh truyền thông</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="https://www.facebook.com/grabfanthang9"
                            class="text-muted text-decoration-none">Page Grab Fan Tháng 9</a></li>
                    <li class="mb-2"><a href="https://www.facebook.com/rapfanthamthinh"
                            class="text-muted text-decoration-none">Page Rap Fan Thám Thính</a></li>
                    <li class="mb-2"><a href="https://www.tiktok.com/@grabfanthang9"
                            class="text-muted text-decoration-none">TikTok Grab Fan Tháng 9</a></li>
                    <li class="mb-2"><a href="https://www.facebook.com/groups/8276007849195211"
                            class="text-muted text-decoration-none">Group Cộng đồng Grab Việt Underground</a></li>
                </ul>
            </div>
        </div>

        <hr>
        <div class="text-center text-muted small">
            &copy; <?php echo date('Y'); ?> Schedio. All Rights Reserved.
        </div>
    </div>
</footer>
<div id="schedio-chatbot">
    <button id="chat-toggle-btn" class="shadow-lg">
        <i class="bi bi-chat-dots-fill fs-4"></i>
    </button>

    <div id="chat-window" class="shadow-lg d-none">
        <div class="chat-header d-flex justify-content-between align-items-center p-3">
            <div class="d-flex align-items-center">
                <div class="bg-white rounded-circle p-1 me-2">
                    <img src="https://cdn-icons-png.flaticon.com/512/4712/4712027.png" width="30">
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-white">Schedio Support</h6>
                    <small class="text-white-50" style="font-size: 11px;">Luôn sẵn sàng 24/7</small>
                </div>
            </div>
            <button id="chat-close-btn" class="btn btn-sm text-white"><i class="bi bi-x-lg"></i></button>
        </div>

        <div id="chat-messages" class="p-3">
            <div class="message bot-message mb-2">
                Yo! Chào homie 🤟. Schedio có thể giúp gì cho bạn hôm nay? <br>
                Bạn muốn hỏi giá hay cách booking?
            </div>
        </div>

        <div class="chat-input-area p-2 border-top d-flex">
            <input type="text" id="user-input" class="form-control border-0" placeholder="Nhập tin nhắn..."
                autocomplete="off">
            <button id="send-btn" class="btn btn-warning text-white ms-2 rounded-circle">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// 1. HÀM XỬ LÝ ĐÓNG TOAST (AJAX) - Đặt ngoài ready()
function closeToast(notificationId) {
    const toastElement = document.getElementById('latestToast');
    if (toastElement) {
        toastElement.classList.remove('show');
        toastElement.classList.add('hide');
    }

    $.ajax({
        url: '<?php echo $base_url; ?>/customer/mark_read.php',
        type: 'POST',
        data: {
            id: notificationId
        },
        success: function(response) {
            console.log("Đã đánh dấu đã đọc.");
        },
        error: function() {
            console.error("Lỗi cập nhật trạng thái thông báo");
        }
    });
}

// 2. LOGIC CHATBOT & UI
$(document).ready(function() {
    const chatWindow = $('#chat-window');
    const messagesContainer = $('#chat-messages');
    const userInput = $('#user-input');

    // Toggle Chat
    $('#chat-toggle-btn, #chat-close-btn').click(function() {
        chatWindow.toggleClass('d-none');
    });

    // Gửi tin nhắn
    function sendMessage() {
        const text = userInput.val().trim();
        if (!text) return;

        messagesContainer.append(`<div class="message user-message mb-2">${text}</div>`);
        userInput.val('');
        scrollToBottom();

        const loadingId = 'loading-' + Date.now();
        messagesContainer.append(
            `<div id="${loadingId}" class="message bot-message mb-2 text-muted fst-italic"><small>Đang soạn tin...</small></div>`
        );
        scrollToBottom();

        $.ajax({
            url: '<?php echo $base_url; ?>/api/chat_gemini.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                message: text
            }),
            success: function(res) {
                $(`#${loadingId}`).remove();
                let botHtml = res.reply.replace(/\n/g, '<br>');
                botHtml = botHtml.replace(/(\d+k)/gi, '<strong class="text-danger">$1</strong>');
                messagesContainer.append(`<div class="message bot-message mb-2">${botHtml}</div>`);
                scrollToBottom();
            },
            error: function() {
                $(`#${loadingId}`).remove();
                messagesContainer.append(
                    `<div class="message bot-message mb-2 text-danger">Lỗi kết nối server AI.</div>`
                );
            }
        });
    }

    $('#send-btn').click(sendMessage);
    userInput.keypress(function(e) {
        if (e.which == 13) sendMessage();
    });

    function scrollToBottom() {
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }
});
</script>
</body>

</html>