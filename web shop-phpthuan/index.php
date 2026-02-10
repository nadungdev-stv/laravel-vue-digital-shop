<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = 'Mua tài khoản Premium uy tín - YouTube, Netflix, Spotify, VPN, Canva';
$pageDescription = 'Mua tài khoản Premium uy tín hàng đầu Việt Nam: YouTube Premium, Netflix, Spotify, VPN, Canva… Giá tốt nhất – kích hoạt nhanh – bảo hành trọn đời – hỗ trợ 24/7';

// Generate CSRF token for security
if (!isset($csrfToken)) {
    require_once __DIR__ . '/includes/helpers.php';
    initSession();
    $csrfToken = generateCSRFToken();
}

require_once __DIR__ . '/includes/header.php';

// Lấy danh mục
$categories = db()->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order")->fetchAll();

// Lấy danh mục với hình ảnh đại diện (sản phẩm đầu tiên của mỗi danh mục)
$categoriesWithImages = db()->query(
    "SELECT c.*,
            (SELECT p.image
             FROM products p
             WHERE p.category_id = c.id AND p.status = 'active'
             ORDER BY p.featured DESC, p.created_at DESC
             LIMIT 1) as representative_image,
            (SELECT COUNT(*)
             FROM products p
             WHERE p.category_id = c.id AND p.status = 'active') as product_count
     FROM categories c
     WHERE c.status = 'active'
     ORDER BY c.sort_order"
)->fetchAll();

// Lấy banners
$banners = db()->query("SELECT * FROM banners WHERE status = 'active' ORDER BY sort_order ASC LIMIT 10")->fetchAll();

