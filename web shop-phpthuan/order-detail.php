<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/sepay.php';

// Load security functions with error handling
if (file_exists(__DIR__ . '/includes/security.php')) {
    try {
        require_once __DIR__ . '/includes/security.php';
    } catch (Exception $e) {
        error_log("Failed to load security.php: " . $e->getMessage());
    }
}

initSession();

// Generate CSRF token for security
$csrfToken = generateCsrfToken();

$user = getCurrentUser();
$isGuest = !isLoggedIn();
$orderCode = $_GET['code'] ?? '';

if (empty($orderCode)) {
    setFlash('error', 'Không tìm thấy đơn hàng');
    redirect($isGuest ? '/products' : '/orders');
}

// Lấy thông tin đơn hàng
try {
    if ($isGuest) {
        // Guest chỉ có thể xem trong session hiện tại (sau khi vừa đặt hàng)
        $order = db()->query(
            "SELECT * FROM orders WHERE order_code = ? AND user_id IS NULL",
            [$orderCode]
        )->fetch();

        // Lưu order code vào session để guest có thể quay lại xem
        if (!isset($_SESSION['guest_orders'])) {
            $_SESSION['guest_orders'] = [];
        }

        // Kiểm tra xem guest có quyền xem đơn hàng này không
        if ($order && !in_array($orderCode, $_SESSION['guest_orders'])) {
            $_SESSION['guest_orders'][] = $orderCode;
        }

        // Nếu không tìm thấy hoặc không có trong session guest_orders
        if (!$order || !in_array($orderCode, $_SESSION['guest_orders'])) {
            setFlash('error', 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem');
            redirect('/products');
        }
    } else {
        // User đã đăng nhập
        $order = db()->query(
            "SELECT * FROM orders WHERE order_code = ? AND user_id = ?",
            [$orderCode, $user['id']]
        )->fetch();

        if (!$order) {
            setFlash('error', 'Không tìm thấy đơn hàng');
            redirect('/orders');
        }
    }

    // Lấy chi tiết sản phẩm
    $items = db()->query(
        "SELECT oi.*, p.image, p.delivery_type
         FROM order_items oi
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = ?",
        [$order['id']]
    )->fetchAll();

    // Lấy danh sách đánh giá đã có (nếu user đã đăng nhập)
    $reviewedProducts = [];
    if (!$isGuest) {
        $reviews = db()->query(
            "SELECT product_id, rating, comment FROM reviews WHERE order_id = ? AND user_id = ?",
            [$order['id'], $user['id']]
        )->fetchAll();

        foreach ($reviews as $review) {
            $reviewedProducts[$review['product_id']] = $review;
        }
    }
} catch (Exception $e) {
    echo "<div class='container mt-5'>";
    echo "<div class='alert alert-danger'>";
    echo "<h4>Lỗi khi tải thông tin đơn hàng:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='/orders' class='btn btn-primary'>Quay lại danh sách đơn hàng</a></p>";
    echo "</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Lấy danh sách email lời mời (nếu có)
$invitationEmails = [];
foreach ($items as $item) {
    if ($item['customer_account_info']) {
        try {
            $accountInfo = json_decode($item['customer_account_info'], true);
            if (isset($accountInfo['invitation_emails']) && is_array($accountInfo['invitation_emails'])) {
                foreach ($accountInfo['invitation_emails'] as $email) {
                    $invitationEmails[] = [
                        'product_name' => $item['product_name'],
                        'email' => $email
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Failed to decode customer_account_info for order {$order['order_code']}: " . $e->getMessage());
        }
    }
}

$pageTitle = 'Đơn hàng ' . $order['order_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container order-detail-container">
    <?php if ($isGuest): ?>
    <!-- Khuyến khích guest đăng ký -->
    <div class="alert alert-info guest-banner">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-1"><i class="fas fa-info-circle"></i> Đơn hàng của bạn đã được tiếp nhận!</h5>
                <p class="mb-0 small">
                    Thông tin tài khoản sẽ được gửi đến email <strong><?= e($order['customer_email']) ?></strong> sau khi thanh toán được xác nhận.
                    Đăng ký tài khoản để theo dõi đơn hàng dễ dàng hơn!
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <a href="/register.php" class="auth-button auth-single-btn" style="text-decoration: none;">
                    <i class="fas fa-user-plus"></i>
                    <span>Đăng ký ngay</span>
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="mb-4">
        <a href="/orders" class="pill-button pill-button-gray">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </a>
    </div>
    <?php endif; ?>

    <!-- Timeline tiến trình đơn hàng -->
    <?php
    // Xác định bước hiện tại
    $step1Complete = true; // Đặt hàng - luôn complete
    $step2Complete = ($order['order_status'] === 'processing' || $order['order_status'] === 'completed');
    $step3Complete = ($order['order_status'] === 'completed');
    $isCancelled = ($order['order_status'] === 'cancelled');

    // Xác định bước hiện tại
    if ($order['order_status'] === 'completed') {
        $currentStep = 3;
    } elseif ($order['order_status'] === 'processing') {
        $currentStep = 3; // Đang xử lý → bước 3 active với icon xoay
    } else {
        $currentStep = 2; // Pending → bước 2 thanh toán active
    }

    // Text và icon cho bước 3 thay đổi theo trạng thái
    if ($order['order_status'] === 'completed') {
        $step3Title = 'Hoàn thành';
        $step3Icon = 'fa-check-circle';
        $step3IconSpin = '';
    } else {
        $step3Title = 'Đang xử lý';
        $step3Icon = 'fa-sync-alt';
        $step3IconSpin = ($order['order_status'] === 'processing') ? 'fa-spin' : '';
    }
    ?>

    <?php if (!$isCancelled): ?>
    <div class="order-timeline-wrapper mb-4">
        <div class="order-timeline">
            <!-- Bước 1: Đặt hàng -->
            <div class="timeline-step <?= $step1Complete ? 'completed' : '' ?>">
                <div class="timeline-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="timeline-title">Đặt hàng</div>
            </div>

            <!-- Connector 1 -->
            <div class="timeline-connector <?= $step2Complete ? 'completed' : '' ?>"></div>

            <!-- Bước 2: Thanh toán -->
            <div class="timeline-step <?= $step2Complete ? 'completed' : '' ?> <?= $currentStep == 2 ? 'active' : '' ?>">
                <div class="timeline-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="timeline-title">Thanh toán</div>
            </div>

            <!-- Connector 2 -->
            <div class="timeline-connector <?= $step3Complete ? 'completed' : '' ?>"></div>

            <!-- Bước 3: Xử lý / Hoàn thành -->
            <div class="timeline-step <?= $step3Complete ? 'completed' : '' ?> <?= $currentStep == 3 ? 'active' : '' ?>">
                <div class="timeline-icon">
                    <i class="fas <?= $step3Icon ?> <?= $step3IconSpin ?>"></i>
                </div>
                <div class="timeline-title"><?= $step3Title ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Thông tin đơn hàng -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt"></i> Đơn hàng #<?= e($order['order_code']) ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Ngày đặt:</strong> <?= formatDate($order['created_at']) ?></p>
                            <?php if ($order['customer_name']): ?>
                            <p class="mb-2"><strong>Người đặt:</strong> <?= e($order['customer_name']) ?></p>
                            <?php endif; ?>
                            <p class="mb-2"><strong>Email liên hệ:</strong> <?= e($order['customer_email']) ?></p>
                            <?php if ($order['customer_phone']): ?>
                            <p class="mb-2"><strong>Số điện thoại:</strong> <?= e($order['customer_phone']) ?></p>
                            <?php endif; ?>
                            <p class="mb-2"><strong>Phương thức thanh toán:</strong>
                                <?php
                                $methods = [
                                    'bank_transfer' => 'Chuyển khoản ngân hàng',
                                    'momo' => 'Ví MoMo',
                                    'wallet' => 'Số dư tài khoản',
                                    'other' => 'Khác'
                                ];
                                echo $methods[$order['payment_method']] ?? $order['payment_method'];
                                ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-2">
                                <strong>Trạng thái đơn hàng:</strong>
                                <?php
                                $statusBadges = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $statusLabels = [
                                    'pending' => 'Chờ thanh toán',
                                    'processing' => 'Đang xử lý',
                                    'completed' => 'Hoàn thành',
                                    'cancelled' => 'Đã hủy'
                                ];
                                $badgeClass = $statusBadges[$order['order_status']] ?? 'secondary';
                                $label = $statusLabels[$order['order_status']] ?? $order['order_status'];
                                $tooltipAttr = ($order['order_status'] === 'processing')
                                    ? 'data-bs-toggle="tooltip" data-bs-placement="top" title="Đơn hàng đang xử lý, sẽ thông báo cho bạn tới email hoặc trang web"'
                                    : '';
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>" id="order-status-badge" <?= $tooltipAttr ?>><?= $label ?></span>
                            </p>
                        </div>
                    </div>

                    <?php if ($order['customer_note']): ?>
                        <div class="alert alert-info">
                            <strong><i class="fas fa-sticky-note"></i> Ghi chú:</strong>
                            <?= nl2br(e($order['customer_note'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['admin_note']): ?>
                        <div class="alert alert-warning">
                            <strong><i class="fas fa-comment"></i> Phản hồi từ admin:</strong>
                            <?= nl2br(e($order['admin_note'])) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Email lời mời nhóm gia đình -->
                    <?php if (!empty($invitationEmails)): ?>
                        <div class="invitation-emails-card">
                            <div class="invitation-header">
                                <i class="fas fa-users"></i>
                                <span>Email nhận lời mời nhóm gia đình</span>
                            </div>
                            <div class="invitation-body">
                                <?php
                                $currentProduct = '';
                                foreach ($invitationEmails as $item):
                                    if ($currentProduct !== $item['product_name']):
                                        if ($currentProduct !== '') echo '</div>';
                                        $currentProduct = $item['product_name'];
                                        echo '<div class="invitation-product-group">';
                                        echo '<div class="product-name">' . e($item['product_name']) . '</div>';
                                    endif;
                                ?>
                                    <div class="invitation-email-item">
                                        <i class="fas fa-envelope-open"></i>
                                        <span class="email-text"><?= e($item['email']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($currentProduct !== '') echo '</div>'; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Chi tiết sản phẩm -->
                    <h6 class="mt-4 mb-3"><i class="fas fa-box"></i> Sản phẩm đã mua</h6>

                    <?php if ($order['payment_status'] === 'pending' && $order['order_status'] !== 'processing'): ?>
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lưu ý:</strong> Vui lòng hoàn tất thanh toán để chúng tôi bắt đầu xử lý đơn hàng của bạn.
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Giá</th>
                                    <th>SL</th>
                                    <th>Tổng</th>
                                    <?php if ($order['payment_status'] === 'paid'): ?>
                                        <th>Tài khoản</th>
                                    <?php endif; ?>
                                    <?php if ($order['order_status'] === 'completed' && !$isGuest): ?>
                                        <th>Đánh giá</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['image']): ?>
                                                    <img src="<?= e($item['image']) ?>"
                                                         alt="<?= e($item['product_name']) ?>"
                                                         class="me-2 rounded"
                                                         style="width: 86px; height: 40px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <div><?= e($item['product_name']) ?></div>
                                                    <?php
                                                    // Hiển thị loại giao hàng
                                                    $deliveryType = $item['delivery_type'] ?? 'account';
                                                    $deliveryIcons = [
                                                        'account' => 'fa-database',
                                                        'email_only' => 'fa-envelope',
                                                        'customer_account' => 'fa-user-edit'
                                                    ];
                                                    $deliveryTexts = [
                                                        'account' => 'Tài khoản từ kho',
                                                        'email_only' => 'Lời mời qua Email',
                                                        'customer_account' => 'Nâng cấp tài khoản'
                                                    ];
                                                    ?>
                                                    <small class="badge bg-info">
                                                        <i class="fas <?= $deliveryIcons[$deliveryType] ?? 'fa-box' ?>"></i>
                                                        <?= $deliveryTexts[$deliveryType] ?? 'Không xác định' ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= formatMoney($item['price']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td><strong><?= formatMoney($item['price'] * $item['quantity']) ?></strong></td>

                                        <?php if ($order['payment_status'] === 'paid'): ?>
                                            <td>
                                                <?php if ($item['account_delivered']): ?>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#accountModal<?= $item['id'] ?>">
                                                        <i class="fas fa-key"></i> Xem
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-spinner fa-spin"></i> Đang xử lý
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($order['order_status'] === 'completed' && !$isGuest): ?>
                                            <td>
                                                <?php if (isset($reviewedProducts[$item['product_id']])): ?>
                                                    <div class="text-success">
                                                        <i class="fas fa-check-circle"></i>
                                                        <small>Đã đánh giá</small>
                                                        <div class="mt-1">
                                                            <?php
                                                            $rating = $reviewedProducts[$item['product_id']]['rating'];
                                                            for ($i = 0; $i < 5; $i++):
                                                            ?>
                                                                <i class="fas fa-star <?= $i < $rating ? 'text-warning' : 'text-muted' ?>" style="font-size: 12px;"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#reviewModal<?= $item['id'] ?>">
                                                        <i class="fas fa-star"></i> Đánh giá
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tổng kết đơn hàng -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator"></i> Tổng kết</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tạm tính:</span>
                        <strong><?= formatMoney($order['total_amount']) ?></strong>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Giảm giá:</span>
                            <strong>-<?= formatMoney($order['discount_amount']) ?></strong>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <h5>Tổng cộng:</h5>
                        <h5 class="text-danger"><?= formatMoney($order['final_amount']) ?></h5>
                    </div>
                </div>
            </div>

            <!-- Thanh toán -->
            <?php if ($order['order_status'] === 'pending' || ($order['payment_status'] === 'pending' && $order['order_status'] !== 'processing')): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-credit-card"></i> Thanh toán</h6>
                    </div>
                    <div class="card-body text-center">
                        <p class="mb-3">Bạn muốn thanh toán ngay?</p>
                        <button type="button" class="pill-button pill-button-blue"
                                data-bs-toggle="modal" data-bs-target="#paymentModal">
                            <i class="fas fa-wallet"></i> Thanh toán ngay
                        </button>
                        <p class="text-muted small mt-3 mb-0">
                            Xem thông tin chuyển khoản và xác nhận thanh toán
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Trạng thái xử lý -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Trạng thái</h6>
                </div>
                <div class="card-body">
                    <?php if ($order['order_status'] === 'processing'): ?>
                        <!-- Đơn hàng đang xử lý - khách đã xác nhận thanh toán -->
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-check-circle"></i>
                            <strong>Đã xác nhận thanh toán</strong>
                            <p class="mb-0 mt-2 small">
                                Bạn đã thanh toán qua
                                <?php
                                $paymentMethodNames = [
                                    'bank_transfer' => 'Chuyển khoản ngân hàng',
                                    'momo' => 'Ví MoMo',
                                    'wallet' => 'Số dư tài khoản',
                                    'other' => 'Phương thức khác'
                                ];
                                echo '<strong>' . ($paymentMethodNames[$order['payment_method']] ?? $order['payment_method']) . '</strong>';
                                ?>, vui lòng đợi xử lý.
                            </p>
                        </div>
                    <?php elseif ($order['order_status'] === 'completed'): ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle"></i>
                            <strong>Hoàn thành</strong>
                            <p class="mb-0 mt-2 small">
                                Đơn hàng đã được giao thành công. Cảm ơn bạn đã tin tưởng!
                            </p>
                        </div>
                    <?php elseif ($order['order_status'] === 'cancelled'): ?>
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-times-circle"></i>
                            <strong>Đã hủy</strong>
                            <p class="mb-0 mt-2 small">
                                Đơn hàng đã bị hủy.
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Trường hợp đơn hàng chưa thanh toán (pending) -->
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-clock"></i>
                            <strong>Chờ thanh toán</strong>
                            <p class="mb-0 mt-2 small">
                                Đơn hàng sẽ được xử lý ngay sau khi bạn hoàn tất thanh toán.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hỗ trợ -->
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-headset"></i> Hỗ trợ</h6>
                </div>
                <div class="card-body text-center">
                    <p class="mb-3">Cần hỗ trợ về đơn hàng này?</p>
                    <a href="/contact?order=<?= e($order['order_code']) ?>"
                       class="pill-button pill-button-blue">
                        <i class="fas fa-comment-dots"></i> Liên hệ hỗ trợ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals hiển thị tài khoản -->
<?php foreach ($items as $item): ?>
    <?php if ($item['account_delivered']): ?>
        <div class="modal fade" id="accountModal<?= $item['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
                <div class="modal-content" style="border-radius: 18px; overflow: hidden;">
                    <div class="modal-header" style="background: #f5f5f7; border-bottom: 1px solid #d2d2d7;">
                        <h5 class="modal-title" style="color: #1d1d1f;">
                            <i class="fas fa-key"></i> Thông tin tài khoản
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0);"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        // Decode JSON để hiển thị đẹp hơn
                        $deliveredData = json_decode($item['account_delivered'], true);
                        $deliveryType = is_array($deliveredData) ? ($deliveredData['type'] ?? 'unknown') : 'text';
                        ?>

                        <?php if ($deliveryType === 'email_only'): ?>
                            <!-- Loại 1: Email - Lời mời nhóm gia đình -->
                            <div class="alert alert-info">
                                <i class="fas fa-users"></i>
                                <strong>Lời mời nhóm gia đình</strong><br>
                                <small>Bạn sẽ nhận được lời mời qua email</small>
                            </div>

                            <?php if (isset($deliveredData['emails']) && is_array($deliveredData['emails'])): ?>
                                <!-- Nhiều emails -->
                                <div class="mb-3">
                                    <strong>Đã gửi lời mời đến <?= count($deliveredData['emails']) ?> email:</strong>
                                </div>
                                <?php foreach ($deliveredData['emails'] as $index => $email): ?>
                                    <div class="card bg-light mb-2">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <small class="text-muted">Email #<?= $index + 1 ?>:</small><br>
                                                    <strong><?= e($email) ?></strong>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="copyToClipboard('<?= e($email) ?>')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Single email -->
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <p class="mb-2"><strong>Email nhận lời mời:</strong></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <code><?= e($deliveredData['email'] ?? 'N/A') ?></code>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="copyToClipboard('<?= e($deliveredData['email'] ?? '') ?>')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php elseif ($deliveryType === 'customer_account'): ?>
                            <!-- Loại 2: Tài khoản khách hàng đã được nâng cấp -->
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <strong>Tài khoản của bạn đã được nâng cấp!</strong><br>
                                <small>Bạn có thể đăng nhập và sử dụng ngay bây giờ</small>
                            </div>
                            <?php if (!empty($deliveredData['note'])): ?>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <strong>Ghi chú:</strong><br>
                                        <?= nl2br(e($deliveredData['note'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php elseif ($deliveryType === 'account' && is_array($deliveredData)): ?>
                            <!-- Loại 3: Tài khoản từ kho (JSON format) -->
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Lưu ý:</strong> Không chia sẻ thông tin này cho người khác!
                            </div>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="mb-2"><strong><i class="fas fa-user"></i> Username:</strong></p>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <code class="fs-5"><?= e($deliveredData['username'] ?? 'N/A') ?></code>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="copyToClipboard('<?= e($deliveredData['username'] ?? '') ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>

                                    <p class="mb-2"><strong><i class="fas fa-key"></i> Password:</strong></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <code class="fs-5"><?= e($deliveredData['password'] ?? 'N/A') ?></code>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="copyToClipboard('<?= e($deliveredData['password'] ?? '') ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>

                                    <?php if (!empty($deliveredData['note'])): ?>
                                        <hr>
                                        <p class="mb-0">
                                            <strong>Ghi chú:</strong><br>
                                            <?= nl2br(e($deliveredData['note'])) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- Fallback: Hiển thị dạng text thuần -->
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Lưu ý:</strong> Không chia sẻ thông tin này cho người khác!
                            </div>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <pre class="mb-0"><?= e($item['account_delivered']) ?></pre>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary mt-3 w-100"
                                    onclick="copyToClipboard('<?= addslashes($item['account_delivered']) ?>')">
                                <i class="fas fa-copy"></i> Sao chép tất cả
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<!-- Modals đánh giá sản phẩm -->
<?php if ($order['order_status'] === 'completed' && !$isGuest): ?>
    <?php foreach ($items as $item): ?>
        <?php if (!isset($reviewedProducts[$item['product_id']])): ?>
            <div class="modal fade" id="reviewModal<?= $item['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" style="max-width: 550px;">
                    <div class="modal-content" style="border: none; border-radius: 18px; overflow: hidden;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #7387df 100%); color: white; border: none; padding: 15px 20px;">
                            <div class="d-flex align-items-center gap-3">
                                <h5 class="modal-title mb-0" style="font-weight: 600; font-size: 16px;">
                                    <i class="fas fa-star me-2"></i>Đánh giá sản phẩm
                                </h5>
                                <small style="opacity: 0.9; font-size: 13px;">Đơn hàng #<?= e($order['order_code']) ?></small>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding: 15px 20px;">
                            <form id="reviewForm<?= $item['id'] ?>" data-order-id="<?= $order['id'] ?>" data-product-id="<?= $item['product_id'] ?>" data-item-id="<?= $item['id'] ?>">
                                <!-- Product header with rating on same line -->
                                <div class="mb-3 pb-3" style="border-bottom: 1px solid #e9ecef;">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if ($item['image']): ?>
                                            <div style="width: 100px; height: 47px; border-radius: 8px; overflow: hidden; flex-shrink: 0;">
                                                <img src="<?= e($item['image']) ?>"
                                                     alt="<?= e($item['product_name']) ?>"
                                                     style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0" style="font-weight: 600; color: #2d3748; font-size: 14px;"><?= e($item['product_name']) ?></h6>
                                        </div>
                                        <div class="star-rating-input">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" name="rating<?= $item['id'] ?>" id="star<?= $item['id'] ?>_<?= $i ?>" value="<?= $i ?>" required>
                                                <label for="star<?= $item['id'] ?>_<?= $i ?>" class="star-label">
                                                    <i class="fas fa-star"></i>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>

                            <!-- Comment section -->
                            <div class="mb-3">
                                <label for="comment<?= $item['id'] ?>" class="form-label" style="font-size: 13px; font-weight: 600; color: #4a5568;">Nhận xét của bạn</label>
                                <textarea class="form-control comment-textarea-<?= $item['id'] ?>" id="comment<?= $item['id'] ?>" name="comment" rows="2"
                                          placeholder="Chia sẻ trải nghiệm của bạn..."
                                          style="font-size: 13px; border-radius: 8px;"></textarea>

                                <!-- Comment suggestions -->
                                <div class="mt-2">
                                    <small class="text-muted d-block mb-2" style="font-size: 11px;">Gợi ý nhận xét (có thể chọn nhiều):</small>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-outline-success comment-suggest-detail-btn" data-item-id="<?= $item['id'] ?>" data-comment="Tài khoản hoạt động tốt" style="border-radius: 15px; font-size: 12px;">
                                            <i class="fas fa-check-circle"></i> TK hoạt động tốt
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success comment-suggest-detail-btn" data-item-id="<?= $item['id'] ?>" data-comment="Giao tài khoản nhanh" style="border-radius: 15px; font-size: 12px;">
                                            <i class="fas fa-bolt"></i> Giao nhanh
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success comment-suggest-detail-btn" data-item-id="<?= $item['id'] ?>" data-comment="Tài khoản chính hãng" style="border-radius: 15px; font-size: 12px;">
                                            <i class="fas fa-shield-alt"></i> Chính hãng
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success comment-suggest-detail-btn" data-item-id="<?= $item['id'] ?>" data-comment="Giá rất hợp lý" style="border-radius: 15px; font-size: 12px;">
                                            <i class="fas fa-dollar-sign"></i> Giá tốt
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success comment-suggest-detail-btn" data-item-id="<?= $item['id'] ?>" data-comment="Sẽ mua lại" style="border-radius: 15px; font-size: 12px;">
                                            <i class="fas fa-redo"></i> Sẽ mua lại
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success comment-suggest-detail-btn" data-item-id="<?= $item['id'] ?>" data-comment="Đúng như mô tả" style="border-radius: 15px; font-size: 12px;">
                                            <i class="fas fa-check"></i> Đúng mô tả
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success comment-suggest-detail-btn" data-item-id="<?= $item['id'] ?>" data-comment="Hỗ trợ nhiệt tình" style="border-radius: 15px; font-size: 12px;">
                                            <i class="fas fa-headset"></i> Hỗ trợ tốt
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success comment-suggest-detail-btn" data-item-id="<?= $item['id'] ?>" data-comment="Shop uy tín" style="border-radius: 15px; font-size: 12px;">
                                            <i class="fas fa-star"></i> Uy tín
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success comment-suggest-detail-btn" data-item-id="<?= $item['id'] ?>" data-comment="Bảo hành tốt" style="border-radius: 15px; font-size: 12px;">
                                            <i class="fas fa-tools"></i> Bảo hành tốt
                                        </button>
                                    </div>
                                </div>
                            </div>

                                <button type="button" class="btn btn-primary w-100" onclick="submitReview(<?= $item['id'] ?>)" style="padding: 10px; font-weight: 600; border-radius: 8px; font-size: 14px; background: linear-gradient(135deg, #667eea 0%, #7387df 100%); border: none;">
                                    <i class="fas fa-paper-plane me-2"></i>Gửi đánh giá
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal Thanh toán -->
<?php if ($order['payment_status'] === 'pending'): ?>
<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered payment-modal-<?= $order['payment_method'] ?>" style="max-width: <?= ($order['payment_method'] === 'bank_transfer') ? '600px' : '450px' ?>;">
        <div class="modal-content" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header" style="background: #f5f5f7; border-bottom: 1px solid #d2d2d7;">
                <h5 class="modal-title" style="color: #1d1d1f;">
                    <i class="fas fa-credit-card"></i> Thanh Toán Đơn Hàng #<?= e($order['order_code']) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0);"></button>
            </div>
            <div class="modal-body">
                <!-- Bank Transfer -->
                <?php if ($order['payment_method'] === 'bank_transfer'): ?>
                    <style>
                        .payment-amount {
                            text-align: center;
                            margin-bottom: 15px;
                            padding-bottom: 12px;
                            border-bottom: 2px solid #e9ecef;
                        }
                        .payment-amount h5 {
                            color: #dc3545;
                            font-weight: 700;
                            margin: 0;
                            font-size: 1.25rem;
                        }
                        .payment-info-box, .qr-box {
                            background: #f8f9fa;
                            border: 1px solid #dee2e6;
                            border-radius: 8px;
                            padding: 12px;
                            height: 100%;
                            display: flex;
                            flex-direction: column;
                        }
                        .payment-info-box h6, .qr-box h6 {
                            color: #495057;
                            font-weight: 600;
                            margin-bottom: 10px;
                            font-size: 0.95rem;
                        }
                        .info-rows-container {
                            display: flex;
                            flex-direction: column;
                            justify-content: space-evenly;
                            flex: 1;
                        }
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 6px 0;
                            border-bottom: 1px solid #e9ecef;
                        }
                        .info-row:last-child {
                            border-bottom: none;
                        }
                        .info-label {
                            color: #6c757d;
                            font-size: 0.95rem;
                            font-weight: 500;
                        }
                        .info-value {
                            color: #212529;
                            font-weight: 600;
                            font-size: 1rem;
                        }
                        .info-value.amount {
                            color: #dc3545;
                            font-size: 1.15rem;
                        }
                        .info-value code {
                            background: #fff;
                            padding: 2px 6px;
                            border-radius: 4px;
                            color: #495057;
                            font-size: 0.95rem;
                        }
                        .btn-copy {
                            background: #007bff;
                            color: white;
                            border: none;
                            padding: 3px 8px;
                            border-radius: 4px;
                            font-size: 0.75rem;
                            cursor: pointer;
                            margin-left: 6px;
                        }
                        .btn-copy:hover {
                            background: #0056b3;
                        }
                        .qr-box {
                            text-align: center;
                            justify-content: center;
                            align-items: center;
                        }
                        .qr-box img {
                            max-width: 220px;
                            width: 100%;
                            border-radius: 6px;
                            margin: 0 auto;
                            display: block;
                        }
                        .qr-note {
                            color: #6c757d;
                            font-size: 0.8rem;
                            margin-top: 6px;
                            margin-bottom: 0;
                        }
                        .payment-note {
                            background: #fff3cd;
                            border: 1px solid #ffc107;
                            border-radius: 6px;
                            padding: 8px 12px;
                            margin-top: 12px;
                            font-size: 0.88rem;
                        }
                    </style>

                    <?php
                    // Lấy thông tin ngân hàng từ SePay settings
                    $bankInfo = getSepayBankInfo();
                    $bankName = $bankInfo['bank_name'];
                    $bankAccount = $bankInfo['account_number'];
                    $accountName = $bankInfo['account_name'];
                    $bankCode = $bankInfo['bank_code'];

                    $amount = $order['final_amount'];
                    // Nội dung chuẩn để webhook SePay nhận diện: DH {order_code}
                    $content = "DH " . $order['order_code'];

                    // Dùng VietQR để tạo ảnh QR
                    $qrUrl = "https://img.vietqr.io/image/{$bankCode}-{$bankAccount}-compact.png?amount={$amount}&addInfo=" . urlencode($content) . "&accountName=" . urlencode($accountName);
                    ?>

                    <div class="payment-amount">
                        <h5>Số tiền: <?= formatMoney($order['final_amount']) ?></h5>
                    </div>

                    <div class="row g-2">
                        <!-- Left: Payment Info -->
                        <div class="col-md-6">
                            <div class="payment-info-box" id="paymentInfoSection">
                                <h6><i class="fas fa-info-circle"></i> Thông tin chuyển khoản</h6>

                                <div class="info-rows-container">
                                    <div class="info-row">
                                        <span class="info-label">Ngân hàng:</span>
                                        <span class="info-value"><?= e($bankName) ?></span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Số TK:</span>
                                        <span class="info-value">
                                            <code><?= e($bankAccount) ?></code>
                                            <button class="btn-copy" onclick="copyToClipboard('<?= e($bankAccount) ?>')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Chủ TK:</span>
                                        <span class="info-value"><?= e($accountName) ?></span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Số tiền:</span>
                                        <span class="info-value amount"><?= formatMoney($amount) ?></span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Nội dung:</span>
                                        <span class="info-value">
                                            <code id="payment_content"><?= e($content) ?></code>
                                            <button class="btn-copy" onclick="copyToClipboard(document.getElementById('payment_content').innerText)">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>

                                <!-- Mobile QR Toggle Button -->
                                <div class="mobile-qr-toggle d-md-none mt-3">
                                    <button type="button" class="btn btn-outline-primary w-100" id="toggleQrBtn" onclick="toggleQRCode()">
                                        <i class="fas fa-qrcode"></i> Xem mã QR
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: QR Code -->
                        <div class="col-md-6 qr-code-section d-none d-md-block" id="qrCodeSection">
                            <div class="qr-box">
                                <h6><i class="fas fa-qrcode"></i> Quét mã QR</h6>
                                <img src="<?= $qrUrl ?>" alt="QR Code">
                                <p class="qr-note">Quét bằng app ngân hàng</p>

                                <!-- Mobile: Back to payment info button -->
                                <div class="mobile-qr-back d-md-none mt-3">
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="toggleQRCode()">
                                        <i class="fas fa-arrow-left"></i> Quay lại thông tin chuyển khoản
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="payment-note">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Lưu ý:</strong> Chuyển khoản đúng số tiền và nội dung để xử lý tự động
                    </div>

                <!-- Wallet -->
                <?php elseif ($order['payment_method'] === 'wallet'): ?>
                    <div class="wallet-payment-content">
                        <div class="wallet-icon-wrapper">
                            <i class="fas fa-wallet"></i>
                        </div>

                        <h4 class="wallet-title">Thanh toán bằng số dư ví</h4>

                        <div class="wallet-info-box">
                            <div class="wallet-info-row">
                                <span class="wallet-label">Số dư hiện tại</span>
                                <span class="wallet-value wallet-current"><?= formatMoney($user['balance']) ?></span>
                            </div>

                            <div class="wallet-divider"></div>

                            <div class="wallet-info-row">
                                <span class="wallet-label">Số tiền thanh toán</span>
                                <span class="wallet-value wallet-deduct">-<?= formatMoney($order['final_amount']) ?></span>
                            </div>

                            <div class="wallet-divider"></div>

                            <div class="wallet-info-row wallet-total">
                                <span class="wallet-label">Số dư còn lại</span>
                                <span class="wallet-value wallet-remain"><?= formatMoney($user['balance'] - $order['final_amount']) ?></span>
                            </div>
                        </div>

                        <?php if ($user['balance'] < $order['final_amount']): ?>
                            <div class="wallet-alert wallet-alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>Số dư không đủ để thanh toán đơn hàng này</span>
                            </div>
                        <?php else: ?>
                            <div class="wallet-alert wallet-alert-success">
                                <i class="fas fa-check-circle"></i>
                                <span>Số dư đủ để hoàn tất thanh toán</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <style>
                    .wallet-payment-content {
                        padding: 20px 0;
                    }

                    .wallet-icon-wrapper {
                        width: 72px;
                        height: 72px;
                        background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
                        border-radius: 18px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 20px;
                        box-shadow: 0 4px 16px rgba(52, 199, 89, 0.25);
                    }

                    .wallet-icon-wrapper i {
                        font-size: 32px;
                        color: white;
                    }

                    .wallet-title {
                        text-align: center;
                        font-size: 20px;
                        font-weight: 600;
                        color: #1d1d1f;
                        margin-bottom: 24px;
                        letter-spacing: -0.02em;
                    }

                    .wallet-info-box {
                        background: #f5f5f7;
                        border: 1px solid #d2d2d7;
                        border-radius: 14px;
                        padding: 18px;
                        margin-bottom: 16px;
                    }

                    .wallet-info-row {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 8px 0;
                    }

                    .wallet-info-row.wallet-total {
                        padding-top: 12px;
                    }

                    .wallet-label {
                        font-size: 15px;
                        color: #6e6e73;
                        font-weight: 500;
                    }

                    .wallet-value {
                        font-size: 16px;
                        font-weight: 600;
                        color: #1d1d1f;
                    }

                    .wallet-value.wallet-current {
                        color: #0071e3;
                    }

                    .wallet-value.wallet-deduct {
                        color: #ff3b30;
                    }

                    .wallet-value.wallet-remain {
                        font-size: 18px;
                        color: #34c759;
                    }

                    .wallet-divider {
                        height: 1px;
                        background: #d2d2d7;
                        margin: 8px 0;
                    }

                    .wallet-alert {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 12px 16px;
                        border-radius: 12px;
                        font-size: 14px;
                        font-weight: 500;
                    }

                    .wallet-alert i {
                        font-size: 16px;
                        flex-shrink: 0;
                    }

                    .wallet-alert-success {
                        background: #e5ffe5;
                        color: #1d7c2e;
                        border: 1px solid #34c759;
                    }

                    .wallet-alert-error {
                        background: #ffe5e5;
                        color: #c41e1e;
                        border: 1px solid #ff3b30;
                    }

                    @media (max-width: 767px) {
                        .wallet-icon-wrapper {
                            width: 64px;
                            height: 64px;
                        }

                        .wallet-icon-wrapper i {
                            font-size: 28px;
                        }

                        .wallet-title {
                            font-size: 18px;
                            margin-bottom: 20px;
                        }

                        .wallet-info-box {
                            padding: 16px;
                        }

                        .wallet-label {
                            font-size: 14px;
                        }

                        .wallet-value {
                            font-size: 15px;
                        }

                        .wallet-value.wallet-remain {
                            font-size: 16px;
                        }
                    }
                    </style>

                <!-- MoMo -->
                <?php elseif ($order['payment_method'] === 'momo'): ?>
                    <style>
                        .payment-amount {
                            text-align: center;
                            margin-bottom: 10px;
                            padding-bottom: 8px;
                            border-bottom: 2px solid #e9ecef;
                        }
                        .payment-amount h5 {
                            color: #dc3545;
                            font-weight: 700;
                            margin: 0;
                            font-size: 1.15rem;
                        }
                        .payment-info-box {
                            background: #f8f9fa;
                            border: 1px solid #dee2e6;
                            border-radius: 8px;
                            padding: 10px;
                            display: flex;
                            flex-direction: column;
                        }
                        .payment-info-box h6 {
                            color: #495057;
                            font-weight: 600;
                            margin-bottom: 8px;
                            font-size: 0.9rem;
                        }
                        .info-rows-container {
                            display: flex;
                            flex-direction: column;
                            gap: 0;
                        }
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 4px 0;
                            border-bottom: 1px solid #e9ecef;
                        }
                        .info-row:last-child {
                            border-bottom: none;
                        }
                        .info-label {
                            color: #6c757d;
                            font-size: 0.9rem;
                            font-weight: 500;
                        }
                        .info-value {
                            color: #212529;
                            font-weight: 600;
                            font-size: 0.95rem;
                        }
                        .info-value.amount {
                            color: #dc3545;
                            font-size: 1.05rem;
                        }
                        .info-value code {
                            background: #fff;
                            padding: 2px 6px;
                            border-radius: 4px;
                            color: #495057;
                            font-size: 0.9rem;
                        }
                        .btn-copy {
                            background: #007bff;
                            color: white;
                            border: none;
                            padding: 3px 8px;
                            border-radius: 4px;
                            font-size: 0.75rem;
                            cursor: pointer;
                            margin-left: 6px;
                        }
                        .btn-copy:hover {
                            background: #0056b3;
                        }
                        .payment-note {
                            background: #fff3cd;
                            border: 1px solid #ffc107;
                            border-radius: 6px;
                            padding: 6px 10px;
                            margin-top: 8px;
                            font-size: 0.85rem;
                        }
                    </style>

                    <?php
                    $amount = $order['final_amount'];
                    $content = ($isGuest ? 'GUEST' : $user['username']) . ' ' . $order['order_code'];
                    $momoPhone = '0848877758';
                    $momoName = 'Ngô Anh Dũng';
                    ?>

                    <div class="payment-amount">
                        <h5>Số tiền: <?= formatMoney($order['final_amount']) ?></h5>
                    </div>

                    <div class="payment-info-box">
                        <h6><i class="fas fa-mobile-alt"></i> Thông tin thanh toán MoMo</h6>

                        <div class="info-rows-container">
                            <div class="info-row">
                                <span class="info-label">Số điện thoại:</span>
                                <span class="info-value">
                                    <code><?= e($momoPhone) ?></code>
                                    <button class="btn-copy" onclick="copyToClipboard('<?= e($momoPhone) ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Chủ ví:</span>
                                <span class="info-value"><?= e($momoName) ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Số tiền:</span>
                                <span class="info-value amount"><?= formatMoney($amount) ?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Nội dung:</span>
                                <span class="info-value">
                                    <code id="momo_content"><?= e($content) ?></code>
                                    <button class="btn-copy" onclick="copyToClipboard(document.getElementById('momo_content').innerText)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="payment-note">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Lưu ý:</strong> Vui lòng ghi <strong>đúng nội dung</strong> khi chuyển để được xử lý tự động.
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer" style="background: #f5f5f7; border-top: 1px solid #d2d2d7;">
                <?php if ($order['payment_method'] === 'wallet'): ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 1000px; padding: 10px 20px; font-weight: 600; letter-spacing: -0.01em; border: none; transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="button" class="btn btn-success" onclick="confirmPayment('<?= e($order['order_code']) ?>', 'wallet')" <?= $user['balance'] < $order['final_amount'] ? 'disabled' : '' ?> style="background: #34c759; border-color: #34c759; border-radius: 1000px; padding: 10px 20px; font-weight: 600; letter-spacing: -0.01em; border: none; transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1); <?= $user['balance'] < $order['final_amount'] ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>">
                        <i class="fas fa-check-circle"></i> Xác nhận thanh toán
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 1000px; padding: 10px 20px; font-weight: 600; letter-spacing: -0.01em; border: none; transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);">
                        <i class="fas fa-clock"></i>
                        Để sau
                    </button>
                    <button type="button" class="btn btn-success" onclick="confirmPayment('<?= e($order['order_code']) ?>', '<?= e($order['payment_method']) ?>')" style="background: #34c759; border-color: #34c759; border-radius: 1000px; padding: 10px 20px; font-weight: 600; letter-spacing: -0.01em; border: none; transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);">
                        <i class="fas fa-check-circle"></i>
                        Đã thanh toán
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Payment Success Overlay - Outside modal -->
<?php if ($order['payment_status'] === 'pending' && $order['payment_method'] === 'bank_transfer'): ?>
<div id="paymentSuccessOverlay" class="payment-success-overlay" style="display: none;">
    <div class="success-content">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>Thanh toán thành công!</h3>
        <p>Đơn hàng của bạn đã được xác nhận thanh toán.</p>
        <button type="button" class="btn btn-success" onclick="location.reload()" style="background: #34c759; border-color: #34c759; border-radius: 1000px; padding: 10px 20px; font-weight: 600; letter-spacing: -0.01em; border: none; transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);">
            <i class="fas fa-check-circle"></i> Xem chi tiết đơn hàng
        </button>
    </div>
</div>

<style>
.payment-success-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.success-content {
    background: white;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    max-width: 400px;
    animation: scaleIn 0.3s ease;
}
@keyframes scaleIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.success-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}
.success-icon i {
    font-size: 40px;
    color: white;
}
.success-content h3 {
    color: #1d1d1f;
    margin-bottom: 10px;
    font-size: 24px;
}
.success-content p {
    color: #86868b;
    margin-bottom: 20px;
}
</style>

<script>
(function() {
    const orderCode = '<?= e($order['order_code']) ?>';
    let checkInterval;
    let isChecking = false;

    function checkPaymentStatus() {
        if (isChecking) return;
        isChecking = true;

        fetch('/api/check-payment-status.php?code=' + orderCode)
            .then(response => response.json())
            .then(data => {
                isChecking = false;
                if (data.success && data.paid) {
                    // Stop polling
                    if (checkInterval) {
                        clearInterval(checkInterval);
                    }
                    // Close payment modal if open
                    const paymentModalEl = document.getElementById('paymentModal');
                    if (paymentModalEl) {
                        const paymentModal = bootstrap.Modal.getInstance(paymentModalEl);
                        if (paymentModal) {
                            paymentModal.hide();
                        }
                    }
                    // Remove modal backdrop
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    // Show success overlay
                    document.getElementById('paymentSuccessOverlay').style.display = 'flex';
                }
            })
            .catch(err => {
                isChecking = false;
                console.error('Check payment error:', err);
            });
    }

    // Start polling every 2 seconds
    checkInterval = setInterval(checkPaymentStatus, 2000);

    // Also check immediately
    checkPaymentStatus();

    // Stop polling when page is hidden
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            if (checkInterval) clearInterval(checkInterval);
        } else {
            checkInterval = setInterval(checkPaymentStatus, 2000);
            checkPaymentStatus();
        }
    });
})();
</script>
<?php endif; ?>

<?php if ($order['payment_status'] === 'pending'): ?>
<script>
// Auto show payment modal for pending orders
<?php
// Chỉ hiện modal nếu chưa xác nhận thanh toán
$hasConfirmed = isset($_SESSION['confirmed_payments']) && in_array($order['order_code'], $_SESSION['confirmed_payments']);
if ($order['payment_status'] === 'pending' && !$hasConfirmed):
?>
document.addEventListener('DOMContentLoaded', function() {
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
});
<?php endif; ?>

// Initialize tooltips on page load
document.addEventListener('DOMContentLoaded', function() {
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipElements.forEach(function(element) {
        new bootstrap.Tooltip(element);
    });
});

// Toggle QR Code on mobile
function toggleQRCode() {
    const qrSection = document.getElementById('qrCodeSection');
    const paymentInfo = document.getElementById('paymentInfoSection');
    const toggleBtn = document.getElementById('toggleQrBtn');

    if (qrSection && paymentInfo && toggleBtn) {
        if (qrSection.classList.contains('show')) {
            // Ẩn QR, hiện thông tin
            qrSection.classList.remove('show');
            qrSection.classList.remove('d-block');
            qrSection.classList.add('d-none');
            paymentInfo.classList.remove('hide');
            paymentInfo.classList.remove('d-none');
            paymentInfo.classList.add('d-block');
            toggleBtn.innerHTML = '<i class="fas fa-qrcode"></i> Xem mã QR';
        } else {
            // Ẩn thông tin, hiện QR
            paymentInfo.classList.add('hide');
            paymentInfo.classList.remove('d-block');
            paymentInfo.classList.add('d-none');
            qrSection.classList.add('show');
            qrSection.classList.remove('d-none');
            qrSection.classList.add('d-block');
            toggleBtn.innerHTML = '<i class="fas fa-times"></i> Ẩn mã QR';
        }
    }
}

function confirmPayment(orderCode, paymentMethod) {
    let confirmMessage;
    if (paymentMethod === 'wallet') {
        confirmMessage = 'Xác nhận thanh toán đơn hàng này bằng số dư ví?\n\nSố tiền sẽ được trừ ngay lập tức.';
    } else {
        confirmMessage = 'Bạn xác nhận đã hoàn tất chuyển khoản?\n\nĐơn hàng sẽ được xử lý sau khi chúng tôi xác nhận thanh toán.';
    }

    if (!confirm(confirmMessage)) {
        return;
    }

    // Disable button and show loading
    const confirmBtn = event.target;
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

    // Make AJAX request with CSRF token
    fetch('/api/confirm-payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'order_code=' + encodeURIComponent(orderCode) + '&csrf_token=' + encodeURIComponent('<?= e($csrfToken) ?>')
    })
    .then(response => {
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Close modal
            const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            paymentModal.hide();

            // Update status badges if returned from API
            if (data.payment_status) {
                const paymentBadge = document.getElementById('payment-status-badge');
                const paymentStatusMap = {
                    'pending': { class: 'warning', label: 'Chờ thanh toán' },
                    'confirming': { class: 'info', label: 'Đang xác nhận' },
                    'paid': { class: 'success', label: 'Đã thanh toán' },
                    'completed': { class: 'success', label: 'Đã thanh toán' },
                    'failed': { class: 'danger', label: 'Thất bại' },
                    'refunded': { class: 'secondary', label: 'Đã hoàn tiền' }
                };
                const paymentInfo = paymentStatusMap[data.payment_status] || { class: 'secondary', label: data.payment_status };
                if (paymentBadge) {
                    paymentBadge.className = 'badge bg-' + paymentInfo.class + ' fs-6';
                    paymentBadge.textContent = paymentInfo.label;
                }
            }

            if (data.order_status) {
                const orderBadge = document.getElementById('order-status-badge');
                const orderStatusMap = {
                    'pending': { class: 'warning', label: 'Chờ thanh toán' },
                    'processing': { class: 'info', label: 'Đang xử lý', tooltip: 'Đơn hàng đang xử lý, sẽ thông báo cho bạn tới email hoặc trang web' },
                    'completed': { class: 'success', label: 'Hoàn thành' },
                    'cancelled': { class: 'danger', label: 'Đã hủy' }
                };
                const orderInfo = orderStatusMap[data.order_status] || { class: 'secondary', label: data.order_status };
                if (orderBadge) {
                    orderBadge.className = 'badge bg-' + orderInfo.class + ' fs-6';
                    orderBadge.textContent = orderInfo.label;

                    // Add tooltip if processing status
                    if (orderInfo.tooltip) {
                        orderBadge.setAttribute('data-bs-toggle', 'tooltip');
                        orderBadge.setAttribute('data-bs-placement', 'top');
                        orderBadge.setAttribute('title', orderInfo.tooltip);
                        // Initialize tooltip
                        new bootstrap.Tooltip(orderBadge);
                    }
                }
            }

            // Show success message
            alert(data.message || 'Cảm ơn bạn! Chúng tôi đã ghi nhận xác nhận của bạn.');

            // Reload page to update other UI elements
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            // Show error
            alert('Lỗi: ' + (data.message || 'Không thể xác nhận thanh toán. Vui lòng thử lại.'));

            // Re-enable button
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại sau.');

        // Re-enable button
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    });
}
</script>
<?php endif; ?>

<!-- Apple Style for Order Detail Page -->
<style>
/* Apple-inspired global styles for order detail */
.order-detail-container {
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Arial, sans-serif;
}

/* Badge pill style */
#order-status-badge {
    border-radius: 1000px !important;
    padding: 8px 18px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    letter-spacing: -0.01em !important;
    border: none !important;
    display: inline-block;
}

/* Card styling */
.order-detail-container .card {
    border: 1px solid #d2d2d7;
    border-radius: 18px;
    box-shadow: none;
    background: #fbfbfd;
}

.order-detail-container .card-header {
    background: #f5f5f7 !important;
    border-bottom: 1px solid #d2d2d7;
    border-radius: 18px 18px 0 0 !important;
    color: #1d1d1f !important;
    padding: 16px 20px;
}

.order-detail-container .card-header h5 {
    font-weight: 600;
    font-size: 17px;
    letter-spacing: -0.02em;
}

.order-detail-container .card-body {
    padding: 20px;
    background: #fbfbfd;
    border-radius: 0 0 18px 18px;
}

/* Remove hover effect for cards */
.order-detail-container .card {
    transition: none !important;
}

.order-detail-container .card:hover {
    transform: none !important;
    box-shadow: none !important;
}

/* Button styling */
.order-detail-container .btn-outline-primary {
    border-color: #0071e3;
    color: #0071e3;
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);
}

.order-detail-container .btn-outline-primary:hover {
    background: #0071e3;
    border-color: #0071e3;
    color: white;
}

.order-detail-container .btn-primary,
.order-detail-container .btn-success {
    background: #0071e3;
    border-color: #0071e3;
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);
}

