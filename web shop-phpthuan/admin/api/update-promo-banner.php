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

$index = (int) ($_POST['index'] ?? -1);
$link = trim($_POST['link'] ?? '');
$imageUrl = trim($_POST['image_url'] ?? '');

if ($index < 0 || $index > 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid promo index']);
    exit;
}

try {
    // Get current promo banners
    $result = db()->query("SELECT setting_value FROM settings WHERE setting_key = 'promo_banners'")->fetch();
    $promoBanners = $result ? json_decode($result['setting_value'], true) : [
        ['image' => '/public/images/banners/vpn-banner.svg', 'link' => '/products?category=vpn-bao-mat-mang'],
        ['image' => '/public/images/banners/esim-banner.svg', 'link' => '/products?category=the-gioi-ai']
    ];

    $image = $promoBanners[$index]['image'] ?? '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../public/images/banners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '-' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            $image = '/public/images/banners/' . $filename;

            // Delete old image if it exists
            if (
                !empty($promoBanners[$index]['image']) &&
                file_exists(__DIR__ . '/../..' . $promoBanners[$index]['image'])
            ) {
                @unlink(__DIR__ . '/../..' . $promoBanners[$index]['image']);
            }
        }
    } elseif (!empty($imageUrl)) {
        $image = $imageUrl;
    }

    // Update promo banner
    $promoBanners[$index] = [
        'image' => $image,
        'link' => $link
    ];

    // Save to database
    $jsonData = json_encode($promoBanners);

    // Use updateSetting helper instead of direct query logic for simplicity and consistency
    updateSetting('promo_banners', $jsonData);

    echo json_encode(['success' => true, 'message' => 'Promo banner updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