// Lấy sản phẩm nổi bật - lấy 24 sản phẩm để có thể "Xem thêm"
$featuredProducts = db()->query(
    "SELECT p.*, c.name as category_name,
            v.name as variant_name, v.variant_title, v.variant_image,
            v.price as variant_price, v.sale_price as variant_sale_price,
            v.slug as variant_slug, v.stock_quantity as variant_stock,
            (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN product_variants v ON p.id = v.product_id AND v.is_main = 1
     WHERE p.status = 'active'
     ORDER BY p.featured DESC, p.created_at DESC
     LIMIT 24"
)->fetchAll();

// Lấy sản phẩm bán chạy và random thứ tự
try {
    $tempBestSelling = db()->query(
        "SELECT p.*, c.name as category_name,
                v.name as variant_name, v.variant_title, v.variant_image,
                v.price as variant_price, v.sale_price as variant_sale_price,
                v.slug as variant_slug, v.stock_quantity as variant_stock,
                (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN product_variants v ON p.id = v.product_id AND v.is_main = 1
         WHERE p.status = 'active'
         ORDER BY COALESCE(p.sold_count, 0) DESC
         LIMIT 20"
    )->fetchAll();

    // Random thứ tự để không bị trùng lặp mỗi lần load
    shuffle($tempBestSelling);
    $bestSellingProducts = array_slice($tempBestSelling, 0, 12);
} catch (Exception $e) {
    $bestSellingProducts = [];
    error_log("Best selling products error: " . $e->getMessage());
}

// Lấy sản phẩm mới
$newProducts = db()->query(
    "SELECT p.*, c.name as category_name,
            v.name as variant_name, v.variant_title, v.variant_image,
            v.price as variant_price, v.sale_price as variant_sale_price,
            v.slug as variant_slug, v.stock_quantity as variant_stock,
            (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN product_variants v ON p.id = v.product_id AND v.is_main = 1
     WHERE p.status = 'active'
     ORDER BY p.created_at DESC
     LIMIT 8"
)->fetchAll();

// Lấy tất cả sản phẩm cho tab navigation (limit 50 để performance)
$allTabProducts = db()->query(
    "SELECT p.id, p.name, p.slug, p.image, p.price, p.sale_price,
            p.featured, p.category_id, c.name as category_name,
            v.name as variant_name, v.variant_title, v.variant_image,
            v.price as variant_price, v.sale_price as variant_sale_price,
            v.slug as variant_slug, v.stock_quantity as variant_stock,
            (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN product_variants v ON v.id = (
         SELECT id FROM product_variants
         WHERE product_id = p.id
         ORDER BY is_main DESC, id ASC
         LIMIT 1
     )
     WHERE p.status = 'active'
     ORDER BY p.featured DESC, p.created_at DESC
     LIMIT 50"
)->fetchAll();

// Lấy promo banners từ database
$promoResult = null;
try {
    $promoResult = db()->query("SELECT setting_value FROM settings WHERE setting_key = 'promo_banners'")->fetch();
} catch (Exception $e) {
    // Bảng chưa tồn tại, dùng giá trị mặc định
}
$promoBanners = $promoResult ? json_decode($promoResult['setting_value'], true) : [
    ['image' => '/public/images/banners/vpn-banner.svg', 'link' => '/products?category=vpn-bao-mat-mang'],
    ['image' => '/public/images/banners/esim-banner.svg', 'link' => '/products?category=the-gioi-ai']
];
?>

<!-- Main Section with Sidebar Categories + Banners + Promo Banners -->
<section class="py-4">
    <div class="container">
        <div class="row g-3">
            <!-- Left Sidebar - Categories -->
            <div class="col-lg-2 col-md-3">
                <div class="categories-sidebar">
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($categories, 0, 6) as $category):
                            // Kiểm tra xem icon là FontAwesome class hay URL
                            $isFontAwesome = $category['icon'] && (
                                strpos($category['icon'], 'fa-') === 0 ||
                                strpos($category['icon'], 'fas ') === 0 ||
                                strpos($category['icon'], 'far ') === 0 ||
                                strpos($category['icon'], 'fab ') === 0
                            );
                            ?>
                            <a href="/products?category=<?= e($category['slug']) ?>"
                                class="list-group-item list-group-item-action d-flex align-items-center category-menu-item">
                                <?php if ($isFontAwesome): ?>
                                    <i class="<?= e($category['icon']) ?> category-icon-fallback me-3"></i>
                                <?php elseif ($category['icon']): ?>
                                    <img src="<?= e($category['icon']) ?>" class="category-icon me-3"
                                        alt="<?= e($category['name']) ?>" loading="lazy"
                                        onerror="this.outerHTML='<i class=\'fas fa-folder category-icon-fallback me-3\'></i>'">
                                <?php else: ?>
                                    <i class="fas fa-folder category-icon-fallback me-3"></i>
                                <?php endif; ?>
                                <span><?= e($category['name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Center Column - Banner Carousel -->
            <div class="col-lg-7 col-md-6">
                <?php if (!empty($banners)): ?>
                    <div id="bannerCarousel" class="carousel slide carousel-main" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($banners as $index => $banner): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <?php if ($banner['link']): ?>
                                        <a href="<?= e($banner['link']) ?>">
                                            <img src="<?= e($banner['image']) ?>" class="d-block w-100"
                                                alt="<?= e($banner['title']) ?>" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                                                <?= $index === 0 ? 'fetchpriority="high"' : '' ?>>
                                        </a>
                                    <?php else: ?>
                                        <img src="<?= e($banner['image']) ?>" class="d-block w-100" alt="<?= e($banner['title']) ?>"
                                            loading="<?= $index === 0 ? 'eager' : 'lazy' ?>" <?= $index === 0 ? 'fetchpriority="high"' : '' ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Navigation Buttons -->
                        <?php if (count($banners) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>

                            <!-- Indicators -->
                            <div class="carousel-indicators" style="padding-bottom: 5px;">
                                <?php foreach ($banners as $index => $banner): ?>
                                    <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="<?= $index ?>"
                                        <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?>
                                        aria-label="Slide <?= $index + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - 2 Promo Banners -->
            <div class="col-12 col-md-3 col-lg-3">
                <div class="promo-banners">
                    <?php foreach ($promoBanners as $index => $promo): ?>
                        <a href="<?= e($promo['link']) ?>"
                            class="promo-banner-item <?= $index === 0 ? 'mb-3' : '' ?> d-block">
                            <img src="<?= getResizedImage($promo['image'], 600) ?>" srcset="<?= getResizedImage($promo['image'], 400, 80) ?> 400w,
                                         <?= getResizedImage($promo['image'], 600, 80) ?> 600w,
                                         <?= getResizedImage($promo['image'], 1070, 75) ?> 1070w"
                                sizes="(max-width: 576px) 100vw, (max-width: 768px) 100vw, 25vw" class="w-100"
                                alt="Promo <?= $index + 1 ?>" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sản phẩm nổi bật -->
<?php if (!empty($featuredProducts)): ?>
    <section class="py-5 bg-light lazy-section" data-section="featured-products">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="featured-title d-inline-block"><i class="fas fa-star"></i> Sản phẩm nổi bật</h2>
            </div>
            <div class="row g-4" id="featuredProductsGrid">
                <?php foreach ($featuredProducts as $index => $product): ?>
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
                    // Ẩn sản phẩm từ thứ 13 trở đi (index >= 12) - Hiển thị 3 hàng ban đầu
                    $hiddenClass = $index >= 12 ? 'featured-hidden' : '';
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-6 featured-product-item <?= $hiddenClass ?>">
                        <div class="card h-100 shadow-sm product-card">
                            <a href="<?= $productUrl ?>" class="text-decoration-none">
                                <div class="position-relative">
                                    <img src="<?= getResizedImage($displayImage, 300) ?>"
                                        srcset="<?= getResponsiveSrcset($displayImage) ?>"
                                        sizes="(max-width: 576px) 50vw, (max-width: 768px) 33vw, (max-width: 992px) 25vw, 300px"
                                        class="card-img-top" alt="<?= e($displayTitle) ?>" loading="lazy">

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
                                    <h6 class="card-title text-dark mb-3 fw-semibold"
                                        style="min-height: 40px; line-height: 1.4;">
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

            <?php if (count($featuredProducts) > 12): ?>
                <div class="text-center mt-4">
                    <button class="pill-button pill-button-gray" id="viewMoreBtn" onclick="toggleFeaturedProducts()">
                        <i class="fas fa-chevron-down"></i>Xem thêm
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Danh mục sản phẩm -->
<?php if (!empty($categoriesWithImages)): ?>
    <section class="py-5 bg-white lazy-section" data-section="categories">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title mb-0">
                    <i class="fas fa-th-large text-primary me-2"></i>Danh mục sản phẩm
                </h2>
                <div class="tabnav-navigation">
                    <button class="tabnav-nav-btn tabnav-nav-prev" onclick="scrollCategoryShelf(-1)" aria-label="Previous">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="tabnav-nav-btn tabnav-nav-next" onclick="scrollCategoryShelf(1)" aria-label="Next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="category-shelf-wrapper">
                <div class="category-shelf" id="categoryShelf">
                    <?php foreach ($categoriesWithImages as $category): ?>
                        <?php if ($category['representative_image']): ?>
                            <a href="/products?category=<?= e($category['slug']) ?>" class="category-shelf-card">
                                <div class="category-card-image-wrapper">
                                    <img src="<?= e($category['representative_image']) ?>" alt="<?= e($category['name']) ?>"
                                        class="category-card-image" loading="lazy">
                                    <div class="category-card-overlay">
                                        <h3 class="category-card-title"><?= e($category['name']) ?></h3>
                                        <?php if ($category['product_count'] > 0): ?>
                                            <p class="category-card-count"><?= number_format($category['product_count']) ?> sản phẩm</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Sản phẩm bán chạy -->
<?php if (!empty($bestSellingProducts)): ?>
    <section class="py-5 bg-light lazy-section" data-section="best-sellers">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title mb-0">
                    <i class="fas fa-fire text-danger me-2"></i>Sản phẩm bán chạy
                </h2>
                <div class="tabnav-navigation">
                    <button class="tabnav-nav-btn tabnav-nav-prev" onclick="scrollBestSellingShelf(-1)"
                        aria-label="Previous">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="tabnav-nav-btn tabnav-nav-next" onclick="scrollBestSellingShelf(1)" aria-label="Next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="bestseller-shelf-wrapper">
                <div class="bestseller-shelf" id="bestsellerShelf">
                    <?php foreach ($bestSellingProducts as $index => $product): ?>
                        <div class="bestseller-shelf-item">
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

                            // URL ưu tiên variant slug nếu có
                            if (!empty($product['variant_slug'])) {
                                $productUrl = '/' . $product['variant_slug'];
                            } elseif (!empty($product['slug'])) {
                                $productUrl = '/' . $product['slug'];
                            } else {
                                $productUrl = '/product?id=' . $product['id'];
                            }

                            $currentPrice = $displaySalePrice ?? $displayPrice;
                            $hasDiscount = !empty($displaySalePrice) && $displaySalePrice < $displayPrice;
                            ?>
                            <a href="<?= $productUrl ?>" class="text-decoration-none product-card bestseller-card">
                                <div class="card h-100 border-0">
                                    <div class="position-relative">
                                        <img src="<?= getResizedImage($displayImage, 300) ?>" srcset="<?= getResizedImage($displayImage, 300, 80) ?> 300w,
                                         <?= getResizedImage($displayImage, 600, 80) ?> 600w,
                                         <?= getResizedImage($displayImage, 1070, 75) ?> 1070w"
                                            sizes="(max-width: 768px) 200px, 260px" class="card-img-top"
                                            alt="<?= e($displayTitle) ?>" loading="lazy">
                                    </div>

                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-2 text-dark"
                                            style="font-size: 14px; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                            <?= e($displayTitle) ?>
                                        </h6>

                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <?php if ($hasDiscount): ?>
                                                <span class="text-danger fw-bold"><?= number_format($displaySalePrice) ?>đ</span>
                                                <small
                                                    class="text-muted text-decoration-line-through"><?= number_format($displayPrice) ?>đ</small>
                                            <?php else: ?>
                                                <span class="text-primary fw-bold"><?= number_format($displayPrice) ?>đ</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($product['category_name']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-tag me-1"></i><?= e($product['category_name']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Featured Products by Category with TabNav -->
<section class="py-5 lazy-section" data-section="featured-categories"
    style="background: linear-gradient(135deg, #fafbff 0%, #f5f7fa 100%);">
    <div class="container">
        <!-- Title and TabNav with Navigation -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">
                <i class="fas fa-star text-warning me-2"></i>Khám phá sản phẩm
            </h2>

            <div class="tabnav-container">
                <div class="tabnav-wrapper">
                    <div class="tabnav" id="categoryTabNav">
                        <div class="tabnav-indicator"></div>
                        <div class="tabnav-mask">
                            <ul role="tablist" class="tabnav-items">
                                <li class="tabnav-item" role="presentation">
                                    <button type="button" role="tab" class="tabnav-link active" data-category="all"
                                        aria-selected="true">
                                        <span>Best seller</span>
                                    </button>
                                </li>
                                <?php
                                // Các danh mục chính để hiển thị trong tab - thứ tự tùy chỉnh
                                $featuredCategoryOrder = ['VPN', 'Cloud Storage', 'Game', 'Giải trí'];

                                // Tạo map categories theo tên để dễ sắp xếp
                                $categoryMap = [];
                                foreach ($categories as $cat) {
                                    if (in_array($cat['name'], $featuredCategoryOrder)) {
                                        $categoryMap[$cat['name']] = $cat;
                                    }
                                }

                                // Hiển thị theo thứ tự đã định
                                foreach ($featuredCategoryOrder as $catName):
                                    if (isset($categoryMap[$catName])):
                                        $cat = $categoryMap[$catName];
                                        ?>
                                        <li class="tabnav-item" role="presentation">
                                            <button type="button" role="tab" class="tabnav-link"
                                                data-category="<?= $cat['id'] ?>">
                                                <span><?= e($cat['name']) ?></span>
                                            </button>
                                        </li>
                                        <?php
                                    endif;
                                endforeach;
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Navigation Arrows -->
                <div class="tabnav-navigation">
                    <button class="tabnav-nav-btn tabnav-nav-prev" id="tabProductsPrev"
                        onclick="scrollTabProducts('prev')" aria-label="Xem sản phẩm trước">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button class="tabnav-nav-btn tabnav-nav-next" id="tabProductsNext"
                        onclick="scrollTabProducts('next')" aria-label="Xem sản phẩm tiếp theo">
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Product Grid with Horizontal Scroll -->
        <div class="position-relative mt-4">
            <div class="tab-products-shelf-wrapper">
                <div class="tab-products-shelf" id="tabProductsShelf">
                    <div class="row g-3 flex-nowrap" id="tabProductGrid">
                        <!-- Products will be loaded here via JavaScript -->
                        <div class="col-12 text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Ưu điểm -->
<section class="py-5 lazy-section" data-section="benefits">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="p-4">
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                    <h5>Bảo mật cao</h5>
                    <p class="text-muted">Giao dịch an toàn, bảo mật thông tin khách hàng</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4">
                    <i class="fas fa-bolt fa-3x text-primary mb-3"></i>
                    <h5>Giao hàng nhanh</h5>
                    <p class="text-muted">Nhận tài khoản ngay sau khi thanh toán</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4">
                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                    <h5>Hỗ trợ 24/7</h5>
                    <p class="text-muted">Đội ngũ hỗ trợ nhiệt tình, sẵn sàng giúp đỡ</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4">
                    <i class="fas fa-award fa-3x text-primary mb-3"></i>
                    <h5>Uy tín cao</h5>
                    <p class="text-muted">Bảo hành đổi trả trong thời gian sử dụng</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Thương hiệu nổi bật -->
<section class="py-5 bg-white lazy-section" data-section="brands">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Thương hiệu nổi bật</h2>
            <p class="text-muted">Những dịch vụ và phần mềm hàng đầu thế giới</p>
        </div>

        <div class="row g-4">
            <?php
            // Chỉ hiển thị 6 thương hiệu nổi bật (1 hàng)
            $brands = [
                ['name' => 'Netflix', 'logo' => 'https://cdn.worldvectorlogo.com/logos/netflix-3.svg', 'category' => 'netflix'],
                ['name' => 'Spotify', 'logo' => 'https://storage.googleapis.com/pr-newsroom-wp/1/2018/11/Spotify_Logo_RGB_Green.png', 'category' => 'spotify'],
                ['name' => 'YouTube Premium', 'logo' => 'https://www.gstatic.com/youtube/img/branding/youtubelogo/svg/youtubelogo.svg', 'category' => 'youtube%20premium'],
                ['name' => 'ChatGPT', 'logo' => 'https://fpt.ai/wp-content/uploads/2024/10/image1.jpg', 'category' => 'chatgpt'],
                ['name' => 'Canva Pro', 'logo' => 'https://static.canva.com/web/images/12487a1e0770d29351bd4ce4f87ec8fe.svg', 'category' => 'canva'],
                ['name' => 'Microsoft Office', 'logo' => 'https://nilandi.com/storage/mssuite-1.png', 'category' => 'office'],
            ];

            foreach ($brands as $brand):
                ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="/products?q=<?= e($brand['category']) ?>" class="brand-card">
                        <div class="brand-logo-wrapper">
                            <img src="<?= e($brand['logo']) ?>" alt="<?= e($brand['name']) ?>" class="brand-logo"
                                loading="lazy">
                        </div>
                        <div class="brand-name"><?= e($brand['name']) ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
    /* ===== Categories Sidebar ===== */
    .categories-sidebar {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        height: 350px;
        border: 1px solid #e9ecef;
    }

    .categories-sidebar .list-group {
        overflow: hidden;
        height: 100%;
        background: transparent;
    }

    .category-menu-item {
        border: none !important;
        border-left: 4px solid transparent !important;
        padding: 14px 18px !important;
        font-size: 15px;
        font-weight: 500;
        color: #2c3e50;
        text-decoration: none;
        background: transparent !important;
        margin: 2px 0;
    }

    .category-menu-item:nth-child(n+7) {
        display: none !important;
    }

    .category-menu-item:hover {
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.15) 0%, rgba(102, 126, 234, 0.05) 100%) !important;
        border-left-color: #667eea !important;
        padding-left: 22px !important;
        color: #667eea !important;
    }

    .category-menu-item:first-child {
        margin-top: 4px;
    }

    .category-menu-item:last-child {
        margin-bottom: 4px;
    }

    .category-icon {
        width: 26px;
        height: 26px;
        object-fit: contain;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
    }

    .category-icon-fallback {
        font-size: 22px;
        width: 26px;
        text-align: center;
        color: #667eea;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
    }

    /* ===== Main Banner Carousel ===== */
    .carousel-main {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .carousel-main .carousel-inner {
        border-radius: 12px;
        background: #f8f9fa;
    }

    .carousel-main .carousel-item {
        transition: transform 0.6s ease-in-out !important;
        background: #f8f9fa;
    }

    .carousel-main .carousel-item img {
        display: block;
        width: 100%;
        height: auto;
        aspect-ratio: 1280 / 632;
        object-fit: cover;
        background: #f8f9fa;
        will-change: transform;
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
    }

    /* Force load all images kể cả khi không active */
    .carousel-main .carousel-item img {
        visibility: visible !important;
        position: relative;
    }

    /* Đảm bảo non-active items vẫn load ảnh nhưng ẩn đi */
    .carousel-main .carousel-item:not(.active) {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        visibility: hidden;
        pointer-events: none;
    }

    /* Override Bootstrap carousel transitions */
    .carousel-main.carousel.slide .carousel-item {
        transition: transform 0.6s ease-in-out !important;
    }

    .carousel-main .carousel-item-next,
    .carousel-main .carousel-item-prev {
        display: block !important;
    }

    .carousel-main .carousel-item-next:not(.carousel-item-start),
    .carousel-main .active.carousel-item-end {
        transform: translateX(100%) !important;
    }

    .carousel-main .carousel-item-prev:not(.carousel-item-end),
    .carousel-main .active.carousel-item-start {
        transform: translateX(-100%) !important;
    }

    .carousel-main .carousel-item-next.carousel-item-start,
    .carousel-main .carousel-item-prev.carousel-item-end {
        transform: translateX(0) !important;
    }

    /* Ẩn nút điều hướng mặc định, chỉ hiện khi hover */
    .carousel-main .carousel-control-prev,
    .carousel-main .carousel-control-next {
        width: 40px;
        height: 40px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .carousel-main:hover .carousel-control-prev,
    .carousel-main:hover .carousel-control-next {
        opacity: 1;
    }

    .carousel-main .carousel-control-prev {
        left: 10px;
    }

    .carousel-main .carousel-control-next {
        right: 10px;
    }

    .carousel-main .carousel-control-prev:hover,
    .carousel-main .carousel-control-next:hover {
        background: rgba(0, 0, 0, 0.7);
    }

    .carousel-main .carousel-control-prev-icon,
    .carousel-main .carousel-control-next-icon {
        width: 20px;
        height: 20px;
    }

    /* Banner Carousel Indicators - Modern Design */
    .carousel-main .carousel-indicators {
        position: absolute;
        bottom: 0px;
        left: 0;
        right: 0;
        margin: 0;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 6px;
        z-index: 10;
    }

    .carousel-main .carousel-indicators button {
        width: 8px !important;
        height: 8px !important;
        border-radius: 50% !important;
        margin: 0 !important;
        background: rgba(255, 255, 255, 0.6) !important;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: none !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2),
            0 1px 2px rgba(0, 0, 0, 0.15) !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        padding: 0 !important;
        flex: 0 0 auto !important;
        text-indent: 0 !important;
        cursor: pointer;
        opacity: 1 !important;
    }

    .carousel-main .carousel-indicators button:hover:not(.active) {
        background: rgba(255, 255, 255, 0.8) !important;
        transform: scale(1.15);
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.25) !important;
    }

    .carousel-main .carousel-indicators button.active {
        background: rgba(255, 255, 255, 1) !important;
        width: 40px !important;
        height: 8px !important;
        border-radius: 4px !important;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3),
            0 1px 3px rgba(0, 0, 0, 0.2) !important;
    }

    /* ===== Promo Banners (Right Side - 2 banners) ===== */
    .promo-banners {
        display: flex;
        flex-direction: column;
        gap: 28px;
    }

    .promo-banner-item {
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        display: block;
    }

    .promo-banner-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .promo-banner-item img {
        width: 100%;
        height: auto;
        aspect-ratio: 1070 / 500;
        object-fit: cover;
        display: block;
    }

    /* ===== Bottom Banners (4 banners) ===== */
    .bottom-banner-item {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        display: block;
    }

    .bottom-banner-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .bottom-banner-item img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        display: block;
    }

    /* ===== Product Cards ===== */
    .product-card {
        --hover-bg-color: #f8f9fa;
        --hover-outline-color: rgba(0, 0, 0, 0.06);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        transition-property: transform, border-radius, background-color, box-shadow, outline, outline-offset;
        border: 1px solid #e0e0e0;
        border-radius: 18px;
        outline: 1px solid transparent;
        outline-offset: 0;
        background-color: #fff;
        will-change: transform;
    }

    .product-card:hover {
        transform: scale(1.03);
        border-radius: 18px;
        background-color: var(--hover-bg-color);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.04) !important;
        outline: 1px solid var(--hover-outline-color);
        outline-offset: -1px;
    }

    /* Featured Title with Star Effect */
    .featured-title {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .featured-title i.fa-star {
        color: #ffd700;
        transition: color 0.4s ease, filter 0.4s ease, transform 0.4s ease;
    }

    .featured-title:hover i.fa-star {
        animation: starPulse 0.6s ease-in-out infinite;
        color: #ffd700;
        filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.8));
    }

    /* Sparkle stars animation */
    .featured-title::before,
    .featured-title::after {
        content: '✨';
        position: absolute;
        font-size: 20px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.5s ease;
    }

    .featured-title::before {
        top: -10px;
        left: -20px;
    }

    .featured-title::after {
        bottom: -10px;
        right: -20px;
    }

    .featured-title:hover::before {
        animation: sparkle 1s ease-in-out infinite;
    }

    .featured-title:hover::after {
        animation: sparkle 1s ease-in-out 0.3s infinite;
    }

    @keyframes starPulse {

        0%,
        100% {
            transform: scale(1) rotate(0deg);
        }

        50% {
            transform: scale(1.2) rotate(15deg);
        }
    }

    @keyframes sparkle {
        0% {
            opacity: 0;
            transform: translate(0, 0) scale(0.5);
        }

        50% {
            opacity: 1;
            transform: translate(10px, -10px) scale(1);
        }

        100% {
            opacity: 0;
            transform: translate(20px, -20px) scale(0.5);
        }
    }

    /* Featured products - Ẩn/hiện */
    .featured-hidden {
        display: none;
    }

    /* Button xem thêm */
    .btn-outline-primary {
        border-color: #667eea;
        color: #667eea;
    }

    .btn-outline-primary:hover {
        background: linear-gradient(135deg, #667eea 0%, #7387df 100%);
        border-color: #667eea;
        color: white;
    }

    /* ===== Responsive Design ===== */

    /* Large Desktop (1200px+) */
    @media (min-width: 1200px) {
        .carousel-main .carousel-item img {
            height: auto;
            aspect-ratio: 1280 / 632;
        }
    }

    /* Desktop & Tablet (992px - 1199px) */
    @media (max-width: 1199px) {
        .carousel-main .carousel-item img {
            height: auto;
            aspect-ratio: 1280 / 632;
        }

        .carousel-main .carousel-item {
            min-height: auto;
        }
    }

    /* Tablet (768px - 991px) */
    @media (min-width: 768px) and (max-width: 991px) {
        .promo-banners {
            flex-direction: row;
            gap: 15px;
            height: auto;
            margin-top: 20px;
        }

        .promo-banner-item {
            flex: 1;
        }

        .promo-banner-item.mb-3 {
            margin-bottom: 0 !important;
        }

        .carousel-main .carousel-item img {
            height: auto;
            aspect-ratio: 1280 / 632;
        }

        .carousel-main .carousel-item {
            min-height: auto;
        }

        .bottom-banner-item img {
            height: 100px;
        }

        /* Hide category sidebar on tablet */
        .categories-sidebar {
            display: none;
        }
    }

    /* Mobile Large (576px - 767px) */
    @media (max-width: 767px) {
        .categories-sidebar {
            display: none;
        }

        .carousel-main {
            position: relative;
            padding-bottom: 49.2%;
            /* 184/374 = 49.2% */
            height: 0;
            overflow: hidden;
        }

        .carousel-main .carousel-inner {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .carousel-main .carousel-item {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100%;
            height: 100% !important;
            padding-bottom: 0 !important;
        }

        .carousel-main .carousel-item:not(.active) {
            visibility: visible;
        }

        .carousel-main .carousel-item a {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: block;
        }

        .carousel-main .carousel-item img {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }

        .carousel-main .carousel-indicators {
            position: absolute;
            bottom: 5px;
            z-index: 2;
            margin-bottom: 0;
        }

        .carousel-main .carousel-control-prev,
        .carousel-main .carousel-control-next {
            position: absolute;
            top: 0;
            bottom: 0;
            z-index: 1;
        }

        .promo-banners {
            flex-direction: row;
            gap: 10px;
            margin-top: 20px;
        }

        .promo-banner-item {
            flex: 1;
        }

        .promo-banner-item.mb-3 {
            margin-bottom: 0 !important;
        }

        .promo-banner-item img {
            width: 100%;
            height: auto;
            aspect-ratio: 1070 / 500;
            object-fit: cover;
        }

        .bottom-banner-item img {
            height: 80px;
        }

        h2 {
            font-size: 1.5rem;
        }

        .product-card {
            margin-bottom: 15px;
        }

        /* Make carousel controls smaller on mobile */
        .carousel-main .carousel-control-prev,
        .carousel-main .carousel-control-next {
            width: 30px;
            height: 30px;
        }

        .carousel-main .carousel-control-prev-icon,
        .carousel-main .carousel-control-next-icon {
            width: 15px;
            height: 15px;
        }

        /* Modern indicators on mobile */
        .carousel-main .carousel-indicators {
            bottom: 12px;
            gap: 5px;
        }

        .carousel-main .carousel-indicators button {
            width: 6px !important;
            height: 6px !important;
            background: rgba(255, 255, 255, 0.7) !important;
        }

        .carousel-main .carousel-indicators button.active {
            width: 20px !important;
            height: 6px !important;
            border-radius: 3px !important;
            background: rgba(255, 255, 255, 1) !important;
        }
    }

    /* Mobile Small (< 576px) */
    @media (max-width: 575px) {
        .container {
            padding-left: 8px;
            padding-right: 8px;
        }

        .carousel-main {
            position: relative;
            padding-bottom: 49.2%;
            /* 184/374 = 49.2% */
            height: 0;
            overflow: hidden;
            border-radius: 8px;
        }

        .carousel-main .carousel-inner {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 8px;
        }

        .carousel-main .carousel-item {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100%;
            height: 100% !important;
            padding-bottom: 0 !important;
        }

        .carousel-main .carousel-item:not(.active) {
            visibility: visible;
        }

        .carousel-main .carousel-item a {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: block;
        }

        .carousel-main .carousel-item img {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }

        .carousel-main .carousel-indicators {
            position: absolute;
            bottom: 5px;
            z-index: 2;
            margin-bottom: 0;
        }

        .carousel-main .carousel-control-prev,
        .carousel-main .carousel-control-next {
            position: absolute;
            top: 0;
            bottom: 0;
            z-index: 1;
        }

        .promo-banners {
            flex-direction: row;
            gap: 10px;
            margin-top: 20px;
        }

        .promo-banner-item {
            flex: 1;
        }

        .promo-banner-item.mb-3 {
            margin-bottom: 0 !important;
        }

        .promo-banner-item img {
            width: 100%;
            height: auto;
            aspect-ratio: 1070 / 500;
            object-fit: cover;
        }

        h2 {
            font-size: 1.1rem;
            margin-bottom: 1rem !important;
        }

        /* Optimize for 2-column mobile layout */
        .row.g-4 {
            --bs-gutter-x: 10px;
            --bs-gutter-y: 12px;
        }

        .featured-product-item {
            padding-left: calc(var(--bs-gutter-x) * 0.5);
            padding-right: calc(var(--bs-gutter-x) * 0.5);
            margin-bottom: var(--bs-gutter-y);
        }

        .product-card {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08) !important;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card a {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card .card-img-top {
            height: auto;
            aspect-ratio: 1070 / 500;
            object-fit: cover;
            width: 100%;
            display: block;
        }

        .product-card .position-relative .card-img-top {
            border-bottom: 1px solid #f0f0f0;
        }

        .card-body {
            padding: 10px 10px 12px 10px !important;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .card-title {
            font-size: 14px !important;
            line-height: 1.35;
            margin-bottom: 0 !important;
            min-height: auto;
            max-height: 2.7em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            word-break: break-word;
            font-weight: 600;
            color: #1a1a1a !important;
        }

        .card-text {
            font-size: 11px;
            margin-bottom: 0;
            line-height: 1.3;
        }

        .product-card .mb-2 {
            margin-bottom: 0 !important;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .badge {
            font-size: 10px !important;
            padding: 3px 6px !important;
            font-weight: 600;
            margin-top: 0;
        }

        .badge.bg-success {
            display: none !important;
        }

        .badge.bg-danger {
            font-size: 11px !important;
            padding: 4px 8px !important;
            font-weight: 700;
        }

        .product-card .position-relative {
            margin-bottom: 0;
        }

        .product-card .position-absolute.top-0.end-0 {
            margin: 8px;
        }

        .btn-sm {
            font-size: 10px !important;
            padding: 4px 6px !important;
            white-space: nowrap;
        }

        .btn-primary {
            font-size: 11px !important;
            padding: 5px 8px !important;
            width: 100%;
        }

        /* Price styling for mobile */
        .product-card .h5 {
            font-size: 17px !important;
            font-weight: 700;
            margin-bottom: 0 !important;
            line-height: 1;
        }

        .text-primary {
            font-size: 17px !important;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 0;
            line-height: 1;
        }

        .text-muted.text-decoration-line-through {
            font-size: 12px !important;
            display: inline-block;
            margin-left: 0;
            vertical-align: baseline;
            opacity: 0.7;
        }

        .text-danger {
            font-size: 10px !important;
            display: inline-block;
        }

        /* Discount badge position */
        .position-absolute.top-0.end-0 {
            margin: 4px !important;
        }

        /* Category icon smaller */
        .category-icon,
        .category-icon-fallback {
            width: 18px !important;
            height: 18px !important;
        }
    }

    /* Extra Mobile Small (< 360px) - Very old or small phones */
    @media (max-width: 359px) {
        .carousel-main {
            position: relative;
            padding-bottom: 49.2%;
            /* 184/374 = 49.2% */
            height: 0;
            overflow: hidden;
        }

        .carousel-main .carousel-inner {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .carousel-main .carousel-item {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100%;
            height: 100% !important;
            padding-bottom: 0 !important;
        }

        .carousel-main .carousel-item:not(.active) {
            visibility: visible;
        }

        .carousel-main .carousel-item a {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: block;
        }

        .carousel-main .carousel-item img {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }

        .carousel-main .carousel-indicators {
            position: absolute;
            bottom: 5px;
            z-index: 2;
            margin-bottom: 0;
        }

        .carousel-main .carousel-control-prev,
        .carousel-main .carousel-control-next {
            position: absolute;
            top: 0;
            bottom: 0;
            z-index: 1;
        }

        .product-card .card-img-top {
            height: auto;
            aspect-ratio: 1070 / 500;
        }

        .card-body {
            padding: 8px 8px 10px 8px !important;
            gap: 6px;
        }

        .card-title {
            font-size: 13px !important;
            line-height: 1.35;
            margin-bottom: 0 !important;
            max-height: 2.7em;
            font-weight: 600;
        }

        .product-card .h5 {
            font-size: 16px !important;
        }

        .text-primary {
            font-size: 16px !important;
        }

        .text-muted.text-decoration-line-through {
            font-size: 11px !important;
        }

        .product-card .mb-2 {
            margin-bottom: 0 !important;
            gap: 5px;
        }

        .badge {
            margin-top: 0;
            font-size: 9px !important;
        }

        .badge.bg-success {
            display: none !important;
        }

        .badge.bg-danger {
            font-size: 10px !important;
            padding: 3px 6px !important;
        }

        .product-card .position-absolute.top-0.end-0 {
            margin: 6px;
        }

        .row.g-4 {
            --bs-gutter-x: 8px;
            --bs-gutter-y: 10px;
        }
    }

    /* Category Shelf Styles */
    .category-shelf-wrapper {
        position: relative;
        overflow: hidden;
        margin: 0 -12px;
        padding: 0 12px;
    }


    .category-shelf {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        scroll-behavior: smooth;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 4px;
        margin: 0;
    }

    .category-shelf::-webkit-scrollbar {
        display: none;
    }

    .category-shelf-card {
        flex: 0 0 auto;
        width: 320px;
        text-decoration: none;
        color: inherit;
        --hover-bg-color: rgba(0, 0, 0, 0.03);
        --hover-outline-color: rgba(0, 0, 0, 0.08);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: transform;
    }

    .category-card-image-wrapper {
        position: relative;
        width: 100%;
        aspect-ratio: 2.14;
        border-radius: 18px;
        overflow: hidden;
        background: #f5f5f7;
        outline: 1px solid rgba(0, 0, 0, 0.06);
        outline-offset: -1px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .category-card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .category-card-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 24px 20px;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.5) 50%, transparent 100%);
        transition: background 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .category-card-title {
        color: white;
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        letter-spacing: -0.02em;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .category-card-count {
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
        margin: 4px 0 0 0;
        font-weight: 400;
        letter-spacing: -0.01em;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    .category-shelf-card:hover .category-card-image-wrapper {
        transform: translateY(-4px);
        outline: 1px solid var(--hover-outline-color);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        background-color: var(--hover-bg-color);
    }

    .category-shelf-card:hover .category-card-image {
        transform: scale(1.05);
    }

    .category-shelf-card:hover .category-card-overlay {
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.6) 50%, transparent 100%);
    }

    .shelf-nav-arrows {
        display: flex;
        gap: 8px;
    }

    .shelf-nav-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1px solid #d2d2d7;
        background: white;
        color: #1d1d1f;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 14px;
    }

    .shelf-nav-btn:hover {
        background: #f5f5f7;
        border-color: #86868b;
        transform: scale(1.05);
    }

    .shelf-nav-btn:active {
        transform: scale(0.95);
    }

    .shelf-nav-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .shelf-nav-btn:disabled:hover {
        background: white;
        border-color: #d2d2d7;
        transform: scale(1);
    }

    @media (max-width: 768px) {
        .category-shelf-card {
            width: 280px;
        }

        .category-card-title {
            font-size: 18px;
        }

        .category-card-count {
            font-size: 13px;
        }

        .shelf-nav-arrows {
            display: none;
        }
    }

    /* Best Sellers Section - Horizontal Scroll */
    .bestseller-shelf-wrapper {
        position: relative;
        overflow: hidden;
        margin: 0 -12px;
        padding: 0 12px;
    }


    .bestseller-shelf {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        scroll-behavior: smooth;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 4px;
        margin: 0;
    }

    .bestseller-shelf::-webkit-scrollbar {
        display: none;
    }

    .bestseller-shelf-item {
        flex: 0 0 auto;
        width: 260px;
    }

    .bestseller-card {
        display: block;
        width: 100%;
        --hover-bg-color: rgba(255, 71, 87, 0.08);
        --hover-outline-color: rgba(255, 71, 87, 0.15);
    }

    .bestseller-card .card {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #e0e0e0;
        border-radius: 19px;
        overflow: hidden;
        background: white;
        box-shadow: none !important;
    }

    /* Override product-card hover effects - Zoom về phía màn hình */
    .bestseller-shelf-item {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Tắt hoàn toàn màu hover cho bestseller cards */
    .bestseller-shelf-item a.product-card.bestseller-card {
        --hover-bg-color: transparent !important;
        --hover-outline-color: transparent !important;
        transform: none !important;
        background-color: transparent !important;
        outline: none !important;
        box-shadow: none !important;
        border-radius: 19px !important;
    }

    .bestseller-shelf-item:hover {
        transform: scale(1.03);
    }

    .bestseller-shelf-item a.product-card.bestseller-card:hover {
        transform: none !important;
        background-color: transparent !important;
        outline: none !important;
        outline-offset: 0 !important;
        box-shadow: none !important;
        border-radius: 19px !important;
    }

    .bestseller-card .card {
        border: 1px solid #e0e0e0 !important;
        border-radius: 19px !important;
        overflow: hidden;
    }

    .bestseller-card:hover .card,
    .bestseller-card .card:hover {
        transform: none !important;
        box-shadow: none !important;
        background-color: white !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 19px !important;
        outline: none !important;
    }

    .bestseller-card .card-body {
        border-bottom-left-radius: 19px !important;
        border-bottom-right-radius: 19px !important;
        border-top-left-radius: 0 !important;
        border-top-right-radius: 0 !important;
    }

    .bestseller-card .badge {
        font-size: 11px;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 8px;
        letter-spacing: -0.01em;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    .bestseller-card .bg-success {
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%) !important;
        border: none;
    }

    .bestseller-card .card-img-top {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        aspect-ratio: 1070 / 500;
        object-fit: cover;
        border-top-left-radius: 19px !important;
        border-top-right-radius: 19px !important;
        border-bottom-left-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    .bestseller-card:hover .card-img-top {
        transform: none;
    }

    .bestseller-card .card-body {
        background: white;
        padding: 12px !important;
    }

    .bestseller-card .card-title {
        font-size: 15px !important;
        height: 42px !important;
        color: #1a1a1a !important;
        font-weight: 600 !important;
    }

    .bestseller-card .text-danger {
        color: #dc3545 !important;
        font-weight: 700 !important;
    }

    .bestseller-card .text-muted {
        color: #6c757d !important;
        opacity: 1 !important;
    }

    .bestseller-card small.text-muted {
        color: #495057 !important;
    }

    @media (max-width: 768px) {
        .bestseller-shelf-item {
            width: 200px;
        }
    }

    /* Featured Brands Section - Modern Design */
    .brand-card {
        display: block;
        text-decoration: none;
        color: inherit;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .brand-logo-wrapper {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 20px;
        padding: 28px 32px;
        aspect-ratio: 16/9;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05),
            0 1px 3px rgba(0, 0, 0, 0.03);
        position: relative;
        overflow: hidden;
    }

    .brand-logo-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.5) 0%, rgba(255, 255, 255, 0) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .brand-logo {
        max-width: 95%;
        max-height: 85%;
        width: auto;
        height: auto;
        object-fit: contain;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 1;
        filter: grayscale(0.3);
    }

    .brand-name {
        text-align: center;
        margin-top: 16px;
        font-size: 14px;
        font-weight: 600;
        color: #1d1d1f;
        letter-spacing: -0.01em;
        transition: color 0.3s ease;
    }

    .brand-card:hover .brand-logo-wrapper {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08),
            0 4px 8px rgba(0, 0, 0, 0.06);
        border-color: rgba(0, 0, 0, 0.08);
    }

    .brand-card:hover .brand-logo-wrapper::before {
        opacity: 1;
    }

    .brand-card:hover .brand-logo {
        transform: scale(1.05);
        filter: grayscale(0);
    }

    .brand-card:hover .brand-name {
        color: #0071e3;
    }

    @media (max-width: 768px) {
        .brand-logo-wrapper {
            padding: 24px 16px;
        }

        .brand-name {
            font-size: 13px;
            margin-top: 12px;
        }
    }

    /* TabNav Container - Flex layout with navigation */
    .tabnav-container {
        display: flex;
        align-items: center;
        gap: 24px;
    }

    /* TabNav - Apple Style (100% Exact Match) */
    .tabnav-wrapper {
        --tabnav-height: 56px;
        --tabnav-platter-padding: 6px;
        --tabnav-platter-background: rgb(232, 232, 237);
        --tabnav-indicator-start: 6px;
        --tabnav-indicator-width: 0;
        --tabnav-indicator-background: rgb(29, 29, 31);
        --tabnav-item-padding-inline: 22px;
        --tabnav-copy-color: rgb(0, 0, 0);
        --tabnav-copy-selected-color: rgb(255, 255, 255);
        position: relative;
        max-width: 100%;
    }

    /* Navigation Buttons next to TabNav */
    .tabnav-navigation {
        display: flex;
        gap: 12px;
    }

    .tabnav-nav-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(0, 0, 0, 0.04);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1),
            0 1px 4px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #1d1d1f;
        font-size: 16px;
    }

    .tabnav-nav-btn:hover {
        background: rgba(255, 255, 255, 1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15),
            0 2px 6px rgba(0, 0, 0, 0.1);
        transform: scale(1.05);
    }

    .tabnav-nav-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .tabnav {
        position: relative;
        width: fit-content;
        max-width: 100%;
        height: var(--tabnav-height);
        background: var(--tabnav-platter-background);
        border-radius: 999px;
        padding: var(--tabnav-platter-padding);
        box-sizing: border-box;
        overflow: hidden;
    }

    .tabnav-indicator {
        position: absolute;
        left: var(--tabnav-indicator-start);
        width: var(--tabnav-indicator-width);
        height: calc(100% - (var(--tabnav-platter-padding) * 2));
        top: var(--tabnav-platter-padding);
        background: var(--tabnav-indicator-background);
        border-radius: 999px;
        transition: left 0.32s cubic-bezier(0.4, 0, 0.2, 1),
            width 0.32s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1;
        box-shadow: 0 3px 8px 0 rgba(0, 0, 0, 0.12),
            0 3px 1px 0 rgba(0, 0, 0, 0.04);
    }

    .tabnav-mask {
        position: relative;
        z-index: 2;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
        -webkit-overflow-scrolling: touch;
        height: 100%;
    }

    .tabnav-mask::-webkit-scrollbar {
        display: none;
    }

    .tabnav-items {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 0;
        min-width: min-content;
        position: relative;
        height: 100%;
        align-items: center;
    }

    .tabnav-item {
        flex-shrink: 0;
        height: 100%;
        display: flex;
        align-items: center;
    }

    .tabnav-link {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-inline: var(--tabnav-item-padding-inline);
        height: 100%;
        background: transparent;
        border: none;
        color: var(--tabnav-copy-color);
        font-size: 14px;
        font-weight: 600;
        letter-spacing: -0.016em;
        white-space: nowrap;
        cursor: pointer;
        transition: color 0.3s ease,
            opacity 0.3s ease;
        border-radius: 999px;
        position: relative;
        z-index: 3;
        outline: none;
        -webkit-tap-highlight-color: transparent;
        font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        opacity: 0.56;
    }

    .tabnav-link:hover:not(.active) {
        opacity: 0.8;
    }

    .tabnav-link.active {
        color: var(--tabnav-copy-selected-color);
        opacity: 1;
        cursor: default;
        pointer-events: none;
    }

    .tabnav-link span {
        position: relative;
        z-index: 4;
        display: block;
    }

    /* Tab Products Shelf - Apple Style */
    .tab-products-shelf-wrapper {
        position: relative;
        padding: 0;
    }


    .tab-products-shelf {
        overflow-x: auto;
        overflow-y: hidden;
        scroll-behavior: smooth;
        scrollbar-width: none;
        -ms-overflow-style: none;
        -webkit-overflow-scrolling: touch;
        padding: 20px 0;
        will-change: scroll-position;
    }

    .tab-products-shelf::-webkit-scrollbar {
        display: none;
    }

    .tab-products-shelf .row {
        margin: 0;
    }

    .tab-products-shelf .row>* {
        flex: 0 0 auto;
        width: 300px;
    }

    /* Apple-style Product Card base styling */
    .tab-products-shelf .product-card {
        border-radius: 18px;
        border: 1px solid rgba(0, 0, 0, 0.06);
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07),
            0 1px 3px rgba(0, 0, 0, 0.06);
    }

    /* Product cards - fade in effect */
    .tab-products-shelf .product-card {
        opacity: 0;
        transition: opacity 0.15s ease-in-out,
            transform 0.3s ease-in-out,
            box-shadow 0.3s ease-in-out,
            border-color 0.3s ease-in-out;
    }

    /* Cards visible when show class applied */
    .tab-products-shelf.show .product-card {
        opacity: 1;
    }

    /* Hover effect - zoom in smoothly */
    .tab-products-shelf .product-card:hover {
        transform: scale(1.03);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1),
            0 4px 8px rgba(0, 0, 0, 0.08);
        border-color: rgba(0, 0, 0, 0.08);
    }

    /* Badge styling */
    .tab-products-shelf .badge {
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 8px;
        letter-spacing: 0.02em;
    }

    /* Typography improvements */
    .tab-products-shelf .card-title {
        font-size: 15px;
        font-weight: 600;
        letter-spacing: -0.01em;
        line-height: 1.3;
    }

    .tab-products-shelf .h4 {
        font-size: 20px;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    /* Global padding adjustments for all devices */
    .py-5 {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }

    .py-4 {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }

    .mt-5 {
        margin-top: 1rem !important;
    }

    /* Custom background color for bg-light sections */
    .bg-light {
        background-color: rgba(246, 246, 246, 1) !important;
    }

    /* Background color for sections - Different colors */
    [data-section="featured-categories"] {
        background: #f6f6f6 !important;
    }

    [data-section="benefits"] {
        background: rgba(255, 255, 255) !important;
    }

    /* Benefits Section - Apple Style Cards */
    [data-section="benefits"] .p-4 {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid rgba(0, 0, 0, 0.06);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07),
            0 1px 3px rgba(0, 0, 0, 0.06);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
    }

    [data-section="benefits"] .p-4:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12),
            0 6px 12px rgba(0, 0, 0, 0.08);
        border-color: rgba(0, 0, 0, 0.08);
    }

    /* Colorful icons - Apple colors */
    [data-section="benefits"] .fa-shield-alt {
        color: #007AFF !important;
    }

    [data-section="benefits"] .fa-bolt {
        color: #FF9500 !important;
    }

    [data-section="benefits"] .fa-headset {
        color: #34C759 !important;
    }

    [data-section="benefits"] .fa-award {
        color: #AF52DE !important;
    }

    [data-section="benefits"] .fa-3x {
        transition: transform 0.3s ease;
    }

    [data-section="benefits"] .p-4:hover .fa-3x {
        transform: scale(1.1);
    }

    [data-section="benefits"] h5 {
        font-weight: 600;
        letter-spacing: -0.01em;
        margin-bottom: 0.75rem;
        color: #1d1d1f;
    }

    [data-section="benefits"] .text-muted {
        font-size: 14px;
        line-height: 1.5;
        color: #6e6e73 !important;
    }

    @media (max-width: 1068px) {
        .tabnav-wrapper {
            --tabnav-height: 44px;
            --tabnav-platter-padding: 4px;
            --tabnav-item-padding-inline: 16px;
        }

        .tabnav-link {
            font-size: 12px;
        }
    }

    @media (max-width: 734px) {
        .tabnav-wrapper {
            --tabnav-height: 40px;
            --tabnav-platter-padding: 3px;
            --tabnav-item-padding-inline: 14px;
        }

        .tabnav-link {
            font-size: 11px;
        }
    }

    @media (max-width: 480px) {
        .tabnav-wrapper {
            --tabnav-height: 36px;
            --tabnav-item-padding-inline: 12px;
        }

        .tabnav-link {
            font-size: 10px;
        }
    }

    /* Mobile: Stack title and TabNav vertically - only for "Khám phá sản phẩm" section */
    @media (max-width: 768px) {

        [data-section="featured-products"],
        [data-section="categories"],
        [data-section="best-sellers"],
        [data-section="featured-categories"],
        [data-section="benefits"],
        [data-section="brands"] {
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
        }

        [data-section="featured-categories"] .d-flex.justify-content-between.align-items-center.mb-4 {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 12px;
            margin-bottom: 1rem !important;
        }

        /* Remove margin-top from product grid */
        [data-section="featured-categories"] .position-relative.mt-4 {
            margin-top: 0 !important;
        }

        /* Prevent zoom on search input tap - set font-size >= 16px */
        #searchInput,
        .form-control {
            font-size: 16px !important;
        }

        /* Remove padding-top from product shelf */
        .tab-products-shelf {
            padding-top: 0 !important;
        }

        /* Keep "Danh mục sản phẩm" and "Sản phẩm bán chạy" sections horizontal - title left, buttons right */
        [data-section="categories"] .d-flex.justify-content-between.align-items-center.mb-4,
        [data-section="best-sellers"] .d-flex.justify-content-between.align-items-center.mb-4 {
            flex-direction: row !important;
            align-items: center !important;
            gap: 12px;
        }

        /* Make titles bigger on mobile */
        .featured-title {
            font-size: 24px !important;
        }

        .section-title {
            font-size: 22px !important;
        }

        /* Adjust icon size on mobile to match text */
        .section-title i.fa-fire,
        .section-title i.fa-th-large {
            font-size: 20px !important;
        }

        [data-section="categories"] .section-title,
        [data-section="best-sellers"] .section-title {
            font-size: 22px !important;
            flex: 1;
        }

        /* Buttons stay on the right */
        [data-section="categories"] .tabnav-navigation,
        [data-section="best-sellers"] .tabnav-navigation {
            flex-shrink: 0;
        }

        .tabnav-container {
            width: fit-content;
            justify-content: flex-start;
            position: relative;
        }

        .tabnav-wrapper {
            flex: 0 1 auto;
            overflow: hidden;
            width: fit-content;
            max-width: 100%;
        }

        .tabnav {
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: none;
            -ms-overflow-style: none;
            scroll-behavior: smooth;
            position: relative;
            overscroll-behavior-x: none;
            -webkit-overflow-scrolling: auto;
        }

        .tabnav::-webkit-scrollbar {
            display: none;
        }

        /* Prevent manual scrolling/dragging */
        .tabnav-mask {
            touch-action: pan-y;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }

        /* Re-enable pointer events for tab buttons */
        .tabnav-link {
            pointer-events: auto;
            cursor: pointer;
        }

        /* Ensure tabs don't shrink on mobile */
        .tabnav-items {
            flex-wrap: nowrap;
            width: max-content;
        }

        .tabnav-item {
            flex-shrink: 0;
            min-width: fit-content;
        }

        .tabnav-link {
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Mobile navigation buttons - hidden on mobile */
        .tabnav-navigation {
            display: none !important;
        }

        .tabnav-nav-btn {
            width: 32px;
            height: 32px;
            font-size: 12px;
            flex-shrink: 0;
        }

        /* Benefits Section Mobile - 2 Columns Layout */
        [data-section="benefits"] .row {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 0.75rem !important;
        }

        [data-section="benefits"] .col-md-3 {
            width: 100% !important;
            padding: 0 !important;
        }

        [data-section="benefits"] .p-4 {
            padding: 1.25rem 1rem !important;
            border-radius: 14px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
        }

        [data-section="benefits"] .fa-3x {
            font-size: 2rem !important;
            margin-bottom: 0.5rem !important;
        }

        [data-section="benefits"] h5 {
            font-size: 15px !important;
            margin-bottom: 0.4rem !important;
            text-align: center;
        }

        [data-section="benefits"] .text-muted {
            font-size: 12px !important;
            line-height: 1.35;
            text-align: center;
        }

        /* Touch interactions */
        [data-section="benefits"] .p-4:hover {
            transform: none;
        }

        [data-section="benefits"] .p-4:active {
            transform: scale(0.97);
        }
    }

    @media (max-width: 576px) {

        /* Make titles bigger on small mobile screens too */
        .featured-title {
            font-size: 22px !important;
        }

        .section-title {
            font-size: 20px !important;
        }

        /* Adjust icon size on small mobile to match text */
        .section-title i.fa-fire,
        .section-title i.fa-th-large {
            font-size: 18px !important;
        }

        [data-section="categories"] .section-title {
            font-size: 20px !important;
        }

        /* Smaller buttons for "Danh mục sản phẩm" on small screens */
        [data-section="categories"] .tabnav-nav-btn {
            width: 28px;
            height: 28px;
            font-size: 11px;
        }

        .tabnav-wrapper {
            --tabnav-height: 40px;
        }

        .tabnav-nav-btn {
            width: 28px;
            height: 28px;
            font-size: 11px;
        }
    }

    /* Tab Products Shelf Responsive */
    @media (max-width: 768px) {
        .tab-products-shelf .row>* {
            width: 260px;
        }

        .tab-products-shelf .card-title {
            font-size: 14px;
        }

        .tab-products-shelf .h4 {
            font-size: 18px;
        }
    }

    @media (max-width: 576px) {
        .tab-products-shelf .row>* {
            width: 240px;
        }

        .tab-products-shelf .card-title {
            font-size: 13px;
            min-height: 36px !important;
        }

        .tab-products-shelf .h4 {
            font-size: 16px;
        }

        .tab-products-shelf .badge {
            font-size: 10px;
            padding: 3px 6px;
        }

        .tabnav-container {
            flex-direction: column;
            gap: 16px;
        }

        .tabnav-nav-btn {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }

        /* Benefits Section - 2 Columns Layout (maintained) */
        [data-section="benefits"] .p-4 {
            padding: 1rem 0.75rem !important;
            border-radius: 12px;
        }

        [data-section="benefits"] .fa-3x {
            font-size: 1.75rem !important;
            margin-bottom: 0.4rem !important;
        }

        [data-section="benefits"] h5 {
            font-size: 14px !important;
            margin-bottom: 0.3rem !important;
        }

        [data-section="benefits"] .text-muted {
            font-size: 11px !important;
            line-height: 1.3;
        }
    }
</style>

<script>
    // Banner Carousel Auto-slide với smooth slide effect
    document.addEventListener('DOMContentLoaded', function () {
        const bannerCarousel = document.getElementById('bannerCarousel');
        if (bannerCarousel) {
            // Ẩn carousel cho đến khi tất cả ảnh load xong
            bannerCarousel.style.opacity = '0';

            // Preload tất cả images vào memory trước
            const allImages = bannerCarousel.querySelectorAll('img');
            const imageObjects = [];

            const imagePromises = Array.from(allImages).map((imgElement, index) => {
                return new Promise((resolve) => {
                    const img = new Image();
                    imageObjects[index] = img;

                    img.onload = () => {
                        // Đợi decode xong
                        if (img.decode) {
                            img.decode()
                                .then(() => {
                                    // Set src cho img element để browser cache hit
                                    imgElement.src = img.src;
                                    resolve();
                                })
                                .catch(() => resolve());
                        } else {
                            imgElement.src = img.src;
                            resolve();
                        }
                    };

                    img.onerror = () => resolve();

                    // Bắt đầu load
                    img.src = imgElement.getAttribute('src') || imgElement.src;
                });
            });

            // Chờ tất cả ảnh load và decode xong
            Promise.all(imagePromises).then(() => {
                // Thêm delay để đảm bảo browser đã cache và render
                setTimeout(() => {
                    // Hiện carousel với fade in
                    bannerCarousel.style.transition = 'opacity 0.3s ease-in-out';
                    bannerCarousel.style.opacity = '1';

                    // Initialize Bootstrap carousel
                    const carousel = new bootstrap.Carousel(bannerCarousel, {
                        interval: 5000,
                        ride: 'carousel',
                        pause: 'hover',
                        wrap: true,
                        touch: true
                    });
                }, 200);
            });
        }
    });

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

    // Product card color extraction is now handled by Intersection Observer for better performance

    // Featured Products - View More (show 3 rows at a time)
    function toggleFeaturedProducts() {
        const hiddenItems = document.querySelectorAll('.featured-hidden');
        const btn = document.getElementById('viewMoreBtn');
        const itemsPerRow = 4; // 4 sản phẩm mỗi hàng
        const rowsToShow = 3; // Hiển thị 3 hàng mỗi lần
        const itemsToShow = itemsPerRow * rowsToShow; // 12 sản phẩm

        // Hiển thị 12 sản phẩm đầu tiên trong danh sách ẩn
        let shownCount = 0;
        hiddenItems.forEach((item, index) => {
            if (index < itemsToShow) {
                item.classList.remove('featured-hidden');
                shownCount++;
            }
        });

        // Kiểm tra còn sản phẩm ẩn nào không
        const remainingHidden = document.querySelectorAll('.featured-hidden').length;
        if (remainingHidden === 0) {
            // Ẩn nút "Xem thêm" nếu đã hiển thị hết
            btn.style.display = 'none';
        } else {
            // Cập nhật text nút để hiển thị số sản phẩm còn lại
            const icon = btn.querySelector('i');
            btn.innerHTML = '';
            if (icon) btn.appendChild(icon);
            btn.appendChild(document.createTextNode(`Xem thêm (${remainingHidden} sản phẩm)`));
        }
    }

    // Category Shelf Navigation
    function scrollCategoryShelf(direction) {
        const shelf = document.getElementById('categoryShelf');
        if (!shelf) return;

        // Get all category cards
        const cards = shelf.querySelectorAll('.category-shelf-card');
        if (cards.length === 0) return;

        const currentScroll = shelf.scrollLeft;

        // Find the first fully visible card (or closest to left edge)
        let currentCardIndex = 0;
        for (let i = 0; i < cards.length; i++) {
            if (cards[i].offsetLeft >= currentScroll) {
                currentCardIndex = i;
                break;
            }
        }

        // Scroll to 3 cards ahead (4th category becomes 1st position)
        let targetCardIndex;
        if (direction === 1) {
            targetCardIndex = Math.min(currentCardIndex + 3, cards.length - 1);
        } else {
            targetCardIndex = Math.max(currentCardIndex - 3, 0);
        }

        const targetScroll = cards[targetCardIndex].offsetLeft;

        shelf.scrollTo({
            left: targetScroll,
            behavior: 'smooth'
        });
    }

    // Best Selling Shelf Navigation
    function scrollBestSellingShelf(direction) {
        const shelf = document.getElementById('bestsellerShelf');
        if (!shelf) return;

        // Scroll 4 cards at a time: 4 * (260px card + 16px gap) = 1104px
        const scrollAmount = 1104;
        const currentScroll = shelf.scrollLeft;
        const maxScroll = shelf.scrollWidth - shelf.clientWidth;

        let targetScroll;

        if (direction === 1) {
            // Next button
            if (currentScroll >= maxScroll - 1) {
                targetScroll = 0;  // At end, go back to start
            } else {
                targetScroll = currentScroll + scrollAmount;
            }
        } else {
            // Prev button
            if (currentScroll <= 0) {
                targetScroll = maxScroll;  // At start, go to end
            } else {
                targetScroll = currentScroll - scrollAmount;
            }
        }

        shelf.scrollTo({
            left: targetScroll,
            behavior: 'smooth'
        });
    }

    // Category card color extraction is now handled by Intersection Observer for better performance
    document.addEventListener('DOMContentLoaded', function () {
        // Update navigation button states on scroll
        const shelf = document.getElementById('categoryShelf');
        if (shelf) {
            const updateNavButtons = () => {
                const prevBtn = document.querySelector('.shelf-nav-prev');
                const nextBtn = document.querySelector('.shelf-nav-next');

                // Always enable both buttons for infinite scroll
                if (prevBtn && nextBtn) {
                    prevBtn.disabled = false;
                    nextBtn.disabled = false;
                }
            };

            shelf.addEventListener('scroll', updateNavButtons);
            updateNavButtons(); // Initial state
        }

        // Update bestseller shelf navigation buttons
        const bestsellerShelf = document.getElementById('bestsellerShelf');
        if (bestsellerShelf) {
            // Find buttons specifically for bestseller shelf (they're in the parent's previous sibling)
            const section = bestsellerShelf.closest('section');
            const navArrows = section ? section.querySelector('.shelf-nav-arrows') : null;

            if (navArrows) {
                const prevBtn = navArrows.querySelector('.shelf-nav-prev');
                const nextBtn = navArrows.querySelector('.shelf-nav-next');

                const updateBestsellerButtons = () => {
                    // Always enable both buttons for infinite scroll
                    if (prevBtn && nextBtn) {
                        prevBtn.disabled = false;
                        nextBtn.disabled = false;
                    }
                };

                bestsellerShelf.addEventListener('scroll', updateBestsellerButtons);
                updateBestsellerButtons(); // Initial state
            }
        }
    });

    // Lazy Loading with Intersection Observer
    document.addEventListener('DOMContentLoaded', function () {
        // Configuration for the observer
        const observerOptions = {
            root: null,
            rootMargin: '200px', // Start loading 200px before section enters viewport
            threshold: 0.01
        };

        // Track which sections have been loaded
        const loadedSections = new Set();

        // Callback function for intersection observer
        const handleIntersection = (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const section = entry.target;
                    const sectionName = section.getAttribute('data-section');

                    // Only load once
                    if (!loadedSections.has(sectionName)) {
                        loadedSections.add(sectionName);

                        // Add loaded class for potential CSS animations
                        section.classList.add('lazy-loaded');

                        // Trigger any section-specific loading logic
                        switch (sectionName) {
                            case 'featured-products':
                                // Apply color extraction to featured product cards
                                initializeFeaturedProducts(section);
                                break;
                            case 'categories':
                                // Initialize category shelf
                                initializeCategoryShelf(section);
                                break;
                            case 'best-sellers':
                                // Initialize best sellers shelf
                                initializeBestSellers(section);
                                break;
                            case 'brands':
                                // Brands are already loaded with lazy images
                                break;
                        }

                        // Stop observing this section
                        observer.unobserve(section);
                    }
                }
            });
        };

        // Initialize featured products section
        function initializeFeaturedProducts(section) {
            const productCards = section.querySelectorAll('.product-card:not(.bestseller-card)');
            productCards.forEach(card => {
                const img = card.querySelector('img.card-img-top');
                if (!img || !img.complete) return;

                try {
                    const color = getAverageColor(img);
                    const rgba = `rgba(${color.r}, ${color.g}, ${color.b}, 0.08)`;
                    const rgbaOutline = `rgba(${color.r}, ${color.g}, ${color.b}, 0.12)`;

                    card.style.setProperty('--hover-bg-color', rgba);
                    card.style.setProperty('--hover-outline-color', rgbaOutline);
                } catch (e) {
                    // Silent fail
                }
            });
        }

        // Initialize category shelf
        function initializeCategoryShelf(section) {
            const categoryCards = section.querySelectorAll('.category-shelf-card');
            categoryCards.forEach(card => {
                const img = card.querySelector('.category-card-image');
                if (!img) return;

                const applyColor = () => {
                    try {
                        const color = getAverageColor(img);
                        const rgba = `rgba(${color.r}, ${color.g}, ${color.b}, 0.08)`;
                        const rgbaOutline = `rgba(${color.r}, ${color.g}, ${color.b}, 0.15)`;

                        card.style.setProperty('--hover-bg-color', rgba);
                        card.style.setProperty('--hover-outline-color', rgbaOutline);
                    } catch (e) {
                        // Silent fail
                    }
                };

                if (img.complete) {
                    applyColor();
                } else {
                    img.addEventListener('load', applyColor);
                }
            });
        }

        // Initialize best sellers section
        function initializeBestSellers(section) {
            // Best sellers don't need color extraction, so nothing to do
        }

        // Create the observer
        const sectionObserver = new IntersectionObserver(handleIntersection, observerOptions);

        // Observe all lazy sections
        const lazySections = document.querySelectorAll('.lazy-section');
        lazySections.forEach(section => {
            const sectionName = section.getAttribute('data-section');

            // Featured products section loads immediately for better UX
            if (sectionName === 'featured-products') {
                section.classList.add('lazy-loaded');
                initializeFeaturedProducts(section);
            } else {
                sectionObserver.observe(section);
            }
        });

        // Performance optimization: Defer non-critical carousel initialization
        // Only initialize carousel after page load
        if (window.requestIdleCallback) {
            requestIdleCallback(() => {
                initializeCarouselPreloading();
            });
        } else {
            setTimeout(initializeCarouselPreloading, 1);
        }

        function initializeCarouselPreloading() {
            // Carousel initialization is already handled above, but we can add
            // additional optimizations here if needed
        }
    });

    // Add smooth fade-in animation for lazy loaded sections
    const style = document.createElement('style');
    style.textContent = `
    .lazy-section {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }

    .lazy-section.lazy-loaded {
        opacity: 1;
        transform: translateY(0);
    }

    /* First section should be visible immediately */
    .lazy-section:first-of-type,
    .lazy-section[data-section="featured-products"] {
        opacity: 1;
        transform: translateY(0);
    }

    /* Reduce motion for users who prefer it */
    @media (prefers-reduced-motion: reduce) {
        .lazy-section {
            opacity: 1;
            transform: none;
            transition: none;
        }
    }
`;
    document.head.appendChild(style);

    // TabNav functionality
    document.addEventListener('DOMContentLoaded', function () {
        const tabNav = document.getElementById('categoryTabNav');
        if (!tabNav) return;

        const tabLinks = tabNav.querySelectorAll('.tabnav-link');
        const indicator = tabNav.querySelector('.tabnav-indicator');
        const productGrid = document.getElementById('tabProductGrid');

        // Embed products data from PHP
        const allProducts = <?= json_encode($allTabProducts, JSON_UNESCAPED_UNICODE) ?>;

        // Function to update indicator position
        function updateIndicator(activeTab) {
            if (!activeTab) return;

            // Get the platter padding value from CSS
            const platterPadding = parseInt(getComputedStyle(tabNav).getPropertyValue('--tabnav-platter-padding')) || 6;

            // Get button dimensions and add platter padding to account for container padding
            const left = activeTab.offsetLeft + platterPadding;
            const width = activeTab.offsetWidth;

            // Set CSS variables - indicator covers full button area
            tabNav.style.setProperty('--tabnav-indicator-start', `${left}px`);
            tabNav.style.setProperty('--tabnav-indicator-width', `${width}px`);
        }

        // Function to filter and display products by category with fade effect
        function loadProducts(category) {
            const shelf = document.getElementById('tabProductsShelf');
            if (!shelf) return;

            // Remove show class to fade out current products
            shelf.classList.remove('show');

            // Wait for fade out, then swap products
            setTimeout(() => {
                let filteredProducts = [];

                if (category === 'all') {
                    filteredProducts = allProducts.slice(0, 12);
                } else if (category === 'featured') {
                    filteredProducts = allProducts.filter(p => p.featured == 1).slice(0, 12);
                } else {
                    // Filter by category_id
                    filteredProducts = allProducts.filter(p => p.category_id == category).slice(0, 12);
                }

                if (filteredProducts.length > 0) {
                    displayProducts(filteredProducts);
                } else {
                    productGrid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Không có sản phẩm trong danh mục này</p>
                    </div>
                `;
                }

                // Reset scroll position
                shelf.scrollLeft = 0;

                // Trigger fade in after content is loaded
                requestAnimationFrame(() => {
                    shelf.classList.add('show');
                });
            }, 150); // Match CSS transition duration (0.15s)
        }

        // Function to display products
        function displayProducts(products) {
            let html = '';
            products.forEach((product, index) => {
                // Determine display values based on variant data
                // Use variant prices if available and greater than 0, otherwise fall back to product prices - Convert to number
                const displayPrice = parseFloat((product.variant_price != null && product.variant_price > 0)
                    ? product.variant_price
                    : product.price);
                const displaySalePrice = (product.variant_sale_price != null && product.variant_sale_price > 0)
                    ? parseFloat(product.variant_sale_price)
                    : (product.sale_price != null && product.sale_price > 0 ? parseFloat(product.sale_price) : null);

                const hasDiscount = displaySalePrice && displaySalePrice < displayPrice;
                const price = hasDiscount ? displaySalePrice : displayPrice;
                const oldPrice = hasDiscount ? displayPrice : null;
                const discountPercent = hasDiscount ? Math.round(((oldPrice - price) / oldPrice) * 100) : 0;
                const savings = hasDiscount ? (oldPrice - price) : 0;

                // Determine display image: variant_image > first_gallery_image > product.image
                let displayImage = '';
                if (product.variant_image) {
                    displayImage = product.variant_image;
                } else if (product.first_gallery_image) {
                    displayImage = product.first_gallery_image;
                } else if (product.image) {
                    displayImage = product.image;
                } else {
                    displayImage = '/public/images/placeholder-product.svg';
                }

                // Determine display title
                let displayTitle = '';
                if (product.variant_title) {
                    displayTitle = product.variant_title;
                } else if (product.variant_name) {
                    displayTitle = product.name + ' ' + product.variant_name;
                } else {
                    displayTitle = product.name;
                }

                // Determine display slug
                const displaySlug = product.variant_slug || product.slug;

                html += `
                <div class="col">
                    <div class="card h-100 shadow-sm product-card">
                        <a href="/${displaySlug}" class="text-decoration-none">
                            <div class="position-relative">
                                <img src="${displayImage}"
                                     class="card-img-top"
                                     alt="${displayTitle}"
                                     loading="lazy"
                                     onerror="this.src='/public/images/placeholder-product.svg'">
                                ${hasDiscount ? `
                                    <span class="position-absolute top-0 end-0 m-2 badge bg-danger">
                                        -${discountPercent}%
                                    </span>
                                ` : ''}
                            </div>

                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title text-dark mb-3 fw-semibold" style="min-height: 40px; line-height: 1.4;">
                                    ${displayTitle}
                                </h6>

                                <div class="mt-auto">
                                    <div class="d-flex align-items-baseline gap-2 mb-2">
                                        <span class="h4 text-primary mb-0 fw-bold">${Number(price).toLocaleString('vi-VN')}đ</span>
                                        ${hasDiscount ? `
                                            <span class="text-muted text-decoration-line-through" style="font-size: 0.875rem;">
                                                ${Number(oldPrice).toLocaleString('vi-VN')}đ
                                            </span>
                                        ` : ''}
                                    </div>

                                    ${hasDiscount ? `
                                        <div class="text-success small">
                                            <i class="fas fa-tags me-1"></i>Tiết kiệm ${Number(savings).toLocaleString('vi-VN')}đ
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            `;
            });

            productGrid.innerHTML = html;
        }

        // Check if TabNav needs navigation buttons on mobile
        function checkTabNavOverflow() {
            const tabNavElement = document.getElementById('categoryTabNav');
            const tabNavItems = tabNavElement?.querySelector('.tabnav-items');
            const prevBtn = document.getElementById('tabProductsPrev');
            const nextBtn = document.getElementById('tabProductsNext');

            if (!tabNavElement || !tabNavItems || !prevBtn || !nextBtn) {
                return;
            }

            // Only check on mobile
            if (window.innerWidth > 768) {
                // On desktop, keep buttons visible for product scrolling
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
                return;
            }

            // Check if the items container is wider than the visible area
            const itemsWidth = tabNavItems.scrollWidth;
            const visibleWidth = tabNavElement.clientWidth;
            const isOverflowing = itemsWidth > visibleWidth;

            if (isOverflowing) {
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
                prevBtn.style.visibility = 'visible';
                nextBtn.style.visibility = 'visible';
                prevBtn.style.opacity = '1';
                nextBtn.style.opacity = '1';
            } else {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            }
        }

        // Initialize indicator on first active tab
        const activeTab = tabNav.querySelector('.tabnav-link.active');
        if (activeTab) {
            // Wait for DOM to fully render
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    updateIndicator(activeTab);
                    // Small delay to ensure layout is calculated
                    setTimeout(() => {
                        checkTabNavOverflow();
                    }, 100);
                });
            });
            // Load initial products with animation
            loadProducts(activeTab.dataset.category);
        }

        // Check overflow on window resize with debounce
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(checkTabNavOverflow, 100);
        });

        // Prevent manual scrolling on mobile TabNav
        const tabNavElement = document.getElementById('categoryTabNav');
        if (tabNavElement && window.innerWidth <= 768) {
            // Prevent mouse wheel scrolling
            tabNavElement.addEventListener('wheel', (e) => {
                e.preventDefault();
            }, { passive: false });

            // Prevent drag scrolling
            let isDown = false;
            let startX;
            let scrollLeft;

            tabNavElement.addEventListener('mousedown', (e) => {
                // Only prevent if not clicking a tab button
                if (!e.target.closest('.tabnav-link')) {
                    isDown = true;
                    startX = e.pageX - tabNavElement.offsetLeft;
                    scrollLeft = tabNavElement.scrollLeft;
                    e.preventDefault();
                }
            });

            tabNavElement.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
            });

            tabNavElement.addEventListener('mouseup', () => {
                isDown = false;
            });

            tabNavElement.addEventListener('mouseleave', () => {
                isDown = false;
            });

            // Prevent touch scrolling on mobile devices
            tabNavElement.addEventListener('touchstart', (e) => {
                if (!e.target.closest('.tabnav-link')) {
                    e.preventDefault();
                }
            }, { passive: false });
        }

        // Tab click handlers
        tabLinks.forEach(tab => {
            tab.addEventListener('click', function () {
                // Nếu tab đang active thì không làm gì cả
                if (this.classList.contains('active')) {
                    return;
                }

                // Remove active class from all tabs
                tabLinks.forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });

                // Add active class to clicked tab
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');

                // Update indicator
                updateIndicator(this);

                // Load products for this category
                loadProducts(this.dataset.category);
            });
        });

        // Update indicator on window resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const activeTab = tabNav.querySelector('.tabnav-link.active');
                if (activeTab) updateIndicator(activeTab);
            }, 150);
        });

    });

    // Tab Products Shelf Scroll Function
    function scrollTabProducts(direction) {
        // On mobile, scroll the TabNav instead of products
        if (window.innerWidth <= 768) {
            const tabNav = document.getElementById('categoryTabNav');
            if (!tabNav) return;

            const scrollAmount = 150; // Scroll amount for tabs
            const currentScroll = tabNav.scrollLeft;
            let targetScroll;

            if (direction === 'next') {
                targetScroll = currentScroll + scrollAmount;
            } else {
                targetScroll = currentScroll - scrollAmount;
            }

            tabNav.scrollTo({
                left: targetScroll,
                behavior: 'smooth'
            });
        } else {
            // Desktop: scroll products
            const shelf = document.getElementById('tabProductsShelf');
            if (!shelf) return;

            // Scroll 4 cards at a time: (4 cards * 284px) + (3 gaps * 12px) = 1172px
            const scrollAmount = 1172;
            const currentScroll = shelf.scrollLeft;

            let targetScroll;
            if (direction === 'next') {
                targetScroll = currentScroll + scrollAmount;
            } else {
                targetScroll = Math.max(0, currentScroll - scrollAmount);
            }

            shelf.scrollTo({
                left: targetScroll,
                behavior: 'smooth'
            });
        }
    }

</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>