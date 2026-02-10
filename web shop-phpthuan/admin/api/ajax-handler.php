<?php
/**
 * Admin AJAX Handler
 * Handles all AJAX requests for admin panel
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
initSession();

// Check admin permission
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Check if AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Set JSON header
header('Content-Type: application/json');

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // Update order status
        case 'update_order_status':
            $orderId = (int)($_POST['order_id'] ?? 0);
            $orderStatus = $_POST['order_status'] ?? '';
            $paymentStatus = $_POST['payment_status'] ?? '';

            if (!$orderId) {
                throw new Exception('ID đơn hàng không hợp lệ');
            }

            db()->query(
                "UPDATE orders SET order_status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?",
                [$orderStatus, $paymentStatus, $orderId]
            );

            // Auto-deliver if paid
            if ($paymentStatus === 'paid') {
                $orderItems = db()->query(
                    "SELECT * FROM order_items WHERE order_id = ? AND account_delivered IS NULL",
                    [$orderId]
                )->fetchAll();

                foreach ($orderItems as $item) {
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

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái đơn hàng',
                'reload' => true
            ]);
            break;

        // Delete order
        case 'delete_order':
            $orderId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

            if (!$orderId) {
                throw new Exception('ID không hợp lệ');
            }

            // Delete order items first
            db()->query("DELETE FROM order_items WHERE order_id = ?", [$orderId]);

            // Delete order
            db()->query("DELETE FROM orders WHERE id = ?", [$orderId]);

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa đơn hàng thành công'
            ]);
            break;

        // Delete product
        case 'delete_product':
            $productId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

            if (!$productId) {
                throw new Exception('ID không hợp lệ');
            }

            // Check if product exists in orders
            $orderCount = db()->query(
                "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?",
                [$productId]
            )->fetch()['count'];

            if ($orderCount > 0) {
                throw new Exception("Không thể xóa sản phẩm này vì đã có {$orderCount} đơn hàng. Bạn có thể đổi trạng thái thành 'Tạm ngưng' thay vì xóa.");
            }

            // Delete product variants first
            db()->query("DELETE FROM product_variants WHERE product_id = ?", [$productId]);

            // Delete product gallery
            db()->query("DELETE FROM product_gallery WHERE product_id = ?", [$productId]);

            // Delete product
            db()->query("DELETE FROM products WHERE id = ?", [$productId]);

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa sản phẩm thành công'
            ]);
            break;

        // Delete user
        case 'delete_user':
            $userId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

            if (!$userId) {
                throw new Exception('ID không hợp lệ');
            }

            // Check if trying to delete admin
            $user = db()->query("SELECT role FROM users WHERE id = ?", [$userId])->fetch();
            if ($user && $user['role'] === 'admin') {
                throw new Exception('Không thể xóa tài khoản admin');
            }

            db()->query("DELETE FROM users WHERE id = ?", [$userId]);

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa người dùng thành công'
            ]);
            break;

        // Update user (role & status)
        case 'update_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? '';

            if (!$userId) {
                throw new Exception('ID người dùng không hợp lệ');
            }

            if (!in_array($role, ['customer', 'admin'])) {
                throw new Exception('Vai trò không hợp lệ');
            }

            if (!in_array($status, ['active', 'inactive'])) {
                throw new Exception('Trạng thái không hợp lệ');
            }

            db()->query(
                "UPDATE users SET role = ?, status = ?, updated_at = NOW() WHERE id = ?",
                [$role, $status, $userId]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật người dùng thành công',
                'reload' => true
            ]);
            break;

        // Update field (generic inline edit)
        case 'update_field':
            $data = json_decode(file_get_contents('php://input'), true);

            $table = $data['table'] ?? '';
            $id = (int)($data['id'] ?? 0);
            $field = $data['field'] ?? '';
            $value = $data['value'] ?? '';

            if (!$table || !$id || !$field) {
                throw new Exception('Dữ liệu không hợp lệ');
            }

            // Whitelist tables and fields for security
            $allowedTables = ['orders', 'products', 'users', 'categories'];
            $allowedFields = ['order_status', 'payment_status', 'status', 'name', 'price', 'stock_quantity'];

            if (!in_array($table, $allowedTables) || !in_array($field, $allowedFields)) {
                throw new Exception('Không được phép cập nhật trường này');
            }

            db()->query(
                "UPDATE $table SET $field = ?, updated_at = NOW() WHERE id = ?",
                [$value, $id]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật thành công'
            ]);
            break;

        // Toggle status (active/inactive)
        case 'toggle_status':
            $data = json_decode(file_get_contents('php://input'), true);

            $table = $data['table'] ?? '';
            $id = (int)($data['id'] ?? 0);
            $currentStatus = $data['current_status'] ?? '';

            if (!$table || !$id) {
                throw new Exception('Dữ liệu không hợp lệ');
            }

            $allowedTables = ['products', 'categories', 'users', 'coupons'];
            if (!in_array($table, $allowedTables)) {
                throw new Exception('Bảng không hợp lệ');
            }

            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';

            db()->query(
                "UPDATE $table SET status = ?, updated_at = NOW() WHERE id = ?",
                [$newStatus, $id]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái',
                'new_status' => $newStatus
            ]);
            break;

        // Create coupon
        case 'create_coupon':
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $type = $_POST['type'] ?? '';
            $value = (float)($_POST['value'] ?? 0);
            $minOrderAmount = (float)($_POST['min_order_amount'] ?? 0);
            $maxDiscount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
            $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
            $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $status = $_POST['status'] ?? 'active';
            $description = trim($_POST['description'] ?? '');

            if (empty($code)) {
                throw new Exception('Vui lòng nhập mã coupon');
            }

            if ($value <= 0) {
                throw new Exception('Giá trị giảm phải lớn hơn 0');
            }

            if ($type === 'percentage' && $value > 100) {
                throw new Exception('Giá trị % không được vượt quá 100');
            }

            // Check if code exists
            $existing = db()->query("SELECT id FROM coupons WHERE code = ?", [$code])->fetch();
            if ($existing) {
                throw new Exception('Mã coupon đã tồn tại');
            }

            db()->query(
                "INSERT INTO coupons (code, type, value, min_order_amount, max_discount, usage_limit, start_date, end_date, status, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$code, $type, $value, $minOrderAmount, $maxDiscount, $usageLimit, $startDate, $endDate, $status, $description]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Đã tạo mã giảm giá thành công',
                'reload' => true
            ]);
            break;

        // Update coupon
        case 'update_coupon':
            $couponId = (int)($_POST['coupon_id'] ?? 0);
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $type = $_POST['type'] ?? '';
            $value = (float)($_POST['value'] ?? 0);
            $minOrderAmount = (float)($_POST['min_order_amount'] ?? 0);
            $maxDiscount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
            $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
            $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $status = $_POST['status'] ?? '';
            $description = trim($_POST['description'] ?? '');

            if (!$couponId) {
                throw new Exception('ID không hợp lệ');
            }

            if (empty($code)) {
                throw new Exception('Vui lòng nhập mã coupon');
            }

            if ($value <= 0) {
                throw new Exception('Giá trị giảm phải lớn hơn 0');
            }

            if ($type === 'percentage' && $value > 100) {
                throw new Exception('Giá trị % không được vượt quá 100');
            }

            // Check if code exists (except current)
            $existing = db()->query("SELECT id FROM coupons WHERE code = ? AND id != ?", [$code, $couponId])->fetch();
            if ($existing) {
                throw new Exception('Mã coupon đã tồn tại');
            }

            db()->query(
                "UPDATE coupons SET code = ?, type = ?, value = ?, min_order_amount = ?, max_discount = ?,
                 usage_limit = ?, start_date = ?, end_date = ?, status = ?, description = ?
                 WHERE id = ?",
                [$code, $type, $value, $minOrderAmount, $maxDiscount, $usageLimit, $startDate, $endDate, $status, $description, $couponId]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật mã giảm giá thành công',
                'reload' => true
            ]);
            break;

        // Delete coupon
        case 'delete_coupon':
            $couponId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

            if (!$couponId) {
                throw new Exception('ID không hợp lệ');
            }

            // Check if coupon has been used
            $coupon = db()->query("SELECT code, used_count FROM coupons WHERE id = ?", [$couponId])->fetch();
            if ($coupon && $coupon['used_count'] > 0) {
                throw new Exception("Mã {$coupon['code']} đã được sử dụng {$coupon['used_count']} lần, không thể xóa.");
            }

            db()->query("DELETE FROM coupons WHERE id = ?", [$couponId]);

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa mã giảm giá thành công'
            ]);
            break;

        // Delete category
        case 'delete_category':
            $categoryId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

            if (!$categoryId) {
                throw new Exception('ID không hợp lệ');
            }

            // Check if category has products
            $productCount = db()->query(
                "SELECT COUNT(*) as count FROM products WHERE category_id = ?",
                [$categoryId]
            )->fetch()['count'];

            if ($productCount > 0) {
                throw new Exception('Không thể xóa danh mục có sản phẩm. Vui lòng xóa sản phẩm trước.');
            }

            db()->query("DELETE FROM categories WHERE id = ?", [$categoryId]);

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa danh mục thành công'
            ]);
            break;

        // Update review status
        case 'update_review_status':
            $reviewId = (int)($_POST['review_id'] ?? 0);
            $status = $_POST['status'] ?? '';

            if (!$reviewId) {
                throw new Exception('ID không hợp lệ');
            }

            if (!in_array($status, ['pending', 'approved', 'rejected'])) {
                throw new Exception('Trạng thái không hợp lệ');
            }

            db()->query(
                "UPDATE reviews SET status = ?, updated_at = NOW() WHERE id = ?",
                [$status, $reviewId]
            );

            // Update product average rating
            $review = db()->query("SELECT product_id FROM reviews WHERE id = ?", [$reviewId])->fetch();
            if ($review) {
                $avgRating = db()->query(
                    "SELECT AVG(rating) as avg FROM reviews WHERE product_id = ? AND status = 'approved'",
                    [$review['product_id']]
                )->fetch()['avg'];

                db()->query(
                    "UPDATE products SET rating = ? WHERE id = ?",
                    [$avgRating ?: 0, $review['product_id']]
                );
            }

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái đánh giá',
                'reload' => true
            ]);
            break;

        // Add review reply
        case 'add_review_reply':
            $reviewId = (int)($_POST['review_id'] ?? 0);
            $adminReply = trim($_POST['admin_reply'] ?? '');

            if (!$reviewId) {
                throw new Exception('ID không hợp lệ');
            }

            if (empty($adminReply)) {
                throw new Exception('Vui lòng nhập nội dung phản hồi');
            }

            db()->query(
                "UPDATE reviews SET admin_reply = ?, updated_at = NOW() WHERE id = ?",
                [$adminReply, $reviewId]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Đã thêm phản hồi thành công',
                'reload' => true
            ]);
            break;

        // Delete review
        case 'delete_review':
            $reviewId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

            if (!$reviewId) {
                throw new Exception('ID không hợp lệ');
            }

            // Get product_id before deleting
            $review = db()->query("SELECT product_id FROM reviews WHERE id = ?", [$reviewId])->fetch();

            db()->query("DELETE FROM reviews WHERE id = ?", [$reviewId]);

            // Update product rating
            if ($review) {
                $avgRating = db()->query(
                    "SELECT AVG(rating) as avg FROM reviews WHERE product_id = ? AND status = 'approved'",
                    [$review['product_id']]
                )->fetch()['avg'];

                db()->query(
                    "UPDATE products SET rating = ? WHERE id = ?",
                    [$avgRating ?: 0, $review['product_id']]
                );
            }

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa đánh giá thành công'
            ]);
            break;

        // Bulk delete
        case 'bulk_delete':
            $data = json_decode(file_get_contents('php://input'), true);

            $table = $data['table'] ?? '';
            $ids = $data['ids'] ?? [];

            if (!$table || empty($ids)) {
                throw new Exception('Dữ liệu không hợp lệ');
            }

            $allowedTables = ['orders', 'products', 'users', 'categories', 'coupons', 'reviews'];
            if (!in_array($table, $allowedTables)) {
                throw new Exception('Bảng không hợp lệ');
            }

            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            db()->query("DELETE FROM $table WHERE id IN ($placeholders)", $ids);

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa ' . count($ids) . ' mục thành công'
            ]);
            break;

        // Get stats (for dashboard refresh)
        case 'get_stats':
            $stats = [
                'total_users' => db()->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
                'total_products' => db()->query("SELECT COUNT(*) as count FROM products")->fetch()['count'],
                'total_orders' => db()->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'],
                'total_revenue' => db()->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE payment_status = 'paid'")->fetch()['total'],
                'pending_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")->fetch()['count'],
                'today_orders' => db()->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")->fetch()['count'],
                'today_revenue' => db()->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'")->fetch()['total']
            ];

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
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
