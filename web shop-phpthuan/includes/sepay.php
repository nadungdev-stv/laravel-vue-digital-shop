<?php
/**
 * SePay Integration Helper
 * Tạo QR code và quản lý thanh toán qua SePay
 */

class SePay {
    private $apiKey;
    private $accountNumber;
    private $bankCode;
    private $accountName;
    private $enabled;

    public function __construct() {
        $this->enabled = getSetting('sepay_enabled') == '1';
        $this->apiKey = getSetting('sepay_api_key', '');
        $this->accountNumber = getSetting('sepay_account_number', '');
        $this->bankCode = getSetting('sepay_bank_code', 'MB');
        $this->accountName = getSetting('sepay_account_name', '');
    }

    /**
     * Kiểm tra SePay có được bật và cấu hình đầy đủ không
     */
    public function isEnabled() {
        return $this->enabled && !empty($this->accountNumber) && !empty($this->bankCode);
    }

    /**
     * Tạo URL QR code thanh toán
     *
     * @param int $amount Số tiền
     * @param string $orderCode Mã đơn hàng
     * @param string $description Mô tả (tùy chọn)
     * @return string URL ảnh QR
     */
    public function generateQRUrl($amount, $orderCode, $description = '') {
        if (!$this->isEnabled()) {
            return $this->generateVietQRUrl($amount, $orderCode, $description);
        }

        // Nội dung chuyển khoản: DH [MÃ ĐƠN]
        $content = "DH {$orderCode}";
        if ($description) {
            $content .= " {$description}";
        }

        // Encode content for URL
        $encodedContent = urlencode($content);
        $encodedName = urlencode($this->accountName);

        // SePay QR URL format
        // https://qr.sepay.vn/img?acc={ACC}&bank={BANK}&amount={AMOUNT}&des={DES}&template=compact
        $url = "https://qr.sepay.vn/img";
        $url .= "?acc=" . $this->accountNumber;
        $url .= "&bank=" . $this->bankCode;
        $url .= "&amount=" . (int)$amount;
        $url .= "&des=" . $encodedContent;
        $url .= "&template=compact";
        $url .= "&accountName=" . $encodedName;

        return $url;
    }

    /**
     * Fallback: Tạo URL QR code qua VietQR (không có SePay)
     */
    private function generateVietQRUrl($amount, $orderCode, $description = '') {
        $bankCode = $this->bankCode ?: getSetting('bank_code', 'MB');
        $accountNo = $this->accountNumber ?: getSetting('bank_account_number', '');
        $accountName = $this->accountName ?: getSetting('bank_account_name', '');

        $content = "DH {$orderCode}";
        if ($description) {
            $content .= " {$description}";
        }

        $url = "https://img.vietqr.io/image/{$bankCode}-{$accountNo}-compact.png";
        $url .= "?amount=" . (int)$amount;
        $url .= "&addInfo=" . urlencode($content);
        $url .= "&accountName=" . urlencode($accountName);

        return $url;
    }

    /**
     * Lấy thông tin ngân hàng
     */
    public function getBankInfo() {
        return [
            'bank_code' => $this->bankCode,
            'bank_name' => $this->getBankName($this->bankCode),
            'account_number' => $this->accountNumber,
            'account_name' => $this->accountName,
        ];
    }

    /**
     * Lấy tên ngân hàng từ mã
     */
    public static function getBankName($code) {
        $banks = [
            'MB' => 'MB Bank',
            'VCB' => 'Vietcombank',
            'TCB' => 'Techcombank',
            'ACB' => 'ACB',
            'VPB' => 'VPBank',
            'TPB' => 'TPBank',
            'BIDV' => 'BIDV',
            'VTB' => 'Vietinbank',
            'MSB' => 'MSB',
            'SHB' => 'SHB',
            'STB' => 'Sacombank',
            'EIB' => 'Eximbank',
            'HDB' => 'HDBank',
            'OCB' => 'OCB',
            'LPB' => 'LienVietPostBank',
        ];

        return $banks[$code] ?? $code;
    }
}

/**
 * Helper function để tạo QR nhanh
 */
function getSepayQRUrl($amount, $orderCode, $description = '') {
    $sepay = new SePay();
    return $sepay->generateQRUrl($amount, $orderCode, $description);
}

/**
 * Kiểm tra SePay có sẵn không
 */
function isSepayEnabled() {
    $sepay = new SePay();
    return $sepay->isEnabled();
}

/**
 * Lấy thông tin ngân hàng nhận tiền
 */
function getSepayBankInfo() {
    $sepay = new SePay();
    return $sepay->getBankInfo();
}
