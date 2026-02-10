<?php
/**
 * Product page với slug-based routing
 * URL: /youtube-premium-3-thang thay vì /product-detail?id=33
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
initSession();

// Generate CSRF token for security
$csrfToken = generateCSRFToken();

// Lấy slug từ URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    setFlash('error', 'Sản phẩm không tồn tại');
    redirect('/products');
}

// Tìm sản phẩm theo slug (có thể là slug của product hoặc variant)
$stmt = db()->query(
    "SELECT p.*, c.name as category_name, c.slug as category_slug
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.slug = ?",
    [$slug]
);
$product = $stmt->fetch();

// Nếu không tìm thấy trong products, thử tìm trong product_variants
if (!$product) {
    try {
        $variantStmt = db()->query(
            "SELECT p.*, c.name as category_name, c.slug as category_slug,
                    v.id as variant_id, v.name as variant_name, v.slug as variant_slug,
                    v.price as variant_price, v.sale_price as variant_sale_price,
                    v.stock_quantity as variant_stock, v.duration as variant_duration,
                    v.variant_title, v.variant_image, v.delivery_type as variant_delivery_type
             FROM product_variants v
             JOIN products p ON v.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE v.slug = ? AND v.status = 'active'",
            [$slug]
        );
        $variant = $variantStmt->fetch();

        if ($variant) {
            // Override product data with variant data
            $product = $variant;
            $product['price'] = $variant['variant_price'];
            $product['sale_price'] = $variant['variant_sale_price'];
            $product['stock_quantity'] = $variant['variant_stock'];
            if ($variant['variant_duration']) {
                $product['duration'] = $variant['variant_duration'];
            }
            // Override với tiêu đề riêng nếu có
            if (!empty($variant['variant_title'])) {
                $product['name'] = $variant['variant_title'];
            } else {
                 // Nếu không có title riêng, nối thêm tên gói vào tên sản phẩm
                 $product['name'] .= ' ' . $variant['variant_name'];
            }
            // Override với ảnh riêng nếu có
            if (!empty($variant['variant_image'])) {
                $product['image'] = $variant['variant_image'];
            }
            // Override loại giao hàng riêng nếu có
            if (!empty($variant['variant_delivery_type'])) {
                $product['delivery_type'] = $variant['variant_delivery_type'];
            }
            // Lưu cả tên và slug của variant
            $product['display_name'] = $variant['name'] . ' - ' . $variant['variant_name'];
            $product['display_slug'] = $variant['variant_slug'];
        }
    } catch (Exception $e) {
        // Bảng variant chưa tồn tại, bỏ qua
    }
}

// Nếu vẫn không tìm thấy, thử tìm theo ID (backward compatibility)
if (!$product && is_numeric($slug)) {
    $stmt = db()->query(
        "SELECT p.*, c.name as category_name, c.slug as category_slug
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.id = ?",
        [(int)$slug]
    );
    $product = $stmt->fetch();
}

if (!$product) {
    setFlash('error', 'Sản phẩm không tồn tại');
    redirect('/products');
}

$productId = $product['id'];

// Lấy gallery images
$galleryImages = [];
try {
    $galleryImages = db()->query(
        "SELECT * FROM product_gallery WHERE product_id = ? ORDER BY sort_order ASC",
        [$productId]
    )->fetchAll();
} catch (Exception $e) {
    // Bảng chưa tồn tại
}

// Lấy các variants (gói thời hạn) của sản phẩm
$variants = [];
$mainVariant = null;
try {
    $variants = db()->query(
        "SELECT * FROM product_variants WHERE product_id = ? AND status = 'active' ORDER BY sort_order ASC",
        [$productId]
    )->fetchAll();

    // Tìm gói chính
    foreach ($variants as $v) {
        if ($v['is_main']) {
            $mainVariant = $v;
            break;
        }
    }

    // Nếu đang xem trang sản phẩm chính (không phải variant) và có variants
    if (!isset($product['variant_id']) && !empty($variants)) {
        // Nếu chỉ có 1 variant, redirect sang variant đó
        if (count($variants) === 1) {
            redirect('/' . $variants[0]['slug']);
        }
        // Nếu có nhiều variant và có gói chính, redirect sang gói chính
        elseif ($mainVariant) {
            redirect('/' . $mainVariant['slug']);
        }
    }
} catch (Exception $e) {
    // Table chưa tồn tại, bỏ qua
}

// DEPRECATED: Xử lý thêm vào giỏ hàng đã chuyển sang AJAX (xem JavaScript ở cuối file)
// Code này bị comment để tránh conflict với AJAX
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $variantId = isset($product['variant_id']) ? $product['variant_id'] : null;

    if ($product['stock_quantity'] > 0 && $product['status'] === 'active') {
        // Lưu thông tin bổ sung vào session
        if (!isset($_SESSION['product_additional_info'])) {
            $_SESSION['product_additional_info'] = [];
        }

        $additionalInfo = [];
        if (isset($_POST['customer_email'])) {
            $additionalInfo['email'] = trim($_POST['customer_email']);
        }
        if (isset($_POST['customer_username'])) {
            $additionalInfo['username'] = trim($_POST['customer_username']);
        }
        if (isset($_POST['customer_password'])) {
            $additionalInfo['password'] = trim($_POST['customer_password']);
        }

        if (!empty($additionalInfo)) {
            $_SESSION['product_additional_info'][$productId] = $additionalInfo;
        }

        addToCart($productId, $quantity, $variantId);
        setFlash('success', 'Đã thêm sản phẩm vào giỏ hàng');
        redirect('/cart.php');
    } else {
        setFlash('error', 'Sản phẩm đã hết hàng');
        redirect('/' . $slug);
    }
}
*/

// Xử lý mua ngay (KHÔNG thêm vào giỏ, dùng session riêng)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $variantId = isset($product['variant_id']) ? $product['variant_id'] : null;

    if ($product['stock_quantity'] > 0 && $product['status'] === 'active') {
        // Lưu thông tin bổ sung vào session
        if (!isset($_SESSION['product_additional_info'])) {
            $_SESSION['product_additional_info'] = [];
        }

        $additionalInfo = [];
        if (isset($_POST['customer_email'])) {
            $additionalInfo['email'] = trim($_POST['customer_email']);
        }
        if (isset($_POST['customer_username'])) {
            $additionalInfo['username'] = trim($_POST['customer_username']);
        }
        if (isset($_POST['customer_password'])) {
            $additionalInfo['password'] = trim($_POST['customer_password']);
        }

        if (!empty($additionalInfo)) {
            $_SESSION['product_additional_info'][$productId] = $additionalInfo;
        }

        // Lưu vào session "buy_now" thay vì cart
        $_SESSION['buy_now_product'] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity
        ];
        redirect('/checkout?direct=1');
    } else {
        setFlash('error', 'Sản phẩm đã hết hàng');
        redirect('/' . $slug);
    }
}

$pageTitle = $product['name'];
require_once __DIR__ . '/includes/header.php';

// Lấy đánh giá
$reviews = db()->query(
    "SELECT r.*, u.username, u.full_name
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     WHERE r.product_id = ? AND r.status = 'approved'
     ORDER BY r.created_at DESC
     LIMIT 10",
    [$productId]
)->fetchAll();

