<?php
/**
 * Telegram Chat Webhook
 * Nhận tin nhắn từ Telegram và lưu vào database
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/chat_helpers.php';

// Log file
$logFile = __DIR__ . '/../logs/telegram-chat.log';
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
    http_response_code(200);
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = $message['text'] ?? '';
$from = $message['from'];

// Chỉ chấp nhận tin nhắn từ admin chat ID
$adminChatId = '6269327932';
if ($chatId != $adminChatId) {
    http_response_code(200);
    exit;
}

// Kiểm tra nếu admin reply trực tiếp tin nhắn (reply_to_message)
if (isset($message['reply_to_message'])) {
    $replyToMessageId = $message['reply_to_message']['message_id'];
    $replyText = trim($text);

    if (!empty($replyText) && strpos($replyText, '/') !== 0) {
        try {
            // Tìm session từ telegram_message_id
            $chatMessage = db()->query(
                "SELECT session_id FROM chat_messages WHERE telegram_message_id = ?",
                [$replyToMessageId]
            )->fetch();

            if ($chatMessage) {
                $sessionId = $chatMessage['session_id'];

                // Kiểm tra session còn active không
                $session = db()->query(
                    "SELECT * FROM chat_sessions WHERE id = ? AND status = 'active'",
                    [$sessionId]
                )->fetch();

                if ($session) {
                    // Lưu tin nhắn vào database
                    $senderName = $from['first_name'] ?? 'Admin';
                    sendChatMessage($sessionId, $replyText, 'admin', $senderName);

                    // Gửi tin nhắn xác nhận với react
                    $botToken = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
                    $reactUrl = "https://api.telegram.org/bot{$botToken}/setMessageReaction";
                    $reactData = [
                        'chat_id' => $chatId,
                        'message_id' => $message['message_id'],
                        'reaction' => json_encode([['type' => 'emoji', 'emoji' => '✅']])
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $reactUrl);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($reactData));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    curl_exec($ch);
                    curl_close($ch);

                    // Log success
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Auto-reply sent to session #{$sessionId} via reply_to_message\n", FILE_APPEND);

                    http_response_code(200);
                    exit;
                } else {
                    sendTelegramResponse($chatId, "❌ Session #{$sessionId} đã đóng hoặc không còn hoạt động.");
                }
            } else {
                // Không tìm thấy message gốc, có thể là reply message admin cũ
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Reply to message #{$replyToMessageId} not found in database\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            sendTelegramResponse($chatId, "❌ Lỗi: " . $e->getMessage());
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error in auto-reply: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

// Parse command: /reply_[session_id] [message] (fallback method)
if (preg_match('/^\/reply_(\d+)\s+(.+)$/s', $text, $matches)) {
    $sessionId = $matches[1];
    $replyMessage = trim($matches[2]);

    try {
        // Kiểm tra session tồn tại
        $session = db()->query(
            "SELECT * FROM chat_sessions WHERE id = ? AND status = 'active'",
            [$sessionId]
        )->fetch();

        if (!$session) {
            throw new Exception('Session không tồn tại hoặc đã đóng');
        }

        // Lưu tin nhắn vào database
        $senderName = $from['first_name'] ?? 'Admin';
        sendChatMessage($sessionId, $replyMessage, 'admin', $senderName);

        // Gửi tin nhắn xác nhận
        sendTelegramResponse($chatId, "✅ Đã gửi tin nhắn tới session #{$sessionId}");

        // Log success
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Reply sent to session #{$sessionId}\n", FILE_APPEND);

    } catch (Exception $e) {
        sendTelegramResponse($chatId, "❌ Lỗi: " . $e->getMessage());
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
} else {
    // Không phải lệnh /reply
    if (strpos($text, '/') === 0) {
        // Là command khác
        switch ($text) {
            case '/help':
                $helpText = "📝 <b>HƯỚNG DẪN TRẢ LỜI CHAT</b>\n\n";
                $helpText .= "🎯 <b>Cách 1: Reply trực tiếp (Khuyên dùng)</b>\n";
                $helpText .= "• Nhấn Reply trên tin nhắn của khách\n";
                $helpText .= "• Gõ nội dung và gửi\n";
                $helpText .= "• Bot sẽ tự động gửi đến đúng người! ✅\n\n";
                $helpText .= "📋 <b>Cách 2: Dùng lệnh</b>\n";
                $helpText .= "<code>/reply_[ID] [tin nhắn]</code>\n\n";
                $helpText .= "Ví dụ:\n";
                $helpText .= "<code>/reply_5 Xin chào, tôi có thể giúp gì cho bạn?</code>\n\n";
                $helpText .= "💡 <b>Lệnh khác:</b>\n";
                $helpText .= "/list - Xem danh sách chat đang hoạt động\n";
                $helpText .= "/help - Xem hướng dẫn này";
                sendTelegramResponse($chatId, $helpText);
                break;

            case '/list':
                // Lấy danh sách active sessions
                $sessions = getAllChatSessions('active', 10);

                if (empty($sessions)) {
                    sendTelegramResponse($chatId, "Không có chat session nào đang hoạt động.");
                } else {
                    $listText = "📋 <b>DANH SÁCH CHAT ĐANG HOẠT ĐỘNG</b>\n\n";
                    foreach ($sessions as $session) {
                        $name = $session['guest_name'] ?? 'User #' . $session['user_id'];
                        $unread = $session['unread_count'];
                        $listText .= "🆔 Session #{$session['id']}\n";
                        $listText .= "👤 {$name}\n";
                        $listText .= "💬 Tin chưa đọc: {$unread}\n";
                        if ($session['last_message']) {
                            $preview = mb_substr($session['last_message'], 0, 50);
                            $listText .= "💭 \"{$preview}...\"\n";
                        }
                        $listText .= "\n";
                    }
                    $listText .= "Dùng <code>/reply_[ID]</code> để trả lời";
                    sendTelegramResponse($chatId, $listText);
                }
                break;

            default:
                sendTelegramResponse($chatId, "Command không hợp lệ. Gửi /help để xem hướng dẫn.");
        }
    }
}

http_response_code(200);

/**
 * Gửi tin nhắn response về Telegram
 */
function sendTelegramResponse($chatId, $text) {
    if (!defined('TELEGRAM_BOT_TOKEN')) return;
    $botToken = TELEGRAM_BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_exec($ch);
        curl_close($ch);
    }
}
