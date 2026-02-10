<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Function to remove Vietnamese accents
function removeVietnameseAccents($str) {
    $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
    $str = preg_replace("/(đ)/", 'd', $str);

    $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
    $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
    $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
    $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
    $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
    $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
    $str = preg_replace("/(Đ)/", 'D', $str);

    return $str;
}

try {
    // Lấy sản phẩm bán chạy
    if (isset($_GET['popular'])) {
        $products = db()->query(
            "SELECT id, name, slug, price, sale_price, image, stock_quantity, sold_count
             FROM products
             WHERE status = 'active'
             ORDER BY sold_count DESC, created_at DESC
             LIMIT 10"
        )->fetchAll();

        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
        exit;
    }

    // Tìm kiếm sản phẩm
    $query = trim($_GET['q'] ?? '');
    $categorySlug = isset($_GET['category']) && $_GET['category'] !== 'all' ? trim($_GET['category']) : '';

    // Nếu có category slug, tìm category ID
    $categoryId = null;
    if (!empty($categorySlug)) {
        $category = db()->query("SELECT id FROM categories WHERE slug = ? AND status = 'active'", [$categorySlug])->fetch();
        if ($category) {
            $categoryId = $category['id'];
        }
    }

    if (empty($query)) {
        echo json_encode([
            'success' => false,
            'error' => 'Vui lòng nhập từ khóa tìm kiếm'
        ]);
        exit;
    }

    // Tìm kiếm thông minh - tách thành các từ
    $queryNoAccent = removeVietnameseAccents($query);

    // Tách query thành các từ riêng lẻ
    $keywords = preg_split('/\s+/', trim($query));
    $keywords = array_filter($keywords); // Bỏ empty strings

    // Build conditions cho mỗi từ khóa
    $nameConditions = [];
    $descConditions = [];
    $featConditions = [];
    $params = [];

    foreach ($keywords as $keyword) {
        $nameConditions[] = "LOWER(name) LIKE LOWER(?)";
        $descConditions[] = "LOWER(description) LIKE LOWER(?)";
        $featConditions[] = "LOWER(features) LIKE LOWER(?)";

        $params[] = '%' . $keyword . '%';
        $params[] = '%' . $keyword . '%';
        $params[] = '%' . $keyword . '%';
    }

    $nameSQL = implode(' OR ', $nameConditions);
    $descSQL = implode(' OR ', $descConditions);
    $featSQL = implode(' OR ', $featConditions);

    // Build keyword match count - đếm số từ khóa match trong tên sản phẩm
    $keywordMatchCases = [];
    $keywordMatchParams = [];
    foreach ($keywords as $keyword) {
        $keywordMatchCases[] = "CASE WHEN LOWER(name) LIKE LOWER(?) THEN 1 ELSE 0 END";
        $keywordMatchParams[] = '%' . $keyword . '%';
    }
    $keywordMatchSQL = implode(' + ', $keywordMatchCases);

    // Build SQL query với tìm kiếm theo từng từ và đếm keyword matches
    $sql = "SELECT id, name, slug, price, sale_price, image, stock_quantity, sold_count,
            ($keywordMatchSQL) as keyword_match_count,
            CASE
                WHEN LOWER(name) = LOWER(?) THEN 1
                WHEN LOWER(name) LIKE LOWER(?) THEN 2
                WHEN LOWER(name) LIKE LOWER(?) THEN 3
                ELSE 4
            END as position_score
            FROM products
            WHERE status = 'active'
            AND (
                ($nameSQL) OR
                ($descSQL) OR
                ($featSQL)
            )";

    // Add params cho keyword match count trước
    $allParams = array_merge($keywordMatchParams, [$query, $query . '%', '%' . $query . '%'], $params);

    // Thêm filter theo category nếu có
    if ($categoryId) {
        $sql .= " AND category_id = ?";
        $allParams[] = $categoryId;
    }

    $sql .= " ORDER BY
                keyword_match_count DESC,
                position_score ASC,
                sold_count DESC,
                stock_quantity DESC,
                created_at DESC
            LIMIT 10";

    $products = db()->query($sql, $allParams)->fetchAll();

    // Nếu không tìm thấy với query có dấu, thử tìm không dấu
    if (empty($products) && $queryNoAccent !== $query) {
        $sql = "SELECT id, name, slug, price, sale_price, image, stock_quantity, sold_count
                FROM products
                WHERE status = 'active'
                AND (
                    LOWER(name) LIKE LOWER(?) OR
                    LOWER(description) LIKE LOWER(?) OR
                    LOWER(features) LIKE LOWER(?)
                )";

        $params = [
            '%' . $queryNoAccent . '%',
            '%' . $queryNoAccent . '%',
            '%' . $queryNoAccent . '%'
        ];

        if ($categoryId) {
            $sql .= " AND category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY sold_count DESC, created_at DESC LIMIT 10";

        $products = db()->query($sql, $params)->fetchAll();
    }

    // Debug mode - thêm tham số debug=1 để xem query
    $debug = isset($_GET['debug']) ? true : false;

    $response = [
        'success' => true,
        'products' => $products,
        'query' => $query,
        'count' => count($products)
    ];

    if ($debug) {
        $response['debug'] = [
            'sql' => $sql,
            'params' => $allParams,
            'query_no_accent' => $queryNoAccent,
            'keywords' => $keywords
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Search API error: " . $e->getMessage() . " | Query: " . ($query ?? 'N/A'));

    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra khi tìm kiếm',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
