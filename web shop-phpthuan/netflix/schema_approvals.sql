-- Netflix Auto-Approval System
-- Tạo bảng pending_approvals để lưu các yêu cầu xác nhận

CREATE TABLE IF NOT EXISTS pending_approvals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  link VARCHAR(1000) NOT NULL COMMENT 'Netflix approval URL',
  email_from VARCHAR(255) COMMENT 'Email sender',
  email_subject VARCHAR(500) COMMENT 'Email subject',
  status ENUM('pending', 'approved', 'failed', 'expired') DEFAULT 'pending' COMMENT 'Approval status',
  attempts INT DEFAULT 0 COMMENT 'Number of approve attempts',
  error_message TEXT NULL COMMENT 'Error message if failed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Request creation time',
  approved_at TIMESTAMP NULL COMMENT 'Approval completion time',
  
  INDEX idx_status_created (status, created_at),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Netflix household approval queue';
