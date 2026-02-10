<?php
$pageTitle = 'Tài khoản của tôi';
require_once __DIR__ . '/includes/header.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    setFlash('error', 'Vui lòng đăng nhập');
    redirect('/login.php?redirect=/account.php');
}

$user = getCurrentUser();
$success = '';
$error = '';

// Xác định tab active từ URL
$activeTab = $_GET['tab'] ?? 'profile';

// PHP processing đã được thay thế bằng AJAX - xem api/update-profile.php và api/change-password.php

// Thống kê
$stats = [
    'total_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$user['id']])->fetch()['count'],
    'total_spent' => db()->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE user_id = ? AND payment_status = 'paid'", [$user['id']])->fetch()['total'],
    'pending_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND order_status = 'pending'", [$user['id']])->fetch()['count'],
];

// Thông báo mới
try {
    $notifications = db()->query(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
        [$user['id']]
    )->fetchAll();
} catch (Exception $e) {
    // Bảng notifications chưa được tạo
    $notifications = [];
}
?>

<div class="container order-detail-container">
    <h1 class="mb-4"><i class="fas fa-user-circle"></i> Tài khoản của tôi</h1>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= e($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                             style="width: 80px; height: 80px; font-size: 2rem;">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <h5 class="mb-1"><?= e($user['full_name'] ?? $user['username']) ?></h5>
                    <p class="text-muted small mb-0"><?= e($user['email']) ?></p>
                    <p class="text-muted small mb-2">
                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                            <?= e(ucfirst($user['role'])) ?>
                        </span>
                    </p>
                    <hr>
                    <p class="mb-3">
                        <i class="fas fa-wallet text-success"></i>
                        <strong>Số dư:</strong><br>
                        <span class="fs-5 text-success"><?= formatMoney($user['balance']) ?></span>
                    </p>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="/wallet?action=deposit" class="pill-button pill-button-sm pill-button-green w-100">
                                <i class="fas fa-plus"></i> Nạp tiền
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/wallet?action=withdraw" class="pill-button pill-button-sm pill-button-blue w-100">
                                <i class="fas fa-minus"></i> Rút tiền
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="list-group list-group-flush">
                    <a href="#" data-tab="profile" class="list-group-item list-group-item-action tab-link <?= $activeTab === 'profile' ? 'active' : '' ?>">
                        <i class="fas fa-user"></i> Thông tin cá nhân
                    </a>
                    <a href="#" data-tab="password" class="list-group-item list-group-item-action tab-link <?= $activeTab === 'password' ? 'active' : '' ?>">
                        <i class="fas fa-lock"></i> Đổi mật khẩu
                    </a>
                    <a href="#" data-tab="notifications" class="list-group-item list-group-item-action tab-link <?= $activeTab === 'notifications' ? 'active' : '' ?>">
                        <i class="fas fa-bell"></i> Thông báo
                        <?php
                        try {
                            $unreadCount = db()->query(
                                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
                                [$user['id']]
                            )->fetch()['count'];
                        } catch (Exception $e) {
                            $unreadCount = 0;
                        }
                        if ($unreadCount > 0):
                        ?>
                            <span class="badge bg-danger ms-auto"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/orders" class="list-group-item list-group-item-action">
                        <i class="fas fa-receipt"></i> Đơn hàng
                    </a>
                    <a href="/wallet" class="list-group-item list-group-item-action">
                        <i class="fas fa-wallet"></i> Ví tiền
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Thống kê -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                            <h3 class="mb-0"><?= $stats['total_orders'] ?></h3>
                            <p class="text-muted mb-0 small">Tổng đơn hàng</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                            <h3 class="mb-0"><?= formatMoney($stats['total_spent']) ?></h3>
                            <p class="text-muted mb-0 small">Tổng chi tiêu</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-star fa-2x text-warning mb-2"></i>
                            <h3 class="mb-0"><?= $user['reward_points'] ?></h3>
                            <p class="text-muted mb-0 small">Điểm tích lũy</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Thông tin cá nhân -->
                <div class="tab-pane fade <?= $activeTab === 'profile' ? 'show active' : '' ?>" id="profile">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-user"></i> Thông tin cá nhân</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="profileForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tên đăng nhập</label>
                                        <input type="text" class="form-control"
                                               value="<?= e($user['username']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" id="profile_email" class="form-control"
                                               value="<?= e($user['email']) ?>"
                                               data-original="<?= e($user['email']) ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Họ tên</label>
                                        <input type="text" name="full_name" id="profile_full_name" class="form-control"
                                               value="<?= e($user['full_name'] ?? '') ?>"
                                               data-original="<?= e($user['full_name'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="text" name="phone" id="profile_phone" class="form-control"
                                               value="<?= e($user['phone'] ?? '') ?>"
                                               data-original="<?= e($user['phone'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Ngày tham gia</label>
                                        <input type="text" class="form-control"
                                               value="<?= formatDate($user['created_at']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Trạng thái</label>
                                        <input type="text" class="form-control"
                                               value="<?= $user['status'] === 'active' ? 'Hoạt động' : 'Bị khóa' ?>" readonly>
                                    </div>
                                </div>

                                <button type="submit" id="updateProfileBtn" class="pill-button pill-button-blue" disabled>
                                    <i class="fas fa-save"></i> Cập nhật thông tin
                                </button>
                                <div id="profileMessage" class="mt-3"></div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Đổi mật khẩu -->
                <div class="tab-pane fade <?= $activeTab === 'password' ? 'show active' : '' ?>" id="password">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-lock"></i> Đổi mật khẩu</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="passwordForm">
                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                                    <input type="password" name="new_password" id="new_password" class="form-control"
                                           minlength="6" required>
                                    <small class="text-muted">Tối thiểu 6 ký tự</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                </div>

                                <button type="submit" class="pill-button pill-button-blue">
                                    <i class="fas fa-key"></i> Đổi mật khẩu
                                </button>
                                <div id="passwordMessage" class="mt-3"></div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Thông báo -->
                <div class="tab-pane fade <?= $activeTab === 'notifications' ? 'show active' : '' ?>" id="notifications">
                    <div class="card">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-bell"></i> Thông báo</h6>
                            <?php if ($unreadCount > 0): ?>
                                <button class="pill-button pill-button-sm pill-button-blue" onclick="markAllRead()">
                                    Đánh dấu đã đọc tất cả
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="list-group list-group-flush" id="notificationsList">
                            <?php if (empty($notifications)): ?>
                                <div class="list-group-item text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p class="mb-0">Chưa có thông báo nào</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <div class="list-group-item <?= $notif['is_read'] ? '' : 'bg-light' ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    <?php if (!$notif['is_read']): ?>
                                                        <span class="badge bg-danger me-2">Mới</span>
                                                    <?php endif; ?>
                                                    <?= e($notif['title']) ?>
                                                </h6>
                                                <p class="mb-1"><?= e($notif['content']) ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> <?= formatDate($notif['created_at']) ?>
                                                </small>
                                            </div>
                                            <?php if ($notif['link']): ?>
                                                <a href="<?= e($notif['link']) ?>" class="pill-button pill-button-sm pill-button-blue ms-2">
                                                    Xem
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($notifications) && count($notifications) >= 5): ?>
                        <div class="card-footer bg-light text-center">
                            <button id="loadMoreNotifications" class="pill-button pill-button-sm pill-button-gray" data-offset="5">
                                <i class="fas fa-angle-down"></i> Xem thêm
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Apple-inspired global styles for account page */
.order-detail-container {
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Arial, sans-serif;
}

