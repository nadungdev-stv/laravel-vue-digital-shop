<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentUser = getCurrentUser();

// Count pending items
$pendingOrders = db()->query("SELECT COUNT(*) as count FROM orders WHERE payment_status = 'pending'")->fetch()['count'];
$lowStockProducts = db()->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity > 0 AND stock_quantity < 5")->fetch()['count'];
$pendingReviews = db()->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'")->fetch()['count'];
$unreadChats = db()->query("SELECT COUNT(DISTINCT session_id) as count FROM chat_messages WHERE sender_type = 'customer' AND is_read = 0")->fetch()['count'];
?>

<div class="admin-sidebar">
    <div class="sidebar-header">
        <a href="/admin/" class="sidebar-brand">
            <i class="fas fa-layer-group"></i> <span>Veyrix Admin</span>
        </a>
    </div>

    <div class="sidebar-content" style="height: calc(100vh - 120px); overflow-y: auto;">

        <div class="nav-section-title"><span>Tổng quan</span></div>
        <div class="nav-item">
            <a class="nav-link <?= $currentPage == 'index.php' ? 'active' : '' ?>" href="/admin/">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'visitors.php') !== false ? 'active' : '' ?>"
                href="/admin/visitors.php">
                <i class="fas fa-globe"></i> <span>Truy cập</span>
            </a>
        </div>

        <div class="nav-section-title"><span>Quản lý bán hàng</span></div>
        <div class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['orders.php', 'order-detail.php']) ? 'active' : '' ?>"
                href="/admin/orders">
                <i class="fas fa-shopping-bag"></i> <span>Đơn hàng</span>
                <?php if ($pendingOrders > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto"><?= $pendingOrders ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['products.php', 'product-add.php', 'product-edit']) ? 'active' : '' ?>"
                href="/admin/products">
                <i class="fas fa-box-open"></i> <span>Sản phẩm</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['coupons.php']) ? 'active' : '' ?>"
                href="/admin/coupons.php">
                <i class="fas fa-ticket-alt"></i> <span>Mã giảm giá</span>
            </a>
        </div>

        <div class="nav-section-title"><span>Khách hàng & CSKH</span></div>
        <div class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['users.php', 'user-detail']) ? 'active' : '' ?>"
                href="/admin/users.php">
                <i class="fas fa-users"></i> <span>Người dùng</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['chats.php', 'chat-detail']) ? 'active' : '' ?>"
                href="/admin/chats.php">
                <i class="fas fa-comments"></i> <span>Chat Support</span>
                <?php if ($unreadChats > 0): ?>
                    <span class="badge bg-primary rounded-pill ms-auto"><?= $unreadChats ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= strpos($currentPage, 'reviews.php') !== false ? 'active' : '' ?>"
                href="/admin/reviews.php">
                <i class="fas fa-star"></i> <span>Đánh giá</span>
                <?php if ($pendingReviews > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill ms-auto"><?= $pendingReviews ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="nav-section-title"><span>Hệ thống</span></div>
        <div class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['accounts.php']) ? 'active' : '' ?>"
                href="/admin/accounts.php">
                <i class="fas fa-key"></i> <span>Kho tài khoản</span>
                <?php if ($lowStockProducts > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill ms-auto">!</span>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['layout.php']) ? 'active' : '' ?>" href="/admin/layout.php">
                <i class="fas fa-paint-brush"></i> <span>Layout</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['settings.php']) ? 'active' : '' ?>"
                href="/admin/settings.php">
                <i class="fas fa-cog"></i> <span>Cấu hình</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= in_array($currentPage, ['qr-generator.php']) ? 'active' : '' ?>"
                href="/admin/qr-generator.php">
                <i class="fas fa-qrcode"></i> <span>Tạo mã QR</span>
            </a>
        </div>

        <div style="height: 50px;"></div>
    </div>

    <!-- Sidebar Footer with Toggle -->
    <div class="sidebar-footer border-top p-3 d-none d-md-flex align-items-center justify-content-center"
        style="position: absolute; bottom: 0; width: 100%; background: white;">
        <button id="sidebarToggle" class="btn btn-light rounded-circle shadow-sm text-secondary"
            style="width: 40px; height: 40px;">
            <i class="fas fa-chevron-left transition-transform"></i>
        </button>
    </div>
</div>

<!-- Mobile Overlay -->
<div id="sidebarOverlay"
    style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; transition: opacity 0.3s;">
</div>

<script src="/public/js/admin2.js"></script>
<style>
    .admin-sidebar.collapsed .sidebar-brand span,
    .admin-sidebar.collapsed .nav-item span,
    .admin-sidebar.collapsed .nav-section-title span,
    .admin-sidebar.collapsed .badge {
        display: none;
    }

    .admin-sidebar.collapsed .sidebar-header {
        justify-content: center;
        padding: 0;
    }

    .admin-sidebar.collapsed .nav-item .nav-link {
        justify-content: center;
        padding: 10px;
        margin-right: 0;
        border-radius: 0;
    }

    .admin-sidebar.collapsed .nav-item .nav-link i {
        margin-right: 0;
        font-size: 20px;
    }

    .admin-sidebar.collapsed .sidebar-footer #sidebarToggle i {
        transform: rotate(180deg);
    }

    /* Mobile Overlay Active State */
    #sidebarOverlay.show {
        display: block !important;
    }

    .ms-auto {
        margin-left: auto !important;
    }
</style>