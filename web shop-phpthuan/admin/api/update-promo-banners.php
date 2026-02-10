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

    if (!isset($data['promo']) || !is_array($data['promo'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request data'
        ]);
        exit;
    }

    $jsonValue = json_encode($data['promo']);

    // Use updateSetting helper
    updateSetting('promo_banners', $jsonValue);

    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu Promo Banners'
    ]);
} catch (Exception $e) {
    error_log("Error updating promo banners: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
