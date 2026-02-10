<?php
$pageTitle = 'Sản phẩm';

// Generate CSRF token for security
if (!isset($csrfToken)) {
    require_once __DIR__ . '/includes/helpers.php';
    initSession();
    $csrfToken = generateCSRFToken();
}

require_once __DIR__ . '/includes/header.php';

// Lấy tham số
$searchQuery = $_GET['q'] ?? '';
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : '';
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : null;
$sortBy = $_GET['sort'] ?? 'default';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;

// Lấy danh mục (chỉ hiện danh mục có sản phẩm)
$categories = db()->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    INNER JOIN products p ON c.id = p.category_id AND p.status = 'active'
    INNER JOIN product_variants v ON p.id = v.product_id
    WHERE c.status = 'active'
    GROUP BY c.id
    HAVING product_count > 0
    ORDER BY c.sort_order
")->fetchAll();

// Lấy giá min/max cho range slider
$priceRange = db()->query("
    SELECT
        MIN(COALESCE(v.sale_price, v.price)) as min_price,
        MAX(COALESCE(v.sale_price, v.price)) as max_price
    FROM products p
    INNER JOIN product_variants v ON p.id = v.product_id
    WHERE p.status = 'active'
")->fetch();
$priceRangeMin = (int)($priceRange['min_price'] ?? 0);
$priceRangeMax = (int)($priceRange['max_price'] ?? 1000000);

// Nếu có category slug, tìm category ID
$categoryId = 0;
if (!empty($categorySlug) && $categorySlug !== 'all') {
    $category = db()->query("SELECT id FROM categories WHERE slug = ? AND status = 'active'", [$categorySlug])->fetch();
    if ($category) {
        $categoryId = $category['id'];
    }
}

// Build SQL query
$sql = "SELECT p.*";
$params = [];
$keywords = [];

// Add search condition - tìm theo từng từ riêng lẻ với keyword match count
if (!empty($searchQuery)) {
    // Tách query thành các từ
    $keywords = preg_split('/\s+/', trim($searchQuery));
    $keywords = array_filter($keywords);

    $nameConditions = [];
    $descConditions = [];
    $featConditions = [];
    $searchParams = []; // Params riêng cho search conditions

    foreach ($keywords as $keyword) {
        $nameConditions[] = "LOWER(p.name) LIKE LOWER(?)";
        $descConditions[] = "LOWER(p.description) LIKE LOWER(?)";
        $featConditions[] = "LOWER(p.features) LIKE LOWER(?)";

        $searchParams[] = '%' . $keyword . '%';
        $searchParams[] = '%' . $keyword . '%';
        $searchParams[] = '%' . $keyword . '%';
    }

    $nameSQL = implode(' OR ', $nameConditions);
    $descSQL = implode(' OR ', $descConditions);
    $featSQL = implode(' OR ', $featConditions);

    // Build keyword match count
    $keywordMatchCases = [];
    $keywordMatchParams = [];
    foreach ($keywords as $keyword) {
        $keywordMatchCases[] = "CASE WHEN LOWER(p.name) LIKE LOWER(?) THEN 1 ELSE 0 END";
        $keywordMatchParams[] = '%' . $keyword . '%';
    }
    $keywordMatchSQL = implode(' + ', $keywordMatchCases);

    $sql .= ", ($keywordMatchSQL) as keyword_match_count,
            CASE
                WHEN LOWER(p.name) = LOWER(?) THEN 1
                WHEN LOWER(p.name) LIKE LOWER(?) THEN 2
                WHEN LOWER(p.name) LIKE LOWER(?) THEN 3
                ELSE 4
            END as position_score,
            p.image, v.name as variant_name, v.variant_title, v.variant_image,
            v.price as variant_price, v.sale_price as variant_sale_price,
            v.slug as variant_slug, v.stock_quantity as variant_stock,
            (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
            FROM products p
            LEFT JOIN product_variants v ON p.id = v.product_id
            WHERE p.status = 'active' AND v.id IS NOT NULL
            AND (($nameSQL) OR ($descSQL) OR ($featSQL))";

    // Merge params theo đúng thứ tự: keyword match -> position score -> search conditions
    $params = array_merge($keywordMatchParams, [$searchQuery, $searchQuery . '%', '%' . $searchQuery . '%'], $searchParams);
} else {
    $sql .= ", p.image, v.name as variant_name, v.variant_title, v.variant_image,
            v.price as variant_price, v.sale_price as variant_sale_price,
            v.slug as variant_slug, v.stock_quantity as variant_stock,
            (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
            FROM products p
            LEFT JOIN product_variants v ON p.id = v.product_id
            WHERE p.status = 'active' AND v.id IS NOT NULL";
}

// Add category filter
if ($categoryId) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

// Add price range filter (lọc theo giá variant thực tế: sale_price nếu có, không thì price)
if ($minPrice !== null) {
    $sql .= " AND COALESCE(v.sale_price, v.price) >= ?";
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $sql .= " AND COALESCE(v.sale_price, v.price) <= ?";
    $params[] = $maxPrice;
}

// Add sorting
switch ($sortBy) {
    case 'price_asc':
        $sql .= " ORDER BY COALESCE(v.sale_price, v.price) ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY COALESCE(v.sale_price, v.price) DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY p.name DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.created_at DESC";
        break;
    default:
        // Nếu có search query, ưu tiên relevance trước
        if (!empty($searchQuery)) {
            $sql .= " ORDER BY keyword_match_count DESC, position_score ASC, p.sold_count DESC, p.created_at DESC";
        } else {
            $sql .= " ORDER BY p.featured DESC, p.created_at DESC";
        }
}

// Add pagination
$offset = ($page - 1) * $perPage;
$sql .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$products = db()->query($sql, $params)->fetchAll();

// Count total products for pagination (without LIMIT)
$countSql = "SELECT COUNT(DISTINCT p.id) as total FROM products p
             LEFT JOIN product_variants v ON p.id = v.product_id
             WHERE p.status = 'active' AND v.id IS NOT NULL";
$countParams = [];

// Add search condition for count query
if (!empty($searchQuery)) {
    $keywords = preg_split('/\s+/', trim($searchQuery));
    $keywords = array_filter($keywords);

    $nameConditions = [];
    $descConditions = [];
    $featConditions = [];

    foreach ($keywords as $keyword) {
        $nameConditions[] = "LOWER(p.name) LIKE LOWER(?)";
        $descConditions[] = "LOWER(p.description) LIKE LOWER(?)";
        $featConditions[] = "LOWER(p.features) LIKE LOWER(?)";

        $countParams[] = '%' . $keyword . '%';
        $countParams[] = '%' . $keyword . '%';
        $countParams[] = '%' . $keyword . '%';
    }

    $nameSQL = implode(' OR ', $nameConditions);
    $descSQL = implode(' OR ', $descConditions);
    $featSQL = implode(' OR ', $featConditions);

    $countSql .= " AND (($nameSQL) OR ($descSQL) OR ($featSQL))";
}

// Add category filter for count
if ($categoryId) {
    $countSql .= " AND p.category_id = ?";
    $countParams[] = $categoryId;
}

// Add price range filter for count (lọc theo giá variant thực tế)
if ($minPrice !== null) {
    $countSql .= " AND COALESCE(v.sale_price, v.price) >= ?";
    $countParams[] = $minPrice;
}
if ($maxPrice !== null) {
    $countSql .= " AND COALESCE(v.sale_price, v.price) <= ?";
    $countParams[] = $maxPrice;
}

$countResult = db()->query($countSql, $countParams)->fetch();
$totalProducts = $countResult['total'];
$totalPages = ceil($totalProducts / $perPage);
?>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

<div class="container my-4">
    <!-- Header Section -->
    <div class="filter-header">
        <div>
            <h1 class="filter-title"><?= !empty($searchQuery) ? 'Kết quả tìm kiếm: "' . e($searchQuery) . '"' : 'Sản phẩm' ?></h1>
            <p class="filter-subtitle">Khám phá bộ sưu tập sản phẩm kỹ thuật số cao cấp của Veyrix</p>
        </div>
        <div class="filter-count">
            Tìm thấy <span class="filter-count-number"><?= $totalProducts ?></span> sản phẩm
        </div>
    </div>

    <!-- Filter Section - Glass Card -->
    <div class="glass-card mb-4">
        <form method="GET" action="/products" id="filterForm">
            <?php if (!empty($searchQuery)): ?>
            <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
            <?php endif; ?>

            <div class="filter-grid">
                <!-- Danh mục -->
                <div class="filter-group filter-col-2">
                    <label class="filter-label">Danh mục</label>
                    <div class="filter-select-wrapper">
                        <span class="material-icons-round filter-icon">category</span>
                        <select name="category" class="filter-select">
                            <option value="all" <?= (empty($categorySlug) || $categorySlug === 'all') ? 'selected' : '' ?>>Tất cả</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="material-icons-round filter-arrow">expand_more</span>
                    </div>
                </div>

                <!-- Mức giá -->
                <div class="filter-group filter-col-4">
                    <label class="filter-label">Mức giá (VNĐ)</label>
                    <!-- Input fields -->
                    <div class="filter-price-wrapper">
                        <div class="filter-input-wrapper">
                            <input type="number"
                                   name="min_price"
                                   id="minPriceInput"
                                   class="filter-input"
                                   placeholder="Từ"
                                   min="<?= $priceRangeMin ?>"
                                   max="<?= $priceRangeMax ?>"
                                   value="<?= $minPrice ?? '' ?>">
                        </div>
                        <div class="filter-price-separator"></div>
                        <div class="filter-input-wrapper">
                            <input type="number"
                                   name="max_price"
                                   id="maxPriceInput"
                                   class="filter-input"
                                   placeholder="Đến"
                                   min="<?= $priceRangeMin ?>"
                                   max="<?= $priceRangeMax ?>"
                                   value="<?= $maxPrice ?? '' ?>">
                        </div>
                    </div>
                    <!-- Range Slider (below inputs) -->
                    <div class="price-slider-container">
                        <div class="price-slider-track"></div>
                        <div class="price-slider-range" id="priceRange"></div>
                        <input type="range"
                               class="price-slider-input"
                               id="priceSliderMin"
                               min="<?= $priceRangeMin ?>"
                               max="<?= $priceRangeMax ?>"
                               value="<?= $minPrice ?? $priceRangeMin ?>"
                               step="10000">
                        <input type="range"
                               class="price-slider-input"
                               id="priceSliderMax"
                               min="<?= $priceRangeMin ?>"
                               max="<?= $priceRangeMax ?>"
                               value="<?= $maxPrice ?? $priceRangeMax ?>"
                               step="10000">
                    </div>
                </div>

                <!-- Sắp xếp -->
                <div class="filter-group filter-col-2">
                    <label class="filter-label">Sắp xếp</label>
                    <div class="filter-select-wrapper">
                        <span class="material-icons-round filter-icon">sort</span>
                        <select name="sort" class="filter-select">
                            <option value="default" <?= $sortBy === 'default' ? 'selected' : '' ?>>Mặc định</option>
                            <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Giá thấp đến cao</option>
                            <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Giá cao đến thấp</option>
                            <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Tên A-Z</option>
                            <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>Tên Z-A</option>
                            <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        </select>
                        <span class="material-icons-round filter-arrow">expand_more</span>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="filter-group filter-col-2 filter-buttons">
                    <button type="submit" class="filter-btn-primary">
                        <span class="material-icons-round">filter_alt</span>
                        <span>Lọc</span>
                    </button>
                    <a href="/products<?= !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : '' ?>" class="filter-btn-reset" title="Khôi phục bộ lọc">
                        <span class="material-icons-round">refresh</span>
                    </a>
                </div>
            </div>

            <!-- Mobile Filter -->
            <div class="filter-mobile">
                <?php if (!empty($searchQuery)): ?>
                <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                <?php endif; ?>

                <!-- Danh mục -->
                <div class="filter-mobile-group">
                    <label class="filter-label">Danh mục</label>
                    <div class="filter-select-wrapper">
                        <span class="material-icons-round filter-icon">category</span>
                        <select name="category" class="filter-select">
                            <option value="all" <?= (empty($categorySlug) || $categorySlug === 'all') ? 'selected' : '' ?>>Tất cả danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="material-icons-round filter-arrow">expand_more</span>
                    </div>
                </div>

                <!-- Mức giá -->
                <div class="filter-mobile-group">
                    <label class="filter-label">Mức giá (VNĐ)</label>
                    <!-- Input fields -->
                    <div class="filter-price-wrapper">
                        <div class="filter-input-wrapper">
                            <input type="number"
                                   name="min_price"
                                   id="minPriceInputMobile"
                                   class="filter-input filter-input-mobile"
                                   placeholder="Từ"
                                   min="<?= $priceRangeMin ?>"
                                   max="<?= $priceRangeMax ?>"
                                   value="<?= $minPrice ?? '' ?>">
                        </div>
                        <div class="filter-price-separator"></div>
                        <div class="filter-input-wrapper">
                            <input type="number"
                                   name="max_price"
                                   id="maxPriceInputMobile"
                                   class="filter-input filter-input-mobile"
                                   placeholder="Đến"
                                   min="<?= $priceRangeMin ?>"
                                   max="<?= $priceRangeMax ?>"
                                   value="<?= $maxPrice ?? '' ?>">
                        </div>
                    </div>
                    <!-- Range Slider Mobile (below inputs) -->
                    <div class="price-slider-container">
                        <div class="price-slider-track"></div>
                        <div class="price-slider-range" id="priceRangeMobile"></div>
                        <input type="range"
                               class="price-slider-input price-slider-mobile"
                               id="priceSliderMinMobile"
                               min="<?= $priceRangeMin ?>"
                               max="<?= $priceRangeMax ?>"
                               value="<?= $minPrice ?? $priceRangeMin ?>"
                               step="10000">
                        <input type="range"
                               class="price-slider-input price-slider-mobile"
                               id="priceSliderMaxMobile"
                               min="<?= $priceRangeMin ?>"
                               max="<?= $priceRangeMax ?>"
                               value="<?= $maxPrice ?? $priceRangeMax ?>"
                               step="10000">
                    </div>
                </div>

                <!-- Sắp xếp -->
                <div class="filter-mobile-group">
                    <label class="filter-label">Sắp xếp</label>
                    <div class="filter-select-wrapper">
                        <span class="material-icons-round filter-icon">sort</span>
                        <select name="sort" class="filter-select">
                            <option value="default" <?= $sortBy === 'default' ? 'selected' : '' ?>>Mặc định</option>
                            <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Giá thấp đến cao</option>
                            <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Giá cao đến thấp</option>
                            <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Tên A-Z</option>
                            <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>Tên Z-A</option>
                            <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        </select>
                        <span class="material-icons-round filter-arrow">expand_more</span>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="filter-mobile-buttons">
                    <button type="submit" class="filter-btn-primary">
                        <span class="material-icons-round">filter_alt</span>
                        <span>Áp dụng lọc</span>
                    </button>
                    <a href="/products<?= !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : '' ?>" class="filter-btn-reset-mobile">
                        <span class="material-icons-round">refresh</span>
                        <span>Đặt lại</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-4x text-muted mb-3"></i>
            <h4>Không tìm thấy sản phẩm nào</h4>
            <p class="text-muted">Thử điều chỉnh bộ lọc để tìm sản phẩm</p>
        </div>
    <?php else: ?>
        <div class="row g-4" id="productsGrid">
            <?php foreach ($products as $product): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 col-6">
                    <div class="card h-100 shadow-sm product-card">
                        <?php
                        // Ưu tiên hiển thị thông tin gói chính nếu có
                        // Title: variant_title > product_name + variant_name > product_name
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
            <?php endforeach; ?>
        </div>

        <!-- Load More Button -->
        <div class="text-center mt-4" id="loadMoreContainer" style="<?= $page >= $totalPages ? 'display: none;' : '' ?>">
            <button class="pill-button pill-button-gray" id="loadMoreBtn" onclick="loadMoreProducts()">
                <i class="fas fa-chevron-down"></i>Xem thêm
            </button>
        </div>
    <?php endif; ?>
</div>

<style>
/* ===== Glass Card Filter Styles ===== */
.filter-header {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}

@media (min-width: 768px) {
    .filter-header {
        flex-direction: row;
        align-items: flex-end;
        justify-content: space-between;
    }
}

.filter-title {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.025em;
    margin-bottom: 4px;
    color: #1e293b;
}

.filter-subtitle {
    color: #64748b;
    margin: 0;
    font-size: 14px;
}

.filter-count {
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
}

.filter-count-number {
    color: #3B82F6;
    font-weight: 700;
}

/* Glass Card */
.glass-card {
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 1.5rem;
    padding: 16px 24px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    overflow: visible;
    transition: padding 0.2s;
}

.glass-card:has(.price-slider-container.active) {
    padding-bottom: 32px;
}

/* Filter Grid - 10 columns: 2 + 4 + 2 + 2 */
.filter-grid {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 16px;
    align-items: end;
}

.filter-col-2 {
    grid-column: span 2;
}

.filter-col-4 {
    grid-column: span 4;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-label {
    display: block;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-left: 12px;
}

/* Fix row g-4 margin */
.row.g-4 {
    margin-top: 0 !important;
}

/* Select Wrapper */
.filter-select-wrapper {
    position: relative;
}

.filter-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 18px;
    transition: color 0.2s;
    pointer-events: none;
}

.filter-select-wrapper:focus-within .filter-icon {
    color: #3B82F6;
}

.filter-arrow {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 18px;
    pointer-events: none;
}

.filter-select {
    width: 100%;
    background: rgba(255, 255, 255, 0.7);
    border: none;
    box-shadow: inset 0 0 0 1px #e2e8f0;
    border-radius: 9999px;
    padding: 10px 36px 10px 36px;
    font-size: 13px;
    color: #334155;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    transition: all 0.2s;
}

.filter-select:focus {
    outline: none;
    box-shadow: inset 0 0 0 2px #3B82F6;
}

/* Input Wrapper */
.filter-input-wrapper {
    position: relative;
    flex: 1;
}

.filter-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.7);
    border: none;
    box-shadow: inset 0 0 0 1px #e2e8f0;
    border-radius: 9999px;
    padding: 10px 16px;
    font-size: 13px;
    color: #334155;
    transition: all 0.2s;
}

.filter-input:focus {
    outline: none;
    box-shadow: inset 0 0 0 2px #3B82F6;
}

.filter-input::placeholder {
    color: #94a3b8;
}

/* Price Wrapper */
.filter-price-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-price-separator {
    width: 12px;
    height: 1px;
    background: #cbd5e1;
    flex-shrink: 0;
}

/* Price Range Slider */
.price-slider-container {
    position: absolute;
    left: 0;
    right: 0;
    top: 100%;
    height: 20px;
    margin: 8px 8px 0 8px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s, visibility 0.2s;
    z-index: 10;
}

.price-slider-container.active {
    opacity: 1;
    visibility: visible;
}

/* Make filter-group relative for absolute positioning */
.filter-group.filter-col-4,
.filter-mobile-group {
    position: relative;
}

.price-slider-track {
    position: absolute;
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
}

.price-slider-range {
    position: absolute;
    height: 6px;
    background: linear-gradient(90deg, #3B82F6, #60a5fa);
    border-radius: 3px;
}

.price-slider-input {
    position: absolute;
    width: 100%;
    height: 6px;
    background: none;
    pointer-events: none;
    -webkit-appearance: none;
    appearance: none;
    margin: 0;
    top: 0;
}

.price-slider-input::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    background: #3B82F6;
    border: 3px solid white;
    border-radius: 50%;
    cursor: pointer;
    pointer-events: auto;
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.4);
    transition: transform 0.15s, box-shadow 0.15s;
}

