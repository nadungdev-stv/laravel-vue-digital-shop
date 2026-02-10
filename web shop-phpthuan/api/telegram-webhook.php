<?php
/**
 * Telegram Bot Webhook Handler
 * Xử lý tin nhắn từ Telegram Bot
 */

// Log tất cả requests vào file để debug
$logFile = __DIR__ . '/../logs/telegram.log';
$logDir = dirname($logFile);
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Lấy dữ liệu từ Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Log request
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Received: " . $content . "\n", FILE_APPEND);

// Kiểm tra xem có tin nhắn không
if (!isset($update['message'])) {
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = $message['text'] ?? '';
$firstName = $message['from']['first_name'] ?? 'bạn';

require_once __DIR__ . '/../src/config/database.php';

// Bot token
$botToken = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';

// Hàm gửi tin nhắn
function sendMessage($botToken, $chatId, $text, $parseMode = 'HTML') {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => $parseMode,
        'disable_web_page_preview' => true
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log response
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Sent to {$chatId}: {$text} (HTTP {$httpCode})\n", FILE_APPEND);

    return $httpCode == 200;
}

// Xử lý các lệnh
if ($text === '/start') {
    $response = "👋 Xin chào <b>{$firstName}</b>!\n\n";
    $response .= "Chào mừng bạn đến với <b>Veyrix Shop</b>\n";
    $response .= "Bot thông báo đơn hàng tự động.\n\n";
    $response .= "📋 <b>Các lệnh:</b>\n";
    $response .= "/start - Bắt đầu\n";
    $response .= "/help - Trợ giúp\n";
    $response .= "/status - Kiểm tra trạng thái\n\n";
    $response .= "🔗 Website: <a href='https://veyrix.pro'>veyrix.pro</a>";

    sendMessage($botToken, $chatId, $response);
} elseif ($text === '/help') {
    $response = "ℹ️ <b>Trợ giúp</b>\n\n";
    $response .= "Bot này sẽ tự động gửi thông báo khi:\n";
    $response .= "• Có đơn hàng mới\n";
    $response .= "• Khách hàng xác nhận thanh toán\n";
    $response .= "• Thanh toán bằng ví thành công\n\n";
    $response .= "🔗 <a href='https://veyrix.pro/admin'>Quản lý đơn hàng</a>";

    sendMessage($botToken, $chatId, $response);
} elseif ($text === '/status') {
    $response = "✅ <b>Bot đang hoạt động</b>\n\n";
    $response .= "📊 Chat ID: <code>{$chatId}</code>\n";
    $response .= "⏰ Thời gian: " . date('d/m/Y H:i:s');

    sendMessage($botToken, $chatId, $response);
} else {
    // Tin nhắn khác
    $response = "Xin chào! Gửi /start để bắt đầu hoặc /help để xem trợ giúp.";
    sendMessage($botToken, $chatId, $response);
}

// Log chat ID để biết ai đang nhắn
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Chat ID: {$chatId}, Name: {$firstName}, Text: {$text}\n", FILE_APPEND);

http_response_code(200);
