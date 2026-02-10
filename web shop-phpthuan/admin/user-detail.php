<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    setFlash('error', 'Người dùng không tồn tại');
    redirect('/admin/users.php');
}

// Lấy thông tin người dùng
$user = db()->query("SELECT * FROM users WHERE id = ?", [$userId])->fetch();

if (!$user) {
    setFlash('error', 'Người dùng không tồn tại');
    redirect('/admin/users.php');
}

$pageTitle = 'Chi tiết người dùng: ' . $user['username'];

// Xử lý cập nhật số dư
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
    $amount = (float)$_POST['amount'];
    $type = $_POST['type']; // add hoặc subtract
    $note = trim($_POST['note'] ?? '');

    if ($type === 'add') {
        db()->query(
            "UPDATE users SET balance = balance + ? WHERE id = ?",
            [$amount, $userId]
        );
    } else {
        db()->query(
            "UPDATE users SET balance = balance - ? WHERE id = ?",
            [$amount, $userId]
        );
    }

    // Ghi log transaction
    db()->query(
        "INSERT INTO transactions (user_id, type, amount, description, created_at)
         VALUES (?, ?, ?, ?, NOW())",
        [$userId, $type === 'add' ? 'admin_add' : 'admin_subtract', $amount, $note]
    );

    setFlash('success', 'Đã cập nhật số dư');
    redirect('/admin/user-detail?id=' . $userId);
}

// Lấy đơn hàng
$orders = db()->query(
    "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 20",
    [$userId]
)->fetchAll();

// Lấy transactions
$transactions = db()->query(
    "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20",
    [$userId]
)->fetchAll();

// Lấy lịch sử đăng nhập (New)
try {
    // Sorting logic
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';
    $validColumns = ['created_at', 'ip_address', 'user_agent'];
    
    if (!in_array($sort, $validColumns)) {
        $sort = 'created_at';
    }
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

    $loginHistory = db()->query(
        "SELECT * FROM user_logins WHERE user_id = ? ORDER BY $sort $order LIMIT 20",
        [$userId]
    )->fetchAll();
} catch (Exception $e) {
    $loginHistory = [];
}

