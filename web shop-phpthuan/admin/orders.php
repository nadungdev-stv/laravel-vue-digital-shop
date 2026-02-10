<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

// Kiểm tra quyền admin
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Quản lý đơn hàng';
$pageDescription = 'Tổng quan tình hình đơn hàng';

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $orderStatus = $_POST['order_status'];
    $paymentStatus = $_POST['payment_status'];

    db()->query(
        "UPDATE orders SET order_status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?",
        [$orderStatus, $paymentStatus, $orderId]
    );

    // Nếu thanh toán thành công, tự động giao tài khoản
    if ($paymentStatus === 'paid') {
        $orderItems = db()->query(
            "SELECT * FROM order_items WHERE order_id = ? AND account_delivered IS NULL",
            [$orderId]
        )->fetchAll();

        foreach ($orderItems as $item) {
            // Lấy tài khoản từ kho
            $account = db()->query(
                "SELECT * FROM accounts_stock
                 WHERE product_id = ? AND status = 'available'
                 LIMIT 1",
                [$item['product_id']]
            )->fetch();

            if ($account) {
                // Cập nhật tài khoản đã giao
                db()->query(
                    "UPDATE order_items SET account_delivered = ? WHERE id = ?",
                    [json_encode([
                        'username' => $account['account_username'],
                        'password' => $account['account_password'],
                        'note' => $account['account_note']
                    ]), $item['id']]
                );

                // Đánh dấu tài khoản đã bán
                db()->query(
                    "UPDATE accounts_stock SET status = 'sold', sold_at = NOW() WHERE id = ?",
                    [$account['id']]
                );
            }
        }
    }

    setFlash('success', 'Đã cập nhật trạng thái đơn hàng');
    redirect('/admin/orders');
}

// Lấy danh sách đơn hàng với bộ lọc
$search = $_GET['search'] ?? '';
$orderStatus = $_GET['order_status'] ?? '';
$paymentStatus = $_GET['payment_status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Sorting
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';
$allowedSortColumns = ['order_code', 'customer_name', 'final_amount', 'payment_status', 'payment_method', 'order_status', 'created_at'];

if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_at';
}

$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Map sort columns
$sortColumnMap = [
    'order_code' => 'o.order_code',
    'customer_name' => 'display_name', // Requires alias in SELECT
    'final_amount' => 'o.final_amount',
    'payment_status' => 'o.payment_status',
    'payment_method' => 'o.payment_method',
    'order_status' => 'o.order_status',
    'created_at' => 'o.created_at'
];

$orderByColumn = $sortColumnMap[$sortBy] ?? 'o.created_at';

$where = [];
$params = [];

