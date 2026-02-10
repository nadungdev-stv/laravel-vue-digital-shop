<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/chat_helpers.php';
initSession();

// Xử lý gửi liên hệ qua mail (TRƯỚC KHI OUTPUT HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        setFlash('error', 'Vui lòng điền đầy đủ thông tin');
    } elseif (!isValidEmail($email)) {
        setFlash('error', 'Email không hợp lệ');
    } else {
        // Nếu đã đăng nhập, tạo support ticket
        if (isLoggedIn()) {
            $user = getCurrentUser();
            db()->query(
                "INSERT INTO support_tickets (user_id, subject, message, status, priority)
                 VALUES (?, ?, ?, 'open', 'medium')",
                [$user['id'], $subject, $message]
            );
        }

        setFlash('success', 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.');
    }
    redirect('/contact');
}

$pageTitle = 'Liên hệ';

// Generate CSRF token
$csrfToken = generateCSRFToken();

$user = getCurrentUser();
$isGuest = !isLoggedIn();

// Get or create chat session
$chatSession = null;
if (isset($_SESSION['chat_session_id'])) {
    $chatSession = db()->query(
        "SELECT * FROM chat_sessions WHERE id = ? AND status = 'active'",
        [$_SESSION['chat_session_id']]
    )->fetch();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 12px; margin-bottom: 12px;">
    <div class="text-center mb-5">
        <h1 style="font-weight: 700; color: #0071e3; font-size: 36px; letter-spacing: -0.02em;">
            Liên hệ với chúng tôi
        </h1>
        <p style="font-size: 16px; color: #5a6c7d; font-weight: 500;">Chúng tôi sẵn sàng hỗ trợ bạn 24/7</p>
    </div>

    <div class="row">
        <div class="col-lg-8" style="margin-bottom: 2rem;">
            <!-- Welcome Section -->
            <div id="welcomeSection" class="welcome-section">
                <div class="welcome-card">
                    <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                        <div class="welcome-icon-inline">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3 style="font-weight: 700; color: #2c3e50; margin-bottom: 0;">Chọn phương thức liên hệ</h3>
                    </div>
                    <p style="color: #5a6c7d; font-size: 15px; line-height: 1.7; margin-bottom: 20px;">
                        Bạn có thể liên hệ với chúng tôi qua các kênh hỗ trợ dưới đây để được tư vấn nhanh chóng.
                    </p>

                    <div class="welcome-features">
                        <div class="welcome-feature-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Phản hồi nhanh chóng</span>
                        </div>
                        <div class="welcome-feature-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Hỗ trợ tận tâm</span>
                        </div>
                        <div class="welcome-feature-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Giải quyết hiệu quả</span>
                        </div>
                    </div>

                    <!-- Contact Options Cards -->
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <div class="contact-option-card" onclick="openChatWidget()">
                            <div class="contact-option-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="contact-option-content">
                                <h5>Chat trực tiếp</h5>
                                <p>Nhận hỗ trợ ngay lập tức</p>
                                <span class="status-badge-inline">
                                    <span class="status-dot-inline"></span> Online
                                </span>
                            </div>
                        </div>
                        <div class="contact-option-card" onclick="toggleMailForm()">
                            <div class="contact-option-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-option-content">
                                <h5>Gửi Mail</h5>
                                <p>Phản hồi trong 1h</p>
                                <span class="status-badge-inline status-badge-secondary">
                                    <i class="fas fa-clock"></i> Nhanh
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mail Form -->
            <div id="mailForm" style="display: none;">
                <div class="mail-card">
                    <div class="d-flex justify-content-between align-items-center mb-4" style="border-bottom: 1px solid rgba(0, 113, 227, 0.1); padding-bottom: 16px;">
                        <h4 class="mb-0" style="font-weight: 600; color: #1d1d1f;">
                            <i class="fas fa-envelope text-primary me-2"></i>Gửi tin nhắn
                        </h4>
                        <button class="pill-button pill-button-gray" onclick="toggleMailForm()">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </button>
                    </div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4 col-sm-6 mb-3">
                                <label class="form-label fw-semibold">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" style="border-radius: 10px;"
                                       value="<?= isLoggedIn() ? e(getCurrentUser()['full_name'] ?? getCurrentUser()['username']) : '' ?>"
                                       required>
                            </div>
                            <div class="col-md-4 col-sm-6 mb-3">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" style="border-radius: 10px;"
                                       value="<?= isLoggedIn() ? e(getCurrentUser()['email']) : '' ?>"
                                       required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" style="border-radius: 10px;"
                                   placeholder="Vấn đề cần hỗ trợ" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nội dung <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="6" style="border-radius: 10px;"
                                      placeholder="Mô tả chi tiết vấn đề của bạn..." required></textarea>
                        </div>

                        <button type="submit" name="send_email" class="pill-button pill-button-blue">
                            <i class="fas fa-paper-plane"></i> Gửi tin nhắn
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="info-card mb-4">
                <h6 class="fw-bold mb-3" style="border-bottom: 1px solid rgba(0, 113, 227, 0.1); padding-bottom: 16px;">
                    <i class="fas fa-info-circle me-2"></i>Thông tin liên hệ
                </h6>

                <div class="info-item">
                    <div class="info-icon-circle">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <strong>Email</strong>
                        <p class="mb-0">
                            <a href="mailto:<?= e(getSetting('contact_email', 'support@example.com')) ?>">
                                <?= e(getSetting('contact_email', 'support@example.com')) ?>
                            </a>
                        </p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon-circle">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div>
                        <strong>Hotline</strong>
                        <p class="mb-0">
                            <a href="tel:<?= e(getSetting('contact_phone', '0848877758')) ?>">
                                <?= e(getSetting('contact_phone', '0848877758')) ?>
                            </a>
                        </p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon-circle">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <strong>Giờ làm việc</strong>
                        <p class="mb-0">Thứ 2 - Chủ nhật: 8:00 - 22:00</p>
                    </div>
                </div>

                <h6 class="small text-muted mb-2 fw-semibold" style="font-size: 0.95rem; margin-top: 20px;">Kết nối với chúng tôi</h6>
                <div class="d-flex gap-2">
                    <a href="https://www.facebook.com/anh.dung.373866/" target="_blank" class="social-icon social-facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://web.telegram.org/a/#6269327932" target="_blank" class="social-icon social-telegram">
                        <i class="fab fa-telegram-plane"></i>
                    </a>
                    <a href="#" class="social-icon social-zalo">
                        <i class="fas fa-comments"></i>
                    </a>
                </div>
            </div>

            <div class="info-card">
                <h6 class="fw-bold mb-3" style="border-bottom: 1px solid rgba(0, 113, 227, 0.1); padding-bottom: 16px;">
                    <i class="fas fa-question-circle me-2"></i>Câu hỏi thường gặp
                </h6>
                <div class="faq-list">
                    <a href="#" class="faq-item">
                        <i class="fas fa-angle-right"></i>
                        <span>Làm thế nào để mua hàng?</span>
                    </a>
                    <a href="#" class="faq-item">
                        <i class="fas fa-angle-right"></i>
                        <span>Chính sách bảo hành như thế nào?</span>
                    </a>
                    <a href="#" class="faq-item">
                        <i class="fas fa-angle-right"></i>
                        <span>Tài khoản bị lỗi thì xử lý ra sao?</span>
                    </a>
                    <a href="#" class="faq-item">
                        <i class="fas fa-angle-right"></i>
                        <span>Phương thức thanh toán nào được hỗ trợ?</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Chat Widget -->
<div class="chat-widget" id="chatWidget">
    <div class="chat-widget-header">
        <h3 class="chat-widget-title">Tin nhắn</h3>
        <div class="d-flex gap-2">
            <button class="chat-widget-action" onclick="toggleExpandWidget()" title="Phóng to">
                <svg id="expandIcon" fill="currentColor" height="20" width="20" viewBox="0 0 24 24">
                    <polyline fill="none" points="15 3 21 3 21 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polyline>
                    <polyline fill="none" points="9 21 3 21 3 15" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polyline>
                    <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="21" x2="14" y1="3" y2="10"></line>
                    <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="3" x2="10" y1="21" y2="14"></line>
                </svg>
                <svg id="compressIcon" fill="currentColor" height="20" width="20" viewBox="0 0 24 24" style="display: none;">
                    <polyline fill="none" points="4 14 10 14 10 20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polyline>
                    <polyline fill="none" points="20 10 14 10 14 4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polyline>
                    <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="14" x2="21" y1="10" y2="3"></line>
                    <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="3" x2="10" y1="21" y2="14"></line>
                </svg>
            </button>
            <button class="chat-widget-action" onclick="minimizeChatWidget()" title="Thu nhỏ">
                <svg fill="currentColor" height="20" width="20" viewBox="0 0 24 24">
                    <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" x1="5" x2="19" y1="12" y2="12"></line>
                </svg>
            </button>
            <button class="chat-widget-close" onclick="minimizeChatWidget()" title="Đóng">
                <svg fill="currentColor" height="20" width="20" viewBox="0 0 24 24">
                    <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="21" x2="3" y1="3" y2="21"></line>
                    <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="21" x2="3" y1="21" y2="3"></line>
                </svg>
            </button>
        </div>
    </div>

    <?php if (!$chatSession): ?>
    <!-- Form start chat trong widget -->
    <div class="chat-widget-body" id="chatWidgetStart" style="display: flex; flex-direction: column;">
        <div style="flex: 0 0 auto;">
            <div class="text-center p-4">
                <i class="fas fa-comments fa-3x mb-3" style="color: #0071e3;"></i>
                <h5 style="font-weight: 600; color: #1d1d1f;">Bắt đầu trò chuyện</h5>
                <p class="text-muted small">Chat ngay để được hỗ trợ</p>
            </div>

            <?php if ($isGuest): ?>
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên của bạn</label>
                    <input type="text" id="chatNameInput" name="name" class="form-control" style="border-radius: 10px;" placeholder="Nhập tên của bạn (ko bắt buộc)" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" id="chatEmailInput" name="email" class="form-control" style="border-radius: 10px;" placeholder="Có thể bỏ qua">
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div style="flex: 1;"></div>

        <?php if ($isGuest): ?>
        <form id="startChatFormWidget" class="p-3" style="flex: 0 0 auto;">
            <button type="submit" id="startChatBtn" class="pill-button pill-button-blue w-100">
                <i class="fas fa-comments"></i> <span id="chatBtnText">Bắt đầu chat với tư cách ẩn danh</span>
            </button>
        </form>
        <?php else: ?>
        <div class="p-3" style="flex: 0 0 auto;">
            <button type="button" class="pill-button pill-button-blue w-100" onclick="startChat()">
                <i class="fas fa-comments"></i> Bắt đầu chat
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Chat messages trong widget -->
    <div class="chat-widget-body" id="chatWidgetMessages">
        <!-- Messages will be loaded here -->
    </div>

    <div class="chat-widget-footer">
        <form id="sendMessageFormWidget" class="d-flex gap-2 align-items-center">
            <input type="file" id="imageInputWidget" accept="image/*" style="display: none;">
            <button type="button" class="btn-upload-widget" onclick="document.getElementById('imageInputWidget').click()" style="width: 36px; height: 36px; background: rgba(0, 113, 227, 0.1); color: #0071e3; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all 0.2s;">
                <i class="fas fa-image"></i>
            </button>
            <input type="text"
                   class="form-control"
                   id="messageInputWidget"
                   placeholder="Nhập tin nhắn..."
                   autocomplete="off"
                   style="flex: 1; border-radius: 20px; border: 1px solid #e0e0e0; padding: 5px 16px; box-shadow: none; background: #f8f9fa; height: 36px;">
            <button type="submit" class="btn-send-widget" style="width: 36px; height: 36px; background: rgba(0, 113, 227, 0.1); color: #0071e3; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all 0.2s;">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- Floating Chat Button -->
<button class="chat-float-button" id="chatFloatButton" onclick="openChatWidget()">
    <div class="chat-button-content">
        <div class="chat-button-icon">
            <svg aria-label="Tin nhắn" fill="currentColor" height="24" role="img" viewBox="0 0 24 24" width="24">
                <title>Hỗ trợ</title>
                <path d="M13.973 20.046 21.77 6.928C22.8 5.195 21.55 3 19.535 3H4.466C2.138 3 .984 5.825 2.646 7.456l4.842 4.752 1.723 7.121c.548 2.266 3.571 2.721 4.762.717Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="2"></path>
                <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x1="7.488" x2="15.515" y1="12.208" y2="7.641"></line>
            </svg>
        </div>
        <span class="chat-button-text">Hỗ trợ</span>
    </div>
    <span class="chat-notification-badge" style="display: none;">1</span>
</button>

<style>
/* Contact Option Cards */
.contact-option-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 12px;
    padding: 14px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 113, 227, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
    width: 230px;
    height: 110px;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 12px;
    text-align: left;
    position: relative;
    overflow: hidden;
}

