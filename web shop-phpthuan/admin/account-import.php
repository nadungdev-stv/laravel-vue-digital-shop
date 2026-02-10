<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Import tài khoản hàng loạt';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $accountsData = trim($_POST['accounts_data'] ?? '');

    $errors = [];
    $imported = 0;

    if (!$productId) {
        $errors[] = 'Vui lòng chọn sản phẩm';
    }

    if (empty($accountsData)) {
        $errors[] = 'Vui lòng nhập dữ liệu tài khoản';
    }

    if (empty($errors)) {
        $lines = explode("\n", $accountsData);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Format: username|password|note hoặc username|password
            $parts = explode('|', $line);

            if (count($parts) >= 2) {
                $username = trim($parts[0]);
                $password = trim($parts[1]);
                $note = isset($parts[2]) ? trim($parts[2]) : '';

                if (!empty($username) && !empty($password)) {
                    db()->query(
                        "INSERT INTO accounts_stock (product_id, account_username, account_password, account_note, status, created_at, updated_at)
                         VALUES (?, ?, ?, ?, 'available', NOW(), NOW())",
                        [$productId, $username, $password, $note]
                    );
                    $imported++;
                }
            }
        }

        // Cập nhật stock_quantity
        db()->query("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?", [$imported, $productId]);

        setFlash('success', "Đã import thành công {$imported} tài khoản");
        redirect('/admin/accounts.php');
    }
}

$products = db()->query("SELECT id, name FROM products WHERE status != 'inactive' ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/admin-apple-style.css">
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
            <div class="d-flex justify-content-between align-items-center my-4">
                <h2><i class="fas fa-file-upload"></i> Import tài khoản hàng loạt</h2>
                <a href="/admin/accounts.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Sản phẩm <span class="text-danger">*</span></label>
                                    <select name="product_id" class="form-select" required>
                                        <option value="">Chọn sản phẩm...</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= $product['id'] ?>" <?= ($_POST['product_id'] ?? '') == $product['id'] ? 'selected' : '' ?>>
                                                <?= e($product['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Dữ liệu tài khoản <span class="text-danger">*</span></label>
                                    <textarea name="accounts_data" class="form-control font-monospace" rows="15" required placeholder="Nhập mỗi tài khoản trên một dòng..."><?= e($_POST['accounts_data'] ?? '') ?></textarea>
                                    <div class="form-text">
                                        <strong>Format:</strong> username|password|note<br>
                                        Mỗi tài khoản trên một dòng, các thông tin cách nhau bằng dấu |<br>
                                        Note có thể bỏ qua
                                    </div>
                                </div>

                                <hr>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-file-upload"></i> Import tài khoản
                                    </button>
                                    <a href="/admin/accounts.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Hủy
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-book"></i> Hướng dẫn</h6>
                        </div>
                        <div class="card-body">
                            <h6>Định dạng dữ liệu:</h6>
                            <pre class="bg-light p-3 rounded small">username1|password1|note1
username2|password2|note2
username3|password3</pre>

                            <h6 class="mt-3">Ví dụ thực tế:</h6>
                            <pre class="bg-light p-3 rounded small">user1@gmail.com|Pass123!|VIP
user2@gmail.com|Pass456!
user3@gmail.com|Pass789!|Premium</pre>

                            <div class="alert alert-info small mt-3 mb-0">
                                <i class="fas fa-info-circle"></i>
                                <strong>Lưu ý:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Mỗi dòng là một tài khoản</li>
                                    <li>Sử dụng dấu | để phân tách</li>
                                    <li>Note có thể bỏ qua</li>
                                    <li>Dòng trống sẽ được bỏ qua</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-lightbulb"></i> Tips</h6>
                        </div>
                        <div class="card-body small">
                            <ul class="mb-0">
                                <li class="mb-2">Copy dữ liệu từ Excel/Google Sheets</li>
                                <li class="mb-2">Kiểm tra kỹ format trước khi import</li>
                                <li class="mb-2">Import từng đợt nhỏ để dễ kiểm soát</li>
                                <li>Backup dữ liệu trước khi import số lượng lớn</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
</body>
</html>