if ($search) {
    $where[] = "(o.order_code LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ? OR EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.product_name LIKE ?))";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($orderStatus) {
    $where[] = "o.order_status = ?";
    $params[] = $orderStatus;
}

if ($paymentStatus) {
    $where[] = "o.payment_status = ?";
    $params[] = $paymentStatus;
}

if ($dateFrom) {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orders = db()->query(
    "SELECT o.*, u.username, u.email,
            COALESCE(u.username, o.customer_name, 'Khách') as display_name,
            COALESCE(u.email, o.customer_email) as display_email
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     $whereClause

     ORDER BY $orderByColumn $sortOrder
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
)->fetchAll();

$totalOrders = db()->query(
    "SELECT COUNT(*) as count FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereClause",
    $params
)->fetch()['count'];

$totalPages = ceil($totalOrders / $perPage);

// Thống kê nhanh
$stats = [
    'total_orders' => db()->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'],
    'pending_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")->fetch()['count'],
    'processing_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'processing'")->fetch()['count'],
    'completed_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'completed'")->fetch()['count'],
    'cancelled_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'cancelled'")->fetch()['count'], // Added cancelled
    'pending_payment' => db()->query("SELECT COUNT(*) as count FROM orders WHERE payment_status = 'pending'")->fetch()['count'],
    'today_revenue' => db()->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'")->fetch()['total'],
];

// Helper functions for sorting
function getSortUrl($column, $currentSort, $currentOrder) {
    $params = $_GET;
    $params['sort'] = $column;
    $params['order'] = ($column === $currentSort && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
    return '?' . http_build_query($params);
}

function getSortIcon($column, $currentSort, $currentOrder) {
    if ($column !== $currentSort) {
        return '<i class="fas fa-sort" style="opacity: 0.3; margin-left: 6px;"></i>';
    }
    return '<i class="fas ' . ($currentOrder === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') . '" style="margin-left: 6px;"></i>';
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
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
            <div class="d-flex justify-content-end align-items-center mb-4">
                <button class="pill-button pill-button-white">
                    <i class="fas fa-file-download"></i>
                    <span class="d-none d-sm-inline">Xuất Excel</span>
                    <span class="d-inline d-sm-none">Xuất</span>
                </button>
            </div>

            <!-- Thống kê nhanh -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card text-center p-3 h-100 border-0 shadow-sm mb-0">
                        <h3 class="mb-1 fw-bold"><?= number_format($stats['total_orders']) ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Tổng đơn</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card text-center p-3 h-100 border-0 shadow-sm mb-0">
                        <h3 class="mb-1 text-warning fw-bold"><?= number_format($stats['pending_orders']) ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Chờ xử lý</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card text-center p-3 h-100 border-0 shadow-sm mb-0">
                        <h3 class="mb-1 text-info fw-bold"><?= number_format($stats['processing_orders']) ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Đang xử lý</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card text-center p-3 h-100 border-0 shadow-sm mb-0">
                        <h3 class="mb-1 text-success fw-bold"><?= number_format($stats['completed_orders']) ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Hoàn thành</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card text-center p-3 h-100 border-0 shadow-sm mb-0">
                        <h3 class="mb-1 text-danger fw-bold"><?= number_format($stats['cancelled_orders']) ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Đã hủy</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card text-center p-3 h-100 border-0 shadow-sm bg-primary text-white mb-0">
                        <h4 class="mb-1 fw-bold"><?= formatMoney($stats['today_revenue']) ?></h4>
                        <small class="text-white-50 text-uppercase fw-bold" style="font-size: 11px;">Doanh thu hôm nay</small>
                    </div>
                </div>
            </div>

            <!-- Bộ lọc -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                             <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-search text-secondary"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" style="border-radius: 0 12px 12px 0;" placeholder="Mã đơn, khách hàng..." value="<?= e($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="order_status" class="form-select" style="border-radius: 12px;">
                                <option value="">Tất cả trạng thái</option>
                                <option value="pending" <?= $orderStatus === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                                <option value="processing" <?= $orderStatus === 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
                                <option value="completed" <?= $orderStatus === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                <option value="cancelled" <?= $orderStatus === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="payment_status" class="form-select" style="border-radius: 12px;">
                                <option value="">Thanh toán</option>
                                <option value="pending" <?= $paymentStatus === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                                <option value="confirming" <?= $paymentStatus === 'confirming' ? 'selected' : '' ?>>Đang xác nhận</option>
                                <option value="paid" <?= $paymentStatus === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                                <option value="failed" <?= $paymentStatus === 'failed' ? 'selected' : '' ?>>Thất bại</option>
                                <option value="refunded" <?= $paymentStatus === 'refunded' ? 'selected' : '' ?>>Hoàn tiền</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_from" class="form-control" style="border-radius: 12px;" value="<?= e($dateFrom) ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_to" class="form-control" style="border-radius: 12px;" value="<?= e($dateTo) ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="pill-button pill-button-gray w-100 justify-content-center">
                                <i class="fas fa-filter me-2"></i> Lọc
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danh sách đơn hàng -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>
                                    <a href="<?= getSortUrl('order_code', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        Mã đơn <?= getSortIcon('order_code', $sortBy, $sortOrder) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('customer_name', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        Khách hàng <?= getSortIcon('customer_name', $sortBy, $sortOrder) ?>
                                    </a>
                                </th>
                                <th>Sản phẩm</th>
                                <th>
                                    <a href="<?= getSortUrl('final_amount', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        Tổng tiền <?= getSortIcon('final_amount', $sortBy, $sortOrder) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('payment_status', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        Thanh toán <?= getSortIcon('payment_status', $sortBy, $sortOrder) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('payment_method', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        Phương thức <?= getSortIcon('payment_method', $sortBy, $sortOrder) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('order_status', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        Trạng thái <?= getSortIcon('order_status', $sortBy, $sortOrder) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('created_at', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        Thời gian <?= getSortIcon('created_at', $sortBy, $sortOrder) ?>
                                    </a>
                                </th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-secondary py-5">
                                        <i class="fas fa-shopping-bag fa-3x mb-3 opacity-25"></i>
                                        <p>Không tìm thấy đơn hàng nào</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                    // Lấy sản phẩm trong đơn (Logic cũ)
                                    $items = [];
                                    try {
                                        $items = db()->query(
                                            "SELECT oi.*, p.image, p.slug
                                             FROM order_items oi
                                             LEFT JOIN products p ON oi.product_id = p.id
                                             WHERE oi.order_id = ?",
                                            [$order['id']]
                                        )->fetchAll();
                                        // ... logic xử lý ảnh/variant như cũ ...
                                        foreach ($items as &$item) {
                                            if ($item['variant_id']) {
                                                $variant = db()->query("SELECT variant_image, slug as variant_slug FROM product_variants WHERE id = ?", [$item['variant_id']])->fetch();
                                                if ($variant) {
                                                    $item['variant_image'] = $variant['variant_image'];
                                                    $item['variant_slug'] = $variant['variant_slug'];
                                                }
                                            }
                                            if ($item['product_id']) {
                                                $gallery = db()->query("SELECT image_path FROM product_gallery WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1", [$item['product_id']])->fetch();
                                                if ($gallery) {
                                                    $item['first_gallery_image'] = $gallery['image_path'];
                                                }
                                            }
                                        }
                                    } catch (Exception $e) { $items = []; }
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="/order-detail?code=<?= e($order['order_code']) ?>" target="_blank" class="fw-bold text-primary text-decoration-none">
                                                #<?= e($order['order_code']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= e($order['display_name']) ?></div>
                                            <div class="small text-secondary"><?= e($order['display_email']) ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                            <?php foreach ($items as $item): ?>
                                                <div class="small text-dark">
                                                    <?= e($item['product_name'] ?? 'Sản phẩm đã xóa') ?> <span class="text-secondary fw-bold">x<?= $item['quantity'] ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-primary">
                                            <?= formatMoney($order['final_amount']) ?>
                                        </td>
                                        <td>
                                            <?php
                                            $paymentBadge = [
                                                'pending' => ['bg' => 'bg-warning', 'text' => 'Chờ TT'],
                                                'confirming' => ['bg' => 'bg-info', 'text' => 'Đang xác thực'],
                                                'paid' => ['bg' => 'bg-success', 'text' => 'Đã TT'],
                                                'completed' => ['bg' => 'bg-success', 'text' => 'Hoàn thành'],
                                                'failed' => ['bg' => 'bg-danger', 'text' => 'Thất bại'],
                                                'refunded' => ['bg' => 'bg-secondary', 'text' => 'Hoàn tiền']
                                            ];
                                            $badge = $paymentBadge[$order['payment_status']] ?? ['bg' => 'bg-secondary', 'text' => $order['payment_status']];
                                            ?>
                                            <span class="badge <?= $badge['bg'] ?> rounded-pill"><?= $badge['text'] ?></span>
                                        </td>
                                        <td>
                                            <span class="small text-secondary"><i class="fas fa-credit-card me-1"></i> <?= e($order['payment_method']) ?></span>
                                        </td>
                                        <td>
                                             <?php
                                            $statusBadge = [
                                                'pending' => ['class' => 'text-warning', 'icon' => 'fa-clock'],
                                                'processing' => ['class' => 'text-info', 'icon' => 'fa-spinner'],
                                                'completed' => ['class' => 'text-success', 'icon' => 'fa-check-circle'],
                                                'cancelled' => ['class' => 'text-danger', 'icon' => 'fa-times-circle']
                                            ];
                                            $st = $statusBadge[$order['order_status']] ?? ['class' => 'text-secondary', 'icon' => 'fa-circle'];
                                            ?>
                                            <div class="<?= $st['class'] ?> fw-bold small">
                                                <i class="fas <?= $st['icon'] ?>"></i> <?= ucfirst($order['order_status']) ?>
                                            </div>
                                        </td>
                                        <td class="text-secondary small">
                                            <?= date('H:i d/m', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="/admin/order-detail?id=<?= $order['id'] ?>" class="btn btn-sm btn-light text-info rounded-circle me-1" title="Chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-light text-primary rounded-circle" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#statusModal<?= $order['id'] ?>" title="Cập nhật">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
                                <a class="page-link rounded-start-pill border-end-0" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            for ($i = $startPage; $i <= $endPage; $i++) :
                            ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Page -->
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-end-pill border-start-0" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- Modals cập nhật trạng thái -->
    <?php foreach ($orders as $order): ?>
        <div class="modal fade" id="statusModal<?= $order['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="border-radius: 18px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Cập nhật đơn #<?= e($order['order_code']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="update_status" value="1">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Trạng thái đơn hàng</label>
                                <select name="order_status" class="form-select" style="border-radius: 12px;">
                                    <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                                    <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
                                    <option value="completed" <?= $order['order_status'] === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                    <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Trạng thái thanh toán</label>
                                <select name="payment_status" class="form-select" style="border-radius: 12px;">
                                    <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                                    <option value="confirming" <?= $order['payment_status'] === 'confirming' ? 'selected' : '' ?>>Đang xác nhận</option>
                                    <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Đã thanh toán (Tự động giao hàng)</option>
                                    <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Thất bại</option>
                                    <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>Hoàn tiền</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="pill-button pill-button-white" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" class="pill-button pill-button-gray">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
