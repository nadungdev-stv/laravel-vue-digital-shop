<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

initSession();
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Quản lý Layout Trang chủ';

// Xử lý xóa category
if (isset($_GET['delete_category'])) {
    $categoryId = (int) $_GET['delete_category'];
    $productCount = db()->query("SELECT COUNT(*) as count FROM products WHERE category_id = ?", [$categoryId])->fetch()['count'];

    if ($productCount > 0) {
        setFlash('error', "Không thể xóa! Danh mục này có {$productCount} sản phẩm");
    } else {
        db()->query("DELETE FROM categories WHERE id = ?", [$categoryId]);
        setFlash('success', 'Đã xóa danh mục');
    }
    redirect('/admin/layout.php');
}

// Lấy danh sách categories
$categories = db()->query("SELECT c.*,
    (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
    FROM categories c ORDER BY c.sort_order")->fetchAll();

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
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        .layout-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #d2d2d7;
            /* Viền nhạt giống Apple */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .layout-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(249, 250, 251, 0.8);
            backdrop-filter: blur(10px);
        }

        .layout-card-body {
            padding: 0;
            flex: 1;
            overflow-y: auto;
            max-height: 500px;
        }

        .list-group-item {
            border: none;
            border-bottom: 1px solid #f2f2f5;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: background 0.15s;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .list-group-item:hover {
            background-color: #f5f5f7;
        }

        .sort-handle {
            cursor: grab;
            color: #ccc;
            margin-right: 12px;
        }

        .sort-handle:active {
            cursor: grabbing;
            color: #0071e3;
        }

        .promo-img-wrapper {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e5e7;
            transition: transform 0.2s;
        }

        .promo-img-wrapper:hover {
            transform: scale(1.02);
        }

        .promo-img-wrapper img {
            width: 100%;
            height: auto;
            display: block;
        }

        .promo-edit-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            color: #1d1d1f;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .promo-edit-btn:hover {
            background: #fff;
            color: #0071e3;
            transform: scale(1.1);
        }

        .banner-thumb {
            width: 120px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .icon-only-button {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: #f5f5f7;
            color: #1d1d1f;
            transition: all 0.2s;
        }

        .icon-only-button:hover {
            background: #e5e5e7;
        }
    </style>
</head>

<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>

                <p class="text-secondary mb-0">Quản lý banner, danh mục và bố cục</p>
            </div>
            <a href="/" target="_blank" class="pill-button pill-button-white text-secondary border">
                <i class="fas fa-external-link-alt me-2"></i>Xem trang chủ
            </a>
        </div>

        <div class="row g-4">
            <!-- Cột trái: Danh mục -->
            <div class="col-lg-3 col-md-4">
                <div class="layout-card">
                    <div class="layout-card-header">
                        <div class="fw-bold"><i class="fas fa-list-ul me-2 text-primary"></i>Danh mục Menu</div>
                        <a href="/admin/category-add.php" class="btn btn-sm btn-light rounded-circle shadow-sm"
                            style="width: 32px; height: 32px; padding: 0; line-height: 32px;">
                            <i class="fas fa-plus text-primary"></i>
                        </a>
                    </div>
                    <div class="layout-card-body">
                        <div class="list-group list-group-flush" id="sortable-categories">
                            <?php foreach ($categories as $cat): ?>
                                <div class="list-group-item" data-id="<?= $cat['id'] ?>">
                                    <i class="fas fa-grip-vertical sort-handle"></i>
                                    <div class="d-flex align-items-center flex-grow-1 overflow-hidden">
                                        <?php if ($cat['icon']): ?>
                                            <?php if (strpos($cat['icon'], 'fa-') === 0 || strpos($cat['icon'], 'fas ') === 0): ?>
                                                <i class="<?= e($cat['icon']) ?> me-3 text-secondary"
                                                    style="width: 20px; text-align: center;"></i>
                                            <?php else: ?>
                                                <img src="<?= e($cat['icon']) ?>" class="me-3"
                                                    style="width: 20px; height: 20px; object-fit: contain;">
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <i class="fas fa-folder me-3 text-secondary"></i>
                                        <?php endif; ?>
                                        <span class="text-truncate"><?= e($cat['name']) ?></span>
                                    </div>
                                    <a href="/admin/category-edit.php?id=<?= $cat['id'] ?>"
                                        class="text-secondary ms-2 small">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột giữa: Banner Carousel -->
            <div class="col-lg-6 col-md-4">
                <div class="layout-card">
                    <div class="layout-card-header">
                        <div class="fw-bold"><i class="fas fa-images me-2 text-info"></i>Banner Chính (Carousel)</div>
                        <a href="/admin/banner-edit.php" class="btn btn-sm btn-light rounded-circle shadow-sm"
                            style="width: 32px; height: 32px; padding: 0; line-height: 32px;">
                            <i class="fas fa-plus text-primary"></i>
                        </a>
                    </div>
                    <div class="layout-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th>Hình ảnh</th>
                                        <th>Tiêu đề</th>
                                        <th class="text-end">Sửa</th>
                                    </tr>
                                </thead>
                                <tbody id="sortable-banners">
                                    <?php foreach ($mainBanners as $banner): ?>
                                        <tr data-id="<?= $banner['id'] ?>">
                                            <td class="text-center"><i class="fas fa-grip-vertical sort-handle m-0"></i>
                                            </td>
                                            <td>
                                                <img src="<?= e($banner['image']) ?>" class="banner-thumb">
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark text-truncate" style="max-width: 200px;">
                                                    <?= e($banner['title']) ?></div>
                                                <small
                                                    class="text-secondary"><?= $banner['status'] === 'active' ? '<span class="text-success">Hiện</span>' : '<span class="text-muted">Ẩn</span>' ?></small>
                                            </td>
                                            <td class="text-end">
                                                <button class="icon-only-button"
                                                    onclick="openBannerModal(<?= $banner['id'] ?>)">
                                                    <i class="fas fa-pen small"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Promo Banners -->
            <div class="col-lg-3 col-md-4">
                <div class="layout-card">
                    <div class="layout-card-header">
                        <div class="fw-bold"><i class="fas fa-ad me-2 text-warning"></i>Promo Banner</div>
                        <span class="badge bg-light text-secondary">2 slots</span>
                    </div>
                    <div class="layout-card-body p-3">
                        <?php foreach ($promoBanners as $index => $promo): ?>
                            <div class="promo-img-wrapper mb-3">
                                <img src="<?= e($promo['image']) ?>" alt="Promo <?= $index + 1 ?>">
                                <button class="promo-edit-btn"
                                    onclick="openPromoModal(<?= $index ?>, '<?= e(addslashes($promo['image'])) ?>', '<?= e(addslashes($promo['link'])) ?>')">
                                    <i class="fas fa-pen small"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                        <div class="alert alert-light border small text-secondary mb-0">
                            <i class="fas fa-info-circle me-1"></i>Kích thước chuẩn: <strong>345x161px</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng quản lý chi tiết Categories -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">Quản lý chi tiết danh mục</h6>
                        <a href="/admin/category-add.php" class="pill-button pill-button-secondary">
                            Quản lý nâng cao <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table-modern w-100">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th style="width: 60px;">Icon</th>
                                    <th>Tên Category</th>
                                    <th>Slug</th>
                                    <th class="text-center">Sản phẩm</th>
                                    <th class="text-center">Trạng thái</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><span class="text-secondary small">#<?= $cat['id'] ?></span></td>
                                        <td class="text-center">
                                            <?php if ($cat['icon']): ?>
                                                <?php if (strpos($cat['icon'], 'fa-') === 0 || strpos($cat['icon'], 'fas ') === 0): ?>
                                                    <i class="<?= e($cat['icon']) ?> text-dark fa-lg"></i>
                                                <?php else: ?>
                                                    <img src="<?= e($cat['icon']) ?>"
                                                        style="width: 24px; height: 24px; object-fit: contain;">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <i class="fas fa-folder text-muted fa-lg"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-dark"><?= e($cat['name']) ?></td>
                                        <td><code class="text-muted"><?= e($cat['slug']) ?></code></td>
                                        <td class="text-center"><span
                                                class="badge bg-light text-dark border"><?= $cat['product_count'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge bg-<?= $cat['status'] === 'active' ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= $cat['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($cat['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="/admin/category-edit.php?id=<?= $cat['id'] ?>"
                                                class="btn btn-sm btn-light rounded-circle"><i
                                                    class="fas fa-pen small"></i></a>
                                            <?php if ($cat['product_count'] == 0): ?>
                                                <a href="?delete_category=<?= $cat['id'] ?>"
                                                    class="btn btn-sm btn-light rounded-circle text-danger"
                                                    onclick="return confirm('Xóa danh mục này?')"><i
                                                        class="fas fa-trash small"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Banner Modal -->
    <div class="modal fade" id="bannerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Chỉnh sửa Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="bannerForm">
                        <input type="hidden" id="banner_id" name="banner_id">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tiêu đề</label>
                            <input type="text" class="form-control rounded-3" id="banner_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Hình ảnh</label>
                            <input type="file" class="form-control rounded-3 mb-2" id="banner_image" name="image"
                                accept="image/*">
                            <img id="banner_image_preview" src="" class="img-fluid rounded-3 border"
                                style="max-height: 150px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Liên kết</label>
                            <input type="text" class="form-control rounded-3" id="banner_link" name="link"
                                placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Trạng thái</label>
                            <select class="form-select rounded-3" id="banner_status" name="status">
                                <option value="active">Hiển thị</option>
                                <option value="inactive">Ẩn</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="pill-button pill-button-white" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="pill-button pill-button-gray" onclick="saveBanner()">Lưu thay
                        đổi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Promo Modal -->
    <div class="modal fade" id="promoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Sửa Promo Banner #<span id="promo_number"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="promoForm">
                        <input type="hidden" id="promo_index" name="promo_index">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Hình ảnh (345x161px)</label>
                            <div class="input-group mb-2">
                                <input type="file" class="form-control rounded-start-3" id="promo_image"
                                    accept="image/*" onchange="previewPromoImage(this)">
                            </div>
                            <input type="text" class="form-control rounded-3 mb-2 small text-muted" id="promo_image_url"
                                placeholder="Hoặc URL ảnh..." onchange="updatePromoPreview(this.value)">
                            <img id="promo_image_preview" src="" class="img-fluid rounded-3 border w-100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Liên kết</label>
                            <input type="text" class="form-control rounded-3" id="promo_link" name="link"
                                placeholder="https://...">
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="pill-button pill-button-white" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="pill-button pill-button-gray" onclick="savePromo()">Lưu thay
                        đổi</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sortable
        new Sortable(document.getElementById('sortable-categories'), {
            animation: 150,
            handle: '.sort-handle',
            onEnd: function (evt) {
                const ids = Array.from(document.querySelectorAll('#sortable-categories .list-group-item[data-id]'))
                    .map((item, index) => ({ id: item.dataset.id, sort_order: index }));

                // Call API (using existing endpoint logic, adjust path if needed)
                fetch('/api/admin/update-category-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ categories: ids })
                });
            }
        });

        new Sortable(document.getElementById('sortable-banners'), {
            animation: 150,
            handle: '.sort-handle',
            onEnd: function (evt) {
                const ids = Array.from(document.querySelectorAll('#sortable-banners tr[data-id]'))
                    .map((row, index) => ({ id: row.dataset.id, sort_order: index }));

                fetch('/api/admin/update-banner-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ banners: ids })
                });
            }
        });

        // Banner Functions
        function openBannerModal(id) {
            // Fetch banner data
            fetch(`/api/admin/get-banner.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const b = data.banner;
                        document.getElementById('banner_id').value = b.id;
                        document.getElementById('banner_title').value = b.title;
                        document.getElementById('banner_image_preview').src = b.image;
                        document.getElementById('banner_link').value = b.link;
                        document.getElementById('banner_status').value = b.status;
                        new bootstrap.Modal(document.getElementById('bannerModal')).show();
                    } else {
                        alert('Lỗi: ' + data.error);
                    }
                });
        }

        function saveBanner() {
            const formData = new FormData(document.getElementById('bannerForm'));
            // Logic upload ảnh nếu có
            fetch('/api/admin/update-banner.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else alert(data.error || 'Lỗi lưu banner');
            });
        }

        // Promo Functions
        function openPromoModal(index, image, link) {
            document.getElementById('promo_index').value = index;
            document.getElementById('promo_number').textContent = index + 1;
            document.getElementById('promo_image_preview').src = image;
            document.getElementById('promo_image_url').value = image;
            document.getElementById('promo_link').value = link;
            new bootstrap.Modal(document.getElementById('promoModal')).show();
        }

        function previewPromoImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('promo_image_preview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function updatePromoPreview(url) {
            document.getElementById('promo_image_preview').src = url;
        }

        function savePromo() {
            const formData = new FormData();
            formData.append('index', document.getElementById('promo_index').value);
            formData.append('link', document.getElementById('promo_link').value);

            const file = document.getElementById('promo_image').files[0];
            if (file) formData.append('image', file);
            else formData.append('image_url', document.getElementById('promo_image_url').value);

            fetch('/api/admin/update-promo-banner.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else alert(data.error || 'Lỗi lưu promo');
            });
        }
    </script>
</body>

</html>