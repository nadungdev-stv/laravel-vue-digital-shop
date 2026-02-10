<?php
// Admin Header - Apple Style
?>
<header class="admin-header">
    <div class="d-flex align-items-center flex-grow-1">
        <button id="mobileSidebarToggle" class="btn btn-link text-dark d-md-none me-3 p-0" style="font-size: 20px;">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Page Title (Desktop) -->
        <div class="d-none d-md-block me-4">
             <?php if (isset($pageTitle)): ?>
                <h1 class="page-title mb-0" style="font-size: 20px;"><?= $pageTitle ?></h1>
                <?php if (isset($pageDescription)): ?>
                    <p class="text-secondary mb-0" style="font-size: 13px;"><?= $pageDescription ?></p>
                <?php endif; ?>
             <?php endif; ?>
        </div>
        
        <!-- Search (Centered) -->
        <div class="header-search w-100 mx-auto" style="max-width: 400px;">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Tìm kiếm đơn hàng, sản phẩm..." id="globalSearch">
        </div>
    </div>

    <div class="header-actions">
        <a href="/" target="_blank" class="header-btn" title="Xem website">
            <i class="fas fa-external-link-alt"></i>
        </a>
        
        <button class="header-btn position-relative" title="Thông báo">
            <i class="fas fa-bell"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                <span class="visually-hidden">New alerts</span>
            </span>
        </button>

        <div class="dropdown">
            <button class="header-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="https://ui-avatars.com/api/?name=Admin&background=random" alt="Admin" style="width: 32px; height: 32px; border-radius: 50%;">
            </button>
            <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu">
                <li><span class="dropdown-header">Xin chào, Admin!</span></li>
                <li><a class="dropdown-item user-dropdown-item" href="/admin/settings.php"><i class="fas fa-user-cog"></i> Cài đặt tài khoản</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><span class="dropdown-header">Tùy chọn cài đặt chỉ áp dụng cho trình duyệt này</span></li>
                <li>
                    <button class="dropdown-item user-dropdown-item d-flex justify-content-between align-items-center" type="button" data-theme-value="auto">
                        <span><i class="fas fa-adjust me-2"></i> Dùng giao diện thiết bị</span>
                        <i class="fas fa-check theme-check d-none"></i>
                    </button>
                </li>
                <li>
                    <button class="dropdown-item user-dropdown-item d-flex justify-content-between align-items-center" type="button" data-theme-value="dark">
                        <span><i class="fas fa-moon me-2"></i> Giao diện tối</span>
                        <i class="fas fa-check theme-check d-none"></i>
                    </button>
                </li>
                <li>
                    <button class="dropdown-item user-dropdown-item d-flex justify-content-between align-items-center" type="button" data-theme-value="light">
                        <span><i class="fas fa-sun me-2"></i> Giao diện sáng</span>
                        <i class="fas fa-check theme-check d-none"></i>
                    </button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item user-dropdown-item text-danger" href="/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>
</header>
<script>
    // Simple global search placeholder logic
    document.getElementById('globalSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            window.location.href = '/admin/orders?search=' + this.value;
        }
    });
</script>