// Sản phẩm liên quan - lấy tối đa 20 sản phẩm
// Ưu tiên sản phẩm cùng category, sau đó lấy thêm từ category khác
$relatedProducts = db()->query(
    "SELECT p.*, c.name as category_name,
            (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
     ORDER BY RAND()
     LIMIT 20",
    [$product['category_id'], $productId]
)->fetchAll();

// Nếu không đủ 20 sản phẩm, lấy thêm từ các category khác
if (count($relatedProducts) < 20) {
    $currentCount = count($relatedProducts);
    $remaining = 20 - $currentCount;

    $moreProducts = db()->query(
        "SELECT p.*, c.name as category_name,
                (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.category_id != ? AND p.id != ? AND p.status = 'active'
         ORDER BY RAND()
         LIMIT ?",
        [$product['category_id'], $productId, $remaining]
    )->fetchAll();

    $relatedProducts = array_merge($relatedProducts, $moreProducts);
}

// Lấy categories cho tab navigation
$categories = db()->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC")->fetchAll();

// Lấy tất cả sản phẩm với variants cho tab "Khám phá sản phẩm"
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

// Xử lý tags (an toàn khi trường chưa tồn tại)
$tags = [];
if (isset($product['tags']) && !empty($product['tags'])) {
    $tags = explode(',', $product['tags']);
    $tags = array_map('trim', $tags);
}

// Check wishlist
$isWishlisted = false;
if (isLoggedIn()) {
    $isWishlisted = isInWishlist(getCurrentUser()['id'], $productId);
}

// Slug hiện tại để đánh dấu variant đang xem
$currentSlug = $product['display_slug'] ?? $product['slug'];

// Tạo mảng ảnh để hiển thị trong carousel
// Priority: variant_image > gallery_images > product_image
$displayImages = [];

// Nếu có variant image, thêm vào đầu tiên
if (!empty($product['variant_image'])) {
    $displayImages[] = $product['variant_image'];
}

// Thêm gallery images
if (!empty($galleryImages)) {
    foreach ($galleryImages as $gImg) {
        $displayImages[] = $gImg['image_path'];
    }
}

// Nếu không có variant image và không có gallery, dùng product image
if (empty($displayImages) && !empty($product['image'])) {
    $displayImages[] = $product['image'];
}

// Fallback nếu không có ảnh nào
if (empty($displayImages)) {
    $displayImages[] = '/public/images/placeholder-product.svg';
}
?>

<style>
.product-image-wrapper {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e9ecef;
    width: 100%;
    max-width: 450px;
    aspect-ratio: 374 / 184;
    min-height: 220px; /* Cố định chiều cao tối thiểu để tránh layout shift */
    position: relative;
    background: #f8f9fa; /* Background khi đang load ảnh */
}

.product-main-image {
    width: 100%;
    height: 100%;
    aspect-ratio: 374 / 184;
    object-fit: cover;
    display: block;
}

/* Carousel controls styling - Modern Design */
.carousel-control-prev,
.carousel-control-next {
    width: 44px;
    height: 44px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 50%;
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15),
                0 2px 4px rgba(0, 0, 0, 0.1);
}

.product-image-wrapper:hover .carousel-control-prev,
.product-image-wrapper:hover .carousel-control-next {
    opacity: 1;
}

.carousel-control-prev {
    left: 12px;
}

.carousel-control-next {
    right: 12px;
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    background: rgba(255, 255, 255, 1);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2),
                0 3px 6px rgba(0, 0, 0, 0.15);
    transform: translateY(-50%) scale(1.08);
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
    width: 22px;
    height: 22px;
    background-size: 100% 100%;
    filter: brightness(0) saturate(100%) invert(0%);
}

/* Carousel indicators - Modern Design */
.carousel-indicators {
    position: absolute;
    bottom: 16px;
    left: 0;
    right: 0;
    margin: 0;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    gap: 6px;
    z-index: 10;
}

.carousel-indicators button {
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

.carousel-indicators button:hover:not(.active) {
    background: rgba(255, 255, 255, 0.8) !important;
    transform: scale(1.15);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.25) !important;
}

.carousel-indicators button.active {
    background: rgba(255, 255, 255, 1) !important;
    width: 24px !important;
    height: 8px !important;
    border-radius: 4px !important;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3),
                0 1px 3px rgba(0, 0, 0, 0.2) !important;
}

.carousel-item {
    transition: transform 0.6s ease-in-out;
}

/* Responsive Design */

/* Tablet & Small Desktop (768px - 991px) */
@media (max-width: 991px) {
    .product-image-wrapper {
        width: 100%;
        max-width: 450px;
        margin: 0 auto;
    }

    .product-title {
        font-size: 22px;
    }

    /* Always show on tablet too */
    .carousel-control-prev,
    .carousel-control-next {
        width: 40px;
        height: 40px;
        opacity: 1 !important;
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        width: 20px;
        height: 20px;
    }
}

/* Mobile Large (576px - 767px) */
@media (max-width: 767px) {
    .product-image-wrapper {
        width: 100%;
        height: 0;
        max-width: 100%;
        padding-bottom: 49.2%; /* 184/374 = 49.2% */
        position: relative;
        aspect-ratio: unset;
        min-height: 0; /* Reset min-height trên mobile */
    }

    .product-image-wrapper .carousel,
    .product-image-wrapper .carousel-inner,
    .product-image-wrapper .carousel-item {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .product-image-wrapper img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }

    .product-title {
        font-size: 20px;
    }

    .product-info-card {
        padding: 12px;
        margin-top: 20px;
    }

    /* Always show carousel controls on mobile */
    .product-image-wrapper .carousel-control-prev,
    .product-image-wrapper .carousel-control-next {
        width: 36px;
        height: 36px;
        opacity: 1 !important;
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2),
                    0 1px 3px rgba(0, 0, 0, 0.15);
    }

    .product-image-wrapper .carousel-control-prev {
        left: 8px;
    }

    .product-image-wrapper .carousel-control-next {
        right: 8px;
    }

    .product-image-wrapper .carousel-control-prev:active,
    .product-image-wrapper .carousel-control-next:active {
        transform: translateY(-50%) scale(0.95);
        background: rgba(255, 255, 255, 1);
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        width: 18px;
        height: 18px;
    }

    /* Modern indicators on mobile */
    .carousel-indicators {
        bottom: 12px;
        gap: 5px;
    }

    .carousel-indicators button {
        width: 6px !important;
        height: 6px !important;
        background: rgba(255, 255, 255, 0.7) !important;
    }

    .carousel-indicators button.active {
        width: 20px !important;
        height: 6px !important;
        border-radius: 3px !important;
        background: rgba(255, 255, 255, 1) !important;
    }

    .btn-lg {
        font-size: 16px;
        padding: 10px 20px;
    }

    .related-product-body {
        padding: 10px 10px 12px 10px !important;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .related-product-title {
        font-size: 14px !important;
        line-height: 1.35 !important;
        font-weight: 600 !important;
        color: #1a1a1a !important;
    }

    .related-product-price {
        font-size: 17px !important;
        font-weight: 700 !important;
    }
}

