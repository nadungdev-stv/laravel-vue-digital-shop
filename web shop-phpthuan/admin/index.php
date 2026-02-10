<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();
if (!isAdmin()) redirect('/');

// Get Stats (Reused logic)
$stats = [
    'total_users' => db()->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'total_orders' => db()->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'],
    'total_revenue' => db()->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE payment_status = 'paid'")->fetch()['total'],
    'today_revenue' => db()->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'")->fetch()['total'],
    'online_users' => db()->query("SELECT COUNT(*) as count FROM online_users")->fetch()['count'], // Realtime table
    'today_views' => db()->query("SELECT access_count FROM visitor_stats WHERE date = CURDATE()")->fetch()['access_count'] ?? 0,
];

// Recent Orders
$recentOrders = db()->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Veyrix Admin 2.0</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- NEW Apple Style CSS -->
    <link rel="stylesheet" href="/public/css/admin-apple-style.css">
</head>
<body>

    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
        
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>

                <p class="text-secondary mb-0">Chào mừng trở lại, Admin!</p>
            </div>
            <div class="d-flex gap-2">
                <button class="pill-button pill-button-white">
                    <i class="fas fa-file-export"></i>
                    <span class="d-none d-sm-inline">Xuất báo cáo</span>
                    <span class="d-inline d-sm-none">Report</span>
                </button>
                <a href="/admin/product-add.php" class="pill-button pill-button-gray">
                    <i class="fas fa-plus"></i>
                    <span class="d-none d-sm-inline">Thêm sản phẩm</span>
                    <span class="d-inline d-sm-none">Thêm</span>
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="row g-3 mb-4">
            <!-- Revenue -->
            <div class="col-6 col-md-6 col-lg-3">
                <div class="stats-card-modern">
                    <i class="fas fa-wallet stats-icon-bg"></i>
                    <div>
                        <div class="stats-label">Doanh thu tổng</div>
                        <div class="stats-value"><?= formatMoney($stats['total_revenue']) ?></div>
                    </div>
                    <div class="stats-trend text-trend-up">
                        <i class="fas fa-arrow-up"></i> +12.5% <span class="text-secondary ms-1 fw-normal d-none d-xl-inline">so với tháng trước</span>
                    </div>
                </div>
            </div>
            
            <!-- Today Revenue -->
            <div class="col-6 col-md-6 col-lg-3">
                <div class="stats-card-modern">
                    <i class="fas fa-calendar-day stats-icon-bg"></i>
                    <div>
                        <div class="stats-label">Hôm nay</div>
                        <div class="stats-value"><?= formatMoney($stats['today_revenue']) ?></div>
                    </div>
                    <div class="stats-trend text-trend-up">
                        <i class="fas fa-bolt"></i> Realtime
                    </div>
                </div>
            </div>

            <!-- Orders -->
            <div class="col-6 col-md-6 col-lg-3">
                <div class="stats-card-modern">
                    <i class="fas fa-shopping-bag stats-icon-bg"></i>
                    <div>
                        <div class="stats-label">Đơn hàng</div>
                        <div class="stats-value"><?= number_format($stats['total_orders']) ?></div>
                    </div>
                    <div class="stats-trend text-secondary">
                        <i class="fas fa-minus"></i> Ổn định
                    </div>
                </div>
            </div>

            <!-- Online Users -->
            <div class="col-6 col-md-6 col-lg-3">
                <div class="stats-card-modern">
                    <i class="fas fa-users stats-icon-bg"></i>
                    <div>
                        <div class="stats-label">Online</div>
                        <div class="stats-value text-primary"><?= $stats['online_users'] ?></div>
                    </div>
                     <div class="stats-trend text-trend-up">
                        <i class="fas fa-users"></i> <?= $stats['today_views'] ?> views
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Orders Table -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Đơn hàng gần đây</span>
                        <a href="/admin/orders" class="text-decoration-none text-primary" style="font-size: 14px;">Xem tất cả</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Số tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $order['id'] ?></td>
                                        <td><?= e($order['user_name'] ?? 'Khách lẻ') ?></td>
                                        <td><?= formatMoney($order['final_amount']) ?></td>
                                        <td>
                                            <?php
                                                $statusClass = 'bg-secondary';
                                                if ($order['payment_status'] == 'paid') $statusClass = 'bg-success';
                                                elseif ($order['payment_status'] == 'pending') $statusClass = 'bg-warning';
                                                elseif ($order['payment_status'] == 'cancelled') $statusClass = 'bg-danger';
                                            ?>
                                            <span class="badge <?= $statusClass ?> rounded-pill"><?= ucfirst($order['payment_status']) ?></span>
                                        </td>
                                        <td class="text-secondary"><?= date('H:i d/m', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions / Mini Stats -->
            <div class="col-lg-4">
                 <div class="card">
                     <div class="card-header">Truy cập nhanh</div>
                     <div class="card-body">
                         <div class="d-grid gap-3">
                             <a href="/admin/settings.php" class="pill-button pill-button-white justify-content-center">
                                 <i class="fas fa-cog text-secondary"></i> Cấu hình hệ thống
                             </a>
                             <a href="/admin/users.php" class="pill-button pill-button-white justify-content-center">
                                 <i class="fas fa-users text-secondary"></i> Quản lý thành viên
                             </a> 
                             <a href="/admin/layout.php" class="pill-button pill-button-white justify-content-center">
                                 <i class="fas fa-images text-secondary"></i> Banner & Layout
                             </a>
                         </div>
                     </div>
                 </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
