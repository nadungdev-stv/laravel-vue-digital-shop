<?php
/**
 * Email Mailer Helper
 * Gửi email qua SMTP hoặc PHP mail()
 */

class Mailer {
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpSecure;
    private $fromEmail;
    private $fromName;
    private $useSmtp;

    public function __construct() {
        // Lấy cấu hình từ Admin Settings
        $this->smtpHost = getSetting('smtp_host', 'smtp.gmail.com');
        $this->smtpPort = (int)getSetting('smtp_port', 587);
        $this->smtpUser = getSetting('smtp_username', '');
        $this->smtpPass = getSetting('smtp_password', '');
        $this->smtpSecure = getSetting('smtp_encryption', 'tls'); // tls hoặc ssl
        $this->fromEmail = getSetting('mail_from_address', 'admin@veyrix.pro');
        $this->fromName = getSetting('mail_from_name', getSetting('site_name', 'Veyrix Shop'));
        $this->useSmtp = !empty($this->smtpUser) && !empty($this->smtpPass);
    }

    /**
     * Gửi email
     * @param string $to Email người nhận
     * @param string $subject Tiêu đề
     * @param string $body Nội dung HTML
     * @return bool
     */
    public function send($to, $subject, $body) {
        if ($this->useSmtp) {
            return $this->sendSmtp($to, $subject, $body);
        }
        return $this->sendMail($to, $subject, $body);
    }

    /**
     * Gửi email qua PHP mail()
     */
    private function sendMail($to, $subject, $body) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Gửi email qua SMTP (sử dụng socket)
     */
    private function sendSmtp($to, $subject, $body) {
        try {
            // Kết nối SMTP
            $socket = $this->smtpConnect();
            if (!$socket) {
                error_log("SMTP: Cannot connect to server");
                return $this->sendMail($to, $subject, $body); // Fallback
            }

            // EHLO
            $this->smtpCommand($socket, "EHLO " . gethostname());

            // STARTTLS nếu cần
            if ($this->smtpSecure === 'tls' && $this->smtpPort == 587) {
                $this->smtpCommand($socket, "STARTTLS");
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->smtpCommand($socket, "EHLO " . gethostname());
            }

            // AUTH LOGIN
            $this->smtpCommand($socket, "AUTH LOGIN");
            $this->smtpCommand($socket, base64_encode($this->smtpUser));
            $this->smtpCommand($socket, base64_encode($this->smtpPass));

            // MAIL FROM
            $this->smtpCommand($socket, "MAIL FROM:<{$this->fromEmail}>");

            // RCPT TO
            $this->smtpCommand($socket, "RCPT TO:<{$to}>");

            // DATA
            $this->smtpCommand($socket, "DATA");

            // Email content
            $message = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $message .= "To: {$to}\r\n";
            $message .= "Subject: {$subject}\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "\r\n";
            $message .= $body;
            $message .= "\r\n.\r\n";

            fwrite($socket, $message);
            $this->getSmtpResponse($socket);

            // QUIT
            $this->smtpCommand($socket, "QUIT");
            fclose($socket);

            return true;
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return $this->sendMail($to, $subject, $body); // Fallback
        }
    }

    /**
     * Kết nối SMTP server
     */
    private function smtpConnect() {
        $host = $this->smtpHost;
        if ($this->smtpSecure === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $socket = @fsockopen($host, $this->smtpPort, $errno, $errstr, 30);
        if (!$socket) {
            error_log("SMTP Connect Error: $errstr ($errno)");
            return false;
        }

        $this->getSmtpResponse($socket);
        return $socket;
    }

    /**
     * Gửi lệnh SMTP và nhận response
     */
    private function smtpCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
        return $this->getSmtpResponse($socket);
    }

    /**
     * Đọc response từ SMTP server
     */
    private function getSmtpResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }

    /**
     * Tạo email template đẹp
     */
    public static function template($title, $content, $buttonText = null, $buttonUrl = null) {
        $siteName = getSetting('site_name', 'Veyrix Shop');
        $primaryColor = '#667eea';

        $buttonHtml = '';
        if ($buttonText && $buttonUrl) {
            $buttonHtml = "
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$buttonUrl}' style='display: inline-block; background: {$primaryColor}; color: white; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;'>{$buttonText}</a>
                </div>
            ";
        }

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; background-color: #f4f4f5;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f4f4f5; padding: 40px 20px;'>
        <tr>
            <td align='center'>
                <table width='100%' cellpadding='0' cellspacing='0' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                    <!-- Header -->
                    <tr>
                        <td style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 32px 40px; text-align: center;'>
                            <h1 style='margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;'>{$siteName}</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style='padding: 40px;'>
                            <h2 style='margin: 0 0 20px; color: #1f2937; font-size: 24px; font-weight: 600;'>{$title}</h2>
                            <div style='color: #4b5563; font-size: 16px; line-height: 1.6;'>
                                {$content}
                            </div>
                            {$buttonHtml}
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style='background-color: #f9fafb; padding: 24px 40px; text-align: center; border-top: 1px solid #e5e7eb;'>
                            <p style='margin: 0; color: #6b7280; font-size: 14px;'>
                                Email này được gửi tự động từ {$siteName}.<br>
                                Vui lòng không trả lời email này.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        ";
    }
}

/**
 * Helper function để gửi email nhanh
 */
function sendEmail($to, $subject, $body) {
    $mailer = new Mailer();
    return $mailer->send($to, $subject, $body);
}

/**
 * Gửi email với template
 */
function sendEmailTemplate($to, $subject, $title, $content, $buttonText = null, $buttonUrl = null) {
    $body = Mailer::template($title, $content, $buttonText, $buttonUrl);
    return sendEmail($to, $subject, $body);
}
