<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Quản lý đánh giá';

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $reviewId = (int)$_POST['review_id'];
    $status = $_POST['status'];

    db()->query(
        "UPDATE reviews SET status = ?, updated_at = NOW() WHERE id = ?",
        [$status, $reviewId]
    );

    // Cập nhật lại rating trung bình của sản phẩm
    $review = db()->query("SELECT product_id FROM reviews WHERE id = ?", [$reviewId])->fetch();
    if ($review) {
        $avgRating = db()->query(
            "SELECT AVG(rating) as avg FROM reviews WHERE product_id = ? AND status = 'approved'",
            [$review['product_id']]
        )->fetch()['avg'];

        db()->query(
            "UPDATE products SET rating = ? WHERE id = ?",
            [$avgRating ?: 0, $review['product_id']]
        );
    }

    setFlash('success', 'Đã cập nhật trạng thái đánh giá');
    redirect('/admin/reviews.php');
}

// Xử lý xóa
if (isset($_GET['delete']) && $_GET['delete']) {
    $reviewId = (int)$_GET['delete'];

    // Lấy product_id trước khi xóa
    $review = db()->query("SELECT product_id FROM reviews WHERE id = ?", [$reviewId])->fetch();

    db()->query("DELETE FROM reviews WHERE id = ?", [$reviewId]);

    // Cập nhật lại rating
    if ($review) {
        $avgRating = db()->query(
            "SELECT AVG(rating) as avg FROM reviews WHERE product_id = ? AND status = 'approved'",
            [$review['product_id']]
        )->fetch()['avg'];

        db()->query(
            "UPDATE products SET rating = ? WHERE id = ?",
            [$avgRating ?: 0, $review['product_id']]
        );
    }

    setFlash('success', 'Đã xóa đánh giá');
    redirect('/admin/reviews.php');
}

// Xử lý phản hồi admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reply'])) {
    $reviewId = (int)$_POST['review_id'];
    $adminReply = trim($_POST['admin_reply']);

    if (!empty($adminReply)) {
        db()->query(
            "UPDATE reviews SET admin_reply = ?, updated_at = NOW() WHERE id = ?",
            [$adminReply, $reviewId]
        );
        setFlash('success', 'Đã thêm phản hồi');
    }
    redirect('/admin/reviews.php');
}

// Bộ lọc
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$rating = $_GET['rating'] ?? '';
$productId = $_GET['product_id'] ?? 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10; // Giảm số lượng để hiển thị card đỡ rối
$offset = ($page - 1) * $perPage;

// Sorting
$sort = $_GET['sort'] ?? 'newest';
$sortMap = [
    'newest' => 'r.created_at DESC',
    'oldest' => 'r.created_at ASC',
    'rating_desc' => 'r.rating DESC',
    'rating_asc' => 'r.rating ASC',
];
$orderBy = $sortMap[$sort] ?? 'r.created_at DESC';

$where = [];
$params = [];

if ($search) {
    $where[] = "(u.username LIKE ? OR r.comment LIKE ? OR p.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $where[] = "r.status = ?";
    $params[] = $status;
}

if ($rating) {
    $where[] = "r.rating = ?";
    $params[] = $rating;
}

