<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

initSession();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// CSRF Protection
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Token bảo mật không hợp lệ']);
    exit;
}

$action = $_POST['action'] ?? '';

// Tính subtotal từ giỏ hàng
$userId = isLoggedIn() ? getCurrentUser()['id'] : null;
$sessionId = session_id();

try {
    if ($userId) {
        $stmt = db()->query(
            "SELECT c.*, p.price, p.sale_price, v.price as variant_price, v.sale_price as variant_sale_price
             FROM cart c
             JOIN products p ON c.product_id = p.id
             LEFT JOIN product_variants v ON c.variant_id = v.id
             WHERE c.user_id = ? AND p.status = 'active'",
            [$userId]
        );
    } else {
        $stmt = db()->query(
            "SELECT c.*, p.price, p.sale_price, v.price as variant_price, v.sale_price as variant_sale_price
             FROM cart c
             JOIN products p ON c.product_id = p.id
             LEFT JOIN product_variants v ON c.variant_id = v.id
             WHERE c.session_id = ? AND c.user_id IS NULL AND p.status = 'active'",
            [$sessionId]
        );
    }

    $subtotal = 0;
    while ($row = $stmt->fetch()) {
        if ($row['variant_id']) {
            $price = $row['variant_sale_price'] ?? $row['variant_price'];
        } else {
            $price = $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'];
        }
        $subtotal += $price * $row['quantity'];
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tính tổng giỏ hàng']);
    exit;
}

if ($action === 'apply') {
    $couponCode = trim($_POST['coupon_code'] ?? '');

    if (empty($couponCode)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá']);
        exit;
    }

    try {
        $stmt = db()->query(
            "SELECT * FROM coupons WHERE code = ? AND status = 'active'
             AND (start_date IS NULL OR start_date <= NOW())
             AND (end_date IS NULL OR end_date >= NOW())
             AND (usage_limit IS NULL OR used_count < usage_limit)",
            [$couponCode]
        );
        $coupon = $stmt->fetch();

        if (!$coupon) {
            echo json_encode(['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn']);
            exit;
        }

        if ($subtotal < $coupon['min_order_amount']) {
            echo json_encode([
                'success' => false,
                'message' => 'Đơn hàng tối thiểu ' . formatMoney($coupon['min_order_amount']) . ' để sử dụng mã này'
            ]);
            exit;
        }

        // Tính discount
        $discount = 0;
        if ($coupon['type'] === 'percent') {
            $discount = ($subtotal * $coupon['value']) / 100;
            if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        } else {
            $discount = $coupon['value'];
        }

        $total = $subtotal - $discount;
        $_SESSION['applied_coupon'] = $coupon;

        echo json_encode([
            'success' => true,
            'message' => 'Áp dụng mã giảm giá thành công!',
            'coupon' => [
                'code' => $coupon['code'],
                'type' => $coupon['type'],
                'value' => $coupon['value'],
                'discount' => $discount
            ],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'subtotal_formatted' => formatMoney($subtotal),
            'discount_formatted' => formatMoney($discount),
            'total_formatted' => formatMoney($total)
        ]);
        exit;

    } catch (Exception $e) {
        error_log("Apply coupon error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi áp dụng mã giảm giá']);
        exit;
    }

} elseif ($action === 'remove') {
    unset($_SESSION['applied_coupon']);

    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa mã giảm giá',
        'subtotal' => $subtotal,
        'discount' => 0,
        'total' => $subtotal,
        'subtotal_formatted' => formatMoney($subtotal),
        'discount_formatted' => formatMoney(0),
        'total_formatted' => formatMoney($subtotal)
    ]);
    exit;

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}