/* Mobile Small (< 576px) */
@media (max-width: 575px) {
    .container {
        padding-left: 8px;
        padding-right: 8px;
    }

    .product-image-wrapper {
        width: 100%;
        height: 0;
        padding-bottom: 49.2%; /* 184/374 = 49.2% */
        margin-bottom: 12px;
        position: relative;
        aspect-ratio: unset;
        min-height: 0; /* Reset min-height trên mobile */
    }

    .product-image-wrapper .carousel,
    .product-image-wrapper .carousel-inner,
    .product-image-wrapper .carousel-item {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .product-image-wrapper img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }

    .product-title {
        font-size: 17px;
        line-height: 1.3;
    }

    .product-category {
        font-size: 11px;
        padding: 3px 8px;
    }

    .wishlist-btn-modern {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .product-info-card {
        padding: 12px;
        margin-top: 0;
    }

    .product-meta {
        padding: 10px;
        font-size: 12px;
        margin-bottom: 12px;
    }

    .product-meta-item {
        font-size: 12px;
    }

    .btn-lg {
        font-size: 14px;
        padding: 10px 16px;
        width: 100%;
    }

    .btn-outline-secondary {
        font-size: 13px;
        padding: 8px 12px;
    }

    h2, h3 {
        font-size: 1.15rem;
        margin-top: 1rem;
        margin-bottom: 0.75rem;
    }

    .card-body {
        padding: 10px;
    }

    /* Stack price and buttons vertically on very small screens */
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 10px;
    }

    .variant-badge {
        font-size: 12px;
        padding: 6px 10px;
        margin-bottom: 8px;
        display: inline-block;
    }

    /* Product price styling */
    .h3, h3 {
        font-size: 1.4rem !important;
    }

    .text-muted.text-decoration-line-through {
        font-size: 0.9rem;
    }

    /* Product description */
    .product-description {
        font-size: 13px;
        line-height: 1.5;
    }

    /* Variants section */
    .list-group-item {
        padding: 10px;
        font-size: 13px;
    }

    /* Related products in 2 columns */
    .col-md-3 {
        flex: 0 0 50%;
        max-width: 50%;
    }

    .row.g-4 {
        --bs-gutter-x: 8px;
        --bs-gutter-y: 12px;
    }

    .product-card .card-img-top {
        height: auto;
        aspect-ratio: 374 / 184;
        object-fit: cover;
        width: 100%;
    }

    .related-product-image {
        width: 100%;
        height: auto;
        aspect-ratio: 374 / 184;
        object-fit: cover;
    }

    .related-product-body {
        padding: 10px 10px 12px 10px !important;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .related-product-title {
        font-size: 14px !important;
        line-height: 1.35 !important;
        font-weight: 600 !important;
        color: #1a1a1a !important;
    }

    .related-product-price {
        font-size: 17px !important;
        font-weight: 700 !important;
    }
}

/* Extra Mobile Small (< 360px) */
@media (max-width: 359px) {
    .product-title {
        font-size: 15px;
    }

    .product-info-card {
        padding: 8px;
    }

    .btn-lg {
        font-size: 13px;
        padding: 8px 12px;
    }

    h2, h3 {
        font-size: 1rem;
    }
}

/* Animation keyframes */
@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

@keyframes cartShake {
    0%, 100% { transform: rotate(0deg); }
    10%, 30%, 50%, 70%, 90% { transform: rotate(-10deg); }
    20%, 40%, 60%, 80% { transform: rotate(10deg); }
}

.cart-shake {
    animation: cartShake 0.5s ease-in-out;
}
.product-info-card {
    background: #fbfbfd;
    border-radius: 18px;
    padding: 20px;
    border: 1px solid #d2d2d7;
    overflow: hidden;
}
.product-title {
    font-size: 24px;
    font-weight: 600;
    color: #1d1d1f;
    margin-bottom: 10px;
    line-height: 1.4;
    letter-spacing: -0.02em;
}
.product-category {
    display: inline-block;
    background: #f5f5f7;
    color: #1d1d1f;
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 0;
    flex-shrink: 0;
    border: 1px solid #d2d2d7;
}

/* Đảm bảo category và wishlist nằm cùng hàng */
.category-wishlist-row {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    gap: 10px;
}
.product-meta {
    background: #f5f5f7;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 16px;
    border: 1px solid #d2d2d7;
}
.product-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    font-size: 13px;
    color: #1d1d1f;
}
.product-price-box {
    background: #f5f5f7;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 20px;
    border: 1px solid #d2d2d7;
}
.product-price-main {
    font-size: 28px;
    font-weight: 700;
    color: #0071e3;
    margin-bottom: 6px;
}
.product-price-old {
    font-size: 16px;
    color: #86868b;
    text-decoration: line-through;
    margin-right: 8px;
}
.product-discount-badge {
    background: #ff3b30;
    color: white;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}
.variant-selector {
    margin-bottom: 24px;
}
.variant-selector label {
    font-size: 14px !important;
    color: #1d1d1f !important;
    font-weight: 600 !important;
    margin-bottom: 12px !important;
}
.variant-option {
    padding: 10px 20px;
    border-radius: 12px;
    border: 1px solid #d2d2d7;
    background: white;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: #1d1d1f;
    cursor: pointer;
    min-width: 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.variant-option:hover:not(:disabled) {
    border-color: #0071e3;
    background: #f5f9ff;
    color: #0071e3;
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0, 113, 227, 0.1);
}
.variant-option.active {
    background: #0071e3;
    border-color: #0071e3;
    color: white;
    box-shadow: 0 4px 10px rgba(0, 113, 227, 0.25);
    font-weight: 600;
    transform: translateY(-1px);
    pointer-events: none; /* Disable clicking */
    cursor: default;
}
.variant-option:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #f5f5f7;
    border-color: #e5e5ea;
    transform: none !important;
}
.product-main-image {
    transition: opacity 0.15s ease-in-out;
}
.action-button-primary {
    background: #0071e3;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
    color: white !important;
}
.action-button-primary:hover {
    background: #0077ED;
    color: white !important;
    box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3);
    transform: none !important;
}
.action-button-primary:active,
.action-button-primary:focus {
    background: #006edb !important;
    color: white !important;
    transform: none !important;
    box-shadow: 0 2px 6px rgba(0, 113, 227, 0.3);
}
.action-button-primary i {
    color: white !important;
}
.action-button-secondary {
    background: white;
    border: 1px solid #d2d2d7;
    color: #1d1d1f;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
}
.action-button-secondary:hover {
    background: #f5f5f7;
    color: #1d1d1f !important;
    border-color: #0071e3;
    transform: none !important;
}
.action-button-secondary:active,
.action-button-secondary:focus {
    background: #f5f5f7 !important;
    color: #1d1d1f !important;
    border-color: #0071e3;
    transform: none !important;
}
.tag-link {
    background: #f5f5f7;
    padding: 4px 10px;
    border-radius: 6px;
    text-decoration: none;
    color: #1d1d1f;
    font-size: 12px;
    transition: all 0.2s ease;
    display: inline-block;
    margin-right: 4px;
    margin-bottom: 4px;
    border: 1px solid #d2d2d7;
}
.tag-link:hover {
    background: #0071e3;
    color: white;
    border-color: #0071e3;
}
.wishlist-btn-modern {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    border: 1px solid #d2d2d7;
    background: white;
    color: #86868b;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}
.wishlist-btn-modern:hover, .wishlist-btn-modern.wishlisted {
    background: #ff3b30;
    color: white;
    border-color: #ff3b30;
}

/* Animation khi thêm vào yêu thích */
@keyframes heartBeat {
    0%, 100% { transform: scale(1); }
    10%, 30% { transform: scale(0.9); }
    20%, 40%, 60%, 80% { transform: scale(1.1); }
    50%, 70% { transform: scale(1.05); }
}

@keyframes heartPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

@keyframes ripple {
    0% {
        transform: scale(0);
        opacity: 1;
    }
    100% {
        transform: scale(2.5);
        opacity: 0;
    }
}

.wishlist-btn-modern.animating {
    animation: heartBeat 0.6s ease-in-out;
}

.wishlist-btn-modern.wishlisted.animating i {
    animation: heartPop 0.4s ease-in-out;
}

/* Ripple effect khi click */
.wishlist-btn-modern::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    border-radius: 50%;
    background: rgba(220, 53, 69, 0.3);
    pointer-events: none;
}

