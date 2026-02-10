<?php
$pageTitle = 'Giới thiệu';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 12px; margin-bottom: 12px;">
    <div class="text-center mb-5">
        <h1 style="font-weight: 700; color: #0071e3; font-size: 36px; letter-spacing: -0.02em;">
            Về chúng tôi
        </h1>
        <p style="font-size: 16px; color: #5a6c7d; font-weight: 500;">Đối tác tin cậy cho nhu cầu tài khoản Premium của bạn</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Hero Section -->
            <div class="about-hero-card mb-4">
                <div class="about-hero-content">
                    <h2 style="font-weight: 700; color: #1d1d1f; font-size: 28px; letter-spacing: -0.02em; margin-bottom: 16px;">
                        Chào mừng đến với <?= e(getSetting('site_name')) ?>
                    </h2>
                    <p style="font-size: 17px; color: #5a6c7d; line-height: 1.8; margin-bottom: 0;">
                        Chúng tôi là đơn vị hàng đầu tại Việt Nam trong việc cung cấp các tài khoản Premium chính hãng với giá cả hợp lý nhất.
                        Với hơn 10,000+ khách hàng tin tưởng, chúng tôi cam kết mang đến trải nghiệm mua sắm tốt nhất cho bạn.
                    </p>
                </div>
            </div>

            <!-- Why Choose Us -->
            <div class="about-section-card mb-4">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 style="font-weight: 700; color: #1d1d1f; font-size: 24px; letter-spacing: -0.02em; margin: 0;">
                        Tại sao chọn chúng tôi?
                    </h3>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon feature-icon-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="feature-content">
                                <h5 class="feature-title">Uy tín - Chất lượng</h5>
                                <p class="feature-desc">Hơn 10,000+ khách hàng tin tưởng và sử dụng dịch vụ của chúng tôi.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon feature-icon-warning">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="feature-content">
                                <h5 class="feature-title">Giao hàng nhanh chóng</h5>
                                <p class="feature-desc">Nhận tài khoản ngay sau khi thanh toán, tự động 24/7.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon feature-icon-primary">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="feature-content">
                                <h5 class="feature-title">Bảo mật tuyệt đối</h5>
                                <p class="feature-desc">Thông tin khách hàng được bảo mật 100%, giao dịch an toàn.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon feature-icon-info">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="feature-content">
                                <h5 class="feature-title">Hỗ trợ 24/7</h5>
                                <p class="feature-desc">Đội ngũ hỗ trợ nhiệt tình, giải đáp mọi thắc mắc nhanh chóng.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon feature-icon-danger">
                                <i class="fas fa-award"></i>
                            </div>
                            <div class="feature-content">
                                <h5 class="feature-title">Bảo hành đổi trả</h5>
                                <p class="feature-desc">Bảo hành trong suốt thời gian sử dụng, đổi mới 1-1 nếu có lỗi.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon feature-icon-success">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="feature-content">
                                <h5 class="feature-title">Giá cả hợp lý</h5>
                                <p class="feature-desc">Giá tốt nhất thị trường, nhiều chương trình khuyến mãi hấp dẫn.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="about-section-card mb-4">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3 style="font-weight: 700; color: #1d1d1f; font-size: 24px; letter-spacing: -0.02em; margin: 0;">
                        Sản phẩm của chúng tôi
                    </h3>
                </div>

                <p style="color: #5a6c7d; font-size: 15px; margin-top: 16px; margin-bottom: 20px;">
                    Chúng tôi cung cấp đa dạng các loại tài khoản Premium:
                </p>

                <div class="product-categories">
                    <div class="product-category-item">
                        <div class="category-icon-wrapper">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <div class="category-content">
                            <h6 class="category-title">Giải trí</h6>
                            <p class="category-desc">Netflix, Spotify, YouTube Premium, Apple Music...</p>
                        </div>
                    </div>

                    <div class="product-category-item">
                        <div class="category-icon-wrapper">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="category-content">
                            <h6 class="category-title">Học tập</h6>
                            <p class="category-desc">Udemy, Coursera, Duolingo, Grammarly...</p>
                        </div>
                    </div>

                    <div class="product-category-item">
                        <div class="category-icon-wrapper">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="category-content">
                            <h6 class="category-title">Công cụ</h6>
                            <p class="category-desc">Canva Pro, Microsoft Office, Adobe Creative Cloud...</p>
                        </div>
                    </div>

                    <div class="product-category-item">
                        <div class="category-icon-wrapper">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <div class="category-content">
                            <h6 class="category-title">Game</h6>
                            <p class="category-desc">Steam, Epic Games, PlayStation Plus, Xbox Game Pass...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commitment Section -->
            <div class="about-section-card">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 style="font-weight: 700; color: #1d1d1f; font-size: 24px; letter-spacing: -0.02em; margin: 0;">
                        Cam kết của chúng tôi
                    </h3>
                </div>

                <div class="commitment-box">
                    <div class="commitment-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Tài khoản chính hãng, hoạt động ổn định</span>
                    </div>
                    <div class="commitment-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Giao hàng tự động ngay sau khi thanh toán</span>
                    </div>
                    <div class="commitment-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Hỗ trợ đổi trả nếu tài khoản bị lỗi</span>
                    </div>
                    <div class="commitment-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Bảo mật thông tin khách hàng tuyệt đối</span>
                    </div>
                    <div class="commitment-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Giá cả minh bạch, không phát sinh chi phí</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Stats Cards -->
            <div class="stats-card stats-card-primary mb-4">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-content">
                    <h2 class="stats-number" data-target="10000">0</h2>
                    <p class="stats-label">Khách hàng tin tưởng</p>
                </div>
            </div>

            <div class="stats-card stats-card-success mb-4">
                <div class="stats-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stats-content">
                    <h2 class="stats-number" data-target="50000">0</h2>
                    <p class="stats-label">Đơn hàng thành công</p>
                </div>
            </div>

            <div class="stats-card stats-card-warning mb-4">
                <div class="stats-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stats-content">
                    <h2 class="stats-number" data-target="100">0</h2>
                    <p class="stats-label">Loại tài khoản</p>
                </div>
            </div>

            <!-- Contact Card -->
            <div class="contact-cta-card">
                <div class="contact-cta-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h5 style="font-weight: 600; color: #1d1d1f; font-size: 16px; margin-bottom: 8px;">
                    Liên hệ với chúng tôi
                </h5>
                <p style="font-size: 13px; color: #5a6c7d; margin-bottom: 16px; line-height: 1.6;">
                    Đội ngũ hỗ trợ của chúng tôi luôn sẵn sàng giải đáp mọi thắc mắc của bạn
                </p>

                <div class="contact-cta-info">
                    <div class="contact-detail-item">
                        <div class="contact-detail-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-detail-content">
                            <span class="contact-detail-label">Email</span>
                            <span class="contact-detail-value"><?= e(getSetting('contact_email', 'admin@veyrix.pro')) ?></span>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-detail-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-detail-content">
                            <span class="contact-detail-label">Hotline</span>
                            <span class="contact-detail-value"><?= e(getSetting('contact_phone', '0848877758')) ?></span>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-detail-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-detail-content">
                            <span class="contact-detail-label">Giờ làm việc</span>
                            <span class="contact-detail-value">24/7 - Cả tuần</span>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-detail-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-detail-content">
                            <span class="contact-detail-label">Địa chỉ</span>
                            <span class="contact-detail-value">Việt Nam</span>
                        </div>
                    </div>
                </div>

                <a href="/contact" class="pill-button pill-button-blue w-100 mt-3">
                    <i class="fas fa-paper-plane"></i> Gửi hỗ trợ ngay
                </a>

                <div class="d-flex gap-2 justify-content-center">
                    <a href="https://www.facebook.com/anh.dung.373866/" target="_blank" class="social-icon social-facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://web.telegram.org/a/#6269327932" target="_blank" class="social-icon social-telegram">
                        <i class="fab fa-telegram-plane"></i>
                    </a>
                    <a href="/contact" class="social-icon social-zalo">
                        <i class="fas fa-comments"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Container Styles */
