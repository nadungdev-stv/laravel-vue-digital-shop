<?php
/**
 * Email Forward Search - Gmail IMAP Handler
 */

class GmailHandler {
    private $config;
    private $connection;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Kết nối đến Gmail IMAP
     */
    public function connect() {
        $mailbox = sprintf(
            '{%s:%d/imap/ssl}INBOX',
            $this->config['gmail']['imap_host'],
            $this->config['gmail']['imap_port']
        );
        
        $this->connection = @imap_open(
            $mailbox,
            $this->config['gmail']['email'],
            $this->config['gmail']['password']
        );
        
        if (!$this->connection) {
            throw new Exception('Không thể kết nối Gmail: ' . imap_last_error());
        }
        
        return true;
    }
    
    /**
     * Đóng kết nối
     */
    public function disconnect() {
        if ($this->connection) {
            imap_close($this->connection);
        }
    }
    
    /**
     * Tìm kiếm email theo địa chỉ nhận gốc
     */
    public function searchEmails($targetEmail, $limit = 20) {
        if (!$this->connection) {
            $this->connect();
        }
        
        $results = [];
        $targetEmail = strtolower(trim($targetEmail));
        
        // Lấy setting số giờ tìm kiếm (mặc định 3 giờ)
        $hoursLimit = $this->config['search']['max_days_ago'] ?? 3; // Tái sử dụng biến max_days_ago để lưu số giờ
        
        // Tính số ngày cần tìm cho IMAP (IMAP chỉ hỗ trợ tìm theo ngày)
        $daysNeeded = ceil($hoursLimit / 24);
        // Giới hạn tối đa 7 ngày cho IMAP search để đảm bảo tốc độ
        $daysAgo = min($daysNeeded, 7);
        
        $sinceDate = date('d-M-Y', strtotime("-{$daysAgo} days"));
        
        // Tạo search criteria
        $allowedSenders = $this->config['search']['allowed_senders'] ?? [];
        
        // Thêm chính email cần tìm vào danh sách sender (để hỗ trợ Manual Forward)
        if (!in_array($targetEmail, $allowedSenders)) {
            $allowedSenders[] = $targetEmail;
        }
        
        $allEmails = [];
        $processedEmails = [];
        
        // Tìm email từ những người gửi được phép
        foreach ($allowedSenders as $sender) {
            // Tối ưu: Thêm TO filter để thu hẹp kết quả
            // Lưu ý: Nếu manual forward, TO header sẽ là email hệ thống, không phải targetEmail
            // Nên nếu sender == targetEmail, ta không dùng TO filter
            
            if ($sender === $targetEmail) {
                // Trường hợp forward thủ công: Tìm email TỪ user gửi đến
                $criteria = sprintf('FROM "%s" SINCE "%s"', $sender, $sinceDate);
            } else {
                // Trường hợp auto forward: Tìm email TỪ Netflix GỬI ĐẾN user
                $criteria = sprintf('FROM "%s" TO "%s" SINCE "%s"', $sender, $targetEmail, $sinceDate);
            }
            
            $emails = @imap_search($this->connection, $criteria);
            
            // Nếu không tìm thấy và không phải manual forward, thử tìm rộng hơn
            if (!$emails && $sender !== $targetEmail) {
                $criteria = sprintf('FROM "%s" SINCE "%s"', $sender, $sinceDate);
                $emails = @imap_search($this->connection, $criteria);
            }
            
            if ($emails) {
                // Sắp xếp mới nhất trước
                rsort($emails);
                
                // Tối ưu: Chỉ xử lý tối đa 20 email gần nhất
                $emails = array_slice($emails, 0, 20);
                
                foreach ($emails as $emailNum) {
                    if (!in_array($emailNum, $processedEmails)) {
                        $allEmails[] = $emailNum;
                        $processedEmails[] = $emailNum;
                    }
                }
            }
        }

        // --- FALLBACK SEARCH STRATEGY ---
        // Nếu không tìm thấy email nào, thử tìm rộng hơn bằng SUBJECT hoặc TEXT
        if (empty($allEmails)) {
            // Chiến lược 1: Tìm tất cả email có Subject chứa "Netflix" và Body chứa target email
            // Dành cho trường hợp forward mà sender không nằm trong allowed list
            $fallbackCriteria = sprintf('SUBJECT "Netflix" TEXT "%s" SINCE "%s"', $targetEmail, $sinceDate);
            error_log("GmailHandler: Using Fallback Search 1 - $fallbackCriteria");
            
            $fallbackEmails = @imap_search($this->connection, $fallbackCriteria);
            if ($fallbackEmails) {
                foreach ($fallbackEmails as $emailNum) {
                    if (!in_array($emailNum, $processedEmails)) {
                        $allEmails[] = $emailNum;
                        $processedEmails[] = $emailNum;
                    }
                }
            }
        }
        
        // Sắp xếp lại tất cả kết quả tìm được
        rsort($allEmails);
        $allEmails = array_slice($allEmails, 0, 30); // Giới hạn xử lý 30 email
        
        error_log("GmailHandler: Total raw emails found: " . count($allEmails));

        foreach ($allEmails as $emailNum) {
            // Dừng sớm nếu đã đủ kết quả
            if (count($results) >= $limit) break;
            
            $header = imap_headerinfo($this->connection, $emailNum);
                    
                    // Filter chính xác theo số giờ
                    $emailTime = strtotime($header->date ?? 'now');
                    $timeLimit = time() - ($hoursLimit * 60 * 60);
                    
                    if ($emailTime < $timeLimit) {
                        continue; // Bỏ qua email cũ hơn giới hạn giờ
                    }
                    
                    // Lấy các header bổ sung
                    $rawHeaders = imap_fetchheader($this->connection, $emailNum);
                    
                    // Kiểm tra Delivered-To hoặc To header
                    $deliveredTo = $this->extractHeader($rawHeaders, 'Delivered-To');
                    $toAddress = $this->extractHeader($rawHeaders, 'X-Original-To');
                    
                    if (empty($deliveredTo)) {
                        $deliveredTo = isset($header->to[0]->mailbox) && isset($header->to[0]->host) 
                            ? $header->to[0]->mailbox . '@' . $header->to[0]->host 
                            : '';
                    }
                    
                    // Kiểm tra xem email có được gửi đến địa chỉ target không
                    $recipients = array_filter([
                        strtolower($deliveredTo),
                        strtolower($toAddress)
                    ]);
                    
                    $isMatch = false;
                    foreach ($recipients as $recipient) {
                        if (strpos($recipient, $targetEmail) !== false) {
                            $isMatch = true;
                            break;
                        }
                    }
                    
                    // Nếu không match qua header, kiểm tra trong raw header
                    if (!$isMatch) {
                        if (stripos($rawHeaders, $targetEmail) !== false) {
                            $isMatch = true;
                        }
                    }
                    
                    if ($isMatch) {
                        $from = isset($header->from[0]) 
                            ? $this->decodeHeader($header->from[0]->personal ?? '') . ' <' . 
                              ($header->from[0]->mailbox ?? '') . '@' . ($header->from[0]->host ?? '') . '>'
                            : 'Unknown';
                        
                        $subject = $this->decodeHeader($header->subject ?? '(Không có tiêu đề)');
                        
                        // Optimization: Bỏ qua email "Thiết bị mới" để tăng tốc
                        if (mb_stripos($subject, 'Một thiết bị mới') !== false || mb_stripos($subject, 'new device') !== false) {
                            continue;
                        }
                        
                        $date = date('Y-m-d H:i:s', strtotime($header->date ?? 'now'));
                        
                        // Chỉ lấy body khi đã match để tối ưu tốc độ
                        $structure = imap_fetchstructure($this->connection, $emailNum);
                        $body = $this->getEmailBody($emailNum, $structure);
                        $plainBody = strip_tags($body);
                        $snippet = mb_substr($plainBody, 0, 150) . '...';
                        
                        // Trích xuất mã số 4-6 chữ số hoặc Link Reset Password
                        $code = null;
                        
                        // 0. TV Household Update (PRIORITY - Check FIRST)
                        $isTVEmail = (
                            stripos($subject, 'cách cập nhật hộ gia đình netflix') !== false ||
                            stripos($subject, 'how to update your netflix household') !== false ||
                            stripos($subject, 'lưu ý quan trọng') !== false
                        );
                        if ($isTVEmail && preg_match('/(https:\/\/www\.netflix\.com\/account\/update-primary-location[^\s<>"]+)/i', $body, $m)) {
                            $code = html_entity_decode($m[1]);
                            $code = trim($code, "\"'<> ");
                            error_log("Netflix TV: Found update-primary-location link for subject: " . substr($subject, 0, 50));
                        } elseif ($isTVEmail) {
                            error_log("Netflix TV: Email matched TV pattern but no update-primary-location link found. Subject: " . substr($subject, 0, 50));
                        }
                        
                                                // 0. LINK RESET PASSWORD (Ưu tiên cao nhất)
                        if (stripos($subject, 'Hoàn thành yêu cầu đặt lại mật khẩu') !== false || 
                            stripos($subject, 'Complete your password reset request') !== false ||
                            stripos($subject, 'đặt lại mật khẩu') !== false) {
                            
                            // Tìm link bắt đầu bằng https://www.netflix.com/password
                            if (preg_match('/https:\/\/www\.netflix\.com\/password[^"\s\<\>]+/', $body, $linkMatches)) {
                                $code = html_entity_decode($linkMatches[0]);
                                // Clean up trailing quotes if regex caught them (though checked above, safe to ensure)
                                $code = trim($code, '"\'');
                            }
                        }

                        // 1. Kiểm tra trường hợp "Mã truy cập tạm thời" (Link Verify) - Ưu tiên trước khi tìm mã số
                        if (!$code) {
                            $isTemporaryCodeEmail = (
                                stripos($subject, 'Mã truy cập Netflix tạm thời') !== false || 
                                stripos($subject, 'temporary Netflix access code') !== false ||
                                stripos($subject, 'Your Netflix verification code') !== false ||
                                stripos($subject, 'Mã truy cập tạm thời') !== false
                            );

                            if ($isTemporaryCodeEmail) {
                                $verifyUrl = null;
                                // Pattern 1: Link plain text
                                if (preg_match('/(https?:\/\/www\.netflix\.com\/[^\s<>"]*account\/travel\/verify[^\s<>"]*)/i', $body, $linkMatches)) {
                                    $verifyUrl = $linkMatches[1];
                                }
                                // Pattern 2: Link href
                                elseif (preg_match('/href=["\']?(https?:\/\/www\.netflix\.com\/[^"\'>\s]*account\/travel\/verify[^"\'>\s]*)["\']?/i', $body, $linkMatches)) {
                                    $verifyUrl = $linkMatches[1];
                                }
                                
                                if ($verifyUrl) {
                                    $verifyUrl = html_entity_decode($verifyUrl);
                                    $verifyUrl = trim($verifyUrl);
                                    error_log("Netflix Code Extraction - Found verify URL: " . substr($verifyUrl, 0, 80) . "...");
                                    $code = $verifyUrl;
                                }
                            }
                        }

                        // 1.5. Household Verification (Link)
                        if (!$code) {
                             $isHouseholdEmail = (
                                stripos($subject, 'gia đình') !== false ||
                                stripos($subject, 'household') !== false ||
                                stripos($subject, 'hộ gia đình') !== false ||
                                stripos($subject, 'primary location') !== false
                            );

                            if ($isHouseholdEmail) {
                                $verifyUrl = null;
                                // Pattern: Link containing household, update-primary-location, or verify
                                if (preg_match('/(https?:\/\/www\.netflix\.com\/[^\s<>"]*(household|update-primary-location|account\/verify)[^\s<>"]*)/i', $body, $linkMatches)) {
                                    $verifyUrl = $linkMatches[1];
                                }
                                elseif (preg_match('/href=["\']?(https?:\/\/www\.netflix\.com\/[^\s<>"]*(household|update-primary-location|account\/verify)[^\s<>"]*)["\']?/i', $body, $linkMatches)) {
                                    $verifyUrl = $linkMatches[1];
                                }
                                
                                if ($verifyUrl) {
                                    $verifyUrl = html_entity_decode($verifyUrl);
                                    $verifyUrl = trim($verifyUrl, '"\' ');
                                    $code = $verifyUrl;
                                }
                            }
                        }

                        // 2. Tìm trực tiếp trong body (nếu chưa có code)
                        // Ưu tiên tìm theo ngữ cảnh tiếng Việt và tiếng Anh
                        if (!$code) {
                            $patterns = [
                                '/Nhập mã này để đăng nhập\s+(\d{4})/iu', // Viet
                                '/Enter this code to sign in\s+(\d{4})/iu', // Eng
                               '/Your Netflix verification code is\s+(\d{4})/iu',
                                '/Mã truy cập tạm thời của bạn là\s+(\d{4})/iu',
                                '/Your temporary access code is\s+(\d{4})/iu'
                            ];

                            foreach ($patterns as $pattern) {
                                if (preg_match($pattern, $plainBody, $matches) || preg_match($pattern, $body, $matches)) {
                                    $code = $matches[1];
                                    break;
                                }
                            }
                        }

                        // Fallback: Tìm số 4 chữ số đứng riêng lẻ (nếu chưa tìm thấy) - Cẩn thận với số năm 2024, 2025
                        if (!$code && preg_match('/\b(\d{4})\b/', $plainBody, $matches)) {
                            // Chỉ lấy nếu không phải là năm hiện tại hoặc tương lai gần (simple check)
                            $val = (int)$matches[1];
                            $currentYear = (int)date('Y');
                            if ($val < 2020 || $val > $currentYear + 5) {
                                 $code = $matches[1];
                            } else {
                                // Nếu là năm, thử tìm số tiếp theo
                                if (preg_match_all('/\b(\d{4})\b/', $plainBody, $allMatches)) {
                                    foreach ($allMatches[1] as $m) {
                                        $v = (int)$m;
                                        if ($v < 2020 || $v > $currentYear + 5) {
                                            $code = $m;
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        
                        $results[] = [
                            'id' => $emailNum,
                            'from' => $from,
                            'subject' => $subject,
                            'date' => $date,
                            'snippet' => $snippet,
                            'code' => $code
                        ];
                    }
        }
        
        return $results;
        return $results;
    }

    /**
     * Lấy danh sách tất cả email Netflix gần đây (dùng cho Cron Job)
     */
    public function fetchRecentEmails($limit = 50) {
        if (!$this->connection) {
            $this->connect();
        }

        $sinceDate = date('d-M-Y', strtotime("-3 days")); // Lấy dữ liệu 3 ngày gần nhất
        // Tìm tất cả email có từ khóa Netflix trong tiêu đề hoặc người gửi
        // Lưu ý: Tìm FROM "Netflix" có thể không đủ nếu tên hiển thị khác.
        // Tốt nhất tìm TEXT "Netflix" hoặc FROM các domain netflix
        $criteria = sprintf('TEXT "Netflix" SINCE "%s"', $sinceDate);
        
        $emails = @imap_search($this->connection, $criteria);
        
        if (empty($emails)) return [];
        
        rsort($emails);
        $emails = array_slice($emails, 0, $limit);
        
        $results = [];
        foreach ($emails as $emailNum) {
            // Header Info
            $header = imap_headerinfo($this->connection, $emailNum);
            $rawHeaders = imap_fetchheader($this->connection, $emailNum);

            // 1. Extract Recipient (Quan trọng để map với user)
            $deliveredTo = $this->extractHeader($rawHeaders, 'Delivered-To');
            $toAddress = $this->extractHeader($rawHeaders, 'X-Original-To');
            
            if (empty($deliveredTo)) {
                $deliveredTo = isset($header->to[0]->mailbox) && isset($header->to[0]->host) 
                    ? $header->to[0]->mailbox . '@' . $header->to[0]->host 
                    : '';
            }
            
            // Ưu tiên Delivered-To vì là đích thực tế
            $recipient = !empty($deliveredTo) ? $deliveredTo : ($toAddress ?: '');
            
            // 2. Extract Data
            $subject = $this->decodeHeader($header->subject ?? '(No Subject)');
            $date = date('Y-m-d H:i:s', strtotime($header->date ?? 'now'));
            $timestamp = $header->udate;
            
            // Skip "New Device" emails (Optimization)
            if (mb_stripos($subject, 'Một thiết bị mới') !== false || mb_stripos($subject, 'new device') !== false) {
                 continue;
            }

            // 3. Get Body
            $structure = imap_fetchstructure($this->connection, $emailNum);
            $body = $this->getEmailBody($emailNum, $structure);
            
            // 4. Extract Code/Link logic (Reusable from isTemporaryCodeEmail but simplified)
            // Chúng ta sẽ parse lại body ở Cron hoặc lưu raw body rồi parse sau.
            // Để tiện, ta trích xuất cơ bản ở đây.
            
            $msgId = $header->message_id ?? md5($date . $subject . $recipient);

            $results[] = [
                'uid' => $msgId, // Use Message-ID to check duplicates
                'recipient' => strtolower($recipient),
                'subject' => $subject,
                'date' => $date,
                'email_number' => $emailNum, // Để fetch body nếu cần
                'body' => $body
            ];
        }
        
        return $results;
    }
    public function getEmailDetail($emailId) {
        if (!$this->connection) {
            $this->connect();
        }
        
        $header = imap_headerinfo($this->connection, $emailId);
        $structure = imap_fetchstructure($this->connection, $emailId);
        
        $from = isset($header->from[0]) 
            ? $this->decodeHeader($header->from[0]->personal ?? '') . ' <' . 
              ($header->from[0]->mailbox ?? '') . '@' . ($header->from[0]->host ?? '') . '>'
            : 'Unknown';
        
        $to = isset($header->to[0]) 
            ? ($header->to[0]->mailbox ?? '') . '@' . ($header->to[0]->host ?? '')
            : '';
        
        return [
            'id' => $emailId,
            'from' => $from,
            'to' => $to,
            'subject' => $this->decodeHeader($header->subject ?? '(Không có tiêu đề)'),
            'date' => date('Y-m-d H:i:s', strtotime($header->date ?? 'now')),
            'body' => $this->getEmailBody($emailId, $structure)
        ];
    }
    
    /**
     * Trích xuất header từ raw headers
     */
    private function extractHeader($rawHeaders, $headerName) {
        $pattern = '/^' . preg_quote($headerName, '/') . ':\s*(.+)$/mi';
        if (preg_match($pattern, $rawHeaders, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
    
    /**
     * Decode header (UTF-8, Base64, etc.)
     */
    private function decodeHeader($text) {
        if (empty($text)) return '';
        
        $decoded = imap_mime_header_decode($text);
        $result = '';
        foreach ($decoded as $part) {
            $result .= $part->text;
        }
        return $result;
    }
    
    /**
     * Lấy body của email
     */
    private function getEmailBody($emailNum, $structure) {
        $body = '';
        
        if ($structure->type === 0) { // Text
            $body = imap_fetchbody($this->connection, $emailNum, 1);
            $body = $this->decodeBody($body, $structure->encoding);
        } elseif ($structure->type === 1) { // Multipart
            $body = $this->getMultipartBody($emailNum, $structure);
        }
        
        // Convert charset if needed
        if (isset($structure->parameters)) {
            foreach ($structure->parameters as $param) {
                if (strtolower($param->attribute) === 'charset') {
                    $body = mb_convert_encoding($body, 'UTF-8', $param->value);
                    break;
                }
            }
        }
        
        return $body;
    }
    
    /**
     * Lấy body từ multipart email
     */
    private function getMultipartBody($emailNum, $structure) {
        $body = '';
        
        if (isset($structure->parts)) {
            foreach ($structure->parts as $partNum => $part) {
                $partNumber = $partNum + 1;
                
                if ($part->subtype === 'HTML') {
                    $body = imap_fetchbody($this->connection, $emailNum, $partNumber);
                    $body = $this->decodeBody($body, $part->encoding);
                    break;
                } elseif ($part->subtype === 'PLAIN' && empty($body)) {
                    $body = imap_fetchbody($this->connection, $emailNum, $partNumber);
                    $body = $this->decodeBody($body, $part->encoding);
                }
            }
        }
        
        return $body;
    }
    
    /**
     * Decode body theo encoding
     */
    private function decodeBody($body, $encoding) {
        switch ($encoding) {
            case 0: // 7BIT
            case 1: // 8BIT
                return $body;
            case 2: // BINARY
                return $body;
            case 3: // BASE64
                return base64_decode($body);
            case 4: // QUOTED-PRINTABLE
                return quoted_printable_decode($body);
            default:
                return $body;
        }
    }
}
