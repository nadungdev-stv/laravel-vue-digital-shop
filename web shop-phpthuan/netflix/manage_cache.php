?php
/**
 * Netflix Email Cache Manager - Web Interface
 * Truy cập file này qua browser để quản lý cache
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load dependencies
require_once __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/sync_logic.php';

// Handle actions
$action = $_GET['action'] ?? '';
$message = '';
$messageType = '';

if ($action === 'sync') {
    try {
        $result = syncEmails($config);
        $message = "✅ Sync thành công! Fetched: {$result['fetched']} emails, Processed: {$result['processed']} emails";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "❌ Lỗi sync: " . $e->getMessage();
        $messageType = 'error';
    }
}

if ($action === 'clear') {
    try {
        db()->query("TRUNCATE TABLE netflix_email_cache");
        $message = "✅ Đã xóa toàn bộ cache!";
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "❌ Lỗi xóa cache: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get cache stats
try {
    $stats = db()->query("SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT recipient) as unique_emails,
        MAX(email_date) as latest_email,
        MIN(email_date) as oldest_email
    FROM netflix_email_cache")->fetch();
    
    $recentEmails = db()->query("
        SELECT recipient, subject, email_date, code 
        FROM netflix_email_cache 
        ORDER BY email_date DESC 
        LIMIT 10
    ")->fetchAll();
    
    $tableExists = true;
} catch (Exception $e) {
    $tableExists = false;
    $errorMsg = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix Cache Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-box h3 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        .stat-box p {
            opacity: 0.9;
            font-size: 0.9em;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 600;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .btn-danger {
            background: #f56565;
            color: white;
        }
        .btn-danger:hover {
            background: #e53e3e;
            transform: translateY(-2px);
        }
        .btn-success {
            background: #48bb78;
            color: white;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }
        .message.error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
        tr:hover {
            background: #f7fafc;
        }
        .code-badge {
            background: #667eea;
            color: white;
            padding: 4px 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        .empty-state h3 {
            margin-bottom: 10px;
        }
        .refresh-info {
            background: #ebf8ff;
            border-left: 4px solid #4299e1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .refresh-info strong {
            color: #2c5282;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Netflix Email Cache Manager</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$tableExists): ?>
            <div class="card">
                <div class="message error">
                    <strong>❌ Lỗi Database:</strong><br>
                    <?php echo htmlspecialchars($errorMsg); ?>
                </div>
                <p>Bảng <code>netflix_email_cache</code> chưa được tạo. Chạy script tạo bảng trước.</p>
            </div>
        <?php else: ?>

            <div class="card">
                <h2>📊 Thống Kê Cache</h2>
                <div class="stats-grid">
                    <div class="stat-box">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Tổng Email</p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo $stats['unique_emails']; ?></h3>
                        <p>Email Unique</p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo $stats['latest_email'] ? date('H:i d/m', strtotime($stats['latest_email'])) : 'N/A'; ?></h3>
                        <p>Email Mới Nhất</p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo $stats['oldest_email'] ? date('d/m/Y', strtotime($stats['oldest_email'])) : 'N/A'; ?></h3>
                        <p>Email Cũ Nhất</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>⚡ Thao Tác</h2>
                
                <?php if ($stats['total'] == 0): ?>
                    <div class="refresh-info">
                        <strong>⚠️ Cache đang trống!</strong><br>
                        Chạy "Sync Emails" để nạp email từ Gmail vào cache. Sau khi sync xong, trang tìm kiếm mới hoạt động được.
                    </div>
                <?php endif; ?>

                <div class="actions">
                    <a href="?action=sync" class="btn btn-primary" onclick="return confirm('Sync emails từ Gmail vào cache?')">
                        🔄 Sync Emails Ngay
                    </a>
                    <a href="?" class="btn btn-success">
                        🔃 Refresh Trang
                    </a>
                    <?php if ($stats['total'] > 0): ?>
                    <a href="?action=clear" class="btn btn-danger" onclick="return confirm('Xóa toàn bộ cache? Hành động này không thể hoàn tác!')">
                        🗑️ Xóa Toàn Bộ Cache
                    </a>
                    <?php endif; ?>
                </div>

                <div class="refresh-info">
                    <strong>💡 Hướng dẫn setup Cron (tùy chọn):</strong><br>
                    Để cache tự động cập nhật, thêm Cron Job trong FastPanel:<br>
                    <code>* * * * * /usr/bin/php <?php echo __DIR__; ?>/cron_fetch.php</code><br>
                    Hoặc vào đây chạy "Sync Emails" thủ công mỗi khi cần.
                </div>
            </div>

            <?php if (count($recentEmails) > 0): ?>
            <div class="card">
                <h2>📧 Email Gần Đây</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Thời Gian</th>
                            <th>Code</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentEmails as $email): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($email['recipient']); ?></td>
                            <td><?php echo htmlspecialchars(mb_substr($email['subject'], 0, 60)) . '...'; ?></td>
                            <td><?php echo date('H:i d/m/Y', strtotime($email['email_date'])); ?></td>
                            <td>
                                <?php if ($email['code']): ?>
                                    <span class="code-badge"><?php echo htmlspecialchars($email['code']); ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e0;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="empty-state">
                    <h3>📭 Cache Trống</h3>
                    <p>Chưa có email nào trong cache. Nhấn "Sync Emails Ngay" để bắt đầu.</p>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>
