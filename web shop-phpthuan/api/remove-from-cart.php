<?php
/**
 * API endpoint để xóa sản phẩm khỏi giỏ hàng qua AJAX
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
    $variantId = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Product ID không hợp lệ']);
        exit;
    }

    $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
    $sessionId = session_id();

    // Xóa sản phẩm khỏi giỏ hàng
    if ($userId) {
        // User đã đăng nhập
        if ($variantId) {
            db()->query(
                "DELETE FROM cart WHERE user_id = ? AND product_id = ? AND variant_id = ?",
                [$userId, $productId, $variantId]
            );
        } else {
            db()->query(
                "DELETE FROM cart WHERE user_id = ? AND product_id = ? AND variant_id IS NULL",
                [$userId, $productId]
            );
        }
    } else {
        // Guest
        if ($variantId) {
            db()->query(
                "DELETE FROM cart WHERE session_id = ? AND product_id = ? AND variant_id = ? AND user_id IS NULL",
                [$sessionId, $productId, $variantId]
            );
        } else {
            db()->query(
                "DELETE FROM cart WHERE session_id = ? AND product_id = ? AND variant_id IS NULL AND user_id IS NULL",
                [$sessionId, $productId]
            );
        }
    }

    // Tính lại tổng số lượng sản phẩm trong giỏ
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
        'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
        'cart_count' => $cartCount
    ]);

} catch (Exception $e) {
    error_log("Remove from cart API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại.']);
}
