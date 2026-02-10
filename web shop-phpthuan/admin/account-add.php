<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
initSession();

if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Thêm tài khoản vào kho';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $note = trim($_POST['note'] ?? '');

    $errors = [];

    if (!$productId) $errors[] = 'Vui lòng chọn sản phẩm';
    if (empty($username)) $errors[] = 'Username không được để trống';
    if (empty($password)) $errors[] = 'Password không được để trống';

    if (empty($errors)) {
        db()->query(
            "INSERT INTO accounts_stock (product_id, account_username, account_password, account_note, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'available', NOW(), NOW())",
            [$productId, $username, $password, $note]
        );

        // Cập nhật stock_quantity
        db()->query("UPDATE products SET stock_quantity = stock_quantity + 1 WHERE id = ?", [$productId]);

        setFlash('success', 'Đã thêm tài khoản vào kho');
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
                <h2><i class="fas fa-plus-circle"></i> Thêm tài khoản vào kho</h2>
                <div>
                    <a href="/admin/account-import.php" class="btn btn-success me-2">
                        <i class="fas fa-file-upload"></i> Import hàng loạt
                    </a>
                    <a href="/admin/accounts.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
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

            <div class="row justify-content-center">
                <div class="col-lg-6">
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
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" required value="<?= e($_POST['username'] ?? '') ?>" placeholder="user@example.com">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="text" name="password" class="form-control" required value="<?= e($_POST['password'] ?? '') ?>" placeholder="********">
                                    <div class="form-text">Lưu ý: Nên để dạng text để dễ copy</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Ghi chú</label>
                                    <textarea name="note" class="form-control" rows="3" placeholder="Thông tin thêm về tài khoản..."><?= e($_POST['note'] ?? '') ?></textarea>
                                </div>

                                <hr>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Lưu tài khoản
                                    </button>
                                    <a href="/admin/accounts.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Hủy
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-info-circle"></i> Lưu ý</h6>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 small">
                                <li class="mb-2">Tài khoản sẽ được tự động giao khi đơn hàng được thanh toán</li>
                                <li class="mb-2">Kiểm tra kỹ thông tin trước khi lưu</li>
                                <li>Sử dụng Import để thêm nhiều tài khoản cùng lúc</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
</body>
</html>
