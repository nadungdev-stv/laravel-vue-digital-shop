<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

initSession();
if (!isAdmin()) {
    setFlash('error', 'Bạn không có quyền truy cập');
    redirect('/');
}

$pageTitle = 'Tạo mã QR Ngân hàng';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Veyrix Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/css/admin-apple-style.css">
    <style>
        .layout-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #d2d2d7;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .layout-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            background: rgba(249, 250, 251, 0.8);
            backdrop-filter: blur(10px);
            font-weight: 600;
        }
        .layout-card-body {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }
        .qr-preview-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px dashed #ced4da;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .qr-image {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            color: #1d1d1f;
        }
        .saved-template-item {
            cursor: pointer;
            transition: background 0.2s;
        }
        .saved-template-item:hover {
            background-color: #f5f5f7;
        }
        /* Custom CSS removed for pill buttons */
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <?php require_once __DIR__ . '/includes/admin_header.php'; ?>

    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>
                <h4 class="mb-1 fw-bold">Tạo mã QR Ngân hàng (VietQR)</h4>
                <p class="text-secondary mb-0">Tạo nhanh mã QR chuyển khoản cho khách hàng</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Input Form -->
            <div class="col-lg-5">
                <div class="layout-card">
                    <div class="layout-card-header d-flex justify-content-between align-items-center">
                        <span class="text-primary"><i class="fas fa-edit me-2"></i>Thông tin thanh toán</span>
                        <div class="d-flex gap-2">
                             <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" onclick="openSettingsModal()" title="Cấu hình giá">
                                <i class="fas fa-cog"></i>
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border rounded-pill" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-history me-1"></i> Mẫu đã lưu
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0" id="savedTemplatesList">
                                    <li><a class="dropdown-item text-muted disabled" href="#">Chưa có mẫu nào</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="layout-card-body">
                        <form id="qrForm">
                            <!-- Service -->
                            <div class="mb-4">
                                <input type="hidden" id="service" value="">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <label class="form-label mb-0 me-2">Dịch vụ:</label>
                                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 service-btn" onclick="selectService('Netflix', this)">
                                        Netflix
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 service-btn" onclick="selectService('YouTube Premium', this)">
                                        YouTube
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 service-btn" onclick="selectService('Spotify', this)">
                                        Spotify
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 service-btn" onclick="selectService('custom', this)">
                                        Tùy biến
                                    </button>
                                </div>
                                <div id="customServiceDiv" class="mt-3 d-none">
                                    <label class="form-label small text-muted">Tên dịch vụ khác</label>
                                    <input type="text" class="form-control rounded-3" id="customServiceInput" placeholder="Nhập tên dịch vụ..." oninput="updateCustomService(this.value)">
                                </div>
                            </div>

                            <!-- Duration -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Thời hạn</label>
                                    <select class="form-select rounded-3" id="duration">
                                        <option value="1 tháng">1 tháng</option>
                                        <option value="2 tháng">2 tháng</option>
                                        <option value="3 tháng">3 tháng</option>
                                        <option value="6 tháng">6 tháng</option>
                                        <option value="12 tháng">12 tháng</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                     <label class="form-label">Số tiền (VNĐ)</label>
                                    <input type="number" class="form-control rounded-3" id="amount" placeholder="VD: 450000" min="0">
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="mb-3">
                                <label class="form-label">Nội dung chuyển khoản</label>
                                <div class="input-group">
                                    <input type="text" class="form-control rounded-start-3" id="addInfo" placeholder="VD: youtube1nam">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateRandomContent()" title="Tạo ngẫu nhiên">
                                        <i class="fas fa-random"></i>
                                    </button>
                                </div>
                                <div class="form-text small">Nên viết liền không dấu, ngắn gọn.</div>
                            </div>

                            <!-- Account Info (Read-only/Default) -->
                            <div class="mb-3">
                                <label class="form-label">Tài khoản nhận</label>
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex align-items-center mb-1">
                                        <img src="https://img.vietqr.io/image/MB-567892868-compact.png" alt="MB Bank" style="height: 20px; margin-right: 8px;">
                                        <span class="fw-bold">MB Bank</span>
                                    </div>
                                    <div class="fw-bold text-primary fs-5">567892868</div>
                                    <div class="text-uppercase small text-secondary">NGÔ ANH DŨNG</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2">
                                <button type="button" class="pill-button pill-button-blue px-4 py-2" onclick="updateQR()">
                                    <i class="fas fa-refresh me-2"></i>Tạo / Cập nhật QR
                                </button>
                                <button type="button" class="pill-button pill-button-white px-4 py-2 border" onclick="saveTemplate()">
                                    <i class="fas fa-save me-2 text-warning"></i>Lưu mẫu này
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview Column -->
            <div class="col-lg-7">
                <div class="layout-card">
                    <div class="layout-card-header">
                        <span class="text-success"><i class="fas fa-eye me-2"></i>Xem trước</span>
                    </div>
                    <div class="layout-card-body d-flex flex-column align-items-center justify-content-center bg-light">
                        <div id="qrLoading" class="text-center d-none my-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-secondary">Đang tạo mã QR...</p>
                        </div>
                        
                        <div id="qrResult" class="text-center">
                            <img id="qrImage" src="" alt="QR Code" class="qr-image mb-4 d-none" style="max-height: 450px;">
                            
                            <div id="qrActions" class="d-none">
                                <a id="downloadLink" href="#" download="qr-code.png" class="pill-button pill-button-green me-2">
                                    <i class="fas fa-download me-2"></i>Tải xuống ảnh
                                </a>
                                <button class="pill-button pill-button-gray" onclick="copyQRUrl()">
                                    <i class="fas fa-link me-2"></i>Copy Link
                                </button>
                            </div>
                            
                            <div id="qrPlaceholder" class="text-center py-5">
                                <i class="fas fa-qrcode fa-5x text-muted opacity-25 mb-3"></i>
                                <p class="text-muted">Nhập thông tin bên gói trài để tạo mã QR</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Cấu hình giá dịch vụ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="settingsModalBody">
                    <!-- Dynamic Content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="savePriceSettings()">Lưu cấu hình</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Default Config
        const BANK_ID = 'MB';
        const ACCOUNT_NO = '567892868';
        const ACCOUNT_NAME = 'Ngô Anh Dũng';
        const TEMPLATE = 'print'; // print, compact, qr_only

        // Pricing Config
        // Pricing Config (Dynamic Structure)
        const defaultServiceConfig = {
            'YouTube Premium': {
                mode: 'fixed',
                prefix: 'ytpre',
                packages: [
                    { duration: '6 tháng', price: 220000 },
                    { duration: '12 tháng', price: 450000 }
                ]
            },
            'Netflix': {
                mode: 'fixed',
                prefix: 'nf',
                packages: [
                    { duration: '1 tháng', price: 75000 },
                    { duration: '2 tháng', price: 150000 },
                    { duration: '3 tháng', price: 225000 },
                    { duration: '6 tháng', price: 450000 },
                    { duration: '12 tháng', price: 900000 }
                ]
            },
            'Spotify': {
                mode: 'fixed',
                prefix: 'sp',
                packages: [
                    { duration: '12 tháng', price: 300000 }
                ]
            }
        };
        let serviceConfig = {};

        document.addEventListener('DOMContentLoaded', () => {
            loadTemplates();
            loadPriceSettings();
            
            // Auto update content when Duration changes
            document.getElementById('duration').addEventListener('change', () => {
                autoFillContent();
                calculatePrice();
            });
        });

        function selectService(value, element) {
            // UI Update
            document.querySelectorAll('.service-btn').forEach(btn => {
                btn.classList.remove('btn-primary', 'text-white', 'shadow-sm');
                btn.classList.add('btn-outline-dark');
            });
            element.classList.remove('btn-outline-dark');
            element.classList.add('btn-primary', 'text-white', 'shadow-sm');

            // Logic Update
            const customDiv = document.getElementById('customServiceDiv');
            const customInput = document.getElementById('customServiceInput');
            const serviceInput = document.getElementById('service');

            if (value === 'custom') {
                customDiv.classList.remove('d-none');
                serviceInput.value = customInput.value; // set to existing input if any
                setTimeout(() => customInput.focus(), 100);
            } else {
                customDiv.classList.add('d-none');
                serviceInput.value = value;
            }
            
            // Special Logic for Duration Filtering based on Config
            const durationSelect = document.getElementById('duration');
            const options = durationSelect.options;
            
            if (serviceConfig[value]) {
                const config = serviceConfig[value];
                const allowedDurations = config.packages.map(p => p.duration);
                
                filterOptions(allowedDurations);
                
                // If current selection is invalid, reset
                if (!allowedDurations.includes(durationSelect.value)) {
                    durationSelect.value = allowedDurations[0] || '';
                }
            } else {
                // Show all for custom
                for (let i = 0; i < options.length; i++) options[i].hidden = false;
            }
            
            autoFillContent();
            calculatePrice();
        }



        function filterOptions(allowedValues) {
            const options = document.getElementById('duration').options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === '' || allowedValues.includes(options[i].value)) {
                    options[i].hidden = false;
                } else {
                    options[i].hidden = true;
                }
            }
        }
        
        function loadPriceSettings() {
            const saved = localStorage.getItem('qr_service_config_v3');
            if (saved) {
                serviceConfig = JSON.parse(saved);
            } else {
                // Migrate from old simple config if needed, or just default
                serviceConfig = JSON.parse(JSON.stringify(defaultServiceConfig));
            }
        }

        function savePriceSettings() {
            // Read values from Modal
            // We'll traverse the current DOM of the modal to update serviceConfig
            for (const serviceName in serviceConfig) {
                const config = serviceConfig[serviceName];
                
                if (config.mode === 'multiply') {
                    const priceInput = document.getElementById(`config_${serviceName}_base`);
                    if (priceInput) config.basePrice = Number(priceInput.value);
                } 
                
                // Read packages
                // We reuse the 'packages' array but need to re-read it from DOM because user might have added/removed rows
                const rows = document.querySelectorAll(`.package-row-${serviceName.replace(/\s+/g, '')}`);
                const newPackages = [];
                rows.forEach(row => {
                    const duration = row.querySelector('.pkg-duration').value;
                    const price = Number(row.querySelector('.pkg-price').value);
                    if (duration) {
                        newPackages.push({ duration, price });
                    }
                });
                config.packages = newPackages;
            }

            localStorage.setItem('qr_service_config_v3', JSON.stringify(serviceConfig));
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('settingsModal'));
            modal.hide();
            
            alert('Đã lưu cấu hình giá mới!');
            
            // Re-trigger select to update UI
            const currentService = document.getElementById('service').value;
             if (['Netflix', 'YouTube Premium', 'Spotify'].includes(currentService)) {
                 // Trigger logic update without UI (button) reference
                 // We need to simulate the button click or just call logic
                 // Ideally find the active button
                 const activeBtn = document.querySelector('.service-btn.btn-primary');
                 if (activeBtn) selectService(currentService, activeBtn);
             }
        }

        function openSettingsModal() {
            renderSettingsModal();
            new bootstrap.Modal(document.getElementById('settingsModal')).show();
        }

        function renderSettingsModal() {
            const body = document.getElementById('settingsModalBody');
            body.innerHTML = '';
            
            for (const [serviceName, config] of Object.entries(serviceConfig)) {
                const safeName = serviceName.replace(/\s+/g, '');
                const wrapper = document.createElement('div');
                wrapper.className = 'mb-4 border-bottom pb-4';
                
                let html = `<h6 class="fw-bold text-primary mb-3">${serviceName}</h6>`;
                
                if (config.mode === 'multiply') {
                    html += `
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label">Giá cơ bản (1 tháng)</label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control" id="config_${serviceName}_base" value="${config.basePrice}">
                                <div class="form-text">Giá các gói = Giá này x Số tháng</div>
                            </div>
                        </div>
                        <label class="form-label mb-2">Các gói thời hạn cho phép:</label>
                    `;
                } else {
                    html += `<label class="form-label mb-2">Danh sách gói & giá cố định:</label>`;
                }
                
                html += `<div id="pkg_container_${safeName}">`;
                
                config.packages.forEach((pkg, index) => {
                    html += createPackageRowHtml(safeName, pkg.duration, pkg.price, config.mode === 'multiply');
                });
                
                html += `</div>`;
                
                // Add button
                html += `
                    <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="addPackageRow('${safeName}', '${config.mode}')">
                        <i class="fas fa-plus me-1"></i> Thêm gói
                    </button>
                `;
                
                wrapper.innerHTML = html;
                body.appendChild(wrapper);
            }
        }

        function createPackageRowHtml(safeName, duration, price, isMultiply) {
            // If mode is multiply, price input is disabled/hidden or just shows 0 (it doesn't matter much effectively, but let's hide it to avoid confusion)
            // But user might want to see '0' or just ignore it. 
            // Better: If multiply, we only select Duration. Price is auto calculated in app, not config.
            
            const priceStyle = isMultiply ? 'display:none' : '';
            const priceVal = isMultiply ? 0 : price;
            
            return `
                <div class="row g-2 mb-2 align-items-center package-row-${safeName}">
                    <div class="col-6">
                        <input type="text" class="form-control form-control-sm pkg-duration" value="${duration}" placeholder="VD: 1 tháng">
                    </div>
                    <div class="col-4" style="${priceStyle}">
                        <input type="number" class="form-control form-control-sm pkg-price" value="${priceVal}" placeholder="Giá">
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.row').remove()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        function addPackageRow(safeName, mode) {
            const container = document.getElementById(`pkg_container_${safeName}`);
            const div = document.createElement('div');
            // Remove wrapper div from string, append directly
            div.innerHTML = createPackageRowHtml(safeName, '', 0, mode === 'multiply');
            // Extract the row
            container.appendChild(div.firstElementChild);
        }

        function calculatePrice() {
            const service = document.getElementById('service').value;
            const duration = document.getElementById('duration').value;
            const amountInput = document.getElementById('amount');
            
            if (!service || !duration) return;
            
            let price = 0;
            const config = serviceConfig[service];
            
            if (config) {
                if (config.mode === 'multiply') { // Netflix logic
                     let months = 0;
                    if (duration.includes('tháng')) {
                        months = parseInt(duration.replace(/[^0-9]/g, '')); // 12 thang -> 12
                    } else if (duration.includes('năm')) {
                         // Fallback logic if 'năm' is not in regex
                        months = 12 * parseInt(duration.replace(/[^0-9]/g, '') || 1);
                    }
                    
                    if (months > 0) {
                        price = months * config.basePrice;
                    }
                } else { // Fixed logic (YouTube, Spotify)
                    const pkg = config.packages.find(p => p.duration === duration);
                    if (pkg) price = pkg.price;
                }
            }
            
            if (price > 0) {
                amountInput.value = price;
            }
        }


        function updateCustomService(val) {
            document.getElementById('service').value = val;
            autoFillContent();
        }

        function autoFillContent() {
            const service = document.getElementById('service').value;
            const duration = document.getElementById('duration').value;
            const contentInput = document.getElementById('addInfo');
            
            // Luôn cập nhật hoặc chỉ cập nhật khi nội dung khớp với format cũ (optional) - Ở đây ta chọn luôn cập nhật để tiện lợi
            if (service && duration) {
                if (serviceConfig[service]) {
                    prefix = serviceConfig[service].prefix;
                } else {
                    prefix = removeVietnameseTones(service).replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
                }

                let cleanDuration = duration.replace(/[^0-9]/g, '') + 'thang';
                
                if (duration === '12 tháng') cleanDuration = '1nam'; // Map 12 months to 1 year for content
                if (duration.includes('năm')) cleanDuration = '1nam';                
                // Format: prefix + duration
                contentInput.value = `${prefix}${cleanDuration}`;
            }
        }

        function generateRandomContent() {
             const service = document.getElementById('service').value || 'dichvu';
             const cleanService = removeVietnameseTones(service).replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
             const randomId = Math.floor(Math.random() * 10000);
             document.getElementById('addInfo').value = `${cleanService}${randomId}`;
        }

        function updateQR() {
            const amount = document.getElementById('amount').value;
            const addInfo = document.getElementById('addInfo').value;
            
            if (!amount) {
                alert('Vui lòng nhập số tiền');
                document.getElementById('amount').focus();
                return;
            }

            const qrPlaceholder = document.getElementById('qrPlaceholder');
            const qrResult = document.getElementById('qrResult');
            const qrImage = document.getElementById('qrImage');
            const qrActions = document.getElementById('qrActions');
            const qrLoading = document.getElementById('qrLoading');

            qrPlaceholder.classList.add('d-none');
            qrActions.classList.add('d-none');
            qrImage.classList.add('d-none');
            qrLoading.classList.remove('d-none');

            // Construct URL
            // Format: https://img.vietqr.io/image/<BANK_ID>-<ACCOUNT_NO>-<TEMPLATE>.png?amount=<AMOUNT>&addInfo=<INFO>&accountName=<NAME>
            const baseUrl = `https://img.vietqr.io/image/${BANK_ID}-${ACCOUNT_NO}-${TEMPLATE}.png`;
            const params = new URLSearchParams({
                amount: amount,
                addInfo: addInfo,
                accountName: ACCOUNT_NAME
            });
            
            const finalUrl = `${baseUrl}?${params.toString()}`;
            
            // Load Image
            qrImage.onload = () => {
                qrLoading.classList.add('d-none');
                qrImage.classList.remove('d-none');
                qrActions.classList.remove('d-none');
            };
            
            qrImage.onerror = () => {
                qrLoading.classList.add('d-none');
                alert('Không thể tạo mã QR. Vui lòng kiểm tra lại thông tin.');
                qrPlaceholder.classList.remove('d-none');
            };

            qrImage.src = finalUrl;
            document.getElementById('downloadLink').href = finalUrl;
        }

        function copyQRUrl() {
            const url = document.getElementById('qrImage').src;
            navigator.clipboard.writeText(url).then(() => {
                alert('Đã copy link ảnh QR!');
            });
        }

        // --- Template Manager (LocalStorage) ---
        function saveTemplate() {
            const service = document.getElementById('service').value;
            const duration = document.getElementById('duration').value;
            const amount = document.getElementById('amount').value;
            const addInfo = document.getElementById('addInfo').value;

            if (!amount) {
                alert('Vui lòng nhập ít nhất số tiền để lưu mẫu');
                return;
            }

            const name = prompt('Đặt tên cho mẫu này:', `${service} ${duration}`);
            if (!name) return;

            const template = {
                id: Date.now(),
                name, service, duration, amount, addInfo
            };

            const templates = JSON.parse(localStorage.getItem('qr_templates') || '[]');
            templates.push(template);
            localStorage.setItem('qr_templates', JSON.stringify(templates));
            
            alert('Đã lưu mẫu thành công!');
            loadTemplates();
        }

        function loadTemplates() {
            const templates = JSON.parse(localStorage.getItem('qr_templates') || '[]');
            const list = document.getElementById('savedTemplatesList');
            
            if (templates.length === 0) {
                list.innerHTML = '<li><a class="dropdown-item text-muted disabled" href="#">Chưa có mẫu nào</a></li>';
                return;
            }

            list.innerHTML = '';
            templates.reverse().forEach(t => { // Show newest first
                const li = document.createElement('li');
                li.innerHTML = `
                    <a class="dropdown-item d-flex justify-content-between align-items-center" href="#" onclick="applyTemplate(${t.id})">
                        <span>
                            <div class="fw-bold small">${t.name}</div>
                            <div class="text-muted" style="font-size: 11px;">${Number(t.amount).toLocaleString('vi-VN')}đ - ${t.addInfo || 'Không nội dung'}</div>
                        </span>
                        <i class="fas fa-chevron-right text-secondary small ms-2"></i>
                    </a>
                `;
                list.appendChild(li);
            });
            
            // Add Clear All option
            const divider = document.createElement('li');
            divider.innerHTML = '<hr class="dropdown-divider">';
            list.appendChild(divider);
            
            const clearBtn = document.createElement('li');
            clearBtn.innerHTML = '<a class="dropdown-item text-danger small text-center" href="#" onclick="clearTemplates()">Xóa tất cả mẫu</a>';
            list.appendChild(clearBtn);
        }

        function applyTemplate(id) {
            const templates = JSON.parse(localStorage.getItem('qr_templates') || '[]');
            const t = templates.find(template => template.id === id);
            
            if (t) {
                // Determine which button to activate
                const serviceVal = t.service;
                let foundBtn = false;
                
                // Reset UI
                document.querySelectorAll('.service-btn').forEach(btn => {
                    btn.classList.remove('btn-primary', 'text-white', 'shadow-sm');
                    btn.classList.add('btn-outline-dark');
                });
                document.getElementById('customServiceDiv').classList.add('d-none');
                
                if (['Netflix', 'YouTube Premium', 'Spotify'].includes(serviceVal)) {
                     document.querySelectorAll('.service-btn').forEach(el => {
                        if (el.getAttribute('onclick').includes(`'${serviceVal}'`)) {
                             el.classList.remove('btn-outline-dark');
                             el.classList.add('btn-primary', 'text-white', 'shadow-sm');
                             foundBtn = true;
                        }
                    });
                } 
                
                if (!foundBtn) {
                     // Custom mode
                     document.querySelectorAll('.service-btn').forEach(el => {
                        if (el.getAttribute('onclick').includes("'custom'")) {
                            el.classList.remove('btn-outline-dark');
                            el.classList.add('btn-primary', 'text-white', 'shadow-sm');
                        }
                    });
                    document.getElementById('customServiceDiv').classList.remove('d-none');
                    document.getElementById('customServiceInput').value = serviceVal;
                }

                document.getElementById('service').value = t.service;
                document.getElementById('duration').value = t.duration;
                document.getElementById('amount').value = t.amount;
                document.getElementById('addInfo').value = t.addInfo;
                updateQR(); 
            }
        }

        function clearTemplates() {
            if (confirm('Bạn có chắc muốn xóa tất cả mẫu đã lưu?')) {
                localStorage.removeItem('qr_templates');
                loadTemplates();
            }
        }

        // Helper: Remove Vietnamese Tones
        function removeVietnameseTones(str) {
            str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g,"a"); 
            str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g,"e"); 
            str = str.replace(/ì|í|ị|ỉ|ĩ/g,"i"); 
            str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g,"o"); 
            str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g,"u"); 
            str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g,"y"); 
            str = str.replace(/đ/g,"d");
            return str;
        }
    </script>
</body>
</html>
