<?php
/**
 * SePay Webhook - Nhận callback khi có giao dịch chuyển khoản
 *
 * SePay sẽ POST đến endpoint này với thông tin giao dịch
 * Xem docs: https://docs.sepay.vn
 */

// Không cần session cho webhook
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Log incoming request
$logFile = __DIR__ . '/../logs/sepay_webhook.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Response headers
header('Content-Type: application/json');

// Test endpoint - GET request để kiểm tra webhook có hoạt động
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'message' => 'SePay webhook endpoint is working',
        'time' => date('Y-m-d H:i:s'),
        'sepay_enabled' => getSetting('sepay_enabled') == '1'
    ]);
    exit;
}

// Get raw POST data
$rawInput = file_get_contents('php://input');
$logData = date('Y-m-d H:i:s') . " - Incoming webhook\n";
$logData .= "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$logData .= "Headers: " . json_encode(getallheaders()) . "\n";
$logData .= "Raw input: " . $rawInput . "\n";

try {
    // Kiểm tra SePay có được bật không
    if (getSetting('sepay_enabled') != '1') {
        $logData .= "SePay is disabled\n";
        file_put_contents($logFile, $logData . "---\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'SePay disabled']);
        exit;
    }

    // Parse JSON từ SePay
    $data = json_decode($rawInput, true);

    if (!$data) {
        $logData .= "Invalid JSON\n";
        file_put_contents($logFile, $logData . "---\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }

    $logData .= "Parsed data: " . print_r($data, true) . "\n";

    // SePay webhook format
    // {
    //   "id": 123456,
    //   "gateway": "MBBank",
    //   "transactionDate": "2024-01-15 10:30:00",
    //   "accountNumber": "0123456789",
    //   "subAccount": null,
    //   "transferType": "in",
    //   "transferAmount": 50000,
    //   "accumulated": 50000,
    //   "code": null,
    //   "content": "DH A123 thanh toan",
    //   "referenceCode": "FT24015...",
    //   "description": "..."
    // }

    // Lấy thông tin từ webhook
    $transactionId = $data['id'] ?? null;
    $amount = $data['transferAmount'] ?? 0;
    $content = $data['content'] ?? '';
    $transferType = $data['transferType'] ?? '';
    $accountNumber = $data['accountNumber'] ?? '';

    // Chỉ xử lý giao dịch tiền vào (in)
    if ($transferType !== 'in') {
        $logData .= "Not an incoming transfer, skipping\n";
        file_put_contents($logFile, $logData . "---\n", FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Ignored outgoing transfer']);
        exit;
    }

    // Kiểm tra số tài khoản
    $configuredAccount = getSetting('sepay_account_number');
    if ($configuredAccount && $accountNumber !== $configuredAccount) {
        $logData .= "Account mismatch: expected {$configuredAccount}, got {$accountNumber}\n";
        file_put_contents($logFile, $logData . "---\n", FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Account mismatch']);
        exit;
    }

    // Tìm mã đơn hàng trong nội dung chuyển khoản
    // Định dạng: DH X123 hoặc X123 (1 chữ + 3 số)
    $orderCode = null;

    // Pattern 1: DH X123
    if (preg_match('/DH\s*([A-Z]\d{3})/i', $content, $matches)) {
        $orderCode = strtoupper($matches[1]);
    }
    // Pattern 2: Chỉ X123 (mã đơn hàng riêng lẻ)
    elseif (preg_match('/\b([A-Z]\d{3})\b/i', $content, $matches)) {
        $orderCode = strtoupper($matches[1]);
    }

    $logData .= "Extracted order code: " . ($orderCode ?: 'NONE') . "\n";

    if (!$orderCode) {
        $logData .= "No order code found in content\n";
        file_put_contents($logFile, $logData . "---\n", FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'No order code found']);
        exit;
    }

    // Tìm đơn hàng
    $stmt = db()->query(
        "SELECT * FROM orders WHERE order_code = ? AND payment_status IN ('pending', 'confirming')",
        [$orderCode]
    );
    $order = $stmt->fetch();

    if (!$order) {
        $logData .= "Order not found or already processed: {$orderCode}\n";
        file_put_contents($logFile, $logData . "---\n", FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Order not found or already processed']);
        exit;
    }

    // Kiểm tra số tiền
    $expectedAmount = (int)$order['final_amount'];
    $receivedAmount = (int)$amount;

    $logData .= "Expected: {$expectedAmount}, Received: {$receivedAmount}\n";

    if ($receivedAmount < $expectedAmount) {
        $logData .= "Amount mismatch - insufficient payment\n";
        file_put_contents($logFile, $logData . "---\n", FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Insufficient amount']);
        exit;
    }

    // Cập nhật trạng thái đơn hàng
    db()->query(
        "UPDATE orders SET
            payment_status = 'paid',
            order_status = 'processing',
            updated_at = NOW(),
            sepay_transaction_id = ?
         WHERE id = ?",
        [$transactionId, $order['id']]
    );

    $logData .= "Order {$orderCode} marked as PAID\n";

    // Tự động giao hàng nếu bật
    $autoDeliver = getSetting('auto_deliver', '1') == '1';
    if ($autoDeliver) {
        // Lấy các item trong đơn hàng
        $items = db()->query(
            "SELECT oi.*, p.delivery_type
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?",
            [$order['id']]
        )->fetchAll();

        foreach ($items as $item) {
            // Chỉ tự động giao với sản phẩm loại 'stock' (từ kho)
            if ($item['delivery_type'] === 'stock' && empty($item['account_delivered'])) {
                // Lấy tài khoản từ kho
                $stockItem = db()->query(
                    "SELECT * FROM product_stocks
                     WHERE product_id = ? AND (variant_id = ? OR variant_id IS NULL) AND is_sold = 0
                     ORDER BY id ASC LIMIT 1",
                    [$item['product_id'], $item['variant_id']]
                )->fetch();

                if ($stockItem) {
                    // Đánh dấu đã bán
                    db()->query(
                        "UPDATE product_stocks SET is_sold = 1, sold_at = NOW(), order_item_id = ? WHERE id = ?",
                        [$item['id'], $stockItem['id']]
                    );

                    // Cập nhật tài khoản đã giao
                    db()->query(
                        "UPDATE order_items SET account_delivered = ? WHERE id = ?",
                        [$stockItem['account_data'], $item['id']]
                    );

                    $logData .= "Delivered stock item #{$stockItem['id']} to order item #{$item['id']}\n";
                }
            }
        }

        // Kiểm tra nếu tất cả items đã được giao
        $undelivered = db()->query(
            "SELECT COUNT(*) as cnt FROM order_items
             WHERE order_id = ? AND account_delivered IS NULL",
            [$order['id']]
        )->fetch()['cnt'];

        if ($undelivered == 0) {
            db()->query(
                "UPDATE orders SET order_status = 'completed' WHERE id = ?",
                [$order['id']]
            );
            $logData .= "Order {$orderCode} marked as COMPLETED\n";
        }
    }

    // Tạo thông báo cho user (nếu có)
    if ($order['user_id']) {
        createNotification(
            $order['user_id'],
            'Thanh toán thành công!',
            "Đơn hàng #{$orderCode} đã được xác nhận thanh toán " . formatMoney($receivedAmount) . ". Cảm ơn bạn đã mua hàng!",
            'success',
            '/order-detail.php?code=' . $orderCode
        );
    }

    // Gửi thông báo Telegram
    $telegramMsg = "✅ *THANH TOÁN THÀNH CÔNG*\n\n";
    $telegramMsg .= "📦 Đơn hàng: `{$orderCode}`\n";
    $telegramMsg .= "💰 Số tiền: " . formatMoney($receivedAmount) . "\n";
    $telegramMsg .= "🏦 Nguồn: SePay Auto\n";
    $telegramMsg .= "📧 Email: {$order['customer_email']}\n";
    $telegramMsg .= "⏰ " . date('d/m/Y H:i:s');

    sendTelegramNotification($telegramMsg);

    $logData .= "Telegram notification sent\n";
    $logData .= "Processing completed successfully\n";
    file_put_contents($logFile, $logData . "---\n", FILE_APPEND);

    echo json_encode(['success' => true, 'message' => 'Payment confirmed']);

} catch (Exception $e) {
    $logData .= "ERROR: " . $e->getMessage() . "\n";
    $logData .= "Trace: " . $e->getTraceAsString() . "\n";
    file_put_contents($logFile, $logData . "---\n", FILE_APPEND);

    error_log("SePay Webhook Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
