<?php
$pageTitle = 'Lấy Mã Xác Nhận Netflix';
$pageDescription = 'Nhập email để lấy mã xác nhận Netflix Household';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-3 py-lg-5">
    <div class="row">
        <!-- Phần chính - 2/3 -->
        <div class="col-lg-8 mb-4">
            
            <!-- Header -->
            <div class="mb-4">
                <h2 class="fw-bold" style="color: #1d1d1f;">Lấy Mã Xác Nhận Netflix</h2>
                <p class="text-muted mb-0">Nhập email tài khoản Netflix để tìm mã xác nhận</p>
            </div>

            <!-- Search Card -->
            <div class="card border-0 mb-4" style="border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                <div class="card-body p-4">
                    <form id="searchForm">
                        <div class="row g-2 align-items-end">
                            <!-- Email -->
                            <div class="col-12 col-md">
                                <label class="form-label fw-semibold">Email tài khoản Netflix</label>
                                <input type="email" 
                                       class="form-control rounded-pill" 
                                       id="emailInput" 
                                       placeholder="Nhập email Netflix" 
                                       required
                                       style="padding-left: 16px; padding-right: 16px;">
                            </div>

                            <!-- Loại tìm kiếm -->
                            <div class="col-12 col-md-auto">
                                <label class="form-label fw-semibold">Loại tìm kiếm</label>
                                <select class="form-select rounded-pill" id="searchType" style="padding-left: 16px; padding-right: 32px; min-width: 220px;">
                                    <option value="temp_code" selected>Mã Tạm Thời</option>
                                    <option value="login_code">Mã Đăng Nhập</option>
                                    <option value="tv_code">Link TV (Cập Nhật Hộ Gia Đình)</option>
                                    <option value="household">Link Xác Minh Gia Đình</option>
                                    <option value="reset_password" <?= isAdmin() ? '' : 'disabled' ?>>Link Đặt Lại Mật Khẩu (Chỉ Admin)</option>
                                </select>
                            </div>

                            <!-- Nút tìm kiếm -->
                            <div class="col-12 col-md-auto" style="margin-top: 28px;">
                                <button type="submit" class="pill-button pill-button-gray w-100" id="searchBtn">
                                    <i class="fas fa-search"></i> Tìm kiếm
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Status Message -->
                    <div id="statusMessage" class="mt-3" style="display: none;"></div>

                    <!-- Results Section -->
                    <div id="resultsSection" class="mt-3" style="display: none;">
                        <div id="resultCount" class="mb-3"></div>
                        <div id="emailList"></div>
                    </div>

                    <!-- Hướng dẫn -->
                    <div class="mt-4 p-3" style="background: #f5f5f7; border-radius: 12px;">
                        <h6 class="fw-bold mb-3" style="color: #1d1d1f;">
                            <i class="fas fa-info-circle me-2 text-primary"></i>Hướng dẫn sử dụng
                        </h6>
                        
                        <div class="mb-3 pb-3" style="border-bottom: 1px solid #e5e5ea;">
                            <div class="d-flex align-items-start">
                                <span class="badge bg-primary rounded-pill me-2 step-badge">Bước 1</span>
                                <span>Trên màn hình Netflix, nhấn <strong>"Xem tạm thời"</strong> → sau đó nhấn <strong>"Gửi email"</strong></span>
                            </div>
                        </div>

                        <div class="mb-3 pb-3" style="border-bottom: 1px solid #e5e5ea;">
                            <div class="d-flex align-items-start">
                                <span class="badge bg-primary rounded-pill me-2 step-badge">Bước 2</span>
                                <span><strong>Chờ 10 giây</strong>, sau đó nhập email tài khoản Netflix vào ô trên và nhấn <strong>"Tìm kiếm"</strong></span>
                            </div>
                        </div>

                        <div class="d-flex align-items-start">
                            <span class="badge bg-primary rounded-pill me-2 step-badge">Bước 3</span>
                            <span>Bạn sẽ thấy <strong>mã xác nhận</strong> của mình. Nếu không thấy, vui lòng làm lại từ bước 1.</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Thao tác nhanh - 1/3 -->
        <div class="col-lg-4">
            <div class="card border-0" style="border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); background: #fff;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color: #2c3e50; font-size: 15px;">
                        <i class="fas fa-th-large me-2" style="color: #667eea;"></i>Thao tác nhanh
                    </h6>

                    <div class="qa-list">
                        <a href="/" class="qa-list-item">
                            <div class="qa-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <span>Trang chủ</span>

                        </a>
                        <a href="/products" class="qa-list-item">
                            <div class="qa-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <span>Mua sắm</span>

                        </a>
                        <a href="/orders" class="qa-list-item">
                            <div class="qa-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <span>Đơn hàng</span>

                        </a>
                        <a href="/contact" class="qa-list-item">
                            <div class="qa-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <span>Hỗ trợ</span>

                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Detail Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="modalTitle">Chi tiết email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalMeta" class="mb-3 pb-3" style="border-bottom: 1px solid #dee2e6;"></div>
                <div id="modalContent" class="bg-light p-3" style="border-radius: 12px;"></div>
            </div>
        </div>
    </div>
