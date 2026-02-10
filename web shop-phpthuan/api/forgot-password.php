<?php
/**
 * API: Gửi mã reset password qua email
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mailer.php';

initSession();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// CSRF Protection
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Token bảo mật không hợp lệ']);
    exit;
}

$email = trim($_POST['email'] ?? '');

// Validate email
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email']);
    exit;
}

if (!isValidEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
    exit;
}

try {
    // Kiểm tra email có tồn tại trong hệ thống không
    $stmt = db()->query("SELECT id, username, full_name FROM users WHERE email = ? AND status = 'active'", [$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Không tiết lộ email không tồn tại (bảo mật)
        echo json_encode([
            'success' => true,
            'message' => 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được mã xác nhận.'
        ]);
        exit;
    }

    // Rate limiting: Kiểm tra số lần gửi trong 1 giờ qua
    $stmt = db()->query(
        "SELECT COUNT(*) as count FROM password_resets WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
        [$email]
    );
    $recentRequests = $stmt->fetch()['count'];

    if ($recentRequests >= 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau 1 giờ.'
        ]);
        exit;
    }

    // Tạo mã 6 chữ số ngẫu nhiên
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Thời gian hết hạn: 15 phút
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Xóa các mã cũ chưa sử dụng của email này
    db()->query("DELETE FROM password_resets WHERE email = ? AND used = 0", [$email]);

    // Lưu mã mới vào database
    db()->query(
        "INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)",
        [$email, $code, $expiresAt]
    );

    // Tạo nội dung email
    $userName = $user['full_name'] ?: $user['username'];
    $siteName = getSetting('site_name', 'Veyrix Shop');

    $emailContent = "
        <p>Xin chào <strong>{$userName}</strong>,</p>
        <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản của mình tại {$siteName}.</p>
        <p>Mã xác nhận của bạn là:</p>
        <div style='text-align: center; margin: 30px 0;'>
            <div style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 40px; border-radius: 12px; font-size: 32px; font-weight: 700; letter-spacing: 8px;'>
                {$code}
            </div>
        </div>
        <p><strong>Lưu ý:</strong></p>
        <ul style='color: #6b7280;'>
            <li>Mã này có hiệu lực trong <strong>15 phút</strong></li>
            <li>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này</li>
            <li>Không chia sẻ mã này với bất kỳ ai</li>
        </ul>
    ";

    // Gửi email
    $emailSent = sendEmailTemplate(
        $email,
        "[$siteName] Mã xác nhận đặt lại mật khẩu",
        "Đặt lại mật khẩu",
        $emailContent
    );

    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Mã xác nhận đã được gửi đến email của bạn!'
        ]);
    } else {
        // Xóa mã nếu gửi email thất bại
        db()->query("DELETE FROM password_resets WHERE email = ? AND code = ?", [$email, $code]);

        echo json_encode([
            'success' => false,
            'message' => 'Không thể gửi email. Vui lòng thử lại sau.'
        ]);
    }

} catch (Exception $e) {
    error_log("Forgot password error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
    ]);
}
