<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');

try {
    initSession();

    if (!isAdmin()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Session error: ' . $e->getMessage()]);
    exit;
}

$bannerId = (int) ($_POST['banner_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$link = trim($_POST['link'] ?? '');
$description = trim($_POST['description'] ?? '');
$sortOrder = (int) ($_POST['sort_order'] ?? 0);
$status = $_POST['status'] ?? 'active';

if ($bannerId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid banner ID']);
    exit;
}

if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

try {
    // Get current banner info
    $banner = db()->query("SELECT * FROM banners WHERE id = ?", [$bannerId])->fetch();

    if (!$banner) {
        echo json_encode(['success' => false, 'error' => 'Banner not found']);
        exit;
    }

    $image = $banner['image'];

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../public/images/banners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            $image = '/public/images/banners/' . $filename;

            // Delete old image
            if ($banner['image'] && file_exists(__DIR__ . '/../..' . $banner['image'])) {
                @unlink(__DIR__ . '/../..' . $banner['image']);
            }
        }
    }

    // Update banner
    db()->query(
        "UPDATE banners SET title = ?, image = ?, link = ?, description = ?, sort_order = ?, status = ? WHERE id = ?",
        [$title, $image, $link, $description, $sortOrder, $status, $bannerId]
    );

    echo json_encode(['success' => true, 'message' => 'Banner updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
