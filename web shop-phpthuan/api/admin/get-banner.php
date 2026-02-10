<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');

try {
    initSession();

    if (!isAdmin()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized', 'debug' => 'Not admin']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Session error: ' . $e->getMessage()]);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $banner = db()->query("SELECT * FROM banners WHERE id = ?", [$id])->fetch();

    if ($banner) {
        echo json_encode([
            'success' => true,
            'banner' => $banner
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Banner not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
