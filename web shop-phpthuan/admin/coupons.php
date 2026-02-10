<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Quản lý mã giảm giá';

// Xử lý tạo mới coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['type'];
    $value = (float)$_POST['value'];
    $minOrderAmount = (float)$_POST['min_order_amount'];
    $maxDiscount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status = $_POST['status'];
    $description = trim($_POST['description']);

    // Validate
    if (empty($code)) {
        setFlash('error', 'Vui lòng nhập mã coupon');
        redirect('/admin/coupons.php');
    }

    if ($value <= 0) {
        setFlash('error', 'Giá trị giảm phải lớn hơn 0');
        redirect('/admin/coupons.php');
    }

    if ($type === 'percentage' && $value > 100) {
        setFlash('error', 'Giá trị % không được vượt quá 100');
        redirect('/admin/coupons.php');
    }

    // Kiểm tra mã đã tồn tại
    $existing = db()->query("SELECT id FROM coupons WHERE code = ?", [$code])->fetch();
    if ($existing) {
        setFlash('error', 'Mã coupon đã tồn tại');
        redirect('/admin/coupons.php');
    }

    try {
        db()->query(
            "INSERT INTO coupons (code, type, value, min_order_amount, max_discount, usage_limit, start_date, end_date, status, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$code, $type, $value, $minOrderAmount, $maxDiscount, $usageLimit, $startDate, $endDate, $status, $description]
        );
        setFlash('success', 'Đã tạo mã giảm giá thành công');
        redirect('/admin/coupons.php');
    } catch (Exception $e) {
        setFlash('error', 'Lỗi: ' . $e->getMessage());
        redirect('/admin/coupons.php');
    }
}

// Xử lý cập nhật coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_coupon'])) {
    $couponId = (int)$_POST['coupon_id'];
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['type'];
    $value = (float)$_POST['value'];
    $minOrderAmount = (float)$_POST['min_order_amount'];
    $maxDiscount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status = $_POST['status'];
    $description = trim($_POST['description']);

    // Validate
    if (empty($code)) {
        setFlash('error', 'Vui lòng nhập mã coupon');
        redirect('/admin/coupons.php');
    }

    if ($value <= 0) {
        setFlash('error', 'Giá trị giảm phải lớn hơn 0');
        redirect('/admin/coupons.php');
    }

    if ($type === 'percentage' && $value > 100) {
        setFlash('error', 'Giá trị % không được vượt quá 100');
        redirect('/admin/coupons.php');
    }

    // Kiểm tra mã đã tồn tại (trừ mã hiện tại)
    $existing = db()->query("SELECT id FROM coupons WHERE code = ? AND id != ?", [$code, $couponId])->fetch();
    if ($existing) {
        setFlash('error', 'Mã coupon đã tồn tại');
        redirect('/admin/coupons.php');
    }

    try {
        db()->query(
            "UPDATE coupons SET code = ?, type = ?, value = ?, min_order_amount = ?, max_discount = ?,
             usage_limit = ?, start_date = ?, end_date = ?, status = ?, description = ?
             WHERE id = ?",
            [$code, $type, $value, $minOrderAmount, $maxDiscount, $usageLimit, $startDate, $endDate, $status, $description, $couponId]
        );
        setFlash('success', 'Đã cập nhật mã giảm giá');
        redirect('/admin/coupons.php');
    } catch (Exception $e) {
        setFlash('error', 'Lỗi: ' . $e->getMessage());
        redirect('/admin/coupons.php');
    }
}

// Xử lý xóa
if (isset($_GET['delete']) && $_GET['delete']) {
    $couponId = (int)$_GET['delete'];

    // Kiểm tra xem coupon đã được sử dụng chưa
    $coupon = db()->query("SELECT code, used_count FROM coupons WHERE id = ?", [$couponId])->fetch();
    if ($coupon && $coupon['used_count'] > 0) {
        setFlash('warning', "Mã {$coupon['code']} đã được sử dụng {$coupon['used_count']} lần, không thể xóa. Bạn có thể vô hiệu hóa thay vì xóa.");
        redirect('/admin/coupons.php');
    }

    db()->query("DELETE FROM coupons WHERE id = ?", [$couponId]);
    setFlash('success', 'Đã xóa mã giảm giá');
    redirect('/admin/coupons.php');
}

// Bộ lọc
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Sorting
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';
$allowedSortColumns = ['code', 'value', 'min_order_amount', 'used_count', 'start_date', 'end_date', 'status', 'created_at'];

if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_at';
}

$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Map sort columns
$sortColumnMap = [
    'code' => 'code',
    'value' => 'value',
    'min_order_amount' => 'min_order_amount',
    'used_count' => 'used_count',
    'start_date' => 'start_date',
    'end_date' => 'end_date',
    'status' => 'status',
    'created_at' => 'created_at'
];

