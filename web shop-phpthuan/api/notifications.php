<?php
// API endpoint for notification actions
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
header('Content-Type: application/json');

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'mark_read':
        // Mark single notification as read
        $notificationId = (int)($_POST['notification_id'] ?? 0);

        if ($notificationId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
            exit;
        }

        // Verify notification belongs to user
        $notif = db()->query("SELECT * FROM notifications WHERE id = ? AND user_id = ?", [$notificationId, $user['id']])->fetch();

        if (!$notif) {
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
            exit;
        }

        $success = markNotificationAsRead($notificationId);
        echo json_encode([
            'success' => $success,
            'unread_count' => getUnreadNotificationCount($user['id'])
        ]);
        break;

    case 'mark_all_read':
        // Mark all notifications as read
        $success = markAllNotificationsAsRead($user['id']);
        echo json_encode([
            'success' => $success,
            'unread_count' => 0
        ]);
        break;

    case 'get_notifications':
        // Get latest notifications
        $limit = (int)($_GET['limit'] ?? 10);
        $notifications = getNotifications($user['id'], $limit);
        $unreadCount = getUnreadNotificationCount($user['id']);

        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
        break;

    case 'delete':
        // Delete notification
        $notificationId = (int)($_POST['notification_id'] ?? 0);

        if ($notificationId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
            exit;
        }

        // Verify notification belongs to user
        $notif = db()->query("SELECT * FROM notifications WHERE id = ? AND user_id = ?", [$notificationId, $user['id']])->fetch();

        if (!$notif) {
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
            exit;
        }

        db()->query("DELETE FROM notifications WHERE id = ?", [$notificationId]);
        echo json_encode([
            'success' => true,
            'unread_count' => getUnreadNotificationCount($user['id'])
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
