</main>

<!-- Footer -->
<footer class="mt-5" style="background: rgb(245, 245, 247); border-top: 1px solid #e0e0e0;">
    <div class="container py-5">
        <div class="row g-4">
            <!-- About Section -->
            <div class="col-lg-4 col-md-6">
                <div class="mb-3">
                    <h5 class="fw-bold mb-3 text-dark d-flex align-items-center" style="font-size: 1.65rem; gap: 10px;">
                        <img src="/public/images/logo.png" alt="Logo"
                            style="height: 32px; width: 32px; object-fit: contain;">
                        <?= e(getSetting('site_name', 'Veyrix Shop')) ?>
                    </h5>
                    <p class="text-muted mb-3" style="line-height: 1.8; font-size: 1.05rem;">
                        <?= e(getSetting('site_description', 'Chuyên cung cấp các tài khoản premium chất lượng cao với giá cả tốt nhất thị trường. Cam kết giao hàng nhanh chóng, bảo hành chu đáo và hỗ trợ tận tình 24/7.')) ?>
                    </p>

                    <div class="mt-3">
                        <h6 class="fw-semibold mb-3 text-dark" style="font-size: 1.05rem;">Tại sao chọn chúng tôi?</h6>
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="fas fa-check-circle text-success mt-1" style="font-size: 1rem;"></i>
                            <div>
                                <div class="fw-semibold text-dark" style="font-size: 1rem;">Giao hàng tự động 24/7</div>
                                <small class="text-muted" style="font-size: 0.95rem;">Nhận tài khoản ngay lập tức sau
                                    khi thanh toán</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="fas fa-check-circle text-success mt-1" style="font-size: 1rem;"></i>
                            <div>
                                <div class="fw-semibold text-dark" style="font-size: 1rem;">Bảo hành đổi trả miễn phí
                                </div>
                                <small class="text-muted" style="font-size: 0.95rem;">Đổi mới 100% nếu có vấn đề xảy
                                    ra</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="fas fa-check-circle text-success mt-1" style="font-size: 1rem;"></i>
                            <div>
                                <div class="fw-semibold text-dark" style="font-size: 1rem;">Hỗ trợ 24/7</div>
                                <small class="text-muted" style="font-size: 0.95rem;">Đội ngũ hỗ trợ luôn sẵn sàng giải
                                    đáp</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-check-circle text-success mt-1" style="font-size: 1rem;"></i>
                            <div>
                                <div class="fw-semibold text-dark" style="font-size: 1rem;">Giá cả cạnh tranh</div>
                                <small class="text-muted" style="font-size: 0.95rem;">Cam kết giá tốt nhất thị
                                    trường</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3 text-dark text-uppercase" style="letter-spacing: 1px; font-size: 1rem;">Liên kết
                </h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="/" class="text-muted text-decoration-none d-inline-flex align-items-center"
                            style="transition: all 0.3s; font-size: 0.95rem;"
                            onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px';"
                            onmouseout="this.style.color=''; this.style.paddingLeft='0';">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Trang chủ
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/products" class="text-muted text-decoration-none d-inline-flex align-items-center"
                            style="transition: all 0.3s; font-size: 0.95rem;"
                            onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px';"
                            onmouseout="this.style.color=''; this.style.paddingLeft='0';">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Sản phẩm
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/about.php" class="text-muted text-decoration-none d-inline-flex align-items-center"
                            style="transition: all 0.3s; font-size: 0.95rem;"
                            onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px';"
                            onmouseout="this.style.color=''; this.style.paddingLeft='0';">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Giới thiệu
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/contact.php" class="text-muted text-decoration-none d-inline-flex align-items-center"
                            style="transition: all 0.3s; font-size: 0.95rem;"
                            onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px';"
                            onmouseout="this.style.color=''; this.style.paddingLeft='0';">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Liên hệ
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Support -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-3 text-dark text-uppercase" style="letter-spacing: 1px; font-size: 1rem;">Hỗ trợ
                </h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none d-inline-flex align-items-center"
                            style="transition: all 0.3s; font-size: 0.95rem;"
                            onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px';"
                            onmouseout="this.style.color=''; this.style.paddingLeft='0';">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Hướng dẫn mua hàng
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none d-inline-flex align-items-center"
                            style="transition: all 0.3s; font-size: 0.95rem;"
                            onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px';"
                            onmouseout="this.style.color=''; this.style.paddingLeft='0';">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Chính sách đổi trả
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none d-inline-flex align-items-center"
                            style="transition: all 0.3s; font-size: 0.95rem;"
                            onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px';"
                            onmouseout="this.style.color=''; this.style.paddingLeft='0';">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Điều khoản dịch vụ
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none d-inline-flex align-items-center"
                            style="transition: all 0.3s; font-size: 0.95rem;"
                            onmouseover="this.style.color='#0d6efd'; this.style.paddingLeft='5px';"
                            onmouseout="this.style.color=''; this.style.paddingLeft='0';">
                            <i class="fas fa-chevron-right me-2" style="font-size: 0.8rem;"></i>Câu hỏi thường gặp
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-3 text-dark text-uppercase" style="letter-spacing: 1px; font-size: 1rem;">Liên hệ
                </h6>
                <div class="mb-3">
                    <div class="d-flex align-items-start mb-3">
                        <i class="fas fa-envelope text-primary me-3 mt-1"></i>
                        <div>
                            <div class="small text-muted" style="font-size: 0.9rem;">Email</div>
                            <a href="mailto:<?= e(getSetting('contact_email', 'admin@veyrix.pro')) ?>"
                                class="text-dark text-decoration-none" style="transition: color 0.3s; font-size: 1rem;"
                                onmouseover="this.style.color='#0d6efd';" onmouseout="this.style.color='';">
                                <?= e(getSetting('contact_email', 'admin@veyrix.pro')) ?>
                            </a>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <i class="fas fa-phone text-primary me-3 mt-1"></i>
                        <div>
                            <div class="small text-muted" style="font-size: 0.9rem;">Hotline</div>
                            <a href="tel:<?= e(getSetting('contact_phone', '0848877758')) ?>"
                                class="text-dark text-decoration-none fw-semibold"
                                style="transition: color 0.3s; font-size: 1.05rem;"
                                onmouseover="this.style.color='#0d6efd';" onmouseout="this.style.color='';">
                                <?= e(getSetting('contact_phone', '0848877758')) ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <h6 class="small text-muted mb-2 fw-semibold" style="font-size: 0.95rem;">Kết nối với chúng tôi</h6>
                    <div class="d-flex gap-2">
                        <a href="https://www.facebook.com/anh.dung.373866/" target="_blank"
                            class="social-icon social-facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://web.telegram.org/a/#6269327932" target="_blank"
                            class="social-icon social-telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                        <a href="/contact" class="social-icon social-zalo">
                            <i class="fas fa-comments"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Methods & Copyright -->
        <div class="row mt-4 pt-4 align-items-center" style="border-top: 1px solid #dee2e6;">
            <!-- Payment Methods - Left -->
            <div class="col-md-8 col-12 mb-3 mb-md-0">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="text-muted fw-semibold" style="font-size: 0.95rem;">Phương thức thanh toán:</span>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-white text-dark px-3 py-2"
                            style="border: 1px solid #dee2e6; font-size: 0.9rem;"><i
                                class="fas fa-wallet me-1 text-primary"></i> Ví điện tử</span>
                        <span class="badge bg-white text-dark px-3 py-2"
                            style="border: 1px solid #dee2e6; font-size: 0.9rem;"><i
                                class="fas fa-credit-card me-1 text-primary"></i> Thẻ ATM</span>
                        <span class="badge bg-white text-dark px-3 py-2"
                            style="border: 1px solid #dee2e6; font-size: 0.9rem;"><i
                                class="fas fa-university me-1 text-primary"></i> Chuyển khoản</span>
                    </div>
                </div>
            </div>

            <!-- Copyright - Right -->
            <div class="col-md-4 col-12 text-md-end text-center">
                <p class="mb-0 text-muted" style="font-size: 0.9rem;">
                    &copy; <?= date('Y') ?> <span
                        class="text-dark fw-semibold"><?= e(getSetting('site_name', 'Veyrix Shop')) ?></span>. All
                    rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTopBtn" class="back-to-top" aria-label="Lên đầu trang">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
    /* Social Icons */
    .social-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        border: 2px solid #dee2e6;
        color: #6c757d;
        background: white;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 16px;
    }

    .social-icon:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .social-facebook:hover {
        background: #1877f2;
        border-color: #1877f2;
        color: white !important;
    }

    .social-facebook:hover i {
        color: white !important;
    }

    .social-telegram:hover {
        background: #0088cc;
        border-color: #0088cc;
        color: white !important;
    }

    .social-telegram:hover i {
        color: white !important;
    }

    .social-zalo:hover {
        background: #0068ff;
        border-color: #0068ff;
        color: white !important;
    }

    .social-zalo:hover i {
        color: white !important;
    }

    /* Back to Top Button */
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: #0d6efd;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .back-to-top.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .back-to-top:hover {
        background: #0a58ca;
        box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
        transform: translateY(-3px);
    }

    .back-to-top:active {
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(13, 110, 253, 0.3);
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .back-to-top {
            width: 45px;
            height: 45px;
            bottom: 20px;
            right: 20px;
            font-size: 16px;
        }

        /* Footer mobile optimizations */
        footer {
            padding-top: 2rem !important;
            padding-bottom: 1rem !important;
        }

        footer .container {
            padding-left: 20px !important;
            padding-right: 20px !important;
        }

        /* Increase heading sizes on mobile */
        footer h5.fw-bold {
            font-size: 1.5rem !important;
        }

        footer h6.fw-bold {
            font-size: 1.1rem !important;
            margin-bottom: 1rem !important;
        }

        footer h6.small {
            font-size: 1rem !important;
        }

        /* About section */
        footer .col-lg-4 p.text-muted {
            font-size: 1.05rem !important;
            line-height: 1.7 !important;
            margin-bottom: 1rem !important;
        }

        /* Why choose us section */
        footer .d-flex.align-items-start.gap-2 {
            gap: 0.5rem !important;
            margin-bottom: 0.75rem !important;
        }

        footer .d-flex.align-items-start.gap-2 .fw-semibold {
            font-size: 1.05rem !important;
        }

        footer .d-flex.align-items-start.gap-2 small {
            font-size: 0.95rem !important;
            line-height: 1.5 !important;
        }

        footer .fa-check-circle {
            font-size: 1.05rem !important;
        }

        /* Links section */
        footer .list-unstyled li {
            margin-bottom: 0.5rem !important;
        }

        footer .list-unstyled a {
            font-size: 1.05rem !important;
        }

        /* Contact section */
        footer .d-flex.align-items-start.mb-3 {
            margin-bottom: 1rem !important;
        }

        footer .d-flex.align-items-start .small {
            font-size: 0.95rem !important;
        }

        footer .d-flex.align-items-start a {
            font-size: 1.05rem !important;
        }

        /* Social icons - slightly smaller */
        .social-icon {
            width: 36px;
            height: 36px;
            font-size: 14px;
        }

        /* Payment methods section */
        footer .row.mt-4 {
            margin-top: 2rem !important;
            padding-top: 2rem !important;
        }

        footer .col-md-8.mb-3 {
            margin-bottom: 1.5rem !important;
        }

        /* Compact payment methods on mobile */
        footer .col-md-8 .d-flex.flex-wrap {
            justify-content: center !important;
            gap: 0.5rem !important;
        }

        footer .col-md-8 .d-flex.flex-wrap span.text-muted {
            display: none !important;
        }

        footer .col-md-8 .d-flex.gap-2 {
            justify-content: center !important;
            gap: 0.4rem !important;
        }

        /* Payment badges - compact */
        footer .badge {
            font-size: 0.7rem !important;
            padding: 0.25rem 0.5rem !important;
            white-space: nowrap;
            border-radius: 6px !important;
        }

        footer .badge i {
            font-size: 0.65rem !important;
            margin-right: 0.25rem !important;
        }

        /* Copyright */
        footer .col-md-4 p {
            font-size: 0.85rem !important;
        }

        /* Add spacing between columns */
        footer .row.g-4>[class*="col-"] {
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        footer .row.g-4>[class*="col-"]:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
    }

    /* Extra small mobile devices */
    @media (max-width: 480px) {
        footer {
            padding-top: 1.5rem !important;
        }

        footer .container {
            padding-left: 16px !important;
            padding-right: 16px !important;
        }

        /* Further reduce text sizes */
        footer h5.fw-bold {
            font-size: 1.2rem !important;
        }

        footer h6.fw-bold {
            font-size: 0.85rem !important;
        }

        footer .col-lg-4 p.text-muted {
            font-size: 0.85rem !important;
        }

        footer .d-flex.align-items-start.gap-2 .fw-semibold {
            font-size: 0.85rem !important;
        }

        footer .d-flex.align-items-start.gap-2 small {
            font-size: 0.8rem !important;
        }

        footer .list-unstyled a {
            font-size: 0.85rem !important;
        }

        /* Stack payment badges vertically on very small screens */
        footer .badge {
            font-size: 0.75rem !important;
            padding: 0.35rem 0.65rem !important;
            margin-bottom: 0.4rem;
        }

        .social-icon {
            width: 34px;
            height: 34px;
            font-size: 13px;
        }
    }
</style>

<script>
    // Back to Top functionality
    (function () {
        const backToTopBtn = document.getElementById('backToTopBtn');

        // Show/hide button based on scroll position (30% of page height)
        window.addEventListener('scroll', function () {
            const scrollPercentage = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;

            if (scrollPercentage > 30) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        // Smooth scroll to top when clicked
        backToTopBtn.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    })();
</script>

<!-- Bootstrap JS - Deferred for better performance -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<!-- Custom JS - Deferred -->
<script src="/public/js/main.js" defer></script>
</body>

</html>