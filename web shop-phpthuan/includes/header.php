<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
initSession();
$currentUser = getCurrentUser();

// Tracking Visitor (Realtime View & Online Users)
require_once __DIR__ . '/visitor_tracking.php';

// Maintenance Mode Check
if (getSetting('maintenance_mode') == '1' && !isAdmin()) {
    // Nếu request đến từ API hoặc AJAX -> JSON response
    if (
        (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
        strpos($_SERVER['REQUEST_URI'], '/api/') !== false
    ) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'System is under maintenance']);
        exit;
    }

    // Nếu không phải đang ở trang maintenance.php thì include và exit
    if (basename($_SERVER['PHP_SELF']) !== 'maintenance.php') {
        include __DIR__ . '/../maintenance.php';
        exit;
    }
}

// Tính tổng số lượng sản phẩm trong giỏ hàng (bao gồm quantity)
// Dùng tên biến khác để tránh conflict với checkout.php
$headerCartItems = getCart();
$cartCount = 0;
foreach ($headerCartItems as $quantity) {
    $cartCount += $quantity;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-3ZDWMNDKGT"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-3ZDWMNDKGT');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Preconnect to CDNs for faster loading -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

    <!-- Favicon and Icons -->
    <link rel="icon" type="image/x-icon" href="/public/images/logo.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/public/images/logo.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/images/logo.ico">

    <?php
    // SEO Settings from Admin
    $seoTitle = getSetting('seo_title', 'Veyrix Shop: Mua tài khoản Premium uy tín - YouTube, Netflix ...');
    $seoDescription = getSetting('seo_description', 'Veyrix Shop. Shop bán tài khoản premium uy tín hàng đầu Việt Nam. Cung cấp YouTube Premium, Netflix, Spotify, VPN, Canva với giá tốt nhất. Bảo hành trọn gói.');
    $seoKeywords = getSetting('seo_keywords', 'mua tài khoản premium, YouTube Premium, Netflix, Spotify, VPN, Canva Pro');
    $robotsMeta = getSetting('robots_meta', 'index, follow');
    $googleVerification = getSetting('google_verification', '');

    // Page-specific title & description
    $isHomepage = !isset($pageTitle) || $pageTitle === 'Trang chủ';
    if ($isHomepage) {
        $finalTitle = $seoTitle;
        $metaDescription = $seoDescription;
    } else {
        $finalTitle = isset($pageTitle) ? e($pageTitle) . ' - ' . e(getSetting('site_name', 'Veyrix')) : $seoTitle;
        $metaDescription = isset($pageDescription) ? $pageDescription : $seoDescription;
    }
    ?>
    <title><?= $finalTitle ?></title>

    <!-- Mobile Meta -->
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= e(getSetting('site_name', 'Veyrix Shop')) ?>">
    <meta name="format-detection" content="telephone=no">

    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="keywords" content="<?= e($seoKeywords) ?>">
    <meta name="robots" content="<?= e($robotsMeta) ?>">
    <meta name="author" content="<?= e(getSetting('site_name', 'Veyrix Shop')) ?>">
    <?php if (!empty($googleVerification)): ?>
        <meta name="google-site-verification" content="<?= e($googleVerification) ?>">
    <?php endif; ?>
    <?php
    // Open Graph settings
    $ogImage = getSetting('og_image', '');
    $ogSiteName = getSetting('og_site_name', getSetting('site_name', 'Veyrix Shop'));
    $ogType = getSetting('og_type', 'website');
    $canonicalUrl = getSetting('canonical_url', 'https://veyrix.pro');
    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    if (empty($ogImage)) {
        $ogImage = $canonicalUrl . '/public/images/og-image.jpg';
    }
    ?>

    <!-- Canonical URL -->
    <link rel="canonical" href="<?= $isHomepage ? e($canonicalUrl) : e($currentUrl) ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?= e($ogType) ?>">
    <meta property="og:url" content="<?= e($currentUrl) ?>">
    <meta property="og:title" content="<?= e($finalTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="<?= e($ogSiteName) ?>">
    <meta property="og:locale" content="vi_VN">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($finalTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">

    <?php if ($isHomepage): ?>
        <!-- Structured Data for Homepage -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "<?= e($ogSiteName) ?>",
            "url": "<?= e($canonicalUrl) ?>",
            "description": "<?= e($metaDescription) ?>",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "<?= e($canonicalUrl) ?>/products?q={search_term_string}",
                "query-input": "required name=search_term_string"
            }
        }
        </script>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "<?= e($ogSiteName) ?>",
            "url": "<?= e($canonicalUrl) ?>",
            "logo": "<?= e($canonicalUrl) ?>/public/images/logo.png",
            "contactPoint": {
                "@type": "ContactPoint",
                "telephone": "<?= e(getSetting('contact_phone', '')) ?>",
                "contactType": "customer service",
                "areaServed": "VN",
                "availableLanguage": "Vietnamese"
            },
            "sameAs": [
                <?php if ($fb = getSetting('social_facebook')): ?>"<?= e($fb) ?>"<?php endif; ?>
            ]
        }
        </script>

        <?php
        // Lấy các danh mục và sản phẩm nổi bật cho SiteNavigationElement
        $navCategories = [];
        $navProducts = [];
        try {
            // Lấy top 4 danh mục active
            $navCategories = db()->query(
                "SELECT name, slug FROM categories WHERE status = 'active' ORDER BY sort_order ASC, id ASC LIMIT 4"
            )->fetchAll();

            // Lấy top 4 sản phẩm nổi bật
            $navProducts = db()->query(
                "SELECT p.name, COALESCE(v.slug, p.slug) as slug, v.variant_title
             FROM products p
             LEFT JOIN product_variants v ON p.id = v.product_id AND v.is_main = 1
             WHERE p.status = 'active' AND p.featured = 1
             ORDER BY p.created_at DESC
             LIMIT 4"
            )->fetchAll();
        } catch (Exception $e) {
            // Ignore errors
        }
        ?>

        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@graph": [
                {
                    "@type": "SiteNavigationElement",
                    "name": "Trang chủ",
                    "url": "<?= e($canonicalUrl) ?>"
                },
                {
                    "@type": "SiteNavigationElement",
                    "name": "Sản phẩm",
                    "url": "<?= e($canonicalUrl) ?>/products"
                }
                <?php foreach ($navCategories as $cat): ?>
                    ,{
                        "@type": "SiteNavigationElement",
                        "name": "<?= e($cat['name']) ?>",
                        "url": "<?= e($canonicalUrl) ?>/products/<?= e($cat['slug']) ?>"
                    }
                <?php endforeach; ?>
                <?php foreach ($navProducts as $prod): ?>
                    ,{
                        "@type": "SiteNavigationElement",
                        "name": "<?= e($prod['variant_title'] ?: $prod['name']) ?>",
                        "url": "<?= e($canonicalUrl) ?>/<?= e($prod['slug']) ?>"
                    }
                <?php endforeach; ?>
            ]
        }
        </script>

        <?php if (!empty($navProducts)): ?>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "ItemList",
                "name": "Sản phẩm nổi bật",
                "itemListElement": [
                    <?php $position = 1; ?>
                    <?php foreach ($navProducts as $index => $prod): ?>
                        <?= $index > 0 ? ',' : '' ?>{
                            "@type": "ListItem",
                            "position": <?= $position++ ?>,
                            "url": "<?= e($canonicalUrl) ?>/<?= e($prod['slug']) ?>",
                            "name": "<?= e($prod['variant_title'] ?: $prod['name']) ?>"
                        }
                    <?php endforeach; ?>
                ]
            }
            </script>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Bootstrap CSS -->
    <!-- Preconnect to CDNs for faster loading -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome - Non-blocking load with font-display swap -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </noscript>
    <style>
        /* Font-display swap for Font Awesome */
        @font-face {
            font-family: 'Font Awesome 6 Free';
            font-display: swap;
        }

        /* Prevent CLS - Set aspect ratio for images (exclude scroll shelf) */
        .card-img-top:not(.tab-products-shelf .card-img-top) {
            aspect-ratio: 1070 / 500;
            object-fit: cover;
            background-color: #f0f0f0;
        }

        .category-card-image {
            aspect-ratio: 16 / 9;
            object-fit: cover;
            background-color: #f0f0f0;
        }

        .brand-logo {
            aspect-ratio: 2 / 1;
            object-fit: contain;
            background-color: #fff;
        }

        /* Mobile optimization - Reduce image quality */
        @media (max-width: 768px) {
            img {
                image-rendering: auto;
            }
        }

        /* Smart Sticky Header */
        body {
            padding-top: 61px;
            /* Match navbar height */
        }

        .navbar.fixed-top {
            transition: transform 0.3s ease-in-out;
            will-change: transform;
        }

        .navbar-hidden {
            transform: translateY(-100%);
        }

        .navbar-visible {
            transform: translateY(0);
        }
    </style>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/public/css/style.css?v=9">
</head>

