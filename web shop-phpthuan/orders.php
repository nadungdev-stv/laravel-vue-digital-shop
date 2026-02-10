<?php
$pageTitle = 'Đơn hàng của tôi';
require_once __DIR__ . '/includes/header.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    setFlash('error', 'Vui lòng đăng nhập');
    redirect('/login.php?redirect=/orders');
}

$user = getCurrentUser();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Lấy đơn hàng
$orders = db()->query(
    "SELECT * FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT ? OFFSET ?",
    [$user['id'], $perPage, $offset]
)->fetchAll();

// Đếm tổng số
$totalOrders = db()->query(
    "SELECT COUNT(*) as count FROM orders WHERE user_id = ?",
    [$user['id']]
)->fetch()['count'];

$totalPages = ceil($totalOrders / $perPage);
?>

<div class="container">
    <h1 class="mb-4"><i class="fas fa-receipt"></i> Đơn hàng của tôi</h1>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <h4>Bạn chưa có đơn hàng nào</h4>
            <p>Hãy mua sắm ngay để trải nghiệm dịch vụ của chúng tôi!</p>
            <a href="/products" class="pill-button pill-button-blue">
                <i class="fas fa-shopping-bag"></i> Mua sắm ngay
            </a>
        </div>
    <?php else: ?>
        <style>
        .orders-table {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .orders-table thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            font-size: 15px;
            padding: 14px 15px;
            border-bottom: 2px solid #dee2e6;
        }
        .orders-table tbody tr {
            border-bottom: 1px solid #e9ecef;
        }
        .orders-table tbody td {
            padding: 14px 15px;
            vertical-align: middle;
            font-size: 15px;
        }
        .order-code {
            font-weight: 600;
            color: #212529;
            font-size: 15px;
            transition: color 0.2s ease;
        }
        .order-code:hover {
            color: #0d6efd !important;
        }
        .order-date {
            color: #212529;
            font-size: 15px;
            font-weight: 600;
        }
        .product-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .product-item:last-child {
            margin-bottom: 0;
        }
        .product-item a {
            transition: color 0.2s ease;
        }
        .product-item a:hover .product-name {
            color: #0d6efd !important;
        }
        .product-img {
            width: 60px;
            height: 28px;
            object-fit: cover;
            border-radius: 3px;
            flex-shrink: 0;
            border: 1px solid #dee2e6;
        }
        .product-name {
            font-size: 15px;
            color: #212529;
            font-weight: 600;
        }
        .order-total {
            font-weight: 600;
            color: #212529;
            font-size: 15px;
        }
        .simple-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 1000px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            border: 1px solid;
            letter-spacing: -0.01em;
            transition: all 0.2s ease;
        }
        .simple-badge i {
            font-size: 13px;
        }
        .simple-badge.badge-success {
            padding: 8px 12px;
        }
        .simple-badge.badge-success i {
            font-size: 15px;
        }
        .badge-warning {
            background: #fff4e5;
            color: #f57c00;
            border-color: #ff9500;
        }
        .badge-success {
            background: #e5ffe5;
            color: #1d7c2e;
            border-color: #34c759;
        }
        .badge-danger {
            background: #ffe5e5;
            color: #c41e1e;
            border-color: #ff3b30;
        }
        .badge-info {
            background: #e5f2ff;
            color: #0056b3;
            border-color: #0071e3;
        }
        .badge-secondary {
            background: #f5f5f7;
            color: #6e6e73;
            border-color: #d2d2d7;
        }

        /* Hover effects for badges */
        .simple-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .actions-wrapper {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 8px;
        }
        .action-btn {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 1000px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid;
            text-align: center;
            white-space: nowrap;
            letter-spacing: -0.01em;
            transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);
        }
        .btn-view {
            background: #0071e3;
            color: white;
            border-color: #0071e3;
        }
        .btn-view:hover {
            background: #0077ed;
            border-color: #0077ed;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3);
        }
        .btn-review {
            background: white;
            color: #ffc107;
            border-color: #ffc107;
            padding: 8px 12px;
        }
        .btn-review:hover {
            background: #ffc107;
            color: white;
            border-color: #ffc107;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }
        .btn-review i {
            font-size: 14px;
        }
        </style>

        <div class="orders-table">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 10%; text-align: center;">Mã đơn</th>
                        <th style="width: 12%;">Ngày đặt</th>
                        <th style="width: 38%;">Sản phẩm</th>
                        <th style="width: 15%; text-align: center;">Tổng tiền</th>
                        <th style="width: 15%; text-align: center;">Trạng thái</th>
                        <th style="width: 10%;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        // Lấy sản phẩm trong đơn
                        try {
                            $items = db()->query(
                                "SELECT oi.*, p.image, p.slug
                                 FROM order_items oi
                                 LEFT JOIN products p ON oi.product_id = p.id
                                 WHERE oi.order_id = ?",
                                [$order['id']]
                            )->fetchAll();

                            // Lấy thông tin variant và gallery cho mỗi item
                            foreach ($items as &$item) {
                                // Lấy tên sản phẩm
                                $item['product_name'] = $item['product_name'] ?? 'Sản phẩm đã xóa';

                                // Lấy variant image nếu có variant_id
                                if (!empty($item['variant_id'])) {
                                    $variant = db()->query(
                                        "SELECT variant_image, slug as variant_slug FROM product_variants WHERE id = ?",
                                        [$item['variant_id']]
                                    )->fetch();
                                    if ($variant) {
                                        $item['variant_image'] = $variant['variant_image'];
                                        $item['variant_slug'] = $variant['variant_slug'];
                                    }
                                }

                                // Lấy ảnh đầu tiên từ gallery
                                if (!empty($item['product_id'])) {
                                    $gallery = db()->query(
                                        "SELECT image_path FROM product_gallery WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1",
                                        [$item['product_id']]
                                    )->fetch();
                                    if ($gallery) {
                                        $item['first_gallery_image'] = $gallery['image_path'];
                                    }
                                }

                                // Xác định ảnh hiển thị (ưu tiên: variant_image > first_gallery_image > product_image)
                                $displayImage = '';
                                if (!empty($item['variant_image'])) {
                                    $displayImage = $item['variant_image'];
                                } elseif (!empty($item['first_gallery_image'])) {
                                    $displayImage = $item['first_gallery_image'];
                                } elseif (!empty($item['image'])) {
                                    $displayImage = $item['image'];
                                }
                                $item['image'] = $displayImage;

                                // Xác định slug (ưu tiên variant_slug)
                                if (!empty($item['variant_slug'])) {
                                    $item['slug'] = $item['variant_slug'];
                                }
                            }
                            unset($item);

                            // Kiểm tra đã review chưa
                            $reviewedProducts = [];
                            if ($order['order_status'] === 'completed') {
                                $reviews = db()->query(
                                    "SELECT product_id FROM reviews WHERE order_id = ? AND user_id = ?",
                                    [$order['id'], $user['id']]
                                )->fetchAll();
                                foreach ($reviews as $review) {
                                    $reviewedProducts[] = $review['product_id'];
                                }
                            }
                        } catch (Exception $e) {
                            $items = [];
                            $reviewedProducts = [];
                        }
                        ?>
                        <tr>
                            <td style="text-align: center;">
                                <a href="/order-detail?code=<?= e($order['order_code']) ?>" class="order-code" style="text-decoration: none;">#<?= e($order['order_code']) ?></a>
                            </td>
                            <td>
                                <span class="order-date"><?= formatDate($order['created_at']) ?></span>
                            </td>
                            <td>
                                <?php if (empty($items)): ?>
                                    <span class="text-muted">Không có sản phẩm</span>
                                <?php else: ?>
                                    <?php foreach ($items as $i => $item): ?>
                                        <div class="product-item">
                                            <?php if (!empty($item['product_id']) && !empty($item['slug'])): ?>
                                                <a href="/<?= e($item['slug']) ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?= e($item['image']) ?>"
                                                             alt="<?= e($item['product_name'] ?? 'Sản phẩm') ?>"
                                                             class="product-img">
                                                    <?php endif; ?>
                                                    <span class="product-name"><?= e($item['product_name'] ?? 'Sản phẩm đã xóa') ?> <small class="text-muted">(x<?= $item['quantity'] ?>)</small></span>
                                                </a>
                                            <?php else: ?>
                                                <?php if (!empty($item['image'])): ?>
                                                    <img src="<?= e($item['image']) ?>"
                                                         alt="<?= e($item['product_name'] ?? 'Sản phẩm') ?>"
                                                         class="product-img">
                                                <?php endif; ?>
                                                <span class="product-name"><?= e($item['product_name'] ?? 'Sản phẩm đã xóa') ?> <small class="text-muted">(x<?= $item['quantity'] ?>)</small></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="order-total"><?= formatMoney($order['final_amount']) ?></span>
                            </td>
                            <td style="text-align: center;">
                                <?php
                                $statusIcons = [
                                    'pending' => 'fa-clock',
                                    'processing' => 'fa-spinner',
                                    'completed' => 'fa-check-circle',
                                    'cancelled' => 'fa-times-circle'
                                ];
                                $statusClasses = [
                                    'pending' => 'badge-warning',
                                    'processing' => 'badge-info',
                                    'completed' => 'badge-success',
                                    'cancelled' => 'badge-danger'
                                ];
                                $statusLabels = [
                                    'pending' => 'Chờ thanh toán',
                                    'processing' => 'Đang xử lý',
                                    'completed' => 'Hoàn thành',
                                    'cancelled' => 'Đã hủy'
                                ];
                                $icon = $statusIcons[$order['order_status']] ?? 'fa-circle';
                                $class = $statusClasses[$order['order_status']] ?? 'badge-secondary';
                                $label = $statusLabels[$order['order_status']] ?? $order['order_status'];
                                $iconSpin = ($order['order_status'] === 'processing') ? 'fa-spin' : '';
                                ?>
                                <span class="simple-badge <?= $class ?>">
                                    <i class="fas <?= $icon ?> <?= $iconSpin ?>"></i>
                                    <?= $label ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions-wrapper">
                                    <a href="/order-detail?code=<?= e($order['order_code']) ?>"
                                       class="action-btn btn-view">
                                        <i class="fas fa-eye"></i> Chi tiết
                                    </a>
                                    <?php if ($order['order_status'] === 'completed'): ?>
                                        <?php
                                        // Đếm sản phẩm chưa review
                                        $unreviewed = array_filter($items, function($item) use ($reviewedProducts) {
                                            return !in_array($item['product_id'], $reviewedProducts);
                                        });
                                        $unreviewedCount = count($unreviewed);
                                        ?>
                                        <?php if ($unreviewedCount > 0): ?>
                                            <button type="button"
                                                    class="action-btn btn-review"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#reviewOrderModal<?= $order['id'] ?>"
                                                    title="Đánh giá (<?= $unreviewedCount ?> sản phẩm)">
                                                <i class="fas fa-star"></i>
                                                <span>(<?= $unreviewedCount ?>)</span>
                                            </button>
                                        <?php else: ?>
                                            <span class="simple-badge badge-success" title="Đã đánh giá">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modals đánh giá -->
<?php if (!empty($orders)): ?>
    <?php foreach ($orders as $order): ?>
        <?php if ($order['order_status'] === 'completed'): ?>
            <?php
            // Lấy lại items cho modal
            try {
                $modalItems = db()->query(
                    "SELECT oi.*, p.image
                     FROM order_items oi
                     LEFT JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = ?",
                    [$order['id']]
                )->fetchAll();

                // Lấy thông tin variant và gallery cho mỗi item
                foreach ($modalItems as &$item) {
                    // Lấy tên sản phẩm
                    $item['product_name'] = $item['product_name'] ?? 'Sản phẩm đã xóa';

                    // Lấy variant image nếu có variant_id
                    if (!empty($item['variant_id'])) {
                        $variant = db()->query(
                            "SELECT variant_image FROM product_variants WHERE id = ?",
                            [$item['variant_id']]
                        )->fetch();
                        if ($variant) {
                            $item['variant_image'] = $variant['variant_image'];
                        }
                    }

                    // Lấy ảnh đầu tiên từ gallery
                    if (!empty($item['product_id'])) {
                        $gallery = db()->query(
                            "SELECT image_path FROM product_gallery WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1",
                            [$item['product_id']]
                        )->fetch();
                        if ($gallery) {
                            $item['first_gallery_image'] = $gallery['image_path'];
                        }
                    }

                    // Xác định ảnh hiển thị (ưu tiên: variant_image > first_gallery_image > product_image)
                    $displayImage = '';
                    if (!empty($item['variant_image'])) {
                        $displayImage = $item['variant_image'];
                    } elseif (!empty($item['first_gallery_image'])) {
                        $displayImage = $item['first_gallery_image'];
                    } elseif (!empty($item['image'])) {
                        $displayImage = $item['image'];
                    }
                    $item['image'] = $displayImage;
                }
                unset($item);

                // Lấy reviewed products
                $modalReviewed = db()->query(
                    "SELECT product_id FROM reviews WHERE order_id = ? AND user_id = ?",
                    [$order['id'], $user['id']]
                )->fetchAll();
                $modalReviewedIds = array_column($modalReviewed, 'product_id');
            } catch (Exception $e) {
                $modalItems = [];
                $modalReviewedIds = [];
            }
            ?>

            <div class="modal fade" id="reviewOrderModal<?= $order['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" style="max-width: 700px;">
                    <div class="modal-content" style="border: none; border-radius: 15px; overflow: hidden;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #7387df 100%); color: white; border: none; padding: 15px 20px;">
                            <div class="d-flex align-items-center gap-3">
                                <h5 class="modal-title mb-0" style="font-weight: 600; font-size: 16px;">
                                    <i class="fas fa-star me-2"></i>Đánh giá sản phẩm
                                </h5>
                                <small style="opacity: 0.9; font-size: 13px;">Đơn hàng #<?= e($order['order_code']) ?></small>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding: 25px; background: #f8f9fa;">
                            <?php foreach ($modalItems as $item): ?>
                                <?php if (!in_array($item['product_id'], $modalReviewedIds)): ?>
                                    <div class="review-product-item mb-3">
                                        <form class="quick-review-form" data-order-id="<?= $order['id'] ?>" data-product-id="<?= $item['product_id'] ?>" data-item-id="<?= $item['id'] ?>">
                                            <!-- Product header with rating on same line -->
                                            <div class="mb-3 pb-3" style="border-bottom: 1px solid #e9ecef;">
                                                <div class="d-flex align-items-center gap-3">
                                                    <?php if ($item['image']): ?>
                                                        <div style="width: 100px; height: 47px; border-radius: 8px; overflow: hidden; flex-shrink: 0;">
                                                            <img src="<?= e($item['image']) ?>"
                                                                 alt="<?= e($item['product_name']) ?>"
                                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0" style="font-weight: 600; color: #2d3748; font-size: 14px;"><?= e($item['product_name']) ?></h6>
                                                    </div>
                                                    <div class="star-rating-input-orders">
                                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                                            <input type="radio" name="rating_<?= $order['id'] ?>_<?= $item['id'] ?>" id="star_<?= $order['id'] ?>_<?= $item['id'] ?>_<?= $i ?>" value="<?= $i ?>" required>
                                                            <label for="star_<?= $order['id'] ?>_<?= $item['id'] ?>_<?= $i ?>" class="star-label mb-0">
                                                                <i class="fas fa-star"></i>
                                                            </label>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>

                                        <!-- Comment section -->
                                        <div class="mb-3">
                                            <label class="form-label mb-2" style="font-weight: 600; color: #2d3748; font-size: 13px;">
                                                <i class="fas fa-comment-dots text-primary me-1"></i>Nhận xét
                                            </label>
                                            <textarea class="form-control review-comment" rows="2"
                                                      placeholder="Chia sẻ trải nghiệm của bạn..."
                                                      style="border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px; font-size: 13px;"></textarea>

                                            <!-- Comment suggestions -->
                                            <div class="mt-2 p-2" style="background: #f7fafc; border-radius: 8px;">
                                                <small class="d-block mb-2" style="color: #4a5568; font-weight: 600; font-size: 11px;">
                                                    <i class="fas fa-lightbulb text-warning me-1"></i>Gợi ý nhận xét (có thể chọn nhiều):
                                                </small>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <button type="button" class="btn btn-sm btn-outline-success comment-suggest-btn" data-comment="Tài khoản hoạt động tốt" style="border-radius: 15px; font-size: 12px;">
                                                        <i class="fas fa-check-circle"></i> TK hoạt động tốt
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success comment-suggest-btn" data-comment="Giao tài khoản nhanh" style="border-radius: 15px; font-size: 12px;">
                                                        <i class="fas fa-bolt"></i> Giao nhanh
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success comment-suggest-btn" data-comment="Tài khoản chính hãng" style="border-radius: 15px; font-size: 12px;">
                                                        <i class="fas fa-shield-alt"></i> Chính hãng
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success comment-suggest-btn" data-comment="Giá rất hợp lý" style="border-radius: 15px; font-size: 12px;">
                                                        <i class="fas fa-dollar-sign"></i> Giá tốt
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success comment-suggest-btn" data-comment="Sẽ mua lại" style="border-radius: 15px; font-size: 12px;">
                                                        <i class="fas fa-redo"></i> Sẽ mua lại
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success comment-suggest-btn" data-comment="Đúng như mô tả" style="border-radius: 15px; font-size: 12px;">
                                                        <i class="fas fa-check"></i> Đúng mô tả
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success comment-suggest-btn" data-comment="Hỗ trợ nhiệt tình" style="border-radius: 15px; font-size: 12px;">
                                                        <i class="fas fa-headset"></i> Hỗ trợ tốt
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success comment-suggest-btn" data-comment="Shop uy tín" style="border-radius: 15px; font-size: 12px;">
                                                        <i class="fas fa-star"></i> Uy tín
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success comment-suggest-btn" data-comment="Bảo hành tốt" style="border-radius: 15px; font-size: 12px;">
                                                        <i class="fas fa-tools"></i> Bảo hành tốt
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                            <button type="submit" class="btn w-100" style="background: linear-gradient(135deg, #667eea 0%, #7387df 100%); color: white; border: none; padding: 10px; font-weight: 600; border-radius: 8px; font-size: 14px;">
                                                <i class="fas fa-paper-plane me-2"></i>Gửi đánh giá
                                            </button>
                                        </form>

                                        <?php if (!($item === end($modalItems))): ?>
                                            <hr class="my-3" style="border-top: 1px dashed #dee2e6;">
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<style>
.star-rating-input-orders {
    display: inline-flex;
    flex-direction: row-reverse;
    gap: 3px;
}

.star-rating-input-orders input[type="radio"] {
    display: none;
}

.star-rating-input-orders label {
    cursor: pointer;
    font-size: 24px;
    color: #ddd;
    transition: color 0.2s;
    margin: 0;
}

.star-rating-input-orders label:hover,
.star-rating-input-orders label:hover ~ label,
.star-rating-input-orders input[type="radio"]:checked ~ label {
    color: #ffc107;
}

.comment-suggest-btn {
    transition: all 0.2s ease;
    font-weight: 500;
}

.comment-suggest-btn.active {
    font-weight: 600;
    background-color: var(--bs-success);
    color: white;
    border-color: var(--bs-success);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Comment suggestion buttons
    document.querySelectorAll('.comment-suggest-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = this.closest('.quick-review-form');
            const comment = this.dataset.comment;
            const commentBox = form.querySelector('.review-comment');

            if (this.classList.contains('active')) {
                // Remove comment
                this.classList.remove('active');
                const currentComments = commentBox.value.split(', ').filter(c => c.trim() !== '');
                const newComments = currentComments.filter(c => c !== comment);
                commentBox.value = newComments.join(', ');
            } else {
                // Add comment
                this.classList.add('active');
                if (commentBox.value.trim() === '') {
                    commentBox.value = comment;
                } else {
                    commentBox.value += ', ' + comment;
                }
            }
        });
    });

    // Star rating change
    document.querySelectorAll('[name^="rating_"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const parts = this.name.split('_');
            const orderId = parts[1];
            const itemId = parts[2];
            updateRatingText(orderId, itemId, this.value);
        });
    });

    // Form submission
    document.querySelectorAll('.quick-review-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const orderId = this.dataset.orderId;
            const productId = this.dataset.productId;
            const itemId = this.dataset.itemId;
            const rating = this.querySelector(`input[name="rating_${orderId}_${itemId}"]:checked`);
            const comment = this.querySelector('.review-comment').value;

            if (!rating) {
                alert('Vui lòng chọn số sao đánh giá');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';

            fetch('/api/submit-review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}&product_id=${productId}&rating=${rating.value}&comment=${encodeURIComponent(comment)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    });

    function updateRatingText(orderId, itemId, rating) {
        const texts = {
            1: 'Rất tệ',
            2: 'Tệ',
            3: 'Bình thường',
            4: 'Tốt',
            5: 'Rất tốt'
        };
        const textElement = document.querySelector(`.rating-text-${orderId}-${itemId}`);
        if (textElement) {
            textElement.textContent = texts[rating] || 'Vui lòng chọn số sao';
        }
    }

    // Table row hover effect
    document.querySelectorAll('.orders-table tbody tr').forEach(function(row) {
        row.addEventListener('mouseenter', function() {
            this.style.setProperty('background-color', '#e0e0e0', 'important');
            this.querySelectorAll('td').forEach(function(td) {
                td.style.setProperty('background-color', '#e0e0e0', 'important');
            });
        });
        row.addEventListener('mouseleave', function() {
            this.style.setProperty('background-color', '#ffffff', 'important');
            this.querySelectorAll('td').forEach(function(td) {
                td.style.setProperty('background-color', 'transparent', 'important');
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
