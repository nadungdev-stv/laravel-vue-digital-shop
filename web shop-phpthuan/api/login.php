<?php
/**
 * API đăng nhập
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
        'success' => true,
        'message' => 'Bạn đã đăng nhập rồi',
        'redirect' => '/'
    ]);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate
if (empty($username) || empty($password)) {
    echo json_encode([
        'success' => false,
        'error' => 'Vui lòng nhập đầy đủ thông tin'
    ]);
    exit;
}

try {
    // Tìm user (có thể dùng username hoặc email)
    $stmt = db()->query(
        "SELECT * FROM users WHERE username = ? OR email = ?",
        [$username, $username]
    );

    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'success' => false,
            'error' => 'Tên đăng nhập hoặc mật khẩu không đúng'
        ]);
        exit;
    }

    // Kiểm tra password
    if (!verifyPassword($password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Tên đăng nhập hoặc mật khẩu không đúng'
        ]);
        exit;
    }

    // Kiểm tra trạng thái tài khoản
    if ($user['status'] !== 'active') {
        echo json_encode([
            'success' => false,
            'error' => 'Tài khoản đã bị khóa'
        ]);
        exit;
    }

    // Đăng nhập thành công
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // Merge guest cart vào user cart
    mergeGuestCartToUser($user['id']);

    // Cập nhật last login (nếu cột tồn tại)
    try {
        db()->query(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$user['id']]
        );
    } catch (Exception $e) {
        // Bỏ qua nếu cột last_login chưa tồn tại
    }

    // Ghi log đăng nhập (New)
    try {
        // Auto-create table
        static $checkedLoginTable = false;
        if (!$checkedLoginTable) {
            db()->query("
                CREATE TABLE IF NOT EXISTS user_logins (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $checkedLoginTable = true;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        
        db()->query(
            "INSERT INTO user_logins (user_id, ip_address, user_agent) VALUES (?, ?, ?)",
            [$user['id'], $ip, $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']
        );
    } catch (Exception $e) {
        error_log("Login log error: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công!',
        'redirect' => '/',
        'user' => [
            'username' => $user['username'],
            'role' => $user['role']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?>
