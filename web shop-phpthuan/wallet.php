<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
initSession();

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    setFlash('error', 'Vui lòng đăng nhập');
    redirect('/login?redirect=/wallet');
}

$user = getCurrentUser();

// Xử lý rút tiền (TRƯỚC KHI OUTPUT HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $amount = (float)($_POST['withdraw_amount'] ?? 0);
    $bankName = trim($_POST['bank_name'] ?? '');
    $bankAccount = trim($_POST['bank_account'] ?? '');
    $accountName = trim($_POST['account_name'] ?? '');

    if ($amount < 50000) {
        setFlash('error', 'Số tiền rút tối thiểu là 50,000đ');
    } elseif ($amount > $user['balance']) {
        setFlash('error', 'Số dư không đủ');
    } elseif (empty($bankName) || empty($bankAccount) || empty($accountName)) {
        setFlash('error', 'Vui lòng điền đầy đủ thông tin ngân hàng');
    } else {
        // Trừ tiền khỏi ví
        $newBalance = $user['balance'] - $amount;
        db()->query("UPDATE users SET balance = ? WHERE id = ?", [$newBalance, $user['id']]);

        // Lưu giao dịch
        db()->query(
            "INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, description, reference_id)
             VALUES (?, 'withdraw', ?, ?, ?, ?, 0)",
            [$user['id'], $amount, $user['balance'], $newBalance, "Rút tiền - $bankName - $bankAccount - $accountName"]
        );

        // Tạo thông báo
        createNotification(
            $user['id'],
            'Yêu cầu rút tiền đang xử lý',
            'Yêu cầu rút ' . formatMoney($amount) . ' của bạn đang được xử lý. Tiền sẽ được chuyển trong 1-3 ngày làm việc.',
            'info',
            '/wallet'
        );

        setFlash('success', 'Yêu cầu rút tiền thành công! Tiền sẽ được chuyển trong 1-3 ngày làm việc.');
    }
    redirect('/wallet');
}

$pageTitle = 'Ví tiền';
require_once __DIR__ . '/includes/header.php';

// Lấy lịch sử giao dịch
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$transactions = db()->query(
    "SELECT * FROM wallet_transactions
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT ? OFFSET ?",
    [$user['id'], $perPage, $offset]
)->fetchAll();

$totalTransactions = db()->query(
    "SELECT COUNT(*) as count FROM wallet_transactions WHERE user_id = ?",
    [$user['id']]
)->fetch()['count'];

$totalPages = ceil($totalTransactions / $perPage);
?>

