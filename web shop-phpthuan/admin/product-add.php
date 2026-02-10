<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Thêm sản phẩm mới';
$errors = [];

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    // Process Categories (Tagify)
    $categorySelection = json_decode($_POST['category_selection'] ?? '[]', true);
    $categoryIds = [];

    if (is_array($categorySelection)) {
        foreach ($categorySelection as $item) {
            if (isset($item['id']) && $item['id'] > 0) {
                $categoryIds[] = (int) $item['id'];
            } else if (isset($item['value']) && !empty($item['value'])) {
                // New Category Creation
                $newCatName = trim($item['value']);
                $newCatSlug = generateSlug($newCatName);

                // Check if slug exists
                $checkSlug = db()->query("SELECT id FROM categories WHERE slug = ?", [$newCatSlug])->fetch();
                if ($checkSlug) {
                    $newCatSlug .= '-' . time();
                }

                try {
                    db()->query("INSERT INTO categories (name, slug, status, created_at) VALUES (?, ?, 'active', NOW())", [$newCatName, $newCatSlug]);
                    $categoryIds[] = db()->lastInsertId();
                } catch (Exception $e) {
                    // Fail silently or log
                }
            }
        }
    }

    $categoryId = !empty($categoryIds) ? $categoryIds[0] : 0; // Primary category for legacy support

    $tags = trim($_POST['tags'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $salePrice = !empty($_POST['sale_price']) ? (float) $_POST['sale_price'] : null;
    $description = trim($_POST['description'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $stockQuantity = (int) ($_POST['stock_quantity'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $featured = isset($_POST['featured']) ? 1 : 0;
    $deliveryType = $_POST['delivery_type'] ?? 'account';
    $image = '';

    // Calculate price/stock from variants if main pricing is missing
    if (!empty($_POST['new_variants']) && empty($_POST['price'])) {
        $minPrice = null;
        $totalStock = 0;
        foreach ($_POST['new_variants'] as $v) {
            if (!empty($v['price'])) {
                $p = (float) $v['price'];
                if ($minPrice === null || $p < $minPrice)
                    $minPrice = $p;
            }
            $totalStock += (int) ($v['stock'] ?? 0);
        }
        if ($minPrice !== null) {
            $price = $minPrice;
            // Also set stock if not manually provided
            if ($stockQuantity <= 0)
                $stockQuantity = $totalStock;
        }
    }

    // Validation
    if (empty($name))
        $errors[] = 'Tên sản phẩm không được để trống';
    if (empty($slug)) {
        $slug = generateSlug($name);
    }
    if (!$categoryId)
        $errors[] = 'Vui lòng chọn danh mục';
    if ($price <= 0)
        $errors[] = 'Giá sản phẩm phải lớn hơn 0';

    // Check slug duplicate
    $existingProduct = db()->query("SELECT id FROM products WHERE slug = ?", [$slug])->fetch();
    if ($existingProduct) {
        $errors[] = 'Slug đã tồn tại. Vui lòng chọn slug khác.';
    }

    // Main image logic removed - will be handled by gallery first image

    if (empty($errors)) {
        // Insert product
        db()->query(
            "INSERT INTO products (name, slug, category_id, tags, price, sale_price, description, features, delivery_type, image, stock_quantity, status, featured, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [$name, $slug, $categoryId, $tags, $price, $salePrice, $description, $features, $deliveryType, $image, $stockQuantity, $status, $featured]
        );

        $productId = db()->lastInsertId();

        // Handle gallery images upload
        if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/public/images/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $sortOrder = 1;
            $isFirstImage = true;

            foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileExt = strtolower(pathinfo($_FILES['gallery_images']['name'][$key], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];

                    if (in_array($fileExt, $allowedExts) && $_FILES['gallery_images']['size'][$key] <= 5 * 1024 * 1024) {
                        $uniqueId = uniqid();
                        $fileName = 'product-' . $productId . '-' . time() . '-' . $uniqueId . '.' . $fileExt;
                        $uploadPath = $uploadDir . $fileName;

                        if (move_uploaded_file($tmp_name, $uploadPath)) {
                            $imagePath = '/public/images/products/' . $fileName;

                            // If this is the first image, set it as the main product image
                            if ($isFirstImage) {
                                try {
                                    db()->query("UPDATE products SET image = ? WHERE id = ?", [$imagePath, $productId]);
                                    $isFirstImage = false;
                                } catch (Exception $e) {
                                }
                            }

                            // Always add to gallery
                            try {
                                db()->query(
                                    "INSERT INTO product_gallery (product_id, image_path, sort_order, created_at) VALUES (?, ?, ?, NOW())",
                                    [$productId, $imagePath, $sortOrder]
                                );
                                $sortOrder++;
                            } catch (Exception $e) {
                                // Table might not exist yet
                            }
                        }
                    }
                }
            }
        }

        // Handle Product Categories (Many-to-Many)
        if (!empty($categoryIds)) {
            // Create table if not exists
            try {
                db()->query("CREATE TABLE IF NOT EXISTS product_categories (
                    product_id INT NOT NULL,
                    category_id INT NOT NULL,
                    PRIMARY KEY (product_id, category_id)
                )");
            } catch (Exception $e) {
            }

            $placeholders = [];
            $values = [];
            foreach ($categoryIds as $cId) {
                $placeholders[] = "(?, ?)";
                $values[] = $productId;
                $values[] = $cId;
            }

            if (!empty($placeholders)) {
                $sql = "INSERT IGNORE INTO product_categories (product_id, category_id) VALUES " . implode(', ', $placeholders);
                db()->query($sql, $values);
            }
        }

        // Handle inline variants
        if (!empty($_POST['new_variants'])) {
            $sortOrder = 1;
            foreach ($_POST['new_variants'] as $index => $variant) {
                if (!empty($variant['name']) && !empty($variant['price'])) {
                    $vName = trim($variant['name']);
                    $vSlug = !empty($variant['slug']) ? $product['slug'] . '-' . trim($variant['slug']) : $product['slug'] . '-' . strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $vName)));
                    $vPrice = (float) $variant['price'];
                    $vSalePrice = !empty($variant['sale_price']) ? (float) $variant['sale_price'] : null;
                    $vStock = (int) ($variant['stock'] ?? 0);
                    $vDelivery = !empty($variant['delivery_type']) ? $variant['delivery_type'] : null;
                    $vTitle = trim($variant['title'] ?? '');
                    $vImage = '';

                    // Handle variant image
                    if (isset($_FILES['new_variants']['name'][$index]['image']) && $_FILES['new_variants']['error'][$index]['image'] === UPLOAD_ERR_OK) {
                        $vFile = [
                            'name' => $_FILES['new_variants']['name'][$index]['image'],
                            'type' => $_FILES['new_variants']['type'][$index]['image'],
                            'tmp_name' => $_FILES['new_variants']['tmp_name'][$index]['image'],
                            'error' => $_FILES['new_variants']['error'][$index]['image'],
                            'size' => $_FILES['new_variants']['size'][$index]['image']
                        ];

                        $vFileExt = strtolower(pathinfo($vFile['name'], PATHINFO_EXTENSION));
                        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];

                        if (in_array($vFileExt, $allowedExts) && $vFile['size'] <= 5 * 1024 * 1024) {
                            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/public/images/variants/';
                            if (!is_dir($uploadDir))
                                mkdir($uploadDir, 0755, true);

                            $vFileName = 'variant-' . $productId . '-' . time() . '-' . $index . '.' . $vFileExt;
                            if (move_uploaded_file($vFile['tmp_name'], $uploadDir . $vFileName)) {
                                $vImage = '/public/images/variants/' . $vFileName;
                            }
                        }
                    }

                    try {
                        db()->query(
                            "INSERT INTO product_variants (product_id, name, slug, price, sale_price, stock_quantity, delivery_type, variant_title, variant_image, sort_order, is_main, status, created_at, updated_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())",
                            [$productId, $vName, $vSlug, $vPrice, $vSalePrice, $vStock, $vDelivery, $vTitle, $vImage, $sortOrder++, ($index === 0 ? 1 : 0)]
                        );

                    } catch (Exception $e) {
                        // Ignore dupes or errors
                    }
                }
            }
            // Set main variant if only one
            try {
                $count = db()->query("SELECT COUNT(*) FROM product_variants WHERE product_id = ?", [$productId])->fetchColumn();
                if ($count == 1) {
                    db()->query("UPDATE product_variants SET is_main = 1 WHERE product_id = ?", [$productId]);
                }
            } catch (Exception $e) {
            }
        }

        setFlash('success', 'Đã thêm sản phẩm thành công.');
        redirect('/admin/product-edit?id=' . $productId);
    }
}

