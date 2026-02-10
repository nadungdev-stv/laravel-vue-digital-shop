<?php
// Helper Functions

// Bắt đầu session nếu chưa có
function initSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Kiểm tra đăng nhập
function isLoggedIn()
{
    initSession();
    return isset($_SESSION['user_id']);
}

// Lấy thông tin user hiện tại
function getCurrentUser()
{
    if (!isLoggedIn())
        return null;

    $userId = $_SESSION['user_id'];
    $stmt = db()->query("SELECT * FROM users WHERE id = ?", [$userId]);
    return $stmt->fetch();
}

// Kiểm tra quyền admin
function isAdmin()
{
    $user = getCurrentUser();
    return $user && in_array($user['role'], ['admin', 'staff']);
}

// Redirect
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

// Format tiền VND
function formatMoney($amount)
{
    return number_format($amount, 0, ',', '.') . 'đ';
}

// Format ngày tháng
function formatDate($date)
{
    return date('d/m/Y H:i', strtotime($date));
}

// Time Ago Function (New)
function timeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    $strTime = array("giây", "phút", "giờ", "ngày", "tháng", "năm");
    $length = array("60", "60", "24", "30", "12", "10");

    $currentTime = time();
    if ($currentTime >= $timestamp) {
        $diff = time() - $timestamp;
        for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; $i++) {
            $diff = $diff / $length[$i];
        }

        $diff = round($diff);
        return $diff . " " . $strTime[$i] . " trước";
    }
    return formatDate($datetime); // Fallback for future dates
}

