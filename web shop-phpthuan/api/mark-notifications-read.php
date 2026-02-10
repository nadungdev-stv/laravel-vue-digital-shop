<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

initSession();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user = getCurrentUser();

try {
    db()->query(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
        [$user['id']]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Đã đánh dấu tất cả thông báo là đã đọc'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
