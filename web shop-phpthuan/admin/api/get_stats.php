<?php
/**
 * Admin API - Get Realtime Stats
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Check Admin
initSession();
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    // 1. Online Users
    $onlineUsers = db()->query("SELECT COUNT(*) as count FROM online_users")->fetch()['count'];
    
    // 2. Today Views
    $todayViews = db()->query("SELECT access_count FROM visitor_stats WHERE date = CURDATE()")->fetch()['access_count'] ?? 0;
    
    // 3. Today Orders & Revenue
    $todayStats = db()->query(
        "SELECT COUNT(*) as count, COALESCE(SUM(final_amount), 0) as revenue 
         FROM orders 
         WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'"
    )->fetch();
    
    // 4. Total Orders & Revenue (cho consistency)
    $totalStats = db()->query(
        "SELECT COUNT(*) as count, COALESCE(SUM(final_amount), 0) as revenue 
         FROM orders 
         WHERE payment_status = 'paid'"
    )->fetch();
    
    // 5. Total Users
    $totalUsers = db()->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];

    echo json_encode([
        'success' => true,
        'online' => (int)$onlineUsers,
        'today_views' => (int)$todayViews,
        'today_orders' => (int)$todayStats['count'],
        'today_revenue' => (float)$todayStats['revenue'],
        'total_revenue' => (float)$totalStats['revenue'],
        'total_users' => (int)$totalUsers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
