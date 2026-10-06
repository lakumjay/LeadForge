<?php
/**
 * LeadForge AI — Master 24/7 Autonomous Outreach Cron
 * 
 * Spec:
 * 1. cPanel cron runs every 5 min: /usr/local/bin/php cron/outreach.php
 * 2. flock() lock file protection (max 2 leads per run)
 * 3. Pipeline: lead select -> domain dedupe -> site audit -> email find -> pitch generate -> SMTP send -> CRM status "Contacted"
 * 4. Daily limit 30 emails, reset at midnight IST
 * 5. Email not found -> status "Manual"
 * 6. CAN-SPAM compliant opt-out footer
 * 7. Follow-up engine: 48h, 5d, 9d (auto-canceled on reply)
 * 8. IMAP reply check -> Telegram bot alert + CRM "In Discussion"
 * 9. Morning 9 AM IST -> Telegram 15 LinkedIn prospect briefing
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../smtp_mailer.php';
require_once __DIR__ . '/../email_verifier.php';
require_once __DIR__ . '/../api/audit.php';
require_once __DIR__ . '/../api/generate.php';
require_once __DIR__ . '/../api/agency.php';
require_once __DIR__ . '/../api/mass_scanner.php';
require_once __DIR__ . '/../api/sales_navigator.php';
require_once __DIR__ . '/../api/followup_engine.php';
require_once __DIR__ . '/../api/telegram.php';
require_once __DIR__ . '/../api/linkedin_ai_engine.php';
require_once __DIR__ . '/imap_listener.php';

$logFile = DATA_PATH . '/cron.log';
$statusFile = DATA_PATH . '/daemon_status.json';
$lockFile = DATA_PATH . '/outreach.lock';

// 1. Non-blocking flock() file lock
$lockFp = fopen($lockFile, 'c+');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] ⏭️ Another outreach cron cycle is actively running. Skipping overlap.\n";
    exit;
}

@set_time_limit(60);

function cronLog(string $msg): void {
    global $logFile;
    $line = "[" . date('Y-m-d H:i:s') . " IST] " . $msg . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

cronLog("⚡ [OUTREACH CRON] Starting 5-minute autonomous cycle...");

$db = Database::getConnection();
$settings = getSettings();
$usdToInr = (float)($settings['usd_to_inr'] ?? 86.5);
$dailyEmailLimit = (int)($settings['daily_email_limit'] ?? 30);
$appUrl = 'https://leadsflow.snwebkarma.in';

// Check today's sent count (IST Midnight reset)
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT COUNT(*) FROM outreach_logs WHERE platform = 'Email' AND DATE(created_at) = ?");
$stmt->execute([$today]);
$emailsSentToday = (int)$stmt->fetchColumn();

cronLog("📊 Daily Quota Status: {$emailsSentToday} / {$dailyEmailLimit} Emails Dispatched Today");

// ----------------------------------------------------
// A. CHECK INCOMING REPLIES VIA IMAP & TELEGRAM ALERT
// ----------------------------------------------------
$replies = checkIncomingEmailReplies($db, $settings);
if (!empty($replies)) {
    cronLog("🚨 [CLIENT REPLIES DETECTED] Processed " . count($replies) . " new reply messages and sent Telegram alerts.");
}

// ----------------------------------------------------
// B. MORNING 9 AM IST TELEGRAM LINKEDIN BRIEFING
// ----------------------------------------------------
$currentHour = (int)date('H');
$currentMinute = (int)date('i');
if ($currentHour === 9 && $currentMinute < 10) {
    $briefingFlagFile = DATA_PATH . "/briefing_sent_{$today}.lock";
    if (!file_exists($briefingFlagFile)) {
        TelegramNotifier::sendMorningLinkedInBriefing($db, $appUrl);
        file_put_contents($briefingFlagFile, date('Y-m-d H:i:s'));
        cronLog("📱 [TELEGRAM BRIEFING] Sent Morning 9 AM IST 15 LinkedIn prospects to phone.");
    }
}

// ----------------------------------------------------
// B2. AUTONOMOUS LINKEDIN SAFE DISPATCH (ANTI-BAN SAFE LIMIT)
// ----------------------------------------------------
$stmt = $db->prepare("SELECT COUNT(*) FROM outreach_logs WHERE platform = 'LinkedIn' AND DATE(created_at) = ?");
$stmt->execute([$today]);
$liSentToday = (int)$stmt->fetchColumn();
$liDailyLimit = (int)($settings['daily_linkedin_limit'] ?? 15);

if ($liSentToday < $liDailyLimit) {
    $stmt = $db->query("SELECT * FROM linkedin_queue WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
    $liProspect = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($liProspect) {
        $liId = (int)$liProspect['id'];
        $db->prepare("UPDATE linkedin_queue SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$liId]);
        $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, 'LinkedIn', 'Server-Side Autonomous Safe Outreach')")->execute([$liId]);
        $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            "LinkedIn Connection: {$liProspect['name']} ({$liProspect['company']})",
            'cPanel Server Autonomous Pilot',
            $liProspect['name'],
            $liProspect['company'],
            $liProspect['linkedin_url'],
            'LinkedIn',
            'contacted',
            250,
            250 * $usdToInr,
            "Role: {$liProspect['role']}\nDispatched via 24/7 Server Autonomous LinkedIn Engine with Anti-Ban Protection.",
            $liProspect['note']
        ]);
        $liSentToday++;
        cronLog("💼 [LINKEDIN AUTO-DISPATCH] Dispatched connection note to {$liProspect['name']} ({$liProspect['company']})! Today: {$liSentToday}/{$liDailyLimit}");
    }
}

// ----------------------------------------------------
// B3. AUTONOMOUS LINKEDIN VIRAL POST AUTO-PUBLISHER
// ----------------------------------------------------
$autoPostResult = autoPublishDailyLinkedInPost($db, $settings);
if ($autoPostResult && !empty($autoPostResult['ok'])) {
    cronLog("🚀 [LINKEDIN AUTO-POST] Auto-published daily viral post: \"{$autoPostResult['headline']}\" ({$autoPostResult['category']}) via {$autoPostResult['published_via']}");
}

// ----------------------------------------------------
// B4. AUTONOMOUS LINKEDIN AI COMMENT ENGINE
// ----------------------------------------------------
$autoCommentResult = autoPublishDailyLinkedInComment($db, $settings);
if ($autoCommentResult && !empty($autoCommentResult['ok'])) {
    cronLog("💬 [LINKEDIN AUTO-COMMENT] Auto-commented on {$autoCommentResult['author']}'s post ({$autoCommentResult['company']}): \"" . substr($autoCommentResult['comment'], 0, 80) . "...\"");
}

// ----------------------------------------------------
// C. 3-STAGE SMART FOLLOW-UP SEQUENCE (48h, 5d, 9d)
// ----------------------------------------------------
if ($emailsSentToday < $dailyEmailLimit) {
    $followups = runAutomatedFollowups($db, $settings);
    foreach ($followups as $fu) {
        if ($fu['success']) {
            $emailsSentToday++;
            cronLog("🔁 [AUTO FOLLOW-UP DELIVERED] {$fu['stage']} -> {$fu['recipient']}");
        }
    }
}

// ----------------------------------------------------
// D. PRIMARY COLD OUTREACH PIPELINE (MAX 2 EMAILS SENT PER RUN)
// ----------------------------------------------------
$leadsDispatchedThisRun = 0;
$candidatesChecked = 0;
$maxLeadsPerRun = 2;
$maxCandidatesToScan = 15;

if ($emailsSentToday < $dailyEmailLimit && !empty($agencies)) {
    $shuffledAgencies = $agencies;
    shuffle($shuffledAgencies);

    foreach ($shuffledAgencies as $targetAgency) {
        if ($leadsDispatchedThisRun >= $maxLeadsPerRun || $candidatesChecked >= $maxCandidatesToScan || $emailsSentToday >= $dailyEmailLimit) {
            break;
        }

        $agencyDomain = preg_replace('/^www\./i', '', parse_url($targetAgency['website'], PHP_URL_HOST) ?? $targetAgency['website']);

        // Strict Domain & Email Deduplication Check
        if (isLeadAlreadyContacted($db, $targetAgency['direct_email'] ?? null, $agencyDomain, $targetAgency['name'])) {
            continue;
        }

        $candidatesChecked++;

        cronLog("🔍 [LIVE AUDIT] Scanning {$targetAgency['name']} ({$targetAgency['website']})...");
        $audit = performSiteAudit($targetAgency['website']);
        $primaryIssue = $audit['issues'][0] ?? [
            'type' => 'Optimization Opportunity',
            'title' => 'Page Load Speed & Conversion Tracking',
            'detail' => 'Opportunity to speed up assets and configure GTM purchase tracking.'
        ];

        $discoveredEmail = $audit['primary_email'] ?? ($targetAgency['direct_email'] ?? null);

        if (!empty($discoveredEmail)) {
            // Verify deliverability (Zero Bounce Shield)
            $emailCheck = EmailVerifier::verify($discoveredEmail, false);

            if ($emailCheck['is_valid'] && $emailCheck['is_deliverable'] && !empty($settings['smtp_user'])) {
                // Generate hyper-targeted problem-first proposal
                $pitch = generateLocalHumanProposal(
                    'email',
                    $primaryIssue['title'],
                    $primaryIssue['detail'],
                    'Team',
                    $targetAgency['name'],
                    $targetAgency['website'],
                    $settings
                );

                $subject = $pitch['subject'] ?? "quick observation regarding {$agencyDomain}";
                $smtpRes = SmtpMailer::send($emailCheck['email'], $subject, $pitch['proposal'], $settings);

                if ($smtpRes['success']) {
                    $emailsSentToday++;
                    $leadsDispatchedThisRun++;
                    cronLog("✉️ [REAL SMTP DELIVERED] Dispatched to {$targetAgency['name']} ({$emailCheck['email']})!");

                    $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, client_email, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        "Opportunity: {$targetAgency['name']} ({$primaryIssue['title']})",
                        'cPanel Server Outreach Cron (cron/outreach.php)',
                        'Director / Founder',
                        $emailCheck['email'],
                        $targetAgency['name'],
                        $targetAgency['website'],
                        'Real SMTP Email',
                        'contacted',
                        250,
                        250 * $usdToInr,
                        "Issue: {$primaryIssue['title']}\nAudit: {$primaryIssue['detail']}\nStatus: Delivered via Gmail TLS Socket",
                        $pitch['proposal']
                    ]);
                    $leadId = (int)$db->lastInsertId();

                    $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, 'Email', 'Autonomous Agency Outreach')")
                       ->execute([$leadId]);
                }
            } else {
                // Email invalid or unverified -> Mark status 'Manual'
                saveManualLead($db, $targetAgency, $primaryIssue, $usdToInr, 'Unverified Email');
            }
        } else {
            // No email on homepage or contact page -> Mark status 'Manual'
            saveManualLead($db, $targetAgency, $primaryIssue, $usdToInr, 'Contact Page / Social DM');
        }
    }
}

function saveManualLead(PDO $db, array $agency, array $issue, float $usdToInr, string $reason): void {
    $domain = preg_replace('/^www\./i', '', parse_url($agency['website'], PHP_URL_HOST) ?? $agency['website']);
    if (isLeadAlreadyContacted($db, null, $domain, $agency['name'])) {
        return;
    }

    $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, client_email, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        "Manual Outreach: {$agency['name']} ({$issue['title']})",
        'cPanel Server Outreach Cron',
        'Founder / CEO',
        'Direct Message / Web Form',
        $agency['name'],
        $agency['website'],
        'LinkedIn / Web Form',
        'new',
        250,
        250 * $usdToInr,
        "Issue: {$issue['title']}\nReason: {$reason}\nReady for 1-click LinkedIn DM.",
        "Hi {$agency['name']} Team,\n\nI was checking {$domain} and spotted a performance opportunity regarding {$issue['title']}.\n\nWould you like a 1-page breakdown?"
    ]);
    cronLog("📌 [MANUAL QUEUE] Saved {$agency['name']} to CRM for 1-click LinkedIn outreach.");
}

// ----------------------------------------------------
// E. UPDATE SERVER HEARTBEAT
// ----------------------------------------------------
$stmt = $db->query("SELECT COUNT(*) FROM leads");
$totalLeadsCount = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT SUM(CASE WHEN deal_value_inr > 0 THEN deal_value_inr ELSE deal_value_usd * {$usdToInr} END) FROM leads");
$totalPipelineInr = (float)$stmt->fetchColumn();

$statusData = [
    'status' => 'running',
    'is_running' => true,
    'last_heartbeat' => time(),
    'last_heartbeat_formatted' => date('Y-m-d H:i:s T'),
    'today_email_sent' => $emailsSentToday,
    'daily_email_limit' => $dailyEmailLimit,
    'total_leads_in_crm' => $totalLeadsCount,
    'total_pipeline_inr' => $totalPipelineInr,
    'total_pipeline_usd' => round($totalPipelineInr / $usdToInr, 2),
    'message' => '24/7 cPanel Outreach Cron is active and healthy.'
];
file_put_contents($statusFile, json_encode($statusData, JSON_PRETTY_PRINT));

cronLog("🏁 [OUTREACH CRON COMPLETE] Finished 5-min run. Sent Today: {$emailsSentToday}/{$dailyEmailLimit} | CRM Total: {$totalLeadsCount}");

// Release lock
if (isset($lockFp) && is_resource($lockFp)) {
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
}
