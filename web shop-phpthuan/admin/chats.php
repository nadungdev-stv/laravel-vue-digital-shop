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

$pageTitle = 'Quản lý Chat';

// Lấy danh sách chat với bộ lọc
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'active';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

// Filter by status
if ($status) {
    $where[] = "cs.status = ?";
    $params[] = $status;
}

// Search
if ($search) {
    $where[] = "(cs.guest_name LIKE ? OR cs.guest_email LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$totalQuery = "
    SELECT COUNT(*) as total
    FROM chat_sessions cs
    LEFT JOIN users u ON cs.user_id = u.id
    $whereClause
";
$total = db()->query($totalQuery, $params)->fetch()['total'];
$totalPages = ceil($total / $perPage);

// Get chat sessions
$query = "
    SELECT
        cs.*,
        u.username,
        u.email as user_email,
        (SELECT COUNT(*) FROM chat_messages WHERE session_id = cs.id AND sender_type = 'customer' AND is_read = 0) as unread_count,
        (SELECT message FROM chat_messages WHERE session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT sender_type FROM chat_messages WHERE session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_sender
    FROM chat_sessions cs
    LEFT JOIN users u ON cs.user_id = u.id
    $whereClause
    ORDER BY cs.last_message_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $perPage;
$params[] = $offset;
$chats = db()->query($query, $params)->fetchAll();

// Count stats
$statsQuery = "
    SELECT
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
        (SELECT COUNT(*) FROM chat_messages WHERE sender_type = 'customer' AND is_read = 0) as total_unread
    FROM chat_sessions
";
$stats = db()->query($statsQuery)->fetch();
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
        .chat-list-item {
            position: relative;
            background: #fff;
            border-bottom: 1px solid #f5f5f7;
            transition: background 0.2s;
            cursor: pointer;
        }
        .chat-list-item:hover {
            background: #f5f5f7;
        }
        .chat-list-item.unread {
            background: #f0f8ff;
        }
        .chat-list-item:last-child {
            border-bottom: none;
        }
        .avatar-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
            color: #fff;
            background: linear-gradient(135deg, #0071e3 0%, #0077ed 100%);
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>

                <p class="text-secondary mb-0">Live Chat Support</p>
            </div>
             <button class="pill-button pill-button-white" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px;">
                        <i class="fas fa-comments text-success fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?= number_format($stats['active_count']) ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Đang hoạt động</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px;">
                        <i class="fas fa-bell text-danger fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold text-danger"><?= number_format($stats['total_unread']) ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Tin nhắn chưa đọc</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 border-0 shadow-sm h-100 d-flex flex-row align-items-center">
                    <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px;">
                        <i class="fas fa-check-circle text-secondary fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?= number_format($stats['closed_count']) ?></h3>
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 11px;">Đã đóng</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                         <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-search text-secondary"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" style="border-radius: 0 12px 12px 0;" placeholder="Tên khách, email..." value="<?= e($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select" style="border-radius: 12px;">
                            <option value="">Tất cả trạng thái</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Đã đóng</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="pill-button pill-button-gray flex-grow-1 text-center justify-content-center">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                            <a href="/admin/chats.php" class="pill-button pill-button-white text-center justify-content-center" style="width: auto;">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Chat List -->
        <div class="card border-0 shadow-sm">

            <div class="card-body p-0">
                <?php if (empty($chats)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-secondary opacity-25 mb-3"></i>
                        <p class="text-secondary">Chưa có hội thoại nào</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($chats as $chat): ?>
                            <?php
                            $customerName = $chat['username'] ?: ($chat['guest_name'] ?: 'Khách');
                            $customerEmail = $chat['user_email'] ?: $chat['guest_email'];
                            $initials = strtoupper(mb_substr($customerName, 0, 1));
                            $hasUnread = $chat['unread_count'] > 0;
                            ?>
                            <div class="list-group-item chat-list-item p-3 <?= $hasUnread ? 'unread' : '' ?>"
                                 onclick="window.location.href='/admin/chat-detail?id=<?= $chat['id'] ?>'">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 flex-shrink-0 shadow-sm">
                                        <?= $initials ?>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center">
                                                <h6 class="mb-0 fw-bold text-dark me-2"><?= e($customerName) ?></h6>
                                                <?php if ($chat['user_id']): ?>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill" style="font-size: 10px;">Thành viên</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" style="font-size: 10px;">Khách</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-secondary">
                                                <?= timeAgo($chat['last_message_at'] ?: $chat['created_at']) ?>
                                            </small>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="mb-0 text-secondary small text-truncate" style="max-width: 70%;">
                                                <?php if ($chat['last_sender'] === 'admin'): ?>
                                                    <i class="fas fa-reply me-1 text-primary"></i>
                                                <?php endif; ?>
                                                <?= e($chat['last_message'] ?: 'Hình ảnh/Tệp tin...') ?>
                                            </p>
                                            <?php if ($hasUnread): ?>
                                                <span class="badge bg-danger rounded-pill"><?= $chat['unread_count'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

             <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-0 py-3">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-start-pill border-end-0" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-end-pill border-start-0" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
        
         <!-- Hướng dẫn trả lời từ Telegram -->
        <div class="alert alert-light border-0 shadow-sm mt-4 d-flex align-items-center" style="border-radius: 16px; background: #eef2ff;">
            <i class="fab fa-telegram fa-2x text-primary me-3"></i>
            <div>
                <h6 class="alert-heading mb-1 fw-bold text-primary">Trả lời nhanh qua Telegram</h6>
                <p class="mb-0 small text-secondary">
                    Bạn có thể trả lời chat trực tiếp từ Telegram bot: <code>/reply_[ID] [tin nhắn]</code> (VD: /reply_5 Chào bạn)
                </p>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto refresh every 10 seconds to check for new messages
    setInterval(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.get('search') && !urlParams.get('status')) {
            fetch(window.location.href)
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newList = doc.querySelector('.list-group');
                    const currentList = document.querySelector('.list-group');
                    
                    // Simple check if counts changed
                    const newUnread = doc.querySelector('.text-danger.fw-bold')?.innerText;
                    const currentUnread = document.querySelector('.text-danger.fw-bold')?.innerText;

                    if (newList && currentList && (newList.innerHTML !== currentList.innerHTML || newUnread !== currentUnread)) {
                        currentList.innerHTML = newList.innerHTML;
                        if(newUnread) document.querySelector('.text-danger.fw-bold').innerText = newUnread;
                    }
                })
                .catch(err => console.log('Auto-refresh failed:', err));
        }
    }, 10000);
    </script>
</body>
</html>
