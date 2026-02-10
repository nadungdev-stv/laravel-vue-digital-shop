<?php
/**
 * API endpoint để thêm sản phẩm vào giỏ hàng qua AJAX
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

initSession();

// CSRF Protection
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Token bảo mật không hợp lệ']);
    exit;
}

try {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

    // Xử lý variant_id: có thể là số, empty string, hoặc null
    $variantId = null;
    if (isset($_POST['variant_id']) && $_POST['variant_id'] !== '' && $_POST['variant_id'] !== '0') {
        $variantId = (int)$_POST['variant_id'];
    }

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Product ID không hợp lệ']);
        exit;
    }

    // Kiểm tra sản phẩm có tồn tại và còn hàng
    $product = db()->query(
        "SELECT * FROM products WHERE id = ?",
        [$productId]
    )->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    // Nếu có variant, kiểm tra variant
    if ($variantId) {
        $variant = db()->query(
            "SELECT * FROM product_variants WHERE id = ? AND product_id = ?",
            [$variantId, $productId]
        )->fetch();

        if (!$variant || $variant['stock_quantity'] <= 0 || $variant['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Gói sản phẩm không khả dụng']);
            exit;
        }
    } else {
        // Kiểm tra product stock
        if ($product['stock_quantity'] <= 0 || $product['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng']);
            exit;
        }
    }

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

    // Thêm vào giỏ hàng
    addToCart($productId, $quantity, $variantId);

    // Tính tổng số lượng sản phẩm trong giỏ (từ database)
    $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
    $sessionId = session_id();

    if ($userId) {
        $cartStmt = db()->query(
            "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?",
            [$userId]
        );
    } else {
        $cartStmt = db()->query(
            "SELECT SUM(quantity) as total FROM cart WHERE session_id = ? AND user_id IS NULL",
            [$sessionId]
        );
    }

    $result = $cartStmt->fetch();
    $cartCount = $result['total'] ? (int)$result['total'] : 0;

    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm vào giỏ hàng',
        'cart_count' => $cartCount,
        'debug' => [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity
        ]
    ]);

} catch (Exception $e) {
    error_log("Add to cart API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại.']);
}