.price-slider-input::-webkit-slider-thumb:hover {
    transform: scale(1.15);
    box-shadow: 0 3px 10px rgba(59, 130, 246, 0.5);
}

.price-slider-input::-webkit-slider-thumb:active {
    transform: scale(1.1);
}

.price-slider-input::-moz-range-thumb {
    width: 20px;
    height: 20px;
    background: #3B82F6;
    border: 3px solid white;
    border-radius: 50%;
    cursor: pointer;
    pointer-events: auto;
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.4);
    transition: transform 0.15s, box-shadow 0.15s;
}

.price-slider-input::-moz-range-thumb:hover {
    transform: scale(1.15);
}

.price-slider-input::-moz-range-track {
    background: transparent;
    border: none;
}

/* Buttons */
.filter-buttons {
    display: flex;
    flex-direction: row;
    align-items: flex-end;
    gap: 6px;
}

.filter-btn-primary {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: #3B82F6;
    color: white;
    font-weight: 600;
    padding: 10px 18px;
    border-radius: 9999px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 0 12px rgba(59, 130, 246, 0.4);
    font-size: 13px;
}

.filter-btn-primary:hover {
    background: #2563eb;
    transform: scale(1.02);
}

.filter-btn-primary:active {
    transform: scale(0.95);
}

