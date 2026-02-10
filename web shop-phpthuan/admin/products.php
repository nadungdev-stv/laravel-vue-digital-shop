<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

// Kiểm tra quyền admin
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Quản lý sản phẩm';

// Xử lý xóa sản phẩm
if (isset($_GET['delete']) && $_GET['delete']) {
    $productId = (int)$_GET['delete'];
    db()->query("DELETE FROM products WHERE id = ?", [$productId]);
    setFlash('success', 'Đã xóa sản phẩm');
    redirect('/admin/products');
}

// Xử lý toggle featured
if (isset($_GET['toggle_featured']) && $_GET['toggle_featured']) {
    $id = (int)$_GET['toggle_featured'];
    $current = db()->query("SELECT featured FROM products WHERE id = ?", [$id])->fetchColumn();
    $new = $current ? 0 : 1;
    db()->query("UPDATE products SET featured = ? WHERE id = ?", [$new, $id]);
    setFlash('success', 'Đã cập nhật trạng thái nổi bật');
    redirect($_SERVER['HTTP_REFERER'] ?? '/admin/products');
}

// Lấy danh sách sản phẩm
$search = $_GET['search'] ?? '';
$categoryId = $_GET['category'] ?? 0;
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Whitelist allowed sort columns
$allowedSortColumns = ['id', 'name', 'category_name', 'price', 'delivery_type', 'variant_count', 'stock_quantity', 'sold_count', 'status', 'featured'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_at';
}

// Whitelist sort order
$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

$where = [];
$params = [];

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryId) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Map sort columns to actual SQL columns
$sortColumnMap = [
    'id' => 'p.id',
    'name' => 'p.name',
    'category_name' => 'c.name',
    'price' => 'p.price',
    'delivery_type' => 'p.delivery_type',
    'variant_count' => 'variant_count',
    'stock_quantity' => 'p.stock_quantity',
    'sold_count' => 'sold_count',
    'status' => 'p.status',
    'featured' => 'p.featured',
    'created_at' => 'p.created_at'
];
$orderByColumn = $sortColumnMap[$sortBy] ?? 'p.created_at';

