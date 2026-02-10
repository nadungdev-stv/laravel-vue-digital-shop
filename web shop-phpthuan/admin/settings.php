<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Cấu hình hệ thống';

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settings = $_POST['settings'] ?? [];

    if (!isset($settings['maintenance_mode'])) {
        $settings['maintenance_mode'] = '0';
    }

    foreach ($settings as $key => $value) {
        // Use updateSetting helper for consistency
        updateSetting($key, $value);
    }

    setFlash('success', 'Đã lưu cấu hình thành công');
    redirect('/admin/settings.php');
}

// Xử lý xóa cache
if (isset($_GET['action']) && $_GET['action'] === 'clear_cache') {
    setFlash('success', 'Đã xóa cache thành công');
    redirect('/admin/settings.php');
}

// Xử lý backup database
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    setFlash('info', 'Tính năng backup đang được phát triển');
    redirect('/admin/settings.php');
}

// Lấy tất cả cài đặt hiện tại
$currentSettings = [];
try {
    $settingsData = db()->query("SELECT * FROM settings")->fetchAll();
    foreach ($settingsData as $setting) {
        $currentSettings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    error_log("Settings error: " . $e->getMessage());
}

if (!function_exists('getSettingValue')) {
    function getSettingValue($key, $default = '')
    {
        global $currentSettings;
        return isset($currentSettings[$key]) ? $currentSettings[$key] : $default;
    }
}

// Thống kê hệ thống
try {
    $systemStats = [
        'total_users' => db()->query("SELECT COUNT(*) as count FROM users")->fetch()['count'] ?? 0,
        'total_products' => db()->query("SELECT COUNT(*) as count FROM products")->fetch()['count'] ?? 0,
        'total_orders' => db()->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'] ?? 0,
        'total_revenue' => db()->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'")->fetch()['total'] ?? 0,
        'php_version' => phpversion(),
        'server_time' => date('Y-m-d H:i:s'),
    ];
} catch (Exception $e) {
    $systemStats = [];
}

// Lấy danh sách payment methods
$paymentMethods = [];
try {
    $paymentMethods = db()->query("SELECT * FROM payment_methods ORDER BY sort_order")->fetchAll();
} catch (Exception $e) {
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
    <style>
        .settings-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #d2d2d7;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .settings-card-header {
            background: #f5f5f7;
            padding: 16px 24px;
            border-bottom: 1px solid #e5e5e7;
            font-weight: 600;
            color: #1d1d1f;
            display: flex;
            align-items: center;
        }

        .settings-card-body {
            padding: 24px;
        }

        .settings-nav .list-group-item {
            border: none;
            padding: 12px 16px;
            margin-bottom: 4px;
            border-radius: 8px;
            color: #1d1d1f;
            transition: all 0.2s;
            font-weight: 500;
        }

        .settings-nav .list-group-item:hover {
            background-color: #f5f5f7;
            color: #0071e3;
        }

        .settings-nav .list-group-item.active {
            background-color: #e8f2ff;
            color: #0071e3;
        }

        .settings-nav .list-group-item i {
            width: 24px;
            text-align: center;
            margin-right: 8px;
        }

        .form-label {
            font-weight: 500;
            font-size: 13px;
            color: #1d1d1f;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border-color: #d2d2d7;
            font-size: 14px;
            padding: 10px 12px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0071e3;
            box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.2);
        }

        .form-text {
            font-size: 12px;
            color: #86868b;
            margin-top: 4px;
        }

        .system-stat-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f7;
            font-size: 13px;
        }

        .system-stat-row:last-child {
            border-bottom: none;
        }

        .system-stat-label {
            color: #86868b;
        }

        .system-stat-value {
            font-weight: 600;
            color: #1d1d1f;
        }
    </style>
</head>

