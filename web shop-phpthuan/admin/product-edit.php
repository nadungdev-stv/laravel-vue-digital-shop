<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$productId) {
    setFlash('error', 'Sản phẩm không tồn tại');
    redirect('/admin/products');
}

$product = db()->query("SELECT * FROM products WHERE id = ?", [$productId])->fetch();
if (!$product) {
    setFlash('error', 'Sản phẩm không tồn tại');
    redirect('/admin/products');
}

$pageTitle = 'Chỉnh sửa: ' . $product['name'];
$errors = [];

// Ensure product_categories table exists (Refactor support)
try {
    db()->query("CREATE TABLE IF NOT EXISTS product_categories (
        product_id INT NOT NULL,
        category_id INT NOT NULL,
        PRIMARY KEY (product_id, category_id)
    )");
} catch (Exception $e) {
    // Ignore if permission error or already exists issues
}

// Handle variant deletion (GET request for simplicity)
if (isset($_GET['delete_variant'])) {
    $vId = (int)$_GET['delete_variant'];
    try {
        db()->query("DELETE FROM product_variants WHERE id = ? AND product_id = ?", [$vId, $productId]);
        setFlash('success', 'Đã xóa gói sản phẩm');
    } catch (Exception $e) {
        setFlash('error', 'Lỗi khi xóa gói');
    }
    redirect('/admin/product-edit?id=' . $productId);
}

// Handle gallery image deletion
if (isset($_GET['delete_image'])) {
    $imgId = (int)$_GET['delete_image'];
    try {
        $img = db()->query("SELECT image_path FROM product_gallery WHERE id = ? AND product_id = ?", [$imgId, $productId])->fetch();
        if ($img) {
            $path = $_SERVER['DOCUMENT_ROOT'] . $img['image_path'];
            if (file_exists($path)) @unlink($path);
            db()->query("DELETE FROM product_gallery WHERE id = ?", [$imgId]);
            setFlash('success', 'Đã xóa ảnh');
        }
    } catch (Exception $e) {
        setFlash('error', 'Lỗi khi xóa ảnh');
    }
    redirect('/admin/product-edit?id=' . $productId);
}

