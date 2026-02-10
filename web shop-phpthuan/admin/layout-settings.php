<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

initSession();
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Chỉnh sửa Layout Trang chủ';

// Lấy danh sách categories
$categories = db()->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order")->fetchAll();

// Lấy main banners (carousel)
$mainBanners = db()->query("SELECT * FROM banners WHERE status = 'active' ORDER BY sort_order ASC")->fetchAll();

// Lấy promo banners
$promoResult = null;
try {
    $promoResult = db()->query("SELECT setting_value FROM settings WHERE setting_key = 'promo_banners'")->fetch();
} catch (Exception $e) {
    // Bảng chưa tồn tại
}
$promoBanners = $promoResult ? json_decode($promoResult['setting_value'], true) : [
    ['image' => '/public/images/banners/vpn-banner.svg', 'link' => '/products?category=vpn-bao-mat-mang'],
    ['image' => '/public/images/banners/esim-banner.svg', 'link' => '/products?category=the-gioi-ai']
];

// Lấy bottom banners
$bottomResult = null;
try {
    $bottomResult = db()->query("SELECT setting_value FROM settings WHERE setting_key = 'bottom_banners'")->fetch();
} catch (Exception $e) {
    // Bảng chưa tồn tại
}
$bottomBanners = $bottomResult ? json_decode($bottomResult['setting_value'], true) : [
    ['image' => '/public/images/banners/steam-banner.svg', 'link' => '/products?category=game-steam'],
    ['image' => '/public/images/banners/vpn-banner.svg', 'link' => '/products?category=edit-anh-video'],
    ['image' => '/public/images/banners/steam-banner.svg', 'link' => '/products?category=game-steam'],
    ['image' => '/public/images/banners/esim-banner.svg', 'link' => '/products?category=window-office']
];

