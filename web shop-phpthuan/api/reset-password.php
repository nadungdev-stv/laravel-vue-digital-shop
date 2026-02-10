<?php
/**
 * API: Xác nhận mã và đặt lại mật khẩu
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

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
$code = trim($_POST['code'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate
if (empty($email) || empty($code) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
    exit;
}

if (!isValidEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
    exit;
}

if (strlen($code) !== 6 || !ctype_digit($code)) {
    echo json_encode(['success' => false, 'message' => 'Mã xác nhận không hợp lệ']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp']);
    exit;
}

try {
    // Kiểm tra mã xác nhận
    $stmt = db()->query(
        "SELECT * FROM password_resets
         WHERE email = ? AND code = ? AND used = 0 AND expires_at > NOW()
         ORDER BY created_at DESC LIMIT 1",
        [$email, $code]
    );
    $resetRequest = $stmt->fetch();

    if (!$resetRequest) {
        // Kiểm tra xem mã đã hết hạn hay sai
        $stmt = db()->query(
            "SELECT * FROM password_resets WHERE email = ? AND code = ? ORDER BY created_at DESC LIMIT 1",
            [$email, $code]
        );
        $expiredOrUsed = $stmt->fetch();

        if ($expiredOrUsed) {
            if ($expiredOrUsed['used']) {
                echo json_encode(['success' => false, 'message' => 'Mã xác nhận đã được sử dụng']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Mã xác nhận đã hết hạn. Vui lòng yêu cầu mã mới.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Mã xác nhận không đúng']);
        }
        exit;
    }

    // Kiểm tra user tồn tại
    $stmt = db()->query("SELECT id, username FROM users WHERE email = ? AND status = 'active'", [$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Tài khoản không tồn tại hoặc đã bị khóa']);
        exit;
    }

    // Cập nhật mật khẩu mới
    $hashedPassword = hashPassword($password);
    db()->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $user['id']]);

    // Đánh dấu mã đã sử dụng
    db()->query("UPDATE password_resets SET used = 1 WHERE id = ?", [$resetRequest['id']]);

    // Xóa tất cả mã cũ của email này
    db()->query("DELETE FROM password_resets WHERE email = ? AND id != ?", [$email, $resetRequest['id']]);

    // Tạo thông báo cho user
    createNotification(
        $user['id'],
        'Mật khẩu đã được thay đổi',
        'Mật khẩu tài khoản của bạn đã được đặt lại thành công. Nếu bạn không thực hiện thao tác này, vui lòng liên hệ hỗ trợ ngay.',
        'info',
        '/profile'
    );

    echo json_encode([
        'success' => true,
        'message' => 'Đặt lại mật khẩu thành công! Đang chuyển đến trang đăng nhập...'
    ]);

} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
    ]);
}
