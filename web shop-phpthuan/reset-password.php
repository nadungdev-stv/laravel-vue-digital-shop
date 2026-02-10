<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
initSession();

// Nếu đã đăng nhập thì redirect
if (isLoggedIn()) {
    redirect('/');
}

$email = $_GET['email'] ?? '';

// Kiểm tra email có hợp lệ không
if (empty($email) || !isValidEmail($email)) {
    setFlash('error', 'Link không hợp lệ. Vui lòng thử lại.');
    redirect('/forgot-password.php');
}

$pageTitle = 'Đặt lại mật khẩu';
$csrfToken = generateCSRFToken();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm" style="border-radius: 16px; border: 1px solid #e5e7eb;">
                <div class="card-body p-4">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-shield-alt text-primary" style="font-size: 48px;"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Xác nhận mã</h4>
                        <p class="text-muted mb-0">
                            Nhập mã 6 chữ số đã gửi đến<br>
                            <strong><?= e($email) ?></strong>
                        </p>
                    </div>

                    <!-- Form nhập mã và mật khẩu mới -->
                    <form id="resetPasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="email" value="<?= e($email) ?>">

                        <!-- Mã xác nhận 6 số -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">Mã xác nhận</label>
                            <div class="code-inputs d-flex gap-2 justify-content-center">
                                <input type="text" class="form-control code-input text-center" maxlength="1" data-index="0" inputmode="numeric" pattern="[0-9]" required>
                                <input type="text" class="form-control code-input text-center" maxlength="1" data-index="1" inputmode="numeric" pattern="[0-9]" required>
                                <input type="text" class="form-control code-input text-center" maxlength="1" data-index="2" inputmode="numeric" pattern="[0-9]" required>
                                <input type="text" class="form-control code-input text-center" maxlength="1" data-index="3" inputmode="numeric" pattern="[0-9]" required>
                                <input type="text" class="form-control code-input text-center" maxlength="1" data-index="4" inputmode="numeric" pattern="[0-9]" required>
                                <input type="text" class="form-control code-input text-center" maxlength="1" data-index="5" inputmode="numeric" pattern="[0-9]" required>
                            </div>
                            <input type="hidden" name="code" id="codeInput">
                        </div>

                        <!-- Mật khẩu mới -->
                        <div class="mb-3">
                            <label class="form-label fw-medium">Mật khẩu mới</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password"
                                       name="password"
                                       id="passwordInput"
                                       class="form-control border-start-0 ps-0"
                                       placeholder="Tối thiểu 6 ký tự"
                                       minlength="6"
                                       required
                                       style="border-radius: 0 8px 8px 0;">
                                <button type="button" class="btn btn-outline-secondary toggle-password" style="border-radius: 0 8px 8px 0;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Xác nhận mật khẩu -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">Xác nhận mật khẩu</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password"
                                       name="confirm_password"
                                       id="confirmPasswordInput"
                                       class="form-control border-start-0 ps-0"
                                       placeholder="Nhập lại mật khẩu"
                                       required
                                       style="border-radius: 0 8px 8px 0;">
                            </div>
                        </div>

                        <div id="alertBox" class="alert d-none mb-3"></div>

                        <button type="submit" id="submitBtn" class="btn btn-primary w-100 fw-semibold py-2" style="border-radius: 8px;">
                            <span class="btn-text">Đặt lại mật khẩu</span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Đang xử lý...
                            </span>
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted small mb-2">Không nhận được mã?</p>
                        <button type="button" id="resendBtn" class="btn btn-link text-decoration-none p-0">
                            Gửi lại mã
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.code-input {
    width: 48px;
    height: 56px;
    font-size: 24px;
    font-weight: 600;
    border-radius: 10px;
    border: 2px solid #dee2e6;
}

.code-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.code-input.filled {
    background-color: #f0f4ff;
    border-color: #667eea;
}

.input-group-text {
    border-radius: 8px 0 0 8px;
    border-color: #dee2e6;
}

.form-control:focus {
    box-shadow: none;
    border-color: #667eea;
}

.input-group:focus-within .input-group-text {
    border-color: #667eea;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);
    transform: translateY(-1px);
}

.btn-primary:disabled {
    opacity: 0.7;
    transform: none;
}

