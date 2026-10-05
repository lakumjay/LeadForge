<?php
/**
 * LeadForge AI - Autonomous Auto-Pilot Background Engine
 * Scans, Audits, Verifies Emails, Dispatches Real SMTP Emails, and Logs 3-Stage Followups
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../smtp_mailer.php';
require_once __DIR__ . '/../email_verifier.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'status';
$statusFile = DATA_PATH . '/autopilot_status.json';

if (!file_exists($statusFile)) {
    file_put_contents($statusFile, json_encode([
        'is_running' => false,
        'last_run' => null,
        'total_dispatched' => 0,
        'today_dispatched' => 0,
        'smtp_status' => 'Pending Config',
        'logs' => []
    ]));
}

$statusData = json_decode(file_get_contents($statusFile), true) ?: [];

try {
    switch ($action) {
        case 'status':
            $settings = getSettings();
            $statusData['smtp_configured'] = !empty($settings['smtp_user']) && !empty($settings['smtp_pass']);
            echo json_encode(['status' => 'success', 'data' => $statusData]);
            break;

        case 'toggle':
            $enable = isset($_POST['enable']) ? (bool)$_POST['enable'] : !$statusData['is_running'];
            $statusData['is_running'] = $enable;
            
            if ($enable) {
                addAutopilotLog($statusData, "🤖 Auto-Pilot Engine ACTIVATED. Running autonomous outreach worker...");
                runAutopilotCycle($statusData);
            } else {
                addAutopilotLog($statusData, "⏸️ Auto-Pilot Engine PAUSED.");
            }

            file_put_contents($statusFile, json_encode($statusData, JSON_PRETTY_PRINT));
            echo json_encode(['status' => 'success', 'data' => $statusData]);
            break;

        case 'trigger_cycle':
            if (!empty($statusData['is_running'])) {
                runAutopilotCycle($statusData);
                file_put_contents($statusFile, json_encode($statusData, JSON_PRETTY_PRINT));
            }
            echo json_encode(['status' => 'success', 'data' => $statusData]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function addAutopilotLog(array &$statusData, string $message): void {
    $time = date('H:i:s');
    if (!isset($statusData['logs'])) $statusData['logs'] = [];
    array_unshift($statusData['logs'], "[{$time}] {$message}");
    $statusData['logs'] = array_slice($statusData['logs'], 0, 25);
}

/**
 * Execute 1 Autonomous Outreach Cycle (With Real SMTP Transmission & Verification)
 */
