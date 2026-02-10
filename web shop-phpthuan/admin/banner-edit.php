<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

// Kiểm tra quyền admin
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$bannerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $bannerId > 0 ? 'edit' : 'add';

// Lấy thông tin banner nếu đang sửa
$banner = null;
if ($bannerId > 0) {
    $banner = db()->query("SELECT * FROM banners WHERE id = ?", [$bannerId])->fetch();
    if (!$banner) {
        setFlash('error', 'Không tìm thấy banner');
        redirect('/admin/banners');
    }
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $image = $banner['image'] ?? '';

    // Xử lý upload ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../public/images/banners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            $image = '/public/images/banners/' . $filename;

            // Xóa ảnh cũ nếu có
            if ($banner && $banner['image'] && file_exists(__DIR__ . '/..' . $banner['image'])) {
                @unlink(__DIR__ . '/..' . $banner['image']);
            }
        }
    }

    if (empty($title)) {
        setFlash('error', 'Vui lòng nhập tiêu đề banner');
    } elseif (empty($image)) {
        setFlash('error', 'Vui lòng upload hình ảnh banner');
    } else {
        if ($action === 'add') {
            db()->query(
                "INSERT INTO banners (title, image, link, description, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)",
                [$title, $image, $link, $description, $sortOrder, $status]
            );
            setFlash('success', 'Đã thêm banner thành công');
        } else {
            db()->query(
                "UPDATE banners SET title = ?, image = ?, link = ?, description = ?, sort_order = ?, status = ? WHERE id = ?",
                [$title, $image, $link, $description, $sortOrder, $status, $bannerId]
            );
            setFlash('success', 'Đã cập nhật banner thành công');
        }
        redirect('/admin/banners');
    }
}

$pageTitle = $action === 'add' ? 'Thêm Banner' : 'Sửa Banner';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/admin-apple-style.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1>
            <i class="fas fa-<?= $action === 'add' ? 'plus' : 'edit' ?>"></i>
            <?= $pageTitle ?>
        </h1>
        <a href="/admin/banners" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control"
                                   value="<?= e($banner['title'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hình ảnh <span class="text-danger">*</span></label>
                            <?php if ($banner && $banner['image']): ?>
                                <div class="mb-2">
                                    <img src="<?= e($banner['image']) ?>"
                                         alt="Current banner"
                                         style="max-height: 150px; border-radius: 8px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" accept="image/*"
                                   <?= $action === 'add' ? 'required' : '' ?>>
                            <small class="text-muted">
                                Kích thước đề xuất: 1200x400px. Định dạng: JPG, PNG, GIF
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Link điều hướng</label>
                            <input type="text" name="link" class="form-control"
                                   value="<?= e($banner['link'] ?? '') ?>"
                                   placeholder="/products?category=slug hoặc URL đầy đủ">
                            <small class="text-muted">
                                Để trống nếu không muốn banner dẫn đến đâu
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"><?= e($banner['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Thứ tự hiển thị</label>
                                    <input type="number" name="sort_order" class="form-control"
                                           value="<?= $banner['sort_order'] ?? 0 ?>" min="0">
                                    <small class="text-muted">Số nhỏ hơn sẽ hiển thị trước</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= ($banner['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                                            Hiển thị
                                        </option>
                                        <option value="inactive" <?= ($banner['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                            Ẩn
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                <?= $action === 'add' ? 'Thêm Banner' : 'Cập nhật' ?>
                            </button>
                            <a href="/admin/banners" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Hướng dẫn
                </div>
                <div class="card-body">
                    <h6>Kích thước ảnh đề xuất:</h6>
                    <ul>
                        <li><strong>Desktop:</strong> 1200x400px</li>
                        <li><strong>Tablet:</strong> 800x300px</li>
                        <li><strong>Mobile:</strong> 600x200px</li>
                    </ul>

                    <h6 class="mt-3">Lưu ý:</h6>
                    <ul class="mb-0">
                        <li>Sử dụng ảnh chất lượng cao</li>
                        <li>Dung lượng tối đa: 2MB</li>
                        <li>Banner sẽ tự động chuyển sau 3 giây</li>
                        <li>Người dùng có thể click nút điều hướng</li>
                    </ul>
                </div>
            </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
</body>
</html>
