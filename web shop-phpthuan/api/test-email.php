<?php
/**
 * API: Gửi email test để kiểm tra cấu hình SMTP
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mailer.php';

initSession();
header('Content-Type: application/json');

// Chỉ admin mới được test
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !isValidEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email hợp lệ']);
    exit;
}

try {
    $siteName = getSetting('site_name', 'Veyrix Shop');
    $smtpHost = getSetting('smtp_host', '');
    $smtpUser = getSetting('smtp_username', '');

    // Thông tin cấu hình
    $configInfo = "
        <p><strong>Cấu hình SMTP hiện tại:</strong></p>
        <ul style='color: #6b7280; font-size: 14px;'>
            <li>Host: " . ($smtpHost ?: '<em>Chưa cấu hình</em>') . "</li>
            <li>Username: " . ($smtpUser ?: '<em>Chưa cấu hình</em>') . "</li>
            <li>Thời gian gửi: " . date('d/m/Y H:i:s') . "</li>
        </ul>
    ";

    $emailContent = "
        <p>Xin chào,</p>
        <p>Đây là email test từ hệ thống <strong>{$siteName}</strong>.</p>
        <p>Nếu bạn nhận được email này, cấu hình SMTP đã hoạt động đúng!</p>
        {$configInfo}
        <p style='color: #10b981; font-weight: 600;'>
            <i class='fas fa-check-circle'></i> Cấu hình SMTP thành công!
        </p>
    ";

    $result = sendEmailTemplate(
        $email,
        "[$siteName] Test Email - Kiểm tra cấu hình SMTP",
        "Email Test Thành Công!",
        $emailContent
    );

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => "Email test đã được gửi đến {$email}. Vui lòng kiểm tra hộp thư (có thể trong Spam)."
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể gửi email. Vui lòng kiểm tra lại cấu hình SMTP.'
        ]);
    }

} catch (Exception $e) {
    error_log("Test email error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
