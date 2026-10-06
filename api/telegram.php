<?php
/**
 * LeadForge AI - Telegram Bot Instant Notification Engine
 * Sends instant alerts for:
 * 1. Morning 9 AM IST Daily 15 LinkedIn Prospect Briefings
 * 2. Instant Client Email Replies & In-Discussion Alerts
 * 3. Daily Summary & Critical System Heartbeats
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

class TelegramNotifier {
    /**
     * Send Markdown/HTML notification to user's Telegram Phone / Group
     */
    public static function send(string $message, ?string $botToken = null, ?string $chatId = null): array {
        $settings = getSettings();
        $token = trim($botToken ?: ($settings['telegram_bot_token'] ?? ''));
        $chat = trim($chatId ?: ($settings['telegram_chat_id'] ?? ''));

        if (empty($token) || empty($chat)) {
            return [
                'ok' => false,
                'message' => 'Telegram bot token or chat ID is not configured in Settings.'
            ];
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $payload = [
            'chat_id' => $chat,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response ?: '', true);
            if (!empty($data['ok'])) {
                return ['ok' => true, 'message' => 'Telegram alert delivered successfully!'];
            }
        }

        return [
            'ok' => false,
            'message' => "Telegram API returned HTTP {$httpCode}: {$response}"
        ];
    }

    /**
     * Morning 9 AM IST Daily 15 LinkedIn Prospect Briefing
     */
    public static function sendMorningLinkedInBriefing(PDO $db, string $appBaseUrl): array {
        $today = date('Y-m-d');
        $stmt = $db->prepare("SELECT * FROM linkedin_queue WHERE DATE(created_at) = ? AND status = 'pending' ORDER BY id ASC LIMIT 15");
        $stmt->execute([$today]);
        $prospects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($prospects)) {
            // Pick uncontacted prospects
            $stmt = $db->query("SELECT * FROM linkedin_queue WHERE status = 'pending' ORDER BY id DESC LIMIT 15");
            $prospects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($prospects)) {
            return ['ok' => false, 'message' => 'No pending LinkedIn prospects found in queue.'];
        }

        $count = count($prospects);
        $msg = "⚡ <b>LeadForge AI — Daily 15 LinkedIn Prospects Ready!</b>\n";
        $msg .= "📅 <i>" . date('D, d M Y') . " (Morning Briefing)</i>\n\n";
        $msg .= "You have <b>{$count} targeted decision makers</b> ready to connect with custom notes.\n\n";

        foreach (array_slice($prospects, 0, 5) as $i => $p) {
            $num = $i + 1;
            $msg .= "{$num}. <b>{$p['name']}</b> ({$p['company']})\n";
            $msg .= "   🔗 <a href=\"{$p['linkedin_url']}\">LinkedIn Profile</a>\n";
            $msg .= "   📝 <i>\"" . htmlspecialchars(substr($p['note'], 0, 100)) . "...\"</i>\n\n";
        }

        if ($count > 5) {
            $rem = $count - 5;
            $msg .= "<i>+ {$rem} more high-value prospects in queue...</i>\n\n";
        }

        $mobileUrl = rtrim($appBaseUrl, '/') . '/linkedin.php';
        $msg .= "📱 <b>Open Mobile Web App:</b>\n<a href=\"{$mobileUrl}\">👉 Open 1-Click LinkedIn Queue ({$count} Leads)</a>\n\n";
        $msg .= "<i>Tip: Open on your phone, tap 'Open Profile', paste note and hit Sent (Takes 5 minutes)!</i>";

        return self::send($msg);
    }

    /**
     * Instant Client Reply Alert (When a lead responds via email)
     */
    public static function sendClientReplyAlert(string $clientEmail, string $clientName, string $subject, string $snippet, string $appBaseUrl): array {
        $msg = "🚨 <b>NEW CLIENT EMAIL REPLY DETECTED!</b>\n\n";
        $msg .= "👤 <b>From:</b> " . htmlspecialchars($clientName) . " (<code>" . htmlspecialchars($clientEmail) . "</code>)\n";
        $msg .= "📌 <b>Subject:</b> " . htmlspecialchars($subject) . "\n\n";
        $msg .= "💬 <b>Message Preview:</b>\n<i>\"" . htmlspecialchars(substr($snippet, 0, 300)) . "...\"</i>\n\n";
        
        $closerUrl = rtrim($appBaseUrl, '/') . '/index.php#closer';
        $msg .= "🎯 <b>Action:</b> Reply within 15 minutes for 70%+ closing rate!\n";
        $msg .= "👉 <a href=\"{$closerUrl}\">Open AI Tactical Chat Closer</a>";

        return self::send($msg);
    }

    /**
     * Instant LinkedIn Post Auto-Published Alert
     */
    public static function sendLinkedInPostAlert(string $headline, string $category, string $status, string $appBaseUrl): array {
        $msg = "🚀 <b>LINKEDIN VIRAL POST AUTO-PUBLISHED!</b>\n\n";
        $msg .= "📌 <b>Category:</b> " . htmlspecialchars(ucwords(str_replace('_', ' ', $category))) . "\n";
        $msg .= "🎯 <b>Headline:</b> " . htmlspecialchars($headline) . "\n";
        $msg .= "⚡ <b>Status:</b> " . htmlspecialchars($status) . "\n";
        $msg .= "🕒 <b>Time:</b> " . date('Y-m-d H:i:s') . " IST\n\n";
        $feedUrl = 'https://www.linkedin.com/feed/';
        $msg .= "👉 <a href=\"{$feedUrl}\">View on LinkedIn Feed</a> | <a href=\"" . rtrim($appBaseUrl, '/') . "/linkedin.php\">Open LeadForge Suite</a>";

        return self::send($msg);
    }
}

// Standalone API handler for AJAX testing from Settings
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'telegram.php') {
    header('Content-Type: application/json; charset=utf-8');
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;
    $action = $data['action'] ?? ($_GET['action'] ?? 'test');

    if ($action === 'test') {
        $token = trim($data['telegram_bot_token'] ?? '');
        $chat = trim($data['telegram_chat_id'] ?? '');
        $testMsg = "✅ <b>LeadForge AI Telegram Connected!</b>\n\n"
            . "Time: " . date('Y-m-d H:i:s') . "\n"
            . "Status: 100% Active\n\n"
            . "You will receive instant phone notifications when:\n"
            . "• A client replies to your cold pitches\n"
            . "• Daily 15 LinkedIn prospects are ready at 9 AM IST";

        $res = TelegramNotifier::send($testMsg, $token, $chat);
        echo json_encode($res);
        exit;
    }
}
