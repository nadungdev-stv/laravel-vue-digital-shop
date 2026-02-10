<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
initSession();

// Xử lý cập nhật giỏ hàng (TRƯỚC KHI OUTPUT HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_quantity' && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
            // AJAX update cho 1 item
            $cartId = (int)$_POST['cart_id'];
            $quantity = max(1, min(5, (int)$_POST['quantity'])); // Giới hạn 1-5

            try {
                db()->query("UPDATE cart SET quantity = ? WHERE id = ?", [$quantity, $cartId]);

                // Trả về JSON nếu là AJAX request
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                }
            } catch (Exception $e) {
                error_log("Update cart error: " . $e->getMessage());
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
            }
        } elseif ($_POST['action'] === 'remove' && isset($_POST['cart_id'])) {
            try {
                db()->query("DELETE FROM cart WHERE id = ?", [$_POST['cart_id']]);
            } catch (Exception $e) {
                error_log("Remove cart error: " . $e->getMessage());
            }
            setFlash('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
        } elseif ($_POST['action'] === 'remove_multiple' && isset($_POST['cart_ids'])) {
            try {
                $cartIds = explode(',', $_POST['cart_ids']);
                $placeholders = str_repeat('?,', count($cartIds) - 1) . '?';
                db()->query("DELETE FROM cart WHERE id IN ($placeholders)", $cartIds);
                setFlash('success', 'Đã xóa ' . count($cartIds) . ' sản phẩm khỏi giỏ hàng');
            } catch (Exception $e) {
                error_log("Remove multiple cart error: " . $e->getMessage());
            }
        } elseif ($_POST['action'] === 'clear') {
            clearCart();
            setFlash('success', 'Đã xóa toàn bộ giỏ hàng');
        }
        redirect('/cart');
    }
}

$pageTitle = 'Giỏ hàng';
$csrfToken = generateCSRFToken();
require_once __DIR__ . '/includes/header.php';

// Lấy cart items với thông tin variant
$userId = isLoggedIn() ? getCurrentUser()['id'] : null;
$sessionId = session_id();

$cartItems = [];
$total = 0;