<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">

        <div class="d-flex justify-content-between align-items-center my-4">
            <div>

                <p class="text-secondary mb-0">Quản lý cấu hình toàn hệ thống</p>
            </div>
            <div>
                <a href="?action=clear_cache" class="pill-button pill-button-white text-warning border-warning me-2"
                    onclick="return confirm('Xóa cache hệ thống?')">
                    <i class="fas fa-broom me-2"></i>Xóa Cache
                </a>
                <a href="?action=backup" class="pill-button pill-button-white text-primary border-primary">
                    <i class="fas fa-database me-2"></i>Sao lưu
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Sidebar Navigation -->
            <div class="col-lg-3">
                <div class="settings-card mb-4">
                    <div class="settings-card-body p-2">
                        <div class="list-group settings-nav" id="settings-tabs" role="tablist">
                            <a class="list-group-item list-group-item-action active" data-bs-toggle="list"
                                href="#general">
                                <i class="fas fa-sliders-h"></i> Chung
                            </a>
                            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#contact">
                                <i class="fas fa-address-book"></i> Liên hệ
                            </a>
                            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#payment">
                                <i class="fas fa-credit-card"></i> Thanh toán
                            </a>
                            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#email">
                                <i class="fas fa-envelope"></i> Email
                            </a>
                            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#seo">
                                <i class="fas fa-search"></i> SEO
                            </a>
                            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#system">
                                <i class="fas fa-server"></i> Hệ thống
                            </a>
                            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#netflix">
                                <i class="fab fa-netflix text-danger"></i> Netflix Tools
                            </a>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header py-2 bg-white small text-uppercase text-secondary">
                        Thông tin hệ thống
                    </div>
                    <div class="settings-card-body pt-0">
                        <div class="system-stat-row">
                            <span class="system-stat-label">PHP Version</span>
                            <span class="system-stat-value"><?= phpversion() ?></span>
                        </div>
                        <div class="system-stat-row">
                            <span class="system-stat-label">Users</span>
                            <span
                                class="system-stat-value"><?= number_format($systemStats['total_users'] ?? 0) ?></span>
                        </div>
                        <div class="system-stat-row">
                            <span class="system-stat-label">Orders</span>
                            <span
                                class="system-stat-value"><?= number_format($systemStats['total_orders'] ?? 0) ?></span>
                        </div>
                        <div class="system-stat-row">
                            <span class="system-stat-label">Products</span>
                            <span
                                class="system-stat-value"><?= number_format($systemStats['total_products'] ?? 0) ?></span>
                        </div>
                        <div class="system-stat-row">
                            <span class="system-stat-label">Doanh thu</span>
                            <span
                                class="system-stat-value text-success"><?= formatMoney($systemStats['total_revenue'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="col-lg-9">
                <form method="POST">
                    <div class="tab-content">

                        <!-- General -->
                        <div class="tab-pane fade show active" id="general">
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    Cài đặt chung
                                </div>
                                <div class="settings-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tên Website</label>
                                            <input type="text" name="settings[site_name]" class="form-control"
                                                value="<?= e(getSettingValue('site_name', 'Veyrix Shop')) ?>">

                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Slogan</label>
                                            <input type="text" name="settings[site_slogan]" class="form-control"
                                                value="<?= e(getSettingValue('site_slogan', 'Uy tín - Chất lượng')) ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Mô tả ngắn</label>
                                            <textarea name="settings[site_description]" class="form-control"
                                                rows="2"><?= e(getSettingValue('site_description')) ?></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Logo URL</label>
                                            <input type="text" name="settings[site_logo]" class="form-control"
                                                value="<?= e(getSettingValue('site_logo')) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Favicon URL</label>
                                            <input type="text" name="settings[site_favicon]" class="form-control"
                                                value="<?= e(getSettingValue('site_favicon')) ?>">
                                        </div>

                                        <div class="col-12">
                                            <hr class="my-2 border-light">
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-check form-switch ps-0">
                                                <label class="form-check-label ms-5 fw-bold" for="maintenance_mode">Bảo
                                                    trì hệ thống</label>
                                                <input class="form-check-input ms-0 fs-5" type="checkbox"
                                                    name="settings[maintenance_mode]" id="maintenance_mode" value="1"
                                                    <?= getSettingValue('maintenance_mode') == '1' ? 'checked' : '' ?>>
                                                <div class="form-text ms-5">Chỉ admin mới có thể truy cập khi bật</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch ps-0">
                                                <label class="form-check-label ms-5 fw-bold"
                                                    for="allow_registration">Cho phép đăng ký</label>
                                                <input class="form-check-input ms-0 fs-5" type="checkbox"
                                                    name="settings[allow_registration]" id="allow_registration"
                                                    value="1" <?= getSettingValue('allow_registration', '1') == '1' ? 'checked' : '' ?>>
                                                <div class="form-text ms-5">Cho phép người dùng mới tạo tài khoản</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div class="tab-pane fade" id="contact">
                            <div class="settings-card">
                                <div class="settings-card-header">Thông tin liên hệ</div>
                                <div class="settings-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Email hỗ trợ</label>
                                            <input type="email" name="settings[contact_email]" class="form-control"
                                                value="<?= e(getSettingValue('contact_email')) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Hotline / Zalo</label>
                                            <input type="text" name="settings[contact_phone]" class="form-control"
                                                value="<?= e(getSettingValue('contact_phone')) ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Địa chỉ</label>
                                            <input type="text" name="settings[contact_address]" class="form-control"
                                                value="<?= e(getSettingValue('contact_address')) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Facebook</label>
                                            <input type="text" name="settings[social_facebook]" class="form-control"
                                                value="<?= e(getSettingValue('social_facebook')) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Telegram</label>
                                            <input type="text" name="settings[social_telegram]" class="form-control"
                                                value="<?= e(getSettingValue('social_telegram')) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Youtube</label>
                                            <input type="text" name="settings[social_youtube]" class="form-control"
                                                value="<?= e(getSettingValue('social_youtube')) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment -->
                        <div class="tab-pane fade" id="payment">
                            <div class="settings-card">
                                <div class="settings-card-header">Cấu hình thanh toán</div>
                                <div class="settings-card-body">
                                    <div class="mb-4">
                                        <h6 class="fw-bold mb-3">Phương thức thanh toán hiện có</h6>
                                        <?php if (empty($paymentMethods)): ?>
                                            <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-warning">
                                                Chưa có phương thức nào</div>
                                        <?php else: ?>
                                            <div class="row g-2">
                                                <?php foreach ($paymentMethods as $method): ?>
                                                    <div class="col-md-4">
                                                        <div class="p-3 border rounded-3 bg-light">
                                                            <div class="d-flex align-items-center mb-2">
                                                                <i class="<?= e($method['icon']) ?> me-2"></i>
                                                                <strong><?= e($method['method_code']) ?></strong>
                                                            </div>
                                                            <div class="small text-secondary"><?= e($method['method_name']) ?>
                                                            </div>
                                                            <div class="mt-2">
                                                                <span
                                                                    class="badge bg-<?= $method['is_active'] ? 'success' : 'secondary' ?> rounded-pill">
                                                                    <?= $method['is_active'] ? 'Active' : 'Inactive' ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <hr class="border-light">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Đơn vị tiền tệ</label>
                                            <select name="settings[currency]" class="form-select">
                                                <option value="VND" <?= getSettingValue('currency', 'VND') === 'VND' ? 'selected' : '' ?>>VND (₫)</option>
                                                <option value="USD" <?= getSettingValue('currency') === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tối thiểu nạp/mua</label>
                                            <input type="number" name="settings[min_order_amount]" class="form-control"
                                                value="<?= e(getSettingValue('min_order_amount', 0)) ?>">
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check form-switch ps-0">
                                                <label class="form-check-label ms-5 fw-bold" for="auto_deliver">Tự động
                                                    trả hàng</label>
                                                <input class="form-check-input ms-0 fs-5" type="checkbox"
                                                    name="settings[auto_deliver]" id="auto_deliver" value="1"
                                                    <?= getSettingValue('auto_deliver', '1') == '1' ? 'checked' : '' ?>>
                                                <div class="form-text ms-5">Gửi tài khoản ngay sau khi thanh toán thành
                                                    công</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SePay Integration -->
                            <div class="settings-card mt-4">
                                <div class="settings-card-header">
                                    <i class="fas fa-qrcode me-2"></i>Tích hợp SePay (Thanh toán tự động)
                                </div>
                                <div class="settings-card-body">
                                    <div class="alert alert-info small mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        SePay giúp tự động xác nhận thanh toán khi khách hàng chuyển khoản.
                                        <a href="https://my.sepay.vn" target="_blank">Đăng ký tại đây</a>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-check form-switch ps-0">
                                                <label class="form-check-label ms-5 fw-bold" for="sepay_enabled">Bật
                                                    SePay</label>
                                                <input class="form-check-input ms-0 fs-5" type="checkbox"
                                                    name="settings[sepay_enabled]" id="sepay_enabled" value="1"
                                                    <?= getSettingValue('sepay_enabled') == '1' ? 'checked' : '' ?>>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">API Key</label>
                                            <input type="text" name="settings[sepay_api_key]"
                                                class="form-control font-monospace"
                                                value="<?= e(getSettingValue('sepay_api_key')) ?>"
                                                placeholder="Lấy từ SePay Dashboard">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Số tài khoản</label>
                                            <input type="text" name="settings[sepay_account_number]"
                                                class="form-control"
                                                value="<?= e(getSettingValue('sepay_account_number')) ?>"
                                                placeholder="VD: 0123456789">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Ngân hàng</label>
                                            <select name="settings[sepay_bank_code]" class="form-select">
                                                <option value="">-- Chọn ngân hàng --</option>
                                                <option value="MB" <?= getSettingValue('sepay_bank_code') === 'MB' ? 'selected' : '' ?>>MB Bank</option>
                                                <option value="VCB" <?= getSettingValue('sepay_bank_code') === 'VCB' ? 'selected' : '' ?>>Vietcombank</option>
                                                <option value="TCB" <?= getSettingValue('sepay_bank_code') === 'TCB' ? 'selected' : '' ?>>Techcombank</option>
                                                <option value="ACB" <?= getSettingValue('sepay_bank_code') === 'ACB' ? 'selected' : '' ?>>ACB</option>
                                                <option value="VPB" <?= getSettingValue('sepay_bank_code') === 'VPB' ? 'selected' : '' ?>>VPBank</option>
                                                <option value="TPB" <?= getSettingValue('sepay_bank_code') === 'TPB' ? 'selected' : '' ?>>TPBank</option>
                                                <option value="BIDV" <?= getSettingValue('sepay_bank_code') === 'BIDV' ? 'selected' : '' ?>>BIDV</option>
                                                <option value="VTB" <?= getSettingValue('sepay_bank_code') === 'VTB' ? 'selected' : '' ?>>Vietinbank</option>
                                                <option value="MSB" <?= getSettingValue('sepay_bank_code') === 'MSB' ? 'selected' : '' ?>>MSB</option>
                                                <option value="SHB" <?= getSettingValue('sepay_bank_code') === 'SHB' ? 'selected' : '' ?>>SHB</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tên tài khoản</label>
                                            <input type="text" name="settings[sepay_account_name]" class="form-control"
                                                value="<?= e(getSettingValue('sepay_account_name')) ?>"
                                                placeholder="VD: NGUYEN VAN A">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Webhook URL</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control font-monospace bg-light"
                                                    value="<?= e((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']) ?>/api/sepay-webhook.php"
                                                    readonly id="sepayWebhookUrl">
                                                <button type="button" class="btn btn-outline-secondary"
                                                    onclick="copyWebhookUrl()">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Copy URL này và dán vào phần Webhook trong SePay
                                                Dashboard</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="tab-pane fade" id="email">
                            <div class="settings-card">
                                <div class="settings-card-header">SMTP Server</div>
                                <div class="settings-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Host</label>
                                            <input type="text" name="settings[smtp_host]" class="form-control"
                                                value="<?= e(getSettingValue('smtp_host')) ?>"
                                                placeholder="smtp.gmail.com">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Port</label>
                                            <input type="text" name="settings[smtp_port]" class="form-control"
                                                value="<?= e(getSettingValue('smtp_port', '587')) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Encryption</label>
                                            <select name="settings[smtp_encryption]" class="form-select">
                                                <option value="tls" <?= getSettingValue('smtp_encryption') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                                <option value="ssl" <?= getSettingValue('smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Username</label>
                                            <input type="text" name="settings[smtp_username]" class="form-control"
                                                value="<?= e(getSettingValue('smtp_username')) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password</label>
                                            <input type="password" name="settings[smtp_password]" class="form-control"
                                                value="<?= e(getSettingValue('smtp_password')) ?>"
                                                placeholder="********">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Mail From Address</label>
                                            <input type="text" name="settings[mail_from_address]" class="form-control"
                                                value="<?= e(getSettingValue('mail_from_address')) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Mail From Name</label>
                                            <input type="text" name="settings[mail_from_name]" class="form-control"
                                                value="<?= e(getSettingValue('mail_from_name')) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Test Email -->
                            <div class="settings-card mt-4">
                                <div class="settings-card-header">
                                    <i class="fas fa-paper-plane me-2"></i>Kiểm tra gửi Email
                                </div>
                                <div class="settings-card-body">
                                    <div class="alert alert-info small mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Gửi email test để kiểm tra cấu hình SMTP hoạt động đúng. Hãy <strong>Lưu cài
                                            đặt</strong> trước khi test.
                                    </div>
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-6">
                                            <label class="form-label">Email nhận test</label>
                                            <input type="email" id="testEmailAddress" class="form-control"
                                                placeholder="your-email@example.com">
                                        </div>
                                        <div class="col-md-6">
                                            <button type="button" id="sendTestEmail" class="btn btn-outline-primary">
                                                <i class="fas fa-paper-plane me-2"></i>Gửi Email Test
                                            </button>
                                        </div>
                                    </div>
                                    <div id="testEmailResult" class="mt-3"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Netflix -->
                        <div class="tab-pane fade" id="netflix">
                            <div class="settings-card">
                                <div class="settings-card-header">Netflix Tools Config</div>
                                <div class="settings-card-body">
                                    <div class="alert alert-light border small text-secondary">
                                        <i class="fas fa-key me-2"></i>Cấu hình tài khoản IMAP Gmail được lưu trong file
                                        <code>.env</code> để bảo mật.
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Thời gian quét (giờ gần nhất)</label>
                                        <input type="number" name="settings[netflix_max_days_ago]" class="form-control"
                                            value="<?= e(getSettingValue('netflix_max_days_ago', 3)) ?>">
                                        <div class="form-text">Mặc định 3 giờ để tối ưu tốc độ</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Whitelist Email Sender</label>
                                        <textarea name="settings[netflix_allowed_senders]"
                                            class="form-control font-monospace text-secondary"
                                            rows="3"><?= e(getSettingValue('netflix_allowed_senders', 'info@account.netflix.com')) ?></textarea>
                                        <div class="form-text">Mỗi email một dòng</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEO -->
                        <div class="tab-pane fade" id="seo">
                            <!-- Google Search Preview -->
                            <div class="settings-card mb-4">
                                <div class="settings-card-header">
                                    <i class="fab fa-google me-2"></i>Xem trước trên Google
                                </div>
                                <div class="settings-card-body">
                                    <div class="google-preview p-3 rounded"
                                        style="background: #202124; color: #bdc1c6; font-family: Arial, sans-serif;">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="me-3"
                                                style="width: 28px; height: 28px; background: #303134; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-globe" style="font-size: 14px; color: #9aa0a6;"></i>
                                            </div>
                                            <div>
                                                <div style="color: #bdc1c6; font-size: 14px;" id="preview-domain">
                                                    veyrix.pro</div>
                                                <div style="color: #969ba1; font-size: 12px;">https://veyrix.pro</div>
                                            </div>
                                        </div>
                                        <div id="preview-title"
                                            style="color: #8ab4f8; font-size: 20px; margin-bottom: 8px; cursor: pointer;">
                                            <?= e(getSettingValue('seo_title', 'Veyrix Shop: Mua tài khoản Premium uy tín - YouTube, Netflix ...')) ?>
                                        </div>
                                        <div id="preview-description"
                                            style="color: #bdc1c6; font-size: 14px; line-height: 1.5;">
                                            <?= e(getSettingValue('seo_description', 'Veyrix Shop. Shop bán tài khoản premium uy tín hàng đầu Việt Nam. Cung cấp YouTube Premium, Netflix, Spotify, VPN, Canva với giá tốt nhất. Bảo hành trọn gói.')) ?>
                                        </div>
                                    </div>
                                    <small class="text-muted mt-2 d-block">
                                        <i class="fas fa-info-circle me-1"></i>Đây là cách website hiển thị trên Google
                                        Search. Thay đổi sẽ cập nhật sau vài ngày đến vài tuần.
                                    </small>
                                </div>
                            </div>

                            <!-- Homepage SEO -->
                            <div class="settings-card mb-4">
                                <div class="settings-card-header">
                                    <i class="fas fa-home me-2"></i>SEO Trang chủ
                                </div>
                                <div class="settings-card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Tiêu đề trang chủ (Title Tag)</label>
                                        <input type="text" name="settings[seo_title]" class="form-control"
                                            id="seo_title"
                                            value="<?= e(getSettingValue('seo_title', 'Veyrix Shop: Mua tài khoản Premium uy tín - YouTube, Netflix ...')) ?>"
                                            placeholder="VD: Veyrix Shop: Mua tài khoản Premium uy tín - YouTube, Netflix ...">
                                        <small class="text-muted">Độ dài tối ưu: 50-60 ký tự. Hiện tại: <span
                                                id="title-count">0</span> ký tự</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Mô tả trang chủ (Meta Description)</label>
                                        <textarea name="settings[seo_description]" class="form-control" rows="3"
                                            id="seo_description"
                                            placeholder="VD: Veyrix Shop. Shop bán tài khoản premium uy tín hàng đầu Việt Nam..."><?= e(getSettingValue('seo_description', 'Veyrix Shop. Shop bán tài khoản premium uy tín hàng đầu Việt Nam. Cung cấp YouTube Premium, Netflix, Spotify, VPN, Canva với giá tốt nhất. Bảo hành trọn gói.')) ?></textarea>
                                        <small class="text-muted">Độ dài tối ưu: 150-160 ký tự. Hiện tại: <span
                                                id="desc-count">0</span> ký tự</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Từ khóa (Meta Keywords)</label>
                                        <input type="text" name="settings[seo_keywords]" class="form-control"
                                            value="<?= e(getSettingValue('seo_keywords', 'mua tài khoản premium, YouTube Premium, Netflix, Spotify, VPN, Canva Pro')) ?>"
                                            placeholder="mua tài khoản premium, YouTube Premium, Netflix, Spotify">
                                        <small class="text-muted">Các từ khóa cách nhau bằng dấu phẩy</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Open Graph / Social Media -->
                            <div class="settings-card mb-4">
                                <div class="settings-card-header">
                                    <i class="fas fa-share-alt me-2"></i>Chia sẻ mạng xã hội (Open Graph)
                                </div>
                                <div class="settings-card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Hình ảnh chia sẻ (OG Image)</label>
                                        <input type="text" name="settings[og_image]" class="form-control"
                                            value="<?= e(getSettingValue('og_image')) ?>"
                                            placeholder="https://veyrix.pro/public/images/og-image.jpg">
                                        <small class="text-muted">Kích thước đề xuất: 1200x630 pixels. Hiển thị khi
                                            share link lên Facebook, Zalo...</small>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">OG Site Name</label>
                                            <input type="text" name="settings[og_site_name]" class="form-control"
                                                value="<?= e(getSettingValue('og_site_name', 'Veyrix Shop')) ?>"
                                                placeholder="Veyrix Shop">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">OG Type</label>
                                            <select name="settings[og_type]" class="form-select">
                                                <option value="website" <?= getSettingValue('og_type', 'website') == 'website' ? 'selected' : '' ?>>Website</option>
                                                <option value="business.business"
                                                    <?= getSettingValue('og_type') == 'business.business' ? 'selected' : '' ?>>Business</option>
                                                <option value="product" <?= getSettingValue('og_type') == 'product' ? 'selected' : '' ?>>Product</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tracking & Analytics -->
                            <div class="settings-card mb-4">
                                <div class="settings-card-header">
                                    <i class="fas fa-chart-line me-2"></i>Tracking & Analytics
                                </div>
                                <div class="settings-card-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Google Analytics ID</label>
                                            <input type="text" name="settings[google_analytics]" class="form-control"
                                                value="<?= e(getSettingValue('google_analytics')) ?>"
                                                placeholder="G-XXXXXXXX">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Google Tag Manager ID</label>
                                            <input type="text" name="settings[gtm_id]" class="form-control"
                                                value="<?= e(getSettingValue('gtm_id')) ?>" placeholder="GTM-XXXXXXX">
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Facebook Pixel ID</label>
                                            <input type="text" name="settings[facebook_pixel]" class="form-control"
                                                value="<?= e(getSettingValue('facebook_pixel')) ?>"
                                                placeholder="123456789">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Google Search Console</label>
                                            <input type="text" name="settings[google_verification]" class="form-control"
                                                value="<?= e(getSettingValue('google_verification')) ?>"
                                                placeholder="google-site-verification=...">
                                            <small class="text-muted">Meta tag verification code</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Robots & Sitemap -->
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <i class="fas fa-robot me-2"></i>Robots & Sitemap
                                </div>
                                <div class="settings-card-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Robots Meta</label>
                                            <select name="settings[robots_meta]" class="form-select">
                                                <option value="index, follow" <?= getSettingValue('robots_meta', 'index, follow') == 'index, follow' ? 'selected' : '' ?>>Index, Follow (Khuyến
                                                    nghị)</option>
                                                <option value="index, nofollow"
                                                    <?= getSettingValue('robots_meta') == 'index, nofollow' ? 'selected' : '' ?>>Index, No Follow</option>
                                                <option value="noindex, follow"
                                                    <?= getSettingValue('robots_meta') == 'noindex, follow' ? 'selected' : '' ?>>No Index, Follow</option>
                                                <option value="noindex, nofollow"
                                                    <?= getSettingValue('robots_meta') == 'noindex, nofollow' ? 'selected' : '' ?>>No Index, No Follow</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Canonical URL</label>
                                            <input type="text" name="settings[canonical_url]" class="form-control"
                                                value="<?= e(getSettingValue('canonical_url', 'https://veyrix.pro')) ?>"
                                                placeholder="https://veyrix.pro">
                                        </div>
                                    </div>
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-lightbulb me-2"></i>
                                        <strong>Lưu ý:</strong> Các sitelinks như "Về chúng tôi", "Sản phẩm", "Liên hệ
                                        với chúng tôi" được Google tự động tạo dựa trên cấu trúc website. Đảm bảo các
                                        trang này có URL và tiêu đề rõ ràng.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System -->
                        <div class="tab-pane fade" id="system">
                            <div class="settings-card">
                                <div class="settings-card-header">Cấu hình nâng cao</div>
                                <div class="settings-card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="settings[enable_cache]"
                                            id="enable_cache" value="1" <?= getSettingValue('enable_cache', '1') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="enable_cache">Bật Cache hệ
                                            thống</label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="settings[force_https]"
                                            id="force_https" value="1" <?= getSettingValue('force_https') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="force_https">Bắt buộc HTTPS</label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="settings[debug_mode]"
                                            id="debug_mode" value="1" <?= getSettingValue('debug_mode') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold text-danger" for="debug_mode">Debug Mode
                                            (Dev Only)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" name="save_settings" class="pill-button pill-button-gray px-5">
                            <i class="fas fa-save me-2"></i>Lưu Thay Đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // SEO Preview & Character Count
        document.addEventListener('DOMContentLoaded', function () {
            const seoTitle = document.getElementById('seo_title');
            const seoDesc = document.getElementById('seo_description');
            const titleCount = document.getElementById('title-count');
            const descCount = document.getElementById('desc-count');
            const previewTitle = document.getElementById('preview-title');
            const previewDesc = document.getElementById('preview-description');

            if (seoTitle && seoDesc) {
                // Update character counts and preview
                function updateSEOPreview() {
                    const titleLen = seoTitle.value.length;
                    const descLen = seoDesc.value.length;

                    titleCount.textContent = titleLen;
                    descCount.textContent = descLen;

                    // Color coding for optimal length
                    titleCount.style.color = (titleLen >= 50 && titleLen <= 60) ? '#198754' : (titleLen > 60 ? '#dc3545' : '#6c757d');
                    descCount.style.color = (descLen >= 150 && descLen <= 160) ? '#198754' : (descLen > 160 ? '#dc3545' : '#6c757d');

                    // Update preview
                    let displayTitle = seoTitle.value || 'Tiêu đề trang web của bạn';
                    if (displayTitle.length > 60) {
                        displayTitle = displayTitle.substring(0, 57) + '...';
                    }
                    previewTitle.textContent = displayTitle;

                    let displayDesc = seoDesc.value || 'Mô tả trang web của bạn sẽ hiển thị ở đây...';
                    if (displayDesc.length > 160) {
                        displayDesc = displayDesc.substring(0, 157) + '...';
                    }
                    previewDesc.textContent = displayDesc;
                }

                seoTitle.addEventListener('input', updateSEOPreview);
                seoDesc.addEventListener('input', updateSEOPreview);

                // Initial update
                updateSEOPreview();
            }

            // Test Email
            const testEmailBtn = document.getElementById('sendTestEmail');
            const testEmailInput = document.getElementById('testEmailAddress');
            const testEmailResult = document.getElementById('testEmailResult');

            if (testEmailBtn) {
                testEmailBtn.addEventListener('click', async function () {
                    const email = testEmailInput.value.trim();

                    if (!email) {
                        testEmailResult.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Vui lòng nhập email</div>';
                        return;
                    }

                    // Disable button
                    testEmailBtn.disabled = true;
                    testEmailBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang gửi...';
                    testEmailResult.innerHTML = '';

                    try {
                        const formData = new FormData();
                        formData.append('email', email);

                        const response = await fetch('/api/test-email.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            testEmailResult.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + data.message + '</div>';
                        } else {
                            testEmailResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + data.message + '</div>';
                        }
                    } catch (error) {
                        testEmailResult.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Có lỗi xảy ra</div>';
                    }

                    // Re-enable button
                    testEmailBtn.disabled = false;
                    testEmailBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Gửi Email Test';
                });
            }
        });

        // Copy Webhook URL
        function copyWebhookUrl() {
            const input = document.getElementById('sepayWebhookUrl');
            input.select();
            document.execCommand('copy');

            // Show feedback
            const btn = input.nextElementSibling;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-success');

            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);
        }
    </script>
</body>

</html>