<div class="container wallet-container">
    <h1 class="mb-4" style="font-weight: 600; color: #1d1d1f; font-size: 28px; letter-spacing: -0.02em;">
        <i class="fas fa-wallet"></i> Ví tiền
    </h1>

    <div class="row">
        <!-- Số dư -->
        <div class="col-lg-8">
            <div class="card mb-4 wallet-balance-card" style="border-radius: 18px;">
                <div class="card-body" style="border-radius: 18px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1" style="font-size: 14px;">
                                <i class="fas fa-wallet"></i> Số dư ví
                            </p>
                            <h2 class="mb-0" style="color: #1d1d1f; font-weight: 700;">
                                <?= formatMoney($user['balance']) ?>
                            </h2>
                            <p class="text-muted mb-0 mt-2" style="font-size: 13px;">
                                <i class="fas fa-user"></i> <?= e($user['username']) ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-wallet" style="font-size: 48px; color: #0071e3; opacity: 0.15;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thống kê -->
            <div class="row mb-4">
                <?php
                $depositTotal = db()->query(
                    "SELECT COALESCE(SUM(amount), 0) as total FROM wallet_transactions
                     WHERE user_id = ? AND type = 'deposit' AND description NOT LIKE '%Chờ%'",
                    [$user['id']]
                )->fetch()['total'];

                $purchaseTotal = db()->query(
                    "SELECT COALESCE(SUM(amount), 0) as total FROM wallet_transactions
                     WHERE user_id = ? AND type = 'purchase'",
                    [$user['id']]
                )->fetch()['total'];
                ?>
                <div class="col-md-6">
                    <div class="card text-center" style="border-radius: 18px;">
                        <div class="card-body" style="border-radius: 18px;">
                            <i class="fas fa-arrow-down fa-2x text-success mb-2"></i>
                            <h4 class="mb-0 text-success"><?= formatMoney($depositTotal) ?></h4>
                            <p class="text-muted mb-0 small">Tổng nạp</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-center" style="border-radius: 18px;">
                        <div class="card-body" style="border-radius: 18px;">
                            <i class="fas fa-arrow-up fa-2x text-danger mb-2"></i>
                            <h4 class="mb-0 text-danger"><?= formatMoney($purchaseTotal) ?></h4>
                            <p class="text-muted mb-0 small">Tổng chi</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lịch sử giao dịch -->
            <div class="card" style="border-radius: 18px;">
                <div class="card-header bg-light" style="border-radius: 18px 18px 0 0;">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Lịch sử giao dịch</h5>
                </div>
                <div class="card-body" style="border-radius: 0 0 18px 18px;">
                    <?php if (empty($transactions)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p class="mb-0">Chưa có giao dịch nào</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Loại</th>
                                        <th>Mô tả</th>
                                        <th class="text-end">Số tiền</th>
                                        <th class="text-end">Số dư</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $trans): ?>
                                        <tr>
                                            <td><?= formatDate($trans['created_at']) ?></td>
                                            <td>
                                                <?php
                                                $typeIcons = [
                                                    'deposit' => '<i class="fas fa-arrow-down text-success"></i> Nạp tiền',
                                                    'withdraw' => '<i class="fas fa-arrow-up text-danger"></i> Rút tiền',
                                                    'purchase' => '<i class="fas fa-shopping-cart text-primary"></i> Mua hàng',
                                                    'refund' => '<i class="fas fa-undo text-info"></i> Hoàn tiền',
                                                    'bonus' => '<i class="fas fa-gift text-warning"></i> Thưởng'
                                                ];
                                                echo $typeIcons[$trans['type']] ?? $trans['type'];
                                                ?>
                                            </td>
                                            <td><?= e($trans['description']) ?></td>
                                            <td class="text-end">
                                                <?php
                                                $amountClass = in_array($trans['type'], ['deposit', 'refund', 'bonus']) ? 'text-success' : 'text-danger';
                                                $prefix = in_array($trans['type'], ['deposit', 'refund', 'bonus']) ? '+' : '-';
                                                ?>
                                                <strong class="<?= $amountClass ?>">
                                                    <?= $prefix ?><?= formatMoney($trans['amount']) ?>
                                                </strong>
                                            </td>
                                            <td class="text-end"><?= formatMoney($trans['balance_after']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Nút hành động -->
            <div class="card mb-4" style="border-radius: 18px;">
                <div class="card-header bg-light" style="border-radius: 18px 18px 0 0;">
                    <h6 class="mb-0"><i class="fas fa-bolt"></i> Hành động nhanh</h6>
                </div>
                <div class="card-body" style="border-radius: 0 0 18px 18px;">
                    <div class="row g-2">
                        <div class="col-6">
                            <button id="depositModalBtn" type="button" class="pill-button pill-button-green w-100" data-bs-toggle="modal" data-bs-target="#depositModal">
                                <i class="fas fa-plus-circle"></i> Nạp tiền
                            </button>
                        </div>
                        <div class="col-6">
                            <button id="withdrawModalBtn" type="button" class="pill-button pill-button-gray w-100" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                                <i class="fas fa-arrow-circle-up"></i> Rút tiền
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hướng dẫn -->
            <div class="card" style="border-radius: 18px;">
                <div class="card-header bg-light" style="border-radius: 18px 18px 0 0;">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Lưu ý</h6>
                </div>
                <div class="card-body" style="border-radius: 0 0 18px 18px;">
                    <ul class="small mb-0">
                        <li class="mb-2"><strong>Nạp tối thiểu:</strong> 10,000đ</li>
                        <li class="mb-2"><strong>Rút tối thiểu:</strong> 50,000đ</li>
                        <li class="mb-2"><strong>Thời gian rút:</strong> 1-3 ngày làm việc</li>
                        <li class="mb-0"><strong>Hỗ trợ:</strong> 24/7 qua chat</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nạp tiền -->
<div class="modal fade" id="depositModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nạp Tiền Vào Ví
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php
                // Lấy thông tin ngân hàng
                try {
                    if (function_exists('getBankSettings')) {
                        $bankSettings = getBankSettings();
                        $bankName = $bankSettings['bank_name'];
                        $bankAccount = $bankSettings['bank_account'];
                        $accountName = $bankSettings['bank_account_name'];
                        $bankCode = $bankSettings['bank_code'];
                    } else {
                        throw new Exception('getBankSettings not available');
                    }
                } catch (Exception $e) {
                    error_log("getBankSettings failed: " . $e->getMessage());
                    $bankName = 'MB Bank';
                    $bankAccount = '567892868';
                    $accountName = 'Ngô Anh Dũng';
                    $bankCode = 'MB';
                }

                $depositContent = 'NAP ' . $user['username'];
                ?>

                <!-- Step 1: Nhập số tiền -->
                <div id="amountStep">
                    <div class="mb-3">
                        <label class="form-label">Số tiền nạp <span class="text-danger">*</span></label>
                        <input type="number" id="depositAmount" class="form-control" min="10000" step="1000" placeholder="Tối thiểu 10,000đ">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chọn nhanh:</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('depositAmount').value=50000">
                                50,000đ
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('depositAmount').value=100000">
                                100,000đ
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('depositAmount').value=200000">
                                200,000đ
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('depositAmount').value=500000">
                                500,000đ
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('depositAmount').value=1000000">
                                1,000,000đ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Thông tin thanh toán (ẩn ban đầu) -->
                <div id="paymentInfoStep" style="display: none;">
                    <style>
                        .payment-amount {
                            text-align: center;
                            margin-bottom: 15px;
                            padding-bottom: 12px;
                            border-bottom: 2px solid #e9ecef;
                        }
                        .payment-amount h5 {
                            color: #dc3545;
                            font-weight: 700;
                            margin: 0;
                            font-size: 1.25rem;
                        }
                        .payment-info-box, .qr-box {
                            background: #f8f9fa;
                            border: 1px solid #dee2e6;
                            border-radius: 8px;
                            padding: 12px;
                            height: 100%;
                            display: flex;
                            flex-direction: column;
                        }
                        .payment-info-box h6, .qr-box h6 {
                            color: #495057;
                            font-weight: 600;
                            margin-bottom: 10px;
                            font-size: 0.95rem;
                        }
                        .info-rows-container {
                            display: flex;
                            flex-direction: column;
                            justify-content: space-evenly;
                            flex: 1;
                        }
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 6px 0;
                            border-bottom: 1px solid #e9ecef;
                        }
                        .info-row:last-child {
                            border-bottom: none;
                        }
                        .info-label {
                            color: #6c757d;
                            font-size: 0.95rem;
                            font-weight: 500;
                        }
                        .info-value {
                            color: #212529;
                            font-weight: 600;
                            font-size: 1rem;
                        }
                        .info-value.amount {
                            color: #dc3545;
                            font-size: 1.15rem;
                        }
                        .info-value code {
                            background: #fff;
                            padding: 2px 6px;
                            border-radius: 4px;
                            color: #495057;
                            font-size: 0.95rem;
                        }
                        .btn-copy {
                            background: #007bff;
                            color: white;
                            border: none;
                            padding: 3px 8px;
                            border-radius: 4px;
                            font-size: 0.75rem;
                            cursor: pointer;
                            margin-left: 6px;
                        }
                        .btn-copy:hover {
                            background: #0056b3;
                        }
                        .qr-box {
                            text-align: center;
                            justify-content: center;
                            align-items: center;
                        }
                        .qr-box img {
                            max-width: 220px;
                            width: 100%;
                            border-radius: 6px;
                            margin: 0 auto;
                            display: block;
                        }
                        .qr-note {
                            color: #6c757d;
                            font-size: 0.8rem;
                            margin-top: 6px;
                            margin-bottom: 0;
                        }
                        .payment-note {
                            background: #fff3cd;
                            border: 1px solid #ffc107;
                            border-radius: 6px;
                            padding: 8px 12px;
                            margin-top: 12px;
                            font-size: 0.88rem;
                        }

                        /* Desktop: Always show QR section */
                        @media (min-width: 992px) {
                            .qr-code-section {
                                display: block !important;
                            }
                            .mobile-qr-toggle {
                                display: none;
                            }
                        }

                        /* Mobile Payment Modal Styles */
                        @media (max-width: 767px) {
                            /* Show QR when toggled */
                            #qrCodeSection.show {
                                display: block !important;
                                width: 100% !important;
                                margin-top: 1rem !important;
                            }
                            /* Hide payment info when QR is shown */
                            #paymentInfoSection.hide {
                                display: none !important;
                            }
                            .payment-info-box {
                                margin-bottom: 0;
                            }
                            .mobile-qr-toggle {
                                text-align: center;
                            }
                            .qr-box {
                                padding: 0.5rem;
                                overflow: hidden;
                            }
                            .qr-box img {
                                max-width: none;
                                width: 130%;
                                height: auto;
                                margin-left: -15%;
                                margin-right: -15%;
                                display: block;
                            }
                            .qr-box h6 {
                                font-size: 16px;
                                margin-bottom: 1rem;
                            }
                            .qr-box .qr-note {
                                font-size: 13px;
                                margin-top: 0.5rem;
                            }
                        }
                    </style>

                    <div class="payment-amount">
                        <h5>Số tiền: <span id="displayAmount"></span></h5>
                    </div>

                    <div class="row g-2">
                        <!-- Left: Payment Info -->
                        <div class="col-md-6">
                            <div class="payment-info-box" id="paymentInfoSection">
                                <h6><i class="fas fa-info-circle"></i> Thông tin chuyển khoản</h6>

                                <div class="info-rows-container">
                                    <div class="info-row">
                                        <span class="info-label">Ngân hàng:</span>
                                        <span class="info-value"><?= e($bankName) ?></span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Số TK:</span>
                                        <span class="info-value">
                                            <code><?= e($bankAccount) ?></code>
                                            <button class="btn-copy" onclick="copyToClipboard('<?= e($bankAccount) ?>')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Chủ TK:</span>
                                        <span class="info-value"><?= e($accountName) ?></span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Số tiền:</span>
                                        <span class="info-value amount" id="displayAmount2"></span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Nội dung:</span>
                                        <span class="info-value">
                                            <code id="payment_content"><?= e($depositContent) ?></code>
                                            <button class="btn-copy" onclick="copyToClipboard(document.getElementById('payment_content').innerText)">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>

                                <!-- Mobile QR Toggle Button -->
                                <div class="mobile-qr-toggle d-md-none mt-3">
                                    <button type="button" class="btn btn-outline-primary w-100" id="toggleQrBtn" onclick="toggleDepositQR()">
                                        <i class="fas fa-qrcode"></i> Xem mã QR
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: QR Code -->
                        <div class="col-md-6 qr-code-section d-none d-md-block" id="qrCodeSection">
                            <div class="qr-box">
                                <h6><i class="fas fa-qrcode"></i> Quét mã QR</h6>
                                <img id="depositQR" src="" alt="QR Code">
                                <p class="qr-note">Quét bằng app ngân hàng</p>

                                <!-- Mobile: Back to payment info button -->
                                <div class="mobile-qr-back d-md-none mt-3">
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="toggleDepositQR()">
                                        <i class="fas fa-arrow-left"></i> Quay lại thông tin chuyển khoản
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="payment-note">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Lưu ý:</strong> Chuyển khoản đúng số tiền và nội dung để xử lý tự động
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="resetDepositModal()">
                    <i class="fas fa-times"></i> <span class="d-none d-md-inline">Đóng</span><span class="d-md-none">Đóng</span>
                </button>
                <button type="button" class="btn btn-success" id="showDepositInfoBtn" onclick="showDepositInfo()">
                    <i class="fas fa-arrow-right"></i> <span class="d-none d-md-inline">Tiếp tục</span><span class="d-md-none">Tiếp tục</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rút tiền -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-arrow-circle-up"></i> Rút tiền về ngân hàng
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Số dư hiện tại: <strong><?= formatMoney($user['balance']) ?></strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Số tiền rút <span class="text-danger">*</span></label>
                        <input type="number" name="withdraw_amount" class="form-control" min="50000" step="1000"
                               max="<?= $user['balance'] ?>" placeholder="Tối thiểu 50,000đ" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ngân hàng <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control" placeholder="VD: Vietcombank" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Số tài khoản <span class="text-danger">*</span></label>
                        <input type="text" name="bank_account" class="form-control" placeholder="VD: 1234567890" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chủ tài khoản <span class="text-danger">*</span></label>
                        <input type="text" name="account_name" class="form-control" placeholder="VD: NGUYEN VAN A" required>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Lưu ý:</strong> Tiền sẽ được chuyển trong 1-3 ngày làm việc. Vui lòng kiểm tra kỹ thông tin ngân hàng trước khi gửi.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="submit" name="withdraw" class="btn btn-warning">
                        <i class="fas fa-check"></i> Xác nhận rút tiền
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showDepositInfo() {
    const amount = parseInt(document.getElementById('depositAmount').value);

    if (!amount || amount < 10000) {
        alert('Vui lòng nhập số tiền nạp tối thiểu 10,000đ');
        return;
    }

    const depositContent = '<?= $depositContent ?>';

    // Update UI
    const formattedAmount = amount.toLocaleString('vi-VN') + 'đ';
    document.getElementById('displayAmount').textContent = formattedAmount;
    document.getElementById('displayAmount2').textContent = formattedAmount;

    // Generate QR
    const qrUrl = `https://img.vietqr.io/image/<?= $bankCode ?>-<?= $bankAccount ?>-print.png?amount=${amount}&addInfo=${encodeURIComponent(depositContent)}&accountName=<?= urlencode($accountName) ?>`;
    document.getElementById('depositQR').src = qrUrl;

    // Show payment info, hide amount step
    document.getElementById('amountStep').style.display = 'none';
    document.getElementById('paymentInfoStep').style.display = 'block';
    document.getElementById('showDepositInfoBtn').style.display = 'none';
}

