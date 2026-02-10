// Main JavaScript for Veyrix Shop

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Alerts will not auto-hide - user must close manually

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            if (!confirm('Bạn có chắc chắn muốn xóa?')) {
                e.preventDefault();
            }
        });
    });

    // Format currency inputs
    const currencyInputs = document.querySelectorAll('input[type="number"][data-currency]');
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function () {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(0);
            }
        });
    });

    // Image preview
    const imageInputs = document.querySelectorAll('input[type="file"][data-preview]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function () {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Format number as VND currency
function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Copy to clipboard
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Đã sao chép vào clipboard', 'success');
        });
    } else {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showToast('Đã sao chép vào clipboard', 'success');
    }
}

// Show toast notification
function showToast(message, type = 'info') {
    let toastContainer = document.getElementById('toast-container');

    // Nếu container đã tồn tại, cập nhật style mới
    if (toastContainer) {
        toastContainer.style.cssText = 'position: fixed; top: 100px; right: 20px; z-index: 999; min-width: 300px;';
    } else {
        toastContainer = createToastContainer();
    }

    const toastId = 'toast-' + Date.now();

    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';

    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `alert ${alertClass} alert-dismissible fade show`;
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    toastContainer.appendChild(toast);

    // Toast sẽ tự động ẩn sau 5 giây
    setTimeout(() => {
        // Sử dụng Bootstrap's fade out
        toast.classList.remove('show');
        toast.classList.add('hiding');

        // Xóa element sau khi animation hoàn tất
        setTimeout(() => {
            toast.remove();
        }, 150);
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = 'position: fixed; top: 100px; right: 20px; z-index: 999; min-width: 300px;';
    document.body.appendChild(container);
    return container;
}

// Loading overlay
function showLoading() {
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9998;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    overlay.innerHTML = '<div class="spinner-border text-light" role="status"></div>';
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// AJAX helper
async function ajaxRequest(url, method = 'GET', data = null) {
    showLoading();
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        const result = await response.json();

        hideLoading();
        return result;
    } catch (error) {
        hideLoading();
        showToast('Có lỗi xảy ra: ' + error.message, 'error');
        return null;
    }
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Dropdown slide animation
document.addEventListener('DOMContentLoaded', function () {
    const dropdownToggles = document.querySelectorAll('[data-bs-toggle="dropdown"]');

    dropdownToggles.forEach(toggle => {
        const dropdownMenu = toggle.nextElementSibling;

        if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
            // When dropdown is showing
            toggle.addEventListener('show.bs.dropdown', function (e) {
                // Set initial state
                dropdownMenu.style.opacity = '0';
                dropdownMenu.style.transform = 'translateY(-20px)';
                dropdownMenu.style.transition = 'none';
            });

            toggle.addEventListener('shown.bs.dropdown', function (e) {
                // Force reflow
                dropdownMenu.offsetHeight;

                // Apply transition and animate
                dropdownMenu.style.transition = 'opacity 0.15s ease-out, transform 0.15s ease-out';
                dropdownMenu.style.opacity = '1';
                dropdownMenu.style.transform = 'translateY(0)';
            });

            // When dropdown is hiding
            toggle.addEventListener('hide.bs.dropdown', function (e) {
                // Animate out
                dropdownMenu.style.opacity = '0';
                dropdownMenu.style.transform = 'translateY(-20px)';
            });
        }
    });
});

// Export functions for global use
window.formatMoney = formatMoney;
window.copyToClipboard = copyToClipboard;
window.showToast = showToast;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.ajaxRequest = ajaxRequest;
window.validateForm = validateForm;
window.debounce = debounce;