$categories = db()->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
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
    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>

<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center my-4">
            <h2><i class="fas fa-plus-circle"></i> Thêm sản phẩm mới</h2>
            <a href="/admin/products" class="simple-button simple-button-blue">
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

        <form method="POST" enctype="multipart/form-data" id="product-form">
            <div class="row g-4">
                <!-- Left Column: Main Information -->
                <div class="col-lg-9">
                    <!-- General Info Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 card-title fw-bold">Thông tin chung</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên sản phẩm <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" id="product-name" class="form-control form-control-lg"
                                    placeholder="Nhập tên sản phẩm..." required value="<?= e($_POST['name'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted">Permalink (Slug)</label>
                                <div class="input-group input-group-sm">
                                    <span
                                        class="input-group-text bg-light">https://<?= $_SERVER['HTTP_HOST'] ?>/product/</span>
                                    <input type="text" name="slug" id="product-slug" class="form-control"
                                        value="<?= e($_POST['slug'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Mô tả sản phẩm</label>
                                <textarea name="description" id="description-editor" class="form-control tinymce-editor"
                                    rows="5"><?= e($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Tính năng nổi bật</label>
                                <textarea name="features" id="features-editor" class="form-control tinymce-editor"
                                    rows="5"><?= e($_POST['features'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Media Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 card-title fw-bold">Hình ảnh</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Thư viện ảnh (Gallery)</label>
                                <input type="file" name="gallery_images[]" id="gallery-images" class="form-control"
                                    accept="image/*" multiple>
                                <small class="text-muted d-block mt-2">Chọn nhiều ảnh. <strong>Ảnh đầu tiên</strong> sẽ
                                    được dùng làm ảnh đại diện sản phẩm. Kéo thả để sắp xếp.</small>

                                <div id="gallery-preview" class="mt-3 d-flex flex-wrap gap-2">
                                    <!-- Previews will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Variants Section (Inline Form) -->
                    <div class="card mb-4" id="quick-variants-card" style="display: none;">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 card-title fw-bold">Quản lý gói sản phẩm (Variants)</h5>
                        </div>
                        <div class="px-4 pb-4 border-bottom">
                            <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle"></i> Thêm gói mới</h6>
                            <div class="row g-3">
                                <!-- Hàng 1: Định danh -->
                                <div class="col-md-5">
                                    <div class="form-floating">
                                        <input type="text" id="inline-v-name" class="form-control"
                                            placeholder="Tên gói">
                                        <label for="inline-v-name">Tên gói <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-floating">
                                        <input type="text" id="inline-v-slug" class="form-control" placeholder="Slug">
                                        <label for="inline-v-slug">Slug (URL)</label>
                                    </div>
                                    <small class="text-muted d-block mt-1 ps-1" style="font-size: 0.8rem;">
                                        URL: ...<span id="inline-slug-preview" class="fw-bold"></span>
                                    </small>
                                </div>

                                <!-- Hàng 2: Giá & Kho -->
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">Giá bán</span>
                                        <input type="number" id="inline-v-price" class="form-control" placeholder="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">Giá KM</span>
                                        <input type="number" id="inline-v-sale-price" class="form-control"
                                            placeholder="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">Kho</span>
                                        <input type="number" id="inline-v-stock" class="form-control" value="0">
                                    </div>
                                </div>

                                <!-- Hàng 3: Cấu hình mở rộng -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-1">Loại giao hàng</label>
                                    <select id="inline-v-delivery" class="form-select form-select-sm">
                                        <option value="">Kế thừa sản phẩm</option>
                                        <option value="account">Kho tài khoản</option>
                                        <option value="email_only">Chỉ cần Email</option>
                                        <option value="customer_account">Nâng cấp TK khách</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-1">Tiêu đề riêng</label>
                                    <input type="text" id="inline-v-title" class="form-control form-control-sm"
                                        placeholder="Tự động theo tên gói">
                                </div>

                                <!-- Hàng 4: Ảnh & Action -->
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label small fw-bold mb-1">Ảnh đại diện gói</label>
                                            <input type="file" id="inline-v-image" class="form-control form-control-sm"
                                                accept="image/*">
                                        </div>
                                        <div class="pt-4">
                                            <button type="button" class="simple-button simple-button-blue px-4"
                                                id="btn-add-inline-variant" style="height: 34px; line-height: 1;">
                                                <i class="fas fa-plus"></i> Thêm
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold mb-2">Danh sách gói (<span id="variant-count-display">0</span>)</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle table-striped table-hover">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Tên gói</th>
                                            <th>Giá bán</th>
                                            <th>Kho</th>
                                            <th>Ảnh</th>
                                            <th class="text-center" width="50"><i class="fas fa-trash"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody id="variants-tbody">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3" id="no-variants-msg">
                                                Chưa có gói nào. Thêm gói mới ở trên.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div id="variants-hidden-inputs"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Settings & Organization -->
                <div class="col-lg-3">
                    <!-- Publish Actions -->
                    <div class="card mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 card-title fw-bold">Đăng sản phẩm</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="simple-button simple-button-green w-100 py-2 fs-6">
                                    <i class="fas fa-save me-2"></i> Lưu sản phẩm
                                </button>
                                <a href="/admin/products"
                                    class="simple-button simple-button-gray w-100 py-2 fs-6 text-center text-decoration-none">
                                    Hủy bỏ
                                </a>
                            </div>
                            <hr>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="status" value="active"
                                    id="status-check" <?= ($_POST['status'] ?? 'active') === 'active' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="status-check">Đang bán (Active)</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="featured" id="featured"
                                    <?= isset($_POST['featured']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="featured">Sản phẩm nổi bật</label>
                            </div>
                        </div>
                    </div>

                    <!-- Price Card -->
                    <div class="card mb-4" id="main-price-card">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 card-title fw-bold">Giá bán</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_variants_toggle">
                                <label class="form-check-label fw-bold" for="enable_variants_toggle">Sản phẩm có nhiều
                                    gói?</label>
                            </div>

                            <div id="single-price-inputs">
                                <div class="mb-3">
                                    <label class="form-label">Giá gốc (VNĐ) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="price" id="main-price-input"
                                            class="form-control font-monospace" min="0"
                                            value="<?= e($_POST['price'] ?? '') ?>" placeholder="0">
                                        <span class="input-group-text">₫</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Giá khuyến mãi</label>
                                    <div class="input-group">
                                        <input type="number" name="sale_price" class="form-control font-monospace"
                                            min="0" value="<?= e($_POST['sale_price'] ?? '') ?>" placeholder="0">
                                        <span class="input-group-text">₫</span>
                                    </div>
                                </div>
                            </div>

                            <div id="price-variants-message" class="alert alert-info small mb-0" style="display: none;">
                                <i class="fas fa-arrow-left me-1"></i> Vui lòng nhập chi tiết các gói ở cột bên trái.
                                Giá sản phẩm sẽ tự động lấy theo gói thấp nhất.
                            </div>
                        </div>
                    </div>

                    <!-- Organization Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 card-title fw-bold">Phân loại</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Danh mục <span class="text-danger">*</span></label>
                                <input name="category_selection" id="category-selection" class="form-control"
                                    placeholder="Tìm kiếm hoặc nhập để tạo mới..." value="">
                                <small class="text-muted">Nhập tên danh mục và nhấn Enter. Danh mục chưa có sẽ được tạo
                                    tự động.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tags</label>
                                <input type="text" name="tags" class="form-control"
                                    value="<?= e($_POST['tags'] ?? '') ?>" placeholder="VD: Premium, Hot, Sale">
                                <small class="text-muted">Ngăn cách bởi dấu phẩy</small>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory & Delivery -->
                    <div class="card mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 card-title fw-bold">Kho hàng & Giao nhận</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Loại giao hàng <span class="text-danger">*</span></label>
                                <select name="delivery_type" class="form-select" required>
                                    <option value="account" <?= ($_POST['delivery_type'] ?? 'account') === 'account' ? 'selected' : '' ?>>Kho tài khoản</option>
                                    <option value="email_only" <?= ($_POST['delivery_type'] ?? '') === 'email_only' ? 'selected' : '' ?>>Chỉ cần Email</option>
                                    <option value="customer_account" <?= ($_POST['delivery_type'] ?? '') === 'customer_account' ? 'selected' : '' ?>>Nâng cấp TK khách</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số lượng tồn</label>
                                <input type="number" name="stock_quantity" class="form-control" min="0"
                                    value="<?= e($_POST['stock_quantity'] ?? 0) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>




    <!-- Quill Editor -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script>
        // Tagify Logic for Categories
        const categoryInput = document.querySelector('#category-selection');
        // Prepare whitelist from PHP
        const categoryWhitelist = [
            <?php foreach ($categories as $cat): ?>
                    { value: <?= json_encode($cat['name']) ?>, id: <?= $cat['id'] ?> },
            <?php endforeach; ?>
        ];

        const tagify = new Tagify(categoryInput, {
            whitelist: categoryWhitelist,
            placeholder: "Tìm danh mục...",
            enforceWhitelist: false, // Allow new tags
            dropdown: {
                maxItems: 20,           // <- mixumum allowed rendered suggestions
                classname: "tags-look", // <- custom classname for this dropdown, so it could be targeted
                enabled: 0,             // <- show suggestions on focus
                closeOnSelect: false    // <- do not hide the suggestions dropdown once an item has been selected
            }
        });

        // Variant Logic
        const enableVariantsToggle = document.getElementById('enable_variants_toggle');
        const quickVariantsCard = document.getElementById('quick-variants-card');
        const singlePriceInputs = document.getElementById('single-price-inputs');
        const priceVariantsMessage = document.getElementById('price-variants-message');
        const mainPriceInput = document.getElementById('main-price-input');

        const variantsTbody = document.getElementById('variants-tbody');
        const variantsHiddenInputs = document.getElementById('variants-hidden-inputs');
        const noVariantsMsg = document.getElementById('no-variants-msg');
        const variantCountDisplay = document.getElementById('variant-count-display');

        let variantIndex = 0;

        // Product Logic
        const productNameInput = document.getElementById('product-name');
        const productSlugInput = document.getElementById('product-slug');

        // Inline Form Elements
        const inlineName = document.getElementById('inline-v-name');
        const inlineSlug = document.getElementById('inline-v-slug');
        const inlinePrice = document.getElementById('inline-v-price');
        const inlineSalePrice = document.getElementById('inline-v-sale-price');
        const inlineStock = document.getElementById('inline-v-stock');
        const inlineDelivery = document.getElementById('inline-v-delivery');
        const inlineTitle = document.getElementById('inline-v-title');
        const inlineImage = document.getElementById('inline-v-image');
        const btnAddInline = document.getElementById('btn-add-inline-variant');
        const inlineSlugPreview = document.getElementById('inline-slug-preview');

        // Consolidated Gallery Logic
        const galleryInput = document.getElementById('gallery-images');
        const galleryPreview = document.getElementById('gallery-preview');
        let galleryDataTransfer = new DataTransfer();

        if (galleryInput && galleryPreview) {
            // Initialize Sortable
            new Sortable(galleryPreview, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function () {
                    updateGalleryFiles();
                }
            });

            galleryInput.addEventListener('change', function (e) {
                // Add new files to DataTransfer
                Array.from(this.files).forEach(file => {
                    galleryDataTransfer.items.add(file);
                });

                // Update input files and re-render
                galleryInput.files = galleryDataTransfer.files;
                renderGallery();
            });

            function renderGallery() {
                galleryPreview.innerHTML = '';

                Array.from(galleryDataTransfer.files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const div = document.createElement('div');
                        div.className = 'position-relative gallery-item';
                        div.style.cursor = 'move';
                        div.file = file; // Attach file object to DOM element

                        // Image
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-thumbnail';
                        img.style.width = '150px';
                        img.style.height = '150px';
                        img.style.objectFit = 'cover';

                        // Delete Button
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'position-absolute top-0 end-0 btn btn-danger btn-sm p-1 m-1';
                        btn.style.lineHeight = '1';
                        btn.innerHTML = '<i class="fas fa-times"></i>';
                        btn.onclick = function () {
                            div.remove();
                            updateGalleryFiles();
                        };

                        // Main Image Badge (First Item)
                        if (index === 0) {
                            const badge = document.createElement('div');
                            badge.className = 'position-absolute bottom-0 start-0 m-1 badge bg-primary';
                            badge.innerText = 'Ảnh đại diện';
                            div.appendChild(badge);
                        }

                        div.appendChild(img);
                        div.appendChild(btn);
                        galleryPreview.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            }

            function updateGalleryFiles() {
                const newDt = new DataTransfer();
                const items = galleryPreview.querySelectorAll('.gallery-item');

                items.forEach((item, index) => {
                    if (item.file) {
                        newDt.items.add(item.file);

                        // Update badge
                        const existingBadge = item.querySelector('.badge');
                        if (existingBadge) existingBadge.remove();

                        if (index === 0) {
                            const badge = document.createElement('div');
                            badge.className = 'position-absolute bottom-0 start-0 m-1 badge bg-primary';
                            badge.innerText = 'Ảnh đại diện';
                            item.appendChild(badge);
                        }
                    }
                });

                galleryDataTransfer = newDt;
                galleryInput.files = galleryDataTransfer.files;
            }
        }

        enableVariantsToggle.addEventListener('change', function () {
            if (this.checked) {
                quickVariantsCard.style.display = 'block';
                singlePriceInputs.style.display = 'none';
                priceVariantsMessage.style.display = 'block';
                mainPriceInput.removeAttribute('required');

                // Update slug preview on show
                updateInlineSlugPreview();
            } else {
                quickVariantsCard.style.display = 'none';
                singlePriceInputs.style.display = 'block';
                priceVariantsMessage.style.display = 'none';
                mainPriceInput.setAttribute('required', 'required');
            }
        });

        // Slug Tools
        function makeSlug(str) {
            return str.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd').replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
        }

        function updateInlineSlugPreview() {
            const pSlug = productSlugInput.value || makeSlug(productNameInput.value || 'product');
            const vSlug = inlineSlug.value || makeSlug(inlineName.value || '');
            inlineSlugPreview.textContent = pSlug + '-' + vSlug;
        }

        inlineName.addEventListener('input', function () {
            inlineSlug.value = makeSlug(this.value);
            updateInlineSlugPreview();
        });

        inlineSlug.addEventListener('input', function () {
            updateInlineSlugPreview();
        });

        productSlugInput.addEventListener('input', updateInlineSlugPreview);
        productNameInput.addEventListener('input', updateInlineSlugPreview);

        // Add Variant Action
        btnAddInline.addEventListener('click', function () {
            const name = inlineName.value;
            const price = inlinePrice.value;

            if (!name || !price) {
                alert('Vui lòng nhập Tên gói và Giá bán!');
                return;
            }

            // Hide empty message
            if (noVariantsMsg) noVariantsMsg.closest('tr').style.display = 'none';

            const slug = inlineSlug.value;
            const salePrice = inlineSalePrice.value;
            const stock = inlineStock.value;
            const delivery = inlineDelivery.value;
            const title = inlineTitle.value;
            const fileInput = inlineImage; // The actual input element

            // Add visible row
            const row = document.createElement('tr');
            row.id = `v-row-${variantIndex}`;
            row.innerHTML = `
                <td><strong>${name}</strong><br><small class="text-muted">${slug}</small></td>
                <td>${Number(price).toLocaleString()}đ</td>
                <td>${stock}</td>
                <td>${fileInput.files.length > 0 ? '<i class="fas fa-image text-success"></i>' : '-'}</td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeVariant(${variantIndex})"><i class="fas fa-times"></i></button></td>
            `;
            variantsTbody.appendChild(row);

            // Add hidden inputs
            let html = `
                <input type="hidden" name="new_variants[${variantIndex}][name]" value="${name}">
                <input type="hidden" name="new_variants[${variantIndex}][slug]" value="${slug}">
                <input type="hidden" name="new_variants[${variantIndex}][price]" value="${price}">
                <input type="hidden" name="new_variants[${variantIndex}][sale_price]" value="${salePrice}">
                <input type="hidden" name="new_variants[${variantIndex}][stock]" value="${stock}">
                <input type="hidden" name="new_variants[${variantIndex}][delivery_type]" value="${delivery}">
                <input type="hidden" name="new_variants[${variantIndex}][title]" value="${title}">
            `;

            // Move file input if present
            const container = document.createElement('div');
            container.id = `v-data-${variantIndex}`;
            container.innerHTML = html;

            if (fileInput.files.length > 0) {
                const newFileInput = fileInput.cloneNode(true);
                newFileInput.value = ''; // Reset for next add

                fileInput.name = `new_variants[${variantIndex}][image]`;
                fileInput.id = '';
                fileInput.className = 'd-none';

                container.appendChild(fileInput);

                // Replace inline input with reset clone
                fileInput.parentNode.replaceChild(newFileInput, fileInput);
            }

            variantsHiddenInputs.appendChild(container);

            variantIndex++;
            variantCountDisplay.innerText = variantIndex;

            // Clear inputs
            inlineName.value = '';
            inlineSlug.value = '';
            inlinePrice.value = '';
            inlineSalePrice.value = '';
            inlineStock.value = '0';
            inlineTitle.value = '';
            // Image already cleared by clone replacement
        });

        window.removeVariant = function (index) {
            const row = document.getElementById(`v-row-${index}`);
            const data = document.getElementById(`v-data-${index}`);
            if (row) row.remove();
            if (data) data.remove();

            // Check if empty
            if (variantsTbody.querySelectorAll('tr:not(#no-variants-msg)').length === 1) { // Only msg row left?
                // Actually logic is: if 1 row and it is msg, show it. If >1 rows, check if msg is hidden.
                // Correct logic:
                const rows = variantsTbody.querySelectorAll('tr');
                // One row is the message row (hidden or shown) plus actual rows
                const actualRows = Array.from(rows).filter(r => r.id !== 'no-variants-msg' && !r.querySelector('#no-variants-msg'));
                if (actualRows.length === 0 && noVariantsMsg) {
                    noVariantsMsg.closest('tr').style.display = 'table-row';
                }
                variantIndex--; // Just decrement count? No unique IDs used. Display count only.
                // Wait, variantIndex should only increment to keep IDs unique. 
                // We should update display count based on actualRows length
                variantCountDisplay.innerText = actualRows.length;
            } else {
                // Update count
                const actualRows = Array.from(variantsTbody.querySelectorAll('tr')).filter(r => r.id && r.id.startsWith('v-row-'));
                variantCountDisplay.innerText = actualRows.length;
            }
        };

        // Initialize Quill Editor (Existing Code)
        document.addEventListener('DOMContentLoaded', function () {
            const editors = document.querySelectorAll('.tinymce-editor');
            editors.forEach(function (textarea) {
                // Create editor div
                const editorDiv = document.createElement('div');
                editorDiv.style.height = '300px';
                editorDiv.style.backgroundColor = 'white';

                // Insert editor before textarea
                textarea.style.display = 'none';
                textarea.parentNode.insertBefore(editorDiv, textarea);

                // Initialize Quill
                const quill = new Quill(editorDiv, {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            [{ 'align': [] }],
                            ['link', 'image', 'video'],
                            ['clean']
                        ]
                    }
                });

                // Set initial content
                if (textarea.value) {
                    quill.root.innerHTML = textarea.value;
                }

                // Sync content back to textarea on change
                quill.on('text-change', function () {
                    textarea.value = quill.root.innerHTML;
                });

                // Sync on form submit
                textarea.closest('form').addEventListener('submit', function () {
                    textarea.value = quill.root.innerHTML;
                });
            });

            // Product Slug listener
            productSlugInput.addEventListener('input', function () {
                if (this.value !== '') {
                    // Manual logic handled in original code
                }
            });

            productNameInput.addEventListener('input', function () {
                const slug = makeSlug(this.value);
                productSlugInput.value = slug;
            });

            // Image preview
            const imageFile = document.getElementById('image-file');
            const imagePreview = document.getElementById('image-preview');
            const previewImg = imagePreview.querySelector('img');

            imageFile.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                        imagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    imagePreview.style.display = 'none';
                }
            });
        });
    </script>

    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
</body>

</html>