.wishlist-btn-modern.rippling::after {
    animation: ripple 0.6s ease-out;
}
.card.border {
    overflow: hidden;
}

.nav-pills {
    border-radius: 8px 8px 0 0;
}

.nav-pills .nav-link {
    color: #666;
    transition: all 0.2s ease;
}
.nav-pills .nav-link:hover {
    background: #e9ecef;
    color: #667eea;
}
.nav-pills .nav-link.active {
    background: #667eea;
    color: white;
}
.related-product-card {
    transition: box-shadow 0.2s ease;
}
.related-product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}
.related-product-image {
    width: 100%;
    height: auto;
    aspect-ratio: 374 / 184;
    object-fit: cover;
    display: block;
}
.related-product-body {
    padding: 8px;
}

/* Product Description Styling */
.product-description h1,
.product-description h2,
.product-description h3,
.product-description h4,
.product-description h5,
.product-description h6 {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    font-weight: 600;
    color: #333;
}
.product-description h1 { font-size: 1.8rem; }
.product-description h2 { font-size: 1.5rem; }
.product-description h3 { font-size: 1.3rem; }
.product-description h4 { font-size: 1.1rem; }
.product-description h5,
.product-description h6 { font-size: 1rem; }

.product-description p {
    margin-bottom: 1rem;
    line-height: 1.8;
}
.product-description ul,
.product-description ol {
    margin-bottom: 1rem;
    padding-left: 2rem;
}
.product-description li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}
.product-description img {
    max-width: 100%;
    height: auto;
    margin: 1.5rem 0;
    border-radius: 8px;
}
.product-description a {
    color: #667eea;
    text-decoration: none;
}
.product-description a:hover {
    text-decoration: underline;
}
.product-description strong {
    font-weight: 600;
}
.product-description .ql-align-center {
    text-align: center;
}
.product-description .ql-align-justify {
    text-align: justify;
}

/* Additional Info Box Styles */
.additional-info-box {
    background: #f5f5f7;
    border: 1px solid #d2d2d7;
    border-radius: 12px;
    padding: 18px;
}

.additional-info-title {
    font-size: 14px;
    font-weight: 600;
    color: #1d1d1f;
    margin-bottom: 12px;
}

.form-floating-custom {
    position: relative;
    margin-bottom: 12px;
}

.form-floating-custom input {
    width: 100%;
    padding: 12px 14px;
    font-size: 14px;
    border: 1px solid #d2d2d7;
    border-radius: 8px;
    background: white;
    transition: all 0.2s ease;
}

.form-floating-custom input:focus {
    border-color: #0071e3;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
}

.form-floating-custom input:focus + label,
.form-floating-custom input:not(:placeholder-shown) + label {
    transform: translateY(-24px) scale(0.85);
    background: #f5f5f7;
    padding: 0 6px;
    color: #0071e3;
}

.form-floating-custom label {
    position: absolute;
    left: 14px;
    top: 12px;
    font-size: 14px;
    color: #86868b;
    pointer-events: none;
    transition: all 0.2s ease;
    transform-origin: left center;
}

@media (max-width: 575px) {
    .additional-info-box {
        padding: 12px;
    }

    .additional-info-title {
        font-size: 13px;
        margin-bottom: 15px;
    }

    .form-floating-custom input {
        padding: 10px 12px;
        font-size: 13px;
    }

    .form-floating-custom label {
        font-size: 13px;
        left: 12px;
        top: 10px;
    }
}

/* Related Products Section Container */
.related-products-section {
    background: linear-gradient(135deg, #f8f9ff 0%, #fff5f8 100%);
    border-radius: 8px;
    padding: 3rem 1rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.15);
    position: relative;
    overflow: hidden;
}

.related-products-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

/* Related Products - Bestseller Shelf Styles */
.bestseller-shelf-wrapper {
    position: relative;
    overflow-x: hidden;
    overflow-y: visible;
    margin: 0 -12px;
    padding: 0 12px 15px 12px;
}

.bestseller-shelf {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    overflow-y: visible;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 16px 4px 20px 4px;
    margin: 0;
    min-height: 290px;
}

.bestseller-shelf::-webkit-scrollbar {
    display: none;
}

.bestseller-shelf-item {
    flex: 0 0 auto;
    width: 260px;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.bestseller-shelf-item:hover {
    transform: scale(1.06);
    z-index: 10;
    position: relative;
}

.bestseller-card {
    display: block;
    width: 100%;
    outline: none !important;
    text-decoration: none !important;
    border: none !important;
    outline-offset: 0 !important;
    -webkit-tap-highlight-color: transparent !important;
}

.bestseller-card:focus,
.bestseller-card:active,
.bestseller-card:hover,
.bestseller-card:focus-visible {
    outline: none !important;
    border: none !important;
    outline-offset: 0 !important;
    box-shadow: none !important;
}

.bestseller-card .card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid #e9ecef !important;
    border-radius: 19px;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    outline: none !important;
    outline-offset: 0 !important;
    -webkit-box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    -moz-box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.bestseller-card:hover .card,
.bestseller-card:focus .card,
.bestseller-card:active .card,
.bestseller-card:focus-visible .card {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
    background-color: white !important;
    border: 1px solid #e9ecef !important;
    outline: none !important;
    outline-offset: 0 !important;
}

.bestseller-card *,
.bestseller-card *:focus,
.bestseller-card *:active,
.bestseller-card *:hover,
.bestseller-card *:focus-visible {
    outline: none !important;
    outline-offset: 0 !important;
    -webkit-tap-highlight-color: transparent !important;
}

/* Remove Bootstrap focus ring */
.bestseller-card .card:focus,
.bestseller-card .card:focus-visible {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
    outline: none !important;
    border: 1px solid #e9ecef !important;
}

.bestseller-card .card-body {
    border-bottom-left-radius: 19px !important;
    border-bottom-right-radius: 19px !important;
    border-top-left-radius: 0 !important;
    border-top-right-radius: 0 !important;
    background: white;
    padding: 12px !important;
    transition: background-color 0.3s ease;
    outline: none !important;
}

.bestseller-card:hover .card-body {
    background: #f8f9ff !important;
    outline: none !important;
}

.bestseller-card .position-relative {
    outline: none !important;
    border: none !important;
}

.bestseller-card .card-img-top {
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
    aspect-ratio: 2.14;
    object-fit: cover;
    border-top-left-radius: 19px !important;
    border-top-right-radius: 19px !important;
    border-bottom-left-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    outline: none !important;
    border: none !important;
}

.bestseller-card:hover .card-img-top {
    transform: scale(1.05);
    opacity: 0.95;
    outline: none !important;
    border: none !important;
}

.bestseller-card .card-title {
    font-size: 14px !important;
    line-height: 1.4;
    font-weight: 600;
    color: #1a1a1a !important;
    transition: color 0.3s ease;
}

.bestseller-card:hover .card-title {
    color: #667eea !important;
}

.bestseller-card .text-danger {
    color: #dc3545 !important;
    font-weight: 700;
    transition: color 0.3s ease;
}

.bestseller-card:hover .text-danger {
    color: #ff1744 !important;
}

.bestseller-card .text-primary {
    color: #0d6efd !important;
    font-weight: 600;
    transition: color 0.3s ease;
}

.bestseller-card:hover .text-primary {
    color: #667eea !important;
}

.bestseller-card .text-muted {
    color: #6c757d !important;
    opacity: 1 !important;
}

.bestseller-card small.text-muted {
    color: #495057 !important;
    transition: color 0.3s ease;
}

.bestseller-card:hover small.text-muted:not(.text-decoration-line-through) {
    color: #667eea !important;
}

.bestseller-card small.text-muted.text-decoration-line-through {
    transition: opacity 0.3s ease;
}

.bestseller-card:hover small.text-muted.text-decoration-line-through {
    opacity: 0.7;
}

.section-title {
    font-size: 24px;
    font-weight: 600;
    color: #333;
}

/* Navigation Arrows */
.shelf-nav-arrows {
    display: flex;
    gap: 8px;
}

.shelf-nav-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid #ddd;
    background: white;
    color: #333;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
}