.toggle-password {
    border-color: #dee2e6;
    border-left: none;
}

.toggle-password:hover {
    background-color: #f8f9fa;
}
</style>

<script>
// Code input handling
const codeInputs = document.querySelectorAll('.code-input');
const hiddenCodeInput = document.getElementById('codeInput');

codeInputs.forEach((input, index) => {
    input.addEventListener('input', function(e) {
        // Chỉ cho phép số
        this.value = this.value.replace(/[^0-9]/g, '');

        if (this.value.length === 1) {
            this.classList.add('filled');
            // Chuyển sang ô tiếp theo
            if (index < codeInputs.length - 1) {
                codeInputs[index + 1].focus();
            }
        } else {
            this.classList.remove('filled');
        }

        // Cập nhật hidden input
        updateHiddenCode();
    });

    input.addEventListener('keydown', function(e) {
        // Xử lý phím Backspace
        if (e.key === 'Backspace' && this.value === '' && index > 0) {
            codeInputs[index - 1].focus();
        }
    });

    // Cho phép paste mã
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);

        pastedData.split('').forEach((char, i) => {
            if (codeInputs[i]) {
                codeInputs[i].value = char;
                codeInputs[i].classList.add('filled');
            }
        });

        updateHiddenCode();

        // Focus vào ô cuối cùng hoặc ô tiếp theo
        const nextIndex = Math.min(pastedData.length, 5);
        codeInputs[nextIndex].focus();
    });
});

function updateHiddenCode() {
    let code = '';
    codeInputs.forEach(input => {
        code += input.value;
    });
    hiddenCodeInput.value = code;
}

// Toggle password visibility
document.querySelector('.toggle-password').addEventListener('click', function() {
    const passwordInput = document.getElementById('passwordInput');
    const icon = this.querySelector('i');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Form submit
document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    const alertBox = document.getElementById('alertBox');
    const password = document.getElementById('passwordInput').value;
    const confirmPassword = document.getElementById('confirmPasswordInput').value;

    // Validate
    if (hiddenCodeInput.value.length !== 6) {
        showAlert('danger', 'Vui lòng nhập đủ mã 6 chữ số');
        return;
    }

    if (password.length < 6) {
        showAlert('danger', 'Mật khẩu phải có ít nhất 6 ký tự');
        return;
    }

    if (password !== confirmPassword) {
        showAlert('danger', 'Mật khẩu xác nhận không khớp');
        return;
    }

    // Disable button
    submitBtn.disabled = true;
    submitBtn.querySelector('.btn-text').classList.add('d-none');
    submitBtn.querySelector('.btn-loading').classList.remove('d-none');
    alertBox.classList.add('d-none');

    try {
        const formData = new FormData(this);

        const response = await fetch('/api/reset-password.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', data.message);

            // Redirect đến trang đăng nhập sau 2 giây
            setTimeout(() => {
                window.location.href = '/login.php';
            }, 2000);
        } else {
            showAlert('danger', data.message);

            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.querySelector('.btn-text').classList.remove('d-none');
            submitBtn.querySelector('.btn-loading').classList.add('d-none');
        }
    } catch (error) {
        showAlert('danger', 'Có lỗi xảy ra, vui lòng thử lại.');

        submitBtn.disabled = false;
        submitBtn.querySelector('.btn-text').classList.remove('d-none');
        submitBtn.querySelector('.btn-loading').classList.add('d-none');
    }
});

function showAlert(type, message) {
    const alertBox = document.getElementById('alertBox');
    alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
    alertBox.classList.add('alert-' + type);
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    alertBox.innerHTML = '<i class="fas fa-' + icon + ' me-2"></i>' + message;
}

// Resend code
document.getElementById('resendBtn').addEventListener('click', async function() {
    const email = '<?= e($email) ?>';

    this.disabled = true;
    this.textContent = 'Đang gửi...';

    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('/api/forgot-password.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', 'Mã xác nhận mới đã được gửi!');
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        showAlert('danger', 'Có lỗi xảy ra, vui lòng thử lại.');
    }

    this.disabled = false;
    this.textContent = 'Gửi lại mã';
});

// Auto focus first input
codeInputs[0].focus();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