.filter-btn-primary .material-icons-round {
    font-size: 16px;
}

.filter-btn-reset {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 9999px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.filter-btn-reset:hover {
    background: #e2e8f0;
    color: #334155;
}

.filter-btn-reset:active .material-icons-round {
    transform: rotate(180deg);
}

.filter-btn-reset .material-icons-round {
    font-size: 18px;
    transition: transform 0.3s;
}

/* Mobile Filter */
.filter-grid {
    display: grid;
}

.filter-mobile {
    display: none;
}

@media (max-width: 991px) {
    .glass-card {
        padding: 16px;
        border-radius: 1rem;
    }

    .filter-grid {
        display: none;
    }

    .filter-mobile {
        display: block;
    }

    .filter-title {
        font-size: 1.5rem;
    }
}

.filter-mobile-group {
    margin-bottom: 14px;
}

.filter-mobile-buttons {
    display: flex;
    gap: 8px;
    margin-top: 16px;
}

.filter-mobile-buttons .filter-btn-primary {
    padding: 10px 16px;
    font-size: 12px;
}

.filter-mobile-buttons .filter-btn-primary .material-icons-round {
    font-size: 14px;
}

.filter-btn-reset-mobile {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    padding: 10px 16px;
    font-size: 12px;
    border-radius: 9999px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.filter-btn-reset-mobile:hover {
    background: #e2e8f0;
    color: #334155;
    text-decoration: none;
}

.filter-btn-reset-mobile .material-icons-round {
    font-size: 14px;
}

/* ===== Product Card Styles ===== */
.product-card {
    --hover-bg-color: #f8f9fa;
    --hover-outline-color: rgba(0, 0, 0, 0.06);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    transition-property: transform, background-color, box-shadow, outline, outline-offset;
    border: 1px solid rgba(0, 0, 0, 0.06) !important;
    border-radius: 18px !important;
    overflow: hidden !important;
    outline: 1px solid transparent;
    outline-offset: 0;
    background-color: #fff;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06) !important;
    will-change: transform;
}

.product-card:hover {
    transform: translateY(-4px) !important;
    background-color: var(--hover-bg-color);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.04) !important;
    outline: 1px solid var(--hover-outline-color);
    outline-offset: -1px;
}

.product-card .card-img-top {
    border-radius: 18px 18px 0 0 !important;
}

/* Hide number input spinners */
.filter-input[type="number"]::-webkit-outer-spin-button,
.filter-input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.filter-input[type="number"] {
    -moz-appearance: textfield;
}
</style>

<script>
// Clean up filter form - remove empty and default values before submit
document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const params = new URLSearchParams();

    // Get all elements with each name (desktop + mobile)
    const qElements = form.querySelectorAll('[name="q"]');
    const categoryElements = form.querySelectorAll('[name="category"]');
    const minPriceElements = form.querySelectorAll('[name="min_price"]');
    const maxPriceElements = form.querySelectorAll('[name="max_price"]');
    const sortElements = form.querySelectorAll('[name="sort"]');

    // Get first non-empty value from all elements
    let searchQuery = '';
    let category = '';
    let minPrice = '';
    let maxPrice = '';
    let sort = '';

    qElements.forEach(el => {
        if (el.value) {
            searchQuery = el.value;
        }
    });

    categoryElements.forEach(el => {
        if (el.offsetParent !== null && el.value) { // Check if visible and has value
            category = el.value;
        }
    });

    minPriceElements.forEach(el => {
        if (el.offsetParent !== null && el.value) {
            minPrice = el.value;
        }
    });

    maxPriceElements.forEach(el => {
        if (el.offsetParent !== null && el.value) {
            maxPrice = el.value;
        }
    });

    sortElements.forEach(el => {
        if (el.offsetParent !== null && el.value) {
            sort = el.value;
        }
    });

    // Only add non-empty and non-default values
    if (searchQuery && searchQuery !== '') {
        params.append('q', searchQuery);
    }

    if (category && category !== '' && category !== 'all') {
        params.append('category', category);
    }

    if (minPrice && minPrice !== '') {
        params.append('min_price', minPrice);
    }

    if (maxPrice && maxPrice !== '') {
        params.append('max_price', maxPrice);
    }

    if (sort && sort !== 'default') {
        params.append('sort', sort);
    }

    // Build clean URL
    const queryString = params.toString();
    const url = queryString ? `/products?${queryString}` : '/products';

    // Navigate to clean URL
    window.location.href = url;
});