/* Card styling */
.order-detail-container .card {
    border: 1px solid #d2d2d7;
    border-radius: 18px;
    box-shadow: none;
    background: #fbfbfd;
}

.order-detail-container .card-header {
    background: #f5f5f7 !important;
    border-bottom: 1px solid #d2d2d7;
    border-radius: 18px 18px 0 0 !important;
    color: #1d1d1f !important;
    padding: 16px 20px;
}

.order-detail-container .card-header h5,
.order-detail-container .card-header h6 {
    font-weight: 600;
    font-size: 17px;
    letter-spacing: -0.02em;
}

.order-detail-container .card-body {
    padding: 20px;
    background: #fbfbfd;
    border-radius: 0 0 18px 18px;
}

/* Remove hover effect for cards */
.order-detail-container .card {
    transition: none !important;
}

.order-detail-container .card:hover {
    transform: none !important;
    box-shadow: none !important;
}

/* Button styling */
.order-detail-container .btn-outline-primary {
    border-color: #0071e3;
    color: #0071e3;
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);
}

.order-detail-container .btn-outline-primary:hover {
    background: #0071e3;
    border-color: #0071e3;
    color: white;
}

.order-detail-container .btn-primary,
.order-detail-container .btn-success {
    background: #0071e3;
    border-color: #0071e3;
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);
}