.order-detail-container .btn-primary:hover,
.order-detail-container .btn-success:hover {
    background: #0077ed;
    border-color: #0077ed;
}

/* Badge styling */
.order-detail-container .badge {
    border-radius: 8px;
    padding: 6px 12px;
    font-weight: 500;
    letter-spacing: -0.01em;
}

.order-detail-container .badge.bg-warning {
    background: #ff9500 !important;
    color: white;
}

.order-detail-container .badge.bg-success {
    background: #34c759 !important;
}

.order-detail-container .badge.bg-info {
    background: #0071e3 !important;
}

.order-detail-container .badge.bg-danger {
    background: #ff3b30 !important;
}

.order-detail-container .badge.bg-secondary {
    background: #8e8e93 !important;
}

/* Alert styling */
.order-detail-container .alert {
    border-radius: 12px;
    border: 1px solid #d2d2d7;
    background: #f5f5f7;
}

.order-detail-container .alert-info {
    background: #e5f2ff;
    border-color: #0071e3;
    color: #1d1d1f;
}

.order-detail-container .alert-warning {
    background: #fff4e5;
    border-color: #ff9500;
    color: #1d1d1f;
}

.order-detail-container .alert-danger {
    background: #ffe5e5;
    border-color: #ff3b30;
    color: #1d1d1f;
}

