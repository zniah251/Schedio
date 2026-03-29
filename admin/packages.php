<?php
session_start();
require_once '../config/db.php';

// Kiểm tra quyền
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    header("Location: login.php");
    exit;
}

$msg = "";
$error = "";

// --- XỬ LÝ POST: THÊM / SỬA / XÓA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. XÓA GÓI
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = intval($_POST['id']);

        // Xóa các ràng buộc khóa ngoại trước (service_option, portfolio...)
        $conn->query("DELETE FROM service_option WHERE package_id = $id");
        $conn->query("DELETE FROM portfolio WHERE package_id = $id");

        // Xóa gói chính
        if ($conn->query("DELETE FROM package WHERE id = $id")) {
            $msg = "Đã xóa gói dịch vụ thành công.";
        } else {
            $error = "Lỗi khi xóa: " . $conn->error;
        }
    }

    // 2. LƯU GÓI (THÊM MỚI HOẶC CẬP NHẬT)
    elseif (isset($_POST['save_package'])) {
        $id = intval($_POST['package_id']); // 0 = Thêm, >0 = Sửa
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $slots = intval($_POST['slots']);
        $active = intval($_POST['active']);
        $overview = trim($_POST['overview']);
        $description = trim($_POST['description']);

        // Mảng giá: [platform_id => price]
        $prices = isset($_POST['price']) ? $_POST['price'] : [];

        if ($id == 0) {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO package (slug, name, overview, description, slot_count, active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $slug, $name, $overview, $description, $slots, $active);

            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                // Thêm giá cho từng platform
                foreach ($prices as $pid => $price) {
                    $p_val = intval($price);
                    if ($p_val >= 0) { // Chỉ lưu nếu có nhập giá
                        $conn->query("INSERT INTO service_option (package_id, platform_id, price) VALUES ($new_id, $pid, $p_val)");
                    }
                }
                $msg = "Thêm gói mới thành công!";
            } else {
                $error = "Lỗi thêm mới: " . $stmt->error;
            }
        } else {
            // UPDATE
            $stmt = $conn->prepare("UPDATE package SET slug=?, name=?, overview=?, description=?, slot_count=?, active=? WHERE id=?");
            $stmt->bind_param("ssssiii", $slug, $name, $overview, $description, $slots, $active, $id);

            if ($stmt->execute()) {
                // Cập nhật giá (Logic: Xóa cũ insert mới cho nhanh, hoặc update từng dòng)
                // Ở đây dùng Update/Insert từng dòng để an toàn
                foreach ($prices as $pid => $price) {
                    $p_val = intval($price);

                    // Kiểm tra xem đã có giá cho platform này chưa
                    $check = $conn->query("SELECT id FROM service_option WHERE package_id=$id AND platform_id=$pid");

                    if ($check->num_rows > 0) {
                        $conn->query("UPDATE service_option SET price=$p_val WHERE package_id=$id AND platform_id=$pid");
                    } else {
                        $conn->query("INSERT INTO service_option (package_id, platform_id, price) VALUES ($id, $pid, $p_val)");
                    }
                }
                $msg = "Cập nhật gói thành công!";
            } else {
                $error = "Lỗi cập nhật: " . $stmt->error;
            }
        }
    }
}

// --- LẤY DANH SÁCH ĐỂ HIỂN THỊ ---
$packages = [];
$sql = "SELECT * FROM package ORDER BY id ASC";
$result = $conn->query($sql);

// Lấy danh sách Platform (để hiển thị tên cột giá)
$platforms = [];
$pl_res = $conn->query("SELECT * FROM platform");
while ($row = $pl_res->fetch_assoc()) {
    $platforms[] = $row;
}

