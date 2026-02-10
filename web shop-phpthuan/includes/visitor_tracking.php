<?php
/**
 * Visitor Tracking Script
 * Included in header to track views and online users
 */

try {
    // 1. Track Online Users
    $sessionId = session_id();
    // Nếu chưa có session id (start session chưa gọi), gọi lại (thường header.php đã gọi rồi)
    if (!$sessionId) {
        @session_start();
        $sessionId = session_id();
    }
    
    if ($sessionId) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        // Xử lý IP nếu đứng sau Proxy/Cloudflare (nếu cần, ở đây dùng cơ bản)
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $currentTime = time();
        
        // Cập nhật người dùng hiện tại (REPLACE = INSERT OR UPDATE)
        db()->query(
            "REPLACE INTO online_users (session_id, last_activity, ip_address, user_agent) VALUES (?, ?, ?, ?)",
            [$sessionId, $currentTime, $ipAddress, substr($userAgent, 0, 250)]
        );
        
        // Xóa người dùng offline (quá 5 phút = 300 giây)
        // Để giảm tải, chỉ chạy logic này ngẫu nhiên (VD: 10% request) hoặc chạy luôn nếu traffic ít
        if (rand(1, 10) === 1) {
            db()->query("DELETE FROM online_users WHERE last_activity < ?", [$currentTime - 300]);
        }
    }
    
    // 2. Track Page Views (Count every reload)
    // Tăng view cho ngày hiện tại
    db()->query(
        "INSERT INTO visitor_stats (date, access_count) VALUES (CURDATE(), 1) 
         ON DUPLICATE KEY UPDATE access_count = access_count + 1"
    );

    // 3. Log detailed history (New)
    // Create table if not exists (One-time check optimization: in production this should be a migration)
    static $checkedTable = false;
    if (!$checkedTable) {
        db()->query("
            CREATE TABLE IF NOT EXISTS visitor_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                request_uri VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_address (ip_address),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $checkedTable = true;
    }

    // Log current visit
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    db()->query(
        "INSERT INTO visitor_logs (ip_address, user_agent, request_uri) VALUES (?, ?, ?)",
        [$ipAddress, substr($userAgent, 0, 250), substr($requestUri, 0, 250)]
    );
    
} catch (Exception $e) {
    // Silent error - không để lỗi tracking làm hỏng trang web
    error_log("Visitor tracking error: " . $e->getMessage());
}
?>