.order-detail-container .alert-success {
    background: #e5ffe5;
    border-color: #34c759;
    color: #1d1d1f;
}

/* Table styling */
.order-detail-container .table {
    border-color: #d2d2d7;
}

.order-detail-container .table thead {
    background: #f5f5f7;
}

.order-detail-container .table th,
.order-detail-container .table td {
    border-color: #e5e5ea;
    color: #1d1d1f;
}

/* Text styling */
.order-detail-container strong {
    color: #1d1d1f;
    font-weight: 600;
}

.order-detail-container p {
    color: #1d1d1f;
}

.order-detail-container .text-muted {
    color: #86868b !important;
}

/* Input styling */
.order-detail-container .form-control {
    border: 1px solid #d2d2d7;
    border-radius: 10px;
    padding: 10px 14px;
    transition: all 0.2s ease;
}

.order-detail-container .form-control:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
}

/* Guest registration banner */
.order-detail-container .alert-info.guest-banner {
    background: linear-gradient(135deg, #0071e3 0%, #0077ed 100%);
    border: none;
    color: white;
}

.order-detail-container .alert-info.guest-banner h5,
.order-detail-container .alert-info.guest-banner p,
.order-detail-container .alert-info.guest-banner strong {
    color: white;
}

/* Modal styling */
.order-detail-container .modal-content {
    border-radius: 18px;
    border: 1px solid #d2d2d7;
}

.order-detail-container .modal-header {
    border-radius: 18px 18px 0 0;
    border-bottom: 1px solid #d2d2d7;
}

.order-detail-container .modal-body {
    background: #fbfbfd;
}

/* Image styling */
.order-detail-container img {
    border-radius: 8px;
}

/* Product images in table */
.order-detail-container .table img {
    border-radius: 6px;
}

/* Invitation Emails Card - Apple Style */
.order-detail-container .invitation-emails-card {
    background: #f5f5f7;
    border: 1px solid #d2d2d7;
    border-radius: 18px;
    padding: 0;
    margin-bottom: 20px;
    overflow: hidden;
}

.order-detail-container .invitation-header {
    background: linear-gradient(135deg, #0071e3 0%, #0077ed 100%);
    color: white;
    padding: 16px 20px;
    font-weight: 600;
    font-size: 15px;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.order-detail-container .invitation-header i {
    font-size: 18px;
}

.order-detail-container .invitation-body {
    padding: 20px;
    background: white;
}

.order-detail-container .invitation-product-group {
    margin-bottom: 20px;
}

.order-detail-container .invitation-product-group:last-child {
    margin-bottom: 0;
}

.order-detail-container .invitation-product-group .product-name {
    font-size: 14px;
    font-weight: 600;
    color: #1d1d1f;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e5e5ea;
}

.order-detail-container .invitation-email-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: #f5f5f7;
    border-radius: 10px;
    margin-bottom: 8px;
    transition: all 0.2s ease;
}

.order-detail-container .invitation-email-item:last-child {
    margin-bottom: 0;
}

.order-detail-container .invitation-email-item:hover {
    background: #e8e8ed;
}

.order-detail-container .invitation-email-item i {
    color: #0071e3;
    font-size: 14px;
    flex-shrink: 0;
}

.order-detail-container .invitation-email-item .email-text {
    font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
    font-size: 13px;
    color: #1d1d1f;
    font-weight: 500;
    word-break: break-all;
}
</style>

<!-- Order Timeline CSS -->
<style>
.order-timeline-wrapper {
    background: #fbfbfd;
    border: 1px solid #d2d2d7;
    border-radius: 18px;
    padding: 30px 20px;
}

.order-timeline {
    display: flex;
    justify-content: center;
    align-items: center;
    max-width: 600px;
    margin: 0 auto;
    position: relative;
}

.timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
    flex: 0 0 auto;
}

.timeline-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #f5f5f7;
    border: 2px solid #d2d2d7;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #86868b;
    font-size: 18px;
    margin-bottom: 10px;
    transition: all 0.4s cubic-bezier(0.28, 0.11, 0.32, 1);
}

