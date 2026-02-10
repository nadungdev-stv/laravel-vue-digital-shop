<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

// Kiểm tra quyền admin
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$orderId) {
    setFlash('error', 'Đơn hàng không tồn tại');
    redirect('/admin/orders');
}

// Lấy thông tin đơn hàng
try {
    $order = db()->query(
        "SELECT o.*, u.username, u.email, u.phone,
                COALESCE(u.username, o.customer_name, 'Khách') as display_name,
                COALESCE(u.email, o.customer_email) as display_email,
                COALESCE(u.phone, o.customer_phone) as display_phone
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         WHERE o.id = ?",
        [$orderId]
    )->fetch();
} catch (Exception $e) {
    setFlash('error', 'Lỗi khi lấy thông tin đơn hàng: ' . $e->getMessage());
    redirect('/admin/orders');
}

if (!$order) {
    setFlash('error', 'Đơn hàng không tồn tại');
    redirect('/admin/orders');
}

// Lấy chi tiết sản phẩm trong đơn
try {
    $orderItems = db()->query(
        "SELECT oi.id, oi.order_id, oi.product_id, oi.quantity, oi.price, oi.total_price,
                oi.account_delivered, oi.customer_account_info, oi.created_at,
                oi.product_name as original_product_name,
                COALESCE(oi.product_name, p.name, 'Sản phẩm đã xóa') as product_name,
                COALESCE(p.image, '') as image,
                COALESCE(p.delivery_type, 'account') as delivery_type
         FROM order_items oi
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = ?",
        [$orderId]
    )->fetchAll();

    // Validate và sửa JSON không hợp lệ
    foreach ($orderItems as &$item) {
        if (!empty($item['account_delivered'])) {
            json_decode($item['account_delivered'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $item['account_delivered'] = null;
            }
        }
        if (!empty($item['customer_account_info'])) {
            json_decode($item['customer_account_info'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $item['customer_account_info'] = null;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error loading order items for order {$orderId}: " . $e->getMessage());
    $orderItems = [];
}

// Lấy lịch sử thanh toán (TẠM THỜI COMMENT - bảng payments chưa tồn tại)
// try {
//     $payments = db()->query(
//         "SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC",
//         [$orderId]
//     )->fetchAll();
// } catch (Exception $e) {
//     $payments = [];
// }
$payments = []; // Tạm thời set rỗng

$pageTitle = 'Chi tiết đơn hàng #' . ($order['order_code'] ?? $orderId);

// Xử lý giao tài khoản thủ công
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deliver_account'])) {
    $orderItemId = (int)$_POST['order_item_id'];
    $deliveryType = $_POST['delivery_type'];

    if ($deliveryType === 'email_only') {
        // Chỉ cần email - gửi lời mời nhóm gia đình
        $emailData = trim($_POST['account_email']);
        if (!empty($emailData)) {
            // Kiểm tra xem là JSON array hay single email
            $emails = json_decode($emailData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($emails)) {
                // Nhiều emails
                $deliveryData = [
                    'type' => 'email_only',
                    'emails' => $emails,
                    'note' => $_POST['account_note'] ?? ''
                ];
                $successMsg = 'Đã xác nhận gửi lời mời đến ' . count($emails) . ' email';
            } else {
                // Single email
                $deliveryData = [
                    'type' => 'email_only',
                    'email' => $emailData,
                    'note' => $_POST['account_note'] ?? ''
                ];
                $successMsg = 'Đã giao email thành công - Hãy gửi lời mời nhóm gia đình cho khách';
            }

            db()->query(
                "UPDATE order_items SET account_delivered = ? WHERE id = ?",
                [json_encode($deliveryData), $orderItemId]
            );

            // Tạo thông báo cho khách hàng
            createNotification(
                $order['user_id'],
                'Đã gửi lời mời nhóm gia đình',
                'Lời mời tham gia nhóm gia đình đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư để chấp nhận lời mời.',
                'success',
                '/order-detail?code=' . $order['order_code']
            );

            setFlash('success', $successMsg);
        }
    } elseif ($deliveryType === 'customer_account') {
        // Tài khoản khách hàng - đã được khách cung cấp khi đặt hàng
        // Chỉ cần xác nhận đã xử lý
        $note = trim($_POST['account_note'] ?? '');
        db()->query(
            "UPDATE order_items SET account_delivered = ? WHERE id = ?",
            [json_encode([
                'type' => 'customer_account',
                'note' => $note
            ]), $orderItemId]
        );

        // Tạo thông báo cho khách hàng
        createNotification(
            $order['user_id'],
            'Tài khoản đã được nâng cấp',
            'Tài khoản của bạn đã được nâng cấp thành công. Bạn có thể sử dụng ngay bây giờ!',
            'success',
            '/order-detail?code=' . $order['order_code']
        );

        setFlash('success', 'Đã xác nhận nâng cấp tài khoản khách hàng thành công');
    } else {
        // Tài khoản từ kho - Cần username và password
        $username = trim($_POST['account_username']);
        $password = trim($_POST['account_password']);

        if (!empty($username) && !empty($password)) {
            db()->query(
                "UPDATE order_items SET account_delivered = ? WHERE id = ?",
                [json_encode([
                    'type' => 'account',
                    'username' => $username,
                    'password' => $password,
                    'note' => $_POST['account_note'] ?? ''
                ]), $orderItemId]
            );

            // Tạo thông báo cho khách hàng
            $item = db()->query("SELECT product_name FROM order_items WHERE id = ?", [$orderItemId])->fetch();
            createNotification(
                $order['user_id'],
                'Đã nhận được tài khoản!',
                'Tài khoản ' . ($item['product_name'] ?? 'sản phẩm') . ' đã được giao. Vui lòng kiểm tra chi tiết đơn hàng để xem thông tin đăng nhập.',
                'success',
                '/order-detail?code=' . $order['order_code']
            );

            setFlash('success', 'Đã giao tài khoản từ kho thành công');
        }
    }

    redirect('/admin/order-detail?id=' . $orderId);
}

// Xử lý cập nhật trạng thái nhanh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_action'])) {
    $action = $_POST['quick_action'];

    if ($action === 'confirm_payment') {
        db()->query(
            "UPDATE orders SET payment_status = 'paid', order_status = 'processing', updated_at = NOW() WHERE id = ?",
            [$orderId]
        );

        // Tự động giao tài khoản
        foreach ($orderItems as $item) {
            if (!$item['account_delivered']) {
                // Kiểm tra loại giao hàng
                $deliveryType = $item['delivery_type'] ?? 'account';

                if ($deliveryType === 'email_only') {
                    // Giao email - gửi lời mời nhóm gia đình
                    db()->query(
                        "UPDATE order_items SET account_delivered = ? WHERE id = ?",
                        [json_encode([
                            'type' => 'email_only',
                            'email' => $order['email'],
                            'note' => 'Tự động giao - Hãy gửi lời mời nhóm gia đình'
                        ]), $item['id']]
                    );
                } elseif ($deliveryType === 'customer_account') {
                    // Tài khoản khách hàng - không cần tự động giao
                    // Khách đã nhập tài khoản khi đặt hàng, admin cần xử lý thủ công
                    // Không làm gì ở đây
                } else {
                    // Giao tài khoản từ kho
                    $account = db()->query(
                        "SELECT * FROM accounts_stock
                         WHERE product_id = ? AND status = 'available'
                         LIMIT 1",
                        [$item['product_id']]
                    )->fetch();

                    if ($account) {
                        db()->query(
                            "UPDATE order_items SET account_delivered = ? WHERE id = ?",
                            [json_encode([
                                'type' => 'account',
                                'username' => $account['account_username'],
                                'password' => $account['account_password'],
                                'note' => $account['account_note']
                            ]), $item['id']]
                        );

                        db()->query(
                            "UPDATE accounts_stock SET status = 'sold', sold_at = NOW() WHERE id = ?",
                            [$account['id']]
                        );
                    }
                }
            }
        }

        setFlash('success', 'Đã xác nhận thanh toán và giao tài khoản');
    } elseif ($action === 'complete') {
        db()->query(
            "UPDATE orders SET order_status = 'completed', updated_at = NOW() WHERE id = ?",
            [$orderId]
        );

        // Tạo thông báo cho khách hàng
        createNotification(
            $order['user_id'],
            'Đơn hàng đã hoàn thành!',
            'Đơn hàng ' . $order['order_code'] . ' đã được hoàn thành. Cảm ơn bạn đã mua hàng!',
            'success',
            '/order-detail?code=' . $order['order_code']
        );

        setFlash('success', 'Đã hoàn thành đơn hàng');
    } elseif ($action === 'cancel') {
        db()->query(
            "UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE id = ?",
            [$orderId]
        );
        setFlash('success', 'Đã hủy đơn hàng');
    }

    redirect('/admin/order-detail?id=' . $orderId);
}

// Xử lý cập nhật trạng thái từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderStatus = $_POST['order_status'];
    $paymentStatus = $_POST['payment_status'];

    db()->query(
        "UPDATE orders SET order_status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?",
        [$orderStatus, $paymentStatus, $orderId]
    );

    // Tạo thông báo nếu trạng thái đơn hàng thay đổi
    if ($orderStatus === 'completed' && $order['order_status'] !== 'completed') {
        createNotification(
            $order['user_id'],
            'Đơn hàng đã hoàn thành!',
            'Đơn hàng ' . $order['order_code'] . ' đã được hoàn thành. Cảm ơn bạn đã mua hàng!',
            'success',
            '/order-detail?code=' . $order['order_code']
        );
    } elseif ($orderStatus === 'cancelled' && $order['order_status'] !== 'cancelled') {
        createNotification(
            $order['user_id'],
            'Đơn hàng đã bị hủy',
            'Đơn hàng ' . $order['order_code'] . ' đã bị hủy. Vui lòng liên hệ với chúng tôi nếu có thắc mắc.',
            'warning',
            '/order-detail?code=' . $order['order_code']
        );
    } elseif ($paymentStatus === 'paid' && $order['payment_status'] !== 'paid') {
        createNotification(
            $order['user_id'],
            'Thanh toán thành công!',
            'Chúng tôi đã nhận được thanh toán cho đơn hàng ' . $order['order_code'] . '. Đơn hàng sẽ được xử lý ngay.',
            'success',
            '/order-detail?code=' . $order['order_code']
        );
    }

    setFlash('success', 'Đã cập nhật trạng thái đơn hàng');
    redirect('/admin/order-detail?id=' . $orderId);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/admin-apple-style.css">
    <style>
        .order-status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .delivered-info {
            background: #d1f2eb;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 5px;
        }
        .not-delivered {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
            <div class="d-flex justify-content-between align-items-center my-4">
                <div>

                </div>
                <div class="d-flex gap-2">
                    <button class="pill-button pill-button-yellow" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                        <i class="fas fa-edit"></i> Cập nhật trạng thái
                    </button>
                    <a href="/admin/orders" class="pill-button pill-button-white">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>

            <div class="row g-4">
                <!-- Thông tin đơn hàng -->
                <div class="col-lg-8">
                    <!-- Trạng thái & Actions -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-2">Trạng thái đơn hàng</h5>
                                    <?php
                                    $statusBadge = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $paymentBadge = [
                                        'pending' => 'warning',
                                        'paid' => 'success',
                                        'failed' => 'danger',
                                        'refunded' => 'secondary'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $statusBadge[$order['order_status']] ?? 'secondary' ?> order-status-badge me-2">
                                        <i class="fas fa-shopping-cart"></i> Đơn hàng: <?= e($order['order_status']) ?>
                                    </span>
                                    <span class="badge bg-<?= $paymentBadge[$order['payment_status']] ?? 'secondary' ?> order-status-badge">
                                        <i class="fas fa-credit-card"></i> Thanh toán: <?= e($order['payment_status']) ?>
                                    </span>
                                </div>
                                <div>
                                    <form method="POST" class="d-inline">
                                        <?php if ($order['payment_status'] === 'pending'): ?>
                                            <button type="submit" name="quick_action" value="confirm_payment"
                                                    class="pill-button pill-button-green pill-button-sm"
                                                    onclick="return confirm('Xác nhận đã nhận được thanh toán?')">
                                                <i class="fas fa-check"></i> Xác nhận thanh toán
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($order['order_status'] === 'processing'): ?>
                                            <button type="submit" name="quick_action" value="complete"
                                                    class="pill-button pill-button-blue pill-button-sm">
                                                <i class="fas fa-check-double"></i> Hoàn thành
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($order['order_status'] !== 'cancelled' && $order['order_status'] !== 'completed'): ?>
                                            <button type="submit" name="quick_action" value="cancel"
                                                    class="pill-button pill-button-red pill-button-sm"
                                                    onclick="return confirm('Hủy đơn hàng này?')">
                                                <i class="fas fa-times"></i> Hủy đơn
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            <hr class="opacity-10">
                            <div class="row text-center mt-4">
                                <div class="col-md-3">
                                    <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                    <p class="mb-0 small text-muted">Ngày đặt</p>
                                    <strong><?= formatDate($order['created_at']) ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <i class="fas fa-credit-card fa-2x text-info mb-2"></i>
                                    <p class="mb-0 small text-muted">Phương thức</p>
                                    <strong><?= e($order['payment_method']) ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <i class="fas fa-box fa-2x text-primary mb-2"></i>
                                    <p class="mb-0 small text-muted">Sản phẩm</p>
                                    <strong><?= count($orderItems) ?> sản phẩm</strong>
                                </div>
                                <div class="col-md-3">
                                    <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                                    <p class="mb-0 small text-muted">Tổng tiền</p>
                                    <strong class="text-success"><?= formatMoney($order['final_amount']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sản phẩm trong đơn -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-shopping-bag"></i> Sản phẩm đã đặt</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($orderItems)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>CẢNH BÁO:</strong> Đơn hàng này không có sản phẩm nào!<br>
                                    <small>Đơn hàng ID: <?= $orderId ?> - Có thể đã xảy ra lỗi khi tạo đơn. Vui lòng kiểm tra PHP error log.</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($orderItems as $item): ?>
                                <div class="card mb-3 border">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center mb-3">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?= e($item['image']) ?>" alt=""
                                                             class="me-3 rounded" style="width: 86px; height: 40px; object-fit: cover; border: 1px solid #e5e5e5;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-1"><?= e($item['product_name'] ?? 'Sản phẩm đã xóa') ?></h6>
                                                        <div>
                                                            <span class="text-muted">Đơn giá: <?= formatMoney($item['price'] ?? 0) ?></span>
                                                            <span class="mx-2">×</span>
                                                            <span class="text-muted">SL: <?= $item['quantity'] ?? 0 ?></span>
                                                            <span class="mx-2">=</span>
                                                            <strong class="text-primary"><?= formatMoney($item['total_price'] ?? 0) ?></strong>
                                                        </div>
                                                        <?php
                                                        $deliveryType = $item['delivery_type'] ?? 'account';
                                                        $deliveryTypeInfo = [
                                                            'account' => ['label' => 'Từ kho', 'badge' => 'primary', 'icon' => 'database'],
                                                            'email_only' => ['label' => 'Lời mời Email', 'badge' => 'info', 'icon' => 'envelope'],
                                                            'customer_account' => ['label' => 'TK Khách hàng', 'badge' => 'warning', 'icon' => 'user-edit']
                                                        ];
                                                        $typeInfo = $deliveryTypeInfo[$deliveryType] ?? $deliveryTypeInfo['account'];
                                                        ?>
                                                        <small class="badge bg-<?= $typeInfo['badge'] ?> mt-1">
                                                            <i class="fas fa-<?= $typeInfo['icon'] ?>"></i> <?= $typeInfo['label'] ?>
                                                        </small>
                                                    </div>
                                                </div>

                                                <!-- Thông tin tài khoản đã giao -->
                                                <?php if ($item['account_delivered']): ?>
                                                    <?php
                                                    $accountData = json_decode($item['account_delivered'], true);
                                                    $accountType = $accountData['type'] ?? 'account';
                                                    ?>
                                                    <div class="delivered-info">
                                                        <div class="d-flex align-items-start">
                                                            <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                                                            <div class="flex-grow-1">
                                                                <h6 class="text-success mb-2">
                                                                    <i class="fas fa-check"></i> Đã giao thành công
                                                                </h6>
                                                                <?php if ($accountType === 'email_only'): ?>
                                                                    <!-- Loại 1: Email only - Lời mời nhóm gia đình -->
                                                                    <div class="alert alert-info mb-2">
                                                                        <i class="fas fa-users"></i>
                                                                        <strong>Đã gửi lời mời nhóm gia đình</strong>
                                                                    </div>

                                                                    <?php if (isset($accountData['emails']) && is_array($accountData['emails'])): ?>
                                                                        <!-- Nhiều emails -->
                                                                        <div class="mb-2">
                                                                            <strong>Đã gửi lời mời đến <?= count($accountData['emails']) ?> email:</strong>
                                                                        </div>
                                                                        <?php foreach ($accountData['emails'] as $index => $email): ?>
                                                                            <p class="mb-1">
                                                                                <strong>Email #<?= $index + 1 ?>:</strong>
                                                                                <code class="bg-white px-2 py-1"><?= e($email) ?></code>
                                                                                <button class="btn btn-sm btn-outline-primary ms-2"
                                                                                        onclick="copyToClipboard('<?= e($email) ?>')">
                                                                                    <i class="fas fa-copy"></i>
                                                                                </button>
                                                                            </p>
                                                                        <?php endforeach; ?>
                                                                    <?php elseif (isset($accountData['email'])): ?>
                                                                        <!-- Single email -->
                                                                        <p class="mb-1">
                                                                            <strong>Email:</strong>
                                                                            <code class="bg-white px-2 py-1"><?= e($accountData['email']) ?></code>
                                                                            <button class="btn btn-sm btn-outline-primary ms-2"
                                                                                    onclick="copyToClipboard('<?= e($accountData['email']) ?>')">
                                                                                <i class="fas fa-copy"></i>
                                                                            </button>
                                                                        </p>
                                                                    <?php endif; ?>
                                                                <?php elseif ($accountType === 'customer_account'): ?>
                                                                    <!-- Loại 2: Customer account - Đã nâng cấp TK khách -->
                                                                    <div class="alert alert-success mb-2">
                                                                        <i class="fas fa-check-circle"></i>
                                                                        <strong>Đã nâng cấp tài khoản khách hàng</strong>
                                                                    </div>
                                                                    <p class="mb-0 text-muted">
                                                                        <i class="fas fa-info-circle"></i>
                                                                        Tài khoản của khách hàng đã được nâng cấp thành công
                                                                    </p>
                                                                <?php else: ?>
                                                                    <!-- Loại 3: Account - Tài khoản từ kho -->
                                                                    <div class="alert alert-primary mb-2">
                                                                        <i class="fas fa-database"></i>
                                                                        <strong>Đã giao tài khoản từ kho</strong>
                                                                    </div>
                                                                    <p class="mb-1">
                                                                        <strong>Username:</strong>
                                                                        <code class="bg-white px-2 py-1"><?= e($accountData['username']) ?></code>
                                                                        <button class="btn btn-sm btn-outline-primary ms-2"
                                                                                onclick="copyToClipboard('<?= e($accountData['username']) ?>')">
                                                                            <i class="fas fa-copy"></i>
                                                                        </button>
                                                                    </p>
                                                                    <p class="mb-1">
                                                                        <strong>Password:</strong>
                                                                        <code class="bg-white px-2 py-1"><?= e($accountData['password']) ?></code>
                                                                        <button class="btn btn-sm btn-outline-primary ms-2"
                                                                                onclick="copyToClipboard('<?= e($accountData['password']) ?>')">
                                                                            <i class="fas fa-copy"></i>
                                                                        </button>
                                                                    </p>
                                                                <?php endif; ?>
                                                                <?php if (!empty($accountData['note'])): ?>
                                                                    <p class="mb-0">
                                                                        <strong>Ghi chú:</strong> <?= e($accountData['note']) ?>
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="not-delivered p-3 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div>
                                                                <i class="fas fa-clock text-warning me-2"></i>
                                                                <strong class="text-warning text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">Chưa giao</strong>
                                                            </div>
                                                            <button class="pill-button pill-button-blue pill-button-sm"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deliverModal<?= $item['id'] ?>">
                                                                <i class="fas fa-paper-plane"></i> Giao ngay
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <?php /* Modal đã di chuyển xuống cuối file (dòng 932-1041) */ ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Tổng cộng -->
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8 text-end">
                                            <p class="mb-2"><strong>Tạm tính:</strong></p>
                                            <?php if ($order['discount_amount'] > 0): ?>
                                                <p class="mb-2 text-success">
                                                    <strong>Giảm giá:</strong>
                                                    <?php if ($order['coupon_code']): ?>
                                                        <span class="badge bg-success"><?= e($order['coupon_code']) ?></span>
                                                    <?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                            <h5 class="mb-0 text-primary"><strong>Tổng cộng:</strong></h5>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-2"><strong><?= formatMoney($order['total_amount']) ?></strong></p>
                                            <?php if ($order['discount_amount'] > 0): ?>
                                                <p class="mb-2 text-success"><strong>-<?= formatMoney($order['discount_amount']) ?></strong></p>
                                            <?php endif; ?>
                                            <h5 class="mb-0 text-success"><strong><?= formatMoney($order['final_amount']) ?></strong></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lịch sử thanh toán -->
                    <?php if (!empty($payments)): ?>
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-history"></i> Lịch sử thanh toán</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                <div class="table-responsive">
                                    <table class="table-modern table-hover">
                                        <thead>
                                            <tr>
                                                <th>Thời gian</th>
                                                <th>Phương thức</th>
                                                <th>Số tiền</th>
                                                <th>Trạng thái</th>
                                                <th>Ghi chú</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td><?= formatDate($payment['created_at']) ?></td>
                                                    <td><?= e($payment['payment_method']) ?></td>
                                                    <td><?= formatMoney($payment['amount']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $payment['status'] === 'completed' ? 'success' : 'warning' ?>">
                                                            <?= e($payment['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= e($payment['note']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Thông tin khách hàng -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-user"></i> Khách hàng</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!$order['user_id']): ?>
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle"></i> Đơn hàng từ khách không đăng nhập
                                </div>
                            <?php endif; ?>
                            <p class="mb-2">
                                <strong><i class="fas fa-user-circle text-primary"></i> Tên:</strong><br>
                                <?= e($order['display_name']) ?>
                            </p>
                            <p class="mb-2">
                                <strong><i class="fas fa-envelope text-danger"></i> Email:</strong><br>
                                <a href="mailto:<?= e($order['display_email']) ?>"><?= e($order['display_email']) ?></a>
                            </p>
                            <?php if ($order['display_phone']): ?>
                                <p class="mb-2">
                                    <strong><i class="fas fa-phone text-success"></i> Điện thoại:</strong><br>
                                    <a href="tel:<?= e($order['display_phone']) ?>"><?= e($order['display_phone']) ?></a>
                                </p>
                            <?php endif; ?>
                            <?php if ($order['user_id']): ?>
                                <hr>
                                <a href="/admin/user-detail?id=<?= $order['user_id'] ?>" class="pill-button pill-button-gray pill-button-sm w-100 justify-content-center">
                                    <i class="fas fa-external-link-alt"></i> Xem hồ sơ khách hàng
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Ghi chú đơn hàng -->
                    <?php if ($order['customer_note']): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Ghi chú</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-0"><?= nl2br(e($order['customer_note'])) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Timeline -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-timeline"></i> Timeline</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <div>
                                        <strong>Đơn hàng được tạo</strong>
                                        <br><small class="text-muted"><?= formatDate($order['created_at']) ?></small>
                                    </div>
                                </div>

                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <div class="timeline-item">
                                        <i class="fas fa-money-bill text-success"></i>
                                        <div>
                                            <strong>Đã thanh toán</strong>
                                            <br><small class="text-muted"><?= formatMoney($order['final_amount']) ?></small>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($order['order_status'] === 'completed'): ?>
                                    <div class="timeline-item">
                                        <i class="fas fa-check-double text-success"></i>
                                        <div>
                                            <strong>Hoàn thành</strong>
                                            <br><small class="text-muted"><?= formatDate($order['updated_at']) ?></small>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($order['order_status'] === 'cancelled'): ?>
                                    <div class="timeline-item">
                                        <i class="fas fa-times-circle text-danger"></i>
                                        <div>
                                            <strong>Đã hủy</strong>
                                            <br><small class="text-muted"><?= formatDate($order['updated_at']) ?></small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Modal cập nhật trạng thái -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Cập nhật trạng thái - <?= e($order['order_code']) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><strong>Trạng thái đơn hàng</strong></label>
                            <select name="order_status" class="form-select" required>
                                <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>
                                    <i class="fas fa-clock"></i> Chờ thanh toán
                                </option>
                                <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>
                                    Đang xử lý
                                </option>
                                <option value="completed" <?= $order['order_status'] === 'completed' ? 'selected' : '' ?>>
                                    Hoàn thành
                                </option>
                                <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>
                                    Đã hủy
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><strong>Trạng thái thanh toán</strong></label>
                            <select name="payment_status" class="form-select" required>
                                <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>
                                    Chờ thanh toán
                                </option>
                                <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>
                                    Đã thanh toán
                                </option>
                                <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>
                                    Thất bại
                                </option>
                                <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>
                                    Hoàn tiền
                                </option>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i>
                                Khi chuyển sang "Đã thanh toán", hệ thống sẽ tự động giao tài khoản
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lưu ý:</strong> Cẩn thận khi thay đổi trạng thái. Hành động này có thể ảnh hưởng đến khách hàng.
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="pill-button pill-button-white" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Đóng
                        </button>
                        <button type="submit" name="update_status" class="pill-button pill-button-blue">
                            <i class="fas fa-save"></i> Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Hiển thị thông báo
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <div class="toast show" role="alert">
                        <div class="toast-header bg-success text-white">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong class="me-auto">Thành công</strong>
                            <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.parentElement.remove()"></button>
                        </div>
                        <div class="toast-body">
                            Đã copy: <code>${text}</code>
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                // Toast will not auto-hide - user must close manually
            }).catch(err => {
                alert('Không thể copy: ' + err);
            });
        }
    </script>

    <!-- Modals Giao Tài Khoản - Đặt ngay trước </body> -->
    <?php foreach ($orderItems as $item): ?>
        <?php
        $deliveryType = $item['delivery_type'] ?? 'account';
        $customerData = json_decode($item['customer_account_info'] ?? '{}', true);
        ?>
        <div class="modal fade" id="deliverModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-paper-plane"></i> Giao: <?= e($item['product_name']) ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="order_item_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="delivery_type" value="<?= $deliveryType ?>">

                            <?php if ($deliveryType === 'email_only'): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-users"></i>
                                    <strong>Gửi lời mời nhóm gia đình</strong>
                                </div>
                                <?php $invitationEmails = $customerData['invitation_emails'] ?? []; ?>
                                <?php if (!empty($invitationEmails)): ?>
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Danh sách email:</strong></label>
                                        <?php foreach ($invitationEmails as $email): ?>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" value="<?= e($email) ?>" readonly>
                                                <button type="button" class="btn btn-outline-primary" onclick="copyToClipboard('<?= e($email) ?>')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="account_email" value="<?= e(json_encode($invitationEmails)) ?>">
                                <?php else: ?>
                                    <div class="mb-3">
                                        <label class="form-label">Email khách hàng</label>
                                        <input type="email" name="account_email" class="form-control" value="<?= e($order['email']) ?>" required>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($deliveryType === 'customer_account'): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-user-edit"></i>
                                    <strong>Nâng cấp tài khoản khách hàng</strong>
                                </div>
                                <?php if (!empty($customerData)): ?>
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <?php if (!empty($customerData['username'])): ?>
                                                <p><strong>Username:</strong> <code><?= e($customerData['username']) ?></code></p>
                                            <?php endif; ?>
                                            <?php if (!empty($customerData['password'])): ?>
                                                <p><strong>Password:</strong> <code><?= e($customerData['password']) ?></code></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-primary">
                                    <i class="fas fa-database"></i>
                                    <strong>Giao tài khoản từ kho</strong>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="account_username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="text" name="account_password" class="form-control" required>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="account_note" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="pill-button pill-button-white" data-bs-dismiss="modal">Đóng</button>
                            <button type="submit" name="deliver_account" class="pill-button pill-button-blue">
                                <i class="fas fa-paper-plane"></i> Giao ngay
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
</body>
</html>
