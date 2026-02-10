<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Thống kê truy cập';

// Sorting
$sortBy = $_GET['sort'] ?? 'time';
$sortOrder = $_GET['order'] ?? 'DESC';
$sortMap = [
    'ip' => 'ip_address',
    'ua' => 'user_agent',
    'time' => 'last_activity'
];
$orderBy = $sortMap[$sortBy] ?? 'last_activity';
$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Get total views all time
$totalViews = db()->query("SELECT SUM(access_count) as total FROM visitor_stats")->fetch()['total'] ?? 0;
// Add current online users to total (approximate)
$currentOnline = db()->query("SELECT COUNT(*) FROM online_users")->fetchColumn();
$totalViews += $currentOnline;

$tab = $_GET['tab'] ?? 'live';

if ($tab === 'live') {
    // Get online users (Last 5 minutes)
    $onlineUsers = db()->query(
        "SELECT ip_address, MAX(user_agent) as user_agent, MAX(last_activity) as last_activity, COUNT(*) as hits 
         FROM online_users
         GROUP BY ip_address
         ORDER BY $orderBy $sortOrder"
    )->fetchAll();

    // Get visitors history (Last 7 days)
    $historyStats = db()->query(
        "SELECT * FROM visitor_stats
         WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
         ORDER BY date DESC"
    )->fetchAll();
} else {
    // History Tab logic
    $searchIp = $_GET['search_ip'] ?? '';
    $page = $_GET['page'] ?? 1;
    
    // Sorting for History
    $histSort = $_GET['sort'] ?? 'created_at';
    $histOrder = $_GET['order'] ?? 'DESC';
    $validHistSort = ['created_at', 'ip_address', 'request_uri', 'user_agent', 'hits'];
    if (!in_array($histSort, $validHistSort)) $histSort = 'created_at';
    $histOrder = strtoupper($histOrder) === 'ASC' ? 'ASC' : 'DESC';

    $whereHtml = "1=1";
    $params = [];
    if ($searchIp) {
        $whereHtml .= " AND ip_address LIKE ?";
        $params[] = "%$searchIp%";
    }
    
    // Check if table exists first (to avoid error on first run if tracking hasn't triggered yet)
    try {
        $logs = paginate(
            "SELECT ip_address, COUNT(*) as hits, MAX(created_at) as created_at, MAX(request_uri) as request_uri, MAX(user_agent) as user_agent 
             FROM visitor_logs 
             WHERE $whereHtml 
             GROUP BY ip_address 
             ORDER BY $histSort $histOrder", 
            $params, 
            $page, 
            20
        );
    } catch (Exception $e) {
        $logs = ['data' => [], 'total' => 0, 'page' => 1, 'total_pages' => 0];
    }
}