.timeline-step.completed .timeline-icon {
    background: #0071e3;
    border-color: #0071e3;
    color: white;
}

.timeline-step.active .timeline-icon {
    background: #0071e3;
    border-color: #0071e3;
    color: white;
    animation: appleGlow 2s ease-in-out infinite;
}

@keyframes appleGlow {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 2px 8px rgba(0, 113, 227, 0.3);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 4px 16px rgba(0, 113, 227, 0.5);
    }
}

.timeline-title {
    color: #6e6e73;
    font-weight: 500;
    font-size: 14px;
    text-align: center;
    letter-spacing: -0.01em;
}

.timeline-step.completed .timeline-title {
    color: #1d1d1f;
    font-weight: 600;
}

.timeline-step.active .timeline-title {
    color: #0071e3;
    font-weight: 600;
}

.timeline-connector {
    flex: 1;
    height: 2px;
    background: #d2d2d7;
    margin: 0 20px;
    margin-bottom: 34px;
    position: relative;
    z-index: 1;
    transition: all 0.4s cubic-bezier(0.28, 0.11, 0.32, 1);
    max-width: 120px;
}

.timeline-connector.completed {
    background: #0071e3;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .order-timeline-wrapper {
        padding: 25px 15px;
        border-radius: 16px;
    }

    .order-timeline {
        max-width: 100%;
    }

    .timeline-icon {
        width: 44px;
        height: 44px;
        font-size: 17px;
    }

    .timeline-title {
        font-size: 13px;
    }

    .timeline-connector {
        margin: 0 15px;
        margin-bottom: 30px;
        max-width: 80px;
    }
}

