<?php
/**
 * Email Forward Search - API Endpoint
 */

// Tắt hiển thị lỗi trực tiếp để không làm hỏng JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Helper function phải định nghĩa trước
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?? [];
}

// Kiểm tra IMAP extension
if (!function_exists('imap_open')) {
    jsonResponse(['error' => 'PHP IMAP extension chưa được cài đặt. Vui lòng bật extension php-imap trên server.'], 500);
}

// Test action đơn giản
if (isset($_GET['action']) && $_GET['action'] === 'test') {
    jsonResponse(['success' => true, 'message' => 'API hoạt động bình thường', 'imap' => true]);
}

// Load config and helpers
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/helpers.php';
    initSession(); // Start session to check admin status

    require_once __DIR__ . '/GmailHandler.php';
    $config = require __DIR__ . '/config.php';
    
    if (!$config || !is_array($config)) {
        throw new Exception('Config file không hợp lệ');
    }
} catch (Exception $e) {
    jsonResponse(['error' => 'Lỗi load config: ' . $e->getMessage()], 500);
}

// Get action from query string
$action = $_GET['action'] ?? '';

// Load Sync Logic
require_once __DIR__ . '/sync_logic.php';

if ($action === 'sync') {
    handleSync($config);
} elseif ($action === 'detail') {
    handleDetail($config);
} elseif ($action === 'fetch_from_link') {
    handleFetchFromLink($config);
} else {
    // Default: Search
    handleSearch($config);
}



/**
 * Tìm kiếm email
 */
