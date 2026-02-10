<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// CSRF Protection
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Token bảo mật không hợp lệ']);
    exit;
}

$user = getCurrentUser();
$action = $_POST['action'] ?? '';
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

try {
    switch ($action) {
        case 'add':
            // Thêm vào wishlist
            if ($productId <= 0) {
                throw new Exception('Sản phẩm không hợp lệ');
            }

            // Kiểm tra sản phẩm tồn tại
            $product = db()->query("SELECT id FROM products WHERE id = ?", [$productId])->fetch();
            if (!$product) {
                throw new Exception('Sản phẩm không tồn tại');
            }

            // Thêm vào wishlist (IGNORE nếu đã tồn tại)
            db()->query(
                "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)",
                [$user['id'], $productId]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Đã thêm vào danh sách yêu thích',
                'is_wishlisted' => true
            ]);
            break;

        case 'remove':
            // Xóa khỏi wishlist
            if ($productId <= 0) {
                throw new Exception('Sản phẩm không hợp lệ');
            }

            db()->query(
                "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?",
                [$user['id'], $productId]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa khỏi danh sách yêu thích',
                'is_wishlisted' => false
            ]);
            break;

        case 'toggle':
            // Toggle wishlist
            if ($productId <= 0) {
                throw new Exception('Sản phẩm không hợp lệ');
            }

            // Kiểm tra xem đã có trong wishlist chưa
            $exists = db()->query(
                "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?",
                [$user['id'], $productId]
            )->fetch();

            if ($exists) {
                // Xóa
                db()->query(
                    "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?",
                    [$user['id'], $productId]
                );
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã xóa khỏi danh sách yêu thích',
                    'is_wishlisted' => false
                ]);
            } else {
                // Thêm
                db()->query(
                    "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)",
                    [$user['id'], $productId]
                );
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã thêm vào danh sách yêu thích',
                    'is_wishlisted' => true
                ]);
            }
            break;

        default:
            throw new Exception('Action không hợp lệ');
    }
} catch (Exception $e) {
    // Kiểm tra nếu là lỗi bảng không tồn tại
    $errorMessage = $e->getMessage();
    if (strpos($errorMessage, "doesn't exist") !== false && strpos($errorMessage, "wishlist") !== false) {
        $errorMessage = 'Tính năng wishlist chưa được cài đặt. Vui lòng liên hệ quản trị viên.';
    }

    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
}