function runAutopilotCycle(array &$statusData): void {
    $db = Database::getConnection();
    $settings = getSettings();
    $now = time();

    // Check last run time (Ensure safe 90-180s human delay)
    $lastRun = $statusData['last_run'] ? strtotime($statusData['last_run']) : 0;
    if (($now - $lastRun) < 90) {
        addAutopilotLog($statusData, "⏳ Safe human delay active. Next auto-pitch in " . (90 - ($now - $lastRun)) . "s...");
        return;
    }

    // Step 1: Pick an agency from directory
    require_once __DIR__ . '/agency.php';
    
    $stmt = $db->query("SELECT company FROM leads WHERE company IS NOT NULL");
    $existingCompanies = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $targetAgency = null;
    foreach ($agencies as $a) {
        if (!in_array($a['name'], $existingCompanies)) {
            $targetAgency = $a;
            break;
        }
    }

    if (!$targetAgency) {
        $targetAgency = [
            'name' => 'Growth Scale Agency ' . rand(10, 99),
            'website' => 'https://singlegrain.com',
            'country' => 'United States',
            'city' => 'Los Angeles, CA',
            'category' => 'SEO, Google Ads & Web',
            'outreach_angle' => 'Technical SEO & Conversion Tracking Partner'
        ];
    }

    addAutopilotLog($statusData, "🎯 Target Selected: {$targetAgency['name']} ({$targetAgency['city']}, {$targetAgency['country']})");

    // Step 2: Perform Real-Time Website & Tracking Audit
    require_once __DIR__ . '/audit.php';
    addAutopilotLog($statusData, "🔍 Auditing live website: {$targetAgency['website']} (Web, SEO & Ads Tracking)...");
    
    $audit = performSiteAudit($targetAgency['website']);
    $primaryIssue = $audit['issues'][0] ?? [
        'type' => 'Optimization Opportunity',
        'title' => 'Page Load Speed & Conversion Tracking',
        'detail' => 'Opportunity to speed up assets and configure GTM purchase tracking.',
        'solution' => 'Enable WebP asset caching and GTM server container.'
    ];

    addAutopilotLog($statusData, "✅ Found Audit Opportunity: {$primaryIssue['title']} ({$audit['response_time']})");

    // Step 3: Extract and Strictly Verify Authentic Target Email
    $cleanDomain = $audit['clean_domain'] ?? preg_replace('/^www\./i', '', parse_url($targetAgency['website'], PHP_URL_HOST));
    $discoveredEmail = $audit['primary_email'] ?? $targetAgency['contact_email'] ?? null;
    
    $candidateEmail = null;
    $isDeliverable = false;
    $finalEmail = null;
    $emailCheck = ['is_valid' => false, 'is_deliverable' => false, 'reason' => 'No public email on homepage'];

    if (!empty($discoveredEmail)) {
        $candidateEmail = $discoveredEmail;
        $emailCheck = EmailVerifier::verify($candidateEmail, false);
        $isDeliverable = $emailCheck['is_valid'] && $emailCheck['is_deliverable'];
        $finalEmail = $isDeliverable ? $emailCheck['email'] : null;
    }

    // Step 4: Generate High-Converting Pitch
    require_once __DIR__ . '/generate.php';
    $pitch = generateLocalHumanProposal(
        'email',
        "Dev, SEO & Tracking support for {$targetAgency['name']}",
        $primaryIssue['detail'] ?? 'Optimization',
        'Team',
        $targetAgency['name'],
        $targetAgency['website'],
        $settings
    );

    // Step 5: Conditional Real SMTP Transmission (NEVER SEND IF UNVERIFIED!)
    $dealUsd = 150;
    $dealInr = $dealUsd * (float)($settings['usd_to_inr'] ?? 86.5);
    $smtpResult = null;

    if ($isDeliverable && !empty($finalEmail)) {
        $smtpResult = SmtpMailer::send(
            $finalEmail,
            $pitch['subject'] ?? "quick question regarding {$cleanDomain}",
            $pitch['proposal'],
            $settings
        );

        $dispatchStatus = $smtpResult['success'] ? 'contacted' : 'new';
        $dispatchNote = $smtpResult['message'];

        if ($smtpResult['success']) {
            addAutopilotLog($statusData, "🚀 REAL SMTP DELIVERED: {$targetAgency['name']} ({$finalEmail})");
            triggerMacNotification("LeadForge Auto-Pilot", "Dispatched to {$targetAgency['name']} ({$finalEmail})!");
        } else {
            addAutopilotLog($statusData, "⚠️ SMTP notice for {$targetAgency['name']}: {$dispatchNote}");
        }
    } else {
        $dispatchStatus = 'new';
        $dispatchNote = "Anti-Bounce Shield Protected: Email unverified on @{$cleanDomain} ({$emailCheck['reason']}). Queued for LinkedIn / Web contact.";
        addAutopilotLog($statusData, "🛡️ BOUNCE SHIELD: No verified email on @{$cleanDomain}. Skipped SMTP to prevent bounce. Added to CRM.");
    }

    // Step 6: Save Deal to CRM Pipeline
    $insStmt = $db->prepare("INSERT INTO leads (title, source, client_name, client_email, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insStmt->execute([
        "Opportunity: {$targetAgency['name']} ({$primaryIssue['title']})",
        'Auto-Pilot Engine',
        'Team Lead',
        $finalEmail ?? "Contact Form / LinkedIn",
        $targetAgency['name'],
        $targetAgency['website'],
        $isDeliverable ? 'Real SMTP Email' : 'LinkedIn / Web Contact',
        $dispatchStatus,
        $dealUsd,
        $dealInr,
        "Issue: {$primaryIssue['title']}\nAudit Note: {$primaryIssue['detail']}\nShield: {$dispatchNote}",
        $pitch['proposal']
    ]);

    $leadId = (int)$db->lastInsertId();

    // Log Outreach for Safety Shield
    $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, ?, ?)")
       ->execute([$leadId, $isDeliverable ? 'Email' : 'LinkedIn', 'Autonomous Audit']);

    // Update Status Counters
    $statusData['last_run'] = date('Y-m-d H:i:s');
    $statusData['total_dispatched'] = ($statusData['total_dispatched'] ?? 0) + ($isDeliverable ? 1 : 0);
    $statusData['today_dispatched'] = ($statusData['today_dispatched'] ?? 0) + ($isDeliverable ? 1 : 0);
    $statusData['last_target'] = $targetAgency['name'];
}

function triggerMacNotification(string $title, string $message): void {
    if (PHP_OS_FAMILY === 'Darwin') {
        $cleanTitle = addslashes($title);
        $cleanMsg = addslashes($message);
        @exec("osascript -e \"display notification \\\"{$cleanMsg}\\\" with title \\\"{$cleanTitle}\\\" sound name \\\"Glass\\\"\" > /dev/null 2>&1 &");
    }
}
