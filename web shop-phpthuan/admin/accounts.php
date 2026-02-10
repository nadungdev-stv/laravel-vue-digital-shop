<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

// Kiểm tra quyền admin
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Quản lý kho tài khoản';

// Xử lý bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selectedIds = $_POST['selected_accounts'] ?? [];

    if (!empty($selectedIds)) {
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));

        switch ($action) {
            case 'delete':
                db()->query("DELETE FROM accounts_stock WHERE id IN ($placeholders)", $selectedIds);
                setFlash('success', 'Đã xóa ' . count($selectedIds) . ' tài khoản');
                break;

            case 'mark_available':
                db()->query("UPDATE accounts_stock SET status = 'available', updated_at = NOW() WHERE id IN ($placeholders)", $selectedIds);
                setFlash('success', 'Đã cập nhật ' . count($selectedIds) . ' tài khoản thành "Sẵn sàng"');
                break;

            case 'mark_reserved':
                db()->query("UPDATE accounts_stock SET status = 'reserved', updated_at = NOW() WHERE id IN ($placeholders)", $selectedIds);
                setFlash('success', 'Đã cập nhật ' . count($selectedIds) . ' tài khoản thành "Đang giữ"');
                break;

            case 'export':
                // Export to CSV
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=accounts_' . date('Y-m-d_His') . '.csv');

                $output = fopen('php://output', 'w');
                fputcsv($output, ['ID', 'Sản phẩm', 'Username', 'Password', 'Ghi chú', 'Trạng thái', 'Ngày tạo']);

                $accounts = db()->query(
                    "SELECT a.*, p.name as product_name
                     FROM accounts_stock a
                     JOIN products p ON a.product_id = p.id
                     WHERE a.id IN ($placeholders)",
                    $selectedIds
                )->fetchAll();

                foreach ($accounts as $acc) {
                    fputcsv($output, [
                        $acc['id'],
                        $acc['product_name'],
                        $acc['account_username'],
                        $acc['account_password'],
                        $acc['account_note'],
                        $acc['status'],
                        $acc['created_at']
                    ]);
                }
                fclose($output);
                exit;
        }
        redirect('/admin/accounts.php');
    }
}

// Xử lý xóa đơn lẻ
if (isset($_GET['delete']) && $_GET['delete']) {
    $accountId = (int)$_GET['delete'];
    db()->query("DELETE FROM accounts_stock WHERE id = ?", [$accountId]);
    setFlash('success', 'Đã xóa tài khoản');
    redirect('/admin/accounts.php');
}

// Xử lý inline edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inline_edit'])) {
    $accountId = (int)$_POST['account_id'];
    $field = $_POST['field'];
    $value = $_POST['value'];

    $allowedFields = ['status', 'account_note'];
    if (in_array($field, $allowedFields)) {
        db()->query(
            "UPDATE accounts_stock SET $field = ?, updated_at = NOW() WHERE id = ?",
            [$value, $accountId]
        );
        echo json_encode(['success' => true]);
        exit;
    }
}

