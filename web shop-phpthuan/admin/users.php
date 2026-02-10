<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Quản lý người dùng';

// Xử lý cập nhật role/status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $userId = (int)$_POST['user_id'];
        $role = $_POST['role'];
        $status = $_POST['status'];

        db()->query(
            "UPDATE users SET role = ?, status = ?, updated_at = NOW() WHERE id = ?",
            [$role, $status, $userId]
        );

        setFlash('success', 'Đã cập nhật người dùng');
        redirect('/admin/users.php');
    }

    // Xử lý cập nhật số dư
    if (isset($_POST['update_balance'])) {
        $userId = (int)$_POST['user_id'];
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

        setFlash('success', 'Đã cập nhật số dư thành công');
        redirect('/admin/users.php');
    }
}

// Bộ lọc
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Sorting
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';
$allowedSortColumns = ['id', 'username', 'email', 'full_name', 'role', 'status', 'created_at', 'order_count', 'total_spent'];

if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_at';
}

$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Map sort columns
$sortColumnMap = [
    'id' => 'u.id',
    'username' => 'u.username',
    'email' => 'u.email',
    'full_name' => 'u.full_name',
    'role' => 'u.role',
    'status' => 'u.status',
    'created_at' => 'u.created_at',
    'order_count' => 'order_count',
    'total_spent' => 'total_spent'
];

$orderByColumn = $sortColumnMap[$sortBy] ?? 'u.created_at';