.contact-option-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 16px rgba(0, 113, 227, 0.2);
    border-color: rgba(0, 113, 227, 0.3);
    background: white;
}

.contact-option-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0071e3 0%, #005bb5 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 2px 8px rgba(0, 113, 227, 0.3);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.contact-option-card:hover .contact-option-icon {
    transform: scale(1.05);
}

.contact-option-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.contact-option-card h5 {
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0;
    font-size: 14px;
    letter-spacing: -0.01em;
}

.contact-option-card p {
    color: #5a6c7d;
    font-size: 11px;
    margin-bottom: 0;
    font-weight: 500;
    line-height: 1.3;
}

.status-badge-inline {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, rgba(52, 199, 89, 0.15) 0%, rgba(52, 199, 89, 0.08) 100%);
    color: #34c759;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    border: 1px solid rgba(52, 199, 89, 0.2);
    align-self: flex-start;
}

.status-badge-inline.status-badge-secondary {
    background: linear-gradient(135deg, rgba(0, 113, 227, 0.15) 0%, rgba(0, 113, 227, 0.08) 100%);
    color: #0071e3;
    border-color: rgba(0, 113, 227, 0.2);
}

.status-badge-inline i {
    font-size: 9px;
    margin-right: 4px;
}

.status-dot-inline {
    display: inline-block;
    width: 6px;
    height: 6px;
    background: #34c759;
    border-radius: 50%;
    margin-right: 4px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Welcome Section */
.welcome-section {
    animation: fadeIn 0.5s ease;
}

.welcome-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 113, 227, 0.1);
    text-align: center;
}