.order-detail-container .btn-primary:hover,
.order-detail-container .btn-success:hover {
    background: #0077ed;
    border-color: #0077ed;
}

/* Badge styling */
.order-detail-container .badge {
    border-radius: 8px;
    padding: 6px 12px;
    font-weight: 500;
    letter-spacing: -0.01em;
}

.order-detail-container .badge.bg-warning {
    background: #ff9500 !important;
    color: white;
}

.order-detail-container .badge.bg-success {
    background: #34c759 !important;
}

.order-detail-container .badge.bg-info {
    background: #0071e3 !important;
}

.order-detail-container .badge.bg-danger {
    background: #ff3b30 !important;
}

.order-detail-container .badge.bg-secondary {
    background: #8e8e93 !important;
}

/* Alert styling */
.order-detail-container .alert {
    border-radius: 12px;
    border: 1px solid #d2d2d7;
    background: #f5f5f7;
}

.order-detail-container .alert-success {
    background: #e5ffe5;
    border-color: #34c759;
    color: #1d1d1f;
}

.order-detail-container .alert-danger {
    background: #ffe5e5;
    border-color: #ff3b30;
    color: #1d1d1f;
}

/* Text styling */
.order-detail-container strong {
    color: #1d1d1f;
    font-weight: 600;
}

.order-detail-container p {
    color: #1d1d1f;
}

.order-detail-container .text-muted {
    color: #86868b !important;
}

/* Form label styling */
.order-detail-container .form-label {
    color: #1d1d1f;
    font-weight: 500;
    font-size: 14px;
    margin-bottom: 8px;
}

/* Input styling */
.order-detail-container .form-control {
    border: 1px solid #d2d2d7;
    border-radius: 10px;
    padding: 10px 14px;
    transition: all 0.2s ease;
    background: white;
}

.order-detail-container .form-control:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
}

.order-detail-container .form-control[readonly] {
    background-color: #f5f5f7;
}

/* Tab styling */
.order-detail-container .nav-tabs {
    border-bottom: 1px solid #d2d2d7;
}

.order-detail-container .nav-tabs .nav-link {
    color: #1d1d1f;
    border: none;
    border-bottom: 2px solid transparent;
    font-weight: 500;
    padding: 12px 20px;
}

.order-detail-container .nav-tabs .nav-link:hover {
    border-color: transparent;
    border-bottom-color: #0071e3;
}

.order-detail-container .nav-tabs .nav-link.active {
    color: #0071e3;
    background: transparent;
    border-color: transparent;
    border-bottom-color: #0071e3;
}

/* List group styling */
.order-detail-container .list-group-item {
    border-color: #d2d2d7;
    background: white;
    transition: all 0.2s ease;
}

.order-detail-container .list-group-item-action:hover {
    background: #f5f5f7;
    color: #0071e3;
}