$where = [];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role) {
    $where[] = "role = ?";
    $params[] = $role;
}

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$users = db()->query(
    "SELECT u.*,
     (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
     (SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE user_id = u.id AND payment_status = 'paid') as total_spent
     FROM users u
     $whereClause
     ORDER BY $orderByColumn $sortOrder
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
)->fetchAll();

$totalUsers = db()->query("SELECT COUNT(*) as count FROM users $whereClause", $params)->fetch()['count'];
$totalPages = ceil($totalUsers / $perPage);

// Thống kê
$stats = [
    'total' => db()->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'customers' => db()->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch()['count'],
    'admins' => db()->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch()['count'],
    'active' => db()->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch()['count'],
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
            <div class="d-flex justify-content-between align-items-center my-4">
                <div>
                    <h4 class="fw-bold fs-4 mb-1">Thành viên</h4>
                    <p class="text-secondary mb-0">Danh sách <?= $totalUsers ?> thành viên</p>
                </div>
                <button class="pill-button pill-button-white">
                    <i class="fas fa-file-export"></i>
                    <span class="d-none d-sm-inline">Xuất dữ liệu</span>
                    <span class="d-inline d-sm-none">Xuất</span>
                </button>
            </div>

            <!-- Thống kê -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px;">
                             <i class="fas fa-users text-primary fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold"><?= $stats['total'] ?></h3>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Tổng người dùng</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px;">
                             <i class="fas fa-user text-info fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold text-info"><?= $stats['customers'] ?></h3>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Khách hàng</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center">
                        <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px;">
                            <i class="fas fa-user-shield text-danger fa-lg"></i>
                        </div>
                         <div>
                            <h3 class="mb-0 fw-bold text-danger"><?= $stats['admins'] ?></h3>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Quản trị viên</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                     <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px;">
                            <i class="fas fa-check-circle text-success fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold text-success"><?= $stats['active'] ?></h3>
                            <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Đang hoạt động</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bộ lọc -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-search text-secondary"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" style="border-radius: 0 12px 12px 0;" placeholder="Tên, email,..." value="<?= e($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="role" class="form-select" style="border-radius: 12px;">
                                <option value="">Tất cả vai trò</option>
                                <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Khách hàng</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select" style="border-radius: 12px;">
                                <option value="">Tất cả trạng thái</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Bị khóa</option>
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

            <!-- Danh sách -->
            <div class="card">
                <div class="table-responsive">
                        <table class="table-modern table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center" width="50">
                                        <a href="<?= getSortUrl('id', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center justify-content-center">
                                            ID <?= getSortIcon('id', $sortBy, $sortOrder) ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?= getSortUrl('username', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                            Thành viên <?= getSortIcon('username', $sortBy, $sortOrder) ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?= getSortUrl('email', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                            Liên hệ <?= getSortIcon('email', $sortBy, $sortOrder) ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?= getSortUrl('role', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                            Vai trò <?= getSortIcon('role', $sortBy, $sortOrder) ?>
                                        </a>
                                    </th>
                                    <th class="text-center">
                                        <a href="<?= getSortUrl('order_count', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center justify-content-center">
                                            Đơn hàng <?= getSortIcon('order_count', $sortBy, $sortOrder) ?>
                                        </a>
                                    </th>
                                    <th class="text-center">
                                        <a href="<?= getSortUrl('total_spent', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center justify-content-center">
                                            Chi tiêu <?= getSortIcon('total_spent', $sortBy, $sortOrder) ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?= getSortUrl('status', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                            Trạng thái <?= getSortIcon('status', $sortBy, $sortOrder) ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?= getSortUrl('created_at', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                            Ngày tham gia <?= getSortIcon('created_at', $sortBy, $sortOrder) ?>
                                        </a>
                                    </th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-secondary py-5">
                                            <i class="fas fa-users-slash fa-3x mb-3 opacity-25"></i>
                                            <p>Không tìm thấy người dùng nào</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="text-center align-middle">
                                                <span class="text-secondary">#<?= $user['id'] ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3 fs-5 fw-bold shadow-sm" style="width: 42px; height: 42px; background: linear-gradient(135deg, #667eea 0%, #7387df 100%);">
                                                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= e($user['username']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="text-dark small"><i class="fas fa-envelope text-secondary me-2"></i><?= e($user['email']) ?></div>
                                                <div class="text-dark small"><i class="fas fa-phone text-secondary me-2"></i><?= e($user['phone'] ?: '---') ?></div>
                                            </td>
                                            <td class="align-middle">
                                                <?php if ($user['role'] === 'admin'): ?>
                                                    <span class="badge bg-danger rounded-pill"><i class="fas fa-user-shield me-1"></i> Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary rounded-pill"><i class="fas fa-user me-1"></i> Khách</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge bg-light text-dark border rounded-pill px-3"><?= $user['order_count'] ?> đơn</span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="fw-bold text-dark"><?= formatMoney($user['total_spent']) ?></div>
                                                <div class="small text-secondary">Số dư: <?= formatMoney($user['balance']) ?></div>
                                            </td>
                                            <td class="align-middle">
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <span class="badge bg-success rounded-pill">Hoạt động</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary rounded-pill">Bị khóa</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle">
                                                <span class="text-secondary small"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                                                <div class="text-muted" style="font-size: 11px;"><?= date('H:i', strtotime($user['created_at'])) ?></div>
                                            </td>
                                            <td class="text-end align-middle">
                                                <button type="button" class="btn btn-sm btn-light text-success rounded-circle me-1" 
                                                        data-bs-toggle="modal" data-bs-target="#balanceModal<?= $user['id'] ?>" title="Nạp/Trừ tiền">
                                                    <i class="fas fa-wallet"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light text-primary rounded-circle me-1" 
                                                        data-bs-toggle="modal" data-bs-target="#editModal<?= $user['id'] ?>" title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="/admin/user-detail?id=<?= $user['id'] ?>" class="btn btn-sm btn-light text-secondary rounded-circle" title="Chi tiết">
                                                    <i class="fas fa-ellipsis-h"></i>
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
                                <a class="page-link rounded-start-pill border-end-0" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= $role ?>&status=<?= $status ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= $role ?>&status=<?= $status ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-end-pill border-start-0" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= $role ?>&status=<?= $status ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
    </div>

    <!-- Modals -->
    <?php foreach ($users as $user): ?>
        <!-- Edit Modal -->
        <div class="modal fade" id="editModal<?= $user['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                    <div class="modal-header border-0 pb-0">
                         <h5 class="modal-title fw-bold">Cập nhật: <?= e($user['username']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body pt-4">
                            <input type="hidden" name="update_user" value="1">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

                            <div class="mb-4">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Vai trò hệ thống</label>
                                <select name="role" class="form-select" style="border-radius: 12px;">
                                    <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Khách hàng</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin (Toàn quyền)</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small text-secondary fw-bold text-uppercase">Trạng thái tài khoản</label>
                                <select name="status" class="form-select" style="border-radius: 12px;">
                                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Hoạt động bình thường</option>
                                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Khóa tài khoản (Banned)</option>
                                </select>
                            </div>

                            <div class="p-3 bg-light rounded-3">
                                <small class="text-secondary d-block mb-1">Email: <span class="text-dark fw-bold"><?= e($user['email']) ?></span></small>
                                <small class="text-secondary d-block">Ngày tham gia: <span class="text-dark fw-bold"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></span></small>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="pill-button pill-button-white" data-bs-dismiss="modal">Hủy bỏ</button>
                            <button type="submit" class="pill-button pill-button-gray">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Balance Modal -->
        <div class="modal fade" id="balanceModal<?= $user['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Cập nhật số dư ví</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="update_balance" value="1">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

                            <div class="text-center mb-4">
                                <span class="d-block text-secondary mb-1">Tài khoản: <strong><?= e($user['username']) ?></strong></span>
                                <span class="d-block text-secondary mb-1">Số dư hiện tại</span>
                                <h2 class="text-success fw-bold"><?= formatMoney($user['balance']) ?></h2>
                            </div>
                            
                            <div class="bg-light p-3 rounded-3 mb-3">
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="type" id="type_add<?= $user['id'] ?>" value="add" checked>
                                    <label class="btn btn-outline-success" for="type_add<?= $user['id'] ?>"><i class="fas fa-plus me-2"></i>Cộng tiền</label>

                                    <input type="radio" class="btn-check" name="type" id="type_subtract<?= $user['id'] ?>" value="subtract">
                                    <label class="btn btn-outline-danger" for="type_subtract<?= $user['id'] ?>"><i class="fas fa-minus me-2"></i>Trừ tiền</label>
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
                            <button type="submit" class="pill-button pill-button-gray px-4">Xác nhận</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
