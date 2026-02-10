<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/chat_helpers.php';
initSession();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'start_session':
            // Tạo hoặc lấy chat session
            $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
            $guestName = $_POST['name'] ?? null;
            $guestEmail = $_POST['email'] ?? null;

            // Nếu không có tên, đặt là "Khách"
            if (!$userId && !$guestName) {
                $guestName = 'Khách';
            }

            $session = getOrCreateChatSession($userId, $guestName, $guestEmail);

            // Lưu session_id vào PHP session
            $_SESSION['chat_session_id'] = $session['id'];

            // Tự động gửi lời chào từ admin (chỉ cho session mới)
            $existingMessages = db()->query(
                "SELECT COUNT(*) as count FROM chat_messages WHERE session_id = ?",
                [$session['id']]
            )->fetch();

            if ($existingMessages['count'] == 0) {
                // Xác định tên để chào
                $displayName = '';

                // Nếu user đã đăng nhập, lấy tên từ database
                if ($userId) {
                    $user = getCurrentUser();
                    $userName = $user['full_name'] ?? $user['username'];
                    $words = preg_split('/\s+/', $userName);
                    $displayName = count($words) === 1 ? $words[0] : end($words);
                    $greetingMessage = "Chào {$displayName}! 👋\n\nMình là trợ lý tư vấn của Veyrix. Bạn muốn được trợ giúp về vấn đề gì?";
                } elseif ($guestName && $guestName !== 'Khách') {
                    $words = preg_split('/\s+/', $guestName);
                    $displayName = count($words) === 1 ? $words[0] : end($words);
                    $greetingMessage = "Chào {$displayName}! 👋\n\nMình là trợ lý tư vấn của Veyrix. Bạn muốn được trợ giúp về vấn đề gì?";
                } else {
                    $greetingMessage = "Xin chào! 👋\n\nMình là trợ lý tư vấn của Veyrix. Bạn muốn được trợ giúp về vấn đề gì?";
                }

                // Gửi tin nhắn chào từ admin
                db()->query(
                    "INSERT INTO chat_messages (session_id, sender_type, sender_name, message, created_at)
                     VALUES (?, 'admin', 'Dũng', ?, NOW())",
                    [$session['id'], $greetingMessage]
                );

                // Cập nhật last_message_at
                db()->query(
                    "UPDATE chat_sessions SET last_message_at = NOW() WHERE id = ?",
                    [$session['id']]
                );
            }

            echo json_encode([
                'success' => true,
                'session' => $session
            ]);
            break;

        case 'send_message':
            // Gửi tin nhắn
            $sessionId = $_POST['session_id'] ?? $_SESSION['chat_session_id'] ?? null;
            $message = trim($_POST['message'] ?? '');

            if (!$sessionId) {
                throw new Exception('Session không hợp lệ');
            }

            // Xử lý upload ảnh
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/chat/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('chat_') . '.' . $extension;
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = '/uploads/chat/' . $filename;
                }
            }

            // Nếu không có tin nhắn và không có ảnh thì báo lỗi
            if (empty($message) && !$imagePath) {
                throw new Exception('Tin nhắn không được để trống');
            }

            // Lấy thông tin người gửi
            $senderName = null;
            $senderType = 'customer';

            if (isLoggedIn()) {
                $user = getCurrentUser();
                $senderName = $user['username'];
            } else {
                $session = db()->query(
                    "SELECT * FROM chat_sessions WHERE id = ?",
                    [$sessionId]
                )->fetch();

                if ($session) {
                    $senderName = $session['guest_name'];
                }
            }

            // Admin sending message
            if (isset($_POST['admin']) && isAdmin()) {
                $senderType = 'admin';
                $senderName = 'Dũng';
            }

            $messageId = sendChatMessage($sessionId, $message, $senderType, $senderName, $imagePath);

            echo json_encode([
                'success' => true,
                'message_id' => $messageId,
                'sender_type' => $senderType
            ]);
            break;

        case 'get_messages':
            // Lấy tin nhắn
            $sessionId = $_GET['session_id'] ?? $_SESSION['chat_session_id'] ?? null;
            $afterTimestamp = $_GET['after'] ?? null;
            $afterId = $_GET['after_id'] ?? null;

            if (!$sessionId) {
                throw new Exception('Session không hợp lệ');
            }

            if ($afterId) {
                $messages = getNewChatMessagesAfterId($sessionId, $afterId);
            } elseif ($afterTimestamp) {
                $messages = getNewChatMessages($sessionId, $afterTimestamp);
            } else {
                $messages = getChatMessages($sessionId, 50, 0);
            }

            // Đánh dấu tin nhắn từ admin là đã đọc (nếu customer đang xem)
            if (!isAdmin()) {
                markMessagesAsRead($sessionId, 'admin');
            }

            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            break;

        case 'mark_read':
            // Đánh dấu đã đọc
            $sessionId = $_POST['session_id'] ?? null;
            $senderType = $_POST['sender_type'] ?? 'customer';

            if (!$sessionId) {
                throw new Exception('Session không hợp lệ');
            }

            markMessagesAsRead($sessionId, $senderType);

            echo json_encode([
                'success' => true
            ]);
            break;

        case 'get_sessions':
            // Lấy danh sách sessions (admin only)
            if (!isAdmin()) {
                throw new Exception('Không có quyền truy cập');
            }

            $status = $_GET['status'] ?? 'active';
            $sessions = getAllChatSessions($status, 50);

            echo json_encode([
                'success' => true,
                'sessions' => $sessions
            ]);
            break;

        case 'close_session':
            // Đóng session (admin only)
            if (!isAdmin()) {
                throw new Exception('Không có quyền truy cập');
            }

            $sessionId = $_POST['session_id'] ?? null;

            if (!$sessionId) {
                throw new Exception('Session không hợp lệ');
            }

            closeChatSession($sessionId);

            echo json_encode([
                'success' => true
            ]);
            break;

        case 'delete_session':
            // Xóa session và messages (cho guest)
            $sessionId = $_POST['session_id'] ?? $_SESSION['chat_session_id'] ?? null;

            if (!$sessionId) {
                throw new Exception('Session không hợp lệ');
            }

            // Kiểm tra quyền: chỉ cho phép xóa nếu là guest session hoặc là admin
            $session = db()->query(
                "SELECT * FROM chat_sessions WHERE id = ?",
                [$sessionId]
            )->fetch();

            if (!$session) {
                throw new Exception('Session không tồn tại');
            }

            // Cho phép guest xóa session của mình
            if (!isLoggedIn() || (isLoggedIn() && $session['user_id'] == getCurrentUser()['id'])) {
                deleteChatSession($sessionId);

                // Xóa session_id khỏi PHP session
                unset($_SESSION['chat_session_id']);

                echo json_encode([
                    'success' => true
                ]);
            } else {
                throw new Exception('Không có quyền xóa session này');
            }
            break;

        default:
            throw new Exception('Action không hợp lệ');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