</div>

<style>
/* Tắt viền xanh Bootstrap */
#emailInput,
#emailInput:focus,
#emailInput:active,
.form-control:focus {
    border-color: #dee2e6 !important;
    box-shadow: none !important;
    outline: none !important;
}

.input-group:focus-within .input-group-text {
    border-color: #dee2e6 !important;
}

/* Tắt hiệu ứng animation cho badge */
.step-badge {
    animation: none !important;
    transform: none !important;
    transition: none !important;
}

/* Email item */
.email-item {
    padding: 14px;
    border-radius: 12px;
    background: #f5f5f7;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.email-item:hover {
    background: #e5e5ea;
}

.email-item .copy-btn {
    margin: 0;
    line-height: 1;
    height: auto;
}

/* Quick Action Styles - List with Rounded Icons */
.qa-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.qa-list-item {
    display: flex;
    align-items: center;
    padding: 4px 4px;
    background: transparent;
    border-radius: 40px;
    text-decoration: none;
    gap: 12px;
    transition: background 0.2s ease;
    position: relative;
    height: 44px;
}

.qa-list-item:hover {
    background: #f0f0f2;
}

.qa-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e8ecf7;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.2s ease;
}

.qa-list-item:hover .qa-icon {
    background: #d6ddf1;
}

.qa-icon i {
    color: #667eea;
    font-size: 16px;
}

.qa-list-item span {
    color: #2c3e50;
    font-size: 14px;
    font-weight: 500;
}

.qa-list-item .fa-chevron-right {
    color: #cbd5e0;
    font-size: 12px;
    transition: transform 0.2s ease;
    display: inline-flex;
    align-items: center;
}

.qa-list-item:hover .fa-chevron-right {
    transform: translateX(3px);
}

