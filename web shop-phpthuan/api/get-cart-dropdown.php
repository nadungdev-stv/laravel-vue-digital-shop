<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
initSession();

try {
    $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
    $sessionId = session_id();

    $cartItemsWithDetails = [];
    $totalAmount = 0;

    // Lấy thông tin chi tiết sản phẩm từ database bao gồm cả variant
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
            'image' => $image,
            'quantity' => $quantity,
            'product_url' => $productUrl
        ];

        $totalAmount += $price * $quantity;
    }

    // Tính tổng số lượng sản phẩm
    $cartCount = 0;
    foreach ($cartItemsWithDetails as $item) {
        $cartCount += $item['quantity'];
    }

    echo json_encode([
        'success' => true,
        'items' => $cartItemsWithDetails,
        'total_amount' => $totalAmount,
        'cart_count' => $cartCount,
        'items_preview' => array_slice($cartItemsWithDetails, 0, 5) // Lấy tối đa 5 items
    ]);

} catch (Exception $e) {
    error_log("Get cart dropdown error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy giỏ hàng'
    ]);
}
