<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

initSession();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$user = getCurrentUser();
$offset = (int)($_GET['offset'] ?? 0);
$limit = 5;

try {
    $notifications = db()->query(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
        [$user['id'], $limit, $offset]
    )->fetchAll();

    $totalCount = db()->query(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?",
        [$user['id']]
    )->fetch()['count'];

    $hasMore = ($offset + $limit) < $totalCount;

    // Format notifications HTML
    $html = '';
    foreach ($notifications as $notif) {
        $isRead = $notif['is_read'];
        $bgClass = !$isRead ? 'bg-light' : '';

        $html .= '<div class="list-group-item ' . $bgClass . '">';
        $html .= '<div class="d-flex justify-content-between align-items-start">';
        $html .= '<div class="flex-grow-1">';
        $html .= '<h6 class="mb-1">';

        if (!$isRead) {
            $html .= '<span class="badge bg-danger me-2">Mới</span>';
        }

        $html .= e($notif['title']) . '</h6>';
        $html .= '<p class="mb-1">' . e($notif['content']) . '</p>';
        $html .= '<small class="text-muted"><i class="fas fa-clock"></i> ' . formatDate($notif['created_at']) . '</small>';
        $html .= '</div>';

        if ($notif['link']) {
            $html .= '<a href="' . e($notif['link']) . '" class="pill-button pill-button-sm pill-button-blue ms-2">Xem</a>';
        }

        $html .= '</div></div>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'hasMore' => $hasMore,
        'nextOffset' => $offset + $limit
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