.welcome-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #0071e3 0%, #005bb5 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: white;
    box-shadow: 0 4px 20px rgba(0, 113, 227, 0.3);
}

.welcome-icon-inline {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #0071e3 0%, #005bb5 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    box-shadow: 0 4px 16px rgba(0, 113, 227, 0.3);
    flex-shrink: 0;
}

.welcome-features {
    display: flex;
    justify-content: center;
    gap: 32px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.welcome-feature-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #2c3e50;
    font-weight: 500;
    font-size: 14px;
}

.welcome-feature-item i {
    font-size: 18px;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mail Card */
.mail-card {
    background: white;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    animation: slideDown 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Floating Chat Widget */
.chat-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 360px;
    height: 700px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0, 113, 227, 0.15), 0 8px 48px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 113, 227, 0.1);
    display: none;
    flex-direction: column;
    z-index: 9999;
    animation: slideUp 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);
    overflow: hidden;
}

.chat-widget.active {
    display: flex;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.chat-widget-header {
    background: white;
    color: #1d1d1f;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(0, 113, 227, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-widget-title {
    font-weight: 700;
    font-size: 20px;
    letter-spacing: -0.02em;
    margin: 0;
    color: #1d1d1f;
}

.chat-widget-action,
.chat-widget-close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: #262626;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
    padding: 0;
}