$products = db()->query(
    "SELECT p.*, c.name as category_name, p.slug,
            (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count,
            (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image,
            COALESCE(p.sold_count, 0) as sold_count
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     $whereClause
     ORDER BY $orderByColumn $sortOrder
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
)->fetchAll();

$totalProducts = db()->query(
    "SELECT COUNT(*) as count FROM products p $whereClause",
    $params
)->fetch()['count'];

$totalPages = ceil($totalProducts / $perPage);

// Lấy danh mục
$categories = db()->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();

// Helper function to generate sort URL
function getSortUrl($column, $currentSort, $currentOrder, $search, $categoryId) {
    $newOrder = 'ASC';
    if ($column === $currentSort) {
        $newOrder = $currentOrder === 'ASC' ? 'DESC' : 'ASC';
    }

    $params = ['sort' => $column, 'order' => $newOrder];
    if ($search) $params['search'] = $search;
    if ($categoryId) $params['category'] = $categoryId;

    return '/admin/products?' . http_build_query($params);
}

// Helper function to get sort icon
function getSortIcon($column, $currentSort, $currentOrder) {
    if ($column !== $currentSort) {
        return '<i class="fas fa-sort" style="opacity: 0.3; margin-left: 6px;"></i>';
    }
    $icon = $currentOrder === 'ASC' ? 'fa-sort-up' : 'fa-sort-down';
    return '<i class="fas ' . $icon . '" style="margin-left: 6px;"></i>';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Veyrix Admin 2.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/admin-apple-style.css">
    <style>
        .product-image-thumb {
            width: 86px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #e5e5e5;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>

                <p class="text-secondary mb-0">Danh sách <?= $totalProducts ?> sản phẩm hiện có</p>
            </div>
            
            <a href="/admin/product-add.php" class="pill-button pill-button-gray">
                <i class="fas fa-plus"></i>
                <span class="d-none d-sm-inline">Thêm sản phẩm</span>
                <span class="d-inline d-sm-none">Thêm</span>
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="/admin/products" class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-search text-secondary"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" style="border-radius: 0 12px 12px 0;" placeholder="Tìm kiếm tên sản phẩm..." value="<?= e($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select" style="border-radius: 12px;">
                            <option value="">Tất cả danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                         <button type="submit" class="pill-button pill-button-gray">Lọc</button>
                         <a href="/admin/products" class="pill-button pill-button-white">Đặt lại</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th class="text-center" width="80">
                                <a href="<?= getSortUrl('id', $sortBy, $sortOrder, $search, $categoryId) ?>" class="text-decoration-none text-dark d-flex align-items-center justify-content-center">
                                    ID <?= getSortIcon('id', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th width="80">Ảnh</th>
                            <th>
                                <a href="<?= getSortUrl('name', $sortBy, $sortOrder, $search, $categoryId) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                    Tên sản phẩm <?= getSortIcon('name', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= getSortUrl('category_name', $sortBy, $sortOrder, $search, $categoryId) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                    Danh mục <?= getSortIcon('category_name', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="<?= getSortUrl('featured', $sortBy, $sortOrder, $search, $categoryId) ?>" class="text-decoration-none text-dark d-flex align-items-center justify-content-center">
                                    Nổi bật <?= getSortIcon('featured', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= getSortUrl('price', $sortBy, $sortOrder, $search, $categoryId) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                    Giá bán <?= getSortIcon('price', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= getSortUrl('delivery_type', $sortBy, $sortOrder, $search, $categoryId) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                    Loại <?= getSortIcon('delivery_type', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="<?= getSortUrl('stock_quantity', $sortBy, $sortOrder, $search, $categoryId) ?>" class="text-decoration-none text-dark d-flex align-items-center justify-content-center">
                                    Kho <?= getSortIcon('stock_quantity', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="<?= getSortUrl('sold_count', $sortBy, $sortOrder, $search, $categoryId) ?>" class="text-decoration-none text-dark d-flex align-items-center justify-content-center">
                                    Đã bán <?= getSortIcon('sold_count', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="<?= getSortUrl('status', $sortBy, $sortOrder, $search, $categoryId) ?>" class="text-decoration-none text-dark d-flex align-items-center justify-content-center">
                                    Trạng thái <?= getSortIcon('status', $sortBy, $sortOrder) ?>
                                </a>
                            </th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="text-center text-secondary">#<?= $product['id'] ?></td>
                            <td>
                                <?php if ($product['first_gallery_image']): ?>
                                    <img src="<?= e($product['first_gallery_image']) ?>" class="product-image-thumb" alt="Img">
                                <?php else: ?>
                                    <div class="product-image-thumb bg-light d-flex align-items-center justify-content-center text-secondary">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($product['name']) ?></div>
                                <div class="small text-secondary text-truncate" style="max-width: 200px;">
                                    <?= substr(strip_tags($product['description']), 0, 50) ?>...
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= e($product['category_name'] ?? 'Chưa phân loại') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="?toggle_featured=<?= $product['id'] ?>" class="text-decoration-none" title="Nhấn để thay đổi">
                                    <?php if ($product['featured']): ?>
                                        <i class="fas fa-star text-warning fa-lg"></i>
                                    <?php else: ?>
                                        <i class="far fa-star text-secondary fa-lg opacity-25"></i>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td class="fw-bold text-primary">
                                <?= formatMoney($product['price']) ?>
                            </td>
                            <td>
                                <?php if ($product['delivery_type'] == 'manual'): ?>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill">Thủ công</span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">Tự động</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($product['delivery_type'] == 'automatic'): ?>
                                    <?= $product['stock_quantity'] ?>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold text-secondary">
                                <?= $product['sold_count'] ?>
                            </td>
                            <td class="text-center">
                                <?php if ($product['status'] == 'active'): ?>
                                    <span class="badge bg-success rounded-pill">Hiển thị</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill">Ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="/admin/product-edit?id=<?= $product['id'] ?>" class="btn btn-sm btn-link text-primary" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/admin/products?delete=<?= $product['id'] ?>" 
                                   class="btn btn-sm btn-link text-danger" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-secondary">
                                <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                                <p>Không tìm thấy sản phẩm nào</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-top-0 py-3">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <!-- Previous Page -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-start-pill border-end-0" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= $categoryId ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Page Numbers -->
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&category=' . $categoryId . '&sort=' . $sortBy . '&order=' . $sortOrder . '">1</a></li>';
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link border-0">...</span></li>';
                            }
                        }

                        for ($i = $startPage; $i <= $endPage; $i++) :
                        ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $categoryId ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link border-0">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&category=' . $categoryId . '&sort=' . $sortBy . '&order=' . $sortOrder . '">' . $totalPages . '</a></li>';
                        }
                        ?>

                        <!-- Next Page -->
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-end-pill border-start-0" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= $categoryId ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
