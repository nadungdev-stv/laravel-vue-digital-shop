<?php
// Maintenance Page
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Prevent direct access if maintenance mode is off (optional, but good for SEO)
if (getSetting('maintenance_mode') != '1' && !isAdmin()) {
    header("Location: /");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đang bảo trì - <?= e(getSetting('site_name', 'Veyrix Shop')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1c23 0%, #111 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            margin: 0;
        }
        .maintenance-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem;
            max-width: 600px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #7387df 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2rem;
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.3);
        }
        h1 {
            background: linear-gradient(to right, #fff, #a5a5a5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .message {
            color: #9ca3af;
            line-height: 1.6;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        .pill-button {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        .btn-reload {
            background: linear-gradient(135deg, #667eea 0%, #7387df 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.4);
        }
        .btn-reload:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.5);
            color: white;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="icon-circle">
            <i class="fas fa-tools text-white"></i>
        </div>
        <h1>Hệ thống đang bảo trì</h1>
        <p class="message">
            Chúng tôi đang tiến hành nâng cấp hệ thống để phục vụ bạn tốt hơn.
            <br>Vui lòng quay lại sau ít phút. Xin lỗi vì sự bất tiện này.
        </p>
        <button onclick="location.reload()" class="pill-button btn-reload">
            <i class="fas fa-sync-alt me-2"></i> Tải lại trang
        </button>
        
        <?php if (getSetting('contact_zalo')): ?>
        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
            <small class="text-secondary">Cần hỗ trợ gấp? Liên hệ Zalo: <?= e(getSetting('contact_zalo')) ?></small>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
