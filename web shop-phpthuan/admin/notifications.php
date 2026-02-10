<?php
$pageTitle = 'Quản lý Thông Báo';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

// Kiểm tra quyền admin
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

// Xử lý xóa thông báo
if (isset($_GET['delete']) && $_GET['delete']) {
    $notifId = (int)$_GET['delete'];
    db()->query("DELETE FROM notifications WHERE id = ?", [$notifId]);
    setFlash('success', 'Đã xóa thông báo');
    redirect('/admin/notifications.php');
}

// Xử lý bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $selectedIds = $_POST['selected_notifications'] ?? [];
    if (!empty($selectedIds)) {
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        db()->query("DELETE FROM notifications WHERE id IN ($placeholders)", $selectedIds);
        setFlash('success', 'Đã xóa ' . count($selectedIds) . ' thông báo');
        redirect('/admin/notifications.php');
    }
}

// Xử lý đánh dấu đã đọc
if (isset($_GET['mark_read']) && $_GET['mark_read']) {
    $notifId = (int)$_GET['mark_read'];
    db()->query("UPDATE notifications SET is_read = 1 WHERE id = ?", [$notifId]);
    redirect('/admin/notifications.php');
}

// Xử lý gửi thông báo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $type = $_POST['type'] ?? 'info';
    $link = trim($_POST['link'] ?? '');
    $targetType = $_POST['target_type'] ?? 'all';
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);

    if (empty($title) || empty($content)) {
        setFlash('error', 'Vui lòng nhập đầy đủ tiêu đề và nội dung');
    } else {
        if ($targetType === 'all') {
            $count = createBroadcastNotification($title, $content, $type, $link);
            setFlash('success', "Đã gửi thông báo đến $count người dùng");
        } elseif ($targetType === 'single' && $targetUserId > 0) {
            if (createNotification($targetUserId, $title, $content, $type, $link)) {
                setFlash('success', "Đã gửi thông báo thành công");
            } else {
                setFlash('error', "Không thể gửi thông báo");
            }
        } else {
            setFlash('error', 'Vui lòng chọn người nhận');
        }
        redirect('/admin/notifications.php');
    }
}

// Lấy danh sách user
$users = db()->query("SELECT id, username, email FROM users WHERE status = 'active' ORDER BY username")->fetchAll();

// Thống kê
$stats = [
    'total' => db()->query("SELECT COUNT(*) as count FROM notifications")->fetch()['count'],
    'unread' => db()->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0")->fetch()['count'],
    'today' => db()->query("SELECT COUNT(*) as count FROM notifications WHERE DATE(created_at) = CURDATE()")->fetch()['count'],
    'this_week' => db()->query("SELECT COUNT(*) as count FROM notifications WHERE YEARWEEK(created_at) = YEARWEEK(NOW())")->fetch()['count'],
];

// Phân trang và lọc
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;
// Sorting
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';
$allowedSortColumns = ['type', 'title', 'created_at'];

if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_at';
}

$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
$orderBy = "n.$sortBy $sortOrder";

$filterStatus = $_GET['status'] ?? '';
$filterType = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($filterStatus === 'unread') {
    $where[] = "n.is_read = 0";
} elseif ($filterStatus === 'read') {
    $where[] = "n.is_read = 1";
}

if ($filterType) {
    $where[] = "n.type = ?";
    $params[] = $filterType;
}

if ($search) {
    $where[] = "(n.title LIKE ? OR n.content LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$notifications = db()->query(
    "SELECT n.*, u.username, u.email
     FROM notifications n
     JOIN users u ON n.user_id = u.id
     $whereClause
     ORDER BY $orderBy
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
)->fetchAll();

$totalNotifications = db()->query(
    "SELECT COUNT(*) as count FROM notifications n JOIN users u ON n.user_id = u.id $whereClause",
    $params
)->fetch()['count'];

$totalPages = ceil($totalNotifications / $perPage);

