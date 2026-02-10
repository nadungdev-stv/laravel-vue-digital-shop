<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

initSession();

// Chỉ cho phép admin chạy migration (hoặc bỏ check này nếu cần)
// if (!isLoggedIn() || !isAdmin()) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Access denied']);
//     exit;
// }

header('Content-Type: application/json');

$results = [];

try {
    db()->query("ALTER TABLE order_items ADD COLUMN variant_id INT NULL AFTER product_id");
    $results[] = '✓ Added variant_id column';
} catch (Exception $e) {
    $results[] = 'variant_id: ' . $e->getMessage();
}

try {
    db()->query("ALTER TABLE order_items ADD COLUMN image VARCHAR(500) NULL AFTER product_name");
    $results[] = '✓ Added image column';
} catch (Exception $e) {
    $results[] = 'image: ' . $e->getMessage();
}

echo json_encode([
    'success' => true,
    'results' => $results
]);