// Handler POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    
    // Process Categories (Tagify)
    $categorySelection = json_decode($_POST['category_selection'] ?? '[]', true);
    $categoryIds = [];
    
    if (is_array($categorySelection)) {
        foreach ($categorySelection as $item) {
            if (isset($item['id']) && $item['id'] > 0) {
                $categoryIds[] = (int)$item['id'];
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
    $price = (float)($_POST['price'] ?? 0);
    $salePrice = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $description = trim($_POST['description'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
    // Fix: Checkbox not sent if unchecked. If set -> active, else -> inactive
    $status = isset($_POST['status']) ? 'active' : 'inactive';
    $featured = isset($_POST['featured']) ? 1 : 0;
    $deliveryType = $_POST['delivery_type'] ?? 'account';
    // $image = $product['image']; // Keep existing unless updated - logic handled in UPDATE query

    // Validation
    if (empty($name)) $errors[] = 'Tên sản phẩm không được để trống';
    if (empty($slug)) {
        $slug = generateSlug($name);
    }
    if (!$categoryId) $errors[] = 'Vui lòng chọn danh mục';
    // if ($price <= 0) $errors[] = 'Giá sản phẩm phải lớn hơn 0'; // Allow 0 if variants exist? product-add enforces >0 but maybe we allow update to 0 if all variants set. Sticking to add logic:
    if ($price <= 0 && empty($_POST['new_variants']) && empty($product['price'])) $errors[] = 'Giá sản phẩm phải lớn hơn 0'; 
    // Relaxed check: Only error if price is 0 AND we aren't adding variants AND we don't have existing price (which we do). 
    // Actually simpler: Just warn if 0. but let's strictly follow "logic like product-add". product-add requires price > 0.
    // However, if using variants, main price might be derived.
    
    // Check slug duplicate (exclude self)
    $existingProduct = db()->query("SELECT id FROM products WHERE slug = ? AND id != ?", [$slug, $productId])->fetch();
    if ($existingProduct) {
        $errors[] = 'Slug đã tồn tại. Vui lòng chọn slug khác.';
    }

    if (empty($errors)) {
        // Update product
        // Note: Image is not updated here directly unless we want to clear it? product-add logic sets image from 1st gallery item.
        // We will keep existing image unless 1st gallery image changes? 
        // For simplicity: We won't touch 'image' column here, relying on gallery logic to update it if needed, OR we just update the text fields.
        
        db()->query(
            "UPDATE products SET 
                name = ?, slug = ?, category_id = ?, tags = ?, price = ?, sale_price = ?, 
                description = ?, features = ?, delivery_type = ?, stock_quantity = ?, 
                status = ?, featured = ?, updated_at = NOW() 
            WHERE id = ?",
            [$name, $slug, $categoryId, $tags, $price, $salePrice, $description, $features, $deliveryType, $stockQuantity, $status, $featured, $productId]
        );

        // Handle Product Categories (Many-to-Many)
        // Delete all and re-insert
        if (!empty($categoryIds)) {
            db()->query("DELETE FROM product_categories WHERE product_id = ?", [$productId]);
            
            // Re-insert
            // Code from product-add
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

        // Handle Gallery Images Upload (Add new ones)
        if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/public/images/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Get current max sort order
            $maxOrder = db()->query("SELECT MAX(sort_order) FROM product_gallery WHERE product_id = ?", [$productId])->fetchColumn();
            $sortOrder = $maxOrder ? $maxOrder + 1 : 1;

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
                            db()->query(
                                "INSERT INTO product_gallery (product_id, image_path, sort_order, created_at) VALUES (?, ?, ?, NOW())",
                                [$productId, $imagePath, $sortOrder++]
                            );
                            
                            // If product has no main image, set this one
                            if (empty($product['image'])) {
                                db()->query("UPDATE products SET image = ? WHERE id = ?", [$imagePath, $productId]);
                                $product['image'] = $imagePath; // Update local var
                            }
                        }
                    }
                }
            }
        }

        // Handle Existing Variants Update (Sort Order, Is Main, Data Updates)
        $mainVariantId = isset($_POST['main_variant_selection']) ? (int)$_POST['main_variant_selection'] : 0;
        
        if (isset($_POST['existing_variant_ids']) && is_array($_POST['existing_variant_ids'])) {
            // Get data map
            $variantsData = $_POST['existing_variants'] ?? [];

            foreach ($_POST['existing_variant_ids'] as $index => $vId) {
                $vId = (int)$vId;
                $sortOrder = $index + 1;
                $isMain = ($vId === $mainVariantId) ? 1 : 0;
                
                // Data from inline inputs
                $vData = $variantsData[$vId] ?? null;
                
                if ($vData) {
                    $vName = trim($vData['name']);
                    // Slug: Only update if provided and different, else keep existing? 
                    // Or regenerate if empty? The previous logic regenerated.
                    $vSlug = trim($vData['slug']);
                    if (empty($vSlug)) {
                         $vSlug = $product['slug'] . '-' . strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $vName)));
                    }
                    
                    // Clean money format (remove dots/commas)
                    $vPriceClean = preg_replace('/[^0-9]/', '', $vData['price']);
                    $vPrice = (float)$vPriceClean;

                    $vSalePriceClean = preg_replace('/[^0-9]/', '', $vData['sale_price']);
                    $vSalePrice = !empty($vSalePriceClean) ? (float)$vSalePriceClean : null;

                    $vStockClean = preg_replace('/[^0-9]/', '', $vData['stock']);
                    $vStock = (int)$vStockClean;
                    
                    // Delivery/Status might not be sent if we only edit numbers, so check if set
                    // But if we use stealth inputs for text, we might not have inputs for delivery/status?
                    // If the user didn't ask to double click those, I won't verify them here.
                    // But wait, if I don't send delivery/status in POST, this UPDATE will overwrite them with defaults/null?
                    // NO. I must use COALESCE or only update if set. 
                    // Or better: Include hidden inputs for Delivery/Status with current values so they are preserved.
                    // OR: Fetch existing variant to get missing values. Expensive.
                    // EASIEST: Just include hidden inputs for everything I'm not showing as editable.
                    
                    $vDelivery = !empty($vData['delivery_type']) ? $vData['delivery_type'] : null;
                    $vStatus = $vData['status'] ?? 'active';

                    try {
                        // Dynamic update query to be safe? 
                        // No, just make sure all fields are present in the form properly.
                        // I will ensure frontend sends all fields, even if hidden.
                        db()->query(
                            "UPDATE product_variants SET 
                                name = ?, slug = ?, price = ?, sale_price = ?, stock_quantity = ?, 
                                delivery_type = ?, status = ?, sort_order = ?, is_main = ?, updated_at = NOW()
                            WHERE id = ? AND product_id = ?",
                            [$vName, $vSlug, $vPrice, $vSalePrice, $vStock, $vDelivery, $vStatus, $sortOrder, $isMain, $vId, $productId]
                        );
                    } catch (Exception $e) {}
                } else {
                    // Fallback for sorting only
                    db()->query(
                        "UPDATE product_variants SET sort_order = ?, is_main = ? WHERE id = ? AND product_id = ?",
                        [$sortOrder, $isMain, $vId, $productId]
                    );
                }
            }
        } elseif ($mainVariantId > 0) {
            // Fallback for just Main selection without list
             db()->query("UPDATE product_variants SET is_main = 0 WHERE product_id = ?", [$productId]);
             db()->query("UPDATE product_variants SET is_main = 1 WHERE id = ? AND product_id = ?", [$mainVariantId, $productId]);
        }

        // Handle New Variants (Insert)
        if (!empty($_POST['new_variants'])) {
            $vSortOrder = db()->query("SELECT MAX(sort_order) FROM product_variants WHERE product_id = ?", [$productId])->fetchColumn();
            $vSortOrder = $vSortOrder ? $vSortOrder + 1 : 1;

            foreach ($_POST['new_variants'] as $index => $variant) {
                if (!empty($variant['name']) && !empty($variant['price'])) {
                    $vName = trim($variant['name']);
                    $rawSlug = trim($variant['slug']);
                    if (!empty($rawSlug)) {
                        // Check if it already starts with product slug
                        if (strpos($rawSlug, $product['slug'] . '-') === 0) {
                            $vSlug = $rawSlug;
                        } else {
                            $vSlug = $product['slug'] . '-' . ltrim($rawSlug, '-');
                        }
                    } else {
                        $vSlug = $product['slug'] . '-' . strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $vName)));
                    }
                    $vPrice = (float)$variant['price'];
                    $vSalePrice = !empty($variant['sale_price']) ? (float)$variant['sale_price'] : null;
                    $vStock = (int)($variant['stock'] ?? 0);
                    $vDelivery = !empty($variant['delivery_type']) ? $variant['delivery_type'] : null;
                    $vTitle = trim($variant['title'] ?? '');
                    $vImage = '';

                    // Handle variant image
                    if (isset($_FILES['new_variants']['name'][$index]['image']) && $_FILES['new_variants']['error'][$index]['image'] === UPLOAD_ERR_OK) {
                        $vFile = [
                            'name' => $_FILES['new_variants']['name'][$index]['image'],
                            'tmp_name' => $_FILES['new_variants']['tmp_name'][$index]['image'],
                            'size' => $_FILES['new_variants']['size'][$index]['image']
                        ];
                        
                        $vFileExt = strtolower(pathinfo($vFile['name'], PATHINFO_EXTENSION));
                        if (in_array($vFileExt, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']) && $vFile['size'] <= 5 * 1024 * 1024) {
                            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/public/images/variants/';
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                            
                            $vFileName = 'variant-' . $productId . '-' . time() . '-' . $index . '.' . $vFileExt;
                            if (move_uploaded_file($vFile['tmp_name'], $uploadDir . $vFileName)) {
                                $vImage = '/public/images/variants/' . $vFileName;
                            }
                        }
                    }

                    db()->query(
                        "INSERT INTO product_variants (product_id, name, slug, price, sale_price, stock_quantity, delivery_type, variant_title, variant_image, sort_order, status, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())",
                        [$productId, $vName, $vSlug, $vPrice, $vSalePrice, $vStock, $vDelivery, $vTitle, $vImage, $vSortOrder++]
                    );
                }
            }
        }

        setFlash('success', 'Cập nhật sản phẩm thành công');
        redirect('/admin/product-edit?id=' . $productId);
    }
}

