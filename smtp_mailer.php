<?php
/**
 * LeadForge AI - Pure Native SMTP Client
 * 100% Dependency-Free Socket Mailer with TLS/SSL & Zero-Spam Plain Text Formatting
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class SmtpMailer {
    public static function send(
        string $toEmail,
        string $subject,
        string $body,
        ?array $customSettings = null
    ): array {
        $settings = $customSettings ?: getSettings();
        
        $smtpHost = trim($settings['smtp_host'] ?? 'smtp.gmail.com');
        $smtpPort = (int)($settings['smtp_port'] ?? 587);
        $smtpUser = trim($settings['smtp_user'] ?? '');
        $smtpPass = trim($settings['smtp_pass'] ?? '');
        $fromEmail = trim($settings['smtp_from_email'] ?? '') ?: $smtpUser;
        $fromName = trim($settings['smtp_from_name'] ?? 'Jay');

        // Unbypassable Sent Ledger Duplicate Guard
        $isTest = (stripos($subject, 'Test Delivery') !== false || stripos($subject, 'SMTP Test') !== false || stripos($subject, 'Verification') !== false);
        $destDomain = strtolower(substr(strrchr($toEmail, "@") ?: '', 1));
        
        require_once __DIR__ . '/database.php';
        if (!$isTest && function_exists('isEmailOrDomainAlreadySent') && isEmailOrDomainAlreadySent($toEmail, $destDomain)) {
            error_log("🛡️ [SMTP MAILER DUPLICATE BLOCKED] Prevented duplicate email to {$toEmail} ({$destDomain})");
            return [
                'success' => false,
                'mode' => 'DUPLICATE_GUARD_BLOCKED',
                'message' => "Duplicate Blocked: {$toEmail} ({$destDomain}) has already been sent an email previously."
            ];
        }
        if (empty($smtpUser) || empty($smtpPass)) {
            // Fallback: Use PHP native mail() or queue for manual/Gmail 1-click
            $headers = "From: {$fromName} <{$fromEmail}>\r\n" .
                       "Reply-To: {$fromEmail}\r\n" .
                       "X-Mailer: PHP/" . phpversion();
            
            $sent = @mail($toEmail, $subject, $body, $headers);
            if ($sent) {
                return [
                    'success' => true,
                    'mode' => 'PHP Native Mail',
                    'message' => "Email sent via local server mailer to {$toEmail}."
                ];
            }

            return [
                'success' => false,
                'mode' => 'Unconfigured SMTP',
                'message' => 'Please enter your Gmail Address & App Password in the Settings tab to send real emails directly.'
            ];
        }

        // Connect via Socket
        $socketTimeout = 10;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $serverAddress = ($smtpPort === 465) ? "ssl://{$smtpHost}:{$smtpPort}" : "tcp://{$smtpHost}:{$smtpPort}";
        $socket = @stream_socket_client($serverAddress, $errno, $errstr, $socketTimeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            return [
                'success' => false,
                'mode' => 'Socket Connection Failed',
                'message' => "Could not connect to {$smtpHost}:{$smtpPort} - {$errstr} ({$errno})"
            ];
        }

        stream_set_timeout($socket, $socketTimeout);

        $response = self::readSocket($socket);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'message' => "Server connection failed: {$response}"];
        }

        // Send EHLO
        self::writeSocket($socket, "EHLO " . gethostname());
        $response = self::readSocket($socket);

        // If port 587, initiate STARTTLS
        if ($smtpPort === 587) {
            self::writeSocket($socket, "STARTTLS");
            $response = self::readSocket($socket);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                return ['success' => false, 'message' => "STARTTLS failed: {$response}"];
            }

            // Enable Crypto
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                fclose($socket);
                return ['success' => false, 'message' => 'TLS encryption handshake failed.'];
            }

            // Re-send EHLO after TLS
            self::writeSocket($socket, "EHLO " . gethostname());
            $response = self::readSocket($socket);
        }

        // Authenticate
        self::writeSocket($socket, "AUTH LOGIN");
        $response = self::readSocket($socket);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'message' => "AUTH LOGIN rejected: {$response}"];
        }

        self::writeSocket($socket, base64_encode($smtpUser));
        $response = self::readSocket($socket);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'message' => "Username rejected: {$response}"];
        }

        // Clean spaces from App Password if any
        $cleanPass = str_replace(' ', '', $smtpPass);
        self::writeSocket($socket, base64_encode($cleanPass));
        $response = self::readSocket($socket);
        if (substr($response, 0, 3) !== '235') {
            fclose($socket);
            return ['success' => false, 'message' => "Invalid Password/App Password. Gmail response: {$response}"];
        }

        // MAIL FROM
        self::writeSocket($socket, "MAIL FROM:<{$fromEmail}>");
        $response = self::readSocket($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'message' => "MAIL FROM rejected: {$response}"];
        }

        // RCPT TO
        self::writeSocket($socket, "RCPT TO:<{$toEmail}>");
        $response = self::readSocket($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'message' => "Recipient {$toEmail} rejected: {$response}"];
        }

        // DATA
        self::writeSocket($socket, "DATA");
        $response = self::readSocket($socket);
        if (substr($response, 0, 3) !== '354') {
            fclose($socket);
            return ['success' => false, 'message' => "DATA rejected: {$response}"];
        }

        // Headers + Clean Plain Text Body (Zero-Spam format, 100% human-grade)
        $uniqKey = bin2hex(random_bytes(12));
        $fromDomain = explode('@', $fromEmail)[1] ?? 'gmail.com';
        $messageId = "<{$uniqKey}@{$fromDomain}>";
        $date = date('r');
        
        $emailHeaders = [
            "Message-ID: {$messageId}",
            "Date: {$date}",
            "From: {$fromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            "To: <{$toEmail}>",
            "Subject: {$subject}",
            "MIME-Version: 1.0",
            "Content-Type: text/plain; charset=UTF-8; format=flowed",
            "Content-Transfer-Encoding: 8bit"
        ];

        $rawPayload = implode("\r\n", $emailHeaders) . "\r\n\r\n" . $body . "\r\n.\r\n";
        fwrite($socket, $rawPayload);

        $response = self::readSocket($socket);
        self::writeSocket($socket, "QUIT");
        fclose($socket);

        if (substr($response, 0, 3) === '250') {
            if (!$isTest && function_exists('recordSentEmailToLedger')) {
                recordSentEmailToLedger($toEmail, $destDomain, $subject);
            }
            return [
                'success' => true,
                'mode' => 'Real SMTP Delivery',
                'message' => "✅ Email successfully delivered to {$toEmail} via {$smtpHost}!"
            ];
        }

        return [
            'success' => false,
            'mode' => 'Transmission Error',
            'message' => "SMTP Delivery Failed: {$response}"
        ];
    }

    private static function writeSocket($socket, string $command): void {
        fwrite($socket, $command . "\r\n");
    }

    private static function readSocket($socket): string {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }
}