while ($row = $result->fetch_assoc()) {
    $pid = $row['id'];
    $row['prices'] = [];

    // Lấy giá chi tiết kèm tên platform
    $p_res = $conn->query("SELECT so.price, pl.name as pl_name, pl.type 
                           FROM service_option so 
                           JOIN platform pl ON so.platform_id = pl.id 
                           WHERE package_id = $pid");
    while ($pr = $p_res->fetch_assoc()) {
        $row['prices'][] = $pr;
    }
    $packages[] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Gói dịch vụ - Schedio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <style>
    /* CSS sửa lỗi hiển thị bảng */
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        white-space: pre-line;
        /* Giữ xuống dòng */
    }

    .price-list small {
        display: flex;
        justify-content: space-between;
        border-bottom: 1px dashed #eee;
        padding: 2px 0;
    }

    .price-list small:last-child {
        border-bottom: none;
    }

    .table-custom td {
        vertical-align: middle;
    }
    </style>
</head>

<body>

    <div class="admin-wrapper">
        <?php include 'templates/sidebar.php'; ?>

        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary fw-bold mb-0">Quản lý Gói dịch vụ</h2>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="bi bi-plus-lg me-1"></i> Thêm gói mới
                </button>
            </div>

            <?php if ($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th style="width: 5%">ID</th>
                                <th style="width: 15%">Tên gói</th>
                                <th style="width: 30%">Mô tả / Tính năng</th>
                                <th style="width: 10%">Slot</th>
                                <th style="width: 25%">Bảng giá</th>
                                <th style="width: 10%">Trạng thái</th>
                                <th style="width: 120px;" class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $pkg): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $pkg['id']; ?></td>

                                <td>
                                    <span
                                        class="fw-bold text-primary fs-5"><?php echo htmlspecialchars($pkg['name']); ?></span><br>
                                    <code class="text-muted small"><?php echo htmlspecialchars($pkg['slug']); ?></code>
                                </td>

                                <td>
                                    <div class="text-truncate-2 small text-muted mb-1">
                                        <em><?php echo htmlspecialchars($pkg['overview']); ?></em>
                                    </div>
                                    <div class="small" style="white-space: pre-line; font-size: 0.85rem;">
                                        <?php
                                            // Cắt ngắn description nếu dài quá
                                            $desc = htmlspecialchars($pkg['description']);
                                            echo (strlen($desc) > 100) ? substr($desc, 0, 100) . '...' : $desc;
                                            ?>
                                    </div>
                                </td>

                                <td class="fw-bold text-center"><?php echo $pkg['slot_count']; ?></td>

                                <td>
                                    <div class="price-list text-muted small">
                                        <?php if (empty($pkg['prices'])): ?>
                                        <span class="text-danger fst-italic">Chưa cấu hình giá</span>
                                        <?php else: ?>
                                        <?php foreach ($pkg['prices'] as $pr): ?>
                                        <div>
                                            <span><?php echo $pr['pl_name']; ?></span>
                                            <span
                                                class="fw-bold text-dark"><?php echo number_format($pr['price']); ?>đ</span>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <?php if ($pkg['active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success">Hiển
                                        thị</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary">Đang
                                        ẩn</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center align-middle">
                                    <div class="d-flex gap-2 justify-content-center align-items-center">

                                        <button class="btn btn-sm btn-outline-primary"
                                            onclick="editPackage(<?php echo $pkg['id']; ?>)" title="Sửa">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <form method="POST" style="margin: 0;"
                                            onsubmit="return confirm('CẢNH BÁO: Xóa gói này sẽ xóa luôn lịch sử đơn hàng liên quan!\nBạn có chắc chắn muốn xóa?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="packageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form id="packageForm" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold" id="modalTitle">Thêm gói dịch vụ mới</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="save_package" value="1">
                        <input type="hidden" name="package_id" id="pkgId" value="0">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên gói <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" id="pkgName" required
                                        placeholder="VD: Gói 1A">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Slug (URL) <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="slug" id="pkgSlug" required
                                        placeholder="VD: goi-1a">
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label fw-bold">Số slot</label>
                                        <input type="number" class="form-control" name="slots" id="pkgSlots" value="1"
                                            required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label fw-bold">Trạng thái</label>
                                        <select class="form-select" name="active" id="pkgStatus">
                                            <option value="1">Hiển thị</option>
                                            <option value="0">Ẩn</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mô tả ngắn (Overview)</label>
                                    <textarea class="form-control" rows="2" name="overview" id="pkgOverview"
                                        placeholder="Mô tả tổng quan..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Chi tiết tính năng</label>
                                    <textarea class="form-control" rows="4" name="description" id="pkgDesc"
                                        placeholder="- Tính năng 1&#10;- Tính năng 2..."></textarea>
                                    <div class="form-text small">Mỗi tính năng một dòng (gạch đầu dòng).</div>
                                </div>
                            </div>

                            <div class="col-12 mt-3 pt-3 border-top">
                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-tag-fill me-2"></i>Cấu hình giá
                                    theo kênh</h6>
                                <div class="row g-3">
                                    <?php foreach ($platforms as $pl): ?>
                                    <div class="col-md-4">
                                        <label class="small fw-bold mb-1 text-uppercase text-secondary"
                                            style="font-size: 0.75rem;"><?php echo $pl['name']; ?></label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control"
                                                name="price[<?php echo $pl['id']; ?>]"
                                                id="price_<?php echo $pl['id']; ?>" placeholder="0">
                                            <span class="input-group-text">VNĐ</span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-white" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-primary px-4">Lưu thông tin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const myModal = new bootstrap.Modal(document.getElementById('packageModal'));

    function openModal() {
        document.getElementById('packageForm').reset();
        document.getElementById('pkgId').value = "0";
        document.getElementById('modalTitle').innerText = "Thêm gói dịch vụ mới";
        myModal.show();
    }

    function editPackage(id) {
        // Gọi API lấy dữ liệu
        fetch('get_package.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                const pkg = data.package;
                const prices = data.prices;

                // Điền thông tin vào form
                document.getElementById('pkgId').value = pkg.id;
                document.getElementById('pkgName').value = pkg.name;
                document.getElementById('pkgSlug').value = pkg.slug;
                document.getElementById('pkgSlots').value = pkg.slot_count;
                document.getElementById('pkgStatus').value = pkg.active;
                document.getElementById('pkgOverview').value = pkg.overview;
                document.getElementById('pkgDesc').value = pkg.description;
                document.getElementById('modalTitle').innerText = "Cập nhật: " + pkg.name;

                // Reset giá trước
                const allPriceInputs = document.querySelectorAll('input[name^="price"]');
                allPriceInputs.forEach(input => input.value = '');

                // Điền giá mới
                for (const [pid, price] of Object.entries(prices)) {
                    const input = document.getElementById('price_' + pid);
                    if (input) input.value = price;
                }

                myModal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Lỗi khi tải dữ liệu gói!");
            });
    }
    </script>

</body>

</html>