function resetDepositModal() {
    // Reset to step 1
    document.getElementById('amountStep').style.display = 'block';
    document.getElementById('paymentInfoStep').style.display = 'none';
    document.getElementById('showDepositInfoBtn').style.display = 'inline-block';
    document.getElementById('depositAmount').value = '';

    // Reset mobile QR toggle
    const qrSection = document.getElementById('qrCodeSection');
    const paymentSection = document.getElementById('paymentInfoSection');
    const toggleBtn = document.getElementById('toggleQrBtn');

    qrSection.classList.remove('show');
    paymentSection.classList.remove('hide');
    if (toggleBtn) {
        toggleBtn.innerHTML = '<i class="fas fa-qrcode"></i> Xem mã QR';
    }
}

function toggleDepositQR() {
    const qrSection = document.getElementById('qrCodeSection');
    const paymentSection = document.getElementById('paymentInfoSection');
    const toggleBtn = document.getElementById('toggleQrBtn');

    if (qrSection.classList.contains('show')) {
        // Hide QR, show payment info
        qrSection.classList.remove('show');
        paymentSection.classList.remove('hide');
        toggleBtn.innerHTML = '<i class="fas fa-qrcode"></i> Xem mã QR';
    } else {
        // Show QR, hide payment info
        qrSection.classList.add('show');
        paymentSection.classList.add('hide');
        toggleBtn.innerHTML = '<i class="fas fa-arrow-left"></i> Quay lại thông tin';
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show temporary success message
        const originalText = event.target.innerHTML;
        event.target.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => {
            event.target.innerHTML = originalText;
        }, 1000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);

        const originalText = event.target.innerHTML;
        event.target.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => {
            event.target.innerHTML = originalText;
        }, 1000);
    });
}

