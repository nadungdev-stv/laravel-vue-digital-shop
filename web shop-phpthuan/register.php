<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
initSession();

// Nếu đã đăng nhập thì redirect
if (isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');

    // Validate
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif (!isValidEmail($email)) {
        $error = 'Email không hợp lệ';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $confirmPassword) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        // Check username exists
        $stmt = db()->query("SELECT id FROM users WHERE username = ?", [$username]);
        if ($stmt->fetch()) {
            $error = 'Tên đăng nhập đã tồn tại';
        } else {
            // Check email exists
            $stmt = db()->query("SELECT id FROM users WHERE email = ?", [$email]);
            if ($stmt->fetch()) {
                $error = 'Email đã được sử dụng';
            } else {
                // Insert new user
                $hashedPassword = hashPassword($password);
                $stmt = db()->query(
                    "INSERT INTO users (username, email, password, full_name, role, status)
                     VALUES (?, ?, ?, ?, 'user', 'active')",
                    [$username, $email, $hashedPassword, $fullName]
                );

                if ($stmt) {
                    $newUserId = db()->lastInsertId();

                    // Tìm và gán các đơn hàng guest có cùng email
                    $guestOrders = db()->query(
                        "SELECT order_code FROM orders WHERE customer_email = ? AND user_id IS NULL",
                        [$email]
                    )->fetchAll();

                    if (!empty($guestOrders)) {
                        // Cập nhật user_id cho các đơn hàng guest
                        db()->query(
                            "UPDATE orders SET user_id = ? WHERE customer_email = ? AND user_id IS NULL",
                            [$newUserId, $email]
                        );

                        $orderCount = count($guestOrders);

                        // Gửi thông báo Telegram
                        $message = "🎉 <b>ĐĂNG KÝ THÀNH VIÊN MỚI</b>\n\n";
                        $message .= "👤 Tên: <b>" . htmlspecialchars($fullName) . "</b>\n";
                        $message .= "📧 Email: " . htmlspecialchars($email) . "\n";
                        $message .= "🆔 Username: " . htmlspecialchars($username) . "\n";
                        $message .= "🔄 Đã đồng bộ: {$orderCount} đơn hàng cũ\n";
                        $message .= "⏰ Thời gian: " . date('d/m/Y H:i:s');

                        // Check if sendTelegramNotification exists before calling
                        if (function_exists('sendTelegramNotification')) {
                            sendTelegramNotification($message);
                        }

                        // Tạo thông báo
                        createNotification(
                            $newUserId,
                            'Chào mừng bạn đến với ' . getSetting('site_name', 'Veyrix Shop') . '!',
                            'Tài khoản của bạn đã được tạo thành công! Chúng tôi đã tìm thấy ' . $orderCount . ' đơn hàng với email này và đã thêm vào tài khoản của bạn.',
                            'success',
                            '/orders'
                        );

                        setFlash('success', 'Đăng ký thành công! ' . $orderCount . ' đơn hàng trước đây đã được thêm vào tài khoản của bạn.');
                    } else {
                        setFlash('success', 'Đăng ký thành công!');

                        // Gửi thông báo Telegram
                        $message = "🎉 <b>ĐĂNG KÝ THÀNH VIÊN MỚI</b>\n\n";
                        $message .= "👤 Tên: <b>" . htmlspecialchars($fullName) . "</b>\n";
                        $message .= "📧 Email: " . htmlspecialchars($email) . "\n";
                        $message .= "🆔 Username: " . htmlspecialchars($username) . "\n";
                        $message .= "⏰ Thời gian: " . date('d/m/Y H:i:s');

                        // Check if sendTelegramNotification exists before calling
                        if (function_exists('sendTelegramNotification')) {
                            sendTelegramNotification($message);
                        }
                    }

                    // Auto-login user và merge guest cart
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';

                    // Merge guest cart vào user cart
                    mergeGuestCartToUser($newUserId);

                    redirect('/');
                } else {
                    $error = 'Có lỗi xảy ra, vui lòng thử lại';
                }
            }
        }
    }
}

