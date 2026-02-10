<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
initSession();

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'error' => 'Bạn không có quyền truy cập'
    ]);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['banners']) || !is_array($data['banners'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request data'
        ]);
        exit;
    }

    foreach ($data['banners'] as $banner) {
        $id = (int)$banner['id'];
        $sortOrder = (int)$banner['sort_order'];
        db()->query(
            "UPDATE banners SET sort_order = ? WHERE id = ?",
            [$sortOrder, $id]
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật thứ tự banner'
    ]);
} catch (Exception $e) {
    error_log("Error updating banner order: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra khi cập nhật thứ tự'
    ]);
}
