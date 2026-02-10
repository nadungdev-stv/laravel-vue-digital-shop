<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

// Kiểm tra quyền admin
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$categoryId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$categoryId) {
    setFlash('error', 'Danh mục không tồn tại');
    redirect('/admin/layout.php');
}

// Lấy thông tin danh mục
$category = db()->query(
    "SELECT * FROM categories WHERE id = ?",
    [$categoryId]
)->fetch();

if (!$category) {
    setFlash('error', 'Danh mục không tồn tại');
    redirect('/admin/layout.php');
}

$pageTitle = 'Chỉnh sửa danh mục: ' . $category['name'];

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

    $errors = [];

    if (empty($name)) {
        $errors[] = 'Tên danh mục không được để trống';
    }

    // Tự động tạo slug nếu không nhập
    if (empty($slug)) {
        $slug = generateSlug($name);
    } else {
        $slug = generateSlug($slug);
    }

    // Kiểm tra slug trùng (ngoại trừ danh mục hiện tại)
    $existingCategory = db()->query(
        "SELECT id FROM categories WHERE slug = ? AND id != ?",
        [$slug, $categoryId]
    )->fetch();

    if ($existingCategory) {
        $errors[] = 'Slug này đã tồn tại, vui lòng chọn slug khác';
    }

    if (empty($errors)) {
        try {
            $result = db()->query(
                "UPDATE categories
                 SET name = ?, slug = ?, description = ?, icon = ?, status = ?,
                     is_featured = ?, sort_order = ?
                 WHERE id = ?",
                [$name, $slug, $description, $icon, $status, $isFeatured, $sortOrder, $categoryId]
            );

            setFlash('success', 'Đã cập nhật danh mục thành công');
            header('Location: /admin/layout.php');
            exit();
        } catch (Exception $e) {
            error_log("Category update error: " . $e->getMessage());
            $errors[] = 'Có lỗi xảy ra khi cập nhật: ' . $e->getMessage();
        }
    }
}

// Đếm số sản phẩm
$productCount = db()->query(
    "SELECT COUNT(*) as count FROM products WHERE category_id = ?",
    [$categoryId]
)->fetch()['count'];
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
            <h2><i class="fas fa-edit"></i> Chỉnh sửa danh mục</h2>
            <a href="/admin/layout.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <h5><i class="fas fa-exclamation-triangle"></i> Có lỗi xảy ra:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                    value="<?= e($_POST['name'] ?? $category['name']) ?>"
                                    placeholder="VD: Tài khoản Game">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slug (URL thân thiện)</label>
                                <input type="text" name="slug" class="form-control"
                                    value="<?= e($_POST['slug'] ?? $category['slug']) ?>"
                                    placeholder="Để trống sẽ tự động tạo từ tên">
                                <div class="form-text">
                                    Slug sẽ được sử dụng trong URL. VD: tai-khoan-game
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea name="description" class="form-control" rows="4"
                                    placeholder="Mô tả chi tiết về danh mục..."><?= e($_POST['description'] ?? $category['description']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Icon (FontAwesome)</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i id="icon-preview"
                                            class="<?= e($_POST['icon'] ?? $category['icon'] ?: 'fas fa-folder') ?>"></i>
                                    </span>
                                    <input type="text" name="icon" id="icon-input" class="form-control"
                                        value="<?= e($_POST['icon'] ?? $category['icon']) ?>"
                                        placeholder="VD: fas fa-gamepad">
                                </div>
                                <div class="form-text">
                                    Tìm icon tại: <a href="https://fontawesome.com/icons" target="_blank">FontAwesome
                                        Icons</a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= ($_POST['status'] ?? $category['status']) === 'active' ? 'selected' : '' ?>>
                                            Hoạt động
                                        </option>
                                        <option value="inactive" <?= ($_POST['status'] ?? $category['status']) === 'inactive' ? 'selected' : '' ?>>
                                            Tạm ẩn
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Thứ tự hiển thị</label>
                                    <input type="number" name="sort_order" class="form-control"
                                        value="<?= e($_POST['sort_order'] ?? $category['sort_order']) ?>" min="0">
                                    <div class="form-text">Số nhỏ hơn sẽ hiển thị trước</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                                        <?= isset($_POST['is_featured']) ? 'checked' : ($category['is_featured'] ? 'checked' : '') ?>>
                                    <label class="form-check-label" for="is_featured">
                                        <strong>Danh mục nổi bật</strong>
                                        <div class="form-text">Hiển thị ở trang chủ</div>
                                    </label>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Cập nhật
                                </button>
                                <a href="/admin/layout.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Thông tin</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>ID:</strong> <?= $category['id'] ?></p>
                        <p><strong>Sản phẩm:</strong> <?= $productCount ?> sản phẩm</p>
                        <p><strong>Tạo lúc:</strong> <?= formatDate($category['created_at']) ?></p>
                        <p class="mb-0"><strong>Cập nhật:</strong> <?= formatDate($category['updated_at']) ?></p>
                        <hr>
                        <a href="/products?category=<?= e($category['slug']) ?>" class="btn btn-info btn-sm w-100"
                            target="_blank">
                            <i class="fas fa-external-link-alt"></i> Xem trang danh mục
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-icons"></i> Icon phổ biến</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3">
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="selectIcon('fas fa-gamepad')">
                                <i class="fas fa-gamepad fa-2x"></i><br><small>Game</small>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="selectIcon('fas fa-film')">
                                <i class="fas fa-film fa-2x"></i><br><small>Netflix</small>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="selectIcon('fas fa-music')">
                                <i class="fas fa-music fa-2x"></i><br><small>Spotify</small>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="selectIcon('fas fa-key')">
                                <i class="fas fa-key fa-2x"></i><br><small>Key</small>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="selectIcon('fas fa-shield-alt')">
                                <i class="fas fa-shield-alt fa-2x"></i><br><small>VPN</small>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="selectIcon('fas fa-cloud')">
                                <i class="fas fa-cloud fa-2x"></i><br><small>Cloud</small>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="selectIcon('fas fa-crown')">
                                <i class="fas fa-crown fa-2x"></i><br><small>Premium</small>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="selectIcon('fas fa-code')">
                                <i class="fas fa-code fa-2x"></i><br><small>Software</small>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Preview icon khi nhập
        document.getElementById('icon-input').addEventListener('input', function () {
            document.getElementById('icon-preview').className = this.value || 'fas fa-folder';
        });

        // Chọn icon phổ biến
        function selectIcon(iconClass) {
            document.getElementById('icon-input').value = iconClass;
            document.getElementById('icon-preview').className = iconClass;
        }
    </script>

    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
</body>

</html>