$orderByColumn = $sortColumnMap[$sortBy] ?? 'created_at';

$where = [];
$params = [];

if ($search) {
    $where[] = "(code LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

if ($type) {
    $where[] = "type = ?";
    $params[] = $type;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Lấy danh sách coupons
$coupons = db()->query(
    "SELECT * FROM coupons
     $whereClause
     $whereClause
     ORDER BY $orderByColumn $sortOrder
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
)->fetchAll();

$totalCoupons = db()->query(
    "SELECT COUNT(*) as count FROM coupons $whereClause",
    $params
)->fetch()['count'];

$totalPages = ceil($totalCoupons / $perPage);

// Thống kê
$stats = [
    'total' => db()->query("SELECT COUNT(*) as count FROM coupons")->fetch()['count'],
    'active' => db()->query("SELECT COUNT(*) as count FROM coupons WHERE status = 'active'")->fetch()['count'],
    'inactive' => db()->query("SELECT COUNT(*) as count FROM coupons WHERE status = 'inactive'")->fetch()['count'],
    'expired' => db()->query("SELECT COUNT(*) as count FROM coupons WHERE status = 'expired'")->fetch()['count'],
    'total_used' => db()->query("SELECT SUM(used_count) as total FROM coupons")->fetch()['total'] ?: 0,
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

// Coupon được sử dụng nhiều nhất
$topUsed = db()->query(
    "SELECT code, type, value, used_count, status
     FROM coupons
     WHERE used_count > 0
     ORDER BY used_count DESC
     LIMIT 5"
)->fetchAll();
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
        .coupon-code {
            font-family: 'SF Mono', 'Courier New', monospace;
            font-weight: 700;
            letter-spacing: 1px;
            background: #f5f5f7;
            padding: 4px 8px;
            border-radius: 6px;
            color: #1d1d1f;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
            <div class="d-flex justify-content-between align-items-center my-4">
                <div>

                    <p class="text-secondary mb-0">Quản lý chương trình khuyến mãi</p>
                </div>
                <button type="button" class="pill-button pill-button-blue" data-bs-toggle="modal" data-bs-target="#createCouponModal">
                    <i class="fas fa-plus"></i> Tạo mã mới
                </button>
            </div>

            <!-- Thống kê -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card text-center p-3 h-100 border-0 shadow-sm">
                        <h3 class="mb-1 fw-bold"><?= $stats['total'] ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Tổng mã</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3 h-100 border-0 shadow-sm">
                        <h3 class="mb-1 text-success fw-bold"><?= $stats['active'] ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Đang hoạt động</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3 h-100 border-0 shadow-sm">
                        <h3 class="mb-1 text-danger fw-bold"><?= $stats['expired'] ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Hết hạn</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3 h-100 border-0 shadow-sm">
                        <h3 class="mb-1 text-primary fw-bold"><?= number_format($stats['total_used']) ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Lượt sử dụng</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-9">
                    <!-- Bộ lọc -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-search text-secondary"></i></span>
                                        <input type="text" name="search" class="form-control border-start-0 ps-0" style="border-radius: 0 12px 12px 0;" placeholder="Mã coupon, mô tả..." value="<?= e($search) ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select name="type" class="form-select" style="border-radius: 12px;">
                                        <option value="">Tất cả loại</option>
                                        <option value="percentage" <?= $type === 'percentage' ? 'selected' : '' ?>>Phần trăm</option>
                                        <option value="fixed" <?= $type === 'fixed' ? 'selected' : '' ?>>Số tiền cố định</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="status" class="form-select" style="border-radius: 12px;">
                                        <option value="">Trạng thái</option>
                                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="pill-button pill-button-gray w-100 justify-content-center">
                                        <i class="fas fa-filter"></i> Lọc
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Danh sách coupons -->
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="<?= getSortUrl('code', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Mã Code <?= getSortIcon('code', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= getSortUrl('value', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Giá trị giảm <?= getSortIcon('value', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= getSortUrl('min_order_amount', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Điều kiện <?= getSortIcon('min_order_amount', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= getSortUrl('used_count', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Sử dụng <?= getSortIcon('used_count', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= getSortUrl('start_date', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Thời gian <?= getSortIcon('start_date', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= getSortUrl('status', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Trạng thái <?= getSortIcon('status', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($coupons)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-secondary">
                                                <i class="fas fa-ticket-alt fa-3x mb-3 opacity-25"></i>
                                                <p>Không tìm thấy mã giảm giá nào</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($coupons as $coupon): ?>
                                            <tr>
                                                <td>
                                                    <div class="coupon-code"><?= e($coupon['code']) ?></div>
                                                    <?php if ($coupon['description']): ?>
                                                        <div class="small text-secondary mt-1 text-truncate" style="max-width: 200px;"><?= e($coupon['description']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($coupon['type'] === 'percentage'): ?>
                                                        <span class="fw-bold text-primary"><?= number_format($coupon['value'], 0) ?>%</span>
                                                        <?php if ($coupon['max_discount']): ?>
                                                            <div class="small text-secondary">Tối đa <?= formatMoney($coupon['max_discount']) ?></div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="fw-bold text-success"><?= formatMoney($coupon['value']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="small text-secondary">Đơn từ:</div>
                                                    <div class="fw-bold"><?= $coupon['min_order_amount'] > 0 ? formatMoney($coupon['min_order_amount']) : '0đ' ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border rounded-pill">
                                                        <?= $coupon['used_count'] ?> / <?= $coupon['usage_limit'] ?: '∞' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($coupon['start_date']): ?>
                                                        <div class="small text-secondary">Từ: <?= date('d/m/Y', strtotime($coupon['start_date'])) ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($coupon['end_date']): ?>
                                                        <div class="small text-secondary">Đến: <?= date('d/m/Y', strtotime($coupon['end_date'])) ?></div>
                                                    <?php else: ?>
                                                        <div class="small text-secondary">Vĩnh viễn</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                     <?php
                                                    $statusBadge = [
                                                        'active' => ['class' => 'bg-success', 'text' => 'Hoạt động'],
                                                        'inactive' => ['class' => 'bg-secondary', 'text' => 'Tạm dừng'],
                                                        'expired' => ['class' => 'bg-danger', 'text' => 'Hết hạn']
                                                    ];
                                                    $bg = $statusBadge[$coupon['status']]['class'] ?? 'bg-secondary';
                                                    $text = $statusBadge[$coupon['status']]['text'] ?? ucfirst($coupon['status']);
                                                    ?>
                                                    <span class="badge <?= $bg ?> rounded-pill"><?= $text ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-light text-primary rounded-circle me-1" 
                                                            data-bs-toggle="modal" data-bs-target="#editCouponModal<?= $coupon['id'] ?>" title="Sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?delete=<?= $coupon['id'] ?>" class="btn btn-sm btn-light text-danger rounded-circle" 
                                                       onclick="return confirm('Bạn có chắc chắn muốn xóa mã <?= e($coupon['code']) ?>? Hành động này không thể hoàn tác.')" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
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
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link rounded-start-pill border-end-0" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                        <a class="page-link rounded-end-pill border-start-0" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar Right -->
                <div class="col-lg-3">
                     <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom fw-bold py-3">
                            Top Sử Dụng
                        </div>
                        <div class="list-group list-group-flush">
                            <?php if (empty($topUsed)): ?>
                                <div class="list-group-item text-secondary small text-center py-3">Chưa có dữ liệu</div>
                            <?php else: ?>
                                <?php foreach ($topUsed as $index => $item): ?>
                                    <div class="list-group-item d-flex align-items-center justify-content-between px-3 py-3">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-light text-secondary rounded-pill me-2">#<?= $index + 1 ?></span>
                                            <div>
                                                <div class="fw-bold text-dark small"><?= e($item['code']) ?></div>
                                                <small class="text-secondary"><?= $item['used_count'] ?> lượt</small>
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

    <!-- Modal Tạo Mới -->
    <div class="modal fade" id="createCouponModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                <form method="POST">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Tạo mã giảm giá mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-4">
                        <input type="hidden" name="create_coupon" value="1">
                        <div class="row g-3">
                             <div class="col-md-6">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Mã Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" placeholder="VD: SUMMERSALE" required style="text-transform: uppercase; border-radius: 12px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Loại</label>
                                <select name="type" class="form-select" id="couponTypeCreate" style="border-radius: 12px;">
                                    <option value="percentage">Phần trăm (%)</option>
                                    <option value="fixed">Số tiền (VNĐ)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Giá trị</label>
                                <input type="number" name="value" class="form-control" placeholder="VD: 10" required style="border-radius: 12px;">
                            </div>
                             <div class="col-md-6">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Đơn tối thiểu</label>
                                <input type="number" name="min_order_amount" class="form-control" value="0" style="border-radius: 12px;">
                            </div>
                             <div class="col-md-6" id="maxDiscountGroupCreate">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Giảm tối đa</label>
                                <input type="number" name="max_discount" class="form-control" placeholder="Để trống nếu không giới hạn" style="border-radius: 12px;">
                            </div>
                             <div class="col-md-4">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Giới hạn số lượng</label>
                                <input type="number" name="usage_limit" class="form-control" placeholder="Để trống = Vô hạn" style="border-radius: 12px;">
                            </div>
                             <div class="col-md-4">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Ngày bắt đầu</label>
                                <input type="datetime-local" name="start_date" class="form-control" style="border-radius: 12px;">
                            </div>
                             <div class="col-md-4">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Ngày kết thúc</label>
                                <input type="datetime-local" name="end_date" class="form-control" style="border-radius: 12px;">
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Trạng thái</label>
                                <select name="status" class="form-select" style="border-radius: 12px;">
                                    <option value="active">Hoạt động ngay</option>
                                    <option value="inactive">Tạm ẩn</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Mô tả ngắn</label>
                                <textarea name="description" class="form-control" rows="2" style="border-radius: 12px;"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="pill-button pill-button-white" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="pill-button pill-button-gray">Tạo mã</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modals Edit (Generate for each coupon) -->
    <?php foreach ($coupons as $coupon): ?>
    <div class="modal fade" id="editCouponModal<?= $coupon['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                <form method="POST">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Cập nhật mã: <?= e($coupon['code']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-4">
                        <input type="hidden" name="update_coupon" value="1">
                        <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Mã Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="<?= e($coupon['code']) ?>" required style="text-transform: uppercase; border-radius: 12px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Loại</label>
                                <select name="type" class="form-select" id="couponTypeEdit<?= $coupon['id'] ?>" style="border-radius: 12px;">
                                    <option value="percentage" <?= $coupon['type'] === 'percentage' ? 'selected' : '' ?>>Phần trăm (%)</option>
                                    <option value="fixed" <?= $coupon['type'] === 'fixed' ? 'selected' : '' ?>>Số tiền (VNĐ)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Giá trị</label>
                                <input type="number" name="value" class="form-control" value="<?= $coupon['value'] ?>" required style="border-radius: 12px;">
                            </div>
                             <div class="col-md-6">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Đơn tối thiểu</label>
                                <input type="number" name="min_order_amount" class="form-control" value="<?= $coupon['min_order_amount'] ?>" style="border-radius: 12px;">
                            </div>
                             <div class="col-md-6" id="maxDiscountGroupEdit<?= $coupon['id'] ?>" style="<?= $coupon['type'] === 'fixed' ? 'display:none' : '' ?>">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Giảm tối đa</label>
                                <input type="number" name="max_discount" class="form-control" value="<?= $coupon['max_discount'] ?>" style="border-radius: 12px;">
                            </div>
                             <div class="col-md-4">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Giới hạn số lượng</label>
                                <input type="number" name="usage_limit" class="form-control" value="<?= $coupon['usage_limit'] ?>" style="border-radius: 12px;">
                            </div>
                             <div class="col-md-4">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Ngày bắt đầu</label>
                                <input type="datetime-local" name="start_date" class="form-control" value="<?= $coupon['start_date'] ? date('Y-m-d\TH:i', strtotime($coupon['start_date'])) : '' ?>" style="border-radius: 12px;">
                            </div>
                             <div class="col-md-4">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Ngày kết thúc</label>
                                <input type="datetime-local" name="end_date" class="form-control" value="<?= $coupon['end_date'] ? date('Y-m-d\TH:i', strtotime($coupon['end_date'])) : '' ?>" style="border-radius: 12px;">
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Trạng thái</label>
                                <select name="status" class="form-select" style="border-radius: 12px;">
                                    <option value="active" <?= $coupon['status'] === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                                    <option value="inactive" <?= $coupon['status'] === 'inactive' ? 'selected' : '' ?>>Tạm ẩn</option>
                                    <option value="expired" <?= $coupon['status'] === 'expired' ? 'selected' : '' ?>>Hết hạn</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Mô tả ngắn</label>
                                <textarea name="description" class="form-control" rows="2" style="border-radius: 12px;"><?= e($coupon['description']) ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="pill-button pill-button-white" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="pill-button pill-button-gray">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle max discount input visibility
        document.getElementById('couponTypeCreate').addEventListener('change', function() {
            const group = document.getElementById('maxDiscountGroupCreate');
            if (this.value === 'fixed') {
                group.style.display = 'none';
            } else {
                group.style.display = 'block';
            }
        });

        <?php foreach ($coupons as $coupon): ?>
        document.getElementById('couponTypeEdit<?= $coupon['id'] ?>').addEventListener('change', function() {
            const group = document.getElementById('maxDiscountGroupEdit<?= $coupon['id'] ?>');
            if (this.value === 'fixed') {
                group.style.display = 'none';
            } else {
                group.style.display = 'block';
            }
        });
        <?php endforeach; ?>
    </script>
</body>
</html>
