<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/chat_helpers.php';
initSession();

// Kiểm tra quyền admin
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$sessionId = (int)($_GET['id'] ?? 0);

if (!$sessionId) {
    setFlash('error', 'Chat không tồn tại');
    redirect('/admin/chats.php');
}

// Get chat session
$session = db()->query(
    "SELECT cs.*, u.username, u.email as user_email
     FROM chat_sessions cs
     LEFT JOIN users u ON cs.user_id = u.id
     WHERE cs.id = ?",
    [$sessionId]
)->fetch();

if (!$session) {
    setFlash('error', 'Chat không tồn tại');
    redirect('/admin/chats.php');
}

$pageTitle = 'Chi tiết Chat #' . $sessionId;

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        setFlash('error', 'Vui lòng nhập tin nhắn');
    } else {
        $currentUser = getCurrentUser();
        $senderName = $currentUser['username'] ?? 'Admin';

        sendChatMessage($sessionId, $message, 'admin', $senderName);
        // setFlash('success', 'Đã gửi tin nhắn'); // Không cần flash để trải nghiệm mượt hơn
        redirect('/admin/chat-detail?id=' . $sessionId);
    }
}

// Handle close chat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_chat'])) {
    db()->query(
        "UPDATE chat_sessions SET status = 'closed', updated_at = NOW() WHERE id = ?",
        [$sessionId]
    );
    setFlash('success', 'Đã đóng chat');
    redirect('/admin/chats.php');
}

// Handle reopen chat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reopen_chat'])) {
    db()->query(
        "UPDATE chat_sessions SET status = 'active', updated_at = NOW() WHERE id = ?",
        [$sessionId]
    );
    setFlash('success', 'Đã mở lại chat');
    redirect('/admin/chat-detail?id=' . $sessionId);
}

// Get messages
$messages = getChatMessages($sessionId);


// Mark messages as read
markMessagesAsRead($sessionId, 'customer');