@media (max-width: 768px) {
    #searchBtn {
        font-size: 16px;
        padding: 10px 20px;
    }
    #searchBtn i {
        font-size: 18px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');
    const emailInput = document.getElementById('emailInput');
    const searchType = document.getElementById('searchType');
    const searchBtn = document.getElementById('searchBtn');
    const statusMessage = document.getElementById('statusMessage');
    const resultsSection = document.getElementById('resultsSection');
    const resultCount = document.getElementById('resultCount');
    const emailList = document.getElementById('emailList');
    const emailModalEl = document.getElementById('emailModal');
    const emailModal = new bootstrap.Modal(emailModalEl);
    const modalTitle = document.getElementById('modalTitle');
    const modalMeta = document.getElementById('modalMeta');
    const modalContent = document.getElementById('modalContent');

    const API_BASE = 'api.php';
    
    // Kiểm tra URL parameter khi load trang
    const urlParams = new URLSearchParams(window.location.search);
    const emailParam = urlParams.get('email');
    if (emailParam) {
        emailInput.value = emailParam;
        // Auto search nếu có email trong URL
        setTimeout(() => searchForm.dispatchEvent(new Event('submit')), 500);
    }

    searchForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = emailInput.value.trim();
        if (!email) return;
        
        // Cập nhật URL với email parameter
        const newUrl = `${window.location.pathname}?email=${encodeURIComponent(email)}`;
        window.history.pushState({}, '', newUrl);

        searchBtn.disabled = true;
        searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tìm...';
        statusMessage.style.display = 'none';
        resultsSection.style.display = 'none';

        try {
            const response = await fetch(`${API_BASE}?action=search`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    searchType: searchType.value
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Đã xảy ra lỗi');
            }
            
            if (data.success) {
                displayResults(data.emails, email);
            } else {
                emailList.innerHTML = `<div class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3"></i><p>${data.error || 'Không tìm thấy kết quả'}</p></div>`;
                resultsSection.style.display = 'block';
            }
        } catch (error) {
            console.error('Error:', error);
            statusMessage.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Lỗi: ${error.message}</div>`;
            statusMessage.style.display = 'block';
        } finally {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i> Tìm kiếm';
        }
    });

    function displayResults(emails, searchEmail) {
        resultsSection.style.display = 'block';
        
        if (emails.length === 0) {
            resultCount.innerHTML = `<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Không tìm thấy email. Vui lòng chờ 10 giây và thử lại.</div>`;
            emailList.innerHTML = '';
            return;
        }
        
        resultCount.innerHTML = `<div class="alert alert-success mb-3"><i class="fas fa-check-circle me-2"></i>Tìm thấy <strong>${emails.length}</strong> kết quả.</div>`;
        
        const typeMessages = {
            'login_code': 'Nhập mã này để đăng nhập',
            'temp_code': 'Mã truy cập Netflix tạm thời của bạn',
            'household': 'Link xác minh gia đình',
            'reset_password': 'Link đặt lại mật khẩu'
        };
        const message = typeMessages[searchType.value] || '';
        
        emailList.innerHTML = emails.map(email => {
            const code = email.code;
            const isLink = code && code.startsWith('http');
            const displayCode = isLink ? code.substring(0, 35) + '...' : code;
            
            let label = isLink ? 'Link đặt mật khẩu:' : 'Mã mới nhận được:';
            if (searchType.value === 'temp_code' && isLink) {
                label = 'Mã truy cập Netflix tạm thời của bạn:';
            } else if (searchType.value === 'household' && isLink) {
                label = 'Link xác minh gia đình của bạn:';
            }

            const isAutoFetch = (searchType.value === 'temp_code') && isLink;
            
            // Check expiration (Strictly > 15 mins, i.e. 16 onwards)
            let isExpired = false;
            // Apply expiration check to both types
            if ((searchType.value === 'temp_code' || searchType.value === 'household') && isLink) {
                try {
                    const emailDate = new Date(email.date);
                    const now = new Date();
                    const diffMins = (now - emailDate) / 60000;
                    if (diffMins >= 16) { 
                        isExpired = true;
                    }
                } catch(e) {}
            }

            return `
            <div class="email-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span>${label}</span>
                        ${code ? (isLink ? 
                            (isExpired ? 
                                `<span class="text-muted small fst-italic"><i class="fas fa-clock"></i> Link đã hết hạn (quá 15 phút)</span>`
                                :
                                (isAutoFetch ? 
                                    `<span class="auto-fetch-wrapper" data-link="${code}" data-status="pending">
                                        <span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Đang lấy mã...</span>
                                     </span>` 
                                    : 
                                    `<a href="${code}" target="_blank" class="fw-bold text-decoration-none" style="color: #667eea; font-size: 16px;">${displayCode}</a>
                                    <a href="${code}" target="_blank" class="text-secondary" title="Mở trong tab mới" style="font-size: 16px;">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>` 
                                )
                            )
                            : `<span class="fw-bold" style="color: #667eea; font-size: 18px;">${code}</span>`) : ''}
                        
                        ${code && !isAutoFetch ? `
                        <div class="copy-btn text-primary" data-code="${code}" style="cursor: pointer; font-size: 16px;" title="Sao chép">
                            <i class="far fa-copy"></i>
                        </div>
                        ` : ''}

                        ${!code ? `
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill view-detail-btn" data-id="${email.id}">
                            <i class="fas fa-eye"></i> Xem
                        </button>
                        ` : ''}
                    </div>
                    <span class="text-muted small">${timeAgo(email.date)}</span>
                </div>
            </div>
        `}).join('');
        
        // Trigger auto-fetch if needed
        setTimeout(processAutoFetchQueue, 100);
        
        // View detail button handlers
        document.querySelectorAll('.view-detail-btn').forEach(btn => {
            btn.addEventListener('click', () => openEmailDetail(btn.dataset.id));
        });
    }

    async function processAutoFetchQueue() {
        const items = document.querySelectorAll('.auto-fetch-wrapper[data-status="pending"]');
        if (items.length > 0) {
            showStatus(`Đang tự động lấy mã cho ${items.length} email... vui lòng đợi.`, 'info');
        }

        for (const item of items) {
            const link = item.dataset.link;
            item.dataset.status = "processing";
            
            try {
                const response = await fetch(`${API_BASE}?action=fetch_from_link`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({link: link})
                });
                const data = await response.json();

                if (data.success && data.code) {
                    // SUCCESS: Render code directly
                    item.innerHTML = `
                    <span class="fw-bold" style="color: #667eea; font-size: 18px;">${data.code}</span>
                    <span class="copy-btn text-primary ms-2" data-code="${data.code}" style="cursor: pointer; font-size: 16px;" title="Sao chép">
                        <i class="far fa-copy"></i>
                    </span>`;
                    
                    // Re-attach copy event implies re-running handler logic?
                    // Simpler: inline onclick or just rely on bubbling if I used delegation?
                    // Previous copy Logic: document.querySelectorAll('.copy-btn').forEach... run ONCE.
                    // So I need to bind this specific button.
                    const copyBtn = item.querySelector('.copy-btn');
                    if(copyBtn) {
                        copyBtn.addEventListener('click', (e) => {
                             navigator.clipboard.writeText(data.code).then(() => {
                                const i = copyBtn.querySelector('i');
                                i.className = 'fas fa-check text-success';
                                setTimeout(() => i.className = 'far fa-copy', 2000);
                            });
                        });
                    }

                } else {
                    // ERROR
                    item.innerHTML = `<span class="text-danger small" title="${data.error}"><i class="fas fa-exclamation-triangle"></i> Lỗi lấy mã</span>`;
                }
            } catch (err) {
                item.innerHTML = `<span class="text-danger small">Lỗi mạng</span>`;
            }
        }
        
        if (items.length > 0) {
            hideStatus();
        }
    }

    async function openEmailDetail(emailId) {
        modalTitle.textContent = 'Đang tải...';
        modalMeta.innerHTML = '';
        modalContent.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        emailModal.show();

        try {
            const response = await fetch(`${API_BASE}?action=detail&id=${emailId}`);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Lỗi tải nội dung');
            }

            if (data.success && data.email) {
                const email = data.email;
                modalTitle.textContent = email.subject || 'Chi tiết email';
                modalMeta.innerHTML = `
                    <div class="mb-1"><strong>Từ:</strong> ${escapeHtml(email.from)}</div>
                    <div class="text-muted small">${formatDate(email.date)}</div>
                `;
                modalContent.innerHTML = email.body && email.body.includes('<') ? email.body : `<pre style="white-space: pre-wrap;">${escapeHtml(email.body || '')}</pre>`;
            }
        } catch (error) {
            modalContent.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    function showStatus(message, type = 'info') {
        statusMessage.className = `alert alert-${type}`;
        statusMessage.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${message}`;
        statusMessage.style.display = 'block';
    }

    function hideStatus() {
        statusMessage.style.display = 'none';
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        try {
            const date = new Date(dateStr);
            return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return dateStr;
        }
    }
    
    function timeAgo(dateStr) {
        if (!dateStr) return '';
        try {
            const date = new Date(dateStr);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Vừa xong';
            if (diffMins < 60) return `${diffMins} phút trước`;
            if (diffHours < 24) return `${diffHours} giờ trước`;
            if (diffDays < 7) return `${diffDays} ngày trước`;
            return formatDate(dateStr);
        } catch (e) {
            return dateStr;
        }
    }
    // Event listener for "Get Code" button
    emailList.addEventListener('click', async function(e) {
        const btn = e.target.closest('.get-link-code-btn');
        if (!btn) return;
        
        const link = btn.getAttribute('data-link');
        if (!link) return;

        // UI Loading State
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        try {
            const response = await fetch(`${API_BASE}?action=fetch_from_link`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({link: link})
            });
            const data = await response.json();

            if (data.success && data.code) {
                // SUCCESS: Replace button with code
                const codeSpan = document.createElement('span');
                codeSpan.className = 'fw-bold text-success ms-2';
                codeSpan.style.fontSize = '18px';
                codeSpan.textContent = data.code;
                codeSpan.title = 'Mã đã lấy thành công';
                
                // Add copy button next to it?
                // Reuse existing copy functionality if possible, but simplest is just show text.
                
                // Remove the button and show code
                btn.replaceWith(codeSpan);
                
                // Also add a copy button manually next to it if we want, but let's keep it simple first.
                
            } else {
                 alert('Lỗi: ' + (data.error || 'Không lấy được mã'));
                 btn.innerHTML = originalHtml;
                 btn.disabled = false;
            }
        } catch (err) {
             alert('Lỗi kết nối: ' + err.message);
             btn.innerHTML = originalHtml;
             btn.disabled = false;
        }
    });

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