// Debug: Hiển thị số lượng dữ liệu
if (isset($_GET['debug'])) {
    echo "<!-- DEBUG INFO -->";
    echo "<!-- Categories: " . count($categories) . " -->";
    echo "<!-- Main Banners: " . count($mainBanners) . " -->";
    echo "<!-- Promo Banners: " . count($promoBanners) . " -->";
    echo "<!-- Bottom Banners: " . count($bottomBanners) . " -->";
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/admin-apple-style.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        .drag-handle {
            cursor: grab;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .sortable-ghost {
            opacity: 0.4;
            background: #f0f0f0;
        }

        .banner-preview {
            max-width: 200px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }

        .nav-pills .nav-link {
            color: #667eea;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #7387df 100%);
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-white p-3" style="min-height: 100vh;">
                <h4 class="mb-4"><i class="fas fa-cog"></i> Admin</h4>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="/admin/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white active" href="/admin/layout-settings.php">
                            <i class="fas fa-palette"></i> Layout Trang chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="/admin/products">
                            <i class="fas fa-box"></i> Sản phẩm
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="/">
                            <i class="fas fa-arrow-left"></i> Về trang chủ
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center my-4">
                    <h2><i class="fas fa-palette"></i> <?= $pageTitle ?></h2>
                </div>

                <?php if (hasFlash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= getFlash('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (hasFlash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= getFlash('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabs Navigation -->
                <ul class="nav nav-pills mb-4" id="layoutTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="categories-tab" data-bs-toggle="pill"
                            data-bs-target="#categories" type="button">
                            <i class="fas fa-th-large"></i> Thể loại (Sidebar)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="main-banners-tab" data-bs-toggle="pill"
                            data-bs-target="#main-banners" type="button">
                            <i class="fas fa-images"></i> Banner Carousel
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="promo-banners-tab" data-bs-toggle="pill"
                            data-bs-target="#promo-banners" type="button">
                            <i class="fas fa-image"></i> Promo Banners (2 ảnh phải)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bottom-banners-tab" data-bs-toggle="pill"
                            data-bs-target="#bottom-banners" type="button">
                            <i class="fas fa-grip-horizontal"></i> Bottom Banners (4 ảnh)
                        </button>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content" id="layoutTabsContent">

                    <!-- Categories Tab -->
                    <div class="tab-pane fade show active" id="categories" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-th-large"></i> Danh sách Thể loại - Kéo thả để sắp xếp
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th width="50"><i class="fas fa-grip-vertical"></i></th>
                                                <th width="60">Icon</th>
                                                <th>Tên</th>
                                                <th>Slug</th>
                                                <th>Trạng thái</th>
                                                <th width="100">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sortable-categories">
                                            <?php foreach ($categories as $cat): ?>
                                                <tr data-id="<?= $cat['id'] ?>">
                                                    <td class="drag-handle text-center">
                                                        <i class="fas fa-grip-vertical text-muted"></i>
                                                    </td>
                                                    <td>
                                                        <img src="<?= e($cat['icon']) ?>" class="banner-preview"
                                                            style="max-width: 40px; max-height: 40px;"
                                                            onerror="this.outerHTML='<i class=\'fas fa-folder fa-2x\'></i>'">
                                                    </td>
                                                    <td><?= e($cat['name']) ?></td>
                                                    <td><code><?= e($cat['slug']) ?></code></td>
                                                    <td>
                                                        <span
                                                            class="badge bg-<?= $cat['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                            <?= $cat['status'] === 'active' ? 'Hiện' : 'Ẩn' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="/admin/category-edit?id=<?= $cat['id'] ?>"
                                                            class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Banners Tab -->
                    <div class="tab-pane fade" id="main-banners" role="tabpanel">
                        <div class="card">
                            <div
                                class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-images"></i> Banner Carousel - Kéo thả để sắp xếp</h5>
                                <a href="/admin/banner-edit.php" class="btn btn-light btn-sm">
                                    <i class="fas fa-plus"></i> Thêm banner
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th width="50"><i class="fas fa-grip-vertical"></i></th>
                                                <th width="200">Ảnh</th>
                                                <th>Tiêu đề</th>
                                                <th>Link</th>
                                                <th>Trạng thái</th>
                                                <th width="100">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sortable-banners">
                                            <?php foreach ($mainBanners as $banner): ?>
                                                <tr data-id="<?= $banner['id'] ?>">
                                                    <td class="drag-handle text-center">
                                                        <i class="fas fa-grip-vertical text-muted"></i>
                                                    </td>
                                                    <td>
                                                        <img src="<?= e($banner['image']) ?>" class="banner-preview"
                                                            alt="<?= e($banner['title']) ?>">
                                                    </td>
                                                    <td><?= e($banner['title']) ?></td>
                                                    <td><small><?= e($banner['link'] ?: 'N/A') ?></small></td>
                                                    <td>
                                                        <span
                                                            class="badge bg-<?= $banner['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                            <?= $banner['status'] === 'active' ? 'Hiện' : 'Ẩn' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="/admin/banner-edit.php?id=<?= $banner['id'] ?>"
                                                            class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Promo Banners Tab -->
                    <div class="tab-pane fade" id="promo-banners" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-image"></i> Promo Banners - 2 ảnh bên phải</h5>
                            </div>
                            <div class="card-body">
                                <form id="promo-banners-form">
                                    <div class="row">
                                        <?php foreach ($promoBanners as $index => $promo): ?>
                                            <div class="col-md-6">
                                                <div class="card mb-3">
                                                    <div class="card-header">
                                                        <strong>Promo Banner <?= $index + 1 ?></strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="mb-3 text-center">
                                                            <img src="<?= e($promo['image']) ?>"
                                                                id="promo-preview-<?= $index ?>" class="img-thumbnail"
                                                                style="max-height: 200px;" alt="Promo <?= $index + 1 ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Đường dẫn ảnh</label>
                                                            <input type="text" class="form-control"
                                                                name="promo[<?= $index ?>][image]"
                                                                value="<?= e($promo['image']) ?>"
                                                                onchange="document.getElementById('promo-preview-<?= $index ?>').src = this.value">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Link (khi click vào)</label>
                                                            <input type="text" class="form-control"
                                                                name="promo[<?= $index ?>][link]"
                                                                value="<?= e($promo['link']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Lưu Promo Banners
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Banners Tab -->
                    <div class="tab-pane fade" id="bottom-banners" role="tabpanel">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-grip-horizontal"></i> Bottom Banners - 4 ảnh ngang
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="bottom-banners-form">
                                    <div class="row">
                                        <?php foreach ($bottomBanners as $index => $bottom): ?>
                                            <div class="col-md-6 col-lg-3">
                                                <div class="card mb-3">
                                                    <div class="card-header">
                                                        <strong>Banner <?= $index + 1 ?></strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="mb-3 text-center">
                                                            <img src="<?= e($bottom['image']) ?>"
                                                                id="bottom-preview-<?= $index ?>" class="img-thumbnail"
                                                                style="max-height: 150px;" alt="Bottom <?= $index + 1 ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Đường dẫn ảnh</label>
                                                            <input type="text" class="form-control form-control-sm"
                                                                name="bottom[<?= $index ?>][image]"
                                                                value="<?= e($bottom['image']) ?>"
                                                                onchange="document.getElementById('bottom-preview-<?= $index ?>').src = this.value">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Link</label>
                                                            <input type="text" class="form-control form-control-sm"
                                                                name="bottom[<?= $index ?>][link]"
                                                                value="<?= e($bottom['link']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Lưu Bottom Banners
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Drag and Drop for Categories
        const categoriesTbody = document.getElementById('sortable-categories');
        if (categoriesTbody) {
            new Sortable(categoriesTbody, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function (evt) {
                    const categoryIds = [];
                    const rows = categoriesTbody.querySelectorAll('tr[data-id]');
                    rows.forEach((row, index) => {
                        categoryIds.push({
                            id: row.getAttribute('data-id'),
                            sort_order: index
                        });
                    });
                    updateCategoryOrder(categoryIds);
                }
            });
        }

        // Drag and Drop for Main Banners
        const bannersTbody = document.getElementById('sortable-banners');
        if (bannersTbody) {
            new Sortable(bannersTbody, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function (evt) {
                    const bannerIds = [];
                    const rows = bannersTbody.querySelectorAll('tr[data-id]');
                    rows.forEach((row, index) => {
                        bannerIds.push({
                            id: row.getAttribute('data-id'),
                            sort_order: index
                        });
                    });
                    updateBannerOrder(bannerIds);
                }
            });
        }

        // Update Category Order
        function updateCategoryOrder(categories) {
            fetch('/admin/api/update-category-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ categories })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Đã cập nhật thứ tự danh mục', 'success');
                    } else {
                        showToast('Lỗi: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    showToast('Có lỗi xảy ra', 'danger');
                });
        }

        // Update Banner Order
        function updateBannerOrder(banners) {
            fetch('/admin/api/update-banner-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ banners })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Đã cập nhật thứ tự banner', 'success');
                    } else {
                        showToast('Lỗi: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    showToast('Có lỗi xảy ra', 'danger');
                });
        }

        // Save Promo Banners
        document.getElementById('promo-banners-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const promo = [];

            for (let i = 0; i < 2; i++) {
                promo.push({
                    image: formData.get(`promo[${i}][image]`),
                    link: formData.get(`promo[${i}][link]`)
                });
            }

            fetch('/admin/api/update-promo-banners.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ promo })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Đã lưu Promo Banners', 'success');
                    } else {
                        showToast('Lỗi: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    showToast('Có lỗi xảy ra', 'danger');
                });
        });

        // Save Bottom Banners
        document.getElementById('bottom-banners-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const bottom = [];

            for (let i = 0; i < 4; i++) {
                bottom.push({
                    image: formData.get(`bottom[${i}][image]`),
                    link: formData.get(`bottom[${i}][link]`)
                });
            }

            fetch('/admin/api/update-bottom-banners.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ bottom })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Đã lưu Bottom Banners', 'success');
                    } else {
                        showToast('Lỗi: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    showToast('Có lỗi xảy ra', 'danger');
                });
        });

        // Toast Notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</body>

</html>