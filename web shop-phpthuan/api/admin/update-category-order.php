<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
initSession();

header('Content-Type: application/json');

// Kiểm tra quyền admin
if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'error' => 'Bạn không có quyền truy cập'
    ]);
    exit;
}

try {
    // Đọc JSON data từ request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['categories']) || !is_array($data['categories'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request data'
        ]);
        exit;
    }

    // Cập nhật thứ tự từng danh mục
    foreach ($data['categories'] as $category) {
        $id = (int)$category['id'];
        $sortOrder = (int)$category['sort_order'];

        db()->query(
            "UPDATE categories SET sort_order = ? WHERE id = ?",
            [$sortOrder, $id]
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật thứ tự danh mục'
    ]);

} catch (Exception $e) {
    error_log("Error updating category order: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra khi cập nhật thứ tự'
    ]);
}
