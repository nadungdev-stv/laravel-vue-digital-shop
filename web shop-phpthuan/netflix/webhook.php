<?php
/**
 * Netflix Auto-Approval Webhook
 * 
 * Nhận email từ Cloudflare Email Routing và lưu vào database
 * để worker tự động approve
 */

require_once 'config.php';

header('Content-Type: application/json');

// Log function
function logMessage($message, $level = 'INFO') {
    $logFile = __DIR__ . '/logs/webhook.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

try {
    // Đọc raw POST data từ Cloudflare
    $rawData = file_get_contents('php://input');
    
    if (empty($rawData)) {
        logMessage('No data received', 'WARNING');
        http_response_code(400);
        echo json_encode(['error' => 'No data received']);
        exit;
    }
    
    // Parse JSON data
    $data = json_decode($rawData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage('Invalid JSON: ' . json_last_error_msg(), 'ERROR');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    // Validate email source - phải từ Netflix
    $from = $data['from'] ?? '';
    $subject = $data['subject'] ?? '';
    $htmlBody = $data['html'] ?? '';
    $textBody = $data['text'] ?? '';
    
    logMessage("Email received - From: $from, Subject: $subject");
    
    // Check if email is from Netflix
    if (strpos($from, 'netflix.com') === false && strpos($from, 'account.netflix.com') === false) {
        logMessage("Email not from Netflix: $from", 'WARNING');
        http_response_code(200); // Vẫn return 200 để Cloudflare không retry
        echo json_encode(['status' => 'ignored', 'reason' => 'Not from Netflix']);
        exit;
    }
    
    // Check if it's a household update email
    if (strpos($subject, 'Hộ gia đình Netflix') === false && 
        strpos($subject, 'Netflix') === false) {
        logMessage("Not a household update email: $subject", 'INFO');
        http_response_code(200);
        echo json_encode(['status' => 'ignored', 'reason' => 'Not household update']);
        exit;
    }
    
    // Extract approval link
    // Pattern: https://www.netflix.com/account/update-primary-location?nftoken=...
    $emailContent = $htmlBody ?: $textBody;
    
    $pattern = '/https:\/\/www\.netflix\.com\/account\/update-primary-location\?[^\s"<>\[\]]+/';
    
    if (preg_match($pattern, $emailContent, $matches)) {
        $approvalLink = $matches[0];
        
        logMessage("Approval link found: $approvalLink");
        
        // Kết nối database
        $db = connectDB();
        
        // Check if link already exists (tránh duplicate)
        $stmt = $db->prepare("SELECT id FROM pending_approvals WHERE link = ? AND status IN ('pending', 'approved')");
        $stmt->execute([$approvalLink]);
        
        if ($stmt->fetch()) {
            logMessage("Link already exists in database", 'INFO');
            http_response_code(200);
            echo json_encode(['status' => 'duplicate', 'message' => 'Link already processed']);
            exit;
        }
        
        // Insert vào database
        $stmt = $db->prepare("
            INSERT INTO pending_approvals 
            (link, email_from, email_subject, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([$approvalLink, $from, $subject]);
        $insertId = $db->lastInsertId();
        
        logMessage("Approval request saved to database - ID: $insertId", 'SUCCESS');
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Approval request queued',
            'id' => $insertId
        ]);
        
    } else {
        logMessage("No approval link found in email", 'WARNING');
        http_response_code(200);
        echo json_encode(['status' => 'no_link', 'message' => 'No approval link found']);
    }
    
} catch (Exception $e) {
    logMessage('Error: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