<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top"
        style="background: linear-gradient(135deg, #667eea 0%, #7387df 100%); box-shadow: 0 2px 10px rgba(0,0,0,0.1); height: 61px;">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand fw-bold d-flex align-items-center" href="/"
                style="font-size: 1.4rem; letter-spacing: 0.5px; gap: 8px;">
                <span style="line-height: 1;"><?= e(getSetting('site_name', 'Veyrix')) ?></span>
            </a>

            <!-- Mobile Toggler - Animated -->
            <button class="navbar-toggler border-0 p-2" type="button" id="menuToggle">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor"
                    class="hamburger-icon">
                    <path class="line line-1" d="M3 6h18" stroke-width="2" stroke-linecap="round" />
                    <path class="line line-2" d="M3 12h18" stroke-width="2" stroke-linecap="round" />
                    <path class="line line-3" d="M3 18h18" stroke-width="2" stroke-linecap="round" />
                </svg>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Search Bar -->
                <div class="flex-grow-1 mx-3 position-relative" style="max-width: 500px;">
                    <div class="search-bar-wrapper" style="position: relative;">
                        <div class="input-group search-bar">
                            <span class="input-group-text border-0 bg-white ps-3">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-0" id="searchInput"
                                placeholder="Bạn đang tìm kiếm gì..." autocomplete="off">
                        </div>

                        <!-- Search Results Dropdown -->
                        <div class="search-results-dropdown" id="searchResults" style="display: none;">
                            <div class="search-loading" style="display: none;">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Đang tìm...</span>
                                    </div>
                                    <p class="mb-0 mt-2 small text-muted">Đang tìm kiếm...</p>
                                </div>
                            </div>
                            <div class="search-content"></div>
                        </div>
                    </div>
                </div>

                <ul class="navbar-nav align-items-center ms-auto">
                    <!-- Wallet Balance (for logged in users) -->
                    <?php if ($currentUser): ?>
                        <?php $walletBalance = getUserWalletBalance($currentUser['id']); ?>
                        <li class="nav-item me-2">
                            <a href="/wallet" class="btn btn-outline-light btn-sm d-flex align-items-center"
                                style="border-radius: 20px; padding: 6px 16px;">
                                <i class="fas fa-wallet me-2"></i>
                                <span class="fw-bold"><?= formatMoney($walletBalance) ?></span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Cart Dropdown -->
                    <li class="nav-item dropdown">
                        <?php
                        $cartItemsWithDetails = [];
                        $totalAmount = 0;

                        // Lấy thông tin chi tiết sản phẩm từ database bao gồm cả variant
                        try {
                            $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
                            $sessionId = session_id();

                            if ($userId) {
                                $stmt = db()->query(
                                    "SELECT c.*, p.name, p.price, p.sale_price, p.image, p.slug,
                                            v.id as variant_id, v.name as variant_name, v.price as variant_price,
                                            v.sale_price as variant_sale_price, v.variant_title, v.variant_image, v.slug as variant_slug,
                                            (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
                                     FROM cart c
                                     JOIN products p ON c.product_id = p.id
                                     LEFT JOIN product_variants v ON c.variant_id = v.id
                                     WHERE c.user_id = ?",
                                    [$userId]
                                );
                            } else {
                                $stmt = db()->query(
                                    "SELECT c.*, p.name, p.price, p.sale_price, p.image, p.slug,
                                            v.id as variant_id, v.name as variant_name, v.price as variant_price,
                                            v.sale_price as variant_sale_price, v.variant_title, v.variant_image, v.slug as variant_slug,
                                            (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
                                     FROM cart c
                                     JOIN products p ON c.product_id = p.id
                                     LEFT JOIN product_variants v ON c.variant_id = v.id
                                     WHERE c.session_id = ? AND c.user_id IS NULL",
                                    [$sessionId]
                                );
                            }

                            while ($row = $stmt->fetch()) {
                                // Xác định giá và tên hiển thị
                                if ($row['variant_id']) {
                                    $price = $row['variant_sale_price'] ?? $row['variant_price'];
                                    $displayName = !empty($row['variant_title']) ? $row['variant_title'] : ($row['name'] . ' - ' . $row['variant_name']);
                                } else {
                                    $price = $row['sale_price'] ?? $row['price'];
                                    $displayName = $row['name'];
                                }

                                // Xác định ảnh hiển thị (ưu tiên: variant_image > first_gallery_image > product_image)
                                $image = '';
                                if (!empty($row['variant_image'])) {
                                    $image = $row['variant_image'];
                                } elseif (!empty($row['first_gallery_image'])) {
                                    $image = $row['first_gallery_image'];
                                } elseif (!empty($row['image'])) {
                                    $image = $row['image'];
                                }

                                $quantity = $row['quantity'];

                                // Xác định URL sản phẩm (ưu tiên variant_slug > slug)
                                $productUrl = !empty($row['variant_slug']) ? '/' . $row['variant_slug'] : '/' . $row['slug'];

                                $cartItemsWithDetails[] = [
                                    'id' => $row['product_id'],
                                    'variant_id' => $row['variant_id'],
                                    'name' => $displayName,
                                    'price' => $price,
                                    'sale_price' => null,
                                    'image' => $image,
                                    'quantity' => $quantity,
                                    'product_url' => $productUrl
                                ];

                                $totalAmount += $price * $quantity;
                            }
                        } catch (Exception $e) {
                            error_log("Header cart error: " . $e->getMessage());
                        }

                        // Tính tổng số lượng sản phẩm (bao gồm cả quantity)
                        $cartCount = 0;
                        foreach ($cartItemsWithDetails as $item) {
                            $cartCount += $item['quantity'];
                        }

                        $cartItemsPreview = array_slice($cartItemsWithDetails, 0, 5); // Lấy tối đa 5 items
                        ?>
                        <a class="nav-link position-relative px-2" href="#" role="button" data-bs-toggle="dropdown"
                            data-bs-auto-close="outside" aria-expanded="false" id="cartDropdown">
                            <div class="icon-circle">
                                <i class="fas fa-shopping-cart"></i>
                                <?php if ($cartCount > 0): ?>
                                    <span class="icon-badge"><?= $cartCount > 9 ? '9+' : $cartCount ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end cart-dropdown shadow-lg"
                            style="min-width: 400px; max-height: 600px; overflow-y: auto; border-radius: 12px; border: none;">
                            <!-- Cart Header -->
                            <li class="dropdown-header cart-header-modern">
                                <h3 class="cart-title"><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</h3>
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="cart-count-badge"><?= $cartCount ?> sản phẩm</span>
                                </div>
                            </li>

                            <?php if (empty($cartItemsWithDetails)): ?>
                                <li class="text-center text-muted py-5">
                                    <i class="fas fa-shopping-cart fa-3x mb-3 opacity-50"></i>
                                    <p class="mb-0">Giỏ hàng trống</p>
                                    <a href="/products" class="pill-button pill-button-blue mt-3">
                                        <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                                    </a>
                                </li>
                            <?php else: ?>
                                <?php foreach ($cartItemsPreview as $item): ?>
                                    <li class="px-3 py-3 border-bottom cart-item" data-cart-item-id="<?= e($item['id']) ?>">
                                        <div class="d-flex align-items-start">
                                            <img src="<?= e($item['image'] ?? '/public/images/placeholder-product.svg') ?>"
                                                alt="<?= e($item['name']) ?>" class="me-3"
                                                style="width: 128px; height: 60px; object-fit: cover; border-radius: 6px;"
                                                onerror="this.src='/public/images/placeholder-product.svg';">
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <h6 class="mb-0 small fw-bold flex-grow-1 me-2">
                                                        <a href="<?= e($item['product_url']) ?>"
                                                            class="text-decoration-none text-dark cart-item-link">
                                                            <?= e($item['name']) ?>
                                                        </a>
                                                    </h6>
                                                    <button type="button" class="btn-close btn-close-sm remove-cart-item"
                                                        data-product-id="<?= e($item['id']) ?>"
                                                        data-variant-id="<?= e($item['variant_id'] ?? '') ?>"
                                                        style="font-size: 10px; flex-shrink: 0;"
                                                        title="Xóa khỏi giỏ hàng"></button>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-primary fw-bold small">
                                                        <?= formatMoney($item['sale_price'] ?? $item['price']) ?>
                                                    </span>
                                                    <span class="text-muted small">x<?= $item['quantity'] ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>

                                <?php if (count($cartItemsWithDetails) > 5): ?>
                                    <li class="px-3 py-2 text-center bg-light">
                                        <small class="text-muted">
                                            Còn <?= count($cartItemsWithDetails) - 5 ?> sản phẩm khác...
                                        </small>
                                    </li>
                                <?php endif; ?>

                                <!-- Total -->
                                <li class="px-3 py-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-bold">Tổng tạm tính:</span>
                                        <span class="text-primary fw-bold fs-5"><?= formatMoney($totalAmount) ?></span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="/cart" class="pill-button pill-button-blue flex-fill text-center"
                                            style="padding: 10px 16px; font-size: 13px;">
                                            <i class="fas fa-eye"></i> Xem giỏ hàng
                                        </a>
                                        <a href="/checkout" class="pill-button pill-button-green flex-fill text-center"
                                            style="padding: 10px 16px; font-size: 13px;">
                                            <i class="fas fa-credit-card"></i> Thanh toán
                                        </a>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <?php if ($currentUser): ?>
                        <!-- Notification Bell -->
                        <li class="nav-item dropdown">
                            <?php
                            $unreadCount = getUnreadNotificationCount($currentUser['id']);
                            $notifications = getNotifications($currentUser['id'], 5);
                            ?>
                            <a class="nav-link position-relative px-2" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false" id="notificationDropdown">
                                <div class="icon-circle">
                                    <i class="fas fa-bell"></i>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="icon-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown shadow-lg"
                                style="min-width: 350px; max-height: 650px; overflow-y: auto; border-radius: 12px; border: none;">
                                <!-- Notification Header -->
                                <li class="dropdown-header notification-header-modern">
                                    <h3 class="notification-title"><i class="fas fa-bell"></i> Thông báo</h3>
                                    <div class="d-flex gap-2 align-items-center">
                                        <?php if ($unreadCount > 0): ?>
                                            <span class="notification-count-badge"><?= $unreadCount ?> mới</span>
                                            <a href="#" class="notification-pill-button"
                                                onclick="markAllAsRead(); return false;">
                                                <i class="fas fa-check"></i> Đánh dấu đã đọc
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </li>

                                <style>
                                    .notification-pill-badge {
                                        display: inline-block;
                                        background: white;
                                        color: #667eea;
                                        padding: 4px 12px;
                                        border-radius: 1000px;
                                        font-size: 12px;
                                        font-weight: 600;
                                        letter-spacing: -0.01em;
                                        margin-right: 6px;
                                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
                                    }

                                    .notification-pill-button {
                                        display: inline-block;
                                        background: rgba(255, 255, 255, 0.9);
                                        color: #0071e3;
                                        padding: 6px 14px;
                                        border-radius: 1000px;
                                        font-size: 12px;
                                        font-weight: 600;
                                        letter-spacing: -0.01em;
                                        text-decoration: none;
                                        transition: all 0.14s ease-out;
                                        border: none;
                                    }

                                    .notification-pill-button:hover {
                                        background: rgba(255, 255, 255, 1);
                                        color: #0071e3;
                                        text-decoration: none;
                                    }

                                    .notification-pill-button i {
                                        font-size: 10px;
                                        margin-right: 4px;
                                    }
                                </style>

                                <?php if (empty($notifications)): ?>
                                    <li class="text-center text-muted py-5">
                                        <i class="fas fa-bell-slash fa-3x mb-3 opacity-50"></i>
                                        <p class="mb-0">Chưa có thông báo</p>
                                    </li>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <li class="notification-item">
                                            <a class="dropdown-item <?= $notif['is_read'] ? '' : 'bg-light' ?> px-3 py-3"
                                                href="<?= $notif['link'] ? e($notif['link']) : '#' ?>"
                                                onclick="markAsRead(<?= $notif['id'] ?>)"
                                                style="transition: background-color 0.2s ease;">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0 me-3">
                                                        <?php
                                                        $iconClass = [
                                                            'success' => 'fa-check-circle text-success',
                                                            'info' => 'fa-info-circle text-info',
                                                            'warning' => 'fa-exclamation-triangle text-warning',
                                                            'error' => 'fa-times-circle text-danger'
                                                        ];
                                                        $icon = $iconClass[$notif['type']] ?? 'fa-bell text-primary';
                                                        ?>
                                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                                            style="width: 40px; height: 40px;">
                                                            <i class="fas <?= $icon ?> fs-5"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="<?= $notif['is_read'] ? '' : 'fw-bold' ?> mb-1"
                                                            style="font-size: 0.9rem;">
                                                            <?= e($notif['title']) ?>
                                                        </div>
                                                        <?php if (!empty($notif['message'])): ?>
                                                            <div class="text-muted small mb-1"><?= e($notif['message']) ?></div>
                                                        <?php endif; ?>
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            <i class="fas fa-clock"></i> <?= formatDate($notif['created_at']) ?>
                                                        </div>
                                                    </div>
                                                    <?php if (!$notif['is_read']): ?>
                                                        <div class="flex-shrink-0 ms-2">
                                                            <span class="badge bg-primary rounded-pill"
                                                                style="width: 8px; height: 8px; padding: 0;"></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        </li>
                                        <?php if ($notif !== end($notifications)): ?>
                                            <li>
                                                <hr class="dropdown-divider m-0">
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <!-- View All Button -->
                                    <li class="border-top">
                                        <a href="/account?tab=notifications"
                                            class="dropdown-item text-center text-primary fw-bold py-3"
                                            style="font-size: 0.9rem;">
                                            <i class="fas fa-eye"></i> Xem tất cả thông báo
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if ($currentUser): ?>
                        <!-- User Dropdown Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative px-2" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <div class="icon-circle">
                                    <i class="far fa-user-circle"></i>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end user-dropdown shadow-lg">
                                <!-- User Header -->
                                <li class="user-dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #7387df 100%);">
                                                <i class="far fa-user" style="color: #fff; font-size: 18px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="fw-bold" style="color: #030303;"><?= e($currentUser['username']) ?>
                                            </div>
                                            <div class="small" style="color: #606060;"><?= e($currentUser['email']) ?></div>
                                        </div>
                                    </div>
                                </li>

                                <!-- Menu Items -->
                                <li class="pt-2">
                                    <a class="dropdown-item d-flex align-items-center" href="/account">
                                        <i class="far fa-id-card me-3" style="color: #4285f4;"></i>
                                        <span>Thông tin tài khoản</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="/orders">
                                        <i class="far fa-clipboard me-3" style="color: #ff9800;"></i>
                                        <span>Đơn hàng đã mua</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="/wishlist">
                                        <i class="far fa-heart me-3" style="color: #e91e63;"></i>
                                        <span>Gian hàng yêu thích</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="/wallet">
                                        <i class="far fa-credit-card me-3" style="color: #00c853;"></i>
                                        <span>Lịch sử thanh toán</span>
                                    </a>
                                </li>
                                <?php if ($currentUser['role'] === 'reseller' || isAdmin()): ?>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="/reseller">
                                            <i class="far fa-handshake me-3" style="color: #ffc107;"></i>
                                            <span>Reseller</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="/account#change-password">
                                        <i class="fas fa-lock me-3" style="color: #9c27b0;"></i>
                                        <span>Đổi mật khẩu</span>
                                    </a>
                                </li>

                                <!-- Admin Section -->
                                <?php if (isAdmin()): ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="/admin/">
                                            <i class="fas fa-tools me-3" style="color: #607d8b;"></i>
                                            <span>Quản lý cửa hàng</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- Logout -->
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li class="pb-2">
                                    <a class="dropdown-item d-flex align-items-center" href="/logout">
                                        <i class="fas fa-power-off me-3" style="color: #dc3545;"></i>
                                        <span style="color: #dc3545;">Đăng xuất</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link px-2" href="#" data-bs-toggle="modal" data-bs-target="#authModal"
                                data-tab="login">
                                <div class="auth-button auth-single-btn">
                                    <i class="far fa-user-circle"></i>
                                    <span>Đăng nhập</span>
                                </div>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Mobile Cart Icon (Right) - Only visible on mobile -->
            <a class="nav-link position-relative px-2 d-lg-none mobile-cart-icon" href="/cart">
                <div class="icon-circle">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="icon-badge"><?= $cartCount > 9 ? '9+' : $cartCount ?></span>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    </nav>

    <!-- Mobile Sidebar Menu -->
    <div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>
    <div class="mobile-sidebar" id="mobileSidebar">
        <div class="mobile-sidebar-header">
            <?php if (!$currentUser): ?>
                <a href="#" class="mobile-sidebar-auth-btn" data-bs-toggle="modal" data-bs-target="#authModal"
                    data-tab="login">
                    <i class="fas fa-user"></i>
                    <span>Đăng nhập/Đăng ký</span>
                </a>
                <button type="button" class="mobile-sidebar-close border-0 bg-transparent p-2" id="mobileSidebarClose">
                    <i class="fas fa-times" style="font-size: 20px; color: #6c757d;"></i>
                </button>
            <?php endif; ?>
        </div>
        <div class="mobile-sidebar-content">
            <?php if ($currentUser): ?>
                <!-- Logged in user info -->
                <div class="mobile-sidebar-user">
                    <div class="d-flex align-items-center" style="padding: 16px;">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                                style="width: 48px; height: 48px;">
                                <i class="fas fa-user text-white fs-5"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-bold"><?= e($currentUser['username']) ?></div>
                            <div class="small text-muted"><?= e($currentUser['email']) ?></div>
                        </div>
                        <button type="button" class="mobile-sidebar-close border-0 bg-transparent p-2"
                            id="mobileSidebarClose">
                            <i class="fas fa-times" style="font-size: 20px; color: #6c757d;"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mobile-sidebar-main-content">
                <!-- Main Navigation -->
                <div class="mobile-sidebar-nav">
                    <a href="/" class="mobile-sidebar-link">
                        <i class="fas fa-home"></i>
                        <span>Trang chủ</span>
                    </a>

                    <?php if ($currentUser): ?>
                        <a href="/wallet" class="mobile-sidebar-link">
                            <i class="fas fa-wallet"></i>
                            <span>Ví của tôi</span>
                            <span
                                class="ms-auto text-primary fw-bold"><?= formatMoney(getUserWalletBalance($currentUser['id'])) ?></span>
                        </a>
                        <a href="/account" class="mobile-sidebar-link">
                            <i class="fas fa-user-circle"></i>
                            <span>Thông tin tài khoản</span>
                        </a>
                        <a href="/orders" class="mobile-sidebar-link">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Đơn hàng đã mua</span>
                        </a>
                        <a href="/wishlist" class="mobile-sidebar-link">
                            <i class="fas fa-heart"></i>
                            <span>Gian hàng yêu thích</span>
                        </a>
                        <?php if ($currentUser['role'] === 'reseller' || isAdmin()): ?>
                            <a href="/reseller" class="mobile-sidebar-link">
                                <i class="fas fa-handshake"></i>
                                <span>Reseller</span>
                            </a>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                            <a href="/admin/" class="mobile-sidebar-link">
                                <i class="fas fa-cog"></i>
                                <span>Quản lý cửa hàng</span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="mobile-sidebar-divider"></div>

                <!-- Categories -->
                <div class="mobile-sidebar-section-title">Danh mục sản phẩm</div>
                <div class="mobile-sidebar-nav">
                    <?php
                    $mobileCategories = db()->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name LIMIT 6")->fetchAll();
                    $mobileCategoryIcons = [
                        'Netflix' => 'fa-film',
                        'Spotify' => 'fa-music',
                        'YouTube' => 'fa-youtube',
                        'Office' => 'fa-file-word',
                        'VPN' => 'fa-shield-alt',
                        'Game' => 'fa-gamepad',
                        'Design' => 'fa-palette'
                    ];
                    foreach ($mobileCategories as $cat):
                        $icon = 'fa-folder';
                        foreach ($mobileCategoryIcons as $key => $iconClass) {
                            if (stripos($cat['name'], $key) !== false) {
                                $icon = $iconClass;
                                break;
                            }
                        }
                        ?>
                        <a href="/products?category=<?= e($cat['slug']) ?>" class="mobile-sidebar-link">
                            <i class="fas <?= $icon ?>"></i>
                            <span><?= e($cat['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($currentUser): ?>
                <!-- Logout button at bottom -->
                <div class="mobile-sidebar-logout">
                    <div class="mobile-sidebar-divider"></div>
                    <div class="mobile-sidebar-nav">
                        <a href="/logout" class="mobile-sidebar-link mobile-sidebar-logout-btn text-danger">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Đăng xuất</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Custom Dropdown Styles -->
    <style>
        /* Mobile Navbar Layout */
        @media (max-width: 991px) {
            .navbar {
                padding: 8px 0;
            }

            .navbar>.container {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                padding-left: 8px;
                padding-right: 8px;
                gap: 0;
            }

            /* Toggler button - Left (order 0) */
            .navbar-toggler {
                order: 0;
                margin-right: 6px;
                padding: 4px 8px;
                flex: 0 0 auto;
                width: auto;
            }

            /* Hamburger icon animation */
            .hamburger-icon .line {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                transform-origin: center;
            }

            /* Active state - transform to X */
            .navbar-toggler.active .line-1 {
                transform: translateY(6px) rotate(45deg);
            }

            .navbar-toggler.active .line-2 {
                opacity: 0;
            }

            .navbar-toggler.active .line-3 {
                transform: translateY(-6px) rotate(-45deg);
            }

            /* Logo - Hidden on mobile */
            .navbar-brand {
                display: none !important;
            }

            /* Navbar collapse - Center (order 1) */
            .navbar-collapse {
                order: 1;
                flex: 1 1 auto;
                margin: 0 !important;
                padding: 0 !important;
                min-width: 0;
                max-width: 100%;
            }

            /* Search wrapper */
            .navbar-collapse>div:first-child {
                margin: 0 6px !important;
                max-width: 100% !important;
                width: 100%;
            }

            .search-bar-wrapper {
                width: 100%;
            }

            /* Hide navbar-nav on first row (desktop cart/notification/user) */
            .navbar-collapse>ul.navbar-nav {
                display: none !important;
            }

            /* Mobile cart icon - Right (order 2) */
            .mobile-cart-icon {
                order: 2;
                flex: 0 0 auto;
                padding: 0 2px !important;
                margin-left: 4px;
            }

            /* Collapsed menu - Full width (order 3) */
            .navbar-collapse.collapse:not(.show) {
                display: flex !important;
                flex: 1 1 auto;
            }

            .navbar-collapse.collapsing,
            .navbar-collapse.show {
                flex: 0 0 100%;
                order: 3;
                margin-top: 10px !important;
            }

            /* Search bar styling */
            .search-bar {
                border-radius: 25px !important;
                overflow: hidden;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            }

            .search-bar input {
                font-size: 14px !important;
                padding: 8px 12px 8px 8px !important;
                border-radius: 0 25px 25px 0 !important;
            }

            .search-bar .input-group-text {
                padding: 8px 4px 8px 12px !important;
                border-radius: 25px 0 0 25px !important;
            }

            .search-bar .input-group-text i {
                font-size: 14px;
            }

            .search-bar .dropdown {
                display: none !important;
            }

            /* Cart icon circle */
            .icon-circle {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.15);
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
            }

            .icon-circle i {
                font-size: 18px;
                color: white;
            }

            .icon-badge {
                position: absolute;
                top: -4px;
                right: -4px;
                background: #ff4757;
                color: white;
                border-radius: 10px;
                padding: 2px 6px;
                font-size: 10px;
                font-weight: 700;
                min-width: 18px;
            }

            /* Collapsed menu content */
            #navbarNav.show>ul,
            #navbarNav.collapsing>ul {
                background: rgba(255, 255, 255, 0.1);
                border-radius: 10px;
                padding: 10px;
                flex-direction: column !important;
                display: flex !important;
            }

            #navbarNav.show .nav-link,
            #navbarNav.collapsing .nav-link {
                color: white !important;
                padding: 10px 15px !important;
                border-radius: 6px;
                margin: 2px 0;
            }

            #navbarNav.show .nav-link:hover,
            #navbarNav.collapsing .nav-link:hover {
                background: rgba(255, 255, 255, 0.15);
            }
        }

        /* Mobile Sidebar Menu */
        .mobile-sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .mobile-sidebar-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        .mobile-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: auto;
            min-width: 260px;
            max-width: 80%;
            background: white;
            z-index: 1050;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .mobile-sidebar.show {
            transform: translateX(0);
        }

        .mobile-sidebar-header {
            padding: 16px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e9ecef;
            gap: 12px;
        }

        .mobile-sidebar-auth-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: linear-gradient(135deg, #667eea 0%, #7387df 100%);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .mobile-sidebar-auth-btn:hover {
            opacity: 0.9;
            color: white;
        }

        .mobile-sidebar-auth-btn i {
            font-size: 14px;
            flex-shrink: 0;
        }

        .mobile-sidebar-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            padding: 8px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .mobile-sidebar-close:hover {
            background: #f8f9fa;
            color: #495057;
        }

        .mobile-sidebar-content {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 100%;
        }

        .mobile-sidebar-main-content {
            flex: 1;
            overflow-y: auto;
        }

        .mobile-sidebar-logout {
            margin-top: auto;
            padding-bottom: 20px;
        }

        .mobile-sidebar-logout-btn {
            font-size: 17px !important;
            font-weight: 600 !important;
            padding: 16px 16px !important;
        }

        .mobile-sidebar-logout-btn i {
            font-size: 18px !important;
        }

        .mobile-sidebar-user {
            border-bottom: 1px solid #e9ecef;
        }

        .mobile-sidebar-user .fw-bold,
        .mobile-sidebar-user .small {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mobile-sidebar-link {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            color: #495057;
            text-decoration: none;
            transition: background-color 0.2s ease;
            font-size: 15px;
            white-space: nowrap;
        }

        .mobile-sidebar-link:hover {
            background: #f8f9fa;
            color: #495057;
        }

        .mobile-sidebar-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 16px;
            text-align: center;
            color: #667eea;
            flex-shrink: 0;
        }

        .mobile-sidebar-link.text-danger i {
            color: #dc3545;
        }

        .mobile-sidebar-link span {
            flex-shrink: 0;
        }

        .mobile-sidebar-divider {
            height: 1px;
            background: #e9ecef;
            margin: 8px 0;
        }

        .mobile-sidebar-section-title {
            padding: 16px 16px 8px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .mobile-sidebar-nav {
            display: flex;
            flex-direction: column;
        }

        /* Hide mobile sidebar on desktop */
        @media (min-width: 992px) {

            .mobile-sidebar,
            .mobile-sidebar-backdrop {
                display: none;
            }
        }

        /* Smaller max-width for very small screens */
        @media (max-width: 375px) {
            .mobile-sidebar {
                max-width: 85%;
            }
        }

        /* Search Bar Styles */
        .search-bar-wrapper {
            position: relative;
        }

        .search-bar {
            border-radius: 25px;
            overflow: visible;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
            background: white;
        }

        .search-bar:focus-within {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .search-bar .input-group-text {
            padding: 0 8px;
        }

        .search-bar input {
            background: white !important;
            padding: 10px 6px !important;
            font-size: 13px !important;
        }

        .search-bar input:focus {
            outline: none;
            box-shadow: none;
            background: white !important;
        }

        /* Remove hover effects from dropdown container */
        .search-bar .dropdown {
            transition: none !important;
            position: relative;
            border-radius: 0 25px 25px 0;
        }

        .search-bar .dropdown:hover {
            background: transparent !important;
        }

        /* Make sure input doesn't overflow */
        .search-bar .form-control {
            border-radius: 0 25px 25px 0;
            font-size: 15px;
        }

        .search-bar .input-group-text:first-child {
            border-radius: 25px 0 0 25px;
        }

        /* Search Results Dropdown */
        .search-results-dropdown {
            position: absolute;
            top: 45px;
            left: 0;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1050;
            max-height: 500px;
            overflow-y: auto;
        }

        .search-results-dropdown::-webkit-scrollbar {
            width: 6px;
        }

        .search-results-dropdown::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .search-results-dropdown::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .search-result-item {
            display: block;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 14px;
        }

        .search-result-item:hover {
            background-color: #f8f9fa;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-section-title {
            display: none;
        }

        .search-empty {
            padding: 40px 20px;
            text-align: center;
            color: #999;
        }

        .search-empty i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* Icon Circle - vòng tròn mờ bao quanh icon */
        .icon-circle {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .icon-circle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .icon-circle i {
            font-size: 18px;
            color: white;
        }

        /* Icon Badge - số thông báo nhỏ gọn */
        .icon-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #dc3545;
            color: white;
            border-radius: 10px;
            padding: 1px 5px;
            font-size: 10px;
            font-weight: bold;
            line-height: 1.4;
            min-width: 18px;
            text-align: center;
            border: 1px solid #ffffffff;
        }

        /* Dropdown Menu Positioning */
        .navbar-nav .dropdown-menu {
            margin-top: 10px !important;
        }

        /* User Dropdown Menu Styling */
        .dropdown-menu .dropdown-item {
            transition: background-color 0.2s ease;
        }

        .dropdown-menu .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        /* Bỏ hiệu ứng scale/zoom khi hover */
        .dropdown-menu .dropdown-item i {
            transition: none;
        }

        /* Wallet Button */
        a.btn.btn-outline-light {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease, border-color 0.3s ease;
            transform: none !important;
        }

        a.btn.btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: white !important;
            color: white !important;
            transform: scale(1.05) !important;
            box-shadow: none !important;
        }

        /* Cart and Notification Dropdown */
        .cart-dropdown,
        .notification-dropdown {
            padding-top: 0 !important;
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }

        /* Cart dropdown header text */
        .cart-dropdown .dropdown-header {
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }

        .cart-dropdown .dropdown-header span {
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .cart-dropdown .notification-pill-badge {
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
            font-weight: 500;
            font-size: 13px;
        }

        /* Modern Cart Header */
        .cart-header-modern {
            background: white;
            color: #1d1d1f;
            padding: 16px 20px !important;
            border-bottom: 1px solid rgba(0, 113, 227, 0.1) !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 12px 12px 0 0 !important;
        }

        .cart-title {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin: 0;
            color: #1d1d1f;
        }

        .cart-title i {
            margin-right: 8px;
            color: #0071e3;
        }

        .cart-count-badge {
            background: rgba(0, 113, 227, 0.1);
            color: #0071e3;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }

        .cart-header-action {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            color: #262626;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            padding: 0;
        }

        .cart-header-action:hover {
            background: rgba(0, 0, 0, 0.05);
            transform: scale(1.05);
        }

        .cart-header-action:active {
            background: rgba(0, 0, 0, 0.1);
            transform: scale(0.95);
        }

        /* Modern Notification Header */
        .notification-header-modern {
            background: white;
            color: #1d1d1f;
            padding: 16px 20px !important;
            border-bottom: 1px solid rgba(0, 113, 227, 0.1) !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 12px 12px 0 0 !important;
        }

        .notification-title {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin: 0;
            color: #1d1d1f;
        }

        .notification-title i {
            margin-right: 8px;
            color: #0071e3;
        }

        .notification-count-badge {
            background: rgba(0, 113, 227, 0.1);
            color: #0071e3;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }

        .notification-header-action {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            color: #262626;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            padding: 0;
        }

        .notification-header-action:hover {
            background: rgba(0, 0, 0, 0.05);
            transform: scale(1.05);
        }

        .notification-header-action:active {
            background: rgba(0, 0, 0, 0.1);
            transform: scale(0.95);
        }

        /* Notification dropdown header text */
        .notification-dropdown .dropdown-header {
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }

        .notification-dropdown .dropdown-header span {
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        /* Notification items */
        .notification-dropdown .dropdown-item {
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }

        .notification-dropdown .notification-pill-badge,
        .notification-dropdown .notification-pill-button {
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }

        /* Cart item link hover effect */
        .cart-item-link {
            transition: color 0.2s ease;
        }

        .cart-item-link:hover {
            color: #0d6efd !important;
        }

        .notification-dropdown::-webkit-scrollbar {
            width: 6px;
        }

        .notification-dropdown::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .notification-dropdown::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .notification-dropdown::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Badge hover effect */
        .badge.rounded-pill {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        /* ========== YouTube-Style Auth Button ========== */

        /* YouTube CSS Variables */
        :root {
            --yt-spec-call-to-action: #065fd4;
            --yt-spec-brand-button-background: rgba(255, 255, 255, 0.98);
            --yt-spec-outline: rgba(0, 0, 0, 0.1);
        }

        /* Auth Button Base - YouTube Spec */
        .auth-button {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 15px;
            height: 36px;
            border-radius: 18px;
            font-family: "Roboto", "Arial", sans-serif;
            font-size: 15px;
            font-weight: 600;
            line-height: 36px;
            text-align: center;
            white-space: nowrap;
            letter-spacing: -0.01em;
            cursor: pointer;
            user-select: none;
            -webkit-user-select: none;
        }

        /* Single Auth Button - YouTube Light Style (giống ảnh) */
        .auth-single-btn {
            background-color: var(--yt-spec-brand-button-background);
            border: 1px solid var(--yt-spec-outline);
            color: var(--yt-spec-call-to-action);
            transition: background-color 0.1s cubic-bezier(0.05, 0, 0, 1),
                border-color 0.1s cubic-bezier(0.05, 0, 0, 1),
                box-shadow 0.1s cubic-bezier(0.05, 0, 0, 1);
        }

        .auth-single-btn:hover {
            background-color: rgba(240, 247, 255, 0.98);
            border-color: rgba(0, 0, 0, 0.15);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .auth-single-btn:active {
            background-color: rgba(230, 243, 255, 0.98);
            border-color: rgba(0, 0, 0, 0.2);
        }

        .auth-single-btn:focus {
            outline: none;
        }

        .auth-button i {
            font-size: 18px;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--yt-spec-call-to-action);
        }

        .auth-button span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Responsive - Mobile */
        @media (max-width: 991px) {
            .auth-button {
                height: 32px;
                line-height: 32px;
                padding: 0 12px;
                font-size: 13px;
                gap: 6px;
            }

            .auth-button i {
                font-size: 16px;
                width: 18px;
                height: 18px;
            }
        }

        @media (max-width: 576px) {
            .auth-button {
                height: 30px;
                line-height: 30px;
                padding: 0 10px;
                font-size: 12px;
                gap: 5px;
            }

            .auth-button i {
                font-size: 14px;
                width: 16px;
                height: 16px;
            }
        }

        /* ========== YouTube-Style User Dropdown ========== */
        .user-dropdown {
            max-width: 300px !important;
            width: 300px !important;
            max-height: 789px !important;
            padding: 0 !important;
            border: none !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 32px rgba(0, 0, 0, 0.1) !important;
            background-color: rgb(255, 255, 255) !important;
            overflow: hidden;
            transform-origin: top right;
            z-index: 2202 !important;
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }

        /* Animation when showing */
        .user-dropdown.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            transform: scale(1) !important;
            pointer-events: auto !important;
        }

        .user-dropdown-header {
            padding: 16px !important;
            border-bottom: 1px solid #e5e5e5 !important;
            background: #f9f9f9 !important;
            border-radius: 12px 12px 0 0 !important;
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }

        .user-dropdown-header .rounded-circle {
            width: 40px !important;
            height: 40px !important;
        }

        .user-dropdown-header .rounded-circle i {
            font-size: 20px !important;
        }

        .user-dropdown-header .fw-bold {
            font-size: 14px;
            line-height: 20px;
            font-weight: 500;
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }

        .user-dropdown-header .small {
            font-size: 12px;
            line-height: 16px;
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }

        .user-dropdown .dropdown-item {
            padding: 10px 16px !important;
            font-size: 14px;
            line-height: 20px;
            color: #030303;
            transition: background-color 0.1s ease;
            border-radius: 0 !important;
            font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }

        .user-dropdown .dropdown-item:hover {
            background-color: #f2f2f2 !important;
            border-radius: 0 !important;
        }

        .user-dropdown .dropdown-item i {
            width: 24px !important;
            height: 24px !important;
            min-width: 24px !important;
            font-size: 18px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        .user-dropdown .dropdown-item span {
            line-height: 24px;
            display: inline-block;
        }

        .user-dropdown .dropdown-divider {
            margin: 0 !important;
            border-color: #e5e5e5 !important;
        }

        .user-dropdown .px-2 {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .user-dropdown .pt-2 {
            padding-top: 8px !important;
        }

        .user-dropdown .pb-2 {
            padding-bottom: 8px !important;
        }

        .user-dropdown .text-danger {
            color: #cc0000 !important;
        }

        .user-dropdown .text-danger:hover {
            background-color: #f2f2f2 !important;
            color: #cc0000 !important;
        }

        /* Last item border radius */
        .user-dropdown li:last-child .dropdown-item {
            border-radius: 0 0 12px 12px !important;
        }

        /* Ensure rounded corners */
        .user-dropdown .dropdown-menu {
            overflow: hidden;
        }

        /* Scrollbar for overflow content */
        .user-dropdown {
            overflow-y: auto;
        }

        .user-dropdown::-webkit-scrollbar {
            width: 8px;
        }

        .user-dropdown::-webkit-scrollbar-track {
            background: transparent;
        }

        .user-dropdown::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }

        .user-dropdown::-webkit-scrollbar-thumb:hover {
            background-color: rgba(0, 0, 0, 0.3);
        }

        /* Animation for menu items - disabled for now to ensure dropdown works */
        /*
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .user-dropdown.show li .dropdown-item {
        animation: fadeInUp 0.2s ease-out backwards;
    }

    .user-dropdown.show li:nth-child(1) .dropdown-item {
        animation-delay: 0.02s;
    }

    .user-dropdown.show li:nth-child(2) .dropdown-item {
        animation-delay: 0.04s;
    }

    .user-dropdown.show li:nth-child(3) .dropdown-item {
        animation-delay: 0.06s;
    }

    .user-dropdown.show li:nth-child(4) .dropdown-item {
        animation-delay: 0.08s;
    }

    .user-dropdown.show li:nth-child(5) .dropdown-item {
        animation-delay: 0.10s;
    }

    .user-dropdown.show li:nth-child(6) .dropdown-item {
        animation-delay: 0.12s;
    }
    */
    </style>

    <!-- Flash Messages -->
    <?php
    $flash = getFlash();
    if ($flash):
        $alertType = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        $alertClass = $alertType[$flash['type']] ?? 'alert-info';
        ?>
        <div class="container mt-3">
            <div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Real-time Update System -->
    <script>
        // Broadcast channel để đồng bộ giữa các tabs
        // DISABLED to prevent loading issues
        // const updateChannel = new BroadcastChannel('shop_updates');
        const updateChannel = { postMessage: function () { }, onmessage: null }; // Dummy object

        // Mark single notification as read
        function markAsRead(notificationId) {
            fetch('/api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_read&notification_id=' + notificationId
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update badge count
                        updateNotificationBadge(data.unread_count);
                        // Broadcast to other tabs
                        updateChannel.postMessage({
                            type: 'notification_read',
                            unread_count: data.unread_count
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Mark all notifications as read
        function markAllAsRead() {

            fetch('/api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_all_read'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update badge count to 0
                        updateNotificationBadge(0);

                        // Update notification UI
                        updateNotificationUIAllRead();

                        // Broadcast to other tabs
                        updateChannel.postMessage({
                            type: 'all_notifications_read'
                        });

                        // Show success toast
                        if (typeof showToast === 'function') {
                            showToast('Đã đánh dấu tất cả thông báo là đã đọc', 'success');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra. Vui lòng thử lại.');
                });
        }

        // Update notification badge count
        function updateNotificationBadge(count) {
            const badge = document.querySelector('#notificationDropdown .icon-badge');
            if (count > 0) {
                if (badge) {
                    badge.textContent = count > 9 ? '9+' : count;
                } else {
                    // Create badge if it doesn't exist
                    const iconCircle = document.querySelector('#notificationDropdown .icon-circle');
                    if (iconCircle) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'icon-badge';
                        newBadge.textContent = count > 9 ? '9+' : count;
                        iconCircle.appendChild(newBadge);
                    }
                }
            } else {
                // Remove badge if count is 0
                if (badge) {
                    badge.remove();
                }
            }
        }

        // Update notification UI to mark all as read
        function updateNotificationUIAllRead() {
            // Remove "unread" styling from all notification items
            document.querySelectorAll('.notification-item .dropdown-item').forEach(item => {
                item.classList.remove('bg-light');
            });

            // Remove fw-bold from notification titles
            document.querySelectorAll('.notification-item .dropdown-item > div > div.flex-grow-1 > div').forEach(title => {
                title.classList.remove('fw-bold');
            });

            // Remove all unread indicator badges (blue dots)
            document.querySelectorAll('.notification-item .badge.bg-primary.rounded-pill').forEach(badge => {
                badge.remove();
            });

            // Hide the "X mới" badge and "Đánh dấu đã đọc" button in dropdown header
            const dropdownHeader = document.querySelector('.notification-dropdown .dropdown-header');
            if (dropdownHeader) {
                const newBadge = dropdownHeader.querySelector('.badge.bg-light.text-primary');
                const markReadBtn = dropdownHeader.querySelector('a.badge.bg-white');
                if (newBadge) newBadge.remove();
                if (markReadBtn) markReadBtn.remove();
            }
        }

        // Update cart badge
        function updateCartBadge(count) {
            const cartLink = document.querySelector('a[href="/cart"]');
            if (!cartLink) return;

            let badge = cartLink.querySelector('.badge');
            if (count > 0) {
                if (badge) {
                    badge.textContent = count;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    newBadge.style.fontSize = '0.7rem';
                    newBadge.textContent = count;
                    cartLink.appendChild(newBadge);
                }
            } else {
                if (badge) {
                    badge.remove();
                }
            }
        }

        // Close cart dropdown
        function closeCartDropdown() {
            const cartDropdown = document.querySelector('.cart-dropdown');
            if (cartDropdown) {
                const bsDropdown = bootstrap.Dropdown.getInstance(cartDropdown.closest('.dropdown'));
                if (bsDropdown) {
                    bsDropdown.hide();
                }
            }
        }

        // Close notification dropdown
        function closeNotificationDropdown() {
            const notificationDropdown = document.querySelector('.notification-dropdown');
            if (notificationDropdown) {
                const bsDropdown = bootstrap.Dropdown.getInstance(notificationDropdown.closest('.dropdown'));
                if (bsDropdown) {
                    bsDropdown.hide();
                }
            }
        }

        // Update wishlist badge
        function updateWishlistBadge(count) {
            const wishlistLink = document.querySelector('a[href="/wishlist"]');
            if (!wishlistLink) return;

            let badge = wishlistLink.querySelector('.badge');
            if (count > 0) {
                if (badge) {
                    badge.textContent = count;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    newBadge.style.fontSize = '0.7rem';
                    newBadge.textContent = count;
                    wishlistLink.appendChild(newBadge);
                }
            } else {
                if (badge) {
                    badge.remove();
                }
            }
        }

        // Show toast notification
        function showToast(title, message, type = 'info') {
            // Tạo toast element
            const toastContainer = document.getElementById('toast-container') || createToastContainer();

            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'primary'} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

            toastContainer.appendChild(toast);

            // Toast will NOT auto-hide - must close manually
            const bsToast = new bootstrap.Toast(toast, { autohide: false });
            bsToast.show();
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '11';
            document.body.appendChild(container);
            return container;
        }

        // Polling for updates
        let lastNotificationCount = <?= $currentUser ? getUnreadNotificationCount($currentUser['id']) : 0 ?>;
        let lastCartCount = <?= count(getCart()) ?>;
        let lastWishlistCount = <?= $currentUser ? getWishlistCount($currentUser['id']) : 0 ?>;

        function checkForUpdates() {
            // Add timeout to prevent hanging
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout

            fetch('/api/check-updates.php', {
                signal: controller.signal
            })
                .then(response => {
                    clearTimeout(timeoutId);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Kiểm tra thông báo mới
                        if (data.logged_in && data.notification_count > lastNotificationCount) {
                            updateNotificationBadge(data.notification_count);

                            // Hiển thị toast cho thông báo mới
                            const newCount = data.notification_count - lastNotificationCount;
                            showToast('Thông báo mới', `Bạn có ${newCount} thông báo mới`, 'info');

                            // Play sound (optional)
                            // new Audio('/public/sounds/notification.mp3').play();

                            // Broadcast to other tabs
                            updateChannel.postMessage({
                                type: 'new_notification',
                                count: data.notification_count
                            });
                        }

                        // Cập nhật cart count
                        if (data.cart_count !== lastCartCount) {
                            updateCartBadge(data.cart_count);
                            updateChannel.postMessage({
                                type: 'cart_updated',
                                count: data.cart_count
                            });
                        }

                        // Cập nhật wishlist count
                        if (data.logged_in && data.wishlist_count !== lastWishlistCount) {
                            updateWishlistBadge(data.wishlist_count);
                            updateChannel.postMessage({
                                type: 'wishlist_updated',
                                count: data.wishlist_count
                            });
                        }

                        // Cập nhật last counts
                        lastNotificationCount = data.notification_count;
                        lastCartCount = data.cart_count;
                        lastWishlistCount = data.wishlist_count;
                    }
                })
                .catch(error => {
                    // Ignore AbortError (timeout) and other errors silently
                    // Don't spam console with errors every 15 seconds
                    if (error.name !== 'AbortError') {
                        // console.error('Error checking updates:', error);
                    }
                });
        }

        // Listen to broadcast messages from other tabs
        updateChannel.onmessage = (event) => {
            const { type, count, unread_count } = event.data;

            switch (type) {
                case 'new_notification':
                    updateNotificationBadge(count);
                    lastNotificationCount = count;
                    break;
                case 'notification_read':
                    updateNotificationBadge(unread_count);
                    lastNotificationCount = unread_count;
                    break;
                case 'all_notifications_read':
                    updateNotificationBadge(0);
                    updateNotificationUIAllRead();
                    lastNotificationCount = 0;
                    break;
                case 'cart_updated':
                    updateCartBadge(count);
                    lastCartCount = count;
                    break;
                case 'wishlist_updated':
                    updateWishlistBadge(count);
                    lastWishlistCount = count;
                    break;
            }
        };

        // COMPLETELY DISABLED: Do not call checkForUpdates at all to prevent loading spinner
        // User can manually refresh page to see updates
        // setInterval(checkForUpdates, 60000);
        // setTimeout(checkForUpdates, 10000);

        // Define refreshCartDropdown stub function EARLY to prevent errors
        function refreshCartDropdown() {
            // Stub function - will be overridden later
        }

        // Fix cart dropdown: prevent closing when clicking inside
        document.addEventListener('DOMContentLoaded', function () {
            const cartDropdown = document.querySelector('.cart-dropdown');
            if (cartDropdown) {
                cartDropdown.addEventListener('click', function (e) {
                    // Prevent closing when clicking inside the dropdown
                    // But allow links and buttons to work normally
                    if (!e.target.closest('a') && !e.target.closest('button')) {
                        e.stopPropagation();
                    }
                });
            }

            // Fix notification dropdown: prevent closing when clicking inside
            const notificationDropdown = document.querySelector('.notification-dropdown');
            if (notificationDropdown) {
                notificationDropdown.addEventListener('click', function (e) {
                    // Prevent closing when clicking inside the dropdown
                    // But allow links to work normally
                    if (!e.target.closest('a')) {
                        e.stopPropagation();
                    }
                });
            }

            // Fix user dropdown: prevent closing when clicking header
            const userDropdownHeader = document.querySelector('.user-dropdown-header');
            if (userDropdownHeader) {
                userDropdownHeader.addEventListener('click', function (e) {
                    // Prevent closing when clicking on header
                    e.stopPropagation();
                });
            }

            // Mobile Sidebar Toggle
            const mobileSidebar = document.getElementById('mobileSidebar');
            const mobileSidebarBackdrop = document.getElementById('mobileSidebarBackdrop');
            const mobileSidebarClose = document.getElementById('mobileSidebarClose');
            const navbarToggler = document.querySelector('.navbar-toggler');

            function openMobileSidebar() {
                mobileSidebar.classList.add('show');
                mobileSidebarBackdrop.classList.add('show');
                document.body.style.overflow = 'hidden';
                // Toggle hamburger icon to X
                if (navbarToggler) {
                    navbarToggler.classList.add('active');
                }
            }

            function closeMobileSidebar() {
                mobileSidebar.classList.remove('show');
                mobileSidebarBackdrop.classList.remove('show');
                document.body.style.overflow = '';
                // Toggle X back to hamburger
                if (navbarToggler) {
                    navbarToggler.classList.remove('active');
                }
            }

            // Open sidebar when clicking navbar toggler
            if (navbarToggler) {
                navbarToggler.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openMobileSidebar();
                });
            }

            // Close sidebar when clicking close button
            if (mobileSidebarClose) {
                mobileSidebarClose.addEventListener('click', function () {
                    closeMobileSidebar();
                });
            }

            // Close sidebar when clicking backdrop
            if (mobileSidebarBackdrop) {
                mobileSidebarBackdrop.addEventListener('click', function () {
                    closeMobileSidebar();
                });
            }

            // Close sidebar when clicking links inside (to navigate)
            const mobileSidebarLinks = document.querySelectorAll('.mobile-sidebar-link');
            mobileSidebarLinks.forEach(link => {
                link.addEventListener('click', function () {
                    // Close sidebar after a short delay to allow navigation
                    setTimeout(closeMobileSidebar, 100);
                });
            });

            // Close sidebar when clicking auth button to open modal
            const mobileSidebarAuthBtn = document.querySelector('.mobile-sidebar-auth-btn');
            if (mobileSidebarAuthBtn) {
                mobileSidebarAuthBtn.addEventListener('click', function () {
                    // Close sidebar immediately to show modal
                    closeMobileSidebar();
                });
            }

            // Handle remove cart item
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-cart-item') || e.target.closest('.remove-cart-item')) {
                    e.preventDefault();
                    e.stopPropagation();

                    const button = e.target.classList.contains('remove-cart-item') ? e.target : e.target.closest('.remove-cart-item');
                    const productId = button.dataset.productId;
                    const variantId = button.dataset.variantId;

                    button.disabled = true;
                    button.style.opacity = '0.5';

                    const formData = new FormData();
                    formData.append('product_id', productId);
                    if (variantId) {
                        formData.append('variant_id', variantId);
                    }

                    fetch('/api/remove-from-cart.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const cartBadges = document.querySelectorAll('#cartDropdown .icon-badge');
                                cartBadges.forEach(badge => {
                                    if (data.cart_count > 0) {
                                        badge.textContent = data.cart_count > 9 ? '9+' : data.cart_count;
                                    } else {
                                        badge.remove();
                                    }
                                });

                                if (typeof refreshCartDropdown === 'function') {
                                    refreshCartDropdown();
                                }

                                showToast('Thành công', data.message, 'success');
                            } else {
                                showToast('Lỗi', data.message || 'Có lỗi xảy ra', 'error');
                                button.disabled = false;
                                button.style.opacity = '1';
                            }
                        })
                        .catch(error => {
                            console.error('Error removing from cart:', error);
                            showToast('Lỗi', 'Có lỗi xảy ra. Vui lòng thử lại.', 'error');
                            button.disabled = false;
                            button.style.opacity = '1';
                        });
                }
            });

            // Function to refresh cart dropdown
            function refreshCartDropdown() {
                fetch('/api/get-cart-dropdown.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const cartItemsContainer = document.querySelector('ul.cart-dropdown');
                            if (!cartItemsContainer) return;

                            const itemsPreview = data.items_preview;
                            const totalAmount = data.total_amount;
                            const cartCount = data.cart_count;

                            let html = `
                            <li class="dropdown-header cart-header-modern">
                                <h3 class="cart-title"><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</h3>
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="cart-count-badge">${cartCount} sản phẩm</span>
                                </div>
                            </li>
                        `;

                            if (itemsPreview.length === 0) {
                                html += `
                                <li class="text-center text-muted py-5">
                                    <i class="fas fa-shopping-cart fa-3x mb-3 opacity-50"></i>
                                    <p class="mb-0">Giỏ hàng trống</p>
                                    <a href="/products" class="pill-button pill-button-blue mt-3">
                                        <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                                    </a>
                                </li>
                            `;
                            } else {
                                itemsPreview.forEach(item => {
                                    const formattedPrice = new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND'
                                    }).format(item.price);

                                    html += `
                                    <li class="px-3 py-3 border-bottom cart-item" data-cart-item-id="${item.id}">
                                        <div class="d-flex align-items-start">
                                            <img src="${item.image || '/public/images/placeholder-product.svg'}"
                                                 alt="${item.name}"
                                                 class="me-3"
                                                 style="width: 128px; height: 60px; object-fit: cover; border-radius: 6px;"
                                                 onerror="this.src='/public/images/placeholder-product.svg';">
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <h6 class="mb-0 small fw-bold flex-grow-1 me-2">
                                                        <a href="${item.product_url}" class="text-decoration-none text-dark cart-item-link">
                                                            ${item.name}
                                                        </a>
                                                    </h6>
                                                    <button type="button" class="btn-close btn-close-sm remove-cart-item"
                                                            data-product-id="${item.id}"
                                                            data-variant-id="${item.variant_id || ''}"
                                                            style="font-size: 10px; flex-shrink: 0;"
                                                            title="Xóa khỏi giỏ hàng"></button>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-primary fw-bold small">
                                                        ${formattedPrice}
                                                    </span>
                                                    <span class="text-muted small">x${item.quantity}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                `;
                                });

                                if (data.items.length > 5) {
                                    html += `
                                    <li class="px-3 py-2 text-center bg-light">
                                        <small class="text-muted">
                                            Còn ${data.items.length - 5} sản phẩm khác...
                                        </small>
                                    </li>
                                `;
                                }

                                const formattedTotal = new Intl.NumberFormat('vi-VN', {
                                    style: 'currency',
                                    currency: 'VND'
                                }).format(totalAmount);

                                html += `
                                <li class="px-3 py-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-bold">Tổng tạm tính:</span>
                                        <span class="text-primary fw-bold fs-5">${formattedTotal}</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="/cart" class="pill-button pill-button-blue flex-fill text-center" style="padding: 10px 16px; font-size: 13px;">
                                            <i class="fas fa-eye"></i> Xem giỏ hàng
                                        </a>
                                        <a href="/checkout" class="pill-button pill-button-green flex-fill text-center" style="padding: 10px 16px; font-size: 13px;">
                                            <i class="fas fa-credit-card"></i> Thanh toán
                                        </a>
                                    </div>
                                </li>
                            `;
                            }

                            cartItemsContainer.innerHTML = html;
                        }
                    })
                    .catch(error => {
                        console.error('Error refreshing cart dropdown:', error);
                    });
            }

            // Allow toasts to auto-hide after 5 seconds
            // Removed the CSS that was preventing toast fade animations
        });
    </script>

    <!-- Auth Modal (Login/Register) -->
    <div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 20px; overflow: hidden;">
                <div class="modal-body p-0">
                    <!-- Mobile Tab Navigation -->
                    <div class="auth-tabs d-md-none">
                        <button class="auth-tab active" data-tab="login">Đăng nhập</button>
                        <button class="auth-tab" data-tab="register">Đăng ký</button>
                    </div>

                    <div class="row g-0">
                        <!-- Login Section -->
                        <div class="col-md-6 auth-section login-section active"
                            style="background: #fff; padding: 3rem;">
                            <button type="button" class="btn-close btn-close-login position-absolute top-0 end-0 m-3"
                                data-bs-dismiss="modal" aria-label="Close"></button>

                            <div class="mb-4">
                                <h2 class="fw-bold mb-2">Đăng nhập</h2>
                                <p class="mb-0 text-muted">Chào mừng bạn quay trở lại!</p>
                            </div>

                            <form id="loginForm" class="auth-form">
                                <div class="alert alert-danger d-none" id="loginError"></div>

                                <div class="mb-3">
                                    <label class="form-label">Email hoặc Tên đăng nhập</label>
                                    <input type="text" name="username" class="form-control" style="border-radius: 10px;"
                                        autocomplete="username" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu</label>
                                    <input type="password" name="password" class="form-control"
                                        style="border-radius: 10px;" autocomplete="off" required>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberLogin">
                                    <label class="form-check-label" for="rememberLogin">Ghi nhớ đăng nhập</label>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold">
                                    Đăng nhập
                                </button>

                                <div class="text-center mt-3">
                                    <a href="/forgot-password.php" class="text-muted text-decoration-none">Quên mật
                                        khẩu?</a>
                                </div>
                            </form>
                        </div>

                        <!-- Register Section -->
                        <div class="col-md-6 auth-section register-section" style="background: #fff; padding: 3rem;">
                            <button type="button" class="btn-close btn-close-register position-absolute top-0 end-0 m-3"
                                data-bs-dismiss="modal" aria-label="Close"></button>

                            <div class="mb-4">
                                <h2 class="fw-bold mb-2">Đăng ký</h2>
                                <p class="text-muted mb-0">Tạo tài khoản mới miễn phí</p>
                            </div>

                            <form id="registerForm" class="auth-form">
                                <div class="alert alert-danger d-none" id="registerError"></div>

                                <div class="mb-3">
                                    <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" style="border-radius: 10px;"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" style="border-radius: 10px;"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Họ và tên</label>
                                    <input type="text" name="full_name" class="form-control"
                                        style="border-radius: 10px;">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control"
                                        style="border-radius: 10px;" autocomplete="new-password" required>
                                    <small class="text-muted">Tối thiểu 6 ký tự</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nhập lại mật khẩu <span
                                            class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control"
                                        style="border-radius: 10px;" autocomplete="new-password" required>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="agreeTerms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        Tôi đồng ý với <a href="/terms" target="_blank">Điều khoản sử dụng</a>
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold">
                                    Đăng ký
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Auth Modal Styles */
        #authModal .modal-xl {
            max-width: 900px;
        }

        #authModal .auth-section {
            min-height: 550px;
            position: relative;
        }

        #authModal .form-control {
            border: 1px solid #dee2e6;
        }

        #authModal .form-control:focus {
            box-shadow: none;
            border-color: #dee2e6;
            outline: none;
        }

        #authModal .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        #authModal .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        #authModal .register-section {
            border-left: 1px solid #e9ecef;
        }

        .hover-opacity-100:hover {
            opacity: 1 !important;
        }

        /* Mobile Auth Tabs */
        .auth-tabs {
            display: flex;
            background: #fff;
            border-bottom: 2px solid #f0f0f0;
            border-radius: 20px 20px 0 0;
        }

        .auth-tab {
            flex: 1;
            padding: 14px 16px;
            background: none;
            border: none;
            font-size: 15px;
            font-weight: 500;
            color: #999;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .auth-tab:first-child {
            border-radius: 20px 0 0 0;
        }

        .auth-tab:last-child {
            border-radius: 0 20px 0 0;
        }

        .auth-tab.active {
            color: #1a1a1a;
            font-weight: 600;
        }

        .auth-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #1a1a1a;
        }

        .auth-tab:active {
            background: #f8f8f8;
        }

        @media (min-width: 769px) {
            #authModal .auth-section {
                display: block !important;
            }

            #authModal .btn-close-login {
                display: none;
            }
        }

        @media (max-width: 768px) {
            #authModal .modal-dialog {
                margin: 1rem;
                max-width: calc(100% - 2rem);
            }

            #authModal .modal-content {
                border-radius: 20px !important;
            }

            #authModal .modal-body {
                overflow-y: auto;
                max-height: calc(100vh - 4rem);
                padding: 0 !important;
            }

            #authModal .modal-body .auth-tabs {
                margin: 0;
            }

            #authModal .auth-section {
                min-height: auto;
                padding: 1.5rem !important;
                display: none;
            }

            #authModal .auth-section.active {
                display: block;
            }

            #authModal .col-md-6 {
                min-height: auto;
            }

            #authModal .btn-close {
                z-index: 10;
            }

            #authModal .login-section,
            #authModal .register-section {
                background: #fff !important;
            }

            #authModal .login-section h2,
            #authModal .register-section h2 {
                font-size: 1.5rem;
            }

            #authModal .form-control {
                border: 1px solid #dee2e6;
            }

            #authModal .form-control:focus {
                border-color: #0d6efd;
                box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
            }

            #authModal .register-section {
                border-left: none;
            }
        }

        @media (max-width: 480px) {
            #authModal .modal-dialog {
                margin: 0.75rem;
                max-width: calc(100% - 1.5rem);
            }

            #authModal .auth-section {
                padding: 1.25rem !important;
            }

            #authModal .auth-tab {
                font-size: 14px;
                padding: 12px 12px;
            }
        }
    </style>

    <script>
        // Wait for DOM to be ready before attaching event listeners
        document.addEventListener('DOMContentLoaded', function () {
            // Handle Auth Tab Switching (Mobile)
            const authTabs = document.querySelectorAll('.auth-tab');
            const loginSection = document.querySelector('.login-section');
            const registerSection = document.querySelector('.register-section');

            authTabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    const targetTab = this.getAttribute('data-tab');

                    // Update tab active state
                    authTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    // Toggle sections
                    if (targetTab === 'login') {
                        loginSection.classList.add('active');
                        registerSection.classList.remove('active');
                    } else {
                        registerSection.classList.add('active');
                        loginSection.classList.remove('active');
                    }
                });
            });

            // Handle modal show event to set correct tab
            const authModal = document.getElementById('authModal');
            if (authModal) {
                authModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const tab = button?.getAttribute('data-tab');

                    if (tab === 'register') {
                        // Show register tab
                        authTabs.forEach(t => {
                            if (t.getAttribute('data-tab') === 'register') {
                                t.click();
                            }
                        });
                    } else {
                        // Show login tab (default)
                        authTabs.forEach(t => {
                            if (t.getAttribute('data-tab') === 'login') {
                                t.click();
                            }
                        });
                    }
                });
            }

            // Handle Login Form Submit
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const errorDiv = document.getElementById('loginError');
                    errorDiv.classList.add('d-none');

                    try {
                        const response = await fetch('/api/login.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Đăng nhập thành công
                            window.location.href = data.redirect || '/';
                        } else {
                            // Hiển thị lỗi
                            errorDiv.textContent = data.error;
                            errorDiv.classList.remove('d-none');
                        }
                    } catch (error) {
                        console.error('Login error:', error); // Debug log
                        errorDiv.textContent = 'Có lỗi xảy ra, vui lòng thử lại';
                        errorDiv.classList.remove('d-none');
                    }
                });
            }

            // Handle Register Form Submit
            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const errorDiv = document.getElementById('registerError');
                    errorDiv.classList.add('d-none');

                    // Validate password match
                    const password = formData.get('password');
                    const confirmPassword = formData.get('confirm_password');

                    if (password !== confirmPassword) {
                        errorDiv.textContent = 'Mật khẩu xác nhận không khớp';
                        errorDiv.classList.remove('d-none');
                        return;
                    }

                    if (password.length < 6) {
                        errorDiv.textContent = 'Mật khẩu phải có ít nhất 6 ký tự';
                        errorDiv.classList.remove('d-none');
                        return;
                    }

                    try {
                        const response = await fetch('/api/register.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Đăng ký thành công
                            window.location.href = data.redirect || '/';
                        } else {
                            // Hiển thị lỗi
                            errorDiv.textContent = data.error;
                            errorDiv.classList.remove('d-none');
                        }
                    } catch (error) {
                        console.error('Register error:', error); // Debug log
                        errorDiv.textContent = 'Có lỗi xảy ra, vui lòng thử lại';
                        errorDiv.classList.remove('d-none');
                    }
                });
            }

            // ============================================
            // SEARCH FUNCTIONALITY
            // ============================================
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');
            const searchContent = searchResults ? searchResults.querySelector('.search-content') : null;
            const searchLoading = searchResults ? searchResults.querySelector('.search-loading') : null;
            let searchTimeout = null;
            let selectedCategoryId = 'all';

            // Animated Placeholder
            const placeholders = [
                'Bạn đang tìm kiếm gì...',
                'Tìm VPN, Netflix, Spotify...',
                'Tìm game, phần mềm...',
                'Tìm Cloud Storage...',
                'Bạn cần gì hôm nay...'
            ];
            let currentPlaceholderIndex = 0;
            let charIndex = 0;
            let isDeleting = false;
            let typingSpeed = 100;

            function animatePlaceholder() {
                // Don't animate if user is typing
                if (searchInput === document.activeElement || searchInput.value.length > 0) {
                    setTimeout(animatePlaceholder, 2000);
                    return;
                }

                const currentText = placeholders[currentPlaceholderIndex];

                if (isDeleting) {
                    searchInput.placeholder = currentText.substring(0, charIndex - 1);
                    charIndex--;
                    typingSpeed = 50;
                } else {
                    searchInput.placeholder = currentText.substring(0, charIndex + 1);
                    charIndex++;
                    typingSpeed = 100;
                }

                if (!isDeleting && charIndex === currentText.length) {
                    // Pause at end before deleting
                    isDeleting = true;
                    typingSpeed = 2000;
                } else if (isDeleting && charIndex === 0) {
                    // Move to next placeholder
                    isDeleting = false;
                    currentPlaceholderIndex = (currentPlaceholderIndex + 1) % placeholders.length;
                }

                setTimeout(animatePlaceholder, typingSpeed);
            }

            // Start animation
            setTimeout(animatePlaceholder, 1000);

            // Show popular products on focus
            searchInput.addEventListener('focus', function () {
                if (!this.value.trim()) {
                    showPopularProducts();
                }
            });

            // Hide results when clicking outside
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.search-bar') && !e.target.closest('.search-results-dropdown')) {
                    searchResults.style.display = 'none';
                }
            });

            // Real-time search
            searchInput.addEventListener('input', function () {
                const query = this.value.trim();

                clearTimeout(searchTimeout);

                if (query.length === 0) {
                    showPopularProducts();
                    return;
                }

                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }

                // Show loading
                searchLoading.style.display = 'block';
                searchContent.innerHTML = '';
                searchResults.style.display = 'block';

                // Debounce search
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            });

            // Enter key to navigate to products page with search
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    const query = this.value.trim();
                    if (query) {
                        window.location.href = '/products?q=' + encodeURIComponent(query);
                    }
                }
            });

            // Perform search
            function performSearch(query) {
                const params = new URLSearchParams({
                    q: query,
                    category: selectedCategoryId
                });

                fetch('/api/search.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        searchLoading.style.display = 'none';

                        if (data.success && data.products && data.products.length > 0) {
                            displaySearchResults(data.products);
                        } else {
                            displayNoResults(query);
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        searchLoading.style.display = 'none';
                        searchContent.innerHTML = '<div class="search-empty"><p>Có lỗi xảy ra. Vui lòng thử lại.</p></div>';
                    });
            }

            // Show popular products - text only
            function showPopularProducts() {
                searchLoading.style.display = 'block';
                searchContent.innerHTML = '';
                searchResults.style.display = 'block';

                fetch('/api/search.php?popular=1')
                    .then(response => response.json())
                    .then(data => {
                        searchLoading.style.display = 'none';

                        if (data.success && data.products && data.products.length > 0) {
                            let html = '';
                            data.products.forEach(product => {
                                html += createProductItem(product);
                            });
                            searchContent.innerHTML = html;
                        } else {
                            searchContent.innerHTML = '<div class="search-empty"><p>Chưa có sản phẩm nào</p></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading popular products:', error);
                        searchLoading.style.display = 'none';
                        searchContent.innerHTML = '<div class="search-empty"><p>Không thể tải sản phẩm</p></div>';
                    });
            }

            // Display search results - text only
            function displaySearchResults(products) {
                let html = '';
                products.forEach(product => {
                    html += createProductItem(product);
                });

                searchContent.innerHTML = html;
            }

            // Display no results
            function displayNoResults(query) {
                searchContent.innerHTML = `
                <div class="search-empty">
                    <i class="fas fa-search"></i>
                    <p class="mb-2">Không tìm thấy sản phẩm nào</p>
                    <small class="text-muted">Thử tìm kiếm với từ khóa khác</small>
                </div>
            `;
            }

            // Create product item HTML - text only
            function createProductItem(product) {
                const productUrl = product.slug ? '/' + product.slug : '/product?id=' + product.id;

                return `
                <a href="${productUrl}" class="search-result-item text-decoration-none text-dark">
                    <div class="py-2 px-3">${product.name}</div>
                </a>
            `;
            }

            // Format money helper
            function formatMoney(amount) {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(amount);
            }

            // Export functions to window for use in other pages
            window.updateCartBadge = updateCartBadge;
            window.updateCartCount = updateCartBadge; // Alias for compatibility
            window.refreshCartDropdown = refreshCartDropdown;
        });
    </script>

    <!-- Smart Sticky Header Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let lastScrollTop = 0;
            const navbar = document.querySelector('.navbar');
            const scrollThreshold = 10;
            const navbarHeight = 61;

            if (navbar) {
                window.addEventListener('scroll', function () {
                    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                    // Prevent negative scrolling (mobile bounce)
                    if (scrollTop < 0) scrollTop = 0;

                    // Always show at top
                    if (scrollTop < navbarHeight) {
                        navbar.classList.remove('navbar-hidden');
                        navbar.classList.add('navbar-visible');
                        lastScrollTop = scrollTop;
                        return;
                    }

                    if (Math.abs(lastScrollTop - scrollTop) <= scrollThreshold)
                        return;

                    if (scrollTop > lastScrollTop) {
                        // Scroll Down -> Hide
                        navbar.classList.remove('navbar-visible');
                        navbar.classList.add('navbar-hidden');
                        // Close any open dropdowns when hiding header
                        const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
                        openDropdowns.forEach(dropdown => {
                            dropdown.classList.remove('show');
                            const toggle = dropdown.parentElement.querySelector('[data-bs-toggle="dropdown"]');
                            if (toggle) toggle.classList.remove('show');
                        });
                    } else {
                        // Scroll Up -> Show
                        navbar.classList.remove('navbar-hidden');
                        navbar.classList.add('navbar-visible');
                    }
                    lastScrollTop = scrollTop;
                }, { passive: true });
            }
        });
    </script>

    <!-- Main Content -->
    <main class="py-4">