// Reset modal when closed
document.getElementById('depositModal')?.addEventListener('hidden.bs.modal', function () {
    resetDepositModal();
});
</script>

<!-- Apple Style for Wallet Page -->
<style>
/* Apple-inspired global styles for wallet page */
.wallet-container {
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Arial, sans-serif;
}

/* Card styling */
.wallet-container .card {
    border: 1px solid #d2d2d7;
    border-radius: 18px;
    box-shadow: none;
    background: #fbfbfd;
}

.wallet-container .card-header {
    background: #f5f5f7 !important;
    border-bottom: 1px solid #d2d2d7;
    border-radius: 18px 18px 0 0 !important;
    color: #1d1d1f !important;
    padding: 16px 20px;
}

.wallet-container .card-header h5,
.wallet-container .card-header h6 {
    font-weight: 600;
    font-size: 17px;
    letter-spacing: -0.02em;
    color: inherit !important;
}

.wallet-container .card-header h6 {
    font-size: 15px;
}

.wallet-container .card-body {
    padding: 20px;
    background: #fbfbfd;
    border-radius: 0 0 18px 18px;
}

/* Remove hover effect for cards */
.wallet-container .card {
    transition: none !important;
}

.wallet-container .card:hover {
    transform: none !important;
    box-shadow: none !important;
}

