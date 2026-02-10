<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $category = $_GET['category'] ?? 'all';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;

    // Validate limit
    $limit = max(1, min($limit, 50)); // Between 1 and 50

    $query = "SELECT p.*, c.name as category_name
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.status = 'active'";

    $params = [];

    if ($category === 'featured') {
        // Sản phẩm nổi bật (featured = 1)
        $query .= " AND p.featured = 1";
    } elseif ($category !== 'all' && is_numeric($category)) {
        // Lọc theo category_id
        $query .= " AND p.category_id = ?";
        $params[] = (int)$category;
    }

    $query .= " ORDER BY p.featured DESC, p.created_at DESC LIMIT " . $limit;

    // Execute query
    if (empty($params)) {
        $products = db()->query($query)->fetchAll();
    } else {
        $products = db()->query($query, $params)->fetchAll();
    }

    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra khi tải sản phẩm',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