.order-detail-container .list-group-item-action.active {
    background: #e5f2ff;
    color: #0071e3;
    border-color: #d2d2d7;
}

/* Fix border-radius for list-group-flush in card */
.order-detail-container .card .list-group-flush {
    border-radius: 0 0 18px 18px;
    overflow: hidden;
}

.order-detail-container .list-group-flush .list-group-item:first-child {
    border-top-left-radius: 0;
    border-top-right-radius: 0;
    border-top: none;
}

.order-detail-container .list-group-flush .list-group-item:last-child {
    border-bottom-left-radius: 18px;
    border-bottom-right-radius: 18px;
}

/* List group in sidebar navigation card */
.order-detail-container .card {
    overflow: hidden;
}

/* Stats card in sidebar */
.order-detail-container .sidebar .card {
    background: linear-gradient(135deg, #667eea 0%, #7387df 100%);
    color: white;
    border: none;
}

.order-detail-container .sidebar .card strong,
.order-detail-container .sidebar .card p {
    color: white;
}

/* Wallet buttons row */
.order-detail-container .row.g-2 {
    margin-top: 0.5rem;
}

.order-detail-container .row.g-2 .pill-button {
    font-size: 13px;
    padding: 8px 12px;
}

/* Notification improvements */
.order-detail-container #notifications .list-group-item {
    padding: 16px 20px;
    transition: all 0.2s ease;
}

.order-detail-container #notifications .list-group-item:not(.text-center):hover {
    background: #f9fafb !important;
}

.order-detail-container #notifications .list-group-item.bg-light {
    background: #e5f2ff !important;
    border-left: 3px solid #0071e3;
}

.order-detail-container #notifications h6 {
    font-size: 15px;
    font-weight: 600;
    color: #1d1d1f;
    letter-spacing: -0.01em;
}

.order-detail-container #notifications p {
    font-size: 14px;
    color: #424245;
    line-height: 1.5;
}

.order-detail-container #notifications .badge.bg-danger {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 600;
}

.order-detail-container #notifications small {
    font-size: 12px;
}

.order-detail-container #notifications .d-flex {
    gap: 12px;
}

/* Badge in sidebar */
.order-detail-container .list-group-item .badge.ms-auto {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 10px;
}

/* Card footer styling */
.order-detail-container .card-footer {
    background: #f5f5f7 !important;
    border-top: 1px solid #d2d2d7;
    border-radius: 0 0 18px 18px !important;
    padding: 12px 20px;
}
</style>

