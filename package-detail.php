<?php
session_start();
require_once 'config/db.php';
include 'templates/header.php';

// 1. Lấy slug từ URL
if (!isset($_GET['slug'])) {
    echo "<script>alert('Gói dịch vụ không tồn tại!'); window.location.href='services.php';</script>";
    exit;
}

$slug = $_GET['slug'];
$is_logged_in = isset($_SESSION['user_id']);

// 2. Lấy thông tin chi tiết gói
$stmt = $conn->prepare("SELECT * FROM package WHERE slug = ? AND active = 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$package = $result->fetch_assoc();

if (!$package) {
    echo "<script>alert('Không tìm thấy gói dịch vụ!'); window.location.href='services.php';</script>";
    exit;
}

// 3. Lấy giá của gói này trên các Platform khác nhau
$prices = [];

$sql_p = "SELECT so.price, pl.name, pl.id as platform_id 
          FROM service_option so 
          JOIN platform pl ON so.platform_id = pl.id 
          WHERE so.package_id = " . $package['id'] . "
          GROUP BY pl.id"; // 

$res_p = $conn->query($sql_p);

while ($row = $res_p->fetch_assoc()) {
    $prices[] = $row;
}

// Xử lý mô tả thành danh sách
$features = explode("\n", $package['description']);
?>

<div class="container my-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="services.php" class="text-decoration-none text-muted">Bảng giá</a></li>
            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">
                <?php echo htmlspecialchars($package['name']); ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold text-primary mb-3"><?php echo htmlspecialchars($package['name']); ?></h1>
            <p class="lead text-muted mb-4">Giải pháp truyền thông tối ưu dành cho nghệ sĩ Underground.</p>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-3"><i class="bi bi-star-fill text-warning me-2"></i>Quyền lợi gói dịch vụ</h4>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($features as $feature): ?>
                        <li class="list-group-item bg-transparent border-0 ps-0 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                            <span class="fs-5"><?php echo htmlspecialchars($feature); ?></span>
                        </li>
                        <?php endforeach; ?>
                        <li class="list-group-item bg-transparent border-0 ps-0 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                            <span class="fs-5">Hỗ trợ tư vấn nội dung miễn phí</span>
                        </li>
                        <li class="list-group-item bg-transparent border-0 ps-0 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                            <span class="fs-5">Báo cáo số liệu sau chiến dịch</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                <div>
                    <strong>Lưu ý:</strong> Gói dịch vụ này bao gồm <strong><?php echo $package['slot_count']; ?>
                        slot</strong> (khung giờ đăng bài). Bạn sẽ được chọn lịch cụ thể ở bước tiếp theo.
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow rounded-4 bg-light">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4 text-center">Chọn kênh triển khai</h4>

                    <?php foreach ($prices as $opt): ?>
                    <?php
                        // Logic kiểm tra giỏ hàng
                        $key = $package['slug'] . '_' . md5($opt['name']);
                        $isAdded = isset($_SESSION['cart'][$key]);
                        $displayPrice = ($opt['price'] >= 1000) ? number_format($opt['price'] / 1000) . 'k' : $opt['price'];
                        ?>

                    <div class="card mb-3 border border-2 hover-shadow transition-all">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($opt['name']); ?></h6>
                                <span class="text-primary fw-bold fs-5"><?php echo $displayPrice; ?></span>
                            </div>

                            <?php if ($is_logged_in): ?>
                            <button
                                class="btn <?php echo $isAdded ? 'btn-secondary' : 'btn-warning'; ?> rounded-pill px-4 fw-bold btn-toggle-cart"
                                data-added="<?php echo $isAdded ? 'true' : 'false'; ?>"
                                onclick="toggleCart(this, '<?php echo $package['slug']; ?>', '<?php echo $package['name']; ?>', '<?php echo $opt['name']; ?>', '<?php echo $displayPrice; ?>', <?php echo $package['slot_count']; ?>)">
                                <?php echo $isAdded ? 'Đã chọn' : '+ Chọn'; ?>
                            </button>
                            <?php else: ?>
                            <a href="login.php" class="btn btn-outline-dark rounded-pill px-3 btn-sm">Đăng nhập</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="text-center mt-4">
                        <small class="text-muted">Giá đã bao gồm thuế và phí dịch vụ.</small>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="services.php" class="text-decoration-none fw-bold text-dark"><i class="bi bi-arrow-left"></i>
                    Quay lại bảng giá</a>
            </div>
        </div>
    </div>
</div>

<?php
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
if ($is_logged_in):
?>
<div id="floating-cart" class="<?php echo $cart_count > 0 ? '' : 'd-none'; ?>">
    <a href="customer/booking.php" class="btn btn-schedio-primary shadow-lg py-3 px-4 rounded-pill">
        <i class="bi bi-cart-check-fill me-2"></i>
        Tiến hành đặt lịch (<span id="cart-count"><?php echo $cart_count; ?></span> gói)
    </a>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function toggleCart(btnElement, id, name, platform, price, slots) {
    const isAdded = $(btnElement).data('added');
    const action = isAdded ? 'remove' : 'add';
    const originalText = btnElement.innerText;

    // Hiệu ứng loading nhẹ
    btnElement.innerText = '...';
    btnElement.disabled = true;

    $.ajax({
        url: 'customer/cart_action.php', // Đảm bảo đường dẫn này đúng
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
                btnElement.classList.remove('btn-warning');
                btnElement.classList.add('btn-secondary');
            } else {
                $(btnElement).data('added', false);
                btnElement.innerText = '+ Chọn';
                btnElement.classList.remove('btn-secondary');
                btnElement.classList.add('btn-warning');
            }
        },
        error: function() {
            btnElement.innerText = originalText;
            btnElement.disabled = false;
            alert('Lỗi kết nối server.');
        }
    });
}
</script>
<?php include 'templates/footer.php'; ?>