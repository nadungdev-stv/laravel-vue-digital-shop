<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$variantId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$variantId) {
    setFlash('error', 'Gói không tồn tại');
    redirect('/admin/products');
}

$variant = db()->query("SELECT * FROM product_variants WHERE id = ?", [$variantId])->fetch();
if (!$variant) {
    setFlash('error', 'Gói không tồn tại');
    redirect('/admin/products');
}

$product = db()->query("SELECT * FROM products WHERE id = ?", [$variant['product_id']])->fetch();

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $salePrice = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isMain = isset($_POST['is_main']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';
    $deliveryType = $_POST['delivery_type'] ?? null;
    $variantTitle = trim($_POST['variant_title'] ?? '');
    $variantImage = trim($_POST['current_variant_image'] ?? '');

    $errors = [];

    // Xử lý upload ảnh
    if (isset($_FILES['variant_image']) && $_FILES['variant_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['variant_image'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];

        if (!in_array($fileExt, $allowedExts)) {
            $errors[] = 'Chỉ chấp nhận file ảnh: JPG, PNG, GIF, SVG, WebP';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Kích thước file tối đa 5MB';
        } else {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/public/images/variants/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'variant-' . $variantId . '-' . time() . '.' . $fileExt;
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Xóa ảnh cũ nếu có
                if (!empty($variant['variant_image'])) {
                    $oldImagePath = $_SERVER['DOCUMENT_ROOT'] . $variant['variant_image'];
                    if (file_exists($oldImagePath) && strpos($variant['variant_image'], '/variants/') !== false) {
                        @unlink($oldImagePath);
                    }
                }
                $variantImage = '/public/images/variants/' . $fileName;
            } else {
                $errors[] = 'Lỗi khi tải ảnh lên';
            }
        }
    }

    if (empty($name)) $errors[] = 'Tên gói không được để trống';
    if (empty($slug)) $errors[] = 'Slug không được để trống';
    if ($price <= 0) $errors[] = 'Giá phải lớn hơn 0';

    // Kiểm tra slug trùng (trừ chính nó)
    // Auto-prepend product slug if missing
    if (isset($product['slug'])) {
        $prefix = $product['slug'] . '-';
        if (strpos($slug, $prefix) !== 0) {
            // Remove any leading dashes to avoid --
            $slug = $prefix . ltrim($slug, '-');
        }
    }

    $existingSlug = db()->query("SELECT id FROM product_variants WHERE slug = ? AND id != ?", [$slug, $variantId])->fetch();
    if ($existingSlug) {
        $errors[] = 'Slug đã tồn tại';
    }

    if (empty($errors)) {
        // Thử UPDATE với các cột mới, nếu lỗi thì UPDATE với cột cũ
        try {
            db()->query(
                "UPDATE product_variants SET name = ?, slug = ?, duration = ?, price = ?, sale_price = ?, stock_quantity = ?, sort_order = ?, is_main = ?, status = ?, delivery_type = ?, variant_title = ?, variant_image = ?, updated_at = NOW() WHERE id = ?",
                [$name, $slug, $duration, $price, $salePrice, $stockQuantity, $sortOrder, $isMain, $status, $deliveryType, $variantTitle, $variantImage, $variantId]
            );
        } catch (Exception $e) {
            // Nếu lỗi (các cột mới chưa tồn tại), UPDATE với cột cũ
            db()->query(
                "UPDATE product_variants SET name = ?, slug = ?, duration = ?, price = ?, sale_price = ?, stock_quantity = ?, sort_order = ?, is_main = ?, status = ?, updated_at = NOW() WHERE id = ?",
                [$name, $slug, $duration, $price, $salePrice, $stockQuantity, $sortOrder, $isMain, $status, $variantId]
            );
        }

        // Tự động set main nếu chỉ có 1 variant
        autoSetMainVariantIfOnlyOne($variant['product_id']);

        setFlash('success', 'Đã cập nhật gói sản phẩm');
        redirect('/admin/product-edit?id=' . $variant['product_id'] . '#variants');
    }
}

$pageTitle = 'Chỉnh sửa gói: ' . $variant['name'];
$isModal = isset($_GET['modal']) && $_GET['modal'] == '1';