$pageTitle = 'Đăng ký';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm" style="border-radius: 12px; overflow: hidden; border: 1px solid #dee2e6;">
                <div class="row g-0">
                    <!-- Login Section (Left - Smaller) -->
                    <div class="col-md-5 auth-section login-section-page" style="background: #f8f9fa; padding: 3rem;">
                        <div class="mb-4">
                            <h2 class="fw-bold mb-2">Đăng nhập</h2>
                            <p class="text-muted mb-0">Chào mừng bạn trở lại</p>
                        </div>

                        <form action="/login.php" method="POST" class="login-form">
                            <div class="mb-3">
                                <label class="form-label">Tên đăng nhập</label>
                                <input type="text" name="username" class="form-control" style="border-radius: 10px;"
                                    autocomplete="username" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mật khẩu</label>
                                <input type="password" name="password" class="form-control" style="border-radius: 10px;"
                                    autocomplete="off" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="rememberLogin">
                                <label class="form-check-label" for="rememberLogin">Ghi nhớ đăng nhập</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold"
                                style="border-radius: 8px; padding: 12px;">
                                Đăng nhập
                            </button>

                            <div class="text-center mt-3">
                                <a href="/forgot-password.php" class="text-muted text-decoration-none small">Quên mật
                                    khẩu?</a>
                            </div>
                        </form>
                    </div>

                    <!-- Register Section (Right - Larger) -->
                    <div class="col-md-7 auth-section register-section-page"
                        style="background: #ffffff; padding: 3rem;">
                        <div class="mb-4">
                            <h2 class="fw-bold mb-2">Đăng ký</h2>
                            <p class="text-muted mb-0">Tạo tài khoản mới miễn phí</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="register-form">
                            <div class="mb-3">
                                <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" style="border-radius: 10px;"
                                    required value="<?= e($_POST['username'] ?? '') ?>" pattern="[a-zA-Z0-9_]{3,20}"
                                    title="3-20 ký tự, chỉ chữ cái, số và dấu gạch dưới">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" style="border-radius: 10px;"
                                    required value="<?= e($_POST['email'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Họ tên</label>
                                <input type="text" name="full_name" class="form-control" style="border-radius: 10px;"
                                    value="<?= e($_POST['full_name'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" style="border-radius: 10px;"
                                    required minlength="6" autocomplete="new-password">
                                <small class="text-muted">Tối thiểu 6 ký tự</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control"
                                    style="border-radius: 10px;" required autocomplete="new-password">
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    Tôi đồng ý với <a href="/terms.php">Điều khoản sử dụng</a>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold">
                                Đăng ký
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Form Control Styles */
    .register-form .form-control,
    .login-form .form-control {
        border: 1px solid #dee2e6;
    }

    .register-form .form-control:focus,
    .login-form .form-control:focus {
        box-shadow: none;
        border-color: #dee2e6;
        outline: none;
    }

    /* Button Styles */
    .register-form .btn-primary,
    .login-form .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
        padding: 12px;
        border-radius: 8px;
    }

    .register-form .btn-primary:hover,
    .login-form .btn-primary:hover {
        background-color: #0b5ed7;
        border-color: #0a58ca;
    }

    /* Section Styles */
    .register-section-page h2,
    .login-section-page h2 {
        font-size: 1.75rem;
        color: #1d1d1f;
    }

    /* Disable hover effects on register page */
    .card.shadow-sm:hover,
    .auth-section:hover,
    .login-section-page:hover,
    .register-section-page:hover {
        transform: none !important;
    }

    /* Bounce animation for attention */
    @keyframes gentle-bounce {

        0%,
        100% {
            transform: translateY(0) scale(1);
        }

        25% {
            transform: translateY(-8px) scale(1.02);
        }

        50% {
            transform: translateY(-4px) scale(1.01);
        }

        75% {
            transform: translateY(-2px) scale(1.005);
        }
    }

    .shake-animation {
        animation: gentle-bounce 0.5s ease-out;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .auth-section {
            padding: 2rem !important;
        }

        .register-section-page,
        .login-section-page {
            min-height: auto;
        }

        .register-section-page h2,
        .login-section-page h2 {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .auth-section {
            padding: 1.5rem !important;
        }

        .register-section-page h2,
        .login-section-page h2 {
            font-size: 1.25rem;
        }
    }
</style>

<script>
    // Prevent login modal and trigger bounce animation on register page
    document.addEventListener('DOMContentLoaded', function () {
        // Check if we're on the register page
        const currentPath = window.location.pathname;
        if (currentPath.includes('/register') || currentPath.includes('register.php')) {

            // Find the login button
            const loginBtn = document.querySelector('[data-bs-target="#authModal"][data-tab="login"]');

            if (loginBtn) {
                // Remove Bootstrap modal attributes to prevent modal from opening
                loginBtn.removeAttribute('data-bs-toggle');
                loginBtn.removeAttribute('data-bs-target');

                // Add click handler for bounce animation
                loginBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Get the card element
                    const card = document.querySelector('.card.shadow-sm');
                    if (card) {
                        // Remove any existing animation
                        card.classList.remove('shake-animation');

                        // Trigger reflow to restart animation
                        void card.offsetWidth;

                        // Add the shake animation
                        card.classList.add('shake-animation');

                        // Scroll to the card smoothly
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });

                        // Remove animation class after it completes
                        setTimeout(function () {
                            card.classList.remove('shake-animation');
                        }, 500);
                    }

                    return false;
                });
            }

            // Also prevent modal from showing via Bootstrap event
            const authModal = document.getElementById('authModal');
            if (authModal) {
                authModal.addEventListener('show.bs.modal', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
            }
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>