.chat-widget-action:hover,
.chat-widget-close:hover {
    background: rgba(0, 0, 0, 0.05);
    transform: scale(1.05);
}

.chat-widget-action:active,
.chat-widget-close:active {
    background: rgba(0, 0, 0, 0.1);
    transform: scale(0.95);
}

/* Expanded state */
.chat-widget.expanded {
    width: 90vw;
    height: 90vh;
    max-width: 1200px;
    max-height: 90vh;
    bottom: 50%;
    right: 50%;
    transform: translate(50%, 50%);
}

.chat-widget.expanded .chat-widget-body {
    max-height: none;
    height: calc(90vh - 180px);
}

.chat-widget-body {
    flex: 1;
    overflow-y: auto;
    background: white;
    padding: 4px;
}

.chat-widget-footer {
    padding: 16px;
    background: white;
    border-top: 1px solid rgba(0, 113, 227, 0.1);
    border-radius: 0 0 16px 16px;
}

.btn-send-widget {
    width: 36px;
    height: 36px;
    background: rgba(0, 113, 227, 0.1);
    color: #0071e3;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.2s;
}

.btn-send-widget:hover {
    background: rgba(0, 113, 227, 0.15) !important;
    transform: scale(1.05);
}

.btn-send-widget:active {
    transform: scale(0.95);
}

.btn-upload-widget:hover {
    background: rgba(0, 113, 227, 0.15) !important;
    transform: scale(1.05);
}

.btn-upload-widget:active {
    transform: scale(0.95);
}

/* Floating Chat Button */
.chat-float-button {
    position: fixed;
    bottom: 24px;
    right: 24px;
    height: 48px;
    padding: 0 20px;
    background: white;
    color: #1d1d1f;
    border: 1px solid #e5e5e7;
    border-radius: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 9998;
    transition: all 0.3s cubic-bezier(0.28, 0.11, 0.32, 1);
    overflow: hidden;
}

.chat-float-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 28px rgba(0, 0, 0, 0.2);
    border-color: #0071e3;
}

.chat-float-button.hidden {
    display: none;
}

.chat-button-content {
    display: flex;
    align-items: center;
    gap: 8px;
}

.chat-button-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0071e3;
}

.chat-button-text {
    font-size: 15px;
    font-weight: 600;
    color: #1d1d1f;
    white-space: nowrap;
}

.chat-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid white;
    background-size: cover;
    background-position: center;
    margin-left: -8px;
    transition: all 0.2s;
}

.chat-avatar:first-child {
    margin-left: 0;
}

.chat-float-button:hover .chat-avatar {
    margin-left: -6px;
}

.chat-float-button:hover .chat-avatar:first-child {
    margin-left: 0;
}

.chat-notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #e41e3f;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    border: 3px solid white;
}

/* Chat Messages in Widget */
.chat-widget-body .chat-message {
    margin: 12px 16px;
    display: flex;
    animation: fadeIn 0.2s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.chat-widget-body .chat-message.customer {
    justify-content: flex-end;
}

.chat-widget-body .message-bubble {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 16px;
    word-wrap: break-word;
    font-size: 14px;
}

.chat-widget-body .chat-message.admin .message-bubble {
    background: white;
    color: #1d1d1f;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.chat-widget-body .chat-message.customer .message-bubble {
    background: linear-gradient(135deg, #0071e3 0%, #005bb5 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-widget-body .message-time {
    font-size: 10px;
    margin-top: 4px;
    opacity: 0.7;
}

.chat-widget-body .message-sender {
    font-weight: 600;
    font-size: 11px;
    margin-bottom: 3px;
}

/* Info card */
.info-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 113, 227, 0.1);
}

