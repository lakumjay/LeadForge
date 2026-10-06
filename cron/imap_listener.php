<?php
/**
 * LeadForge AI - IMAP Reply Detector & Instant Telegram Bot Auto-Alert
 * Connects securely to Gmail via IMAP SSL, detects replies from CRM leads,
 * updates lead status to 'discussing', and sends instant Telegram push alerts.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../api/telegram.php';

function checkIncomingEmailReplies(PDO $db, array $settings): array {
    $results = [];
    $smtpUser = trim($settings['smtp_user'] ?? '');
    $smtpPass = trim($settings['smtp_pass'] ?? '');

    if (empty($smtpUser) || empty($smtpPass)) {
        return $results;
    }

    if (!function_exists('imap_open')) {
        // Fallback: If imap extension not enabled, log notice
        return $results;
    }

    $appUrl = 'https://leadsflow.snwebkarma.in';
    $mailbox = "{imap.gmail.com:993/imap/ssl}INBOX";

    try {
        $inbox = @imap_open($mailbox, $smtpUser, $smtpPass, OP_READONLY, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
        if (!$inbox) {
            return $results;
        }

        // Search unread or recent messages in the last 2 days
        $sinceDate = date('d-M-Y', strtotime('-2 days'));
        $emailIds = @imap_search($inbox, 'SINCE "' . $sinceDate . '"');

        if (!empty($emailIds)) {
            // Get all contacted leads from database
            $stmt = $db->query("SELECT id, client_name, client_email, company, status FROM leads WHERE client_email IS NOT NULL AND client_email LIKE '%@%' AND status IN ('contacted', 'followup_1', 'followup_2', 'new')");
            $contactedLeads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $emailMap = [];
            foreach ($contactedLeads as $l) {
                $emailMap[strtolower(trim($l['client_email']))] = $l;
            }

            foreach ($emailIds as $emailNum) {
                $header = @imap_headerinfo($inbox, $emailNum);
                $fromInfo = $header->from[0] ?? null;
                if (!$fromInfo) continue;

                $fromEmail = strtolower(trim(($fromInfo->mailbox ?? '') . '@' . ($fromInfo->host ?? '')));
                $fromName = trim($fromInfo->personal ?? $fromEmail);
                $subject = trim($header->subject ?? 'Re: observation');

                if (isset($emailMap[$fromEmail])) {
                    $matchedLead = $emailMap[$fromEmail];
                    $body = @imap_fetchbody($inbox, $emailNum, '1');
                    $snippet = strip_tags(substr(trim((string)$body ?: $subject), 0, 350));

                    // Update lead status to 'discussing' (In Discussion)
                    $db->prepare("UPDATE leads SET status = 'discussing', notes = notes || '\n[CLIENT REPLY RECEIVED " . date('Y-m-d H:i') . "]: ' || ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                       ->execute([$snippet, $matchedLead['id']]);

                    // Send Instant Telegram Alert
                    TelegramNotifier::sendClientReplyAlert($fromEmail, $matchedLead['client_name'] ?: $fromName, $subject, $snippet, $appUrl);

                    $results[] = [
                        'lead_id' => $matchedLead['id'],
                        'client_email' => $fromEmail,
                        'client_name' => $matchedLead['client_name'],
                        'subject' => $subject
                    ];
                }
            }
        }

        @imap_close($inbox);
    } catch (Throwable $e) {
        error_log("IMAP reply check error: " . $e->getMessage());
    }

    return $results;
}

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'imap_listener.php') {
    $db = Database::getConnection();
    $settings = getSettings();
    $res = checkIncomingEmailReplies($db, $settings);
    echo "Checked IMAP replies. Found: " . count($res) . "\n";
}
