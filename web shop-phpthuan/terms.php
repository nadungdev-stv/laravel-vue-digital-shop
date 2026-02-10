<?php
$pageTitle = 'Điều khoản sử dụng';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1 class="mb-4"><i class="fas fa-file-contract"></i> Điều khoản sử dụng</h1>

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">
                        <small>Cập nhật lần cuối: <?= date('d/m/Y') ?></small>
                    </p>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Khi sử dụng dịch vụ của chúng tôi, bạn đồng ý tuân thủ các điều khoản dưới đây.
                    </div>

                    <h4 class="mt-4 mb-3">1. Điều khoản chung</h4>
                    <p>
                        Bằng việc truy cập và sử dụng website <?= e(getSetting('site_name')) ?>,
                        bạn đồng ý tuân thủ và bị ràng buộc bởi các điều khoản và điều kiện sử dụng sau đây.
                    </p>
                    <ul>
                        <li>Bạn phải đủ 18 tuổi hoặc có sự đồng ý của người giám hộ</li>
                        <li>Thông tin bạn cung cấp phải chính xác và đầy đủ</li>
                        <li>Bạn chịu trách nhiệm về mật khẩu và hoạt động trên tài khoản của mình</li>
                        <li>Không được sử dụng dịch vụ cho mục đích bất hợp pháp</li>
                    </ul>

                    <h4 class="mt-4 mb-3">2. Sản phẩm và dịch vụ</h4>
                    <p>
                        Chúng tôi cung cấp các tài khoản Premium cho các dịch vụ của bên thứ ba:
                    </p>
                    <ul>
                        <li>Tài khoản được cung cấp là tài khoản hợp lệ, đang hoạt động</li>
                        <li>Thời hạn sử dụng được ghi rõ trong mô tả sản phẩm</li>
                        <li>Chúng tôi không chịu trách nhiệm về chính sách của các dịch vụ bên thứ ba</li>
                        <li>Khách hàng không được thay đổi thông tin tài khoản đã mua</li>
                    </ul>

                    <h4 class="mt-4 mb-3">3. Giá cả và thanh toán</h4>
                    <ul>
                        <li>Giá sản phẩm được hiển thị bằng VNĐ, đã bao gồm thuế</li>
                        <li>Chúng tôi chấp nhận các phương thức thanh toán được liệt kê trên website</li>
                        <li>Đơn hàng chỉ được xử lý sau khi thanh toán được xác nhận</li>
                        <li>Chúng tôi có quyền thay đổi giá mà không cần thông báo trước</li>
                    </ul>

                    <h4 class="mt-4 mb-3">4. Giao hàng</h4>
                    <ul>
                        <li>Tài khoản sẽ được giao tự động qua email hoặc hiển thị trên website</li>
                        <li>Thời gian giao hàng thường ngay lập tức sau khi thanh toán</li>
                        <li>Với dịch vụ cần xử lý thủ công, thời gian tối đa là 24 giờ</li>
                        <li>Khách hàng có trách nhiệm kiểm tra email và website để nhận tài khoản</li>
                    </ul>

                    <h4 class="mt-4 mb-3">5. Chính sách bảo hành</h4>
                    <p>
                        Chúng tôi cam kết bảo hành cho tất cả sản phẩm:
                    </p>
                    <ul>
                        <li>Bảo hành trong suốt thời gian sử dụng đã ghi trong sản phẩm</li>
                        <li>Đổi tài khoản mới nếu tài khoản bị lỗi không thể sử dụng</li>
                        <li>Không bảo hành nếu khách hàng tự ý thay đổi thông tin tài khoản</li>
                        <li>Yêu cầu bảo hành phải được gửi trong vòng 24h kể từ khi phát hiện lỗi</li>
                    </ul>

                    <h4 class="mt-4 mb-3">6. Chính sách hoàn tiền</h4>
                    <ul>
                        <li>Hoàn tiền 100% nếu không thể giao hàng trong 24h</li>
                        <li>Hoàn tiền nếu tài khoản bị lỗi và không thể thay thế</li>
                        <li>Không hoàn tiền nếu khách hàng đã sử dụng tài khoản</li>
                        <li>Thời gian xử lý hoàn tiền: 3-7 ngày làm việc</li>
                    </ul>

                    <h4 class="mt-4 mb-3">7. Quyền sở hữu trí tuệ</h4>
                    <p>
                        Tất cả nội dung trên website bao gồm văn bản, hình ảnh, logo, và mã nguồn
                        đều thuộc quyền sở hữu của chúng tôi. Nghiêm cấm sao chép hoặc sử dụng
                        mà không có sự cho phép.
                    </p>

                    <h4 class="mt-4 mb-3">8. Giới hạn trách nhiệm</h4>
                    <ul>
                        <li>Chúng tôi không chịu trách nhiệm về thiệt hại gián tiếp phát sinh</li>
                        <li>Không chịu trách nhiệm về sự gián đoạn hoặc lỗi kỹ thuật của website</li>
                        <li>Không chịu trách nhiệm về hành vi của bên thứ ba</li>
                        <li>Trách nhiệm của chúng tôi giới hạn ở giá trị đơn hàng</li>
                    </ul>

                    <h4 class="mt-4 mb-3">9. Bảo mật thông tin</h4>
                    <p>
                        Chúng tôi cam kết bảo mật thông tin cá nhân của khách hàng:
                    </p>
                    <ul>
                        <li>Thông tin được mã hóa và lưu trữ an toàn</li>
                        <li>Không chia sẻ thông tin cho bên thứ ba mà không có sự đồng ý</li>
                        <li>Chỉ sử dụng thông tin cho mục đích giao dịch và hỗ trợ</li>
                        <li>Khách hàng có quyền yêu cầu xóa thông tin cá nhân</li>
                    </ul>

                    <h4 class="mt-4 mb-3">10. Hành vi cấm</h4>
                    <p>Người dùng không được:</p>
                    <ul>
                        <li>Sử dụng dịch vụ cho mục đích bất hợp pháp</li>
                        <li>Cố gắng truy cập trái phép vào hệ thống</li>
                        <li>Spam, gửi thư rác hoặc quấy rối người khác</li>
                        <li>Đăng tải nội dung vi phạm pháp luật hoặc đạo đức</li>
                        <li>Mạo danh người khác hoặc tổ chức</li>
                    </ul>

                    <h4 class="mt-4 mb-3">11. Thay đổi điều khoản</h4>
                    <p>
                        Chúng tôi có quyền thay đổi các điều khoản này bất kỳ lúc nào.
                        Việc tiếp tục sử dụng dịch vụ sau khi có thay đổi đồng nghĩa với
                        việc bạn chấp nhận các điều khoản mới.
                    </p>

                    <h4 class="mt-4 mb-3">12. Luật áp dụng</h4>
                    <p>
                        Các điều khoản này được điều chỉnh bởi pháp luật Việt Nam.
                        Mọi tranh chấp phát sinh sẽ được giải quyết tại tòa án có thẩm quyền.
                    </p>

                    <hr class="my-4">

                    <h4 class="mb-3"><i class="fas fa-question-circle"></i> Liên hệ</h4>
                    <p>
                        Nếu bạn có bất kỳ câu hỏi nào về các điều khoản này, vui lòng liên hệ:
                    </p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope text-primary me-2"></i> Email: <?= e(getSetting('contact_email', 'support@example.com')) ?></li>
                        <li><i class="fas fa-phone text-primary me-2"></i> Hotline: <?= e(getSetting('contact_phone', '0848877758')) ?></li>
                    </ul>

                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Lưu ý:</strong> Vui lòng đọc kỹ các điều khoản trước khi sử dụng dịch vụ.
                        Việc đặt hàng đồng nghĩa với việc bạn đã đọc và đồng ý với tất cả các điều khoản trên.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">Liên kết nhanh</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <a href="/about.php" class="text-decoration-none">
                                <i class="fas fa-angle-right text-primary"></i> Giới thiệu
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="/contact.php" class="text-decoration-none">
                                <i class="fas fa-angle-right text-primary"></i> Liên hệ
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="/terms.php" class="text-decoration-none">
                                <i class="fas fa-angle-right text-primary"></i> Điều khoản
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-body bg-light">
                    <h6 class="mb-2">Cần hỗ trợ?</h6>
                    <p class="small mb-2">Liên hệ với chúng tôi để được giải đáp</p>
                    <a href="/contact.php" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-headset"></i> Liên hệ ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
