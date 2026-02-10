<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
initSession();

// Cho phép cả khách và user đã đăng nhập
$user = getCurrentUser();
$isGuest = !isLoggedIn();

// Kiểm tra nếu là mua ngay (direct checkout)
$isDirect = isset($_GET['direct']) && isset($_SESSION['buy_now_product']);

// Nếu có tham số direct nhưng không có session, redirect về trang chủ
if (isset($_GET['direct']) && !isset($_SESSION['buy_now_product'])) {
    setFlash('error', 'Phiên mua hàng đã hết hạn. Vui lòng thử lại.');
    redirect('/');
    exit;
}

$cartItems = [];
$subtotal = 0;

if ($isDirect) {
    // Mua ngay - lấy từ session buy_now
    $buyNowData = $_SESSION['buy_now_product'];
    $productId = $buyNowData['product_id'];
    $variantId = $buyNowData['variant_id'] ?? null;
    $quantity = $buyNowData['quantity'];

    // Lấy thông tin product và variant
    try {
        $stmt = db()->query(
            "SELECT p.*, v.id as variant_id, v.name as variant_name, v.price as variant_price,
                    v.sale_price as variant_sale_price, v.variant_title, v.variant_image, v.delivery_type as variant_delivery_type,
                    (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
             FROM products p
             LEFT JOIN product_variants v ON p.id = v.product_id AND v.id = ?
             WHERE p.id = ? AND p.status = 'active'",
            [$variantId, $productId]
        );
        $row = $stmt->fetch();

        if ($row) {
            // Xác định giá, tên hiển thị và delivery_type
            if ($row['variant_id']) {
                $price = $row['variant_sale_price'] ?? $row['variant_price'];
                $displayName = !empty($row['variant_title']) ? $row['variant_title'] : ($row['name'] . ' - ' . $row['variant_name']);
                $deliveryType = $row['variant_delivery_type'] ?? $row['delivery_type'];
            } else {
                $price = $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'];
                $displayName = $row['name'];
                $deliveryType = $row['delivery_type'];
            }

            // Xác định ảnh hiển thị (ưu tiên: variant_image > first_gallery_image > product_image)
            $image = '';
            if (!empty($row['variant_image'])) {
                $image = $row['variant_image'];
            } elseif (!empty($row['first_gallery_image'])) {
                $image = $row['first_gallery_image'];
            } elseif (!empty($row['image'])) {
                $image = $row['image'];
            }

            $quantity = (int)$quantity;
            $itemTotal = $price * $quantity;
            $cartItems[] = [
                'product_id' => $productId,
                'variant_id' => $row['variant_id'],
                'name' => $displayName,
                'image' => $image,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $itemTotal,
                'stock_quantity' => $row['stock_quantity'] ?? 0,
                'delivery_type' => $deliveryType
            ];
            $subtotal += $itemTotal;
        }
    } catch (Exception $e) {
        error_log("Direct checkout error: " . $e->getMessage());
    }
} else {
    // Checkout bình thường từ giỏ hàng
    $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
    $sessionId = session_id();

    try {
        if ($userId) {
            $stmt = db()->query(
                "SELECT c.*, p.name, p.price, p.sale_price, p.image, p.stock_quantity, p.status, p.delivery_type,
                        v.id as variant_id, v.name as variant_name, v.price as variant_price,
                        v.sale_price as variant_sale_price, v.variant_title, v.variant_image, v.delivery_type as variant_delivery_type,
                        (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
                 FROM cart c
                 JOIN products p ON c.product_id = p.id
                 LEFT JOIN product_variants v ON c.variant_id = v.id
                 WHERE c.user_id = ? AND p.status = 'active'",
                [$userId]
            );
        } else {
            $stmt = db()->query(
                "SELECT c.*, p.name, p.price, p.sale_price, p.image, p.stock_quantity, p.status, p.delivery_type,
                        v.id as variant_id, v.name as variant_name, v.price as variant_price,
                        v.sale_price as variant_sale_price, v.variant_title, v.variant_image, v.delivery_type as variant_delivery_type,
                        (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
                 FROM cart c
                 JOIN products p ON c.product_id = p.id
                 LEFT JOIN product_variants v ON c.variant_id = v.id
                 WHERE c.session_id = ? AND c.user_id IS NULL AND p.status = 'active'",
                [$sessionId]
            );
        }

        while ($row = $stmt->fetch()) {
            // Xác định giá, tên hiển thị và delivery_type
            if ($row['variant_id']) {
                $price = $row['variant_sale_price'] ?? $row['variant_price'];
                $displayName = !empty($row['variant_title']) ? $row['variant_title'] : ($row['name'] . ' - ' . $row['variant_name']);
                $deliveryType = $row['variant_delivery_type'] ?? $row['delivery_type'];
            } else {
                $price = $row['sale_price'] > 0 ? $row['sale_price'] : $row['price'];
                $displayName = $row['name'];
                $deliveryType = $row['delivery_type'];
            }

            // Xác định ảnh hiển thị (ưu tiên: variant_image > first_gallery_image > product_image)
            $image = '';
            if (!empty($row['variant_image'])) {
                $image = $row['variant_image'];
            } elseif (!empty($row['first_gallery_image'])) {
                $image = $row['first_gallery_image'];
            } elseif (!empty($row['image'])) {
                $image = $row['image'];
            }

            $quantity = $row['quantity'];
            $itemTotal = $price * $quantity;

            $cartItems[] = [
                'product_id' => $row['product_id'],
                'variant_id' => $row['variant_id'],
                'name' => $displayName,
                'image' => $image,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $itemTotal,
                'stock_quantity' => $row['stock_quantity'] ?? 0,
                'delivery_type' => $deliveryType
            ];

            $subtotal += $itemTotal;
        }
    } catch (Exception $e) {
        error_log("Checkout cart error: " . $e->getMessage());
    }

    // Nếu giỏ hàng trống
    if (empty($cartItems)) {
        setFlash('warning', 'Giỏ hàng của bạn đang trống');
        redirect('/cart.php');
    }
}

// Nếu không có sản phẩm hợp lệ trong giỏ hàng
if (empty($cartItems)) {
    setFlash('error', 'Không tìm thấy sản phẩm trong giỏ hàng hoặc sản phẩm đã ngừng bán');
    redirect('/products');
}

$discount = 0;
$couponCode = '';
$total = $subtotal - $discount;

// Xử lý xóa mã giảm giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_coupon'])) {
    unset($_SESSION['applied_coupon']);
    setFlash('success', 'Đã xóa mã giảm giá');
    redirect('/checkout.php');
}

// Xử lý áp dụng mã giảm giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $couponCode = trim($_POST['coupon_code']);
    if (!empty($couponCode)) {
        $stmt = db()->query(
            "SELECT * FROM coupons WHERE code = ? AND status = 'active'
             AND (start_date IS NULL OR start_date <= NOW())
             AND (end_date IS NULL OR end_date >= NOW())
             AND (usage_limit IS NULL OR used_count < usage_limit)",
            [$couponCode]
        );
        $coupon = $stmt->fetch();

        if ($coupon) {
            if ($subtotal >= $coupon['min_order_amount']) {
                if ($coupon['type'] === 'percent') {
                    $discount = ($subtotal * $coupon['value']) / 100;
                    if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                        $discount = $coupon['max_discount'];
                    }
                } else {
                    $discount = $coupon['value'];
                }
                $total = $subtotal - $discount;
                $_SESSION['applied_coupon'] = $coupon;
                setFlash('success', 'Áp dụng mã giảm giá thành công!');
            } else {
                setFlash('error', 'Đơn hàng tối thiểu ' . formatMoney($coupon['min_order_amount']) . ' để sử dụng mã này');
            }
        } else {
            setFlash('error', 'Mã giảm giá không hợp lệ hoặc đã hết hạn');
        }
    }
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $paymentMethod = $_POST['payment_method'] ?? 'bank_transfer';
    $customerEmail = trim($_POST['customer_email'] ?? ($user ? $user['email'] : ''));
    $customerName = trim($_POST['customer_name'] ?? ($user ? $user['full_name'] ?? $user['username'] : ''));
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $customerNote = trim($_POST['customer_note'] ?? '');
    $customerAccounts = $_POST['customer_account'] ?? [];
    $invitationEmails = $_POST['invitation_emails'] ?? [];

    // Tính lại discount từ session trước khi đặt hàng
    $orderDiscount = 0;
    if (isset($_SESSION['applied_coupon'])) {
        $coupon = $_SESSION['applied_coupon'];
        if ($coupon['type'] === 'percent') {
            $orderDiscount = ($subtotal * $coupon['value']) / 100;
            if ($coupon['max_discount'] && $orderDiscount > $coupon['max_discount']) {
                $orderDiscount = $coupon['max_discount'];
            }
        } else {
            $orderDiscount = $coupon['value'];
        }
    }
    $orderTotal = $subtotal - $orderDiscount;

    // Validate
    $errors = [];

    // Nếu là guest, bắt buộc nhập đầy đủ thông tin
    if ($isGuest) {
        if (empty($customerName)) {
            $errors[] = 'Vui lòng nhập họ tên';
        }
        if (empty($customerEmail)) {
            $errors[] = 'Vui lòng nhập email';
        }
        if (empty($customerPhone)) {
            $errors[] = 'Vui lòng nhập số điện thoại';
        }
        // Guest chỉ được dùng chuyển khoản
        if ($paymentMethod === 'wallet') {
            $errors[] = 'Vui lòng đăng nhập để sử dụng ví tiền';
        }
    }

    if (!isValidEmail($customerEmail)) {
        $errors[] = 'Email không hợp lệ';
    }

    // Kiểm tra số lượng tồn kho
    foreach ($cartItems as $item) {
        if ($item['stock_quantity'] < $item['quantity']) {
            $errors[] = 'Sản phẩm "' . $item['name'] . '" không đủ số lượng';
        }
    }

    if (empty($errors)) {
        try {
            db()->getConnection()->beginTransaction();

            // Tạo mã đơn hàng
            $orderCode = generateOrderCode();

            // Tạo đơn hàng (hỗ trợ guest với user_id = NULL)
            $userId = $isGuest ? null : $user['id'];

            $stmt = db()->query(
                "INSERT INTO orders (user_id, order_code, total_amount, discount_amount, final_amount,
                 payment_method, payment_status, order_status, customer_email, customer_note, customer_name, customer_phone)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, ?)",
                [$userId, $orderCode, $subtotal, $orderDiscount, $orderTotal,
                 $paymentMethod, $customerEmail, $customerNote, $customerName, $customerPhone]
            );

            if (!$stmt) {
                throw new Exception("Lỗi khi tạo đơn hàng");
            }

            $orderId = db()->lastInsertId();

            // Thêm chi tiết đơn hàng và xử lý tài khoản
            foreach ($cartItems as $item) {
                $productId = $item['product_id'];
                $variantId = $item['variant_id'] ?? null;

                // Chuẩn bị thông tin khách hàng (tài khoản hoặc email nhận lời mời)
                $customerAccountInfo = null;
                $deliveryType = $item['delivery_type'] ?? 'account';

                if ($deliveryType === 'email_only' && isset($invitationEmails[$productId])) {
                    // Lưu danh sách email nhận lời mời
                    $customerAccountInfo = json_encode([
                        'type' => 'email_only',
                        'invitation_emails' => $invitationEmails[$productId]
                    ]);
                } elseif ($deliveryType === 'customer_account' && isset($customerAccounts[$productId])) {
                    // Lưu thông tin tài khoản khách hàng
                    $customerAccountInfo = json_encode([
                        'type' => 'customer_account',
                        'username' => $customerAccounts[$productId]['username'] ?? '',
                        'password' => $customerAccounts[$productId]['password'] ?? ''
                    ]);
                }

                // Thêm order item (lưu tên đầy đủ của variant nếu có, variant_id và image)
                // Kiểm tra xem cột variant_id có tồn tại không
                $columns = db()->query("SHOW COLUMNS FROM order_items LIKE 'variant_id'")->fetch();
                $hasVariantId = !empty($columns);

                if ($hasVariantId) {
                    // Insert với variant_id và image
                    $itemStmt = db()->query(
                        "INSERT INTO order_items (order_id, product_id, variant_id, product_name, image, quantity, price, customer_account_info)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$orderId, $productId, $item['variant_id'] ?? null, $item['name'], $item['image'] ?? null, $item['quantity'], $item['price'], $customerAccountInfo]
                    );
                } else {
                    // Insert không có variant_id và image (backward compatible)
                    $itemStmt = db()->query(
                        "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, customer_account_info)
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [$orderId, $productId, $item['name'], $item['quantity'], $item['price'], $customerAccountInfo]
                    );
                }

                if (!$itemStmt) {
                    throw new Exception("Lỗi khi thêm sản phẩm vào đơn hàng: " . $item['name']);
                }

                $orderItemId = db()->lastInsertId();

                // Nếu có tài khoản có sẵn trong kho, tự động giao
                try {
                    for ($i = 0; $i < $item['quantity']; $i++) {
                        // Lấy tài khoản có sẵn
                        $stmt = db()->query(
                            "SELECT * FROM accounts_stock
                             WHERE product_id = ? AND status = 'available'
                             LIMIT 1",
                            [$productId]
                        );
                        $account = $stmt->fetch();

                        if ($account) {
                            // Cập nhật trạng thái tài khoản
                            db()->query(
                                "UPDATE accounts_stock
                                 SET status = 'sold', sold_to_order_id = ?, sold_at = NOW()
                                 WHERE id = ?",
                                [$orderId, $account['id']]
                            );

                            // Cập nhật thông tin giao hàng
                            $accountInfo = "Username: " . $account['username'] . "\n";
                            $accountInfo .= "Password: " . $account['password'] . "\n";
                            if ($account['additional_info']) {
                                $accountInfo .= "Ghi chú: " . $account['additional_info'];
                            }

                            db()->query(
                                "UPDATE order_items
                                 SET account_delivered = ?
                                 WHERE id = ?",
                                [$accountInfo, $orderItemId]
                            );

                            // Giảm tồn kho
                            db()->query(
                                "UPDATE products SET stock_quantity = stock_quantity - 1
                                 WHERE id = ?",
                                [$productId]
                            );
                        } else {
                            // Không còn tài khoản trong kho, dừng lại
                            break;
                        }
                    }
                } catch (Exception $e) {
                    // Bảng accounts_stock có thể chưa tồn tại, bỏ qua
                    error_log("Auto delivery error: " . $e->getMessage());
                }
            }

            // Cập nhật sử dụng coupon
            if (isset($_SESSION['applied_coupon'])) {
                $coupon = $_SESSION['applied_coupon'];

                // Try to log coupon usage (optional table)
                try {
                    db()->query(
                        "INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount)
                         VALUES (?, ?, ?, ?)",
                        [$coupon['id'], $userId, $orderId, $orderDiscount]
                    );
                } catch (Exception $e) {
                    // Ignore if table doesn't exist
                }

                // Update coupon used count
                try {
                    db()->query(
                        "UPDATE coupons SET used_count = used_count + 1 WHERE id = ?",
                        [$coupon['id']]
                    );
                } catch (Exception $e) {
                    // Ignore if column doesn't exist
                }

                unset($_SESSION['applied_coupon']);
            }

            // Tạo thông báo đơn hàng thành công (chỉ cho user đã đăng nhập)
            if (!$isGuest) {
                createNotification(
                    $user['id'],
                    'Đơn hàng đã được tạo thành công!',
                    'Đơn hàng ' . $orderCode . ' của bạn đã được tiếp nhận. Tổng tiền: ' . formatMoney($orderTotal) . '. Chúng tôi sẽ xử lý và giao hàng sớm nhất.',
                    'success',
                    '/order-detail?code=' . $orderCode
                );
            }

            db()->getConnection()->commit();

            // Gửi thông báo Telegram về đơn hàng mới
            try {
                // Chuẩn bị thông tin sản phẩm để gửi thông báo
                $orderItems = [];
                foreach ($cartItems as $item) {
                    $orderItems[] = [
                        'name' => $item['name'] ?? $item['product_name'],
                        'variant_name' => $item['variant_name'] ?? '',
                        'quantity' => $item['quantity'],
                        'total_price' => $item['subtotal']
                    ];
                }

                // Tên phương thức thanh toán
                $paymentMethodName = $paymentMethod === 'wallet' ? 'Ví điện tử' :
                                   ($paymentMethod === 'bank' ? 'Chuyển khoản' :
                                   ($paymentMethod === 'momo' ? 'Momo' : $paymentMethod));

                $messageId = sendNewOrderTelegramNotification(
                    $orderCode,
                    $customerName,
                    $customerEmail,
                    $customerPhone,
                    $orderTotal,
                    $paymentMethodName,
                    $orderItems
                );

                // Lưu message_id vào database
                if ($messageId && is_numeric($messageId)) {
                    try {
                        db()->query(
                            "UPDATE orders SET telegram_message_id = ? WHERE order_code = ?",
                            [$messageId, $orderCode]
                        );
                    } catch (Exception $e) {
                        // Bỏ qua nếu cột chưa tồn tại
                        error_log("Failed to save telegram_message_id: " . $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                // Không làm gì nếu gửi telegram thất bại, đơn hàng vẫn được tạo
                error_log("Telegram notification error: " . $e->getMessage());
            }

            // Xóa giỏ hàng
            if ($isDirect) {
                // Xóa session buy_now
                unset($_SESSION['buy_now_product']);
            } else {
                // Xóa giỏ hàng thông thường
                clearCart();
            }

            // Xóa thông tin bổ sung đã lưu
            unset($_SESSION['product_additional_info']);

            setFlash('success', 'Đặt hàng thành công! Mã đơn hàng: ' . $orderCode);
            redirect('/order-detail?code=' . $orderCode);

        } catch (Exception $e) {
            db()->getConnection()->rollBack();
            setFlash('error', 'Có lỗi xảy ra khi tạo đơn hàng: ' . $e->getMessage());
            redirect('/checkout.php');
        }
    } else {
        foreach ($errors as $error) {
            setFlash('error', $error);
        }
        redirect('/checkout.php');
    }
}

$appliedCoupon = $_SESSION['applied_coupon'] ?? null;
if ($appliedCoupon) {
    if ($appliedCoupon['type'] === 'percent') {
        $discount = ($subtotal * $appliedCoupon['value']) / 100;
        if ($appliedCoupon['max_discount'] && $discount > $appliedCoupon['max_discount']) {
            $discount = $appliedCoupon['max_discount'];
        }
    } else {
        $discount = $appliedCoupon['value'];
    }
    $total = $subtotal - $discount;
    $couponCode = $appliedCoupon['code'];
}

$pageTitle = 'Thanh toán';
$csrfToken = generateCSRFToken();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container order-detail-container">
    <h1 class="mb-4"><i class="fas fa-credit-card"></i> Thanh toán</h1>

    <!-- Timeline tiến trình đơn hàng -->
    <div class="order-timeline-wrapper mb-4">
        <div class="order-timeline">
            <!-- Bước 1: Đặt hàng -->
            <div class="timeline-step completed active">
                <div class="timeline-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="timeline-title">Đặt hàng</div>
            </div>

            <!-- Connector 1 -->
            <div class="timeline-connector"></div>

            <!-- Bước 2: Thanh toán -->
            <div class="timeline-step">
                <div class="timeline-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="timeline-title">Thanh toán</div>
            </div>

            <!-- Connector 2 -->
            <div class="timeline-connector"></div>

            <!-- Bước 3: Đang xử lý -->
            <div class="timeline-step">
                <div class="timeline-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="timeline-title">Đang xử lý</div>
            </div>
        </div>
    </div>

    <?php if ($isGuest): ?>
    <!-- Khuyến khích đăng nhập/đăng ký -->
    <div class="alert alert-info border-info" style="background: linear-gradient(135deg, #667eea20 0%, #7387df20 100%);">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-2"><i class="fas fa-info-circle"></i> Đặt hàng không cần tài khoản</h5>
                <p class="mb-2">Bạn có thể đặt hàng như khách, nhưng sẽ nhận được nhiều ưu đãi hơn khi đăng ký:</p>
                <ul class="mb-0 small">
                    <li>Theo dõi đơn hàng dễ dàng</li>
                    <li>Nhận thông báo trực tiếp</li>
                    <li>Sử dụng ví tiền, mã giảm giá</li>
                    <li>Lưu lịch sử mua hàng</li>
                </ul>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="/login?redirect=/checkout" class="pill-button pill-button-blue mb-2 d-inline-block">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </a>
                <br class="d-none d-md-block">
                <a href="/register?redirect=/checkout" class="pill-button pill-button-gray d-inline-block">
                    <i class="fas fa-user-plus"></i> Đăng ký ngay
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form thanh toán -->
        <div class="col-lg-8">
            <form method="POST" id="checkoutForm">
                <!-- Thông tin khách hàng -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-user"></i> Thông tin người đặt hàng</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($isGuest): ?>
                        <!-- Form cho khách không đăng nhập -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Họ tên <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="customer_name" class="form-control"
                                       placeholder="Nhập họ và tên của bạn" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-envelope"></i> Email
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="customer_email" class="form-control"
                                       placeholder="email@example.com" required>
                                <small class="text-muted">Email để nhận tài khoản và thông tin đơn hàng</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-phone"></i> Số điện thoại
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="tel" name="customer_phone" class="form-control"
                                       placeholder="0848877758" required>
                                <small class="text-muted">Số điện thoại để liên hệ khi cần</small>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Form cho user đã đăng nhập -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ tên</label>
                                <input type="text" class="form-control" value="<?= e($user['full_name'] ?? $user['username']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-envelope"></i> Email liên hệ
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="customer_email" class="form-control"
                                       value="<?= e($user['email']) ?>" required>
                                <small class="text-muted">Email để liên hệ và nhận thông báo đơn hàng</small>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú đơn hàng</label>
                            <textarea name="customer_note" class="form-control" rows="3"
                                      placeholder="Ghi chú thêm cho đơn hàng (nếu có)"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Email nhận lời mời (cho sản phẩm email_only) -->
                <?php
                $emailOnlyItems = [];
                foreach ($cartItems as $item) {
                    if (($item['delivery_type'] ?? 'account') === 'email_only') {
                        $emailOnlyItems[] = $item;
                    }
                }
                ?>

                <?php if (!empty($emailOnlyItems)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-users"></i> Email nhận lời mời nhóm gia đình
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($emailOnlyItems as $item): ?>
                        <div class="card mb-3 border-primary">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($item['name']) ?></strong>
                                    <?php if ($item['quantity'] > 1): ?>
                                        <span class="badge bg-secondary">x<?= $item['quantity'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-info">
                                    <i class="fas fa-envelope"></i> Cần Email
                                </span>
                            </div>
                            <div class="card-body">
                                <?php
                                // Lấy email đã lưu từ session (nếu có)
                                $savedEmail = '';
                                if (isset($_SESSION['product_additional_info'][$item['product_id']]['email'])) {
                                    $savedEmail = $_SESSION['product_additional_info'][$item['product_id']]['email'];
                                }
                                ?>
                                <?php for ($i = 0; $i < $item['quantity']; $i++): ?>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i>
                                        <?php if ($item['quantity'] > 1): ?>
                                            Email người nhận #<?= $i + 1 ?>
                                        <?php else: ?>
                                            Email người nhận lời mời
                                        <?php endif; ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="email"
                                           name="invitation_emails[<?= $item['product_id'] ?>][<?= $i ?>]"
                                           class="form-control border-info"
                                           placeholder="Nhập email người nhận lời mời"
                                           value="<?= $i === 0 && !empty($savedEmail) ? e($savedEmail) : '' ?>"
                                           required>
                                    <small class="text-muted">
                                        Lời mời tham gia <?= e($item['name']) ?> sẽ được gửi đến email này
                                    </small>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lưu ý:</strong> Vui lòng kiểm tra kỹ email trước khi đặt hàng. Lời mời chỉ có thể gửi đến email bạn cung cấp.
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Thông tin tài khoản khách hàng (cho sản phẩm nâng cấp) -->
                <?php
                $hasCustomerAccountProduct = false;
                foreach ($cartItems as $item) {
                    if (($item['delivery_type'] ?? 'account') === 'customer_account') {
                        $hasCustomerAccountProduct = true;
                        break;
                    }
                }
                ?>

                <?php if ($hasCustomerAccountProduct): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-user-edit"></i> Thông tin tài khoản của bạn
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Một số sản phẩm trong giỏ hàng yêu cầu bạn cung cấp tài khoản để chúng tôi nâng cấp.
                            Vui lòng nhập thông tin tài khoản của bạn bên dưới.
                        </div>

                        <?php foreach ($cartItems as $index => $item):
                            $deliveryType = $item['delivery_type'] ?? 'account';
                            if ($deliveryType === 'customer_account'):
                        ?>
                        <div class="card mb-3 border-primary">
                            <div class="card-header bg-light">
                                <strong><?= e($item['name']) ?></strong>
                                <span class="badge bg-warning ms-2">
                                    <i class="fas fa-user-edit"></i> Cần tài khoản của bạn
                                </span>
                            </div>
                            <div class="card-body">
                                <?php
                                // Lấy thông tin tài khoản đã lưu từ session (nếu có)
                                $savedUsername = '';
                                $savedPassword = '';
                                if (isset($_SESSION['product_additional_info'][$item['product_id']])) {
                                    $savedUsername = $_SESSION['product_additional_info'][$item['product_id']]['username'] ?? '';
                                    $savedPassword = $_SESSION['product_additional_info'][$item['product_id']]['password'] ?? '';
                                }
                                ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i> Username/Email tài khoản
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                               name="customer_account[<?= $item['product_id'] ?>][username]"
                                               class="form-control"
                                               placeholder="Nhập username hoặc email tài khoản của bạn"
                                               value="<?= e($savedUsername) ?>"
                                               required>
                                        <small class="text-muted">
                                            Tài khoản này sẽ được nâng cấp lên <?= e($item['name']) ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-key"></i> Password
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="password"
                                               name="customer_account[<?= $item['product_id'] ?>][password]"
                                               class="form-control"
                                               placeholder="Nhập mật khẩu tài khoản"
                                               value="<?= e($savedPassword) ?>"
                                               required>
                                        <small class="text-muted">
                                            Mật khẩu để đăng nhập vào tài khoản của bạn
                                        </small>
                                    </div>
                                </div>
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-shield-alt"></i>
                                    <strong>Bảo mật:</strong> Thông tin tài khoản của bạn được mã hóa và chỉ dùng để nâng cấp dịch vụ.
                                    Chúng tôi cam kết không lưu trữ mật khẩu sau khi hoàn tất.
                                </div>
                            </div>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Hidden input for payment method -->
                <input type="hidden" name="payment_method" id="selected_payment_method" value="bank_transfer">
            </form>
        </div>

        <!-- Sidebar thanh toán -->
        <div class="col-lg-4">
            <!-- Đơn hàng của bạn -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-shopping-cart"></i> Đơn hàng của bạn</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Tổng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td>
                                            <?= e($item['name']) ?>
                                            <strong>× <?= $item['quantity'] ?></strong>
                                        </td>
                                        <td class="text-end"><?= formatMoney($item['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Tạm tính:</th>
                                    <td class="text-end checkout-subtotal"><?= formatMoney($subtotal) ?></td>
                                </tr>
                                <?php if ($discount > 0): ?>
                                <tr class="text-success">
                                    <th>Giảm giá:</th>
                                    <td class="text-end checkout-discount">-<?= formatMoney($discount) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="table-active">
                                    <th>Tổng cộng:</th>
                                    <th class="text-end text-danger fs-5 checkout-total"><?= formatMoney($total) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Mã giảm giá - Collapsible -->
                    <div class="mt-3 pt-3 border-top">
                        <?php if ($appliedCoupon): ?>
                        <!-- Hiển thị mã giảm giá đã áp dụng -->
                        <div class="alert alert-success mb-0 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-tag"></i>
                                <strong>Mã giảm giá:</strong> <?= e($appliedCoupon['code']) ?>
                                <br>
                                <small class="text-muted">
                                    <?php if ($appliedCoupon['type'] === 'percent'): ?>
                                        Giảm <?= $appliedCoupon['value'] ?>%
                                    <?php else: ?>
                                        Giảm <?= formatMoney($appliedCoupon['value']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <form method="POST" style="margin: 0;" id="remove-coupon-form">
                                <button type="submit" name="remove_coupon" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-times"></i> Xóa
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <!-- Link để hiện form nhập mã giảm giá -->
                        <a href="#" id="show-coupon-form" class="text-decoration-none">
                            <i class="fas fa-tag"></i> Bạn có mã giảm giá? Nhập vào đây
                        </a>

                        <!-- Form nhập mã giảm giá (ẩn mặc định) -->
                        <div id="coupon-form" class="mt-2" style="display: none;">
                            <form method="POST" id="apply-coupon-form">
                                <div class="input-group">
                                    <input type="text" name="coupon_code" id="coupon-code-input" class="form-control form-control-sm"
                                           placeholder="Nhập mã giảm giá" value="<?= e($couponCode) ?>" required>
                                    <button type="submit" name="apply_coupon" class="btn btn-primary btn-sm">
                                        <i class="fas fa-check"></i> Áp dụng
                                    </button>
                                </div>
                                <div id="coupon-message" class="mt-2"></div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Phương thức thanh toán -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-wallet"></i> Phương thức thanh toán</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        <!-- Chuyển khoản -->
                        <div class="payment-option active" data-method="bank_transfer">
                            <div class="payment-radio">
                                <div class="radio-dot"></div>
                            </div>
                            <div class="payment-content">
                                <div class="payment-icon">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="payment-info">
                                    <div class="payment-name">Chuyển khoản</div>
                                    <div class="payment-desc">Ngân hàng</div>
                                </div>
                            </div>
                        </div>

                        <!-- Số dư (chỉ hiện khi đã login) -->
                        <?php if (!$isGuest): ?>
                        <div class="payment-option <?= ($user['balance'] < $total) ? 'disabled' : '' ?>"
                             data-method="wallet">
                            <div class="payment-radio">
                                <div class="radio-dot"></div>
                            </div>
                            <div class="payment-content">
                                <div class="payment-icon">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="payment-info">
                                    <div class="payment-name">Số dư</div>
                                    <div class="payment-balance">
                                        <span class="badge bg-<?= ($user['balance'] >= $total) ? 'success' : 'secondary' ?>">
                                            <?= formatMoney($user['balance']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- MoMo -->
                        <div class="payment-option" data-method="momo">
                            <div class="payment-radio">
                                <div class="radio-dot"></div>
                            </div>
                            <div class="payment-content">
                                <div class="payment-icon momo">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="payment-info">
                                    <div class="payment-name">Ví MoMo</div>
                                    <div class="payment-desc">Ví điện tử</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút thanh toán -->
            <button type="submit" form="checkoutForm" name="place_order" class="pill-button pill-button-blue pill-button-lg w-100 mb-3">
                <i class="fas fa-arrow-right"></i> Bắt đầu thanh toán
            </button>
        </div>
    </div>
</div>

<style>
.payment-option {
    background: white;
    border: 2px solid #e8ecef;
    border-radius: 12px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    min-height: 70px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.payment-option:hover:not(.disabled) {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.payment-option.active {
    background: linear-gradient(135deg, #f8fbff 0%, #f0f7ff 100%);
    box-shadow: 0 4px 20px rgba(13, 110, 253, 0.15);
}

.payment-option.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: #f8f9fa;
}

.payment-option.disabled:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

/* Radio Button */
.payment-radio {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.payment-option.active .payment-radio {
    border-color: #0d6efd;
    background: #0d6efd;
}

.payment-radio .radio-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: white;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
}

.payment-option.active .payment-radio .radio-dot {
    opacity: 1;
    transform: scale(1);
}

/* Payment Content */
.payment-content {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.payment-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 10px;
    font-size: 18px;
    color: #6b7280;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.payment-option.active .payment-icon {
    background: #e7f0ff;
    color: #0d6efd;
}

.payment-icon.momo {
    background: #fff5f7;
    color: #a50064;
}

.payment-option.active .payment-icon.momo {
    background: #ffe5ed;
    color: #a50064;
}

/* Payment Info */
.payment-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    overflow: hidden;
}

.payment-name {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.payment-desc {
    font-size: 11px;
    color: #9ca3af;
    margin: 0;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.payment-balance {
    margin-top: 2px;
}

.payment-balance .badge {
    font-size: 11px;
    padding: 4px 8px;
    font-weight: 600;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .payment-option {
        min-height: 65px;
        padding: 12px 10px;
        gap: 8px;
    }

    .payment-radio {
        width: 18px;
        height: 18px;
    }

    .payment-content {
        gap: 8px;
    }

    .payment-icon {
        width: 36px;
        height: 36px;
        font-size: 16px;
    }

    .payment-name {
        font-size: 13px;
    }

    .payment-desc {
        font-size: 10px;
    }
}
</style>

<style>
/* Apple-inspired global styles for checkout */
.order-detail-container {
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Arial, sans-serif;
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

.order-detail-container .card-header h5,
.order-detail-container .card-header h6 {
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

/* Form label styling */
.order-detail-container .form-label {
    color: #1d1d1f;
    font-weight: 500;
    font-size: 14px;
    margin-bottom: 8px;
}

/* Input styling */
.order-detail-container .form-control {
    border: 1px solid #d2d2d7;
    border-radius: 10px;
    padding: 10px 14px;
    transition: all 0.2s ease;
    background: white;
}

.order-detail-container .form-control:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
}

.order-detail-container textarea.form-control {
    resize: vertical;
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
</style>

<script>
// Handle payment method selection
document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', function() {
        if (this.classList.contains('disabled')) return;

        // Remove active class from all options
        document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('active'));

        // Add active class to clicked option
        this.classList.add('active');

        // Update hidden input
        document.getElementById('selected_payment_method').value = this.dataset.method;
    });
});

// Toggle coupon form
const showCouponLink = document.getElementById('show-coupon-form');
if (showCouponLink) {
    showCouponLink.addEventListener('click', function(e) {
        e.preventDefault();
        this.style.display = 'none';
        document.getElementById('coupon-form').style.display = 'block';
        document.querySelector('#coupon-form input[name="coupon_code"]').focus();
    });
}

// AJAX Apply Coupon
const applyCouponForm = document.getElementById('apply-coupon-form');
if (applyCouponForm) {
    applyCouponForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const couponCode = document.getElementById('coupon-code-input').value.trim();
        const submitBtn = this.querySelector('button[type="submit"]');
        const messageDiv = document.getElementById('coupon-message');
        const originalBtnHTML = submitBtn.innerHTML;

        if (!couponCode) {
            messageDiv.innerHTML = '<div class="alert alert-danger alert-sm mb-0"><i class="fas fa-exclamation-circle"></i> Vui lòng nhập mã giảm giá</div>';
            return;
        }

        // Disable button and show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        messageDiv.innerHTML = '';

        const formData = new FormData();
        formData.append('action', 'apply');
        formData.append('coupon_code', couponCode);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        fetch('/api/apply-coupon.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message and reload to update UI
                messageDiv.innerHTML = '<div class="alert alert-success alert-sm mb-0"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';

                // Update prices
                document.querySelectorAll('.checkout-subtotal').forEach(el => el.textContent = data.subtotal_formatted);
                document.querySelectorAll('.checkout-discount').forEach(el => el.textContent = data.discount_formatted);
                document.querySelectorAll('.checkout-total').forEach(el => el.textContent = data.total_formatted);

                // Show success toast
                if (typeof showToast === 'function') {
                    showToast(data.message, 'success');
                }

                // Reload page after short delay to show applied coupon
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                messageDiv.innerHTML = '<div class="alert alert-danger alert-sm mb-0"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHTML;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.innerHTML = '<div class="alert alert-danger alert-sm mb-0"><i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra. Vui lòng thử lại.</div>';
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHTML;
        });
    });
}

// AJAX Remove Coupon
const removeCouponForm = document.getElementById('remove-coupon-form');
if (removeCouponForm) {
    removeCouponForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnHTML = submitBtn.innerHTML;

        // Disable button and show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('csrf_token', '<?= $csrfToken ?>');

        fetch('/api/apply-coupon.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success toast
                if (typeof showToast === 'function') {
                    showToast(data.message, 'success');
                }

                // Update prices
                document.querySelectorAll('.checkout-subtotal').forEach(el => el.textContent = data.subtotal_formatted);
                document.querySelectorAll('.checkout-discount').forEach(el => el.textContent = data.discount_formatted);
                document.querySelectorAll('.checkout-total').forEach(el => el.textContent = data.total_formatted);

                // Reload page after short delay to hide coupon section
                setTimeout(() => {
                    location.reload();
                }, 800);
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHTML;
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHTML;
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        });
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