try {
    if ($userId) {
        $stmt = db()->query(
            "SELECT c.*, p.name, p.price, p.sale_price, p.image, p.stock_quantity, p.status, p.slug,
                    v.id as variant_id, v.name as variant_name, v.price as variant_price,
                    v.sale_price as variant_sale_price, v.variant_title, v.variant_image, v.slug as variant_slug,
                    (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
             FROM cart c
             JOIN products p ON c.product_id = p.id
             LEFT JOIN product_variants v ON c.variant_id = v.id
             WHERE c.user_id = ?",
            [$userId]
        );
    } else {
        $stmt = db()->query(
            "SELECT c.*, p.name, p.price, p.sale_price, p.image, p.stock_quantity, p.status, p.slug,
                    v.id as variant_id, v.name as variant_name, v.price as variant_price,
                    v.sale_price as variant_sale_price, v.variant_title, v.variant_image, v.slug as variant_slug,
                    (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
             FROM cart c
             JOIN products p ON c.product_id = p.id
             LEFT JOIN product_variants v ON c.variant_id = v.id
             WHERE c.session_id = ? AND c.user_id IS NULL",
            [$sessionId]
        );
    }

    while ($row = $stmt->fetch()) {
        // Xác định giá và tên hiển thị
        $price = 0;
        $displayName = '';

        // Kiểm tra variant TỒN TẠI trong DB (không chỉ cart.variant_id)
        $hasVariant = $row['variant_id'] && !empty($row['variant_price']);

        if ($hasVariant) {
            // Nếu có variant và variant tồn tại
            $price = $row['variant_sale_price'] ?? $row['variant_price'];
            $displayName = !empty($row['variant_title']) ? $row['variant_title'] : ($row['name'] . ' - ' . $row['variant_name']);
        } else {
            // Nếu không có variant hoặc variant đã bị xóa
            $price = $row['sale_price'] ?? $row['price'] ?? 0;
            $displayName = $row['name'] ?? 'Sản phẩm';
        }

        // Đảm bảo displayName không rỗng
        if (empty($displayName)) {
            $displayName = $row['name'] ?? 'Sản phẩm';
        }

        // Xác định ảnh hiển thị (ưu tiên: variant_image > first_gallery_image > product_image)
        $image = '';
        if (!empty($row['variant_image'])) {
            $image = $row['variant_image'];
        } elseif (!empty($row['first_gallery_image'])) {
            $image = $row['first_gallery_image'];
        } elseif (!empty($row['image'])) {
            $image = $row['image'];
        }

        $quantity = (int)($row['quantity'] ?? 1);
        $subtotal = $price * $quantity;

        // Xác định URL sản phẩm (ưu tiên variant_slug > slug)
        $productUrl = !empty($row['variant_slug']) ? '/' . $row['variant_slug'] : '/' . ($row['slug'] ?? '#');

        $cartItems[] = [
            'cart_id' => $row['id'],
            'product_id' => $row['product_id'],
            'variant_id' => $row['variant_id'],
            'name' => $displayName,
            'image' => $image,
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'stock_quantity' => $row['stock_quantity'] ?? 0,
            'status' => $row['status'] ?? 'active',
            'product_url' => $productUrl
        ];

        $total += $subtotal;
    }
} catch (Exception $e) {
    error_log("Cart error: " . $e->getMessage());
}
?>

<div class="container">
    <h1 class="mb-4"><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</h1>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
            <h4>Giỏ hàng trống</h4>
            <p>Bạn chưa có sản phẩm nào trong giỏ hàng</p>
            <a href="/products" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update">

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th width="150">Giá</th>
                                            <th width="120">Số lượng</th>
                                            <th width="150">Tổng</th>
                                            <th width="80"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cartItems as $item): ?>
                                            <tr data-cart-item-id="<?= $item['cart_id'] ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= getProductImage($item['image'], $item['name'], 128, 60) ?>"
                                                             alt="<?= e($item['name']) ?>"
                                                             class="me-3"
                                                             style="width: 128px; height: 60px; object-fit: cover; border-radius: 6px;">
                                                        <div>
                                                            <h6 class="mb-0">
                                                                <a href="<?= e($item['product_url']) ?>" class="text-decoration-none text-dark">
                                                                    <?= e($item['name']) ?>
                                                                </a>
                                                            </h6>
                                                            <?php if ($item['stock_quantity'] <= 0): ?>
                                                                <small class="text-danger">
                                                                    <i class="fas fa-exclamation-triangle"></i> Hết hàng
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= formatMoney($item['price']) ?></strong>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           name="quantities[<?= $item['cart_id'] ?>]"
                                                           value="<?= $item['quantity'] ?>"
                                                           min="1"
                                                           max="5"
                                                           class="form-control quantity-input"
                                                           data-cart-id="<?= $item['cart_id'] ?>"
                                                           data-price="<?= $item['price'] ?>"
                                                           style="width: 80px;">
                                                </td>
                                                <td>
                                                    <strong class="text-primary item-subtotal" data-cart-id="<?= $item['cart_id'] ?>"><?= formatMoney($item['subtotal']) ?></strong>
                                                </td>
                                                <td>
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger remove-cart-item-ajax"
                                                            data-cart-id="<?= $item['cart_id'] ?>"
                                                            data-product-id="<?= $item['product_id'] ?>"
                                                            data-variant-id="<?= $item['variant_id'] ?? '' ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Cart Items -->
                            <div class="cart-items-mobile">
                                <?php $index = 1; foreach ($cartItems as $item): ?>
                                    <div class="cart-item-mobile" data-cart-item-id="<?= $item['cart_id'] ?>">
                                        <div class="cart-item-mobile-header">
                                            <div class="cart-item-number">#<?= $index ?></div>
                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button"
                                                        class="btn btn-sm btn-danger remove-cart-item-ajax"
                                                        data-cart-id="<?= $item['cart_id'] ?>"
                                                        data-product-id="<?= $item['product_id'] ?>"
                                                        data-variant-id="<?= $item['variant_id'] ?? '' ?>"
                                                        style="padding: 4px 8px; font-size: 12px;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <input type="checkbox"
                                                       class="cart-item-checkbox"
                                                       id="item_<?= $item['cart_id'] ?>"
                                                       value="<?= $item['cart_id'] ?>">
                                            </div>
                                        </div>

                                        <div class="cart-item-mobile-content">
                                            <div class="cart-item-mobile-left">
                                                <img src="<?= getProductImage($item['image'], $item['name'], 214, 100) ?>"
                                                     alt="<?= e($item['name']) ?>"
                                                     class="cart-item-mobile-image">
                                            </div>

                                            <div class="cart-item-mobile-right">
                                                <div class="cart-item-mobile-name">
                                                    <a href="<?= e($item['product_url']) ?>" class="text-decoration-none text-dark">
                                                        <?= e($item['name']) ?>
                                                    </a>
                                                    <?php if ($item['stock_quantity'] <= 0): ?>
                                                        <span class="badge bg-danger ms-1">Hết hàng</span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="cart-item-mobile-price">
                                                    <?= formatMoney($item['price']) ?>
                                                </div>

                                                <div class="cart-item-mobile-quantity">
                                                    <span class="qty-label">SL:</span>
                                                    <input type="number"
                                                           name="quantities[<?= $item['cart_id'] ?>]"
                                                           value="<?= $item['quantity'] ?>"
                                                           min="1"
                                                           max="5"
                                                           class="form-control quantity-input"
                                                           data-cart-id="<?= $item['cart_id'] ?>"
                                                           data-price="<?= $item['price'] ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cart-item-mobile-footer">
                                            <span class="subtotal-label">Tổng tiền:</span>
                                            <span class="subtotal-amount item-subtotal" data-cart-id="<?= $item['cart_id'] ?>"><?= formatMoney($item['subtotal']) ?></span>
                                        </div>
                                    </div>
                                <?php $index++; endforeach; ?>

                                <!-- Mobile: Select & Delete Controls -->
                                <div class="mobile-select-controls d-md-none">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllMobile">
                                        <i class="fas fa-check-square"></i> Chọn tất cả
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" id="deleteSelectedMobile">
                                        <i class="fas fa-trash"></i> Xóa đã chọn
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Đơn hàng của bạn -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-shopping-cart"></i> Đơn hàng của bạn</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th class="text-end">Tổng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <tr>
                                            <td>
                                                <?= e($item['name']) ?>
                                                <strong>× <?= $item['quantity'] ?></strong>
                                            </td>
                                            <td class="text-end"><?= formatMoney($item['subtotal']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Tạm tính:</th>
                                        <td class="text-end checkout-subtotal"><?= formatMoney($total) ?></td>
                                    </tr>
                                    <tr class="table-active">
                                        <th>Tổng cộng:</th>
                                        <th class="text-end text-danger fs-5 checkout-total"><?= formatMoney($total) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Mã giảm giá - Collapsible -->
                        <div class="mt-3 pt-3 border-top">
                            <!-- Link để hiện form nhập mã giảm giá -->
                            <a href="#" id="show-coupon-form" class="text-decoration-none">
                                <i class="fas fa-tag"></i> Bạn có mã giảm giá? Nhập vào đây
                            </a>

                            <!-- Form nhập mã giảm giá (ẩn mặc định) -->
                            <div id="coupon-form" class="mt-2" style="display: none;">
                                <form method="POST" id="apply-coupon-form">
                                    <div class="input-group">
                                        <input type="text" name="coupon_code" id="coupon-code-input" class="form-control form-control-sm"
                                               placeholder="Nhập mã giảm giá" value="" required>
                                        <button type="submit" name="apply_coupon" class="btn btn-primary btn-sm">
                                            <i class="fas fa-check"></i> Áp dụng
                                        </button>
                                    </div>
                                    <div id="coupon-message" class="mt-2"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nút thanh toán -->
                <a href="/checkout" class="pill-button pill-button-blue pill-button-lg w-100 mb-3">
                    <i class="fas fa-arrow-right"></i> Tiến hành thanh toán
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function removeCartItem(cartId) {
    if (!confirm('Xóa sản phẩm này khỏi giỏ hàng?')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="cart_id" value="${cartId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Mobile: Select All functionality
document.addEventListener('DOMContentLoaded', function() {
    // Realtime cart total update
    const quantityInputs = document.querySelectorAll('.quantity-input');
    let updateTimeout = null;
    let isUpdating = false; // Flag to prevent infinite loops

    function formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
    }

    function updateCartTotals(changedInput) {
        if (isUpdating) return; // Prevent recursive calls
        isUpdating = true;

        let total = 0;
        const processedCarts = new Set(); // Track processed cart IDs

        // Process each input and update UI
        quantityInputs.forEach(input => {
            const cartId = input.dataset.cartId;

            // Skip if already processed this cart ID
            if (processedCarts.has(cartId)) return;
            processedCarts.add(cartId);

            const price = parseFloat(input.dataset.price);
            const quantity = parseInt(input.value) || 1;
            const subtotal = price * quantity;

            // Sync other inputs with same cart_id (but not the one being changed)
            const cartInputs = document.querySelectorAll(`.quantity-input[data-cart-id="${cartId}"]`);
            cartInputs.forEach(inp => {
                // Only sync if it's not the input user is currently changing
                if (inp !== changedInput && parseInt(inp.value) !== quantity) {
                    inp.value = quantity;
                }
            });

            // Cập nhật subtotal cho item này
            const subtotalElements = document.querySelectorAll(`.item-subtotal[data-cart-id="${cartId}"]`);
            subtotalElements.forEach(el => {
                el.textContent = formatMoney(subtotal);
            });

            // Cập nhật số lượng trong cart summary
            const qtyElements = document.querySelectorAll(`.cart-summary-qty[data-cart-id="${cartId}"] .qty-value`);
            qtyElements.forEach(el => {
                el.textContent = quantity;
            });

            total += subtotal;
        });

        // Cập nhật tổng tiền
        const totalTempElements = document.querySelectorAll('.cart-total-temp');
        const totalFinalElements = document.querySelectorAll('.cart-total-final');

        totalTempElements.forEach(el => el.textContent = formatMoney(total));
        totalFinalElements.forEach(el => el.textContent = formatMoney(total));

        isUpdating = false;
    }

    // Hàm cập nhật database qua AJAX
    function updateCartDatabase(cartId, quantity) {
        const formData = new FormData();
        formData.append('action', 'update_quantity');
        formData.append('cart_id', cartId);
        formData.append('quantity', quantity);

        fetch('/cart', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Update failed:', data.error);
            }
        })
        .catch(error => {
            console.error('Update error:', error);
        });
    }

    // Lắng nghe sự kiện thay đổi số lượng
    quantityInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            // Cập nhật UI ngay lập tức
            setTimeout(() => updateCartTotals(this), 10);
        });

        input.addEventListener('change', function(e) {
            const cartId = this.dataset.cartId;
            const quantity = parseInt(this.value) || 1;

            // Cập nhật UI
            updateCartTotals(this);

            // Cập nhật database sau 500ms (debounce)
            clearTimeout(updateTimeout);
            updateTimeout = setTimeout(() => {
                updateCartDatabase(cartId, quantity);
            }, 500);
        });
    });

    const selectAllBtn = document.getElementById('selectAllMobile');
    const deleteSelectedBtn = document.getElementById('deleteSelectedMobile');
    const checkboxes = document.querySelectorAll('.cart-item-checkbox');

    if (selectAllBtn) {
        let allSelected = false;
        selectAllBtn.addEventListener('click', function() {
            allSelected = !allSelected;
            checkboxes.forEach(cb => cb.checked = allSelected);

            if (allSelected) {
                selectAllBtn.innerHTML = '<i class="fas fa-square"></i> Bỏ chọn tất cả';
            } else {
                selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Chọn tất cả';
            }
        });
    }

    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function() {
            const selected = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            if (selected.length === 0) {
                alert('Vui lòng chọn ít nhất một sản phẩm để xóa');
                return;
            }

            if (!confirm(`Xóa ${selected.length} sản phẩm đã chọn khỏi giỏ hàng?`)) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="remove_multiple">
                <input type="hidden" name="cart_ids" value="${selected.join(',')}">
            `;
            document.body.appendChild(form);
            form.submit();
        });
    }

    // AJAX xóa sản phẩm khỏi giỏ hàng
    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-cart-item-ajax');
        if (!removeBtn) return;

        e.preventDefault();

        if (!confirm('Xóa sản phẩm này khỏi giỏ hàng?')) {
            return;
        }

        const cartId = removeBtn.dataset.cartId;
        const productId = removeBtn.dataset.productId;
        const variantId = removeBtn.dataset.variantId;

        // Disable button while processing
        removeBtn.disabled = true;
        removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        // Gửi AJAX request
        const formData = new FormData();
        formData.append('product_id', productId);
        if (variantId) {
            formData.append('variant_id', variantId);
        }

        fetch('/api/remove-from-cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Xóa tất cả elements có cùng cart_id (table row, mobile item, summary item)
                const itemsToRemove = document.querySelectorAll(`[data-cart-item-id="${cartId}"]`);
                itemsToRemove.forEach(item => {
                    item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-20px)';
                    setTimeout(() => item.remove(), 300);
                });

                // Cập nhật cart badge và dropdown trong header
                if (typeof updateCartCount === 'function') {
                    updateCartCount(data.cart_count);
                }
                if (typeof refreshCartDropdown === 'function') {
                    refreshCartDropdown();
                }

                // Tính lại tổng tiền sau khi xóa
                setTimeout(() => {
                    const remainingInputs = document.querySelectorAll('.quantity-input');
                    let newTotal = 0;
                    const processedCarts = new Set();

                    remainingInputs.forEach(input => {
                        const cartId = input.dataset.cartId;
                        if (processedCarts.has(cartId)) return;
                        processedCarts.add(cartId);

                        const price = parseFloat(input.dataset.price);
                        const quantity = parseInt(input.value) || 1;
                        newTotal += price * quantity;
                    });

                    // Cập nhật tổng tiền
                    document.querySelectorAll('.cart-total-temp').forEach(el => {
                        el.textContent = formatMoney(newTotal);
                    });
                    document.querySelectorAll('.cart-total-final').forEach(el => {
                        el.textContent = formatMoney(newTotal);
                    });

                    // Nếu giỏ hàng trống, reload trang để hiển thị thông báo giỏ hàng trống
                    if (remainingInputs.length === 0) {
                        location.reload();
                    }
                }, 350);

                // Show success toast
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Đã xóa sản phẩm khỏi giỏ hàng', 'success');
                }
            } else {
                // Re-enable button if failed
                removeBtn.disabled = false;
                removeBtn.innerHTML = '<i class="fas fa-trash"></i>';

                if (typeof showToast === 'function') {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            removeBtn.disabled = false;
            removeBtn.innerHTML = '<i class="fas fa-trash"></i>';

            if (typeof showToast === 'function') {
                showToast('Có lỗi xảy ra khi xóa sản phẩm', 'error');
            } else {
                alert('Có lỗi xảy ra khi xóa sản phẩm');
            }
        });
    });
});

// Toggle coupon form
const showCouponLink = document.getElementById('show-coupon-form');
if (showCouponLink) {
    showCouponLink.addEventListener('click', function(e) {
        e.preventDefault();
        this.style.display = 'none';
        document.getElementById('coupon-form').style.display = 'block';
        document.querySelector('#coupon-form input[name="coupon_code"]').focus();
    });
}

// AJAX Apply Coupon
const applyCouponForm = document.getElementById('apply-coupon-form');
if (applyCouponForm) {
    applyCouponForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const couponCode = document.getElementById('coupon-code-input').value.trim();
        const submitBtn = this.querySelector('button[type="submit"]');
        const messageDiv = document.getElementById('coupon-message');
        const originalBtnHTML = submitBtn.innerHTML;

        if (!couponCode) {
            messageDiv.innerHTML = '<div class="alert alert-danger alert-sm mb-0"><i class="fas fa-exclamation-circle"></i> Vui lòng nhập mã giảm giá</div>';
            return;
        }

        // Disable button and show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        messageDiv.innerHTML = '';

        const formData = new FormData();
        formData.append('action', 'apply');
        formData.append('coupon_code', couponCode);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        fetch('/api/apply-coupon.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                messageDiv.innerHTML = '<div class="alert alert-success alert-sm mb-0"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';

                // Show success toast
                if (typeof showToast === 'function') {
                    showToast(data.message, 'success');
                }

                // Redirect to checkout with coupon applied
                setTimeout(() => {
                    window.location.href = '/checkout';
                }, 1000);
            } else {
                messageDiv.innerHTML = '<div class="alert alert-danger alert-sm mb-0"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHTML;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.innerHTML = '<div class="alert alert-danger alert-sm mb-0"><i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra. Vui lòng thử lại.</div>';
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHTML;
        });
    });
}
</script>

<style>
/* Card styling */
.card {
    border: 1px solid #d2d2d7;
    border-radius: 18px;
    box-shadow: none;
    transition: none !important;
}

.card:hover {
    transform: none !important;
    box-shadow: none !important;
}

.card-header {
    background: #f5f5f7 !important;
    border-bottom: 1px solid #d2d2d7;
    border-radius: 18px 18px 0 0 !important;
    color: #1d1d1f !important;
    padding: 16px 20px;
}

.card-header h5,
.card-header h6 {
    font-weight: 600;
    font-size: 17px;
    letter-spacing: -0.02em;
}

/* Coupon form styling */
#coupon-form .input-group {
    display: flex;
    width: 100%;
}

#coupon-form .form-control-sm {
    border: 1px solid #d2d2d7;
    border-right: none;
    border-radius: 8px 0 0 8px;
    padding: 8px 12px;
    font-size: 14px;
    background: white;
    color: #1d1d1f;
    flex: 1;
}

#coupon-form .form-control-sm:focus {
    border-color: #0071e3;
    box-shadow: none;
    outline: none;
    z-index: 2;
}

#coupon-form .btn-primary {
    background: #0071e3;
    border: 1px solid #0071e3;
    border-radius: 0 8px 8px 0;
    color: white;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
    white-space: nowrap;
}

#coupon-form .btn-primary:hover {
    background: #0077ed;
    border-color: #0077ed;
}

#coupon-form .btn-primary:active,
#coupon-form .btn-primary:focus {
    background: #006edb;
    border-color: #006edb;
    box-shadow: none;
    outline: none;
}

#show-coupon-form {
    color: #0071e3;
    font-size: 14px;
}

#show-coupon-form:hover {
    color: #0077ed;
}

/* Alert messages */
.alert-sm {
    padding: 8px 12px;
    font-size: 13px;
    border-radius: 6px;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-danger {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

/* Product name links */
.cart-item-mobile-name a,
.cart-summary-name a,
h6.mb-0 a {
    transition: color 0.2s ease;
}

.cart-item-mobile-name a:hover,
.cart-summary-name a:hover,
h6.mb-0 a:hover {
    color: #0d6efd !important;
}

/* Mobile Cart Styles */
@media (max-width: 991px) {
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }

    h1 {
        font-size: 1.4rem;
        margin-bottom: 1rem !important;
        font-weight: 600;
        color: #212529;
    }

    /* Hide desktop table on mobile */
    .table-responsive {
        display: none;
    }

    /* Mobile cart items */
    .cart-item-mobile {
        display: block;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 0;
        margin-bottom: 12px;
        background: white;
        position: relative;
        overflow: hidden;
    }

    .cart-item-mobile-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 12px;
        background: #f8f9fa;
        border-bottom: 1px solid #ddd;
    }

    .cart-item-number {
        font-size: 14px;
        font-weight: 600;
        color: #495057;
    }

    .cart-item-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    .cart-item-mobile-content {
        display: flex;
        gap: 12px;
        padding: 12px;
    }

    .cart-item-mobile-left {
        flex-shrink: 0;
        width: 110px;
    }

    .cart-item-mobile-image {
        width: 100%;
        height: auto;
        aspect-ratio: 1070 / 500;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #ddd;
    }

    .cart-item-mobile-right {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }

    .cart-item-mobile-name {
        font-size: 14px;
        font-weight: 500;
        line-height: 1.4;
        color: #333;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .cart-item-mobile-name .badge {
        font-size: 10px;
        padding: 3px 8px;
        font-weight: 600;
    }

    .cart-item-mobile-price {
        font-size: 16px;
        font-weight: 600;
        color: #212529;
    }

    .cart-item-mobile-quantity {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .cart-item-mobile-quantity .qty-label {
        font-size: 13px;
        font-weight: 500;
        color: #6c757d;
    }

    .cart-item-mobile-quantity input {
        width: 65px;
        font-size: 14px;
        font-weight: 500;
        padding: 6px 8px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        text-align: center;
        -moz-appearance: textfield;
    }

    .cart-item-mobile-quantity input::-webkit-outer-spin-button,
    .cart-item-mobile-quantity input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .cart-item-mobile-quantity input:focus {
        border-color: #86b7fe;
        outline: none;
    }

    .cart-item-mobile-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        background: #f8f9fa;
        border-top: 1px solid #ddd;
    }

    .cart-item-mobile-footer .subtotal-label {
        font-size: 14px;
        font-weight: 500;
        color: #495057;
    }

    .cart-item-mobile-footer .subtotal-amount {
        font-size: 18px;
        font-weight: 700;
        color: #dc3545;
    }

    /* Mobile select controls */
    .mobile-select-controls {
        display: flex;
        gap: 8px;
        padding: 12px;
        background: white;
        border-radius: 8px;
        margin-top: 16px;
        position: sticky;
        bottom: 10px;
        z-index: 10;
        border: 1px solid #ddd;
    }

    .mobile-select-controls .btn {
        flex: 1;
        font-size: 14px;
        padding: 10px;
        font-weight: 500;
        border-radius: 4px;
    }

    /* Card adjustments */
    .card {
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    .card-body {
        padding: 14px;
    }

    /* Summary card */
    .col-lg-4 {
        margin-top: 0;
    }

    .card-header {
        padding: 12px 14px;
    }

    .card-header h5 {
        font-size: 1.1rem;
    }

    /* Summary text */
    .d-flex.justify-content-between.mb-2,
    .d-flex.justify-content-between.mb-3 {
        font-size: 14px;
    }

    .d-flex.justify-content-between.mb-3 h5 {
        font-size: 1.2rem;
    }

    /* Buttons */
    .btn {
        font-size: 14px;
        padding: 10px 14px;
        font-weight: 500;
    }

    .btn-sm {
        font-size: 13px;
        padding: 8px 12px;
    }

    .btn-success {
        font-size: 15px;
        padding: 12px;
        font-weight: 600;
    }

    /* Empty cart alert */
    .alert-info {
        padding: 2rem 1rem !important;
    }

    .alert-info h4 {
        font-size: 1.2rem;
    }

    .alert-info p {
        font-size: 14px;
    }

    .alert-info i.fa-3x {
        font-size: 2rem !important;
    }

    /* Discount code section */
    .input-group input {
        font-size: 14px;
    }

    .input-group .btn {
        font-size: 13px;
        padding: 6px 12px;
    }

    /* Cart summary items on mobile */
    .cart-summary-item {
        display: flex;
        gap: 8px;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .cart-summary-item:last-child {
        border-bottom: none;
    }

    .cart-summary-thumb {
        width: 70px;
        height: auto;
        aspect-ratio: 1070 / 500;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ddd;
        flex-shrink: 0;
    }

    .cart-summary-info {
        flex: 1;
        min-width: 0;
    }

    .cart-summary-name {
        font-size: 13px;
        font-weight: 500;
        color: #333;
        line-height: 1.3;
        margin-bottom: 3px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .cart-summary-qty {
        font-size: 12px;
        color: #6c757d;
    }

    .cart-summary-price {
        font-size: 14px;
        font-weight: 600;
        color: #212529;
        flex-shrink: 0;
    }
}

@media (min-width: 992px) {
    .cart-items-mobile {
        display: none;
    }

    /* Cart summary items on desktop */
    .cart-summary-item {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .cart-summary-item:last-child {
        border-bottom: none;
    }

    .cart-summary-thumb {
        width: 80px;
        height: auto;
        aspect-ratio: 1070 / 500;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e0e0e0;
        flex-shrink: 0;
    }

    .cart-summary-info {
        flex: 1;
        min-width: 0;
    }

    .cart-summary-name {
        font-size: 14px;
        font-weight: 500;
        color: #333;
        line-height: 1.4;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .cart-summary-qty {
        font-size: 13px;
        color: #6c757d;
    }

    .cart-summary-price {
        font-size: 15px;
        font-weight: 600;
        color: #212529;
        flex-shrink: 0;
    }
}

/* Desktop: hide mobile cart items */
.cart-items-mobile {
    display: none;
}

/* Mobile: show mobile cart items */
@media (max-width: 991px) {
    .cart-items-mobile {
        display: block;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