@media (max-width: 480px) {
    .order-timeline-wrapper {
        padding: 22px 12px;
        border-radius: 14px;
    }

    .timeline-icon {
        width: 42px;
        height: 42px;
        font-size: 16px;
        margin-bottom: 8px;
    }

    .timeline-title {
        font-size: 12px;
    }

    .timeline-connector {
        margin: 0 12px;
        margin-bottom: 28px;
        max-width: 60px;
    }
}
</style>

<!-- Star Rating CSS & JavaScript -->
<?php if ($order['order_status'] === 'completed' && !$isGuest): ?>
<style>
.star-rating-input {
    display: inline-flex;
    flex-direction: row-reverse;
    gap: 3px;
}

.star-rating-input input[type="radio"] {
    display: none;
}

.star-rating-input label {
    cursor: pointer;
    font-size: 24px;
    color: #ddd;
    transition: color 0.2s;
    margin: 0;
}

.star-rating-input label:hover,
.star-rating-input label:hover ~ label,
.star-rating-input input[type="radio"]:checked ~ label {
    color: #ffc107;
}

.comment-suggest-detail-btn.active {
    font-weight: 600;
    background-color: var(--bs-success);
    color: white;
    border-color: var(--bs-success);
}

.comment-suggest-detail-btn {
    transition: all 0.2s ease;
}