$customerName = $session['username'] ?: ($session['guest_name'] ?: 'Khách');
$customerEmail = $session['user_email'] ?: $session['guest_email'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Veyrix Admin 2.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/admin-apple-style.css">
    <style>
        .chat-layout {
            display: flex;
            gap: 24px;
            height: calc(100vh - 140px); /* Adjust based on header height */
        }
        
        /* Chat Main Area */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #d2d2d7;
        }

        .chat-header {
            padding: 16px 24px;
            border-bottom: 1px solid #f5f5f7;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            z-index: 10;
        }

        .chat-messages {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            background: #fbfbfd;
            scroll-behavior: smooth;
        }

        .message-bubble {
            max-width: 75%;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-bubble.customer {
            align-items: flex-start;
        }

        .message-bubble.admin {
            align-items: flex-end;
            margin-left: auto;
        }

        .message-content {
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 15px;
            line-height: 1.5;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }

        .message-bubble.customer .message-content {
            background: #fff;
            color: #1d1d1f;
            border-bottom-left-radius: 4px;
            border: 1px solid #e5e5e7;
        }

        .message-bubble.admin .message-content {
            background: linear-gradient(135deg, #0071e3 0%, #0077ed 100%);
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .message-meta {
            font-size: 11px;
            color: #86868b;
            margin-top: 6px;
            padding: 0 4px;
        }

        /* Input Area */
        .chat-input-area {
            padding: 20px 24px;
            background: #fff;
            border-top: 1px solid #f5f5f7;
        }

        .chat-input-wrapper {
            position: relative;
            display: flex;
            align-items: flex-end;
            background: #f5f5f7;
            border-radius: 24px;
            padding: 4px;
        }

        .chat-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 12px 16px;
            max-height: 120px;
            resize: none;
            outline: none;
            font-size: 15px;
        }

        .btn-send {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #0071e3;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin: 4px;
        }

        .btn-send:hover {
            transform: scale(1.05);
            background: #0077ed;
        }

        /* Sidebar Info */
        .chat-info-sidebar {
            width: 320px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-card {
            background: #fff;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #d2d2d7;
        }

        .avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF9A9E 0%, #FECFEF 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin: 0 auto 16px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container pb-0">
        <div class="chat-layout">
            <!-- Main Chat Window -->
            <div class="chat-main">
                <!-- Header -->
                <div class="chat-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <a href="/admin/chats.php" class="btn btn-light btn-sm rounded-circle me-3 text-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0 fw-bold d-flex align-items-center">
                                <?= e($customerName) ?>
                                <?php if ($session['status'] === 'active'): ?>
                                    <span class="badge bg-success rounded-pill ms-2" style="font-size: 10px;">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill ms-2" style="font-size: 10px;">Closed</span>
                                <?php endif; ?>
                            </h5>
                            <small class="text-secondary"><?= e($customerEmail) ?></small>
                        </div>
                    </div>
                    <?php if ($session['status'] === 'active'): ?>
                        <form method="POST" onsubmit="return confirm('Đóng phiên chat này?');" style="margin:0;">
                            <button type="submit" name="close_chat" class="pill-button pill-button-white text-danger border-danger">
                                <i class="fas fa-times-circle"></i> Đóng Chat
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="margin:0;">
                            <button type="submit" name="reopen_chat" class="pill-button pill-button-white text-success border-success">
                                <i class="fas fa-check-circle"></i> Mở lại Chat
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Messages -->
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5 h-100 d-flex flex-column justify-content-center align-items-center opacity-50">
                            <i class="fas fa-comments fa-4x text-secondary mb-3"></i>
                            <p class="text-secondary fw-bold">Bắt đầu cuộc trò chuyện</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-bubble <?= $msg['sender_type'] ?>" data-message-id="<?= $msg['id'] ?>">
                                <div class="message-content">
                                    <?= nl2br(e($msg['message'])) ?>
                                </div>
                                <div class="message-meta">
                                    <?php if ($msg['sender_type'] === 'admin'): ?>
                                        <?= e($msg['sender_name'] ?: 'Admin') ?>
                                    <?php endif; ?>
                                    <?= date('H:i', strtotime($msg['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Input -->
                <div class="chat-input-area">
                    <?php if ($session['status'] === 'active'): ?>
                        <form method="POST" id="messageForm">
                            <div class="chat-input-wrapper">
                                <textarea name="message" class="chat-input" id="messageInput" placeholder="Nhập tin nhắn..." rows="1" required></textarea>
                                <button type="submit" name="send_message" class="btn-send">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-3 bg-light rounded-3 text-secondary">
                            <i class="fas fa-lock me-2"></i> Phiên chat đã đóng
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="chat-info-sidebar">
                <div class="info-card text-center">
                    <div class="avatar-large">
                        <?= strtoupper(substr($customerName, 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-1"><?= e($customerName) ?></h5>
                    <p class="text-secondary small mb-3"><?= e($customerEmail) ?></p>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <?php if ($session['user_id']): ?>
                            <a href="/admin/user-detail?id=<?= $session['user_id'] ?>" class="badge bg-primary text-decoration-none p-2 rounded-pill">
                                <i class="fas fa-user-circle"></i> Xem Profile
                            </a>
                        <?php else: ?>
                            <span class="badge bg-secondary p-2 rounded-pill">Khách vãng lai</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-card">
                    <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Thông tin phiên</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Session ID:</span>
                            <span class="fw-bold">#<?= $sessionId ?></span>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Bắt đầu:</span>
                            <span><?= date('d/m/Y H:i', strtotime($session['created_at'])) ?></span>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">IP Address:</span>
                            <span class="font-monospace"><?= $session['ip_address'] ?? 'N/A' ?></span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-secondary">Trình duyệt:</span>
                            <span title="<?= e($session['user_agent']) ?>" class="text-truncate" style="max-width: 120px;">
                                <?= e($session['user_agent'] ?? 'N/A') ?>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="alert alert-primary border-0 shadow-sm" style="border-radius: 18px;">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fab fa-telegram fa-lg me-2"></i>
                        <h6 class="mb-0 fw-bold">Telegram Bot</h6>
                    </div>
                    <p class="small mb-2">Trả lời nhanh qua Telegram:</p>
                    <code class="d-block bg-white p-2 rounded border border-primary-subtle user-select-all">/reply_<?= $sessionId ?> Xin chào</code>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto scroll to bottom
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
        scrollToBottom();

        // Auto expand textarea
        const textarea = document.getElementById('messageInput');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
                if(this.value === '') this.style.height = 'auto';
            });
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    document.getElementById('messageForm').submit();
                }
            });
        }

        // Poll for new messages
        let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;
        const sessionId = <?= $sessionId ?>;

        setInterval(async () => {
            try {
                // Note: Using existing API endpoint for messages
                const response = await fetch(`/api/chat.php?action=get_messages&session_id=${sessionId}&after_id=${lastMessageId}`);
                const data = await response.json();

                if (data.success && data.messages.length > 0) {
                    const container = document.getElementById('chatMessages');
                    data.messages.forEach(msg => {
                        const div = document.createElement('div');
                        div.className = `message-bubble ${msg.sender_type}`;
                        
                        // Check if we need customer name logic here, but keeping it simple for JS
                        const name = msg.sender_type === 'admin' ? (msg.sender_name || 'Admin') : '<?= $customerName ?>';
                        
                        div.innerHTML = `
                            <div class="message-content">${msg.message}</div>
                            <div class="message-meta">
                                ${name} ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                            </div>
                        `;
                        container.appendChild(div);
                        lastMessageId = msg.id;
                    });
                    scrollToBottom();
                }
            } catch (e) {
                console.error("Polling error", e);
            }
        }, 3000);
    </script>
</body>
</html>