function handleSearch($config) {
    $input = getJsonInput();
    $email = $input['email'] ?? '';
    $searchType = $input['searchType'] ?? 'temp_code';

    // Verify admin permission for reset password search
    if ($searchType === 'reset_password' && !isAdmin()) {
        jsonResponse(['error' => 'Bạn không có quyền thực hiện hành động này'], 403);
    }
    
    if (empty($email)) {
        jsonResponse(['error' => 'Vui lòng nhập địa chỉ email'], 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Địa chỉ email không hợp lệ'], 400);
    }
    
    try {
        // LIVE SEARCH: Fetch directly from Gmail via IMAP
        $gmail = new GmailHandler($config);
        $gmail->connect();
        $results = $gmail->searchEmails($email, 50);
        $gmail->disconnect();
        
        // Lọc theo loại tìm kiếm (giữ nguyên logic normalize)
        $filteredResults = filterBySearchType($results, $searchType);
        
        jsonResponse([
            'success' => true,
            'count' => count($filteredResults),
            'emails' => $filteredResults,
            'source' => 'live'
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Lỗi: ' . $e->getMessage()], 500);
    }
}

/**
 * Lọc email theo loại tìm kiếm
 */
function filterBySearchType($emails, $searchType) {
    $subjectFilters = [
        'temp_code' => ['mã tạm thời', 'mã tạm thời', 'temporary access code', 'mã truy cập netflix tạm thời', 'mã truy cập', 'verification code'],
        'login_code' => ['mã đăng nhập của bạn', 'sign-in code'],
        'tv_code' => ['cách cập nhật hộ gia đình netflix'],
        'household' => ['gia đình', 'household', 'hộ gia đình', 'primary location', 'vị trí chính', 'mã tạm thời', 'mã tạm thời', 'mã truy cập', 'mã truy cập', 'temporary access'],
        'reset_password' => ['đặt lại mật khẩu', 'reset your password', 'password reset']
    ];
    
    if (!isset($subjectFilters[$searchType])) {
        return $emails;
    }
    
    $filters = $subjectFilters[$searchType];
    
    return array_values(array_filter($emails, function($email) use ($filters) {
        $subject = $email['subject'] ?? '';
        
        // Normalize Unicode to NFC (Precomposed) if intl extension exists
        if (class_exists('Normalizer')) {
             $subject = Normalizer::normalize($subject, Normalizer::FORM_C);
        }
        $subject = mb_strtolower($subject);

        foreach ($filters as $filter) {
            $filterCheck = $filter;
            if (class_exists('Normalizer')) {
                $filterCheck = Normalizer::normalize($filterCheck, Normalizer::FORM_C);
            }
            $filterCheck = mb_strtolower($filterCheck);

            if (mb_strpos($subject, $filterCheck) !== false) {
                return true;
            }
        }
        return false;
    }));
}

/**
 * Lấy chi tiết email
 */
function handleDetail($config) {
    $emailId = $_GET['id'] ?? 0;
    
    if (empty($emailId)) {
        jsonResponse(['error' => 'Missing email ID'], 400);
    }
    
    $gmail = new GmailHandler($config);
    
    try {
        $gmail->connect();
        $email = $gmail->getEmailDetail((int)$emailId);
        $gmail->disconnect();
        
        jsonResponse([
            'success' => true,
            'email' => $email
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Lỗi: ' . $e->getMessage()], 500);
    }
}

/**
 * Lấy mã từ link Netflix trực tiếp
 */
function handleFetchFromLink($config) {
    $input = getJsonInput();
    $link = trim($input['link'] ?? '');
    
    if (empty($link)) {
        jsonResponse(['error' => 'Vui lòng nhập link xác thực Netflix'], 400);
    }
    
    // Validate link
    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        jsonResponse(['error' => 'Link không hợp lệ'], 400);
    }
    
    // Kiểm tra xem có phải link Netflix không
    if (stripos($link, 'netflix.com') === false) {
        jsonResponse(['error' => 'Chỉ chấp nhận link từ netflix.com'], 400);
    }
    
    try {
        // Gọi Puppeteer để lấy nội dung trang
        $nodePath = $config['node_path'];
        $scriptPath = __DIR__ . '/fetch_code.js';
        
        $cmd = "$nodePath \"$scriptPath\" " . escapeshellarg($link);
        $output = shell_exec($cmd . ' 2>&1'); // Capture both stdout and stderr
        
        // DEBUG LOGGING
        file_put_contents(__DIR__ . '/debug_netflix.log', date('Y-m-d H:i:s') . " - Processing Link: $link\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/debug_netflix.log', "Output Length: " . strlen($output) . "\nOutput Content:\n$output\n--------------------------------\n", FILE_APPEND);

        if (empty($output)) {
            throw new Exception('Không thể lấy nội dung từ link. Vui lòng thử lại.');
        }

        // Check for Node errors (Missing Puppeteer, etc.)
        if (stripos($output, 'Error: Cannot find module') !== false || stripos($output, 'internal/modules/cjs/loader') !== false) {
            jsonResponse([
               'success' => false,
               'error' => 'Lỗi Server: Thiếu thư viện Puppeteer. Vui lòng chạy "npm install puppeteer" trên server.',
               'debug_output' => $output
           ], 500);
       }
        
        // Danh sách patterns ngữ cảnh (Ưu tiên cao -> thấp)
        $patterns = [
            // Netflix TV Code (User case: "Nhập mã này trên thiết bị...")
            '/Nhập mã này trên thiết bị.*?(\d{4})/isu',
            '/Enter this code on the device.*?(\d{4})/isu',
            
            // Standard Verification patterns
            '/Mã truy cập tạm thời của bạn là\s*(\d{4})/iu',
            '/Your temporary access code is\s*(\d{4})/iu',
            '/Your Netflix verification code is\s*(\d{4})/iu',
            '/Nhập mã này để đăng nhập\s*(\d{4})/iu'
        ];

        $code = null;

        // 1. Thử tìm theo context patterns trước
        $matchedPattern = '';
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $output, $matches)) {
                $code = $matches[1];
                $matchedPattern = $pattern;
                file_put_contents(__DIR__ . '/debug_netflix.log', "MATCHED PATTERN: $pattern => Code: $code\n", FILE_APPEND);
                break;
            }
        }

        // 2. Fallback: Tìm số 4 chữ số đứng riêng (như logic cũ)
        if (!$code && preg_match('/\b(\d{4})\b/', $output, $matches)) {
             // ... existing year logic ...
             // Thực ra logic check năm nên extract ra function, nhưng ở đây viết inline cũng được
        }

        if ($code || preg_match('/\b(\d{4})\b/', $output, $matches)) {
            // Logic xử lý year filtering
            if (!$code) {
                // Đây là trường hợp fallback match generic
                $potentialCode = $matches[1];
                $year = (int)date('Y');
                
                // Filter năm
                if ((int)$potentialCode >= 2020 && (int)$potentialCode <= $year + 5) {
                    // Try to find another one
                     if (preg_match_all('/\b(\d{4})\b/', $output, $allMatches)) {
                        foreach ($allMatches[1] as $match) {
                            $val = (int)$match;
                            if ($val < 2020 || $val > $year + 5) {
                                $code = $match;
                                break;
                            }
                        }
                    }
                } else {
                    $code = $potentialCode;
                }
            }

            if ($code) {
                jsonResponse([
                    'success' => true,
                    'code' => $code,
                    'message' => 'Lấy mã thành công!',
                    'debug_output' => $output,
                    'matched_pattern' => $matchedPattern
                ]);
            } else {
                // Found numbers but likely years
                 jsonResponse([
                    'success' => false,
                    'error' => 'Không tìm thấy mã hợp lệ (chỉ thấy số năm).',
                    'debug_info' => substr($output, 0, 200)
                ], 400);
            }
        } else {
            // Log output để debug
            error_log('Puppeteer output: ' . $output);
            
            jsonResponse([
                'success' => false,
                'error' => 'Không tìm thấy mã 4 số trong trang. Link có thể đã hết hạn hoặc không hợp lệ.',
                'debug_info' => substr($output, 0, 200) // First 200 chars for debugging
            ], 400);
        }
    } catch (Exception $e) {
        error_log('Error in handleFetchFromLink: ' . $e->getMessage());
        jsonResponse(['error' => 'Lỗi: ' . $e->getMessage()], 500);
    }
}

/**
 * Trigger Sync (Hybrid Search)
 */
function handleSync($config) {
    try {
        $result = syncEmails($config);
        // Clean output buffers to ensure valid JSON
        if (ob_get_length()) ob_clean();
        jsonResponse(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Sync Error: ' . $e->getMessage()], 500);
    }
}