// If modal mode, only output the form
if ($isModal) {
    ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/admin/product-variant-edit?id=<?= $variantId ?>" enctype="multipart/form-data">
        <div class="alert alert-info">
            <strong>Sản phẩm:</strong> <?= e($product['name']) ?>
        </div>
<?php } else { ?>
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
                <h2><i class="fas fa-edit"></i> Chỉnh sửa gói: <?= e($variant['name']) ?></h2>
                <a href="/admin/product-edit?id=<?= $variant['product_id'] ?>#variants" class="simple-button simple-button-blue">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="alert alert-info">
                            <strong>Sản phẩm:</strong> <?= e($product['name']) ?>
                        </div>
<?php } ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tên gói <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? $variant['name']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" class="form-control" required value="<?= e($_POST['slug'] ?? $variant['slug']) ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Thời hạn</label>
                                <input type="text" name="duration" class="form-control" value="<?= e($_POST['duration'] ?? $variant['duration']) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Giá gốc (VNĐ) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" required min="0" value="<?= e($_POST['price'] ?? $variant['price']) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Giá khuyến mãi (VNĐ)</label>
                                <input type="number" name="sale_price" class="form-control" min="0" value="<?= e($_POST['sale_price'] ?? $variant['sale_price']) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Tồn kho</label>
                                <input type="number" name="stock_quantity" class="form-control" min="0" value="<?= e($_POST['stock_quantity'] ?? $variant['stock_quantity']) ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Thứ tự sắp xếp</label>
                                <input type="number" name="sort_order" class="form-control" min="0" value="<?= e($_POST['sort_order'] ?? $variant['sort_order']) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= ($_POST['status'] ?? $variant['status']) === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                                    <option value="inactive" <?= ($_POST['status'] ?? $variant['status']) === 'inactive' ? 'selected' : '' ?>>Tạm ẩn</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Loại giao hàng</label>
                                <select name="delivery_type" class="form-select">
                                    <option value="" <?= empty($_POST['delivery_type'] ?? $variant['delivery_type']) ? 'selected' : '' ?>>Kế thừa từ sản phẩm</option>
                                    <option value="account" <?= ($_POST['delivery_type'] ?? $variant['delivery_type']) === 'account' ? 'selected' : '' ?>>Tài khoản có sẵn trong kho</option>
                                    <option value="email_only" <?= ($_POST['delivery_type'] ?? $variant['delivery_type']) === 'email_only' ? 'selected' : '' ?>>Gửi qua email</option>
                                    <option value="customer_account" <?= ($_POST['delivery_type'] ?? $variant['delivery_type']) === 'customer_account' ? 'selected' : '' ?>>Khách cung cấp tài khoản</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label d-block">Gói chính</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_main" id="is_main"
                                           <?= ($_POST['is_main'] ?? $variant['is_main']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_main">
                                        Đánh dấu là gói chính (sẽ hiển thị với màu nổi bật)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Tiêu đề riêng cho gói này (tuỳ chọn)</label>
                                <input type="text" name="variant_title" class="form-control"
                                       placeholder="Để trống sẽ dùng tiêu đề của sản phẩm"
                                       value="<?= e($_POST['variant_title'] ?? $variant['variant_title']) ?>">
                                <small class="text-muted">Nếu để trống, sẽ hiển thị tiêu đề của sản phẩm chính</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Ảnh riêng cho gói này (tuỳ chọn)</label>
                                <input type="file" name="variant_image" id="variant-image-file" class="form-control" accept="image/*">
                                <?php if (!empty($variant['variant_image'])): ?>
                                    <input type="hidden" name="current_variant_image" value="<?= e($variant['variant_image']) ?>">
                                    <div id="variant-image-preview" class="mt-2">
                                        <img src="<?= e($variant['variant_image']) ?>" alt="Preview" class="img-thumbnail" style="max-width: 300px;">
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted d-block mt-1">Nếu không chọn ảnh, sẽ hiển thị ảnh của sản phẩm chính</small>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="simple-button simple-button-blue">
                                <i class="fas fa-save"></i> Cập nhật
                            </button>
                            <?php if (!$isModal): ?>
                            <a href="/admin/product-edit?id=<?= $variant['product_id'] ?>#variants" class="simple-button simple-button-blue">
                                <i class="fas fa-times"></i> Hủy
                            </a>
                            <?php else: ?>
                            <button type="button" class="simple-button simple-button-gray" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Đóng
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>

<?php if ($isModal) { ?>
    <script>
        // Variant image preview for modal
        document.getElementById('variant-image-file')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('variant-image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.id = 'variant-image-preview';
                        preview.className = 'mt-2';
                        document.getElementById('variant-image-file').parentElement.appendChild(preview);
                    }
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="img-thumbnail" style="max-width: 300px;">';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
<?php } else { ?>
                </div>
            </div>
        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variant image preview
        document.getElementById('variant-image-file')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('variant-image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.id = 'variant-image-preview';
                        preview.className = 'mt-2';
                        document.getElementById('variant-image-file').parentElement.appendChild(preview);
                    }
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="img-thumbnail" style="max-width: 300px;">';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
<?php } ?>