// Thống kê
$stats = [
    'total_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$userId])->fetch()['count'],
    'total_spent' => db()->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE user_id = ? AND payment_status = 'paid'", [$userId])->fetch()['total'],
    'completed_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND order_status = 'completed'", [$userId])->fetch()['count'],
    'pending_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND payment_status = 'pending'", [$userId])->fetch()['count'],
    'total_reviews' => db()->query("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?", [$userId])->fetch()['count'],
];
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
        <!-- Header Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
             <div class="d-flex align-items-center gap-3">
                <a href="/admin/users.php" class="btn btn-outline-secondary rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                   <h4 class="fw-bold mb-0 text-dark"><?= e($user['username']) ?></h4>
                   <p class="text-secondary mb-0 small">User ID: #<?= $user['id'] ?></p>
                </div>
            </div>
            <div class="d-flex gap-2">
                 <button class="pill-button pill-button-white text-danger">
                    <i class="fas fa-ban me-2"></i> Khóa tài khoản
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: User Info & Stats -->
            <div class="col-lg-4">
                <!-- User Profile Card -->
                <div class="card mb-4 text-center p-4">
                     <div class="position-relative d-inline-block mx-auto mb-3">
                        <div class="rounded-circle bg-primary-soft text-primary d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 2.5rem;">
                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                        </div>
                        <span class="position-absolute bottom-0 end-0 p-2 bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?> border border-white rounded-circle">
                            <span class="visually-hidden">Status</span>
                        </span>
                    </div>
                    
                    <h5 class="fw-bold mb-1"><?= e($user['full_name'] ?: $user['username']) ?></h5>
                    <p class="text-secondary mb-3"><?= e($user['email']) ?></p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-4">
                         <span class="badge bg-secondary-soft text-dark rounded-pill px-3 py-2">
                            <?= e(ucfirst($user['role'])) ?>
                        </span>
                        <span class="badge bg-success-soft text-success rounded-pill px-3 py-2">
                             <?= e(ucfirst($user['status'])) ?>
                        </span>
                    </div>
                    
                    <hr class="opacity-10 my-0">
                    
                    <div class="user-details-list text-start mt-3">
                         <div class="d-flex align-items-center py-2">
                            <div class="icon-square bg-light text-secondary me-3">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-secondary d-block">Số dư ví</small>
                                <span class="fw-bold text-success h5 mb-0"><?= formatMoney($user['balance']) ?></span>
                            </div>
                            <button class="pill-button pill-button-gray px-4" data-bs-toggle="modal" data-bs-target="#balanceModal">
                                <i class="fas fa-plus-circle me-1"></i> Nạp/Trừ
                            </button>

                        </div>
                        
                        <?php if ($user['phone']): ?>
                        <div class="d-flex align-items-center py-2">
                             <div class="icon-square bg-light text-secondary me-3">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <small class="text-secondary d-block">Điện thoại</small>
                                <span class="text-dark fw-medium"><?= e($user['phone']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex align-items-center py-2">
                             <div class="icon-square bg-light text-secondary me-3">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <small class="text-secondary d-block">Ngày tham gia</small>
                                <span class="text-dark fw-medium"><?= formatDate($user['created_at']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                 <!-- Stats Summary -->
                <div class="card">
                     <div class="card-header border-0 bg-white pt-4 px-4 pb-0">
                        <h6 class="fw-bold mb-0">Thống kê hoạt động</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 text-center">
                                    <h4 class="fw-bold text-primary mb-0"><?= $stats['total_orders'] ?></h4>
                                    <small class="text-secondary">Đơn hàng</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 text-center">
                                    <h4 class="fw-bold text-success mb-0"><?= formatMoney($stats['total_spent']) ?></h4>
                                    <small class="text-secondary">Chi tiêu</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 text-center">
                                    <h4 class="fw-bold text-info mb-0"><?= $stats['completed_orders'] ?></h4>
                                    <small class="text-secondary">Thành công</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 text-center">
                                    <h4 class="fw-bold text-warning mb-0"><?= $stats['pending_orders'] ?></h4>
                                    <small class="text-secondary">Đang chờ</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Tabs -->
            <div class="col-lg-8">
                 <div class="card h-100">
                     <div class="card-header border-0 bg-white pt-3 px-3">
                         <ul class="nav nav-pills custom-pills gap-2" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active rounded-pill px-4" data-bs-toggle="tab" href="#orders">
                                    <i class="fas fa-shopping-cart me-2"></i>Đơn hàng
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link rounded-pill px-4" data-bs-toggle="tab" href="#transactions">
                                    <i class="fas fa-exchange-alt me-2"></i>Giao dịch
                                </a>
                            </li>
                             <li class="nav-item">
                                <a class="nav-link rounded-pill px-4" data-bs-toggle="tab" href="#login-history">
                                    <i class="fas fa-history me-2"></i>Lịch sử đăng nhập
                                </a>
                            </li>
                        </ul>
                     </div>
                     
                     <div class="card-body p-0">
                         <div class="tab-content">
                             <!-- Orders Tab -->
                             <div class="tab-pane fade show active" id="orders">
                                 <?php if (empty($orders)): ?>
                                    <div class="text-center py-5">
                                        <div class="opacity-25 mb-3">
                                            <i class="fas fa-shopping-cart fa-3x"></i>
                                        </div>
                                        <p class="text-secondary">Chưa có đơn hàng nào</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Mã đơn</th>
                                                    <th>Tổng tiền</th>
                                                    <th>Trạng thái</th>
                                                    <th>Ngày tạo</th>
                                                    <th class="text-end pe-4">Chi tiết</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="font-monospace fw-bold text-primary">#<?= e($order['order_code']) ?></span>
                                                    </td>
                                                    <td class="fw-bold"><?= formatMoney($order['final_amount']) ?></td>
                                                    <td>
                                                        <?php
                                                        $statusBadge = [
                                                            'pending' => 'bg-warning-soft text-warning',
                                                            'processing' => 'bg-info-soft text-info',
                                                            'completed' => 'bg-success-soft text-success',
                                                            'cancelled' => 'bg-danger-soft text-danger'
                                                        ];
                                                        ?>
                                                        <span class="badge rounded-pill <?= $statusBadge[$order['order_status']] ?? 'bg-secondary' ?>">
                                                            <?= e(ucfirst($order['order_status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-secondary small"><?= date('H:i d/m/Y', strtotime($order['created_at'])) ?></td>
                                                    <td class="text-end pe-4">
                                                        <a href="/admin/order-detail?id=<?= $order['id'] ?>" class="btn btn-sm btn-light rounded-circle">
                                                            <i class="fas fa-chevron-right"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                             </div>

                             <!-- Transactions Tab -->
                             <div class="tab-pane fade" id="transactions">
                                 <?php if (empty($transactions)): ?>
                                     <div class="text-center py-5">
                                        <div class="opacity-25 mb-3">
                                            <i class="fas fa-exchange-alt fa-3x"></i>
                                        </div>
                                        <p class="text-secondary">Chưa có giao dịch nào</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($transactions as $trans): ?>
                                            <div class="list-group-item px-4 py-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <?php
                                                        $typeIcon = [
                                                            'deposit' => ['icon' => 'plus', 'bg' => 'bg-success-soft', 'color' => 'text-success'],
                                                            'withdraw' => ['icon' => 'minus', 'bg' => 'bg-danger-soft', 'color' => 'text-danger'],
                                                            'purchase' => ['icon' => 'shopping-bag', 'bg' => 'bg-primary-soft', 'color' => 'text-primary'],
                                                            'refund' => ['icon' => 'undo', 'bg' => 'bg-warning-soft', 'color' => 'text-warning'],
                                                            'admin_add' => ['icon' => 'plus-circle', 'bg' => 'bg-success-soft', 'color' => 'text-success'],
                                                            'admin_subtract' => ['icon' => 'minus-circle', 'bg' => 'bg-danger-soft', 'color' => 'text-danger'],
                                                        ];
                                                        $style = $typeIcon[$trans['type']] ?? ['icon' => 'circle', 'bg' => 'bg-light', 'color' => 'text-secondary'];
                                                        ?>
                                                        <div class="rounded-circle <?= $style['bg'] ?> <?= $style['color'] ?> d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                                            <i class="fas fa-<?= $style['icon'] ?>"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold"><?= e(ucfirst($trans['type'])) ?></h6>
                                                            <small class="text-secondary"><?= e($trans['description']) ?></small>
                                                            <div class="small text-muted mt-1"><?= date('H:i d/m/Y', strtotime($trans['created_at'])) ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="fw-bold <?= in_array($trans['type'], ['deposit', 'refund', 'admin_add']) ? 'text-success' : 'text-danger' ?>">
                                                            <?= in_array($trans['type'], ['deposit', 'refund', 'admin_add']) ? '+' : '-' ?><?= formatMoney($trans['amount']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                             </div>
                             
                             <!-- Login History Tab (New) -->
                             <div class="tab-pane fade" id="login-history">
                                 <?php if (empty($loginHistory)): ?>
                                     <div class="text-center py-5">
                                        <div class="opacity-25 mb-3">
                                            <i class="fas fa-history fa-3x"></i>
                                        </div>
                                        <p class="text-secondary">Chưa có lịch sử đăng nhập</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">
                                                        <a href="<?= getSortUrl('created_at', $sort, $order) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                            Thời gian <?= getSortIcon('created_at', $sort, $order) ?>
                                                        </a>
                                                    </th>
                                                    <th>
                                                        <a href="<?= getSortUrl('ip_address', $sort, $order) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                            IP Address <?= getSortIcon('ip_address', $sort, $order) ?>
                                                        </a>
                                                    </th>
                                                    <th>
                                                        <a href="<?= getSortUrl('user_agent', $sort, $order) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                            Thiết bị (User Agent) <?= getSortIcon('user_agent', $sort, $order) ?>
                                                        </a>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($loginHistory as $log): ?>
                                                <tr>
                                                    <td class="ps-4 text-secondary"><?= date('H:i:s d/m/Y', strtotime($log['created_at'])) ?></td>
                                                    <td>
                                                        <span class="font-monospace text-primary bg-primary-soft px-2 py-1 rounded small">
                                                            <?= e($log['ip_address']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted text-truncate d-block" style="max-width: 300px;" title="<?= e($log['user_agent']) ?>">
                                                            <?= e($log['user_agent']) ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                             </div>
                         </div>
                     </div>
                 </div>
            </div>
        </div>
    </div>

    <!-- Modal cập nhật số dư -->
    <div class="modal fade" id="balanceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Cập nhật số dư ví</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <span class="d-block text-secondary mb-1">Số dư hiện tại</span>
                            <h2 class="text-success fw-bold"><?= formatMoney($user['balance']) ?></h2>
                        </div>
                        
                        <div class="bg-light p-3 rounded-3 mb-3">
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="type" id="type_add" value="add" checked>
                                <label class="btn btn-outline-success" for="type_add"><i class="fas fa-plus me-2"></i>Cộng tiền</label>

                                <input type="radio" class="btn-check" name="type" id="type_subtract" value="subtract">
                                <label class="btn btn-outline-danger" for="type_subtract"><i class="fas fa-minus me-2"></i>Trừ tiền</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Số tiền (VNĐ)</label>
                            <input type="number" name="amount" class="form-control form-control-lg" required min="1000" step="1000" placeholder="0" style="border-radius: 12px;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Ghi chú</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Lý do điều chỉnh số dư..." required style="border-radius: 12px;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="pill-button pill-button-white" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="update_balance" class="pill-button pill-button-gray px-4">Xác nhận</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .icon-square {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px;
        }
        .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
        .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
        .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
        .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
        .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
        .bg-secondary-soft { background-color: rgba(108, 117, 125, 0.1); }
        
        .custom-pills .nav-link {
            color: #6c757d;
            background: transparent;
            font-weight: 500;
        }
        .custom-pills .nav-link.active {
            background-color: #f8f9fa;
            color: #0d6efd;
        }
    </style>
<?php
// Helper functions for sorting (Local scope)
if (!function_exists('getSortUrl')) {
    function getSortUrl($column, $currentSort, $currentOrder) {
        $params = $_GET;
        $params['sort'] = $column;
        $params['order'] = ($column === $currentSort && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
        return '?' . http_build_query($params);
    }
}

if (!function_exists('getSortIcon')) {
    function getSortIcon($column, $currentSort, $currentOrder) {
        if ($column !== $currentSort) {
            return '<i class="fas fa-sort" style="opacity: 0.3; margin-left: 6px;"></i>';
        }
        return '<i class="fas ' . ($currentOrder === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') . '" style="margin-left: 6px;"></i>';
    }
}
?>
</body>
</html>
