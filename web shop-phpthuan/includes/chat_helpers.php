<?php
/**
 * Chat Helper Functions
 * Hỗ trợ hệ thống chat trực tiếp
 */

/**
 * Tạo hoặc lấy chat session
 */
function getOrCreateChatSession($userId = null, $guestName = null, $guestEmail = null) {
    // Nếu user đã đăng nhập, tìm session active
    if ($userId) {
        $session = db()->query(
            "SELECT * FROM chat_sessions WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1",
            [$userId]
        )->fetch();

        if ($session) {
            return $session;
        }
    }

    // Luôn tạo session mới cho guest (không tìm session cũ theo email)
    // Tạo session mới
    db()->query(
        "INSERT INTO chat_sessions (user_id, guest_name, guest_email, status, created_at)
         VALUES (?, ?, ?, 'active', NOW())",
        [$userId, $guestName, $guestEmail]
    );

    $sessionId = db()->getConnection()->lastInsertId();

    return db()->query(
        "SELECT * FROM chat_sessions WHERE id = ?",
        [$sessionId]
    )->fetch();
}

/**
 * Gửi tin nhắn trong chat
 */
function sendChatMessage($sessionId, $message, $senderType = 'customer', $senderName = null, $imagePath = null) {
    // Lưu tin nhắn vào database
    db()->query(
        "INSERT INTO chat_messages (session_id, sender_type, sender_name, message, image, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())",
        [$sessionId, $senderType, $senderName, $message, $imagePath]
    );

    $messageId = db()->getConnection()->lastInsertId();

    // Cập nhật last_message_at của session
    db()->query(
        "UPDATE chat_sessions SET last_message_at = NOW() WHERE id = ?",
        [$sessionId]
    );

    // Nếu là tin nhắn từ customer, gửi lên Telegram
    if ($senderType === 'customer') {
        $session = db()->query(
            "SELECT * FROM chat_sessions WHERE id = ?",
            [$sessionId]
        )->fetch();

        sendChatToTelegram($session, $message, $senderName, $imagePath);
    }

    return $messageId;
}

/**
 * Lấy danh sách tin nhắn của session
 */
function getChatMessages($sessionId, $limit = 50, $offset = 0) {
    return db()->query(
        "SELECT * FROM chat_messages
         WHERE session_id = ?
         ORDER BY created_at ASC
         LIMIT ? OFFSET ?",
        [$sessionId, $limit, $offset]
    )->fetchAll();
}

/**
 * Lấy tin nhắn mới từ timestamp
 */
function getNewChatMessages($sessionId, $afterTimestamp) {
    return db()->query(
        "SELECT * FROM chat_messages
         WHERE session_id = ? AND created_at > ?
         ORDER BY created_at ASC",
        [$sessionId, $afterTimestamp]
    )->fetchAll();
}

/**
 * Lấy tin nhắn mới sau ID (tốt hơn timestamp)
 */
function getNewChatMessagesAfterId($sessionId, $afterId) {
    return db()->query(
        "SELECT * FROM chat_messages
         WHERE session_id = ? AND id > ?
         ORDER BY created_at ASC",
        [$sessionId, $afterId]
    )->fetchAll();
}

/**
 * Đánh dấu tin nhắn đã đọc
 */
function markMessagesAsRead($sessionId, $senderType) {
    db()->query(
        "UPDATE chat_messages
         SET is_read = TRUE
         WHERE session_id = ? AND sender_type = ? AND is_read = FALSE",
        [$sessionId, $senderType]
    );
}

/**
 * Đếm tin nhắn chưa đọc
 */
function countUnreadMessages($sessionId, $senderType) {
    $result = db()->query(
        "SELECT COUNT(*) as count FROM chat_messages
         WHERE session_id = ? AND sender_type = ? AND is_read = FALSE",
        [$sessionId, $senderType]
    )->fetch();

    return $result['count'];
}

/**
 * Gửi tin nhắn lên Telegram
 */
function sendChatToTelegram($session, $message, $senderName, $imagePath = null) {
    if (!defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN)) return false;
    $botToken = TELEGRAM_BOT_TOKEN;
    $chatId = '6269327932';

    // Format tin nhắn
    $telegramMessage = "💬 <b>TIN NHẮN CHAT MỚI</b>\n\n";
    $telegramMessage .= "👤 Người gửi: <b>" . ($senderName ?: 'Khách') . "</b>\n";

    if ($session['guest_email']) {
        $telegramMessage .= "📧 Email: {$session['guest_email']}\n";
    }

    $telegramMessage .= "🆔 Session: #{$session['id']}\n\n";

    if ($message) {
        $telegramMessage .= "💭 Nội dung:\n" . htmlspecialchars($message) . "\n\n";
    }

    $telegramMessage .= "↩️ <b>Reply tin nhắn này để trả lời khách hàng</b>\n";
    $telegramMessage .= "📝 Hoặc gửi: <code>/reply_{$session['id']} [tin nhắn]</code>\n";
    $telegramMessage .= "🔗 <a href='https://veyrix.pro/admin/chat-detail?id={$session['id']}'>Xem chi tiết</a>";

    // Nếu có ảnh, gửi ảnh kèm caption
    if ($imagePath) {
        $imageUrl = 'https://veyrix.pro' . $imagePath;
        $url = "https://api.telegram.org/bot{$botToken}/sendPhoto";

        $data = [
            'chat_id' => $chatId,
            'photo' => $imageUrl,
            'caption' => $telegramMessage,
            'parse_mode' => 'HTML'
        ];
    } else {
        // Gửi text thông thường
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $telegramMessage,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];
    }

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $response = json_decode($result, true);
            if ($response && $response['ok'] && isset($response['result']['message_id'])) {
                // Lưu telegram_message_id
                db()->query(
                    "UPDATE chat_messages
                     SET telegram_message_id = ?
                     WHERE session_id = ? AND sender_type = 'customer'
                     ORDER BY created_at DESC LIMIT 1",
                    [$response['result']['message_id'], $session['id']]
                );
            }
        }
    }
}

/**
 * Lấy tất cả chat sessions (cho admin)
 */
function getAllChatSessions($status = 'active', $limit = 50) {
    return db()->query(
        "SELECT cs.*,
         (SELECT COUNT(*) FROM chat_messages WHERE session_id = cs.id AND sender_type = 'customer' AND is_read = FALSE) as unread_count,
         (SELECT message FROM chat_messages WHERE session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_message
         FROM chat_sessions cs
         WHERE cs.status = ?
         ORDER BY cs.last_message_at DESC, cs.created_at DESC
         LIMIT ?",
        [$status, $limit]
    )->fetchAll();
}

/**
 * Đóng chat session
 */
function closeChatSession($sessionId) {
    db()->query(
        "UPDATE chat_sessions SET status = 'closed', updated_at = NOW() WHERE id = ?",
        [$sessionId]
    );
}

/**
 * Xóa chat session và tất cả messages
 */
function deleteChatSession($sessionId) {
    // Xóa tất cả messages của session
    db()->query(
        "DELETE FROM chat_messages WHERE session_id = ?",
        [$sessionId]
    );

    // Xóa session
    db()->query(
        "DELETE FROM chat_sessions WHERE id = ?",
        [$sessionId]
    );
}