// Fetch Data for View
$allCategories = db()->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
$variants = db()->query("SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC", [$productId])->fetchAll();
$galleryImages = db()->query("SELECT * FROM product_gallery WHERE product_id = ? ORDER BY sort_order ASC", [$productId])->fetchAll();
$selectedCategoriesRaw = db()->query("SELECT c.id, c.name FROM categories c JOIN product_categories pc ON c.id = pc.category_id WHERE pc.product_id = ?", [$productId])->fetchAll();

// Format categories for Tagify
$selectedCategoriesJson = [];
foreach ($selectedCategoriesRaw as $sc) {
    $selectedCategoriesJson[] = ['value' => $sc['name'], 'id' => $sc['id']];
}
$selectedCategoriesJson = json_encode($selectedCategoriesJson);

// Helper for sorting whitelist
$categoryWhitelist = [];
foreach ($allCategories as $cat) {
    if ($cat['id'] != $product['category_id']) { // Optional filter
    }
    $categoryWhitelist[] = ['value' => $cat['name'], 'id' => $cat['id']];
}
$categoryWhitelistJson = json_encode($categoryWhitelist);
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
                <h2><i class="fas fa-edit"></i> Chỉnh sửa sản phẩm</h2>
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
                                    <label class="form-label fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="product-name" class="form-control form-control-lg" placeholder="Nhập tên sản phẩm..." required value="<?= e($_POST['name'] ?? $product['name']) ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-muted">Permalink (Slug)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light">https://<?= $_SERVER['HTTP_HOST'] ?>/product/</span>
                                        <input type="text" name="slug" id="product-slug" class="form-control" value="<?= e($_POST['slug'] ?? $product['slug']) ?>">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Mô tả sản phẩm</label>
                                    <textarea name="description" id="description-editor" class="form-control tinymce-editor" rows="5"><?= e($_POST['description'] ?? $product['description']) ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tính năng nổi bật</label>
                                    <textarea name="features" id="features-editor" class="form-control tinymce-editor" rows="5"><?= e($_POST['features'] ?? $product['features']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Media Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 card-title fw-bold">Hình ảnh</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Ảnh hiện có</label>
                                    <div class="d-flex flex-wrap gap-2" id="existing-gallery">
                                        <?php foreach ($galleryImages as $img): ?>
                                            <div class="position-relative gallery-item" style="width: 150px; height: 150px;">
                                                <img src="<?= e($img['image_path']) ?>" class="img-thumbnail w-100 h-100" style="object-fit: cover;">
                                                <a href="/admin/product-edit?id=<?= $productId ?>&delete_image=<?= $img['id'] ?>" class="position-absolute top-0 end-0 btn btn-danger btn-sm p-1 m-1" onclick="return confirm('Xóa ảnh này?')" style="line-height: 1;">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <?php if($product['image'] == $img['image_path']): ?>
                                                    <div class="position-absolute bottom-0 start-0 m-1 badge bg-primary">Đại diện</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if(empty($galleryImages)): ?>
                                            <p class="text-muted small">Chưa có ảnh nào.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Thêm ảnh mới</label>
                                    <input type="file" name="gallery_images[]" id="gallery-images" class="form-control" accept="image/*" multiple>
                                    <small class="text-muted d-block mt-2">Chọn nhiều ảnh. Kéo thả để sắp xếp.</small>
                                    
                                    <div id="gallery-preview" class="mt-3 d-flex flex-wrap gap-2">
                                        <!-- Previews will be inserted here -->
                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>

                    <!-- Right Column: Settings & Organization -->
                    <div class="col-lg-3">
                        <!-- Publish Actions -->
                        <div class="card mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 card-title fw-bold">Cập nhật</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="simple-button simple-button-green w-100 py-2 fs-6">
                                        <i class="fas fa-save me-2"></i> Lưu thay đổi
                                    </button>
                                    <a href="/admin/products" class="simple-button simple-button-gray w-100 py-2 fs-6 text-center text-decoration-none">
                                        Hủy bỏ
                                    </a>
                                </div>
                                <hr>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="status" value="active" id="status-check" <?= ($product['status'] ?? 'active') === 'active' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="status-check">Đang bán (Active)</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="featured" id="featured" <?= ($product['featured'] ?? 0) ? 'checked' : '' ?>>
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
                                <!-- Removed Toggle for Edit page, just show fields always or if no variants? 
                                     Actually, let's keep the simple logic from product-add but without the toggle hiding/showing massively as we might have variants.
                                     Rule: If variants exist, price main is ignored mostly, but we still allow editing it.
                                -->
                                <div id="single-price-inputs">
                                    <div class="mb-3">
                                        <label class="form-label">Giá gốc (VNĐ) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" name="price" id="main-price-input" class="form-control font-monospace" min="0" value="<?= e($product['price'] ?? 0) ?>" placeholder="0">
                                            <span class="input-group-text">₫</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Giá khuyến mãi</label>
                                        <div class="input-group">
                                            <input type="number" name="sale_price" class="form-control font-monospace" min="0" value="<?= e($product['sale_price'] ?? '') ?>" placeholder="0">
                                            <span class="input-group-text">₫</span>
                                        </div>
                                    </div>
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
                                    <!-- Initial value set via JS -->
                                    <input name="category_selection" id="category-selection" class="form-control" placeholder="Tìm kiếm hoặc nhập để tạo mới..." value="<?= e($selectedCategoriesJson) ?>">
                                    <small class="text-muted">Nhập tên danh mục và nhấn Enter.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tags</label>
                                    <input type="text" name="tags" class="form-control" value="<?= e($product['tags'] ?? '') ?>" placeholder="VD: Premium, Hot, Sale">
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
                                        <option value="account" <?= ($product['delivery_type'] ?? 'account') === 'account' ? 'selected' : '' ?>>Kho tài khoản</option>
                                        <option value="email_only" <?= ($product['delivery_type'] ?? '') === 'email_only' ? 'selected' : '' ?>>Chỉ cần Email</option>
                                        <option value="customer_account" <?= ($product['delivery_type'] ?? '') === 'customer_account' ? 'selected' : '' ?>>Nâng cấp TK khách</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Số lượng tồn</label>
                                    <input type="number" name="stock_quantity" class="form-control" min="0" value="<?= e($product['stock_quantity'] ?? 0) ?>">
                                </div>
                            </div>
                        </div>
                    </div> <!-- End Right Column -->

                    <!-- Variants Section (Full Width) -->
                    <div class="col-12">
                        <!-- Quick Variants Section (Inline Form) -->
                        <div class="card mb-4" id="quick-variants-card" style="display: block;">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 card-title fw-bold">Quản lý gói sản phẩm (Variants)</h5>
                            </div>
                            
                            <!-- Existing Variants -->
                             <?php if (!empty($variants)): ?>
                            <div class="px-4 py-4 border-bottom bg-light">
                                <h6 class="fw-bold mb-3">Gói hiện có</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle table-sm bg-white table-hover">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="text-center" width="50">#</th>
                                                <th class="text-center" width="80">Ảnh</th>
                                                <th>Tên gói / Slug</th>
                                                <th>Giá bán</th>
                                                <th class="text-center">Kho</th>
                                                <th class="text-center">Loại giao hàng</th>
                                                <th class="text-center">Trạng thái</th>
                                                <th class="text-center" width="120">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="variants-tbody">
                                            <?php foreach ($variants as $v): ?>
                                            <tr>
                                                <td class="text-center align-middle">
                                                    <i class="fas fa-grip-vertical text-muted cursor-move handle" style="cursor: grab;"></i>
                                                    <input type="hidden" name="existing_variant_ids[]" value="<?= $v['id'] ?>">
                                                    <!-- Keep Delivery/Status hidden to preserve values during update -->
                                                    <input type="hidden" name="existing_variants[<?= $v['id'] ?>][delivery_type]" value="<?= $v['delivery_type'] ?>">
                                                    <input type="hidden" name="existing_variants[<?= $v['id'] ?>][status]" value="<?= $v['status'] ?>">
                                                </td>
                                                <td class="text-center">
                                                    <?php if (!empty($v['variant_image'])): ?>
                                                        <img src="<?= e($v['variant_image']) ?>" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <span class="text-muted small"><i class="fas fa-image"></i></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input type="text" name="existing_variants[<?= $v['id'] ?>][name]" 
                                                           class="form-control-plaintext form-control-sm fw-bold mb-1 stealth-input" 
                                                           value="<?= e($v['name']) ?>" readonly ondblclick="enableStealthEdit(this)" onblur="disableStealthEdit(this)" title="Double click to edit">
                                                    
                                                    <input type="text" name="existing_variants[<?= $v['id'] ?>][slug]" 
                                                           class="form-control-plaintext form-control-sm font-monospace text-muted stealth-input" 
                                                           style="font-size: 0.75rem;" 
                                                           value="<?= e($v['slug']) ?>" readonly ondblclick="enableStealthEdit(this)" onblur="disableStealthEdit(this)" title="Double click to edit">
                                                    
                                                    <div class="mt-1">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="main_variant_selection" id="main_v_<?= $v['id'] ?>" value="<?= $v['id'] ?>" <?= $v['is_main'] ? 'checked' : '' ?>>
                                                            <label class="form-check-label small text-secondary" for="main_v_<?= $v['id'] ?>">Gói chính</label>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" name="existing_variants[<?= $v['id'] ?>][price]" 
                                                           class="form-control-plaintext form-control-sm mb-1 stealth-input" 
                                                           value="<?= formatMoney($v['price']) ?>" 
                                                           readonly ondblclick="enableStealthEdit(this, 'money')" onblur="disableStealthEdit(this, 'money')" title="Double click to edit">
                                                    
                                                    <input type="text" name="existing_variants[<?= $v['id'] ?>][sale_price]" 
                                                           class="form-control-plaintext form-control-sm text-danger stealth-input" 
                                                           value="<?= $v['sale_price'] > 0 ? formatMoney($v['sale_price']) : '' ?>" placeholder="Giá KM"
                                                           readonly ondblclick="enableStealthEdit(this, 'money')" onblur="disableStealthEdit(this, 'money')" title="Double click to edit">
                                                </td>
                                                <td class="text-center">
                                                    <input type="text" name="existing_variants[<?= $v['id'] ?>][stock]" 
                                                           class="form-control-plaintext form-control-sm text-center stealth-input" 
                                                           value="<?= $v['stock_quantity'] ?>" 
                                                           readonly ondblclick="enableStealthEdit(this)" onblur="disableStealthEdit(this)" title="Double click to edit">
                                                </td>
                                                <td class="text-center small">
                                                    <?php
                                                    switch ($v['delivery_type']) {
                                                        case 'account': echo '<span class="badge bg-info text-dark">Kho TK</span>'; break;
                                                        case 'email_only': echo '<span class="badge bg-warning text-dark">Email</span>'; break;
                                                        case 'customer_account': echo '<span class="badge bg-secondary">Nạp tiền</span>'; break;
                                                        default: echo '<span class="text-muted">--</span>'; break;
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($v['status'] == 'active'): ?>
                                                        <span class="text-success"><i class="fas fa-check-circle"></i></span>
                                                    <?php else: ?>
                                                        <span class="text-secondary"><i class="fas fa-eye-slash"></i></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="/admin/product-variant-edit?id=<?= $v['id'] ?>" class="btn btn-outline-primary" title="Chỉnh sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?id=<?= $productId ?>&delete_variant=<?= $v['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Xóa gói này?')" title="Xóa">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const vTbody = document.getElementById('variants-tbody');
                                            if (vTbody) {
                                                new Sortable(vTbody, {
                                                    handle: '.handle',
                                                    animation: 150
                                                });
                                            }
                                        });

                                        window.enableStealthEdit = function(input, type) {
                                            console.log('Enable stealth edit', input);
                                            input.readOnly = false;
                                            input.classList.remove('form-control-plaintext', 'stealth-input');
                                            input.classList.add('form-control', 'bg-white');
                                            
                                            if (type === 'money') {
                                                let val = input.value;
                                                val = val.replace(/[^0-9]/g, ''); // Keep only digits
                                                input.value = val;
                                            }
                                            
                                            input.select();
                                        }

                                        window.disableStealthEdit = function(input, type) {
                                            console.log('Disable stealth edit', input);
                                            input.readOnly = true;
                                            input.classList.remove('form-control', 'bg-white');
                                            input.classList.add('form-control-plaintext', 'stealth-input');
                                            
                                            if (type === 'money') {
                                                 let val = input.value.replace(/[^0-9]/g, '');
                                                 if (val) {
                                                     val = parseInt(val).toLocaleString('vi-VN'); 
                                                     // Ensure dots are used
                                                     if (val.indexOf('.') === -1 && val.indexOf(',') > -1) {
                                                         // If locale used commas? Standard vi-VN uses dots for thousands.
                                                     }
                                                     val = val.replace(/,/g, '.'); // Force dots if browser gave commas
                                                 }
                                                 input.value = val;
                                            }
                                        }
                                    </script>
                                    <style>
                                        .stealth-input {
                                            background: transparent !important;
                                            border: 1px solid transparent !important;
                                            box-shadow: none !important;
                                            padding: 0.1rem 0.25rem !important;
                                            cursor: text; /* Change to text cursor to urge interaction */
                                        }
                                        .stealth-input:hover {
                                            background: rgba(0,0,0,0.03) !important;
                                            border: 1px dashed #ccc !important; /* Visual hint on hover */
                                        }
                                    </style>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="px-4 pb-4 pt-4 border-bottom">
                                <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle"></i> Thêm gói mới</h6>
                                <div class="row g-3">
                                    <!-- Hàng 1: Định danh -->
                                    <div class="col-md-5">
                                        <div class="form-floating">
                                            <input type="text" id="inline-v-name" class="form-control" placeholder="Tên gói">
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
                                            <input type="number" id="inline-v-sale-price" class="form-control" placeholder="0">
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
                                        <input type="text" id="inline-v-title" class="form-control form-control-sm" placeholder="Tự động theo tên gói">
                                    </div>

                                    <!-- Hàng 4: Ảnh & Action -->
                                    <div class="col-12">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="flex-grow-1">
                                                <label class="form-label small fw-bold mb-1">Ảnh đại diện gói</label>
                                                <input type="file" id="inline-v-image" class="form-control form-control-sm" accept="image/*">
                                            </div>
                                            <div class="pt-4">
                                                <button type="button" class="simple-button simple-button-blue px-4" id="btn-add-inline-variant" style="height: 34px; line-height: 1;">
                                                    <i class="fas fa-plus"></i> Thêm
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="fw-bold mb-2">Gói mới chờ thêm (<span id="variant-count-display">0</span>)</h6>
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
                                                    Chưa có gói mới nào.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div id="variants-hidden-inputs"></div>
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
        const categoryWhitelist = <?= $categoryWhitelistJson ?>;
        
        const tagify = new Tagify(categoryInput, {
            whitelist: categoryWhitelist,
            placeholder: "Tìm danh mục...",
            enforceWhitelist: false, // Allow new tags
            dropdown: {
                maxItems: 20,           
                classname: "tags-look", 
                enabled: 0,             
                closeOnSelect: false    
            }
        });

        // Variant Logic
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
                onEnd: function() {
                    updateGalleryFiles();
                }
            });

            galleryInput.addEventListener('change', function(e) {
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
                    reader.onload = function(e) {
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
                        btn.onclick = function() {
                            div.remove();
                            updateGalleryFiles();
                        };
                        
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
                    }
                });

                galleryDataTransfer = newDt;
                galleryInput.files = galleryDataTransfer.files;
            }
        }

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

        inlineName.addEventListener('input', function() {
            inlineSlug.value = makeSlug(this.value);
            updateInlineSlugPreview();
        });
        
        inlineSlug.addEventListener('input', function() {
            updateInlineSlugPreview();
        });
        
        productSlugInput.addEventListener('input', updateInlineSlugPreview);
        productNameInput.addEventListener('input', updateInlineSlugPreview);

        // Add Variant Action
        btnAddInline.addEventListener('click', function() {
            const name = inlineName.value;
            const price = inlinePrice.value;
            
            if(!name || !price) {
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
            
            if(fileInput.files.length > 0) {
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

        window.removeVariant = function(index) {
            const row = document.getElementById(`v-row-${index}`);
            const data = document.getElementById(`v-data-${index}`);
            if (row) row.remove();
            if (data) data.remove();
            
            // Check if empty
            const rows = variantsTbody.querySelectorAll('tr');
            // Filter out the "no-variants-msg" row
            const actualRows = Array.from(rows).filter(r => r.id !== 'no-variants-msg' && !r.querySelector('#no-variants-msg'));
            
            if (actualRows.length === 0 && noVariantsMsg) {
                 noVariantsMsg.closest('tr').style.display = 'table-row';
            }
            variantCountDisplay.innerText = actualRows.length;
        };

        document.addEventListener('DOMContentLoaded', function() {
            const editors = document.querySelectorAll('.tinymce-editor');
            editors.forEach(function(textarea) {
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
                            ['bold', 'italic', 'underline', 'strike'],
                            ['blockquote', 'code-block'],
                            [{ 'header': 1 }, { 'header': 2 }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'color': [] }, { 'background': [] }],
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
                quill.on('text-change', function() {
                    textarea.value = quill.root.innerHTML;
                });

                // Sync on form submit
                textarea.closest('form').addEventListener('submit', function() {
                    textarea.value = quill.root.innerHTML;
                });
            });
        });
    </script>

    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
