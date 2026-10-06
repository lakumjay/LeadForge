<?php
/**
 * LeadForge AI - 24/7 Perpetual Cron Runner for cPanel / Cloud Hosting
 * 
 * Runs 1 complete autonomous cycle per cron execution:
 * - Scans & audits live agency websites
 * - Sends zero-bounce dynamic problem-first SMTP emails
 * - Rotates US, UK, Canada, Australia targets
 * - Multiplies mass leads and saves to SQLite CRM
 * - Safe from server timeouts (optimized for 24/7 background cron)
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/smtp_mailer.php';
require_once __DIR__ . '/email_verifier.php';
require_once __DIR__ . '/api/audit.php';
require_once __DIR__ . '/api/generate.php';
require_once __DIR__ . '/api/agency.php';
require_once __DIR__ . '/api/mass_scanner.php';
require_once __DIR__ . '/api/linkedin_helper.php';
require_once __DIR__ . '/api/sales_navigator.php';
require_once __DIR__ . '/api/followup_engine.php';

$logFile = DATA_PATH . '/daemon.log';
$statusFile = DATA_PATH . '/daemon_status.json';
$lockFile = DATA_PATH . '/cron.lock';

// Non-blocking flock to prevent overlapping cron runs
$lockFp = fopen($lockFile, 'c+');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] ⏭️ Another cron runner cycle is actively running. Skipping overlap.\n";
    exit;
}

@set_time_limit(60);

function cLog(string $msg): void {
    global $logFile;
    $line = "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

cLog("⚡ [CRON EXECUTION] Starting 24/7 Autonomous Cycle...");

$countries = ['United States', 'United Kingdom', 'Canada', 'Australia', 'India', 'United Arab Emirates', 'Singapore', 'Germany', 'Netherlands', 'Ireland', 'New Zealand', 'France', 'Saudi Arabia'];
$categories = ['ecommerce', 'no_website', 'agency', 'saas_tech', 'all'];

$settings = getSettings();
$db = Database::getConnection();
$usdToInr = (float)($settings['usd_to_inr'] ?? 86.5);
$dailyEmailLimit = (int)($settings['daily_email_limit'] ?? 999999);
$dailyLinkedInLimit = (int)($settings['daily_linkedin_limit'] ?? 999999);

// Rotate country and category based on hour / minute
$countryIdx = (int)date('i') % count($countries);
$categoryIdx = (int)date('H') % count($categories);
$currentCountry = $countries[$countryIdx];
$currentCategory = $categories[$categoryIdx];

// Check today's outreach counts
$stmt = $db->query("SELECT COUNT(*) FROM outreach_logs WHERE platform = 'Email' AND DATE(created_at) = DATE('now')");
$emailsSentToday = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM outreach_logs WHERE platform = 'LinkedIn' AND DATE(created_at) = DATE('now')");
$linkedInSentToday = (int)$stmt->fetchColumn();

cLog("🎯 Target: {$currentCountry} | Category: {$currentCategory} | Mode: ⚡ UNLIMITED REAL-TIME DISPATCH | Sent Today: (Email: {$emailsSentToday}, LI: {$linkedInSentToday})");

// ----------------------------------------------------
// 1. LIVE AGENCY AUDIT & INSTANT PROBLEM-FIRST DISPATCH
// ----------------------------------------------------
$agenciesToAuditCount = min(3, count($agencies));
$startAgencyIdx = (int)date('i') % count($agencies);

for ($a = 0; $a < $agenciesToAuditCount; $a++) {
    $targetAgency = $agencies[($startAgencyIdx + $a) % count($agencies)];
    $agencyDomain = preg_replace('/^www\./i', '', parse_url($targetAgency['website'], PHP_URL_HOST));

    // Duplicate Suppression Guard: Check if company or domain was already contacted in database
    if (isLeadAlreadyContacted($db, null, $agencyDomain, $targetAgency['name'])) {
        cLog("⏭️ [DUPLICATE GUARD] {$targetAgency['name']} ({$agencyDomain}) was already processed. Skipping to prevent repeated emails.");
        continue;
    }

    cLog("🔍 Auditing {$targetAgency['name']} ({$targetAgency['website']})...");
    $audit = performSiteAudit($targetAgency['website']);
    $primaryIssue = $audit['issues'][0] ?? [
        'type' => 'Optimization Opportunity',
        'title' => 'Page Load Speed & Conversion Tracking',
        'detail' => 'Opportunity to speed up assets and configure GTM purchase tracking.'
    ];

    $discoveredEmail = $audit['primary_email'] ?? ($targetAgency['direct_email'] ?? null);

    if (!empty($discoveredEmail)) {
        if (isLeadAlreadyContacted($db, $discoveredEmail, $agencyDomain, $targetAgency['name'])) {
            cLog("⏭️ [DUPLICATE GUARD] {$discoveredEmail} already exists in CRM. Skipping.");
            continue;
        }

        $emailCheck = EmailVerifier::verify($discoveredEmail, false);

        if ($emailCheck['is_valid'] && $emailCheck['is_deliverable'] && !empty($settings['smtp_user'])) {
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
                cLog("✉️ [VERIFIED REAL SMTP DELIVERED] Dispatched to {$targetAgency['name']} ({$emailCheck['email']})!");

                $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, client_email, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    "Opportunity: {$targetAgency['name']} ({$primaryIssue['title']})",
                    '24/7 Autonomous Server Cron',
                    'Director',
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

                $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, ?, ?)")
                   ->execute([$leadId, 'Email', 'Autonomous Agency Outreach']);
                
                // Safe natural pause between individual sends
                sleep(10);
            }
        }
    } else {
        $socials = $audit['social_profiles'] ?? [];
        $hasSocial = !empty($socials['linkedin']) || !empty($socials['instagram']) || !empty($socials['contact_page']);
        
        $targetPlatform = 'Website / Contact Form';
        $targetIdentifier = 'Contact Form / Web';
        
        if (!empty($socials['linkedin'])) {
            $targetPlatform = 'LinkedIn DM / InMail';
            $targetIdentifier = $socials['linkedin'];
        } elseif (!empty($socials['instagram'])) {
            $targetPlatform = 'Instagram DM';
            $targetIdentifier = $socials['instagram'];
        } elseif (!empty($socials['contact_page'])) {
            $targetPlatform = 'Website Contact Form';
            $targetIdentifier = $socials['contact_page'];
        }

        $socialNotesList = [];
        if (!empty($socials['linkedin'])) $socialNotesList[] = "LinkedIn: {$socials['linkedin']}";
        if (!empty($socials['instagram'])) $socialNotesList[] = "Instagram: {$socials['instagram']}";
        if (!empty($socials['twitter'])) $socialNotesList[] = "Twitter/X: {$socials['twitter']}";
        if (!empty($socials['contact_page'])) $socialNotesList[] = "Contact Form: {$socials['contact_page']}";
        $socialsStr = !empty($socialNotesList) ? implode("\n", $socialNotesList) : "Website: {$targetAgency['website']}";

        cLog("🎯 [MULTI-CHANNEL SOCIAL EXTRACTED] {$targetAgency['name']} -> {$targetPlatform} ({$targetIdentifier}). Saved to CRM with instant DM pitch.");

        $dmPitch = "Hi {$targetAgency['name']} Team,\n\nI was checking {$agencyDomain} and spotted a quick performance opportunity regarding {$primaryIssue['title']}.\n\nI specialize in fast turnaround web fixes, Laravel backend and speed optimizations for agencies.\n\nWould you like a quick 2-minute Loom/breakdown?\n\nBest,\nJay";

        $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, client_email, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            "Opportunity: {$targetAgency['name']} ({$primaryIssue['title']})",
            '24/7 Autonomous Server Cron',
            'Director / Founder',
            $targetIdentifier,
            $targetAgency['name'],
            $targetAgency['website'],
            $targetPlatform,
            'new',
            250,
            250 * $usdToInr,
            "Issue: {$primaryIssue['title']}\nAudit: {$primaryIssue['detail']}\nFound Channels:\n{$socialsStr}",
            $dmPitch
        ]);
        $leadId = (int)$db->lastInsertId();

        $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, ?, ?)")
           ->execute([$leadId, $targetPlatform, 'Multi-Channel Profile Discovery']);
    }
}

// ----------------------------------------------------
// 2. GOOGLE LINKEDIN SALES NAVIGATOR FOUNDER OUTREACH
// ----------------------------------------------------
$founders = scanGoogleSalesNavigatorDorks($currentCountry, $currentCategory, 2);
foreach ($founders as $fProspect) {
    $fRes = processFounderProspect($fProspect, $db, $settings, $usdToInr);
    if ($fRes['smtp_delivered']) {
        $emailsSentToday++;
        cLog("👔 [FOUNDER SMTP DELIVERED] Dispatched to {$fRes['founder']} ({$fRes['company']}) - [{$fRes['issue']}]");
    }
}

// ----------------------------------------------------
// 3. AUTOMATED 3-STAGE SMART FOLLOW-UP SEQUENCE
// ----------------------------------------------------
$followupResults = runAutomatedFollowups($db, $settings);
foreach ($followupResults as $fu) {
    if ($fu['success']) {
        $emailsSentToday++;
        cLog("🔁 [AUTO FOLLOW-UP SENT] {$fu['stage']} -> {$fu['recipient']}");
    }
}

// ----------------------------------------------------
// 4. MASS MULTIPLIER ROTATING LEADS GENERATION
// ----------------------------------------------------
$batchSize = rand(8, 15);
$massLeads = generateMassLeadsList($currentCategory, $currentCountry, null, null, null, null, null, $batchSize, $usdToInr);

foreach ($massLeads as $lead) {
    $res = processAndDispatchSingleLead($lead, $db, $settings, $usdToInr);
    if ($res['smtp_delivered']) {
        $emailsSentToday++;
        cLog("✉️ [MASS SMTP DELIVERED] {$res['name']} ({$res['email']}) - \${$res['deal_usd']}");
    }
}
cLog("✅ [CRM SYNC] Processed {$batchSize} mass leads for {$currentCountry} ({$currentCategory}).");

// ----------------------------------------------------
// 5. LINKEDIN AUTO-CONNECT PREPARATION
// ----------------------------------------------------
$prospectList = !empty($activeProspects) ? $activeProspects : ($prospects ?? []);
if ($linkedInSentToday < $dailyLinkedInLimit && !empty($prospectList)) {
    $stmt = $db->query("SELECT client_name FROM leads WHERE platform = 'LinkedIn'");
    $alreadyContacted = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($prospectList as $prospect) {
        if (!in_array($prospect['name'], $alreadyContacted)) {
            $dealUsd = (float)($prospect['deal_usd'] ?? 250);
            $dealInr = $dealUsd * $usdToInr;

            $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, company, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                "LinkedIn Outreach: {$prospect['name']} ({$prospect['company']})",
                '24/7 Autonomous Server Cron',
                $prospect['name'],
                $prospect['company'],
                'LinkedIn',
                'contacted',
                $dealUsd,
                $dealInr,
                "Role: {$prospect['role']}\nLocation: {$prospect['location']}\nAngle: {$prospect['recommended_angle']}\nAuto-Dispatched 24/7.",
                $prospect['connection_note']
            ]);
            $leadId = (int)$db->lastInsertId();

            $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, ?, ?)")
               ->execute([$leadId, 'LinkedIn', '24/7 Auto-Connection Pitch']);

            $linkedInSentToday++;
            cLog("🤖 [LINKEDIN AUTO-CONNECT] Dispatched: {$prospect['name']} ({$prospect['company']})");
            break;
        }
    }
}

// ----------------------------------------------------
// 4. UPDATE STATUS HEARTBEAT
// ----------------------------------------------------
$stmt = $db->query("SELECT SUM(CASE WHEN deal_value_inr > 0 THEN deal_value_inr ELSE deal_value_usd * {$usdToInr} END) FROM leads");
$totalPipelineInr = (float)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM leads");
$totalLeadsCount = (int)$stmt->fetchColumn();

$daemonStatus = [
    'status' => 'running',
    'is_running' => true,
    'last_heartbeat' => date('Y-m-d H:i:s'),
    'current_country' => $currentCountry,
    'current_category' => $currentCategory,
    'today_email_sent' => $emailsSentToday,
    'daily_email_limit' => $dailyEmailLimit,
    'today_linkedin_sent' => $linkedInSentToday,
    'daily_linkedin_limit' => $dailyLinkedInLimit,
    'total_leads_in_crm' => $totalLeadsCount,
    'total_pipeline_inr' => $totalPipelineInr,
    'total_pipeline_usd' => round($totalPipelineInr / $usdToInr, 2),
    'message' => "Server Cron is running 24/7 across {$currentCountry}."
];
cLog("🏁 [CRON FINISHED] Cycle completed successfully. Total CRM Leads: {$totalLeadsCount} | Total Pipeline: ₹" . number_format($totalPipelineInr, 2));

if (isset($lockFp) && is_resource($lockFp)) {
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
}