/* Button styling */
.wallet-container .btn-outline-primary {
    border-color: #0071e3;
    color: #0071e3;
    border-radius: 12px;
    padding: 8px 16px;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);
}

.wallet-container .btn-outline-primary:hover {
    background: #0071e3;
    border-color: #0071e3;
    color: white;
}

/* Text styling */
.wallet-container strong {
    color: #1d1d1f;
    font-weight: 600;
}

.wallet-container p {
    color: #1d1d1f;
}

.wallet-container .text-muted {
    color: #86868b !important;
}

.wallet-container h2,
.wallet-container h4 {
    color: #1d1d1f;
    font-weight: 600;
    letter-spacing: -0.02em;
}

/* Input styling */
.wallet-container .form-control,
.wallet-container .form-select {
    border: 1px solid #d2d2d7;
    border-radius: 10px;
    padding: 10px 14px;
    transition: all 0.2s ease;
    background: #fbfbfd;
}

.wallet-container .form-control:focus,
.wallet-container .form-select:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
    background: white;
}

.wallet-container .form-label {
    font-weight: 500;
    color: #1d1d1f;
    font-size: 14px;
    margin-bottom: 8px;
}

/* Alert styling */
.wallet-container .alert {
    border-radius: 12px;
    border: 1px solid #d2d2d7;
    background: #f5f5f7;
}

