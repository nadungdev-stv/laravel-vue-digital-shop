<?php
// Security Functions - Minimal Version

// Get bank settings from database
function getBankSettings() {
    try {
        return array(
            'bank_name' => getSetting('bank_name', 'MB Bank'),
            'bank_account' => getSetting('bank_account_number', '567892868'),
            'bank_account_name' => getSetting('bank_account_name', 'Ngo Anh Dung'),
            'bank_code' => getSetting('bank_code', 'MB')
        );
    } catch (Exception $e) {
        error_log("getBankSettings() error: " . $e->getMessage());
        return array(
            'bank_name' => 'MB Bank',
            'bank_account' => '567892868',
            'bank_account_name' => 'Ngo Anh Dung',
            'bank_code' => 'MB'
        );
    }
}

// Sanitize order code (alphanumeric only)
function sanitizeOrderCode($orderCode) {
    return preg_replace('/[^A-Za-z0-9]/', '', $orderCode);
}
