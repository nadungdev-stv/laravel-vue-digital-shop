<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để đánh giá']);
    exit;
}

$user = getCurrentUser();

// Lấy dữ liệu
$orderId = (int)($_POST['order_id'] ?? 0);
$productId = (int)($_POST['product_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

// Validate
if (!$orderId || !$productId) {
    echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Đánh giá phải từ 1-5 sao']);
    exit;
}

try {
    // Kiểm tra đơn hàng
    $order = db()->query(
        "SELECT * FROM orders WHERE id = ? AND user_id = ?",
        [$orderId, $user['id']]
    )->fetch();

    if (!$order) {
        throw new Exception('Không tìm thấy đơn hàng');
    }

    // Kiểm tra đơn hàng đã hoàn thành
    if ($order['order_status'] !== 'completed') {
        throw new Exception('Chỉ có thể đánh giá đơn hàng đã hoàn thành');
    }

    // Kiểm tra sản phẩm có trong đơn hàng
    $orderItem = db()->query(
        "SELECT * FROM order_items WHERE order_id = ? AND product_id = ?",
        [$orderId, $productId]
    )->fetch();

    if (!$orderItem) {
        throw new Exception('Sản phẩm không có trong đơn hàng này');
    }

    // Kiểm tra đã đánh giá chưa
    $existingReview = db()->query(
        "SELECT id FROM reviews WHERE order_id = ? AND product_id = ? AND user_id = ?",
        [$orderId, $productId, $user['id']]
    )->fetch();

    if ($existingReview) {
        throw new Exception('Bạn đã đánh giá sản phẩm này rồi');
    }

    // Tự động duyệt nếu đánh giá 3-5 sao, pending nếu 1-2 sao
    $status = ($rating >= 3) ? 'approved' : 'pending';

    // Lưu đánh giá
    db()->query(
        "INSERT INTO reviews (product_id, user_id, order_id, rating, comment, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
        [$productId, $user['id'], $orderId, $rating, $comment, $status]
    );

    // Cập nhật rating trung bình của sản phẩm (chỉ tính reviews đã approved)
    $avgRating = db()->query(
        "SELECT AVG(rating) as avg FROM reviews WHERE product_id = ? AND status = 'approved'",
        [$productId]
    )->fetch()['avg'];

    db()->query(
        "UPDATE products SET rating = ? WHERE id = ?",
        [$avgRating ?: 0, $productId]
    );

    // Tạo thông báo cho admin
    try {
        $product = db()->query("SELECT name FROM products WHERE id = ?", [$productId])->fetch();
        $notificationMessage = 'Khách hàng ' . $user['username'] . ' đã đánh giá sản phẩm "' . $product['name'] . '" (' . $rating . ' sao)';

        if ($status === 'pending') {
            $notificationMessage .= ' - Cần kiểm duyệt';
        }

        db()->query(
            "INSERT INTO notifications (title, message, type, created_at)
             VALUES (?, ?, 'review', NOW())",
            [
                'Đánh giá mới',
                $notificationMessage
            ]
        );
    } catch (Exception $e) {
        // Bỏ qua nếu bảng notifications không tồn tại
    }

    // Thông báo khác nhau dựa vào status
    $responseMessage = ($status === 'approved')
        ? 'Cảm ơn bạn đã đánh giá! Đánh giá của bạn đã được công khai.'
        : 'Cảm ơn bạn đã đánh giá! Đánh giá của bạn sẽ được hiển thị sau khi được duyệt.';

    echo json_encode([
        'success' => true,
        'message' => $responseMessage
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