.info-card h6 {
    color: #0071e3 !important;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: -0.01em;
}

.info-item {
    display: flex;
    align-items: start;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 8px;
    background: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(0, 113, 227, 0.08);
    transition: all 0.3s ease;
}

.info-item:hover {
    background: white;
    border-color: rgba(0, 113, 227, 0.2);
    transform: translateX(4px);
}

.info-item:last-of-type {
    margin-bottom: 0;
}

.info-icon-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0071e3 0%, #005bb5 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 18px;
    box-shadow: 0 2px 8px rgba(0, 113, 227, 0.3);
}

.info-item i {
    font-size: 18px;
}

.info-item strong {
    display: block;
    color: #2c3e50;
    font-size: 14px;
    margin-bottom: 4px;
    font-weight: 600;
}

.info-item p,
.info-item a {
    color: #5a6c7d;
    font-size: 14px;
    text-decoration: none;
    font-weight: 500;
}

.info-item a:hover {
    color: #0071e3;
}

/* Social buttons */
.social-icon {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.social-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    color: white;
}

.social-facebook {
    background: linear-gradient(135deg, #1877f2 0%, #0c5bc9 100%);
}

.social-telegram {
    background: linear-gradient(135deg, #0088cc 0%, #006699 100%);
}

.social-zalo {
    background: linear-gradient(135deg, #0180c7 0%, #015a8d 100%);
}

/* FAQ list */
.faq-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.faq-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    color: #2c3e50;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(0, 113, 227, 0.08);
}

.faq-item:hover {
    background: white;
    border-color: rgba(0, 113, 227, 0.2);
    padding-left: 18px;
    color: #0071e3;
    box-shadow: 0 2px 8px rgba(0, 113, 227, 0.1);
}

.faq-item i {
    color: #0071e3;
    font-size: 14px;
    transition: all 0.3s ease;
    width: 16px;
    flex-shrink: 0;
}

.faq-item:hover i {
    transform: translateX(4px);
}

/* Form controls */
.form-control,
.form-select {
    border: 1px solid #d2d2d7;
    transition: all 0.2s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
}

.form-label {
    color: #1d1d1f;
    font-size: 14px;
    margin-bottom: 8px;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .contact-option-card {
        width: 100%;
        height: auto;
        min-height: 110px;
        padding: 14px;
        gap: 12px;
    }

    .contact-option-icon {
        width: 45px;
        height: 45px;
        font-size: 20px;
    }

    .contact-option-card h5 {
        font-size: 14px;
    }

    .contact-option-card p {
        font-size: 11px;
    }

    .welcome-card {
        padding: 28px 20px;
    }

    .container {
        padding-bottom: 1rem !important;
    }

    .welcome-icon {
        width: 64px;
        height: 64px;
        font-size: 28px;
        margin-bottom: 20px;
    }

    .welcome-icon-inline {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }

    .welcome-card h3 {
        font-size: 18px;
    }

    .welcome-card p {
        font-size: 14px;
    }

    .welcome-features {
        gap: 16px;
        margin-bottom: 20px;
    }

    .welcome-feature-item {
        font-size: 13px;
    }

    .welcome-feature-item i {
        font-size: 16px;
    }

    .mail-card {
        padding: 20px;
    }

    .chat-widget {
        width: calc(100vw - 32px);
        right: 16px;
        bottom: 16px;
        max-height: calc(100vh - 100px);
    }

    .chat-widget.expanded {
        width: 100vw;
        height: 100vh;
        max-width: 100vw;
        max-height: 100vh;
        bottom: 0;
        right: 0;
        transform: none;
        border-radius: 0;
    }

    .chat-widget.expanded .chat-widget-header {
        border-radius: 0;
    }

    .chat-widget.expanded .chat-widget-body {
        height: calc(100vh - 160px);
    }

    .chat-widget.expanded .chat-widget-footer {
        border-radius: 0;
    }

    .chat-float-button {
        height: 48px;
        padding: 0 16px;
        right: 16px;
        bottom: 16px;
    }

    .chat-button-text {
        font-size: 14px;
    }

    .chat-avatar {
        width: 20px;
        height: 20px;
        border-width: 1.5px;
    }

    .chat-notification-badge {
        width: 20px;
        height: 20px;
        font-size: 11px;
    }
}

/* Hide back to top button on contact page */
#backToTopBtn {
    display: none !important;
}
</style>

<script>
let chatSessionId = <?= $chatSession ? $chatSession['id'] : 'null' ?>;
let lastMessageTime = null;
let pollingInterval = null;

// Toggle Mail Form
function toggleMailForm() {
    const mailForm = document.getElementById('mailForm');
    const welcomeSection = document.getElementById('welcomeSection');

    if (mailForm.style.display === 'none') {
        mailForm.style.display = 'block';
        welcomeSection.style.display = 'none';
        mailForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        mailForm.style.display = 'none';
        welcomeSection.style.display = 'block';
    }
}

// Open/Close Chat Widget
function openChatWidget() {
    const widget = document.getElementById('chatWidget');
    const floatButton = document.getElementById('chatFloatButton');

    widget.classList.add('active');
    floatButton.classList.add('hidden');

    if (chatSessionId) {
        loadMessagesWidget();
        if (!pollingInterval) {
            pollingInterval = setInterval(loadMessagesWidget, 2000);
        }
    }
}

function closeChatWidget() {
    const widget = document.getElementById('chatWidget');
    const floatButton = document.getElementById('chatFloatButton');

    // Show confirmation for guests if chat session exists
    <?php if ($isGuest): ?>
    if (chatSessionId && confirm('Bạn muốn kết thúc cuộc trò chuyện này?\n(Tin nhắn sẽ bị xóa)')) {
        // User confirmed, delete session
        const formData = new FormData();
        formData.append('session_id', chatSessionId);

        fetch('/api/chat.php?action=delete_session', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                chatSessionId = null;
                widget.classList.remove('active');
                widget.classList.remove('expanded');
                floatButton.classList.remove('hidden');

                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }

                // Reset lại form chat ban đầu (không reload trang)
                const startScreen = document.getElementById('chatWidgetStart');
                if (startScreen) {
                    location.reload(); // Vẫn cần reload để reset form
                }
            } else {
                console.error('Lỗi xóa session:', data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi kết nối:', error);
        });
    }
    // If user cancels, do nothing
    <?php else: ?>
    // For logged-in users, close directly (don't delete)
    widget.classList.remove('active');
    widget.classList.remove('expanded');
    floatButton.classList.remove('hidden');

    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    <?php endif; ?>
}