.shelf-nav-btn:hover:not(:disabled) {
    background: #f8f9fa;
    border-color: #ccc;
    transform: scale(1.05);
}

.shelf-nav-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.shelf-nav-btn:active:not(:disabled) {
    transform: scale(0.95);
}

@media (max-width: 768px) {
    .section-title {
        font-size: 20px;
    }

    .related-products-section {
        padding: 16px 12px;
        border-radius: 20px;
    }

    .bestseller-shelf {
        min-height: 270px;
        padding: 12px 4px 16px 4px;
    }

    .bestseller-shelf-item {
        width: 200px;
    }

    .bestseller-card .card-body {
        padding: 10px !important;
    }

    .bestseller-card .card-title {
        font-size: 13px !important;
    }

    .shelf-nav-arrows {
        display: none;
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

.tab-products-shelf .row > * {
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
    [data-section="featured-categories"] .d-flex.justify-content-between.align-items-center.mb-4 {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 16px;
    }

    /* Keep "Danh mục sản phẩm" and "Sản phẩm bán chạy" sections horizontal - title left, buttons right */
    [data-section="categories"] .d-flex.justify-content-between.align-items-center.mb-4,
    [data-section="best-sellers"] .d-flex.justify-content-between.align-items-center.mb-4 {
        flex-direction: row !important;
        align-items: center !important;
        gap: 12px;
    }

    /* Make title smaller on mobile */
    [data-section="categories"] .section-title,
    [data-section="best-sellers"] .section-title {
        font-size: 18px !important;
        flex: 1;
    }

    /* Buttons stay on the right */
    [data-section="categories"] .tabnav-navigation,
    [data-section="best-sellers"] .tabnav-navigation {
        flex-shrink: 0;
    }

    .tabnav-container {
        width: 100%;
        justify-content: space-between;
        position: relative;
    }

    .tabnav-wrapper {
        flex: 1;
        overflow: hidden;
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

    /* Mobile navigation buttons - smaller and inline */
    .tabnav-navigation {
        display: flex;
        gap: 8px;
        position: relative;
    }

    .tabnav-nav-btn {
        width: 32px;
        height: 32px;
        font-size: 12px;
        flex-shrink: 0;
    }

    .section-title {
        font-size: 20px;
    }
}

@media (max-width: 576px) {
    /* General section titles */
    .section-title {
        font-size: 18px;
    }

    /* Make "Danh mục sản phẩm" title even smaller on small screens */
    [data-section="categories"] .section-title {
        font-size: 16px !important;
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
    .tab-products-shelf .row > * {
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
    .tab-products-shelf .row > * {
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
}
</style>

<div class="container my-4">
    <div class="row g-2">
        <!-- Hình ảnh sản phẩm -->
        <div class="col-lg-auto">
            <div class="product-image-wrapper position-relative">
                <div id="productCarousel" class="carousel slide" data-bs-ride="false">
                    <div class="carousel-inner">
                        <?php foreach ($displayImages as $index => $image): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <img src="<?= e($image) ?>"
                                     class="d-block product-main-image" alt="<?= e($product['name']) ?>"
                                     onerror="this.onerror=null; this.src='/public/images/placeholder-product.svg';">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($displayImages) > 1): ?>
                        <!-- Previous button -->
                        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>

                        <!-- Next button -->
                        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>

                        <!-- Indicators -->
                        <div class="carousel-indicators">
                            <?php foreach ($displayImages as $index => $image): ?>
                                <button type="button" data-bs-target="#productCarousel"
                                        data-bs-slide-to="<?= $index ?>"
                                        <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?>
                                        aria-label="Slide <?= $index + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="col-lg">
            <div class="product-info-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h1 class="product-title mb-0 flex-grow-1 me-3" id="productName"><?= e($product['name']) ?></h1>
                    <?php if (isLoggedIn()): ?>
                        <button type="button" class="wishlist-btn-modern wishlist-btn <?= $isWishlisted ? 'wishlisted' : '' ?>"
                                data-product-id="<?= $productId ?>"
                                title="<?= $isWishlisted ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích' ?>"
                                style="flex-shrink: 0;">
                            <i class="<?= $isWishlisted ? 'fas' : 'far' ?> fa-heart"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <span class="product-category d-inline-block mb-3">
                    <i class="fas fa-folder-open"></i> <?= e($product['category_name']) ?>
                </span>

                <!-- Thông tin meta -->
                <div class="product-meta">
                    <div class="product-meta-item">
                        <i class="fas fa-boxes text-primary"></i>
                        <strong>Tình trạng:</strong>
                        <?php if ($product['stock_quantity'] > 0 && $product['status'] === 'active'): ?>
                            <span class="badge bg-success">Còn hàng</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Hết hàng</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-meta-item">
                        <i class="fas fa-barcode text-primary"></i>
                        <strong>SKU:</strong>
                        <code class="bg-white px-2 py-1 rounded"><?= e($currentSlug) ?></code>
                    </div>
                    <?php if (!empty($tags)): ?>
                    <div class="product-meta-item">
                        <i class="fas fa-tags text-primary"></i>
                        <strong>Tags:</strong>
                        <div class="d-inline-flex flex-wrap gap-1 ms-2">
                            <?php foreach ($tags as $tag): ?>
                                <a href="/products?tag=<?= urlencode($tag) ?>" class="tag-link"><?= e($tag) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Giá -->
                <div class="product-price-box" id="priceBox">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="product-price-main" id="salePrice"><?= formatMoney($product['sale_price'] ?: $product['price']) ?></div>
                            <?php if ($product['sale_price']): ?>
                                <div class="d-flex align-items-center" id="oldPriceSection">
                                    <span class="product-price-old" id="oldPrice"><?= formatMoney($product['price']) ?></span>
                                    <span class="product-discount-badge" id="discountBadge">
                                        Giảm <?= round((($product['price'] - $product['sale_price']) / $product['price']) * 100) ?>%
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Variants -->
                <?php if (!empty($variants)): ?>
                <div class="variant-selector">
                    <label class="d-block mb-2">
                        Chọn thời hạn
                    </label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($variants as $variant): ?>
                            <?php $isCurrentVariant = ($currentSlug === $variant['slug']); ?>
                            <button type="button"
                                    class="variant-option <?= $isCurrentVariant ? 'active' : '' ?>"
                                    data-variant-slug="<?= e($variant['slug']) ?>"
                                    data-variant-id="<?= e($variant['id']) ?>"
                                    onclick="loadVariant('<?= e($variant['slug']) ?>')">
                                <?= e($variant['name']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Form chung cho cả thông tin và nút hành động -->
                <form method="POST" id="productForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="variant_id" id="variantIdInput" value="<?= isset($product['variant_id']) ? $product['variant_id'] : '' ?>">
                    <?php
                    $deliveryType = $product['delivery_type'] ?? 'account';
                    ?>

                    <div id="additionalInfoContainer">
                    <?php if ($deliveryType === 'customer_account'): ?>
                    <!-- Nhập tài khoản khách hàng -->
                    <div class="mb-3 additional-info-box">
                        <h6 class="additional-info-title">Nhập thông tin bổ sung</h6>
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control"
                                           placeholder=""
                                           id="customer_username"
                                           name="customer_username"
                                           required>
                                    <label>Tài khoản <?= e($product['name']) ?></label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating-custom">
                                    <input type="password" class="form-control"
                                           placeholder=""
                                           id="customer_password"
                                           name="customer_password"
                                           required>
                                    <label>Mật khẩu</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php elseif ($deliveryType === 'email_only'): ?>
                    <!-- Nhập email để mời vào nhóm -->
                    <div class="mb-3 additional-info-box">
                        <h6 class="additional-info-title">Nhập thông tin bổ sung</h6>
                        <div class="form-floating-custom">
                            <input type="email" class="form-control"
                                   placeholder=""
                                   id="customer_email"
                                   name="customer_email"
                                   required>
                            <label>Email muốn mời vào nhóm</label>
                        </div>
                        <div class="form-text mt-2" style="font-size: 12px; color: #6c757d;">
                            <i class="fas fa-info-circle"></i> Sản phẩm mời tham gia vào nhóm gia đình/đội nhóm qua Email.
                        </div>
                    </div>
                    <?php endif; ?>
                    </div>

                    <!-- Nút hành động -->
                    <div class="row g-2" id="actionButtons">
                        <?php if ($product['stock_quantity'] <= 0 || $product['status'] !== 'active'): ?>
                            <div class="col-12">
                                <button type="button" class="btn btn-secondary w-100" disabled
                                        style="padding: 10px; border-radius: 6px; font-size: 14px; font-weight: 600;">
                                    <i class="fas fa-ban"></i> Tạm hết hàng
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="col-md-7">
                                <button type="submit" name="buy_now" class="btn action-button-primary w-100">
                                    <i class="fas fa-bolt"></i> Mua ngay
                                </button>
                            </div>
                            <div class="col-md-5">
                                <button type="submit" name="add_to_cart" class="btn action-button-secondary w-100">
                                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Mô tả và đánh giá -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border" style="border-radius: 8px;">
                <ul class="nav nav-pills p-3 bg-light" role="tablist" style="border-bottom: 1px solid #dee2e6;">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#description"
                           style="border-radius: 6px; font-weight: 500; padding: 8px 16px; font-size: 14px;">
                            <i class="fas fa-align-left"></i> Mô tả
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="nav-link" data-bs-toggle="tab" href="#reviews"
                           style="border-radius: 6px; font-weight: 500; padding: 8px 16px; font-size: 14px;">
                            <i class="fas fa-star"></i> Đánh giá (<?= count($reviews) ?>)
                        </a>
                    </li>
                </ul>

                <div class="tab-content p-3">
                    <div id="description" class="tab-pane fade show active">
                        <h6 class="mb-3" style="font-weight: 600; color: #333;">Thông tin chi tiết</h6>
                        <?php if ($product['description']): ?>
                            <div class="product-description" style="color: #555; line-height: 1.6; font-size: 14px;">
                                <?= $product['description'] ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">Chưa có mô tả chi tiết.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="reviews" class="tab-pane fade">
                        <h6 class="mb-3" style="font-weight: 600; color: #333;">
                            <i class="fas fa-star text-warning me-2"></i>Đánh giá từ khách hàng
                        </h6>
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                            </div>
                        <?php else: ?>
                            <div class="reviews-list">
                                <?php foreach ($reviews as $review): ?>
                                    <?php
                                    // Ẩn username: 2 ký tự đầu + *** + 1 ký tự cuối
                                    $username = $review['username'];
                                    $usernameLength = mb_strlen($username);
                                    if ($usernameLength <= 3) {
                                        $maskedUsername = $username; // Quá ngắn, giữ nguyên
                                    } else {
                                        $maskedUsername = mb_substr($username, 0, 2) . '***' . mb_substr($username, -1);
                                    }
                                    ?>
                                    <div class="review-item mb-3 p-3" style="background: #fff; border-radius: 8px; border: 1px solid #e9ecef; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                        <div class="d-flex align-items-start mb-2">
                                            <!-- Avatar -->
                                            <div class="me-3">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                                     style="width: 48px; height: 48px; font-size: 22px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            </div>

                                            <!-- Review content -->
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <div>
                                                        <strong style="color: #333; font-size: 15px;">
                                                            <?= e($maskedUsername) ?>
                                                        </strong>
                                                        <div class="text-warning mt-1" style="font-size: 14px;">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="fas fa-star <?= $i <= $review['rating'] ? '' : 'text-muted' ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted" style="font-size: 13px;">
                                                        <i class="far fa-clock me-1"></i><?= date('d/m/Y', strtotime($review['created_at'])) ?>
                                                    </small>
                                                </div>

                                                <?php if ($review['comment']): ?>
                                                    <p class="mb-2 mt-2" style="color: #555; line-height: 1.6; font-size: 14px;">
                                                        <?= nl2br(e($review['comment'])) ?>
                                                    </p>
                                                <?php endif; ?>

                                                <!-- Admin reply -->
                                                <?php if ($review['admin_reply']): ?>
                                                    <div class="mt-3 p-3" style="background: #f8f9fa; border-left: 3px solid #0d6efd; border-radius: 4px;">
                                                        <div class="d-flex align-items-start">
                                                            <i class="fas fa-reply text-primary me-2 mt-1"></i>
                                                            <div>
                                                                <strong class="text-primary" style="font-size: 13px;">Phản hồi từ Shop:</strong>
                                                                <p class="mb-0 mt-1" style="color: #555; line-height: 1.6; font-size: 13px;">
                                                                    <?= nl2br(e($review['admin_reply'])) ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Khám phá sản phẩm -->
    <section class="py-5 lazy-section" data-section="featured-categories" style="background: linear-gradient(135deg, #fafbff 0%, #f5f7fa 100%); margin-top: 3rem; border-radius: 20px;">
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
                                    <button type="button" role="tab" class="tabnav-link active" data-category="all" aria-selected="true">
                                        <span>Best seller</span>
                                    </button>
                                </li>
                                <?php
                                // Các danh mục chính để hiển thị trong tab - thứ tự tùy chỉnh
                                $featuredCategoryOrder = ['Cloud Storage', 'Game', 'Giải trí'];

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
                                    <button type="button" role="tab" class="tabnav-link" data-category="<?= $cat['id'] ?>">
                                        <span><?= e($cat['name']) ?></span>
                                    </button>
                                </li>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                                <li class="tabnav-item" role="presentation">
                                    <button type="button" role="tab" class="tabnav-link" data-category="related">
                                        <span>Liên quan</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                    <!-- Navigation Arrows -->
                    <div class="tabnav-navigation">
                        <button class="tabnav-nav-btn tabnav-nav-prev" id="tabProductsPrev" onclick="scrollTabProducts('prev')">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="tabnav-nav-btn tabnav-nav-next" id="tabProductsNext" onclick="scrollTabProducts('next')">
                            <i class="fas fa-chevron-right"></i>
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
</div>

<script>
// Wishlist functionality
document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const isWishlisted = this.classList.contains('wishlisted');
        const action = isWishlisted ? 'remove' : 'add';

        // Thêm animation ngay lập tức
        this.classList.add('rippling', 'animating');

        fetch('/api/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=' + action + '&product_id=' + productId + '&csrf_token=<?= e($csrfToken) ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.classList.toggle('wishlisted');
                const icon = this.querySelector('i');
                icon.classList.toggle('far');
                icon.classList.toggle('fas');
                this.title = data.is_wishlisted ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích';
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }

            // Xóa animation class sau khi animation hoàn tất
            setTimeout(() => {
                this.classList.remove('rippling', 'animating');
            }, 600);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');

            // Xóa animation class nếu có lỗi
            setTimeout(() => {
                this.classList.remove('rippling', 'animating');
            }, 600);
        });
    });
});

