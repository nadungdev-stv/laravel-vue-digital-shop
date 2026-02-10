<?php
/**
 * API: Kiểm tra trạng thái thanh toán đơn hàng
 * Dùng cho AJAX polling khi khách hàng đang chờ thanh toán
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$orderCode = $_GET['code'] ?? '';

if (empty($orderCode)) {
    echo json_encode(['success' => false, 'message' => 'Missing order code']);
    exit;
}

try {
    $stmt = db()->query(
        "SELECT id, order_code, payment_status, order_status, final_amount
         FROM orders WHERE order_code = ?",
        [$orderCode]
    );
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    $isPaid = $order['payment_status'] === 'paid';

    echo json_encode([
        'success' => true,
        'paid' => $isPaid,
        'payment_status' => $order['payment_status'],
        'order_status' => $order['order_status'],
        'message' => $isPaid ? 'Thanh toán thành công!' : 'Đang chờ thanh toán'
    ]);

} catch (Exception $e) {
    error_log("Check payment status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
