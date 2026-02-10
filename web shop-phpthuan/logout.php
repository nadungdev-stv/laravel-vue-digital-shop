<?php
require_once __DIR__ . '/includes/helpers.php';
initSession();

// Xóa tất cả session
session_destroy();

// Redirect về trang chủ
redirect('/?message=logout');
?>