function minimizeChatWidget() {
    const widget = document.getElementById('chatWidget');
    const floatButton = document.getElementById('chatFloatButton');

    widget.classList.remove('active');
    widget.classList.remove('expanded');
    floatButton.classList.remove('hidden');
}

function toggleExpandWidget() {
    const widget = document.getElementById('chatWidget');
    const expandIcon = document.getElementById('expandIcon');
    const compressIcon = document.getElementById('compressIcon');

    widget.classList.toggle('expanded');

    // Toggle icon visibility
    if (widget.classList.contains('expanded')) {
        expandIcon.style.display = 'none';
        compressIcon.style.display = 'block';
    } else {
        expandIcon.style.display = 'block';
        compressIcon.style.display = 'none';
    }
}

// Start chat for guest (widget)
<?php if ($isGuest): ?>
// Update chat button text based on name input
const chatNameInput = document.getElementById('chatNameInput');
const chatBtnText = document.getElementById('chatBtnText');

if (chatNameInput && chatBtnText) {
    chatNameInput.addEventListener('input', function() {
        const name = this.value.trim();
        if (name) {
            // Split name into words
            const words = name.split(/\s+/);
            let displayName;

            if (words.length === 1) {
                // 1 word: use as-is with full text
                displayName = words[0];
                chatBtnText.textContent = `Chào ${displayName}, bắt đầu liên hệ với chúng tôi`;
            } else {
                // 2+ words: use last word
                displayName = words[words.length - 1];
                chatBtnText.textContent = `Chào ${displayName}, bắt đầu liên hệ với chúng tôi`;
            }
        } else {
            chatBtnText.textContent = 'Bắt đầu chat với tư cách ẩn danh';
        }
    });
}