/* Modal sizing for all screens */
@media (min-width: 768px) {
    #paymentModal .payment-modal-bank_transfer {
        max-width: 600px;
    }

    #paymentModal .payment-modal-momo,
    #paymentModal .payment-modal-wallet {
        max-width: 450px;
    }

    #accountModal .modal-dialog {
        max-width: 500px;
    }
}

@media (max-width: 767px) {
    #paymentModal .modal-dialog,
    .modal-dialog[style*="max-width"] {
        max-width: 95% !important;
        margin: 1rem auto;
    }

    #paymentModal .modal-title {
        font-size: 15px !important;
    }

    #paymentModal .modal-header {
        padding: 14px 16px !important;
    }
}

/* Desktop: Always show QR section */
@media (min-width: 992px) {
    .qr-code-section {
        display: block !important;
    }

    .mobile-qr-toggle {
        display: none;
    }
}

/* Mobile Payment Modal Styles */
@media (max-width: 767px) {
    /* Show QR when toggled - override Bootstrap d-none */
    #qrCodeSection.show {
        display: block !important;
        width: 100% !important;
        margin-top: 1rem !important;
    }

    /* Hide payment info when QR is shown */
    #paymentInfoSection.hide {
        display: none !important;
    }

    /* Make payment info full width on mobile */
    .payment-info-box {
        margin-bottom: 0;
    }

    /* Mobile QR toggle button */
    .mobile-qr-toggle {
        text-align: center;
    }

    /* QR Code image larger on mobile */
    .qr-box {
        padding: 0.5rem;
        overflow: hidden;
    }

    .qr-box img {
        max-width: none;
        width: 130%;
        height: auto;
        margin-left: -15%;
        margin-right: -15%;
        display: block;
    }

    .qr-box h6 {
        font-size: 16px;
        margin-bottom: 1rem;
    }

    .qr-box .qr-note {
        font-size: 13px;
        margin-top: 0.5rem;
    }

    /* Modal footer buttons in row */
    #paymentModal .modal-footer {
        display: flex !important;
        flex-direction: row !important;
        gap: 8px;
        padding: 12px;
        justify-content: space-between;
    }

    #paymentModal .modal-footer .btn {
        flex: 1;
        font-size: 13px;
        padding: 10px 6px;
        white-space: nowrap;
    }

    #paymentModal .modal-footer .btn i {
        font-size: 12px;
    }

    /* Adjust modal body padding */
    #paymentModal .modal-body {
        padding: 1rem;
    }

    /* Payment amount */
    .payment-amount h5 {
        font-size: 1.2rem;
        margin-bottom: 1rem;
    }

    /* QR box styling */
    .qr-box {
        text-align: center;
        padding: 1rem;
    }
}