.container {
    /* animation disabled */
}

/* Hero Card */
.about-hero-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 113, 227, 0.1);
}

.about-hero-icon {
    width: 40px;
    height: 40px;
    background: rgba(0, 113, 227, 0.1);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    margin-bottom: 8px;
    border: none;
    flex-shrink: 0;
    transition: all 0.2s;
    vertical-align: middle;
}

.about-hero-icon i {
    font-size: 20px;
    color: #0071e3;
}

/* Section Card */
.about-section-card {
    background: white;
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid #e0e0e0;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.section-icon {
    width: 56px;
    height: 56px;
    background: rgba(0, 113, 227, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: none;
    transition: all 0.2s;
}

.section-icon i {
    font-size: 24px;
    color: #0071e3;
}

/* Feature Items */
.feature-item {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 16px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.feature-item:hover {
    background: white;
    transform: scale(1.02);
    box-shadow: 0 8px 20px rgba(0, 113, 227, 0.15);
}

.feature-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: none;
    transition: all 0.2s;
}

.feature-icon i {
    font-size: 22px;
}

.feature-icon-success {
    background: rgba(52, 199, 89, 0.1);
    color: #34c759;
}

.feature-icon-warning {
    background: rgba(255, 149, 0, 0.1);
    color: #ff9500;
}

.feature-icon-primary {
    background: rgba(0, 113, 227, 0.1);
    color: #0071e3;
}

.feature-icon-info {
    background: rgba(90, 200, 250, 0.1);
    color: #5ac8fa;
}

.feature-icon-danger {
    background: rgba(255, 59, 48, 0.1);
    color: #ff3b30;
}

.feature-content {
    flex: 1;
}

.feature-title {
    font-weight: 600;
    font-size: 16px;
    color: #1d1d1f;
    margin-bottom: 6px;
}

.feature-desc {
    font-size: 14px;
    color: #5a6c7d;
    margin: 0;
    line-height: 1.6;
}

/* Product Categories */
.product-categories {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.product-category-item {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 14px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

.product-category-item:hover {
    box-shadow: 0 4px 16px rgba(0, 113, 227, 0.1);
    transform: translateX(8px);
}

.category-icon-wrapper {
    width: 50px;
    height: 50px;
    background: rgba(0, 113, 227, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: none;
    transition: all 0.2s;
}

.category-icon-wrapper i {
    font-size: 22px;
    color: #0071e3;
}

.category-content {
    flex: 1;
}

.category-title {
    font-weight: 600;
    font-size: 16px;
    color: #1d1d1f;
    margin-bottom: 4px;
}

.category-desc {
    font-size: 14px;
    color: #5a6c7d;
    margin: 0;
}

/* Commitment Box */
.commitment-box {
    background: linear-gradient(135deg, #e5f2ff 0%, #f0f8ff 100%);
    border-radius: 16px;
    padding: 24px;
    margin-top: 20px;
    border: 1px solid rgba(0, 113, 227, 0.2);
}

.commitment-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    color: #1d1d1f;
    font-size: 15px;
}

.commitment-item:not(:last-child) {
    border-bottom: 1px solid rgba(0, 113, 227, 0.1);
}

.commitment-item i {
    color: #34c759;
    font-size: 18px;
    flex-shrink: 0;
}

/* Stats Cards */
.stats-card {
    border-radius: 16px;
    padding: 20px;
    color: white;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255, 255, 255, 0.1);
    transform: rotate(45deg);
}

.stats-card-primary {
    background: linear-gradient(135deg, #0071e3 0%, #005bb5 100%);
}

.stats-card-success {
    background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
}

.stats-card-warning {
    background: linear-gradient(135deg, #ff9500 0%, #ff9f0a 100%);
}

.stats-icon {
    width: 45px;
    height: 45px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: none;
    transition: all 0.2s;
}

.stats-icon i {
    font-size: 20px;
}

.stats-content {
    flex: 1;
    text-align: left;
}

.stats-number {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 4px;
    line-height: 1;
}

.stats-label {
    font-size: 13px;
    margin: 0;
    opacity: 0.95;
    font-weight: 500;
}

/* Contact CTA Card */
.contact-cta-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    border: 1px solid #e0e0e0;
}

.contact-cta-icon {
    width: 50px;
    height: 50px;
    background: rgba(0, 113, 227, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    border: none;
    transition: all 0.2s;
}

.contact-cta-icon i {
    font-size: 22px;
    color: #0071e3;
}

.contact-cta-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}

.contact-detail-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: all 0.2s;
}

.contact-detail-item:hover {
    background: #e9ecef;
    transform: translateX(4px);
}

.contact-detail-icon {
    width: 36px;
    height: 36px;
    background: rgba(0, 113, 227, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: none;
}

.contact-detail-icon i {
    color: #0071e3;
    font-size: 16px;
}

.contact-detail-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.contact-detail-label {
    font-size: 11px;
    color: #86868b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contact-detail-value {
    font-size: 13px;
    color: #1d1d1f;
    font-weight: 500;
    word-break: break-word;
}

.d-flex.gap-2.justify-content-center {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e0e0e0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .about-hero-card {
        padding: 28px 20px;
    }

    .about-hero-icon {
        width: 64px;
        height: 64px;
        margin-bottom: 20px;
    }

    .about-hero-icon i {
        font-size: 28px;
    }

    .about-section-card {
        padding: 24px 20px;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .section-icon {
        width: 48px;
        height: 48px;
    }

    .section-icon i {
        font-size: 20px;
    }

    .feature-item {
        padding: 16px;
    }

    .feature-icon {
        width: 40px;
        height: 40px;
    }

    .feature-icon i {
        font-size: 18px;
    }

    .feature-title {
        font-size: 15px;
    }

    .feature-desc {
        font-size: 13px;
    }

    .product-category-item {
        padding: 16px;
    }

    .category-icon-wrapper {
        width: 45px;
        height: 45px;
    }

    .category-icon-wrapper i {
        font-size: 20px;
    }

    .stats-card {
        padding: 24px;
    }

    .stats-icon {
        width: 50px;
        height: 50px;
    }

    .stats-icon i {
        font-size: 24px;
    }

    .stats-number {
        font-size: 40px;
    }

    .stats-label {
        font-size: 14px;
    }

    .contact-cta-card {
        padding: 24px 20px;
    }

    .contact-cta-icon {
        width: 61px;
        height: 61px;
    }

    .contact-cta-icon i {
        font-size: 28px;
    }

    .contact-detail-item {
        padding: 10px;
    }

    .contact-detail-icon {
        width: 32px;
        height: 32px;
    }

    .contact-detail-icon i {
        font-size: 14px;
    }

    .contact-detail-label {
        font-size: 10px;
    }

    .contact-detail-value {
        font-size: 12px;
    }

    .container {
        padding-bottom: 1rem !important;
    }
}
</style>

<script>
// Display numbers immediately without animation
document.addEventListener('DOMContentLoaded', () => {
    const statsNumbers = document.querySelectorAll('.stats-number');
    statsNumbers.forEach(num => {
        const target = parseInt(num.getAttribute('data-target'));
        num.textContent = target.toLocaleString() + '+';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