// Thống kê theo loại
$typeStats = db()->query(
    "SELECT type, COUNT(*) as count
     FROM notifications
     GROUP BY type"
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
    <title><?= e($pageTitle) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/admin-apple-style.css">
    <style>
        .notification-item {
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
            border-left-color: #0d6efd;
        }
        .notification-item.unread {
            background-color: #f0f8ff;
        }
        .stat-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .template-btn {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center my-4">
                <div>

                    <p class="text-secondary mb-0">Gửi và quản lý thông báo đến người dùng</p>
                </div>
                <button type="button" class="pill-button pill-button-gray" data-bs-toggle="modal" data-bs-target="#sendNotificationModal">
                    <i class="fas fa-paper-plane"></i> Gửi Thông Báo Mới
                </button>
            </div>

            <!-- Thống kê -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card h-100" onclick="window.location='?status='">
                        <div class="card-body text-center">
                            <i class="fas fa-bell fa-3x text-primary mb-3"></i>
                            <h2 class="mb-0"><?= number_format($stats['total']) ?></h2>
                            <p class="text-muted mb-0">Tổng thông báo</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card h-100 border-warning" onclick="window.location='?status=unread'">
                        <div class="card-body text-center">
                            <i class="fas fa-envelope fa-3x text-warning mb-3"></i>
                            <h2 class="mb-0 text-warning"><?= number_format($stats['unread']) ?></h2>
                            <p class="text-muted mb-0">Chưa đọc</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card h-100 border-success" onclick="window.location='?type=success'">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-day fa-3x text-success mb-3"></i>
                            <h3 class="mb-0 fw-bold text-success"><?= number_format($stats['today']) ?></h3>
                            <small class="text-secondary fw-bold text-uppercase" style="font-size: 10px;">Hôm nay</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card h-100 border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-week fa-3x text-info mb-3"></i>
                            <h3 class="mb-0 fw-bold text-info"><?= number_format($stats['this_week']) ?></h3>
                            <small class="text-secondary fw-bold text-uppercase" style="font-size: 10px;">Tuần này</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bộ lọc -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-filter"></i> Bộ lọc</h6>
                        <?php if ($search || $filterStatus || $filterType): ?>
                            <a href="/admin/notifications.php" class="small text-danger text-decoration-none">
                                <i class="fas fa-times"></i> Xóa lọc
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm..." value="<?= e($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <option value="unread" <?= $filterStatus === 'unread' ? 'selected' : '' ?>>Chưa đọc</option>
                                <option value="read" <?= $filterStatus === 'read' ? 'selected' : '' ?>>Đã đọc</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="type" class="form-select">
                                <option value="">Tất cả loại</option>
                                <option value="success" <?= $filterType === 'success' ? 'selected' : '' ?>>Success</option>
                                <option value="info" <?= $filterType === 'info' ? 'selected' : '' ?>>Info</option>
                                <option value="warning" <?= $filterType === 'warning' ? 'selected' : '' ?>>Warning</option>
                                <option value="error" <?= $filterType === 'error' ? 'selected' : '' ?>>Error</option>
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

            <!-- Danh sách thông báo -->
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-list"></i> Danh sách thông báo
                            <span class="badge bg-secondary"><?= number_format($totalNotifications) ?></span>
                        </h6>
                        <div class="dropdown">
                            <button class="pill-button pill-button-white dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-tasks"></i> Thao tác hàng loạt
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="bulkDelete()">
                                        <i class="fas fa-trash"></i> Xóa đã chọn
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <form method="POST" id="bulkForm">
                        <div class="table-responsive">
                            <table class="table-modern w-100">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </th>
                                        <th width="80">
                                            <a href="<?= getSortUrl('type', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Loại <?= getSortIcon('type', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= getSortUrl('title', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Tiêu đề <?= getSortIcon('title', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>Người nhận</th>
                                        <th width="100">Trạng thái</th>
                                        <th>
                                            <a href="<?= getSortUrl('created_at', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Ngày gửi <?= getSortIcon('created_at', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th width="150">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($notifications)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                Không có thông báo nào
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($notifications as $notif): ?>
                                            <tr class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                                                <td>
                                                    <input type="checkbox" class="form-check-input notification-checkbox" name="selected_notifications[]" value="<?= $notif['id'] ?>">
                                                </td>
                                                <td>
                                                    <?php
                                                    $typeConfig = [
                                                        'success' => ['class' => 'success', 'icon' => 'check-circle'],
                                                        'info' => ['class' => 'info', 'icon' => 'info-circle'],
                                                        'warning' => ['class' => 'warning', 'icon' => 'exclamation-triangle'],
                                                        'error' => ['class' => 'danger', 'icon' => 'times-circle']
                                                    ];
                                                    $config = $typeConfig[$notif['type']] ?? ['class' => 'secondary', 'icon' => 'bell'];
                                                    ?>
                                                    <i class="fas fa-<?= $config['icon'] ?> fa-2x text-<?= $config['class'] ?>"></i>
                                                </td>
                                                <td>
                                                    <strong><?= e($notif['title']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= e(mb_substr($notif['content'], 0, 60)) ?>...</small>
                                                </td>
                                                <td>
                                                    <div>
                                                        <i class="fas fa-user"></i> <?= e($notif['username']) ?>
                                                    </div>
                                                    <small class="text-muted"><?= e($notif['email']) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($notif['is_read']): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check"></i> Đã đọc
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-envelope"></i> Chưa đọc
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-info icon-only-button" data-bs-toggle="modal" data-bs-target="#viewModal<?= $notif['id'] ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if (!$notif['is_read']): ?>
                                                            <a href="?mark_read=<?= $notif['id'] ?>" class="btn btn-outline-success icon-only-button" title="Đánh dấu đã đọc">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="?delete=<?= $notif['id'] ?>" class="btn btn-outline-danger icon-only-button" onclick="return confirm('Xóa thông báo này?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?= $notif['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-<?= $config['class'] ?> text-white">
                                                            <h5 class="modal-title">
                                                                <i class="fas fa-<?= $config['icon'] ?>"></i>
                                                                <?= e($notif['title']) ?>
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><?= nl2br(e($notif['content'])) ?></p>
                                                            <?php if ($notif['link']): ?>
                                                                <div class="alert alert-info">
                                                                    <i class="fas fa-link"></i>
                                                                    <strong>Link:</strong>
                                                                    <a href="<?= e($notif['link']) ?>" target="_blank">
                                                                        <?= e($notif['link']) ?>
                                                                    </a>
                                                                </div>
                                                            <?php endif; ?>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-user"></i> Người nhận:<br>
                                                                        <strong><?= e($notif['username']) ?></strong>
                                                                    </small>
                                                                </div>
                                                                <div class="col-6 text-end">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-clock"></i> Ngày gửi:<br>
                                                                        <strong><?= formatDate($notif['created_at']) ?></strong>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="card-footer bg-white">
                            <nav>
                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>&sort=<?= $sortBy ?>&order=<?= $sortOrder ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <!-- Modal Gửi Thông Báo -->
    <div class="modal fade" id="sendNotificationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-paper-plane"></i> Gửi Thông Báo Mới</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="notifTitle" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nội dung <span class="text-danger">*</span></label>
                                    <textarea name="content" id="notifContent" class="form-control" rows="5" required></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Loại thông báo</label>
                                        <select name="type" class="form-select">
                                            <option value="success">Success (Xanh lá)</option>
                                            <option value="info" selected>Info (Xanh dương)</option>
                                            <option value="warning">Warning (Vàng)</option>
                                            <option value="error">Error (Đỏ)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Gửi đến <span class="text-danger">*</span></label>
                                        <select name="target_type" class="form-select" id="targetType" onchange="toggleUserSelect()">
                                            <option value="all">Tất cả người dùng</option>
                                            <option value="single">Một người dùng</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3" id="userSelectDiv" style="display: none;">
                                    <label class="form-label">Chọn người dùng</label>
                                    <select name="target_user_id" class="form-select" id="userSelect">
                                        <option value="">-- Chọn user --</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?= $u['id'] ?>">
                                                <?= e($u['username']) ?> (<?= e($u['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Link liên quan (tùy chọn)</label>
                                    <input type="text" name="link" class="form-control" placeholder="/products hoặc https://...">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-file-alt"></i> Mẫu nhanh</h6>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary template-btn" onclick="useTemplate('Khuyến mãi đặc biệt', 'Giảm giá lên đến 50% cho tất cả sản phẩm. Nhanh tay đặt hàng ngay!')">
                                                Khuyến mãi
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success template-btn" onclick="useTemplate('Sản phẩm mới', 'Chúng tôi vừa cập nhật nhiều sản phẩm mới. Hãy xem ngay!')">
                                                Sản phẩm mới
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning template-btn" onclick="useTemplate('Bảo trì hệ thống', 'Hệ thống sẽ bảo trì vào lúc 23:00 hôm nay. Vui lòng hoàn tất giao dịch trước đó.')">
                                                Bảo trì
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info template-btn" onclick="useTemplate('Cập nhật tính năng', 'Website đã được cập nhật nhiều tính năng mới. Khám phá ngay!')">
                                                Cập nhật
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3 small">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Lưu ý:</strong>
                                    <ul class="mb-0 mt-2 ps-3">
                                        <li>Thông báo sẽ hiển thị ngay lập tức</li>
                                        <li>Người dùng sẽ thấy ở trang chủ</li>
                                        <li>Có thể gửi cho 1 hoặc nhiều user</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Đóng
                        </button>
                        <button type="submit" name="send_notification" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Gửi Thông Báo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Select all
        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.notification-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
        });

        // Bulk delete
        function bulkDelete() {
            const checked = document.querySelectorAll('.notification-checkbox:checked');
            if (checked.length === 0) {
                alert('Vui lòng chọn ít nhất 1 thông báo!');
                return;
            }

            if (confirm(`Bạn có chắc muốn xóa ${checked.length} thông báo?`)) {
                const form = document.getElementById('bulkForm');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'bulk_delete';
                input.value = '1';
                form.appendChild(input);
                form.submit();
            }
        }

        // Toggle user select
        function toggleUserSelect() {
            const targetType = document.getElementById('targetType').value;
            const userSelectDiv = document.getElementById('userSelectDiv');
            const userSelect = document.getElementById('userSelect');

            if (targetType === 'single') {
                userSelectDiv.style.display = 'block';
                userSelect.required = true;
            } else {
                userSelectDiv.style.display = 'none';
                userSelect.required = false;
            }
        }

        // Use template
        function useTemplate(title, content) {
            document.getElementById('notifTitle').value = title;
            document.getElementById('notifContent').value = content;
        }
    </script>

    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
</body>
</html>