if ($productId) {
    $where[] = "r.product_id = ?";
    $params[] = $productId;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$reviews = db()->query(
    "SELECT r.*,
     u.username, u.email,
     p.name as product_name, p.image as product_image, p.slug as product_slug,
     o.order_code
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     JOIN products p ON r.product_id = p.id
     LEFT JOIN orders o ON r.order_id = o.id
     $whereClause
     ORDER BY $orderBy
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
)->fetchAll();

// Lấy thêm thông tin variant cho mỗi review
foreach ($reviews as &$review) {
    $variantId = null;

    if (isset($review['variant_id']) && !empty($review['variant_id'])) {
        $variantId = $review['variant_id'];
    }
    elseif (!empty($review['order_id']) && !empty($review['product_id'])) {
        try {
            $orderItem = db()->query(
                "SELECT variant_id FROM order_items WHERE order_id = ? AND product_id = ? LIMIT 1",
                [$review['order_id'], $review['product_id']]
            )->fetch();
            if ($orderItem && !empty($orderItem['variant_id'])) {
                $variantId = $orderItem['variant_id'];
            }
        } catch (Exception $e) {}
    }

    if ($variantId) {
        try {
            $variant = db()->query(
                "SELECT variant_name, slug as variant_slug, variant_image FROM product_variants WHERE id = ?",
                [$variantId]
            )->fetch();
            if ($variant) {
                $review['variant_name'] = $variant['variant_name'];
                $review['variant_slug'] = $variant['variant_slug'];
                $review['variant_image'] = $variant['variant_image'];
            }
        } catch (Exception $e) {}
    }
}
unset($review);

$totalReviews = db()->query(
    "SELECT COUNT(*) as count FROM reviews r
     JOIN users u ON r.user_id = u.id
     JOIN products p ON r.product_id = p.id
     $whereClause",
    $params
)->fetch()['count'];

$totalPages = ceil($totalReviews / $perPage);

// Thống kê
$stats = [
    'total' => db()->query("SELECT COUNT(*) as count FROM reviews")->fetch()['count'],
    'pending' => db()->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'")->fetch()['count'],
    'approved' => db()->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'approved'")->fetch()['count'],
    'rejected' => db()->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'rejected'")->fetch()['count'],
    'avg_rating' => db()->query("SELECT AVG(rating) as avg FROM reviews WHERE status = 'approved'")->fetch()['avg'] ?: 0,
    'today' => db()->query("SELECT COUNT(*) as count FROM reviews WHERE DATE(created_at) = CURDATE()")->fetch()['count'],
];

// Sản phẩm được đánh giá nhiều nhất
$topReviewed = db()->query(
    "SELECT p.id, p.name, COUNT(*) as review_count, AVG(r.rating) as avg_rating
     FROM reviews r
     JOIN products p ON r.product_id = p.id
     WHERE r.status = 'approved'
     GROUP BY p.id
     ORDER BY review_count DESC
     LIMIT 5"
)->fetchAll();

// Lấy danh sách sản phẩm cho filter
$products = db()->query("SELECT id, name FROM products ORDER BY name")->fetchAll();
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
        .review-card {
            background: #fff;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid #d2d2d7;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.2s;
        }
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        .star-rating {
            color: #ffc107;
            font-size: 14px;
        }
        .product-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #e5e5e7;
        }
        .admin-reply-box {
            background: #f5f5f7;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
        }
        .user-avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #1d1d1f;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>

                <p class="text-secondary mb-0">Quản lý phản hồi khách hàng</p>
            </div>
             <div class="d-flex gap-2">
                <a href="?status=" class="pill-button pill-button-<?= empty($status) ? 'primary' : 'white' ?>">
                    Tất cả
                </a>
                <a href="?status=pending" class="pill-button pill-button-<?= $status === 'pending' ? 'primary' : 'white' ?>">
                    Chờ duyệt (<?= $stats['pending'] ?>)
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
             <div class="col-md-2">
                <div class="card p-3 border-0 shadow-sm h-100 text-center">
                    <h3 class="fw-bold mb-1"><?= $stats['total'] ?></h3>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 10px;">Tổng đánh giá</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card p-3 border-0 shadow-sm h-100 text-center">
                    <h3 class="fw-bold mb-1 text-warning"><?= $stats['pending'] ?></h3>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 10px;">Chờ duyệt</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card p-3 border-0 shadow-sm h-100 text-center">
                    <h3 class="fw-bold mb-1 text-success"><?= $stats['approved'] ?></h3>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 10px;">Đã duyệt</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card p-3 border-0 shadow-sm h-100 text-center">
                    <h3 class="fw-bold mb-1 text-danger"><?= $stats['rejected'] ?></h3>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 10px;">Từ chối</small>
                </div>
            </div>
              <div class="col-md-2">
                <div class="card p-3 border-0 shadow-sm h-100 text-center">
                     <h3 class="fw-bold mb-1 text-primary"><?= number_format($stats['avg_rating'], 1) ?> <small class="fs-6 text-muted">/5</small></h3>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 10px;">Trung bình</small>
                </div>
            </div>
              <div class="col-md-2">
                <div class="card p-3 border-0 shadow-sm h-100 text-center">
                    <h3 class="fw-bold mb-1"><?= $stats['today'] ?></h3>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 10px;">Hôm nay</small>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-9">
                <!-- Bộ lọc -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                             <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-search text-secondary"></i></span>
                                    <input type="text" name="search" class="form-control border-start-0 ps-0" style="border-radius: 0 12px 12px 0;" placeholder="Người dùng, nội dung..." value="<?= e($search) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="product_id" class="form-select" style="border-radius: 12px;">
                                    <option value="">Tất cả sản phẩm</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= $product['id'] ?>" <?= $productId == $product['id'] ? 'selected' : '' ?>>
                                            <?= e($product['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="rating" class="form-select" style="border-radius: 12px;">
                                    <option value="">Sao</option>
                                    <?php for($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?= $i ?>" <?= $rating == $i ? 'selected' : '' ?>><?= $i ?> sao</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select" style="border-radius: 12px;">
                                    <option value="">Trạng thái</option>
                                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Từ chối</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="sort" class="form-select" style="border-radius: 12px;">
                                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
                                    <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Đánh giá cao</option>
                                    <option value="rating_asc" <?= $sort === 'rating_asc' ? 'selected' : '' ?>>Đánh giá thấp</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="pill-button pill-button-gray w-100 justify-content-center">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reviews List -->
                <?php if (empty($reviews)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-secondary opacity-25 mb-3"></i>
                        <h5 class="text-secondary">Chưa có đánh giá nào</h5>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="row g-4">
                                <!-- User & Content -->
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="user-avatar-circle me-3">
                                            <?= strtoupper(substr($review['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($review['username']) ?></div>
                                            <div class="text-secondary small">
                                                <i class="fas fa-envelope me-1"></i><?= e($review['email']) ?>
                                                <span class="mx-2">•</span>
                                                <i class="far fa-clock me-1"></i><?= formatDate($review['created_at']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <div class="star-rating mb-2">
                                            <?php for($i = 0; $i < 5; $i++): ?>
                                                <i class="fas fa-star<?= $i < $review['rating'] ? '' : ' text-black-50 opacity-25' ?>"></i>
                                            <?php endfor; ?>
                                            <span class="fw-bold text-dark ms-2" style="font-size: 15px;"><?= $review['rating'] ?>/5</span>
                                        </div>
                                        <p class="mb-0 text-dark" style="line-height: 1.6;"><?= nl2br(e($review['comment'])) ?></p>
                                    </div>

                                    <?php if ($review['admin_reply']): ?>
                                        <div class="admin-reply-box">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-reply text-primary me-2 mt-1"></i>
                                                <div>
                                                    <div class="fw-bold text-primary small text-uppercase mb-1">Phản hồi từ Admin</div>
                                                    <div class="small text-secondary"><?= nl2br(e($review['admin_reply'])) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Info & Actions -->
                                <div class="col-md-4 border-start">
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $displayName = !empty($review['variant_name']) ? $review['variant_name'] : $review['product_name'];
                                            $displayImage = !empty($review['variant_image']) ? $review['variant_image'] : $review['product_image'];
                                            ?>
                                            <?php if ($displayImage): ?>
                                                <img src="<?= e($displayImage) ?>" class="product-thumb me-3" alt="Product">
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-dark small text-truncate" style="max-width: 150px;"><?= e($displayName) ?></div>
                                                <?php if ($review['order_code']): ?>
                                                    <div class="badge bg-light text-secondary border mt-1">
                                                        <i class="fas fa-receipt me-1"></i><?= e($review['order_code']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-2">
                                        <?php if ($review['status'] === 'pending'): ?>
                                            <form method="POST" class="d-grid">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <button class="pill-button pill-button-gray w-100 justify-content-center">
                                                    <i class="fas fa-check me-2"></i>Duyệt bài
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($review['status'] !== 'rejected'): ?>
                                            <form method="POST" class="d-grid">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button class="pill-button pill-button-white w-100 text-danger border-danger justify-content-center">
                                                    <i class="fas fa-ban me-2"></i>Từ chối
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                         <?php if ($review['status'] === 'approved' && empty($review['admin_reply'])): ?>
                                            <button class="pill-button pill-button-white w-100 justify-content-center" 
                                                    type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#replyForm<?= $review['id'] ?>">
                                                <i class="fas fa-reply me-2"></i>Trả lời
                                            </button>
                                            
                                            <div class="collapse mt-2" id="replyForm<?= $review['id'] ?>">
                                                <form method="POST">
                                                    <input type="hidden" name="add_reply" value="1">
                                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                    <textarea name="admin_reply" class="form-control mb-2" rows="2" placeholder="Nhập câu trả lời..." style="font-size: 13px;" required></textarea>
                                                    <button type="submit" class="btn btn-sm btn-primary w-100">Gửi</button>
                                                </form>
                                            </div>
                                         <?php endif; ?>

                                         <a href="?delete=<?= $review['id'] ?>" 
                                            class="text-secondary small text-center text-decoration-none mt-2"
                                            onclick="return confirm('Xóa vĩnh viễn đánh giá này?')">
                                             <i class="fas fa-trash me-1"></i> Xóa đánh giá
                                         </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                 <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav>
                        <ul class="pagination">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-start-pill border-end-0" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&sort=<?= $sort ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&sort=<?= $sort ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-end-pill border-start-0" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&sort=<?= $sort ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>

            </div>

             <!-- Sidebar Right -->
            <div class="col-lg-3">
                 <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom fw-bold py-3">
                        Top Đánh Giá
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($topReviewed)): ?>
                            <div class="list-group-item text-secondary small text-center py-3">Chưa có dữ liệu</div>
                        <?php else: ?>
                            <?php foreach ($topReviewed as $index => $item): ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between px-3 py-3">
                                    <div class="d-flex align-items-center" style="overflow: hidden;">
                                        <span class="badge bg-light text-secondary rounded-pill me-2">#<?= $index + 1 ?></span>
                                        <div style="min-width: 0;">
                                            <div class="fw-bold text-dark small text-truncate"><?= e($item['name']) ?></div>
                                            <small class="text-secondary">
                                                <i class="fas fa-star text-warning"></i> <?= number_format($item['avg_rating'], 1) ?>
                                                <span class="mx-1">•</span>
                                                <?= $item['review_count'] ?> đánh giá
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