// Escape HTML
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Flash message
function setFlash($type, $message)
{
    initSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash()
{
    initSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Generate random string
function generateRandomString($length = 10)
{
    return substr(str_shuffle(str_repeat($x = '0848877758ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
}

// Tạo mã đơn hàng (1 chữ cái + 3 số = 4 ký tự)
function generateOrderCode()
{
    $maxAttempts = 10;
    $attempt = 0;

    do {
        // Tạo mã: 1 chữ cái ngẫu nhiên + 3 số ngẫu nhiên
        $letter = chr(rand(65, 90)); // A-Z
        $numbers = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT); // 000-999
        $code = $letter . $numbers;

        // Kiểm tra xem mã đã tồn tại chưa
        $existing = db()->query("SELECT id FROM orders WHERE order_code = ?", [$code])->fetch();

        if (!$existing) {
            return $code;
        }

        $attempt++;
    } while ($attempt < $maxAttempts);

    // Nếu không tìm được mã unique sau 10 lần thử, dùng fallback với thời gian
    return chr(rand(65, 90)) . substr(time(), -3);
}

// Validate email
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hash password
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

// CSRF Token
function generateCSRFToken()
{
    initSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token)
{
    initSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Get cart from database
function getCart()
{
    initSession();
    $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
    $sessionId = session_id();

    try {
        if ($userId) {
            // User đã đăng nhập - lấy cart theo user_id
            $items = db()->query(
                "SELECT product_id, quantity FROM cart WHERE user_id = ?",
                [$userId]
            )->fetchAll();
        } else {
            // Guest - lấy cart theo session_id
            $items = db()->query(
                "SELECT product_id, quantity FROM cart WHERE session_id = ? AND user_id IS NULL",
                [$sessionId]
            )->fetchAll();
        }

        // Chuyển thành format cũ: [product_id => quantity]
        $cart = [];
        foreach ($items as $item) {
            $cart[$item['product_id']] = $item['quantity'];
        }

        return $cart;
    } catch (Exception $e) {
        error_log("Get cart error: " . $e->getMessage());
        return [];
    }
}

// Add to cart
function addToCart($productId, $quantity = 1, $variantId = null)
{
    initSession();
    $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
    $sessionId = session_id();

    try {
        if ($userId) {
            // User đã đăng nhập
            // Kiểm tra đã có trong cart chưa (bao gồm cả variant_id)
            $existing = db()->query(
                "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))",
                [$userId, $productId, $variantId, $variantId]
            )->fetch();

            if ($existing) {
                // Cập nhật số lượng
                db()->query(
                    "UPDATE cart SET quantity = quantity + ? WHERE id = ?",
                    [$quantity, $existing['id']]
                );
            } else {
                // Thêm mới
                db()->query(
                    "INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)",
                    [$userId, $productId, $variantId, $quantity]
                );
            }
        } else {
            // Guest
            $existing = db()->query(
                "SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ? AND user_id IS NULL AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))",
                [$sessionId, $productId, $variantId, $variantId]
            )->fetch();

            if ($existing) {
                db()->query(
                    "UPDATE cart SET quantity = quantity + ? WHERE id = ?",
                    [$quantity, $existing['id']]
                );
            } else {
                db()->query(
                    "INSERT INTO cart (session_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)",
                    [$sessionId, $productId, $variantId, $quantity]
                );
            }
        }
    } catch (Exception $e) {
        error_log("Add to cart error: " . $e->getMessage());
    }
}

// Remove from cart
function removeFromCart($productId)
{
    initSession();
    $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
    $sessionId = session_id();

    try {
        if ($userId) {
            db()->query(
                "DELETE FROM cart WHERE user_id = ? AND product_id = ?",
                [$userId, $productId]
            );
        } else {
            db()->query(
                "DELETE FROM cart WHERE session_id = ? AND product_id = ? AND user_id IS NULL",
                [$sessionId, $productId]
            );
        }
    } catch (Exception $e) {
        error_log("Remove from cart error: " . $e->getMessage());
    }
}

// Clear cart
function clearCart()
{
    initSession();
    $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
    $sessionId = session_id();

    try {
        if ($userId) {
            db()->query("DELETE FROM cart WHERE user_id = ?", [$userId]);
        } else {
            db()->query("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL", [$sessionId]);
        }
    } catch (Exception $e) {
        error_log("Clear cart error: " . $e->getMessage());
    }
}

// Merge guest cart to user cart (khi user đăng nhập)
function mergeGuestCartToUser($userId)
{
    initSession();
    $sessionId = session_id();

    try {
        // Lấy cart của guest
        $guestItems = db()->query(
            "SELECT product_id, quantity FROM cart WHERE session_id = ? AND user_id IS NULL",
            [$sessionId]
        )->fetchAll();

        if (!empty($guestItems)) {
            foreach ($guestItems as $item) {
                // Kiểm tra xem user đã có sản phẩm này chưa
                $existing = db()->query(
                    "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
                    [$userId, $item['product_id']]
                )->fetch();

                if ($existing) {
                    // Cộng dồn số lượng
                    db()->query(
                        "UPDATE cart SET quantity = quantity + ? WHERE id = ?",
                        [$item['quantity'], $existing['id']]
                    );
                } else {
                    // Chuyển cart item từ guest sang user
                    db()->query(
                        "UPDATE cart SET user_id = ?, session_id = NULL WHERE session_id = ? AND product_id = ?",
                        [$userId, $sessionId, $item['product_id']]
                    );
                }
            }

            // Xóa các cart item guest còn lại (đã merge)
            db()->query(
                "DELETE FROM cart WHERE session_id = ? AND user_id IS NULL",
                [$sessionId]
            );
        }
    } catch (Exception $e) {
        error_log("Merge guest cart error: " . $e->getMessage());
    }
}

// Update cart quantity (set exact quantity)
function updateCartQuantity($productId, $quantity)
{
    initSession();
    $userId = isLoggedIn() ? getCurrentUser()['id'] : null;
    $sessionId = session_id();

    try {
        if ($quantity <= 0) {
            // Nếu quantity <= 0 thì xóa khỏi cart
            removeFromCart($productId);
            return;
        }

        if ($userId) {
            db()->query(
                "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?",
                [$quantity, $userId, $productId]
            );
        } else {
            db()->query(
                "UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ? AND user_id IS NULL",
                [$quantity, $sessionId, $productId]
            );
        }
    } catch (Exception $e) {
        error_log("Update cart quantity error: " . $e->getMessage());
    }
}

// Get cart total
function getCartTotal()
{
    $cart = getCart();
    if (empty($cart))
        return 0;

    $productIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $stmt = db()->query("SELECT id, price, sale_price FROM products WHERE id IN ($placeholders)", $productIds);
    $products = $stmt->fetchAll();

    $total = 0;
    foreach ($products as $product) {
        $price = $product['sale_price'] ?? $product['price'];
        $quantity = $cart[$product['id']];
        $total += $price * $quantity;
    }

    return $total;
}

// Pagination helper
function paginate($sql, $params, $page = 1, $perPage = 20)
{
    $offset = ($page - 1) * $perPage;

    // Count total
    $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_table";
    $stmt = db()->query($countSql, $params);
    $totalRows = $stmt->fetch()['total'];

    // Get data
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = db()->query($sql, $params);
    $data = $stmt->fetchAll();

    return [
        'data' => $data,
        'total' => $totalRows,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($totalRows / $perPage)
    ];
}

// Upload image
function uploadImage($file, $directory = 'uploads')
{
    $targetDir = __DIR__ . "/../public/" . $directory . "/";

    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $newFileName = uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $newFileName;

    // Check if image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return false;
    }

    // Check file size (max 5MB)
    if ($file["size"] > 5000000) {
        return false;
    }

    // Allow certain formats
    if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
        return false;
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $directory . '/' . $newFileName;
    }

    return false;
}

// Get setting
function getSetting($key, $default = null)
{
    try {
        // Lấy từ bảng settings
        $stmt = db()->query("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        $result = $stmt->fetch();

        if ($result && $result['setting_value'] !== null && $result['setting_value'] !== '') {
            return $result['setting_value'];
        }

        return $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Update setting
function updateSetting($key, $value)
{
    // Lưu vào bảng settings
    $stmt = db()->query(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = ?",
        [$key, $value, $value]
    );
    return $stmt !== false;
}

// ========================================
// NOTIFICATION FUNCTIONS
// ========================================

/**
 * Tạo thông báo cho user
 *
 * @param int $userId ID của user nhận thông báo
 * @param string $title Tiêu đề thông báo
 * @param string $content Nội dung thông báo
 * @param string $type Loại: success, info, warning, error
 * @param string $link Link liên quan (optional)
 * @return bool
 */
function createNotification($userId, $title, $content, $type = 'info', $link = null)
{
    try {
        db()->query(
            "INSERT INTO notifications (user_id, title, content, type, link) VALUES (?, ?, ?, ?, ?)",
            [$userId, $title, $content, $type, $link]
        );
        return true;
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Tạo thông báo cho tất cả user (broadcast)
 *
 * @param string $title Tiêu đề
 * @param string $content Nội dung
 * @param string $type Loại thông báo
 * @param string $link Link (optional)
 * @return int Số lượng user nhận được thông báo
 */
function createBroadcastNotification($title, $content, $type = 'info', $link = null)
{
    try {
        // Lấy tất cả user đang active
        $users = db()->query("SELECT id FROM users WHERE status = 'active'")->fetchAll();

        $count = 0;
        foreach ($users as $user) {
            if (createNotification($user['id'], $title, $content, $type, $link)) {
                $count++;
            }
        }

        return $count;
    } catch (Exception $e) {
        error_log("Failed to create broadcast: " . $e->getMessage());
        return 0;
    }
}

/**
 * Lấy số lượng thông báo chưa đọc
 *
 * @param int $userId
 * @return int
 */
function getUnreadNotificationCount($userId)
{
    try {
        $result = db()->query(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        )->fetch();
        return $result['count'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Lấy danh sách thông báo
 *
 * @param int $userId
 * @param int $limit Số lượng tối đa
 * @return array
 */
function getNotifications($userId, $limit = 10)
{
    try {
        return db()->query(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        )->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Đánh dấu thông báo đã đọc
 *
 * @param int $notificationId
 * @return bool
 */
function markNotificationAsRead($notificationId)
{
    try {
        db()->query("UPDATE notifications SET is_read = 1 WHERE id = ?", [$notificationId]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Đánh dấu tất cả thông báo của user là đã đọc
 *
 * @param int $userId
 * @return bool
 */
function markAllNotificationsAsRead($userId)
{
    try {
        db()->query("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0", [$userId]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ========================================
// WISHLIST FUNCTIONS
// ========================================

/**
 * Kiểm tra sản phẩm có trong wishlist không
 *
 * @param int $userId
 * @param int $productId
 * @return bool
 */
function isInWishlist($userId, $productId)
{
    try {
        $result = db()->query(
            "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?",
            [$userId, $productId]
        )->fetch();
        return $result !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Lấy số lượng sản phẩm trong wishlist
 *
 * @param int $userId
 * @return int
 */
function getWishlistCount($userId)
{
    try {
        $result = db()->query(
            "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?",
            [$userId]
        )->fetch();
        return $result['count'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Lấy số dư ví của user
 *
 * @param int $userId
 * @return float
 */
function getUserWalletBalance($userId)
{
    try {
        $user = db()->query(
            "SELECT balance FROM users WHERE id = ?",
            [$userId]
        )->fetch();
        return $user ? (float) $user['balance'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Tạo slug thân thiện URL từ chuỗi tiếng Việt
 *
 * @param string $string Chuỗi cần chuyển thành slug
 * @return string Slug đã được tạo
 */
function generateSlug($string)
{
    // Chuyển thành chữ thường
    $string = mb_strtolower($string, 'UTF-8');

    // Bảng chuyển đổi ký tự có dấu tiếng Việt
    $vietnameseMap = [
        'à' => 'a',
        'á' => 'a',
        'ả' => 'a',
        'ã' => 'a',
        'ạ' => 'a',
        'ă' => 'a',
        'ằ' => 'a',
        'ắ' => 'a',
        'ẳ' => 'a',
        'ẵ' => 'a',
        'ặ' => 'a',
        'â' => 'a',
        'ầ' => 'a',
        'ấ' => 'a',
        'ẩ' => 'a',
        'ẫ' => 'a',
        'ậ' => 'a',
        'đ' => 'd',
        'è' => 'e',
        'é' => 'e',
        'ẻ' => 'e',
        'ẽ' => 'e',
        'ẹ' => 'e',
        'ê' => 'e',
        'ề' => 'e',
        'ế' => 'e',
        'ể' => 'e',
        'ễ' => 'e',
        'ệ' => 'e',
        'ì' => 'i',
        'í' => 'i',
        'ỉ' => 'i',
        'ĩ' => 'i',
        'ị' => 'i',
        'ò' => 'o',
        'ó' => 'o',
        'ỏ' => 'o',
        'õ' => 'o',
        'ọ' => 'o',
        'ô' => 'o',
        'ồ' => 'o',
        'ố' => 'o',
        'ổ' => 'o',
        'ỗ' => 'o',
        'ộ' => 'o',
        'ơ' => 'o',
        'ờ' => 'o',
        'ớ' => 'o',
        'ở' => 'o',
        'ỡ' => 'o',
        'ợ' => 'o',
        'ù' => 'u',
        'ú' => 'u',
        'ủ' => 'u',
        'ũ' => 'u',
        'ụ' => 'u',
        'ư' => 'u',
        'ừ' => 'u',
        'ứ' => 'u',
        'ử' => 'u',
        'ữ' => 'u',
        'ự' => 'u',
        'ỳ' => 'y',
        'ý' => 'y',
        'ỷ' => 'y',
        'ỹ' => 'y',
        'ỵ' => 'y',
    ];

    // Thay thế ký tự có dấu
    $string = strtr($string, $vietnameseMap);

    // Thay khoảng trắng và ký tự đặc biệt bằng dấu gạch ngang
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);

    // Loại bỏ dấu gạch ngang ở đầu và cuối
    $string = trim($string, '-');

    // Loại bỏ dấu gạch ngang liên tiếp
    $string = preg_replace('/-+/', '-', $string);

    return $string;
}

// Get product image URL with SVG fallback
function getProductImage($image, $productName = 'Product', $width = 1070, $height = 500)
{
    // If image exists and is not empty
    if (!empty($image) && file_exists($_SERVER['DOCUMENT_ROOT'] . $image)) {
        return e($image);
    }

    // Generate SVG placeholder
    $text = urlencode($productName);
    return "/public/images/generate-svg?text={$text}&w={$width}&h={$height}";
}

/**
 * Generate responsive image URL with resize
 * @param string $image Original image path
 * @param int $width Target width
 * @param int $quality Image quality (1-100)
 * @return string Resized image URL
 */
function getResizedImage($image, $width = 300, $quality = 80)
{
    if (empty($image)) {
        return '';
    }
    // Return original image instead of using resize service
    return $image;
}

/**
 * Generate srcset for responsive images
 * @param string $image Original image path
 * @return string srcset attribute value
 */
function getResponsiveSrcset($image)
{
    if (empty($image)) {
        return '';
    }

    // Return original image for all sizes
    return $image . " 300w, " .
        $image . " 600w, " .
        $image . " 900w";
}

// Security functions removed to avoid duplicate declaration errors
// getSetting() already exists at line 412

/**
 * Tự động đặt gói duy nhất làm gói mặc định
 * Nếu sản phẩm chỉ có 1 gói, tự động set is_main = 1 cho gói đó
 */
function autoSetMainVariantIfOnlyOne($productId)
{
    try {
        // Đếm số lượng variants của sản phẩm
        $count = db()->query(
            "SELECT COUNT(*) as total FROM product_variants WHERE product_id = ?",
            [$productId]
        )->fetch()['total'];

        // Nếu chỉ có 1 variant
        if ($count == 1) {
            // Set variant đó làm main
            db()->query(
                "UPDATE product_variants SET is_main = 1 WHERE product_id = ?",
                [$productId]
            );
            return true;
        }

        // Nếu không có variant nào là main, set variant đầu tiên làm main
        $hasMain = db()->query(
            "SELECT COUNT(*) as total FROM product_variants WHERE product_id = ? AND is_main = 1",
            [$productId]
        )->fetch()['total'];

        if ($hasMain == 0 && $count > 0) {
            // Lấy variant đầu tiên (theo sort_order)
            $firstVariant = db()->query(
                "SELECT id FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1",
                [$productId]
            )->fetch();

            if ($firstVariant) {
                db()->query(
                    "UPDATE product_variants SET is_main = 1 WHERE id = ?",
                    [$firstVariant['id']]
                );
                return true;
            }
        }

        return false;
    } catch (Exception $e) {
        error_log("Auto set main variant error: " . $e->getMessage());
        return false;
    }
}

/**
 * Gửi thông báo Telegram
 */
function sendTelegramNotification($message)
{
    // Kiểm tra xem cURL có khả dụng không
    if (!function_exists('curl_init')) {
        error_log("cURL is not available on this server");
        return false;
    }

    if (!defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN))
        return false;
    $botToken = TELEGRAM_BOT_TOKEN;
    $chatId = '6269327932';

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    try {
        error_log("Sending Telegram to {$chatId}: {$message}"); // Debug log

        $ch = curl_init();
        if ($ch === false) {
            error_log("Failed to initialize cURL");
            return false;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP code

        if (curl_errno($ch)) {
            error_log("cURL Error: " . curl_error($ch));
        }

        curl_close($ch);

        error_log("Telegram Result ({$httpCode}): " . $result); // Debug log

        return $result;
    } catch (Exception $e) {
        error_log("Telegram notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Gửi thông báo đơn hàng mới qua Telegram
 */
function sendNewOrderTelegramNotification($orderCode, $customerName, $customerEmail, $customerPhone, $orderTotal, $paymentMethod, $items)
{
    if (!defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN))
        return false;

    $message = "🛍️ <b>ĐƠN HÀNG MỚI #{$orderCode}</b>\n";
    $message .= "timestamp: " . date('d/m/Y H:i:s') . "\n\n";

    $message .= "👤 <b>Khách hàng:</b>\n";
    $message .= "• Tên: " . ($customerName ?: 'N/A') . "\n";
    $message .= "• Email: " . $customerEmail . "\n";
    $message .= "• SĐT: " . $customerPhone . "\n\n";

    $message .= "🛒 <b>Sản phẩm:</b>\n";
    foreach ($items as $item) {
        $variantInfo = !empty($item['variant_name']) ? " (" . $item['variant_name'] . ")" : "";
        $message .= "• " . $item['name'] . $variantInfo . "\n";
        $message .= "   x" . $item['quantity'] . " - " . formatMoney($item['total_price']) . "\n";
    }

    $message .= "\n💳 <b>Thanh toán:</b> " . $paymentMethod . "\n";
    $message .= "💰 <b>Tổng cộng:</b> " . formatMoney($orderTotal) . "\n";

    // Gửi tin nhắn
    $result = sendTelegramNotification($message);

    // Parse result để lấy message_id (dùng cho việc edit sau này nếu cần)
    if ($result) {
        $json = json_decode($result, true);
        return $json['result']['message_id'] ?? null;
    }

    return null;
}

/**
 * Xóa tin nhắn Telegram
 */
function deleteTelegramMessage($messageId)
{
    if (!defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN) || empty($messageId))
        return false;
    $botToken = TELEGRAM_BOT_TOKEN;
    $chatId = '6269327932'; // Hardcoded for now based on sendTelegramNotification

    $url = "https://api.telegram.org/bot{$botToken}/deleteMessage";

    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId
    ];

    try {
        $ch = curl_init();
        if ($ch === false)
            return false;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    } catch (Exception $e) {
        error_log("Delete Telegram message error: " . $e->getMessage());
        return false;
    }
}
?>