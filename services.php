<?php
session_start();
require_once 'config/db.php';
include 'templates/header.php';

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$is_logged_in = isset($_SESSION['user_id']); // Biến kiểm tra đăng nhập

// Lấy dữ liệu gói
$sql = "SELECT * FROM package WHERE active = 1 ORDER BY id ASC";
$result = $conn->query($sql);

// Hàm render nút bấm (Chỉ gọi khi đã đăng nhập)
function renderCartButton($slug, $name, $platform, $price, $slots)
{
    $key = $slug . '_' . md5($platform);
    $isAdded = isset($_SESSION['cart'][$key]);

    $btnClass = $isAdded ? 'btn-secondary' : 'btn-outline-warning';
    $btnText = $isAdded ? 'Đã chọn' : '+ Chọn';

    echo "<button class='btn btn-sm $btnClass mt-2 btn-toggle-cart' 
            data-added='" . ($isAdded ? 'true' : 'false') . "'
            onclick=\"toggleCart(this, '$slug', '$name', '$platform', '$price', $slots)\">
            $btnText
          </button>";
}
?>

<section class="hero-banner">
    <div class="container text-center text-white">
        <h1 class="display-4 fw-bold">Các gói dịch vụ của chúng tôi</h1>
        <p class="lead">Chọn các gói dịch vụ phù hợp để tối ưu chiến dịch truyền thông của bạn.</p>
    </div>
</section>

<section class="container-fluid px-4 my-5 py-5">

    <?php if ($is_logged_in): ?>
        <div id="floating-cart" class="<?php echo $cart_count > 0 ? '' : 'd-none'; ?>">
            <a href="customer/booking.php" class="btn btn-schedio-primary shadow-lg py-3 px-4 rounded-pill">
                <i class="bi bi-cart-check-fill me-2"></i>
                Tiến hành đặt lịch (<span id="cart-count"><?php echo $cart_count; ?></span> gói)
            </a>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center shadow-sm mb-5" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Bạn cần <strong>đăng nhập</strong> tài khoản khách hàng để có thể chọn gói và đặt lịch.
            <a href="login.php" class="btn btn-sm btn-dark ms-3">Đăng nhập ngay</a>
        </div>
    <?php endif; ?>

    <div class="table-responsive schedio-pricing-table shadow-sm">
        <table class="table table-striped mb-0 table-hover">
            <thead class="table-schedio-header text-center align-middle">
                <tr>
                    <th style="width: 25%;">GÓI</th>
                    <th style="width: 20%;">PAGE GRAB FAN<br>THÁNG 9</th>
                    <th style="width: 20%;">PAGE RAP FAN<br>THÁM THÍNH</th>
                    <th style="width: 20%;">GROUP CỘNG ĐỒNG<br>GRAB VIỆT UNDERGROUND</th>
                    <th class="text-center" style="width: 15%;">CHI TIẾT</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0):
                    while ($pkg = $result->fetch_assoc()):
                        // Tách tính năng
                        $features = explode("\n", $pkg['description']);

                        // --- ĐOẠN SỬA ĐỔI: LẤY GIÁ THEO ID (CHÍNH XÁC 100%) ---
                        $prices = [];
                        // Lấy thêm cột platform_id
                        $sql_p = "SELECT so.price, so.platform_id 
                      FROM service_option so 
                      WHERE so.package_id = " . $pkg['id'];

                        $res_p = $conn->query($sql_p);
                        while ($row = $res_p->fetch_assoc()) {
                            // Lưu vào mảng với Key là ID của Platform (1, 2, 3)
                            $prices[$row['platform_id']] = ($row['price'] >= 1000) ? number_format($row['price'] / 1000) . 'k' : $row['price'];
                        }

                        // Gán biến dựa trên ID (Khớp với DB bạn đã import)
                        // ID 1 = PAGE GRAB FAN THÁNG 9
                        $p1 = $prices[1] ?? '--';
                        // ID 2 = PAGE RAP FAN THÁM THÍNH
                        $p2 = $prices[2] ?? '--';
                        // ID 3 = GROUP CỘNG ĐỒNG...
                        $p3 = $prices[3] ?? '--';

                        $slots = $pkg['slot_count'];
                        // -----------------------------------------------------
                ?>
                        <tr>
                            <td class="fw-bold">
                                <?php echo htmlspecialchars($pkg['name']); ?>
                                <ul class="list-unstyled fw-normal mt-2 text-muted small">
                                    <?php foreach ($features as $f): ?>
                                        <li><?php echo htmlspecialchars($f); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>

                            <td class="text-center align-middle">
                                <strong class="d-block mb-1"><?php echo $p1; ?></strong>
                                <?php if ($is_logged_in && $p1 != '--') renderCartButton($pkg['slug'], $pkg['name'], 'PAGE GRAB FAN THÁNG 9', $p1, $slots); ?>
                            </td>

                            <td class="text-center align-middle">
                                <strong class="d-block mb-1"><?php echo $p2; ?></strong>
                                <?php if ($is_logged_in && $p2 != '--') renderCartButton($pkg['slug'], $pkg['name'], 'PAGE RAP FAN THÁM THÍNH', $p2, $slots); ?>
                            </td>

                            <td class="text-center align-middle">
                                <strong class="d-block mb-1"><?php echo $p3; ?></strong>
                                <?php
                                // Tên platform dài quá nên code cũ có thể gây lỗi khi truyền vào JS, nên dùng tên chuẩn
                                if ($is_logged_in && $p3 != '--')
                                    renderCartButton($pkg['slug'], $pkg['name'], 'GROUP CỘNG ĐỒNG GRAB VIỆT UNDERGROUND', $p3, $slots);
                                ?>
                            </td>

                            <td class="text-center align-middle">
                                <a href="package-detail.php?slug=<?php echo $pkg['slug']; ?>"
                                    class="btn btn-outline-dark btn-sm">Xem chi tiết</a>
                            </td>
                        </tr>
                <?php endwhile;
                endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    function toggleCart(btnElement, id, name, platform, price, slots) {
        // ... (Giữ nguyên logic JS cũ) ...
        const isAdded = $(btnElement).data('added');
        const action = isAdded ? 'remove' : 'add';
        const originalText = btnElement.innerText;
        btnElement.innerText = '...';
        btnElement.disabled = true;

        $.ajax({
            url: 'customer/cart_action.php',
            type: 'POST',
            data: {
                action: action,
                id: id,
                name: name,
                platform: platform,
                price: price,
                slots: slots
            },
            dataType: 'json',
            success: function(res) {
                $('#cart-count').text(res.count);
                if (res.count > 0) $('#floating-cart').removeClass('d-none');
                else $('#floating-cart').addClass('d-none');

                btnElement.disabled = false;
                if (action === 'add') {
                    $(btnElement).data('added', true);
                    btnElement.innerText = 'Đã chọn';
                    btnElement.classList.remove('btn-outline-warning');
                    btnElement.classList.add('btn-secondary');
                } else {
                    $(btnElement).data('added', false);
                    btnElement.innerText = '+ Chọn';
                    btnElement.classList.remove('btn-secondary');
                    btnElement.classList.add('btn-outline-warning');
                }
            },
            error: function() {
                btnElement.innerText = originalText;
                btnElement.disabled = false;
                alert('Lỗi kết nối.');
            }
        });
    }
</script>
<style>
    #floating-cart {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
        animation: bounceIn 0.5s;
    }

    @keyframes bounceIn {
        0% {
            transform: scale(0);
        }

        80% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }
</style>
<?php include 'templates/footer.php'; ?>