<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
initSession();

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'error' => 'Bạn không có quyền truy cập'
    ]);
    exit;
}

// CSRF Protection
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Token bảo mật không hợp lệ']);
    exit;
}

try {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Không có file được upload hoặc có lỗi xảy ra');
    }

    $file = $_FILES['image'];
    $fileName = $file['name'];
    $fileTmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Kiểm tra file extension (SVG removed for security - can contain JavaScript)
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Chỉ cho phép upload file ảnh (jpg, jpeg, png, gif, webp)');
    }

    // Kiểm tra file size (max 5MB)
    if ($fileSize > 5 * 1024 * 1024) {
        throw new Exception('File quá lớn. Tối đa 5MB');
    }

    // Tạo tên file mới để tránh trùng
    $newFileName = uniqid() . '-' . time() . '.' . $fileExtension;

    // Đường dẫn lưu file
    $uploadDir = __DIR__ . '/../../public/images/banners/';

    // Tạo thư mục nếu chưa có
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . $newFileName;

    // Di chuyển file
    if (!move_uploaded_file($fileTmpPath, $destination)) {
        throw new Exception('Không thể lưu file');
    }

    // Trả về đường dẫn public
    $publicPath = '/public/images/banners/' . $newFileName;

    echo json_encode([
        'success' => true,
        'path' => $publicPath,
        'message' => 'Upload ảnh thành công'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