.wallet-container .alert-info {
    background: #e5f2ff;
    border-color: #0071e3;
    color: #1d1d1f;
}

/* Table styling */
.wallet-container .table {
    border-color: #d2d2d7;
}

.wallet-container .table thead {
    background: #f5f5f7;
}

.wallet-container .table th,
.wallet-container .table td {
    border-color: #e5e5ea;
    color: #1d1d1f;
}

/* Icon styling */
.wallet-container .text-primary {
    color: #0071e3 !important;
}

.wallet-container .text-success {
    color: #34c759 !important;
}

.wallet-container .text-danger {
    color: #ff3b30 !important;
}

.wallet-container .text-warning {
    color: #ff9500 !important;
}

.wallet-container .text-info {
    color: #5ac8fa !important;
}

/* Pagination */
.wallet-container .pagination .page-link {
    border-radius: 8px;
    margin: 0 2px;
    border: 1px solid #d2d2d7;
    color: #0071e3;
}

.wallet-container .pagination .page-item.active .page-link {
    background: #0071e3;
    border-color: #0071e3;
}

/* Special card borders */
.wallet-container .card.border-warning {
    border: 2px solid #ff9500 !important;
}

/* Wallet balance card */
.wallet-balance-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
    border: 2px solid #e5e5ea !important;
}

/* Quick Action Cards */
.quick-action-card {
    background: linear-gradient(135deg, #f5f5f7 0%, #e8e8ed 100%);
    border: 1px solid #d2d2d7;
    border-radius: 18px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);
}

.quick-action-card:hover {
    background: linear-gradient(135deg, #0071e3 0%, #0077ed 100%);
    border-color: #0071e3;
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 113, 227, 0.3);
}

.quick-action-card i {
    font-size: 48px;
    color: #0071e3;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.quick-action-card:hover i {
    color: white;
}

.quick-action-card h5 {
    color: #1d1d1f;
    font-weight: 600;
    margin-bottom: 5px;
    transition: all 0.3s ease;
}

.quick-action-card:hover h5 {
    color: white;
}

.quick-action-card p {
    color: #86868b;
    font-size: 14px;
    margin: 0;
    transition: all 0.3s ease;
}

.quick-action-card:hover p {
    color: rgba(255, 255, 255, 0.8);
}

/* Modal styling */
.wallet-container .modal-content {
    border-radius: 18px;
    border: 1px solid #d2d2d7;
}

.wallet-container .modal-header {
    border-radius: 18px 18px 0 0;
    border-bottom: 1px solid #d2d2d7;
}

.wallet-container .modal-header.bg-primary {
    background: linear-gradient(135deg, #0071e3 0%, #0077ed 100%) !important;
}

.wallet-container .modal-header.bg-warning {
    background: linear-gradient(135deg, #ff9500 0%, #ff9f0a 100%) !important;
}

.wallet-container .modal-body {
    background: #fbfbfd;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
