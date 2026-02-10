<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Lấy tham số
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : '';
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : null;
$sortBy = $_GET['sort'] ?? 'default';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;

// Nếu có category slug, tìm category ID
$categoryId = 0;
if (!empty($categorySlug)) {
    $category = db()->query("SELECT id FROM categories WHERE slug = ? AND status = 'active'", [$categorySlug])->fetch();
    if ($category) {
        $categoryId = $category['id'];
    }
}

// Build query
$where = ["p.status = 'active'", "v.id IS NOT NULL"];
$params = [];

if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

// Add price range filter (lọc theo giá variant thực tế)
if ($minPrice !== null) {
    $where[] = "COALESCE(v.sale_price, v.price) >= ?";
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $where[] = "COALESCE(v.sale_price, v.price) <= ?";
    $params[] = $maxPrice;
}

$whereClause = implode(' AND ', $where);

// Đếm tổng số sản phẩm
$countSql = "SELECT COUNT(DISTINCT p.id) as total FROM products p
             LEFT JOIN product_variants v ON p.id = v.product_id
             WHERE $whereClause";
$stmt = db()->query($countSql, $params);
$totalProducts = $stmt->fetch()['total'];
$totalPages = ceil($totalProducts / $perPage);

// Build ORDER BY clause
$orderBy = "p.featured DESC, p.created_at DESC";
switch ($sortBy) {
    case 'price_asc':
        $orderBy = "COALESCE(v.sale_price, v.price) ASC";
        break;
    case 'price_desc':
        $orderBy = "COALESCE(v.sale_price, v.price) DESC";
        break;
    case 'name_asc':
        $orderBy = "p.name ASC";
        break;
    case 'name_desc':
        $orderBy = "p.name DESC";
        break;
    case 'newest':
        $orderBy = "p.created_at DESC";
        break;
}

// Lấy sản phẩm
$offset = ($page - 1) * $perPage;
$sql = "SELECT p.*, c.name as category_name,
               v.name as variant_name, v.variant_title, v.variant_image,
               v.price as variant_price, v.sale_price as variant_sale_price,
               v.slug as variant_slug, v.stock_quantity as variant_stock,
               (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_variants v ON p.id = v.product_id AND v.is_main = 1
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$products = db()->query($sql, $params)->fetchAll();

// Generate HTML
ob_start();
foreach ($products as $product):
    // Ưu tiên hiển thị thông tin gói chính nếu có
    if (!empty($product['variant_title'])) {
        $displayTitle = $product['variant_title'];
    } elseif (!empty($product['variant_name'])) {
        $displayTitle = $product['name'] . ' ' . $product['variant_name'];
    } else {
        $displayTitle = $product['name'];
    }

    // Image: variant_image > first_gallery_image > product_image
    if (!empty($product['variant_image'])) {
        $displayImage = $product['variant_image'];
    } elseif (!empty($product['first_gallery_image'])) {
        $displayImage = $product['first_gallery_image'];
    } else {
        $displayImage = $product['image'];
    }

    $displayPrice = !empty($product['variant_price']) ? $product['variant_price'] : $product['price'];
    $displaySalePrice = isset($product['variant_sale_price']) ? $product['variant_sale_price'] : $product['sale_price'];
    $displayStock = isset($product['variant_stock']) ? $product['variant_stock'] : $product['stock_quantity'];

    // URL ưu tiên variant slug nếu có
    if (!empty($product['variant_slug'])) {
        $productUrl = '/' . $product['variant_slug'];
    } elseif (!empty($product['slug'])) {
        $productUrl = '/' . $product['slug'];
    } else {
        $productUrl = '/product?id=' . $product['id'];
    }

    $currentPrice = $displaySalePrice ?? $displayPrice;
    $hasDiscount = !empty($displaySalePrice);
    ?>
    <div class="col-xl-3 col-lg-4 col-md-6 col-6">
        <div class="card h-100 shadow-sm product-card">
            <a href="<?= $productUrl ?>" class="text-decoration-none">
                <div class="position-relative">
                    <img src="<?= getProductImage($displayImage, $displayTitle) ?>"
                         class="card-img-top"
                         alt="<?= e($displayTitle) ?>">

                    <?php if ($hasDiscount): ?>
                        <?php
                        $discountPercent = round((($displayPrice - $displaySalePrice) / $displayPrice) * 100);
                        ?>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-danger">
                            -<?= $discountPercent ?>%
                        </span>
                    <?php endif; ?>
                </div>

                <div class="card-body d-flex flex-column">
                    <h6 class="card-title text-dark mb-3 fw-semibold" style="min-height: 40px; line-height: 1.4;">
                        <?= e($displayTitle) ?>
                    </h6>

                    <div class="mt-auto">
                        <div class="d-flex align-items-baseline gap-2 mb-2">
                            <span class="h4 text-primary mb-0 fw-bold"><?= formatMoney($currentPrice) ?></span>
                            <?php if ($hasDiscount): ?>
                                <span class="text-muted text-decoration-line-through" style="font-size: 0.875rem;">
                                    <?= formatMoney($displayPrice) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($hasDiscount): ?>
                            <?php
                            $saved = $displayPrice - $displaySalePrice;
                            ?>
                            <div class="text-success small">
                                <i class="fas fa-tags me-1"></i>Tiết kiệm <?= formatMoney($saved) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
    </div>
<?php endforeach;
$html = ob_get_clean();

// Return JSON response
echo json_encode([
    'success' => true,
    'html' => $html,
    'hasMore' => $page < $totalPages,
    'currentPage' => $page,
    'totalPages' => $totalPages
]);