/* SF Pro Text font for entire order detail page */
.container, .card, .card-body, .card-header, .card-title, .card-text,
.modal, .modal-header, .modal-body, .modal-footer, .modal-title,
.alert, .btn, .table, .badge, .text-muted, .form-label, .form-control,
h1, h2, h3, h4, h5, h6, p, span, div, a, td, th, li {
    font-family: SF Pro Text, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
}
</style>

<script>
// Star rating text update
document.addEventListener('DOMContentLoaded', function() {
    const ratingTexts = {
        1: 'Rất tệ',
        2: 'Tệ',
        3: 'Bình thường',
        4: 'Tốt',
        5: 'Rất tốt'
    };

    document.querySelectorAll('[name^="rating"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const itemId = this.name.replace('rating', '');
            const textElement = document.getElementById('ratingText' + itemId);
            if (textElement) {
                textElement.textContent = ratingTexts[this.value] || 'Vui lòng chọn số sao';
            }
        });
    });

    // Comment suggestion buttons
    document.querySelectorAll('.comment-suggest-detail-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const comment = this.dataset.comment;
            const commentBox = document.querySelector('.comment-textarea-' + itemId);

            if (this.classList.contains('active')) {
                // Remove comment
                this.classList.remove('active');
                const currentComments = commentBox.value.split(', ').filter(c => c.trim() !== '');
                const newComments = currentComments.filter(c => c !== comment);
                commentBox.value = newComments.join(', ');
            } else {
                // Add comment
                this.classList.add('active');
                if (commentBox.value.trim() === '') {
                    commentBox.value = comment;
                } else {
                    commentBox.value += ', ' + comment;
                }
            }
        });
    });
});

// Submit review function
function submitReview(itemId) {
    const form = document.getElementById('reviewForm' + itemId);
    const orderId = form.dataset.orderId;
    const productId = form.dataset.productId;
    const rating = document.querySelector('[name="rating' + itemId + '"]:checked');
    const comment = document.getElementById('comment' + itemId).value;

    // Validate
    if (!rating) {
        alert('Vui lòng chọn số sao đánh giá');
        return;
    }

    // Disable submit button
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';

    // Submit via AJAX
    fetch('/api/submit-review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&product_id=${productId}&rating=${rating.value}&comment=${encodeURIComponent(comment)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal' + itemId));
            modal.hide();

            // Show success message
            alert(data.message);

            // Reload page to show updated review status
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Lỗi: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
