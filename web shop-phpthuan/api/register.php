<?php
/**
 * API đăng ký tài khoản
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

header('Content-Type: application/json');

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

// Nếu đã đăng nhập
if (isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'error' => 'Bạn đã đăng nhập rồi'
    ]);
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$fullName = trim($_POST['full_name'] ?? '');

// Validate
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'error' => 'Vui lòng nhập đầy đủ thông tin bắt buộc'
    ]);
    exit;
}

if (!isValidEmail($email)) {
    echo json_encode([
        'success' => false,
        'error' => 'Email không hợp lệ'
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        'success' => false,
        'error' => 'Mật khẩu phải có ít nhất 6 ký tự'
    ]);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode([
        'success' => false,
        'error' => 'Mật khẩu xác nhận không khớp'
    ]);
    exit;
}

try {
    // Check username exists
    $stmt = db()->query("SELECT id FROM users WHERE username = ?", [$username]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'error' => 'Tên đăng nhập đã tồn tại'
        ]);
        exit;
    }

    // Check email exists
    $stmt = db()->query("SELECT id FROM users WHERE email = ?", [$email]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'error' => 'Email đã được sử dụng'
        ]);
        exit;
    }

    // Insert new user
    $hashedPassword = hashPassword($password);
    $stmt = db()->query(
        "INSERT INTO users (username, email, password, full_name, role, status)
         VALUES (?, ?, ?, ?, 'user', 'active')",
        [$username, $email, $hashedPassword, $fullName]
    );

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'Có lỗi xảy ra khi tạo tài khoản'
        ]);
        exit;
    }

    $newUserId = db()->lastInsertId();

    // Tìm và gán các đơn hàng guest có cùng email
    $guestOrders = db()->query(
        "SELECT order_code FROM orders WHERE customer_email = ? AND user_id IS NULL",
        [$email]
    )->fetchAll();

    $orderMessage = '';
    if (!empty($guestOrders)) {
        // Cập nhật user_id cho các đơn hàng guest
        db()->query(
            "UPDATE orders SET user_id = ? WHERE customer_email = ? AND user_id IS NULL",
            [$newUserId, $email]
        );

        $orderCount = count($guestOrders);

        // Tạo thông báo
        createNotification(
            $newUserId,
            'Chào mừng bạn đến với ' . getSetting('site_name', 'Veyrix Shop') . '!',
            'Tài khoản của bạn đã được tạo thành công! Chúng tôi đã tìm thấy ' . $orderCount . ' đơn hàng với email này và đã thêm vào tài khoản của bạn.',
            'success',
            '/orders'
        );

        $orderMessage = ' ' . $orderCount . ' đơn hàng trước đây đã được thêm vào tài khoản của bạn.';
    }

    // Auto-login user
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'user';

    // Merge guest cart vào user cart
    mergeGuestCartToUser($newUserId);

    echo json_encode([
        'success' => true,
        'message' => 'Đăng ký thành công!' . $orderMessage,
        'redirect' => '/'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?>