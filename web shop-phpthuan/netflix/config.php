<?php
/**
 * Email Forward Search - Cấu hình
 */

// Load .env file từ thư mục chính của web shop
$envFile = __DIR__ . '/../.env';
$envVars = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $envVars[trim($key)] = $value; // Chỉ trim key, giữ nguyên value
        }
    }
}

// Load settings from database
require_once __DIR__ . '/../includes/db.php';

function getNetflixSetting($key, $default = '') {
    try {
        $result = db()->query("SELECT setting_value FROM settings WHERE setting_key = ?", [$key])->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Kết nối database cho approval system
 */
function connectDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        require_once __DIR__ . '/../includes/db.php';
        $pdo = db();
    }
    
    return $pdo;
}

return [
    // Gmail IMAP settings (từ .env)
    'gmail' => [
        'email' => $envVars['NETFLIX_EMAIL_GMAIL'] ?? '',
        'password' => $envVars['NETFLIX_EMAIL_PASSWORD'] ?? '',
        'imap_host' => $envVars['NETFLIX_EMAIL_IMAP_HOST'] ?? 'imap.gmail.com',
        'imap_port' => (int)($envVars['NETFLIX_EMAIL_IMAP_PORT'] ?? 993),
    ],
    
    // Giới hạn tìm kiếm (từ database)
    'search' => [
        'max_days_ago' => (int)getNetflixSetting('netflix_max_days_ago', 30),
        'allowed_senders' => array_filter(explode("\n", getNetflixSetting('netflix_allowed_senders', "info@account.netflix.com"))),
    ],

    // Đường dẫn Node.js (tự động phát hiện OS)
    'node_path' => (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
        ? '"C:/Program Files/nodejs/node.exe"'  // Local Windows
        : '/usr/bin/node',                      // VPS Linux (FastPanel)
];

