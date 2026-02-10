<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
initSession();

// Nếu đã đăng nhập thì redirect
if (isLoggedIn()) {
    redirect('/');
}

$pageTitle = 'Quên mật khẩu';
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
                            <i class="fas fa-lock-open text-primary" style="font-size: 48px;"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Quên mật khẩu?</h4>
                        <p class="text-muted mb-0">Nhập email của bạn để nhận mã xác nhận</p>
                    </div>

                    <!-- Form nhập email -->
                    <form id="forgotPasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                        <div class="mb-4">
                            <label class="form-label fw-medium">Địa chỉ Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input type="email"
                                       name="email"
                                       id="emailInput"
                                       class="form-control border-start-0 ps-0"
                                       placeholder="example@email.com"
                                       required
                                       style="border-radius: 0 8px 8px 0;">
                            </div>
                        </div>

                        <div id="alertBox" class="alert d-none mb-3"></div>

                        <button type="submit" id="submitBtn" class="btn btn-primary w-100 fw-semibold py-2" style="border-radius: 8px;">
                            <span class="btn-text">Gửi mã xác nhận</span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Đang gửi...
                            </span>
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="/login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Quay lại đăng nhập
                        </a>
                    </div>
                </div>
            </div>

            <!-- Thông tin bổ sung -->
            <div class="text-center mt-4">
                <p class="text-muted small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Mã xác nhận sẽ được gửi đến email của bạn và có hiệu lực trong 15 phút.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
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
</style>

<script>
document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    const alertBox = document.getElementById('alertBox');
    const email = document.getElementById('emailInput').value;

    // Disable button
    submitBtn.disabled = true;
    submitBtn.querySelector('.btn-text').classList.add('d-none');
    submitBtn.querySelector('.btn-loading').classList.remove('d-none');
    alertBox.classList.add('d-none');

    try {
        const formData = new FormData(this);

        const response = await fetch('/api/forgot-password.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');

        if (data.success) {
            alertBox.classList.add('alert-success');
            alertBox.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;

            // Redirect đến trang nhập mã sau 2 giây
            setTimeout(() => {
                window.location.href = '/reset-password.php?email=' + encodeURIComponent(email);
            }, 2000);
        } else {
            alertBox.classList.add('alert-danger');
            alertBox.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + data.message;

            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.querySelector('.btn-text').classList.remove('d-none');
            submitBtn.querySelector('.btn-loading').classList.add('d-none');
        }
    } catch (error) {
        alertBox.classList.remove('d-none');
        alertBox.classList.add('alert-danger');
        alertBox.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Có lỗi xảy ra, vui lòng thử lại.';

        submitBtn.disabled = false;
        submitBtn.querySelector('.btn-text').classList.remove('d-none');
        submitBtn.querySelector('.btn-loading').classList.add('d-none');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