// ===== Realtime Filter =====
// Debounce function for price inputs
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Auto submit form function
function autoSubmitFilter() {
    document.getElementById('filterForm').dispatchEvent(new Event('submit'));
}

// Debounced version for price inputs (wait 500ms after user stops typing)
const debouncedSubmit = debounce(autoSubmitFilter, 500);

// Add change event to all select elements (category, sort)
document.querySelectorAll('#filterForm select').forEach(select => {
    select.addEventListener('change', autoSubmitFilter);
});

// Add input event to price inputs with debounce
document.querySelectorAll('#filterForm input[type="number"]').forEach(input => {
    input.addEventListener('input', debouncedSubmit);
    // Also submit on Enter key
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            autoSubmitFilter();
        }
    });
});

// ===== Price Range Slider =====
const MIN_PRICE_GAP = 20000; // Minimum gap between min and max price

function initPriceSlider(config) {
    const {
        sliderMin,
        sliderMax,
        inputMin,
        inputMax,
        rangeEl,
        minVal,
        maxVal
    } = config;

    const sliderMinEl = document.getElementById(sliderMin);
    const sliderMaxEl = document.getElementById(sliderMax);
    const inputMinEl = document.getElementById(inputMin);
    const inputMaxEl = document.getElementById(inputMax);
    const rangeElement = document.getElementById(rangeEl);

    if (!sliderMinEl || !sliderMaxEl) return;

    const min = parseInt(sliderMinEl.min);
    const max = parseInt(sliderMinEl.max);

    // Get slider container
    const sliderContainer = sliderMinEl.closest('.price-slider-container');
    const priceWrapper = inputMinEl.closest('.filter-price-wrapper');

    // Show slider when clicking on price inputs
    if (priceWrapper && sliderContainer) {
        priceWrapper.addEventListener('click', function(e) {
            sliderContainer.classList.add('active');
        });

        // Show slider when focusing on price inputs
        inputMinEl.addEventListener('focus', function() {
            sliderContainer.classList.add('active');
        });
        inputMaxEl.addEventListener('focus', function() {
            sliderContainer.classList.add('active');
        });

        // Hide slider when clicking outside price area
        document.addEventListener('click', function(e) {
            const filterGroup = sliderContainer.closest('.filter-group, .filter-mobile-group');
            if (filterGroup && !filterGroup.contains(e.target)) {
                sliderContainer.classList.remove('active');
            }
        });
    }

    // Update range bar position
    function updateRange() {
        const minValue = parseInt(sliderMinEl.value);
        const maxValue = parseInt(sliderMaxEl.value);
        const percentMin = ((minValue - min) / (max - min)) * 100;
        const percentMax = ((maxValue - min) / (max - min)) * 100;
        rangeElement.style.left = percentMin + '%';
        rangeElement.style.width = (percentMax - percentMin) + '%';
    }

    // Format number with thousand separator
    function formatPrice(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Slider min change - with minimum gap
    sliderMinEl.addEventListener('input', function() {
        let minValue = parseInt(this.value);
        let maxValue = parseInt(sliderMaxEl.value);

        // Enforce minimum gap
        if (minValue > maxValue - MIN_PRICE_GAP) {
            minValue = maxValue - MIN_PRICE_GAP;
            if (minValue < min) {
                minValue = min;
                maxValue = min + MIN_PRICE_GAP;
                sliderMaxEl.value = maxValue;
                inputMaxEl.value = maxValue;
            }
            this.value = minValue;
        }

        inputMinEl.value = minValue;
        updateRange();
    });

    // Slider max change - with minimum gap
    sliderMaxEl.addEventListener('input', function() {
        let maxValue = parseInt(this.value);
        let minValue = parseInt(sliderMinEl.value);

        // Enforce minimum gap
        if (maxValue < minValue + MIN_PRICE_GAP) {
            maxValue = minValue + MIN_PRICE_GAP;
            if (maxValue > max) {
                maxValue = max;
                minValue = max - MIN_PRICE_GAP;
                sliderMinEl.value = minValue;
                inputMinEl.value = minValue;
            }
            this.value = maxValue;
        }

        inputMaxEl.value = maxValue;
        updateRange();
    });

    // Input min change - with minimum gap
    inputMinEl.addEventListener('input', function() {
        let value = parseInt(this.value) || min;
        value = Math.max(min, Math.min(value, max - MIN_PRICE_GAP));
        sliderMinEl.value = value;
        updateRange();
    });

    // Input max change - with minimum gap
    inputMaxEl.addEventListener('input', function() {
        let value = parseInt(this.value) || max;
        value = Math.max(min + MIN_PRICE_GAP, Math.min(value, max));
        sliderMaxEl.value = value;
        updateRange();
    });

    // Slider release - trigger filter
    sliderMinEl.addEventListener('change', debouncedSubmit);
    sliderMaxEl.addEventListener('change', debouncedSubmit);

    // Initial update
    updateRange();
}

// Initialize Desktop Price Slider
initPriceSlider({
    sliderMin: 'priceSliderMin',
    sliderMax: 'priceSliderMax',
    inputMin: 'minPriceInput',
    inputMax: 'maxPriceInput',
    rangeEl: 'priceRange',
    minVal: <?= $minPrice ?? $priceRangeMin ?>,
    maxVal: <?= $maxPrice ?? $priceRangeMax ?>
});

// Initialize Mobile Price Slider
initPriceSlider({
    sliderMin: 'priceSliderMinMobile',
    sliderMax: 'priceSliderMaxMobile',
    inputMin: 'minPriceInputMobile',
    inputMax: 'maxPriceInputMobile',
    rangeEl: 'priceRangeMobile',
    minVal: <?= $minPrice ?? $priceRangeMin ?>,
    maxVal: <?= $maxPrice ?? $priceRangeMax ?>
});

// Load More Products Function
let currentPage = <?= $page ?>;
let totalPages = <?= $totalPages ?>;
let isLoading = false;

function loadMoreProducts() {
    if (isLoading) return;

    isLoading = true;
    const btn = document.getElementById('loadMoreBtn');
    const originalContent = btn.innerHTML;

    // Show loading state
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải...';
    btn.disabled = true;

    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const params = new URLSearchParams();

    // Copy existing filters
    if (urlParams.has('category')) params.append('category', urlParams.get('category'));
    if (urlParams.has('min_price')) params.append('min_price', urlParams.get('min_price'));
    if (urlParams.has('max_price')) params.append('max_price', urlParams.get('max_price'));
    if (urlParams.has('sort')) params.append('sort', urlParams.get('sort'));

    // Add next page
    params.append('page', currentPage + 1);

    // Fetch more products
    fetch('/api/load-more-products.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success && data.html) {
                // Append new products to grid
                const grid = document.getElementById('productsGrid');
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.html;

                // Append each product card
                while (tempDiv.firstChild) {
                    grid.appendChild(tempDiv.firstChild);
                }

                // Update current page
                currentPage = data.currentPage;
                totalPages = data.totalPages;

                // Apply color effects to new cards
                applyColorEffects();

                // Check if there are more pages
                if (!data.hasMore) {
                    document.getElementById('loadMoreContainer').style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error loading more products:', error);
            alert('Có lỗi xảy ra khi tải thêm sản phẩm. Vui lòng thử lại.');
        })
        .finally(() => {
            // Restore button state
            btn.innerHTML = originalContent;
            btn.disabled = false;
            isLoading = false;
        });
}

// Extract dominant color from image
function getAverageColor(img) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = img.naturalWidth || img.width;
    canvas.height = img.naturalHeight || img.height;

    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    let r = 0, g = 0, b = 0;

    for (let i = 0; i < data.length; i += 4) {
        r += data[i];
        g += data[i + 1];
        b += data[i + 2];
    }

    const pixelCount = data.length / 4;
    r = Math.floor(r / pixelCount);
    g = Math.floor(g / pixelCount);
    b = Math.floor(b / pixelCount);

    return { r, g, b };
}

// Apply hover color effect to product cards
function applyColorEffects() {
    const productCards = document.querySelectorAll('.product-card:not(.has-color)');

    productCards.forEach(card => {
        const img = card.querySelector('img.card-img-top');
        if (!img) return;

        const applyColor = () => {
            try {
                const color = getAverageColor(img);
                const rgba = `rgba(${color.r}, ${color.g}, ${color.b}, 0.08)`;
                const rgbaOutline = `rgba(${color.r}, ${color.g}, ${color.b}, 0.12)`;

                card.style.setProperty('--hover-bg-color', rgba);
                card.style.setProperty('--hover-outline-color', rgbaOutline);
                card.classList.add('has-color');
            } catch (e) {
                // Color extraction failed, using default hover style
            }
        };

        if (img.complete) {
            applyColor();
        } else {
            img.addEventListener('load', applyColor);
        }
    });
}

// Apply color effects on page load
document.addEventListener('DOMContentLoaded', function() {
    applyColorEffects();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
