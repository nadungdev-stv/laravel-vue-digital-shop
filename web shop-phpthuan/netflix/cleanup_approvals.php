<?php
/**
 * Cleanup Script - Tự động xóa approval records cũ
 * 
 * Chạy bằng cron mỗi giờ:
 * 0 * * * * php /path/to/cleanup_approvals.php
 */

require_once __DIR__ . '/config.php';

// Log function
function logCleanup($message) {
    $logFile = __DIR__ . '/logs/cleanup.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry;
}

try {
    $db = connectDB();
    
    // Xóa records > 24 giờ
    $stmt = $db->prepare("
        DELETE FROM pending_approvals 
        WHERE created_at < NOW() - INTERVAL 24 HOUR
    ");
    
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    
    logCleanup("Cleanup completed - Deleted $deletedCount old record(s)");
    
    // Expired records (pending > 1 giờ)
    $stmt = $db->prepare("
        UPDATE pending_approvals 
        SET status = 'expired' 
        WHERE status = 'pending' 
        AND created_at < NOW() - INTERVAL 1 HOUR
    ");
    
    $stmt->execute();
    $expiredCount = $stmt->rowCount();
    
    if ($expiredCount > 0) {
        logCleanup("Marked $expiredCount record(s) as expired");
    }
    
} catch (Exception $e) {
    logCleanup("ERROR: " . $e->getMessage());
    exit(1);
}
