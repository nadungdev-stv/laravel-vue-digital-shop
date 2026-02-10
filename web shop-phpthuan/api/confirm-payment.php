<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

header('Content-Type: application/json');

// ============ SECURITY CHECKS ============

// 1. CSRF Protection
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Token bảo mật không hợp lệ. Vui lòng tải lại trang.']);
    exit;
}

// 2. Input Sanitization
$orderCode = $_POST['order_code'] ?? '';

if (empty($orderCode)) {
    // logSecurityEvent('invalid_order_code', ['input' => $_POST['order_code'] ?? '']);
    echo json_encode(['success' => false, 'message' => 'Mã đơn hàng không hợp lệ']);
    exit;
}

try {
    $user = getCurrentUser();
    $isGuest = !isLoggedIn();

    // Lấy thông tin đơn hàng
    if ($isGuest) {
        // Guest - kiểm tra order code có trong session không
        if (!isset($_SESSION['guest_orders']) || !in_array($orderCode, $_SESSION['guest_orders'])) {
            // logSecurityEvent('unauthorized_access', ['order_code' => $orderCode, 'user_type' => 'guest']);
            throw new Exception('Không tìm thấy đơn hàng hoặc bạn không có quyền truy cập');
        }

        $order = db()->query(
            "SELECT * FROM orders WHERE order_code = ? AND user_id IS NULL",
            [$orderCode]
        )->fetch();
    } else {
        // Logged in user
        $order = db()->query(
            "SELECT * FROM orders WHERE order_code = ? AND user_id = ?",
            [$orderCode, $user['id']]
        )->fetch();
    }

    if (!$order) {
        // logSecurityEvent('order_not_found', ['order_code' => $orderCode, 'user_id' => $user['id'] ?? null]);
        throw new Exception('Không tìm thấy đơn hàng');
    }

    // Kiểm tra trạng thái thanh toán
    if ($order['payment_status'] !== 'pending') {
        throw new Exception('Đơn hàng này đã được xác nhận thanh toán');
    }

    // Xử lý thanh toán bằng ví
    if ($order['payment_method'] === 'wallet') {
        if ($isGuest) {
            throw new Exception('Khách vãng lai không thể thanh toán bằng ví');
        }

        // Kiểm tra số dư
        if ($user['balance'] < $order['final_amount']) {
            throw new Exception('Số dư không đủ. Vui lòng nạp thêm tiền vào ví.');
        }

        // Bắt đầu transaction
        db()->getConnection()->beginTransaction();

        try {
            // Trừ số dư
            $newBalance = $user['balance'] - $order['final_amount'];
            db()->query(
                "UPDATE users SET balance = ? WHERE id = ?",
                [$newBalance, $user['id']]
            );

            // Tạo transaction trong wallet_transactions
            db()->query(
                "INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, description, reference_id, created_at)
                 VALUES (?, 'payment', ?, ?, ?, ?, ?, NOW())",
                [$user['id'], -$order['final_amount'], $user['balance'], $newBalance, 'Thanh toán đơn hàng #' . $orderCode, $order['id']]
            );

            // Cập nhật trạng thái đơn hàng
            db()->query(
                "UPDATE orders SET payment_status = 'completed', order_status = 'processing', updated_at = NOW() WHERE id = ?",
                [$order['id']]
            );

            db()->getConnection()->commit();

            // Xóa tin nhắn cũ và gửi thông báo Telegram mới
            try {
                // Xóa tin nhắn "Đơn hàng mới" cũ nếu có
                if (!empty($order['telegram_message_id'])) {
                    deleteTelegramMessage($order['telegram_message_id']);

                    // Xóa message_id khỏi database
                    db()->query(
                        "UPDATE orders SET telegram_message_id = NULL WHERE id = ?",
                        [$order['id']]
                    );
                }

                // Gửi tin nhắn MỚI về thanh toán ví
                $message = "💳 <b>THANH TOÁN VÍ THÀNH CÔNG</b>\n\n";
                $message .= "📋 Mã đơn: <b>{$orderCode}</b>\n";
                $message .= "👤 Khách hàng: {$user['username']}\n";
                $message .= "💰 Số tiền: <b>" . formatMoney($order['final_amount']) . "</b>\n";
                $message .= "💳 Phương thức: Ví điện tử\n";
                $message .= "✅ Trạng thái: Đã thanh toán\n\n";
                $message .= "🔗 <a href='https://veyrix.pro/admin/orders'>Xem chi tiết</a>";

                sendTelegramNotification($message);
            } catch (Exception $e) {
                error_log("Telegram notification error: " . $e->getMessage());
            }

            // Log successful wallet payment
            // logSecurityEvent('wallet_payment_success', [
            //     'order_code' => $orderCode,
            //     'amount' => $order['final_amount'],
            //     'user_id' => $user['id'],
            //     'balance_before' => $user['balance'],
            //     'balance_after' => $newBalance
            // ]);
        } catch (Exception $e) {
            db()->getConnection()->rollBack();

            // Log wallet payment failure
            // logSecurityEvent('wallet_payment_failed', [
            //     'order_code' => $orderCode,
            //     'error' => $e->getMessage(),
            //     'user_id' => $user['id']
            // ]);

            throw new Exception('Lỗi khi xử lý thanh toán: ' . $e->getMessage());
        }
    } else {
        // Bank transfer confirmation
        // Cập nhật payment_status sang 'confirming' và order_status sang 'processing'
        db()->query(
            "UPDATE orders SET payment_status = 'confirming', order_status = 'processing', updated_at = NOW() WHERE id = ?",
            [$order['id']]
        );

        // Xóa tin nhắn cũ và gửi thông báo Telegram mới
        try {
            // Xóa tin nhắn "Đơn hàng mới" cũ nếu có
            if (!empty($order['telegram_message_id'])) {
                deleteTelegramMessage($order['telegram_message_id']);

                // Xóa message_id khỏi database
                db()->query(
                    "UPDATE orders SET telegram_message_id = NULL WHERE id = ?",
                    [$order['id']]
                );
            }

            // Gửi tin nhắn MỚI về xác nhận thanh toán
            $customerName = $isGuest ? $order['customer_name'] : $user['username'];
            $paymentMethodName = $order['payment_method'] === 'bank' ? 'Chuyển khoản' :
                               ($order['payment_method'] === 'momo' ? 'Momo' : $order['payment_method']);

            $message = "🔔 <b>XÁC NHẬN THANH TOÁN</b>\n\n";
            $message .= "📋 Mã đơn: <b>{$orderCode}</b>\n";
            $message .= "👤 Khách hàng: {$customerName}\n";
            $message .= "💰 Số tiền: <b>" . formatMoney($order['final_amount']) . "</b>\n";
            $message .= "💳 Phương thức: {$paymentMethodName}\n";
            $message .= "⏳ Trạng thái: Đang xác nhận\n\n";
            $message .= "ℹ️ Khách hàng xác nhận đã chuyển khoản\n\n";
            $message .= "🔗 <a href='https://veyrix.pro/admin/orders'>Xem chi tiết</a>";

            sendTelegramNotification($message);
        } catch (Exception $e) {
            error_log("Telegram notification error: " . $e->getMessage());
        }

        // Log payment confirmation
        // logSecurityEvent('payment_confirmed', [
        //     'order_code' => $orderCode,
        //     'payment_method' => $order['payment_method'],
        //     'amount' => $order['final_amount'],
        //     'user_id' => $user['id'] ?? null
        // ]);
    }

    // Lưu vào session để không hiện modal thanh toán nữa
    if (!isset($_SESSION['confirmed_payments'])) {
        $_SESSION['confirmed_payments'] = [];
    }
    $_SESSION['confirmed_payments'][] = $orderCode;

    // Tạo thông báo cho admin
    try {
        if ($order['payment_method'] === 'wallet') {
            $notificationTitle = "Đơn hàng mới #" . $orderCode;
            $notificationMessage = "Khách hàng " . $user['username'] . " đã thanh toán bằng ví cho đơn hàng #" . $orderCode . " (" . formatMoney($order['final_amount']) . ")";
        } else {
            $notificationTitle = "Xác nhận thanh toán đơn hàng #" . $orderCode;
            $notificationMessage = ($isGuest ? "Khách" : "Khách hàng " . $user['username']) . " xác nhận đã chuyển khoản cho đơn hàng #" . $orderCode . " (" . formatMoney($order['final_amount']) . ")";
        }

        db()->query(
            "INSERT INTO notifications (title, message, type, created_at) VALUES (?, ?, 'payment', NOW())",
            [$notificationTitle, $notificationMessage]
        );
    } catch (Exception $e) {
        // Bỏ qua lỗi nếu bảng notifications không tồn tại
    }

    // Message trả về
    if ($order['payment_method'] === 'wallet') {
        $responseMessage = 'Thanh toán thành công! Đơn hàng của bạn đang được xử lý.';
        $newPaymentStatus = 'completed';
        $newOrderStatus = 'processing';
    } else {
        $responseMessage = 'Cảm ơn bạn! Chúng tôi đã ghi nhận xác nhận của bạn. Đơn hàng sẽ được xử lý ngay sau khi chúng tôi nhận được thanh toán.';
        $newPaymentStatus = 'confirming';
        $newOrderStatus = 'processing';
    }

    echo json_encode([
        'success' => true,
        'message' => $responseMessage,
        'payment_status' => $newPaymentStatus,
        'order_status' => $newOrderStatus
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
