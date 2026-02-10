/**
 * Admin Panel AJAX Handler
 * Handles all AJAX operations for admin pages
 */

// Global config
const AdminAjax = {
    baseUrl: window.location.origin,

    // Show loading overlay
    showLoading: function(message = 'Đang xử lý...') {
        if (!document.getElementById('admin-loading-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'admin-loading-overlay';
            overlay.innerHTML = `
                <div class="admin-loading-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">${message}</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        document.getElementById('admin-loading-overlay').style.display = 'flex';
    },

    // Hide loading overlay
    hideLoading: function() {
        const overlay = document.getElementById('admin-loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    },

    // Show toast notification
    showToast: function(message, type = 'success') {
        // Remove existing toast
        const existingToast = document.getElementById('admin-toast');
        if (existingToast) {
            existingToast.remove();
        }

        const toast = document.createElement('div');
        toast.id = 'admin-toast';
        toast.className = `admin-toast admin-toast-${type}`;

        const icon = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        }[type] || 'fa-info-circle';

        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(toast);

        // Auto remove after 4 seconds
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    // Confirm dialog
    confirm: function(message, callback) {
        const result = window.confirm(message);
        if (result && callback) {
            callback();
        }
        return result;
    },

    // Generic AJAX request
    request: function(url, options = {}) {
        const defaultOptions = {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        };

        // If data is FormData, remove Content-Type header
        if (options.body instanceof FormData) {
            delete defaultOptions.headers['Content-Type'];
        }

        const finalOptions = { ...defaultOptions, ...options };

        return fetch(url, finalOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                throw error;
            });
    }
};

// Handle form submissions with AJAX
document.addEventListener('submit', function(e) {
    const form = e.target;

    // Check if form has ajax-form class
    if (!form.classList.contains('ajax-form')) {
        return;
    }

    e.preventDefault();

    const formData = new FormData(form);
    const url = form.action || window.location.href;
    const method = form.method || 'POST';

    AdminAjax.showLoading();

    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        AdminAjax.hideLoading();

        if (data.success) {
            AdminAjax.showToast(data.message || 'Thao tác thành công!', 'success');

            // Close modal if exists
            const modal = form.closest('.modal');
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }

            // Reload page or update content
            if (data.reload) {
                setTimeout(() => window.location.reload(), 1000);
            } else if (data.redirect) {
                setTimeout(() => window.location.href = data.redirect, 1000);
            } else if (data.html) {
                // Update specific element
                if (data.target) {
                    const target = document.querySelector(data.target);
                    if (target) {
                        target.innerHTML = data.html;
                    }
                }
            }
        } else {
            AdminAjax.showToast(data.message || 'Có lỗi xảy ra!', 'error');
        }
    })
    .catch(error => {
        AdminAjax.hideLoading();
        AdminAjax.showToast('Lỗi kết nối! Vui lòng thử lại.', 'error');
        console.error('Form submission error:', error);
    });
});

// Handle delete actions with AJAX
document.addEventListener('click', function(e) {
    const deleteBtn = e.target.closest('[data-ajax-delete]');

    if (deleteBtn) {
        e.preventDefault();

        const url = deleteBtn.getAttribute('href') || deleteBtn.getAttribute('data-url');
        const confirmMsg = deleteBtn.getAttribute('data-confirm') || 'Bạn có chắc chắn muốn xóa?';

        if (!AdminAjax.confirm(confirmMsg)) {
            return;
        }

        AdminAjax.showLoading('Đang xóa...');

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            AdminAjax.hideLoading();

            if (data.success) {
                AdminAjax.showToast(data.message || 'Đã xóa thành công!', 'success');

                // Remove row from table
                const row = deleteBtn.closest('tr');
                if (row) {
                    row.remove();
                }

                // Or reload page
                if (data.reload) {
                    setTimeout(() => window.location.reload(), 1000);
                }
            } else {
                AdminAjax.showToast(data.message || 'Không thể xóa!', 'error');
            }
        })
        .catch(error => {
            AdminAjax.hideLoading();
            AdminAjax.showToast('Lỗi kết nối! Vui lòng thử lại.', 'error');
            console.error('Delete error:', error);
        });
    }
});

// Handle update status with AJAX
document.addEventListener('change', function(e) {
    const statusSelect = e.target.closest('[data-ajax-status]');

    if (statusSelect) {
        const url = statusSelect.getAttribute('data-url');
        const field = statusSelect.getAttribute('name');
        const value = statusSelect.value;
        const id = statusSelect.getAttribute('data-id');

        AdminAjax.showLoading('Đang cập nhật...');

        const data = {
            id: id,
            field: field,
            value: value
        };

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            AdminAjax.hideLoading();

            if (data.success) {
                AdminAjax.showToast(data.message || 'Đã cập nhật!', 'success');
            } else {
                AdminAjax.showToast(data.message || 'Cập nhật thất bại!', 'error');
                // Revert select value
                statusSelect.value = statusSelect.getAttribute('data-original');
            }
        })
        .catch(error => {
            AdminAjax.hideLoading();
            AdminAjax.showToast('Lỗi kết nối!', 'error');
            console.error('Status update error:', error);
        });
    }
});

// Handle AJAX pagination
document.addEventListener('click', function(e) {
    const pageLink = e.target.closest('[data-ajax-page]');

    if (pageLink) {
        e.preventDefault();

        const url = pageLink.getAttribute('href');
        const targetContainer = pageLink.getAttribute('data-target') || '.admin-container';

        AdminAjax.showLoading('Đang tải...');

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            AdminAjax.hideLoading();

            // Parse HTML and extract content
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector(targetContainer);

            if (newContent) {
                document.querySelector(targetContainer).innerHTML = newContent.innerHTML;

                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });

                // Update URL
                window.history.pushState({}, '', url);
            }
        })
        .catch(error => {
            AdminAjax.hideLoading();
            AdminAjax.showToast('Lỗi tải trang!', 'error');
            console.error('Pagination error:', error);
        });
    }
});

// Handle AJAX search/filter
document.addEventListener('submit', function(e) {
    const searchForm = e.target.closest('[data-ajax-search]');

    if (searchForm) {
        e.preventDefault();

        const formData = new FormData(searchForm);
        const url = searchForm.action || window.location.href;
        const targetContainer = searchForm.getAttribute('data-target') || '.admin-container';

        // Build URL with query params
        const params = new URLSearchParams(formData);
        const searchUrl = `${url}?${params.toString()}`;

        AdminAjax.showLoading('Đang tìm kiếm...');

        fetch(searchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            AdminAjax.hideLoading();

            // Parse HTML and extract content
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector(targetContainer);

            if (newContent) {
                document.querySelector(targetContainer).innerHTML = newContent.innerHTML;

                // Update URL
                window.history.pushState({}, '', searchUrl);
            }
        })
        .catch(error => {
            AdminAjax.hideLoading();
            AdminAjax.showToast('Lỗi tìm kiếm!', 'error');
            console.error('Search error:', error);
        });
    }
});

// Handle inline edit
document.addEventListener('click', function(e) {
    const editBtn = e.target.closest('[data-inline-edit]');

    if (editBtn) {
        e.preventDefault();

        const field = editBtn.getAttribute('data-field');
        const id = editBtn.getAttribute('data-id');
        const currentValue = editBtn.getAttribute('data-value');
        const cell = editBtn.closest('td');

        // Create input
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control form-control-sm';
        input.value = currentValue;

        // Create buttons
        const saveBtn = document.createElement('button');
        saveBtn.className = 'pill-button pill-button-blue-sm mt-1 me-1';
        saveBtn.innerHTML = '<i class="fas fa-check"></i>';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'pill-button pill-button-gray mt-1';
        cancelBtn.innerHTML = '<i class="fas fa-times"></i>';

        // Store original content
        const originalContent = cell.innerHTML;

        // Replace cell content
        cell.innerHTML = '';
        cell.appendChild(input);
        cell.appendChild(saveBtn);
        cell.appendChild(cancelBtn);

        input.focus();

        // Cancel handler
        cancelBtn.onclick = function() {
            cell.innerHTML = originalContent;
        };

        // Save handler
        saveBtn.onclick = function() {
            const newValue = input.value;

            if (newValue === currentValue) {
                cell.innerHTML = originalContent;
                return;
            }

            const url = editBtn.getAttribute('data-url');

            AdminAjax.showLoading('Đang lưu...');

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    field: field,
                    value: newValue
                })
            })
            .then(response => response.json())
            .then(data => {
                AdminAjax.hideLoading();

                if (data.success) {
                    AdminAjax.showToast('Đã cập nhật!', 'success');
                    // Update displayed value
                    editBtn.setAttribute('data-value', newValue);
                    cell.innerHTML = originalContent.replace(currentValue, newValue);
                } else {
                    AdminAjax.showToast(data.message || 'Cập nhật thất bại!', 'error');
                    cell.innerHTML = originalContent;
                }
            })
            .catch(error => {
                AdminAjax.hideLoading();
                AdminAjax.showToast('Lỗi kết nối!', 'error');
                cell.innerHTML = originalContent;
                console.error('Inline edit error:', error);
            });
        };
    }
});

// Handle table sorting with AJAX
AdminAjax.handleTableSort = function(link, event) {
    event.preventDefault();

    const url = link.getAttribute('href');
    const targetContainer = '.admin-container';

    // Show loading state
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        tableContainer.style.opacity = '0.5';
        tableContainer.style.pointerEvents = 'none';
    }

    // Fetch new data
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.text();
    })
    .then(html => {
        // Parse the HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Extract the new table
        const newTableContainer = doc.querySelector('.table-responsive');
        const oldTableContainer = document.querySelector('.table-responsive');

        if (newTableContainer && oldTableContainer) {
            // Replace table content
            oldTableContainer.innerHTML = newTableContainer.innerHTML;

            // Re-attach sort event listeners
            AdminAjax.initTableSort();
        }

        // Extract pagination if exists
        const newPagination = doc.querySelector('.pagination');
        const oldPagination = document.querySelector('.pagination');
        if (newPagination && oldPagination) {
            oldPagination.parentElement.innerHTML = newPagination.parentElement.innerHTML;
        }

        // Update URL without reload
        window.history.pushState({}, '', url);

        // Restore state
        if (tableContainer) {
            tableContainer.style.opacity = '1';
            tableContainer.style.pointerEvents = 'auto';
        }
    })
    .catch(error => {
        console.error('Sort error:', error);
        AdminAjax.showToast('Lỗi khi sắp xếp dữ liệu!', 'error');

        // Restore state
        if (tableContainer) {
            tableContainer.style.opacity = '1';
            tableContainer.style.pointerEvents = 'auto';
        }
    });
};

// Initialize table sort listeners
AdminAjax.initTableSort = function() {
    const sortLinks = document.querySelectorAll('.sortable-header');
    sortLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            AdminAjax.handleTableSort(this, e);
        });
    });
};

// Handle Load More button
AdminAjax.handleLoadMore = function(button) {
    const nextPage = parseInt(button.getAttribute('data-page'));
    const totalPages = parseInt(button.getAttribute('data-total-pages'));
    const search = button.getAttribute('data-search') || '';
    const category = button.getAttribute('data-category') || '';
    const sort = button.getAttribute('data-sort') || '';
    const order = button.getAttribute('data-order') || '';

    // Build URL
    const params = new URLSearchParams();
    params.append('page', nextPage);
    if (search && search !== '') {
        params.append('search', decodeURIComponent(search));
    }
    if (category && category !== '' && category !== '0') {
        params.append('category', category);
    }
    if (sort && sort !== '') {
        params.append('sort', sort);
    }
    if (order && order !== '') {
        params.append('order', order);
    }
    const url = `/admin/products?${params.toString()}`;

    // Show loading state
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải...';
    button.disabled = true;

    // Fetch data
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.text();
    })
    .then(html => {
        // Parse HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Extract new table rows
        const newRows = doc.querySelectorAll('.table tbody tr');
        const currentTbody = document.querySelector('.table tbody');

        if (newRows.length > 0 && currentTbody) {
            // Append new rows (skip empty state row)
            newRows.forEach(row => {
                // Check if this is not an empty state row
                const firstCell = row.querySelector('td');
                if (firstCell && !firstCell.hasAttribute('colspan')) {
                    currentTbody.appendChild(row.cloneNode(true));
                }
            });
        }

        // Update button state
        if (nextPage < totalPages) {
            button.setAttribute('data-page', nextPage + 1);
            button.innerHTML = `<i class="fas fa-arrow-down"></i> Xem thêm (Trang ${nextPage}/${totalPages})`;
            button.disabled = false;
        } else {
            // Hide button if no more pages
            button.parentElement.remove();
        }

        // Update URL
        window.history.pushState({}, '', url);
    })
    .catch(error => {
        AdminAjax.showToast('Lỗi khi tải thêm dữ liệu!', 'error');
        button.innerHTML = originalText;
        button.disabled = false;
    });
};

// Initialize Load More button
AdminAjax.initLoadMore = function() {
    const loadMoreBtn = document.querySelector('.load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            AdminAjax.handleLoadMore(this);
        });
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin AJAX initialized');

    // Initialize table sorting
    AdminAjax.initTableSort();

    // Initialize load more
    AdminAjax.initLoadMore();
});

// Export for global use
window.AdminAjax = AdminAjax;