// AJAX variant loader
function loadVariant(slug) {
    // Hiển thị loading state
    const buttons = document.querySelectorAll('.variant-option');
    buttons.forEach(btn => btn.disabled = true);

    // Gọi API
    fetch(`/api/get-variant.php?slug=${encodeURIComponent(slug)}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;

                // Cập nhật tên sản phẩm
                document.getElementById('productName').textContent = data.name;

                // Cập nhật giá
                document.getElementById('salePrice').textContent = data.formatted_sale_price || data.formatted_price;

                // Cập nhật giá cũ và discount badge
                const oldPriceSection = document.getElementById('oldPriceSection');
                if (data.sale_price) {
                    if (!oldPriceSection) {
                        // Tạo mới nếu chưa có
                        const priceBox = document.getElementById('priceBox');
                        const newSection = document.createElement('div');
                        newSection.id = 'oldPriceSection';
                        newSection.className = 'd-flex align-items-center';
                        newSection.innerHTML = `
                            <span class="product-price-old" id="oldPrice">${data.formatted_price}</span>
                            <span class="product-discount-badge" id="discountBadge">
                                Giảm ${data.discount_percent}%
                            </span>
                        `;
                        priceBox.querySelector('div > div').appendChild(newSection);
                    } else {
                        // Cập nhật nếu đã có
                        document.getElementById('oldPrice').textContent = data.formatted_price;
                        document.getElementById('discountBadge').textContent = `Giảm ${data.discount_percent}%`;
                    }
                } else {
                    // Xóa section nếu không có sale
                    if (oldPriceSection) {
                        oldPriceSection.remove();
                    }
                }

                // Cập nhật ảnh carousel (chỉ khi variant có ảnh riêng)
                if (data.has_own_image && data.image) {
                    updateCarouselImage(data.image);
                }

                // Cập nhật variant_id trong hidden input
                document.getElementById('variantIdInput').value = data.variant_id;

                // Cập nhật form input dựa vào delivery_type
                updateAdditionalInfoForm(data.delivery_type, data.name);

                // Cập nhật nút hành động
                updateActionButtons(data.stock_quantity, data.status);

                // Cập nhật active state cho buttons
                buttons.forEach(btn => {
                    if (btn.dataset.variantSlug === slug) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                    btn.disabled = false;
                });

                // Cập nhật URL không reload trang (History API)
                window.history.pushState({slug: slug}, '', data.url);

            } else {
                alert('Không thể tải thông tin gói. Vui lòng thử lại.');
                buttons.forEach(btn => btn.disabled = false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
            buttons.forEach(btn => btn.disabled = false);
        });
}

// Cập nhật ảnh carousel
function updateCarouselImage(imageUrl) {
    const carousel = document.querySelector('#productCarousel .carousel-inner');
    const firstItem = carousel.querySelector('.carousel-item');
    const img = firstItem.querySelector('img');

    // Smooth transition
    img.style.opacity = '0';
    setTimeout(() => {
        img.src = imageUrl;
        img.onerror = function() {
            // Fallback nếu ảnh không load được
            this.src = '/public/images/placeholder-product.svg';
        };
        img.style.opacity = '1';
    }, 150);
}

// Cập nhật form nhập thông tin bổ sung
function updateAdditionalInfoForm(deliveryType, productName) {
    const container = document.getElementById('additionalInfoContainer');

    if (deliveryType === 'customer_account') {
        container.innerHTML = `
            <div class="mb-3 additional-info-box">
                <h6 class="additional-info-title">Nhập thông tin bổ sung</h6>
                <div class="row g-2">
                    <div class="col-12">
                        <div class="form-floating-custom">
                            <input type="text" class="form-control"
                                   placeholder=""
                                   id="customer_username"
                                   name="customer_username"
                                   required>
                            <label>Tài khoản ${productName}</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating-custom">
                            <input type="password" class="form-control"
                                   placeholder=""
                                   id="customer_password"
                                   name="customer_password"
                                   required>
                            <label>Mật khẩu</label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (deliveryType === 'email_only') {
        container.innerHTML = `
            <div class="mb-3 additional-info-box">
                <h6 class="additional-info-title">Nhập thông tin bổ sung</h6>
                <div class="form-floating-custom">
                    <input type="email" class="form-control"
                           placeholder=""
                           id="customer_email"
                           name="customer_email"
                           required>
                    <label>Email muốn mời vào nhóm</label>
                </div>
                <div class="form-text mt-2" style="font-size: 12px; color: #6c757d;">
                    <i class="fas fa-info-circle"></i> Sản phẩm mời tham gia vào nhóm gia đình/đội nhóm qua Email.
                </div>
            </div>
        `;
    } else {
        container.innerHTML = '';
    }
}

// Cập nhật nút hành động
function updateActionButtons(stockQuantity, status) {
    const container = document.getElementById('actionButtons');

    if (stockQuantity <= 0 || status !== 'active') {
        container.innerHTML = `
            <div class="col-12">
                <button type="button" class="btn btn-secondary w-100" disabled
                        style="padding: 10px; border-radius: 6px; font-size: 14px; font-weight: 600;">
                    <i class="fas fa-ban"></i> Tạm hết hàng
                </button>
            </div>
        `;
    } else {
        container.innerHTML = `
            <div class="col-md-7">
                <button type="submit" name="buy_now" class="btn action-button-primary w-100">
                    <i class="fas fa-bolt"></i> Mua ngay
                </button>
            </div>
            <div class="col-md-5">
                <button type="submit" name="add_to_cart" class="btn action-button-secondary w-100">
                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                </button>
            </div>
        `;
    }
}

// Xử lý nút back/forward của browser
window.addEventListener('popstate', function(event) {
    if (event.state && event.state.slug) {
        loadVariant(event.state.slug);
    }
});

// Xử lý form thêm giỏ hàng với AJAX
document.getElementById('productForm').addEventListener('submit', function(e) {
    // Chỉ chặn khi click nút "Thêm vào giỏ"
    if (e.submitter && e.submitter.name === 'add_to_cart') {
        e.preventDefault();

        // Thu thập dữ liệu form
        const formData = new FormData(this);
        formData.append('product_id', <?= $productId ?>);

        // Disable button để tránh spam
        const addToCartBtn = e.submitter;
        const originalText = addToCartBtn.innerHTML;
        addToCartBtn.disabled = true;
        addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thêm...';

        // Gửi AJAX request
        fetch('/api/add-to-cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {

            if (data.success) {
                // Hiệu ứng bay vào giỏ hàng
                flyToCart();

                // Cập nhật số lượng giỏ hàng
                updateCartCount(data.cart_count);

                // Refresh nội dung cart dropdown
                refreshCartDropdown();

                // Hiển thị thông báo thành công
                showToast('Đã thêm vào giỏ hàng!', 'success');

                // Reset button
                addToCartBtn.disabled = false;
                addToCartBtn.innerHTML = originalText;
            } else {
                console.error('API Error:', data.message);
                alert(data.message || 'Có lỗi xảy ra');
                addToCartBtn.disabled = false;
                addToCartBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
            addToCartBtn.disabled = false;
            addToCartBtn.innerHTML = originalText;
        });
    }
    // Nếu là nút "Mua ngay" thì để form submit bình thường
});

// Hiệu ứng bay vào giỏ hàng
function flyToCart() {
    const productImage = document.querySelector('.product-main-image');

    // Tìm icon giỏ hàng (ưu tiên mobile nếu đang ở mobile)
    let cartIcon = document.querySelector('.mobile-cart-icon .fa-shopping-cart');

    // Nếu không tìm thấy hoặc đang ẩn, dùng icon desktop
    if (!cartIcon || window.getComputedStyle(cartIcon.closest('.mobile-cart-icon')).display === 'none') {
        cartIcon = document.querySelector('#cartDropdown .fa-shopping-cart');
    }

    if (!productImage || !cartIcon) return;

    // Clone ảnh sản phẩm
    const flyingImage = productImage.cloneNode(true);
    flyingImage.style.cssText = `
        position: fixed;
        width: 80px;
        height: 40px;
        z-index: 9999;
        transition: all 0.8s cubic-bezier(0.5, 0, 0.5, 1);
        pointer-events: none;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    `;

    // Vị trí bắt đầu (GIỮA ảnh sản phẩm)
    const startRect = productImage.getBoundingClientRect();
    const startCenterX = startRect.left + (startRect.width / 2) - 40; // 40 = width/2
    const startCenterY = startRect.top + (startRect.height / 2) - 20; // 20 = height/2

    flyingImage.style.top = startCenterY + 'px';
    flyingImage.style.left = startCenterX + 'px';

    document.body.appendChild(flyingImage);

    // Vị trí kết thúc (icon giỏ hàng)
    const endRect = cartIcon.getBoundingClientRect();

    // Trigger animation
    setTimeout(() => {
        flyingImage.style.top = endRect.top + 'px';
        flyingImage.style.left = endRect.left + 'px';
        flyingImage.style.width = '20px';
        flyingImage.style.height = '20px';
        flyingImage.style.opacity = '0';
    }, 10);

    // Xóa element sau khi animation xong
    setTimeout(() => {
        flyingImage.remove();

        // Thêm hiệu ứng shake cho giỏ hàng
        cartIcon.classList.add('cart-shake');
        setTimeout(() => {
            cartIcon.classList.remove('cart-shake');
        }, 500);
    }, 800);
}

// Refresh nội dung cart dropdown
function refreshCartDropdown() {
    fetch('/api/get-cart-dropdown.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Tìm ul.cart-dropdown trực tiếp
                const cartItemsContainer = document.querySelector('ul.cart-dropdown');
                if (!cartItemsContainer) {
                    console.error('Cart dropdown UL not found');
                    return;
                }

                // Cập nhật HTML của cart dropdown
                const itemsPreview = data.items_preview;
                const totalAmount = data.total_amount;
                const cartCount = data.cart_count;

                // Xây dựng HTML mới
                let html = `
                    <!-- Cart Header -->
                    <li class="dropdown-header py-3 border-bottom" style="background: linear-gradient(135deg, #667eea 0%, #7387df 100%); color: white; border-radius: 12px 12px 0 0;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</span>
                            <span class="badge bg-light text-primary">${cartCount} sản phẩm</span>
                        </div>
                    </li>
                `;

                if (itemsPreview.length === 0) {
                    html += `
                        <li class="text-center text-muted py-5">
                            <i class="fas fa-shopping-cart fa-3x mb-3 opacity-50"></i>
                            <p class="mb-0">Giỏ hàng trống</p>
                            <a href="/products" class="btn btn-primary btn-sm mt-3">
                                <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                            </a>
                        </li>
                    `;
                } else {
                    itemsPreview.forEach(item => {
                        const price = item.price;
                        const formattedPrice = new Intl.NumberFormat('vi-VN', {
                            style: 'currency',
                            currency: 'VND'
                        }).format(price);

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
                        <!-- Total -->
                        <li class="px-3 py-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold">Tổng tạm tính:</span>
                                <span class="text-primary fw-bold fs-5">${formattedTotal}</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="/cart" style="background-color: #0071e3 !important; color: white !important; border: none !important; padding: 10px 16px !important; border-radius: 1000px !important; text-decoration: none !important; display: inline-block !important; flex: 1 1 0% !important; text-align: center !important; font-size: 13px !important; font-weight: 600 !important; transition: none !important;">
                                    <i class="fas fa-eye"></i> Xem giỏ hàng
                                </a>
                                <a href="/checkout" style="background-color: #34c759 !important; color: white !important; border: none !important; padding: 10px 16px !important; border-radius: 1000px !important; text-decoration: none !important; display: inline-block !important; flex: 1 1 0% !important; text-align: center !important; font-size: 13px !important; font-weight: 600 !important; transition: none !important;">
                                    <i class="fas fa-credit-card"></i> Thanh toán
                                </a>
                            </div>
                        </li>
                    `;
                }

                cartItemsContainer.innerHTML = html;

                // Force reflow to ensure CSS is applied
                void cartItemsContainer.offsetHeight;
            }
        })
        .catch(error => {
            console.error('Error refreshing cart dropdown:', error);
        });
}

// Cập nhật số lượng giỏ hàng
function updateCartCount(count) {
    const badge = document.querySelector('#cartDropdown .icon-badge');
    const headerBadge = document.querySelector('.cart-dropdown .badge');

    if (count > 0) {
        const displayCount = count > 9 ? '9+' : count;

        if (badge) {
            badge.textContent = displayCount;
        } else {
            // Tạo badge mới nếu chưa có
            const iconCircle = document.querySelector('#cartDropdown .icon-circle');
            if (iconCircle) {
                const newBadge = document.createElement('span');
                newBadge.className = 'icon-badge';
                newBadge.textContent = displayCount;
                iconCircle.appendChild(newBadge);
            }
        }

        if (headerBadge) {
            headerBadge.textContent = count + ' sản phẩm';
        }
    }
}

// Hiển thị toast notification
function showToast(message, type = 'success') {
    // Tạo toast element
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 999;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInRight 0.3s ease-out;
    `;

    toast.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(toast);

    // Auto remove sau 3 giây
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// TabNav functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabNav = document.getElementById('categoryTabNav');
    if (!tabNav) return;

    const tabLinks = tabNav.querySelectorAll('.tabnav-link');
    const indicator = tabNav.querySelector('.tabnav-indicator');
    const productGrid = document.getElementById('tabProductGrid');

    // Embed products data from PHP
    const allProducts = <?= json_encode($allTabProducts, JSON_UNESCAPED_UNICODE) ?>;
    const relatedProducts = <?= json_encode($relatedProducts, JSON_UNESCAPED_UNICODE) ?>;

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
            } else if (category === 'related') {
                filteredProducts = relatedProducts.slice(0, 12);
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
            // Use variant price if available, otherwise use product price - Convert to number
            const displayPrice = parseFloat((product.variant_price != null && product.variant_price > 0) ? product.variant_price : product.price);
            const displaySalePrice = (product.variant_sale_price != null && product.variant_sale_price > 0) ? parseFloat(product.variant_sale_price) : (product.sale_price != null && product.sale_price > 0 ? parseFloat(product.sale_price) : null);

            const hasDiscount = displaySalePrice && displaySalePrice < displayPrice;
            const price = hasDiscount ? displaySalePrice : displayPrice;
            const oldPrice = hasDiscount ? displayPrice : null;
            const discountPercent = hasDiscount ? Math.round(((oldPrice - price) / oldPrice) * 100) : 0;
            const savings = hasDiscount ? (oldPrice - price) : 0;

            // Use variant image if available
            let displayImage = product.variant_image || product.first_gallery_image || product.image || '/public/images/placeholder-product.svg';
            let displayTitle = product.variant_title || (product.variant_name ? product.name + ' ' + product.variant_name : product.name);
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
        tab.addEventListener('click', function() {
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