<script>
(function() {
    'use strict';

    // Use event delegation on document to catch form submissions
    document.addEventListener('submit', function(e) {
        // Profile form
        if (e.target.id === 'profileForm') {
            e.preventDefault();
            e.stopPropagation();

            const updateProfileBtn = document.getElementById('updateProfileBtn');
            const formData = new FormData(e.target);
            const messageDiv = document.getElementById('profileMessage');

            // Disable button and show loading
            updateProfileBtn.disabled = true;
            updateProfileBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

            fetch('/api/update-profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';

                    // Update data-original attributes with new values
                    const profileInputs = e.target.querySelectorAll('input[data-original]');
                    profileInputs.forEach(input => {
                        input.setAttribute('data-original', input.value);
                    });

                    // Disable button again since no changes now
                    updateProfileBtn.disabled = true;
                    updateProfileBtn.innerHTML = '<i class="fas fa-save"></i> Cập nhật thông tin';

                    // Auto-hide success message after 3 seconds
                    setTimeout(() => {
                        messageDiv.innerHTML = '';
                    }, 3000);
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                    updateProfileBtn.disabled = false;
                    updateProfileBtn.innerHTML = '<i class="fas fa-save"></i> Cập nhật thông tin';
                }
            })
            .catch(error => {
                console.error('Profile update error:', error);
                messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra, vui lòng thử lại</div>';
                updateProfileBtn.disabled = false;
                updateProfileBtn.innerHTML = '<i class="fas fa-save"></i> Cập nhật thông tin';
            });
            return false;
        }

        // Password form
        if (e.target.id === 'passwordForm') {
            e.preventDefault();
            e.stopPropagation();

            const formData = new FormData(e.target);
            const messageDiv = document.getElementById('passwordMessage');
            const submitBtn = e.target.querySelector('button[type="submit"]');

            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

            fetch('/api/change-password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';

                    // Clear form
                    e.target.reset();

                    // Re-enable button after 1 second
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-key"></i> Đổi mật khẩu';
                    }, 1000);
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-key"></i> Đổi mật khẩu';
                }
            })
            .catch(error => {
                console.error('Password change error:', error);
                messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra, vui lòng thử lại</div>';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-key"></i> Đổi mật khẩu';
            });
            return false;
        }
    }, false);

    // Tab switching without page reload
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('tab-link') || e.target.closest('.tab-link')) {
            e.preventDefault();

            const link = e.target.classList.contains('tab-link') ? e.target : e.target.closest('.tab-link');
            const tabName = link.getAttribute('data-tab');

            // Update URL without reload
            history.pushState({tab: tabName}, '', '/account?tab=' + tabName);

            // Update active state on sidebar
            document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            // Show/hide tab panes
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });

            const targetPane = document.getElementById(tabName);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        }
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.tab) {
            const tabName = e.state.tab;

            // Update sidebar
            document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
            const activeLink = document.querySelector('.tab-link[data-tab="' + tabName + '"]');
            if (activeLink) {
                activeLink.classList.add('active');
            }

            // Show/hide tab panes
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });

            const targetPane = document.getElementById(tabName);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        }
    });

    // Profile form change detection
    document.addEventListener('DOMContentLoaded', function() {
        const profileForm = document.getElementById('profileForm');
        const updateProfileBtn = document.getElementById('updateProfileBtn');

        if (profileForm && updateProfileBtn) {
            const profileInputs = profileForm.querySelectorAll('input[data-original]');

            function checkProfileChanges() {
                let hasChanges = false;
                profileInputs.forEach(input => {
                    if (input.value !== input.getAttribute('data-original')) {
                        hasChanges = true;
                    }
                });
                updateProfileBtn.disabled = !hasChanges;
            }

            profileInputs.forEach(input => {
                input.addEventListener('input', checkProfileChanges);
            });
        }

        // Load more notifications
        const loadMoreBtn = document.getElementById('loadMoreNotifications');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                const offset = parseInt(this.getAttribute('data-offset'));
                const notificationsList = document.getElementById('notificationsList');

                // Show loading
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải...';

                fetch('/api/load-notifications.php?offset=' + offset)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Append new notifications
                        notificationsList.insertAdjacentHTML('beforeend', data.html);

                        // Update offset
                        this.setAttribute('data-offset', data.nextOffset);

                        // Hide button if no more notifications
                        if (!data.hasMore) {
                            this.style.display = 'none';
                        } else {
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-angle-down"></i> Xem thêm';
                        }
                    } else {
                        this.innerHTML = '<i class="fas fa-exclamation-circle"></i> Lỗi tải dữ liệu';
                    }
                })
                .catch(error => {
                    console.error('Load notifications error:', error);
                    this.innerHTML = '<i class="fas fa-exclamation-circle"></i> Lỗi tải dữ liệu';
                });
            });
        }
    });

    // Mark all notifications as read
    window.markAllRead = function() {
        if (!confirm('Đánh dấu tất cả thông báo là đã đọc?')) {
            return;
        }

        fetch('/api/mark-notifications-read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Có lỗi xảy ra, vui lòng thử lại');
            }
        })
        .catch(error => {
            console.error('Mark all read error:', error);
            alert('Có lỗi xảy ra, vui lòng thử lại');
        });
    };
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