document.getElementById('startChatFormWidget')?.addEventListener('submit', function(e) {
    e.preventDefault();

    // Disable button to prevent double submission
    const submitBtn = document.getElementById('startChatBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kết nối...';

    // Manually collect form data from inputs
    const formData = new FormData();
    const nameInput = document.getElementById('chatNameInput');
    const emailInput = document.getElementById('chatEmailInput');

    if (nameInput) formData.append('name', nameInput.value);
    if (emailInput) formData.append('email', emailInput.value);

    fetch('/api/chat.php?action=start_session', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            chatSessionId = data.session.id;
            // Không reload, chuyển sang giao diện chat bằng AJAX
            const startScreen = document.getElementById('chatWidgetStart');
            startScreen.innerHTML = `
                <div class="chat-widget-body" id="chatWidgetMessages">
                    <!-- Messages will be loaded here -->
                </div>
                <div class="chat-widget-footer" style="padding: 16px; background: white; border-top: 1px solid rgba(0, 113, 227, 0.1); border-radius: 0 0 16px 16px;">
                    <form id="sendMessageFormWidget" class="d-flex gap-2 align-items-center">
                        <input type="file" id="imageInputWidget" accept="image/*" style="display: none;">
                        <button type="button" class="btn-upload-widget" onclick="document.getElementById('imageInputWidget').click()" style="width: 36px; height: 36px; background: rgba(0, 113, 227, 0.1); color: #0071e3; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all 0.2s;">
                            <i class="fas fa-image"></i>
                        </button>
                        <input type="text"
                               class="form-control"
                               id="messageInputWidget"
                               placeholder="Nhập tin nhắn..."
                               autocomplete="off"
                               style="flex: 1; border-radius: 20px; border: 1px solid #e0e0e0; padding: 5px 16px; box-shadow: none; background: #f8f9fa; height: 36px;">
                        <button type="submit" class="btn-send-widget" style="width: 36px; height: 36px; background: rgba(0, 113, 227, 0.1); color: #0071e3; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all 0.2s;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            `;

            // Load messages sau một chút để đảm bảo greeting đã được lưu
            setTimeout(() => {
                loadMessagesWidget();

                // Bắt đầu polling
                if (!pollingInterval) {
                    pollingInterval = setInterval(loadMessagesWidget, 2000);
                }

                // Đăng ký sự kiện gửi tin nhắn
                setupSendMessageHandler();
            }, 500);
        } else {
            throw new Error(data.message || 'Có lỗi xảy ra khi gửi lời chào');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'Có lỗi xảy ra');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});
<?php endif; ?>

// Start chat for logged-in user
function startChat() {
    fetch('/api/chat.php?action=start_session', {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            chatSessionId = data.session.id;
            // Không reload, chuyển sang giao diện chat bằng AJAX
            const startScreen = document.getElementById('chatWidgetStart');
            startScreen.innerHTML = `
                <div class="chat-widget-body" id="chatWidgetMessages">
                    <!-- Messages will be loaded here -->
                </div>
                <div class="chat-widget-footer" style="padding: 16px; background: white; border-top: 1px solid rgba(0, 113, 227, 0.1); border-radius: 0 0 16px 16px;">
                    <form id="sendMessageFormWidget" class="d-flex gap-2 align-items-center">
                        <input type="file" id="imageInputWidget" accept="image/*" style="display: none;">
                        <button type="button" class="btn-upload-widget" onclick="document.getElementById('imageInputWidget').click()" style="width: 36px; height: 36px; background: rgba(0, 113, 227, 0.1); color: #0071e3; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all 0.2s;">
                            <i class="fas fa-image"></i>
                        </button>
                        <input type="text"
                               class="form-control"
                               id="messageInputWidget"
                               placeholder="Nhập tin nhắn..."
                               autocomplete="off"
                               style="flex: 1; border-radius: 20px; border: 1px solid #e0e0e0; padding: 5px 16px; box-shadow: none; background: #f8f9fa; height: 36px;">
                        <button type="submit" class="btn-send-widget" style="width: 36px; height: 36px; background: rgba(0, 113, 227, 0.1); color: #0071e3; border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all 0.2s;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            `;

            // Load messages sau một chút để đảm bảo greeting đã được lưu
            setTimeout(() => {
                loadMessagesWidget();

                // Bắt đầu polling
                if (!pollingInterval) {
                    pollingInterval = setInterval(loadMessagesWidget, 2000);
                }

                // Đăng ký sự kiện gửi tin nhắn
                setupSendMessageHandler();
            }, 500);
        } else {
            throw new Error(data.message || 'Có lỗi xảy ra khi gửi lời chào');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'Có lỗi xảy ra');
    });
}

// Setup send message handler for dynamically created form
function setupSendMessageHandler() {
    const form = document.getElementById('sendMessageFormWidget');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const messageInput = document.getElementById('messageInputWidget');
        const message = messageInput.value.trim();

        if (!message) return;

        // Tạo tin nhắn tạm thời và hiển thị ngay lập tức
        const tempMessage = {
            id: 'temp-' + Date.now(),
            sender_type: 'customer',
            sender_name: '<?= isLoggedIn() ? e(getCurrentUser()["full_name"] ?? getCurrentUser()["username"]) : "Bạn" ?>',
            message: message,
            created_at: new Date().toISOString()
        };

        // Hiển thị tin nhắn ngay lập tức
        appendMessageWidget(tempMessage);
        scrollToBottomWidget();

        // Clear input ngay
        messageInput.value = '';

        // Gửi đến server ở background
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('session_id', chatSessionId);
        formData.append('message', message);

        fetch('/api/chat.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Reload messages để lấy message ID thật từ server
                loadMessagesWidget();
            } else {
                console.error('Lỗi gửi tin nhắn:', data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi kết nối:', error);
        });
    });

    // Xử lý khi chọn ảnh
    const imageInput = document.getElementById('imageInputWidget');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                // Tạo preview ảnh
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Hiển thị preview ảnh ngay lập tức
                    const tempMessage = {
                        id: 'temp-' + Date.now(),
                        sender_type: 'customer',
                        sender_name: '<?= isLoggedIn() ? e(getCurrentUser()["full_name"] ?? getCurrentUser()["username"]) : "Bạn" ?>',
                        message: '',
                        image: e.target.result,
                        created_at: new Date().toISOString()
                    };
                    appendMessageWidget(tempMessage);
                    scrollToBottomWidget();

                    // Upload ảnh lên server
                    const formData = new FormData();
                    formData.append('action', 'send_message');
                    formData.append('session_id', chatSessionId);
                    formData.append('message', '[Hình ảnh]');
                    formData.append('image', file);

                    fetch('/api/chat.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            loadMessagesWidget();
                        } else {
                            console.error('Lỗi gửi ảnh:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi kết nối:', error);
                    });
                };
                reader.readAsDataURL(file);

                // Reset input để có thể chọn lại cùng file
                imageInput.value = '';
            }
        });
    }
}

