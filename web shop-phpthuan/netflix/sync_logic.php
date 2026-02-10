<?php
/**
 * Shared Sync Logic
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/GmailHandler.php';

function syncEmails($config) {
    // 1. Cleanup Old Data (> 3 Days)
    db()->query("DELETE FROM netflix_email_cache WHERE created_at < NOW() - INTERVAL 3 DAY");
    
    // 2. Connect Gmail
    $gmail = new GmailHandler($config);
    $emails = $gmail->fetchRecentEmails(50); // Fetch last 50 emails
    
    $processed = 0;
    
    foreach ($emails as $email) {
        $body = $email['body'];
        $code = null;
        
        // Extract Link (Priority)
        if (preg_match('/https:\/\/www\.netflix\.com\/account\/travel\/verify\S+/', $body, $matches)) {
            $code = strip_tags($matches[0]);
            $code = preg_replace('/[">].*$/', '', $code);
        } 
        // Extract 4-Digit Code
        elseif (preg_match('/\b(\d{4})\b/', $body, $matches)) {
            $val = (int)$matches[1];
            $curYear = (int)date('Y');
            if ($val < 2020 || $val > $curYear + 5) {
                $code = $matches[1];
            }
        }
        
        // Save to DB
        $stmt = db()->getConnection()->prepare("
            INSERT INTO netflix_email_cache 
            (email_uid, recipient, subject, body, code, email_date)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            subject = VALUES(subject),
            body = VALUES(body),
            code = VALUES(code)
        ");
        
        $stmt->execute([
            $email['uid'],
            $email['recipient'],
            $email['subject'],
            $body,
            $code,
            $email['date']
        ]);
        
        if ($stmt->rowCount() > 0) {
            $processed++; // Only count actual inserts/updates? rowCount returns 1 for insert, 2 for update
        }
    }
    
    $gmail->disconnect();
    
    return [
        'fetched' => count($emails),
        'processed' => $processed
    ];
}