// Helper functions for sorting
function getSortUrl($column, $currentSort, $currentOrder) {
    global $tab;
    $params = $_GET;
    $params['tab'] = $tab; // Ensure tab is preserved
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
                <p class="text-secondary mb-0">Theo dõi lưu lượng truy cập thời gian thực</p>
            </div>
            <div class="d-flex gap-3">
                <div class="bg-white px-4 py-2 rounded-pill shadow-sm border d-flex align-items-center">
                    <i class="fas fa-eye text-primary me-2"></i>
                    <span class="text-secondary me-2">Tổng view:</span>
                    <span class="fw-bold text-dark"><?= number_format($totalViews) ?></span>
                </div>
                <button class="pill-button pill-button-white" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Làm mới
                </button>
            </div>
        </div>
        
        <!-- Tabs -->
        <ul class="nav pill-button custom-pills gap-2" role="tablist">
            <li class="nav-item">
                <a class="nav-link rounded-pill px-4 <?= $tab === 'live' ? 'active' : '' ?>" href="?tab=live">
                    <i class="fas fa-broadcast-tower me-2"></i>Trực tiếp
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill px-4 <?= $tab === 'history' ? 'active' : '' ?>" href="?tab=history">
                    <i class="fas fa-history me-2"></i>Lịch sử chi tiết
                </a>
            </li>
        </ul>

        <?php if ($tab === 'live'): ?>
        <div class="row g-4">
            <!-- Online Users Table -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="d-flex align-items-center gap-2">
                             <span class="position-relative d-flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-success"></span>
                            </span>
                            Đang Online (<?= count($onlineUsers) ?>)
                        </span>
                        <span class="badge bg-success rounded-pill">Live</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="<?= getSortUrl('ip', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                IP Address <?= getSortIcon('ip', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= getSortUrl('hits', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Hits <?= getSortIcon('hits', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= getSortUrl('ua', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Trình duyệt (User Agent) <?= getSortIcon('ua', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?= getSortUrl('time', $sortBy, $sortOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                Truy cập lúc <?= getSortIcon('time', $sortBy, $sortOrder) ?>
                                            </a>
                                        </th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($onlineUsers)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-secondary">
                                                <i class="fas fa-globe-americas fa-3x mb-3 opacity-25"></i>
                                                <p>Hiện không có người dùng nào online</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($onlineUsers as $user): ?>
                                        <tr>
                                            <td class="font-monospace text-primary"><?= e($user['ip_address']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary rounded-pill"><?= number_format($user['hits']) ?></span>
                                            </td>
                                            <td>
                                                <small class="text-secondary text-truncate d-block" style="max-width: 300px;" title="<?= e($user['user_agent']) ?>">
                                                    <?= e($user['user_agent']) ?>
                                                </small>
                                            </td>
                                            <td><?= date('H:i:s d/m', $user['last_activity']) ?></td>
                                            <td>
                                                <span class="badge bg-success rounded-pill">Active</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                 </div>
            </div>

            <!-- History Stats -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-history text-secondary me-2"></i> Lịch sử 7 ngày qua
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush border-0">
                            <?php foreach ($historyStats as $stat): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3 border-bottom-0">
                                <div>
                                    <span class="fw-bold d-block"><?= date('d/m/Y', strtotime($stat['date'])) ?></span>
                                    <small class="text-secondary"><?= date('l', strtotime($stat['date'])) ?></small>
                                </div>
                                <span class="badge bg-primary rounded-pill" style="font-size: 14px;">
                                    <?= number_format($stat['access_count']) ?> <i class="fas fa-eye ms-1 opacity-50"></i>
                                </span>
                            </li>
                            <?php endforeach; ?>
                            
                            <?php if (empty($historyStats)): ?>
                            <li class="list-group-item text-center py-4 text-secondary">
                                Chưa có dữ liệu thống kê
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- History Tab Content -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">Nhật ký truy cập chi tiết</span>
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="tab" value="history">
                    <input type="text" name="search_ip" class="form-control form-control-sm rounded-pill" placeholder="Tìm theo IP..." value="<?= e($searchIp) ?>">
                    <button class="btn btn-sm btn-primary rounded-pill"><i class="fas fa-search"></i></button>
                    <?php if($searchIp): ?>
                        <a href="?tab=history" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-modern">

                        <thead>
                            <tr>
                                <th>
                                    <a href="<?= getSortUrl('created_at', $histSort, $histOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        Thời gian <?= getSortIcon('created_at', $histSort, $histOrder) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('ip_address', $histSort, $histOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        IP Address <?= getSortIcon('ip_address', $histSort, $histOrder) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('hits', $histSort, $histOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        Hits <?= getSortIcon('hits', $histSort, $histOrder) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('request_uri', $histSort, $histOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        Request URI <?= getSortIcon('request_uri', $histSort, $histOrder) ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('user_agent', $histSort, $histOrder) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                        User Agent <?= getSortIcon('user_agent', $histSort, $histOrder) ?>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs['data'])): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-secondary">
                                        <p>Chưa có dữ liệu nhật ký.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs['data'] as $log): ?>
                                <tr>
                                    <td><?= date('H:i:s d/m/Y', strtotime($log['created_at'])) ?></td>
                                    <td class="font-monospace text-primary"><?= e($log['ip_address']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary rounded-pill"><?= number_format($log['hits']) ?></span>
                                    </td>
                                    <td><small class="text-break"><?= e($log['request_uri']) ?></small></td>
                                    <td>
                                        <small class="text-secondary text-truncate d-block" style="max-width: 400px;" title="<?= e($log['user_agent']) ?>">
                                            <?= e($log['user_agent']) ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($logs['total_pages'] > 1): ?>
            <div class="card-footer bg-white d-flex justify-content-center py-3">
                <nav>
                    <ul class="pagination mb-0">
                        <?php for ($i = 1; $i <= $logs['total_pages']; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?tab=history&page=<?= $i ?><?= $searchIp ? "&search_ip=$searchIp" : '' ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        @keyframes ping {
            75%, 100% {
                transform: scale(2);
                opacity: 0;
            }
        }
        .animate-ping {
            animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        .h-3 { height: 0.75rem; }
        .w-3 { width: 0.75rem; }
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
</body>
</html>