// Load messages for widget
function loadMessagesWidget() {
    if (!chatSessionId) return;

    const url = lastMessageTime
        ? `/api/chat.php?action=get_messages&session_id=${chatSessionId}&after=${encodeURIComponent(lastMessageTime)}`
        : `/api/chat.php?action=get_messages&session_id=${chatSessionId}`;

    fetch(url)
    .then(res => res.json())
    .then(data => {
        if (data.success && data.messages.length > 0) {
            // Xóa tất cả tin nhắn tạm thời trước khi thêm tin nhắn thật
            const chatMessages = document.getElementById('chatWidgetMessages');
            if (chatMessages) {
                const tempMessages = chatMessages.querySelectorAll('[data-message-id^="widget-temp-"]');
                tempMessages.forEach(temp => temp.remove());
            }

            data.messages.forEach(msg => {
                appendMessageWidget(msg);
                lastMessageTime = msg.created_at;
            });
            scrollToBottomWidget();
        }
    });
}

// Send message from widget
document.getElementById('sendMessageFormWidget')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const messageInput = document.getElementById('messageInputWidget');
    const message = messageInput.value.trim();

    if (!message) return;

    // Tạo tin nhắn tạm thời và hiển thị ngay lập tức
    const tempMessage = {
        id: 'temp-' + Date.now(),
        sender_type: 'customer',
        sender_name: '<?= isLoggedIn() ? e(getCurrentUser()["full_name"] ?? getCurrentUser()["username"]) : "Bạn" ?>',
        message: message,
        created_at: new Date().toISOString()
    };

    // Hiển thị tin nhắn ngay lập tức
    appendMessageWidget(tempMessage);
    scrollToBottomWidget();

    // Clear input ngay
    messageInput.value = '';

    // Gửi đến server ở background
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('session_id', chatSessionId);
    formData.append('message', message);

    fetch('/api/chat.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Reload messages để lấy message ID thật từ server
            loadMessagesWidget();
        } else {
            console.error('Lỗi gửi tin nhắn:', data.message);
        }
    })
    .catch(error => {
        console.error('Lỗi kết nối:', error);
    });
});

// Append message to widget
function appendMessageWidget(msg) {
    const chatMessages = document.getElementById('chatWidgetMessages');
    if (!chatMessages) return;

    if (document.querySelector(`[data-message-id="widget-${msg.id}"]`)) {
        return; // Message already exists
    }

    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${msg.sender_type}`;
    messageDiv.setAttribute('data-message-id', `widget-${msg.id}`);

    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';

    // Chỉ hiển thị tên người gửi nếu là admin
    if (msg.sender_name && msg.sender_type === 'admin') {
        const sender = document.createElement('div');
        sender.className = 'message-sender';
        sender.textContent = msg.sender_name;
        bubble.appendChild(sender);
    }

    // Hiển thị ảnh nếu có
    if (msg.image) {
        const img = document.createElement('img');
        img.src = msg.image;
        img.style.maxWidth = '100%';
        img.style.borderRadius = '8px';
        img.style.marginBottom = '8px';
        img.style.cursor = 'pointer';
        img.onclick = function() {
            window.open(msg.image, '_blank');
        };
        bubble.appendChild(img);
    }

    // Hiển thị text nếu có
    if (msg.message) {
        const text = document.createElement('div');
        text.textContent = msg.message;
        bubble.appendChild(text);
    }

    const time = document.createElement('div');
    time.className = 'message-time';
    time.textContent = formatTime(msg.created_at);
    bubble.appendChild(time);

    messageDiv.appendChild(bubble);
    chatMessages.appendChild(messageDiv);
}

// Scroll to bottom widget
function scrollToBottomWidget() {
    const chatMessages = document.getElementById('chatWidgetMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
}

// Format time
function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

// Initialize
if (chatSessionId) {
    // Setup message handler for existing session (after page reload)
    setupSendMessageHandler();
}

// Control chat button visibility based on scroll position
// Show from 0-80%, hide from 80-100%
window.addEventListener('scroll', function() {
    const chatButton = document.getElementById('chatFloatButton');
    const scrollPercentage = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;

    if (scrollPercentage >= 80) {
        chatButton.classList.add('hidden');
    } else {
        chatButton.classList.remove('hidden');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
