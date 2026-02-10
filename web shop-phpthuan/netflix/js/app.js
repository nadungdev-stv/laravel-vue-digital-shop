// DOM Elements
const searchForm = document.getElementById('searchForm');
const emailInput = document.getElementById('emailInput');
const searchBtn = document.getElementById('searchBtn');
const statusMessage = document.getElementById('statusMessage');
const resultsSection = document.getElementById('resultsSection');
const resultCount = document.getElementById('resultCount');
const emailList = document.getElementById('emailList');
const emailModal = document.getElementById('emailModal');
const modalClose = document.getElementById('modalClose');
const modalTitle = document.getElementById('modalTitle');
const modalMeta = document.getElementById('modalMeta');
const modalContent = document.getElementById('modalContent');

// API Base URL - Thay đổi theo server của bạn
const API_BASE = 'api.php';

// Handle search form submit
searchForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = emailInput.value.trim();
    if (!email) return;

    // Update button state
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<span class="spinner"></span> Đang tìm...';

    hideStatus();
    resultsSection.style.display = 'none';

    try {
        const response = await fetch(`${API_BASE}?action=search`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Đã xảy ra lỗi');
        }

        if (data.success) {
            displayResults(data.emails, email);
        }
    } catch (error) {
        console.error('Search error:', error);
        showStatus(error.message, 'error');
    } finally {
        searchBtn.disabled = false;
        searchBtn.innerHTML = '<span>🔍</span> Tìm kiếm';
    }
});

// Display search results
function displayResults(emails, searchEmail) {
    resultsSection.style.display = 'block';

    if (emails.length === 0) {
        resultCount.innerHTML = `Không tìm thấy email nào gửi đến <strong>${searchEmail}</strong>`;
        emailList.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 20px;">Không có kết quả</p>';
        return;
    }

    resultCount.innerHTML = `Tìm thấy <strong>${emails.length}</strong> email gửi đến <strong>${searchEmail}</strong>`;

    emailList.innerHTML = emails.map(email => `
        <div class="email-item" data-id="${email.id}">
            <div class="email-header">
                <span class="email-from">${escapeHtml(email.from)}</span>
                <span class="email-date">${formatDate(email.date)}</span>
            </div>
            <div class="email-subject">${escapeHtml(email.subject)}</div>
            <div class="email-snippet">${escapeHtml(email.snippet)}</div>
        </div>
    `).join('');

    // Add click handlers
    document.querySelectorAll('.email-item').forEach(item => {
        item.addEventListener('click', () => {
            openEmailDetail(item.dataset.id);
        });
    });

    // Scroll to results
    resultsSection.scrollIntoView({ behavior: 'smooth' });
}

// Open email detail modal
async function openEmailDetail(emailId) {
    modalTitle.textContent = 'Đang tải...';
    modalMeta.innerHTML = '';
    modalContent.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner" style="margin: 0 auto;"></div></div>';
    emailModal.classList.add('active');

    try {
        const response = await fetch(`${API_BASE}?action=detail&id=${emailId}`);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Không thể tải email');
        }

        const email = data.email;

        modalTitle.textContent = email.subject || '(Không có tiêu đề)';
        modalMeta.innerHTML = `
            <p><strong>Từ:</strong> ${escapeHtml(email.from)}</p>
            <p><strong>Đến:</strong> ${escapeHtml(email.to)}</p>
            <p><strong>Ngày:</strong> ${formatDate(email.date)}</p>
        `;

        // Check if body is HTML
        if (email.body.includes('<') && email.body.includes('>')) {
            modalContent.innerHTML = email.body;
        } else {
            modalContent.textContent = email.body || '(Không có nội dung)';
        }
    } catch (error) {
        console.error('Email detail error:', error);
        modalContent.innerHTML = `<p style="color: var(--error);">${error.message}</p>`;
    }
}

// Close modal
modalClose.addEventListener('click', () => {
    emailModal.classList.remove('active');
});

emailModal.addEventListener('click', (e) => {
    if (e.target === emailModal) {
        emailModal.classList.remove('active');
    }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && emailModal.classList.contains('active')) {
        emailModal.classList.remove('active');
    }
});

// Utility functions
function showStatus(message, type = 'info') {
    statusMessage.className = `status status-${type}`;
    statusMessage.innerHTML = `<span>${getStatusIcon(type)}</span> ${message}`;
    statusMessage.style.display = 'flex';
}

function hideStatus() {
    statusMessage.style.display = 'none';
}

function getStatusIcon(type) {
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠️',
        info: 'ℹ️'
    };
    return icons[type] || icons.info;
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
        const now = new Date();
        const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

        if (diffDays === 0) {
            return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        } else if (diffDays === 1) {
            return 'Hôm qua';
        } else if (diffDays < 7) {
            return `${diffDays} ngày trước`;
        } else {
            return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
    } catch (e) {
        return dateStr;
    }
}
