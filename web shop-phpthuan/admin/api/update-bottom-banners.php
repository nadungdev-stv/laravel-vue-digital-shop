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

    if (!isset($data['bottom']) || !is_array($data['bottom'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request data'
        ]);
        exit;
    }

    $jsonValue = json_encode($data['bottom']);

    // Use updateSetting helper
    updateSetting('bottom_banners', $jsonValue);

    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu Bottom Banners'
    ]);
} catch (Exception $e) {
    error_log("Error updating bottom banners: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
