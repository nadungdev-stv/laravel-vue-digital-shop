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
$fullName = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validate
if (empty($email) || !isValidEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
    exit;
}

// Kiểm tra email trùng
$stmt = db()->query("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng bởi tài khoản khác']);
    exit;
}

// Update
try {
    db()->query(
        "UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?",
        [$fullName, $phone, $email, $user['id']]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thông tin thành công'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