// Export toàn bộ
if (isset($_GET['export_all'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=all_accounts_' . date('Y-m-d_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Sản phẩm', 'Username', 'Password', 'Ghi chú', 'Trạng thái', 'Ngày tạo', 'Ngày cập nhật']);

    $allAccounts = db()->query(
        "SELECT a.*, p.name as product_name
         FROM accounts_stock a
         JOIN products p ON a.product_id = p.id
         ORDER BY a.created_at DESC"
    )->fetchAll();

    foreach ($allAccounts as $acc) {
        fputcsv($output, [
            $acc['id'],
            $acc['product_name'],
            $acc['account_username'],
            $acc['account_password'],
            $acc['account_note'],
            $acc['status'],
            $acc['created_at'],
            $acc['updated_at']
        ]);
    }
    fclose($output);
    exit;
}

// Bộ lọc
$search = $_GET['search'] ?? '';
$productId = $_GET['product'] ?? 0;
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
$offset = ($page - 1) * $perPage;

// Sorting
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';
$allowedSortColumns = ['id', 'product_name', 'account_username', 'account_note', 'status', 'created_at'];

if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_at';
}

$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Map sort columns
$sortColumnMap = [
    'id' => 'a.id',
    'product_name' => 'p.name',
    'account_username' => 'a.account_username',
    'account_note' => 'a.account_note',
    'status' => 'a.status',
    'created_at' => 'a.created_at'
];

$orderByColumn = $sortColumnMap[$sortBy] ?? 'a.created_at';

$where = [];
$params = [];

if ($search) {
    $where[] = "(a.account_username LIKE ? OR a.account_password LIKE ? OR a.account_note LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($productId) {
    $where[] = "a.product_id = ?";
    $params[] = $productId;
}

if ($status) {
    $where[] = "a.status = ?";
    $params[] = $status;
}

if ($dateFrom) {
    $where[] = "DATE(a.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(a.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$accounts = db()->query(
    "SELECT a.*, p.name as product_name
     FROM accounts_stock a
     JOIN products p ON a.product_id = p.id
     $whereClause
     $whereClause
     ORDER BY $orderByColumn $sortOrder
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
)->fetchAll();

$totalAccounts = db()->query(
    "SELECT COUNT(*) as count FROM accounts_stock a JOIN products p ON a.product_id = p.id $whereClause",
    $params
)->fetch()['count'];

$totalPages = ceil($totalAccounts / $perPage);

// Lấy danh sách sản phẩm
$products = db()->query("SELECT id, name FROM products WHERE delivery_type = 'account' ORDER BY name")->fetchAll();

// Thống kê tổng quan
$stats = [
    'total' => db()->query("SELECT COUNT(*) as count FROM accounts_stock")->fetch()['count'],
    'available' => db()->query("SELECT COUNT(*) as count FROM accounts_stock WHERE status = 'available'")->fetch()['count'],
    'sold' => db()->query("SELECT COUNT(*) as count FROM accounts_stock WHERE status = 'sold'")->fetch()['count'],
    'reserved' => db()->query("SELECT COUNT(*) as count FROM accounts_stock WHERE status = 'reserved'")->fetch()['count'],
];

// Thống kê theo sản phẩm
$productStats = db()->query(
    "SELECT p.id, p.name,
     COUNT(*) as total,
     SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available,
     SUM(CASE WHEN a.status = 'sold' THEN 1 ELSE 0 END) as sold,
     SUM(CASE WHEN a.status = 'reserved' THEN 1 ELSE 0 END) as reserved
     FROM accounts_stock a
     JOIN products p ON a.product_id = p.id
     GROUP BY a.product_id, p.id, p.name
     ORDER BY available ASC, p.name"
)->fetchAll();

// Cảnh báo sản phẩm sắp hết
$lowStockProducts = array_filter($productStats, function($stat) {
    return $stat['available'] < 5 && $stat['available'] > 0;
});

// Thống kê theo thời gian (7 ngày gần nhất)
$dailyStats = db()->query(
    "SELECT DATE(created_at) as date,
     COUNT(*) as total,
     SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
     SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold
     FROM accounts_stock
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at)
     ORDER BY date DESC"
)->fetchAll();


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
    <style>
        .account-row {
            transition: background 0.2s;
        }
        .account-row:hover {
            background-color: #f8f9fa;
        }
        .copy-btn {
            opacity: 0;
            transition: all 0.2s;
            cursor: pointer;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }
        .copy-btn:hover {
            background: #e5e5e7;
            color: #0071e3;
        }
        .account-row:hover .copy-btn {
            opacity: 1;
        }
        .editable-field {
            cursor: pointer;
            border-radius: 6px;
            padding: 4px 8px;
            transition: background 0.2s;
        }
        .editable-field:hover {
            background-color: #f5f5f7;
            box-shadow: inset 0 0 0 1px #e5e5e7;
        }
        .low-stock-card {
            background: #fff0f0;
            border: 1px solid #ffcccc;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
        
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>

                <p class="text-secondary mb-0">Quản lý và nhập liệu tài khoản</p>
            </div>
            <div class="d-flex gap-2">
                <a href="/admin/account-add.php" class="pill-button pill-button-gray">
                    <i class="fas fa-plus"></i> Thêm mới
                </a>
                <a href="/admin/account-import.php" class="pill-button pill-button-white text-success border-success">
                    <i class="fas fa-file-excel"></i> Import Excel
                </a>
                <a href="?export_all=1" class="pill-button pill-button-white text-secondary border-secondary">
                    <i class="fas fa-download"></i> Export
                </a>
            </div>
        </div>

        <!-- Cảnh báo tồn kho thấp -->
        <?php if (!empty($lowStockProducts)): ?>
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" style="border-radius: 12px; background: #fff5f5;">
                <i class="fas fa-exclamation-triangle text-danger fa-2x me-3"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold text-danger">Cảnh báo tồn kho thấp!</div>
                    <div class="small">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 me-1 mb-1">
                                <?= e($product['name']) ?>: <?= $product['available'] ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Thống kê -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card p-3 border-0 shadow-sm h-100 text-center" onclick="filterByStatus('')" style="cursor: pointer;">
                    <h3 class="fw-bold mb-1"><?= number_format($stats['total']) ?></h3>
                    <small class="text-secondary fw-bold text-uppercase">Tổng tài khoản</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-0 shadow-sm h-100 text-center" onclick="filterByStatus('available')" style="cursor: pointer;">
                    <h3 class="fw-bold mb-1 text-success"><?= number_format($stats['available']) ?></h3>
                    <small class="text-secondary fw-bold text-uppercase">Sẵn sàng</small>
                </div>
            </div>
             <div class="col-md-3">
                <div class="card p-3 border-0 shadow-sm h-100 text-center" onclick="filterByStatus('sold')" style="cursor: pointer;">
                    <h3 class="fw-bold mb-1 text-primary"><?= number_format($stats['sold']) ?></h3>
                    <small class="text-secondary fw-bold text-uppercase">Đã bán</small>
                </div>
            </div>
             <div class="col-md-3">
                <div class="card p-3 border-0 shadow-sm h-100 text-center" onclick="filterByStatus('reserved')" style="cursor: pointer;">
                    <h3 class="fw-bold mb-1 text-warning"><?= number_format($stats['reserved']) ?></h3>
                    <small class="text-secondary fw-bold text-uppercase">Đang giữ</small>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-9">
                <!-- Filter -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                         <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-filter text-primary me-2"></i>Bộ lọc nâng cao</h6>
                            <?php if ($search || $productId || $status || $dateFrom || $dateTo): ?>
                                <a href="/admin/accounts.php" class="small text-danger text-decoration-none">
                                    <i class="fas fa-times"></i> Xóa lọc
                                </a>
                            <?php endif; ?>
                        </div>
                        <form method="GET" id="filterForm">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-search text-secondary"></i></span>
                                        <input type="text" name="search" class="form-control border-start-0 ps-0" style="border-radius: 0 12px 12px 0;" placeholder="Username, Note..." value="<?= e($search) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <select name="product" class="form-select" style="border-radius: 12px;">
                                        <option value="">Tất cả sản phẩm</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= $product['id'] ?>" <?= $productId == $product['id'] ? 'selected' : '' ?>>
                                                <?= e($product['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select name="status" class="form-select" style="border-radius: 12px;">
                                         <option value="">Tất cả trạng thái</option>
                                        <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Sẵn sàng</option>
                                        <option value="sold" <?= $status === 'sold' ? 'selected' : '' ?>>Đã bán</option>
                                        <option value="reserved" <?= $status === 'reserved' ? 'selected' : '' ?>>Đang giữ</option>
                                    </select>
                                </div>
                            </div>
                             <div class="row g-3">
                                <div class="col-md-3">
                                    <input type="date" name="date_from" class="form-control" title="Từ ngày" style="border-radius: 12px;" value="<?= e($dateFrom) ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type="date" name="date_to" class="form-control" title="Đến ngày" style="border-radius: 12px;" value="<?= e($dateTo) ?>">
                                </div>
                                 <div class="col-md-2">
                                    <select name="per_page" class="form-select" style="border-radius: 12px;" title="Số dòng">
                                        <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                                        <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                                        <option value="200" <?= $perPage == 200 ? 'selected' : '' ?>>200</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                     <button type="submit" class="pill-button pill-button-gray w-100 justify-content-center">Lọc kết quả</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table -->
                <div class="card border-0 shadow-sm">
                     <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold mb-0">Danh sách tài khoản</h6>
                            <span class="badge bg-light text-secondary rounded-pill"><?= number_format($totalAccounts) ?></span>
                        </div>
                        
                         <!-- Bulk Actions -->
                         <div class="dropdown">
                            <button class="pill-button pill-button-white dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Hành động hàng loạt
                            </button>
                            <ul class="dropdown-menu border-0 shadow-lg p-2" style="border-radius: 12px;">
                                <li><a class="dropdown-item rounded-2" href="#" onclick="submitBulkAction('export')"><i class="fas fa-file-export me-2 text-primary"></i>Export đã chọn</a></li>
                                <li><a class="dropdown-item rounded-2" href="#" onclick="submitBulkAction('mark_available')"><i class="fas fa-check me-2 text-success"></i>Đặt 'Sẵn sàng'</a></li>
                                <li><a class="dropdown-item rounded-2" href="#" onclick="submitBulkAction('mark_reserved')"><i class="fas fa-clock me-2 text-warning"></i>Đặt 'Đang giữ'</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item rounded-2 text-danger" href="#" onclick="submitBulkAction('delete')"><i class="fas fa-trash me-2"></i>Xóa đã chọn</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <form method="POST" id="bulkForm">
                            <input type="hidden" name="bulk_action" id="bulkActionInput">
                            <div class="table-responsive">
                                <table class="table-modern w-100">
                                    <thead>
                                        <tr>
                                            <th width="40"><input type="checkbox" class="form-check-input" id="selectAllCheckbox"></th>
                                            <th>
                                                <a href="<?= getSortUrl('product_name', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                    Sản phẩm <?= getSortIcon('product_name', $sortBy, $sortOrder) ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="<?= getSortUrl('account_username', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                    Tài khoản / Mật khẩu <?= getSortIcon('account_username', $sortBy, $sortOrder) ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="<?= getSortUrl('status', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                    Trạng thái <?= getSortIcon('status', $sortBy, $sortOrder) ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="<?= getSortUrl('account_note', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                    Ghi chú <?= getSortIcon('account_note', $sortBy, $sortOrder) ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="<?= getSortUrl('created_at', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                    Ngày tạo <?= getSortIcon('created_at', $sortBy, $sortOrder) ?>
                                                </a>
                                            </th>
                                            <th class="text-end">#</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($accounts)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5 opacity-50">
                                                    <i class="fas fa-box-open fa-3x mb-3"></i>
                                                    <p>Không tìm thấy tài khoản nào</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($accounts as $account): ?>
                                                <tr class="account-row">
                                                    <td>
                                                        <input type="checkbox" class="form-check-input account-checkbox" name="selected_accounts[]" value="<?= $account['id'] ?>">
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" title="<?= e($account['product_name']) ?>">
                                                            <?= e($account['product_name']) ?>
                                                        </div>
                                                        <small class="text-secondary">#<?= $account['id'] ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center mb-1">
                                                            <div class="bg-light px-2 py-1 rounded me-2 font-monospace text-truncate" style="max-width: 150px;"><?= e($account['account_username']) ?></div>
                                                            <div class="copy-btn text-primary" onclick="copyText('<?= e($account['account_username']) ?>')">
                                                                <i class="far fa-copy"></i>
                                                            </div>
                                                        </div>
                                                         <div class="d-flex align-items-center">
                                                            <div class="bg-light px-2 py-1 rounded me-2 font-monospace text-truncate" style="max-width: 150px; color: #86868b;"><?= e($account['account_password']) ?></div>
                                                            <div class="copy-btn text-primary" onclick="copyText('<?= e($account['account_password']) ?>')">
                                                                <i class="far fa-copy"></i>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                         <?php
                                                            $statusBadge = [
                                                                'available' => ['class' => 'bg-success', 'text' => 'Sẵn sàng'],
                                                                'sold' => ['class' => 'bg-primary', 'text' => 'Đã bán'],
                                                                'reserved' => ['class' => 'bg-warning', 'text' => 'Đang giữ']
                                                            ];
                                                            $bg = $statusBadge[$account['status']]['class'] ?? 'bg-secondary';
                                                            $text = $statusBadge[$account['status']]['text'] ?? $account['status'];
                                                        ?>
                                                        <div class="editable-field" onclick="<?= $account['status'] !== 'sold' ? "editStatus({$account['id']}, '{$account['status']}')" : '' ?>">
                                                            <span class="badge <?= $bg ?> rounded-pill"><?= $text ?></span>
                                                        </div>
                                                    </td>
                                                     <td>
                                                        <div class="editable-field text-secondary small" onclick="editNote(<?= $account['id'] ?>, '<?= e($account['account_note']) ?>')">
                                                            <?= $account['account_note'] ? e($account['account_note']) : '<span class="opacity-50 fst-italic">Thêm ghi chú...</span>' ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-secondary small">
                                                        <?= date('d/m/Y', strtotime($account['created_at'])) ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow p-2" style="border-radius: 12px;">
                                                                <li><a class="dropdown-item rounded-2" href="#" onclick="copyBoth('<?= e($account['account_username']) ?>', '<?= e($account['account_password']) ?>')"><i class="fas fa-clone me-2 text-primary"></i>Copy cả hai</a></li>
                                                                <?php if ($account['status'] !== 'sold'): ?>
                                                                    <li><hr class="dropdown-divider"></li>
                                                                    <li><a class="dropdown-item rounded-2 text-danger" href="?delete=<?= $account['id'] ?>" onclick="return confirm('Xóa tài khoản này?')"><i class="fas fa-trash me-2"></i>Xóa</a></li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>

                     <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-white border-0 py-3">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link rounded-start-pill border-end-0" href="?page=<?= $page - 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link rounded-end-pill border-start-0" href="?page=<?= $page + 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                 <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom fw-bold py-3">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>Tồn kho theo sản phẩm
                    </div>
                    <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($productStats as $stat): ?>
                             <div class="list-group-item px-3 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-bold small text-truncate" style="max-width: 150px;"><?= e($stat['name']) ?></div>
                                    <a href="?product=<?= $stat['id'] ?>" class="btn btn-xs btn-light rounded-pill px-2 py-0 border" style="font-size: 10px;">Chi tiết</a>
                                </div>
                                <div class="progress mb-2" style="height: 6px; border-radius: 3px;">
                                    <div class="progress-bar bg-success" style="width: <?= $stat['total'] > 0 ? ($stat['available'] / $stat['total'] * 100) : 0 ?>%"></div>
                                    <div class="progress-bar bg-warning" style="width: <?= $stat['total'] > 0 ? ($stat['reserved'] / $stat['total'] * 100) : 0 ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-secondary">
                                    <span><span class="text-success fw-bold"><?= $stat['available'] ?></span> sẵn có</span>
                                    <span><span class="text-primary fw-bold"><?= $stat['sold'] ?></span> đã bán</span>
                                </div>
                             </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom fw-bold py-3">
                        <i class="fas fa-history me-2 text-primary"></i>Nhập mới 7 ngày qua
                    </div>
                    <div class="list-group list-group-flush">
                         <?php if (empty($dailyStats)): ?>
                                <div class="list-group-item text-secondary small text-center py-3">Chưa có dữ liệu</div>
                        <?php else: ?>
                            <?php foreach ($dailyStats as $dayStat): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div>
                                        <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($dayStat['date'])) ?></div>
                                        <small class="text-success">+<?= $dayStat['available'] ?> sẵn sàng</small>
                                    </div>
                                    <span class="badge bg-light text-dark border"><?= $dayStat['total'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterByStatus(status) {
            const url = new URL(window.location.href);
            url.searchParams.set('status', status);
            url.searchParams.set('page', 1); // Reset page
            window.location.href = url.toString();
        }

        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.account-checkbox');
            const selectAll = document.getElementById('selectAllCheckbox');
            const isChecked = !checkboxes[0]?.checked; // Toggle based on first item
            
            checkboxes.forEach(cb => cb.checked = isChecked);
            if(selectAll) selectAll.checked = isChecked;
        }

        // Sync select all checkbox
        document.getElementById('selectAllCheckbox')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.account-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        function submitBulkAction(action) {
            const checked = document.querySelectorAll('.account-checkbox:checked');
            if (checked.length === 0) {
                alert('Vui lòng chọn ít nhất 1 tài khoản');
                return;
            }

            if (action === 'delete' && !confirm(`Bạn có chắc muốn xóa ${checked.length} tài khoản đã chọn?`)) {
                return;
            }

            document.getElementById('bulkActionInput').value = action;
            document.getElementById('bulkForm').submit();
        }

        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Could show a toast here
                 alert('Đã copy: ' + text); // Simple alert for now, or use a custom toast
            });
        }

        function copyBoth(user, pass) {
            const text = `User: ${user}\nPass: ${pass}`;
            copyText(text);
        }

        function editNote(id, currentNote) {
            const newNote = prompt('Nhập ghi chú mới:', currentNote);
            if (newNote !== null && newNote !== currentNote) {
                updateAccount(id, 'account_note', newNote);
            }
        }

        function editStatus(id, currentStatus) {
            // Simple mapping for prompt
            // In a real app, use a modal
            const statusMap = {'available': 'Sẵn sàng', 'reserved': 'Đang giữ', 'sold': 'Đã bán (Không thể sửa)'};
            
            // Allow cycling or selection? Let's keep it simple: Cycle available <-> reserved
            let newStatus = currentStatus === 'available' ? 'reserved' : 'available';
            
            if(confirm(`Đổi trạng thái thành "${newStatus === 'available' ? 'Sẵn sàng' : 'Đang giữ'}"?`)) {
                 updateAccount(id, 'status', newStatus);
            }
        }

        function updateAccount(id, field, value) {
            const formData = new FormData();
            formData.append('inline_edit', 1);
            formData.append('account_id', id);
            formData.append('field', field);
            formData.append('value', value);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload(); 
                } else {
                    alert('Lỗi cập nhật');
                }
            })
            .catch(err => console.error(err));
        }
    </script>
</body>
</html>
