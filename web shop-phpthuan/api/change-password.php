<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

initSession();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user = getCurrentUser();
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate
if (empty($currentPassword) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
    exit;
}

if (!verifyPassword($currentPassword, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp']);
    exit;
}

// Update password
try {
    $hashedPassword = hashPassword($newPassword);
    db()->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $user['id']]);

    // Tạo thông báo
    createNotification(
        $user['id'],
        'Mật khẩu đã được thay đổi',
        'Mật khẩu tài khoản của bạn đã được thay đổi thành công. Nếu không phải bạn thực hiện, vui lòng liên hệ ngay với chúng tôi.',
        'success',
        '/account?tab=password'
    );

    echo json_encode([
        'success' => true,
        'message' => 'Đổi mật khẩu thành công'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
