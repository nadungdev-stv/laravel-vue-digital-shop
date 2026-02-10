<?php
/**
 * Database and Application Configuration
 * Loads configuration from .env file for security
 */

// Load .env file
function loadEnv($path)
{
    if (!file_exists($path)) {
        die('Configuration file not found. Please create .env file from .env.example');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set as environment variable
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Load environment variables from .env
$envPath = __DIR__ . '/../../.env';
loadEnv($envPath);

// Helper function to get env variable with default
function env($key, $default = '')
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER'));
define('DB_PASS', env('DB_PASS'));
define('DB_NAME', env('DB_NAME'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Website Configuration
define('SITE_URL', env('SITE_URL', 'http://localhost/'));
define('SITE_NAME', env('SITE_NAME', 'Veyrix Shop Premium'));
define('ADMIN_EMAIL', env('ADMIN_EMAIL', 'admin@example.com'));

// Session Configuration
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 7200));

// Payment Configuration
define('ENABLE_AUTO_PAYMENT', env('ENABLE_AUTO_PAYMENT', 'false') === 'true');
define('PAYMENT_WEBHOOK_URL', env('PAYMENT_WEBHOOK_URL', ''));

// Email Configuration
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', 587));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_FROM', env('SMTP_FROM', 'noreply@example.com'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'Veyrix Shop'));

// Telegram Configuration
define('TELEGRAM_BOT_TOKEN', env('TELEGRAM_BOT_TOKEN', ''));
?>