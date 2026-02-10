<?php
/**
 * API kiểm tra cập nhật mới
 * - Thông báo chưa đọc
 * - Số lượng giỏ hàng
 * - Số lượng wishlist
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    // Cho guest, chỉ trả về cart count
    $cartCount = count(getCart());

    echo json_encode([
        'success' => true,
        'logged_in' => false,
        'cart_count' => $cartCount,
        'notification_count' => 0,
        'wishlist_count' => 0,
        'timestamp' => time()
    ]);
    exit;
}

$user = getCurrentUser();

try {
    // Lấy số lượng thông báo chưa đọc
    $notificationCount = getUnreadNotificationCount($user['id']);

    // Lấy số lượng giỏ hàng
    $cartCount = count(getCart());

    // Lấy số lượng wishlist
    $wishlistCount = getWishlistCount($user['id']);

    // Lấy thông báo mới nhất (5 cái)
    $latestNotifications = getNotifications($user['id'], 5);

    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'notification_count' => $notificationCount,
        'cart_count' => $cartCount,
        'wishlist_count' => $wishlistCount,
        'latest_notifications' => array_map(function($notif) {
            return [
                'id' => $notif['id'],
                'title' => $notif['title'],
                'content' => $notif['content'],
                'type' => $notif['type'],
                'link' => $notif['link'],
                'is_read' => $notif['is_read'],
                'created_at' => $notif['created_at']
            ];
        }, $latestNotifications),
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi khi kiểm tra cập nhật',
        'timestamp' => time()
    ]);
}
?>
