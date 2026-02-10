<?php
/**
 * API endpoint để lấy thông tin variant
 * Dùng cho AJAX khi người dùng chọn thời hạn
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

// Chỉ chấp nhận GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Lấy slug từ query parameter
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(['error' => 'Slug is required']);
    exit;
}

try {
    // Tìm variant theo slug
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

    if (!$variant) {
        http_response_code(404);
        echo json_encode(['error' => 'Variant not found']);
        exit;
    }

    // Xác định ảnh hiển thị
    $displayImage = null;
    $hasOwnImage = false;

    if (!empty($variant['variant_image'])) {
        // Variant có ảnh riêng
        $displayImage = $variant['variant_image'];
        $hasOwnImage = true;
    } else {
        // Variant không có ảnh riêng → lấy ảnh đầu tiên từ gallery hoặc product image
        try {
            $galleryImages = db()->query(
                "SELECT image_path FROM product_gallery WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1",
                [$variant['id']]
            )->fetchAll();

            if (!empty($galleryImages)) {
                $displayImage = $galleryImages[0]['image_path'];
            } else {
                $displayImage = $variant['image']; // Fallback to product image
            }
        } catch (Exception $e) {
            $displayImage = $variant['image']; // Fallback to product image
        }
    }

    // Chuẩn bị dữ liệu trả về
    $response = [
        'success' => true,
        'data' => [
            'variant_id' => $variant['variant_id'],
            'variant_name' => $variant['variant_name'],
            'variant_slug' => $variant['variant_slug'],
            'product_id' => $variant['id'],
            'name' => !empty($variant['variant_title']) ? $variant['variant_title'] : ($variant['name'] . ' ' . $variant['variant_name']),
            'image' => $displayImage,
            'has_own_image' => $hasOwnImage, // Flag để JS biết có cần update ảnh không
            'price' => $variant['variant_price'],
            'sale_price' => $variant['variant_sale_price'],
            'stock_quantity' => $variant['variant_stock'],
            'duration' => $variant['variant_duration'] ?: $variant['duration'],
            'delivery_type' => !empty($variant['variant_delivery_type']) ? $variant['variant_delivery_type'] : $variant['delivery_type'],
            'status' => $variant['status'],

            // Format giá để hiển thị
            'formatted_price' => formatMoney($variant['variant_price']),
            'formatted_sale_price' => $variant['variant_sale_price'] ? formatMoney($variant['variant_sale_price']) : null,
            'discount_percent' => $variant['variant_sale_price']
                ? round((($variant['variant_price'] - $variant['variant_sale_price']) / $variant['variant_price']) * 100)
                : 0,

            // URL cho history API
            'url' => '/' . $variant['variant_slug']
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}
