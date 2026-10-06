<?php
/**
 * LeadForge AI - 24/7 Perpetual Autonomous Outreach Daemon
 * 
 * Runs continuously 24/7 in background (Day & Night)
 * - Auto-rotates across all target countries (US, UK, Canada, Australia)
 * - Audits real digital agencies & delivers genuine pitches via Gmail SMTP
 * - Generates high-velocity mass multiplier leads into SQLite CRM
 * - Prepares active LinkedIn connection requests within 20/day safety quota
 * - Streams and auto-pitches fresh radar bounties (<24h)
 * - Auto self-heals and runs forever
 */

declare(strict_types=1);

// Prevent script timeout
set_time_limit(0);
ignore_user_abort(true);
ini_set('display_errors', '0');
error_reporting(E_ALL);

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

$daemonStatusFile = DATA_PATH . '/daemon_status.json';
$logFile = DATA_PATH . '/daemon.log';

function daemonLog(string $msg): void {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    $line = "[{$time}] {$msg}\n";
    @file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    daemonLog("⚠️ [PHP NOTICE] {$errstr} in " . basename($errfile) . ":{$errline}");
    return true;
});

daemonLog("🚀 ========================================================");
daemonLog("🚀 LeadForge AI 24/7 Autonomous Master Daemon Online!");
daemonLog("🚀 Target Goal: ₹50,000+/month ($600+ USD) Client Acquisition Engine");
daemonLog("🚀 Running 100% Hands-Free Day & Night across US, UK, CA, AU, India, UAE, SG & Global");
daemonLog("🚀 ========================================================");

$countries = ['United States', 'United Kingdom', 'Canada', 'Australia', 'India', 'United Arab Emirates', 'Singapore', 'Germany', 'Netherlands', 'Ireland', 'New Zealand', 'France', 'Saudi Arabia'];
$categories = ['ecommerce', 'no_website', 'agency', 'saas_tech', 'all'];

$countryIdx = 0;
$categoryIdx = 0;
$agencyIdx = 0;
$cycleCount = 0;
$totalSmtpSent = 0;
$totalLeadsProcessed = 0;
$totalLinkedInSent = 0;
$startTime = time();

while (true) {
    try {
        $cycleCount++;
        $currentCountry = $countries[$countryIdx % count($countries)];
        $currentCategory = $categories[$categoryIdx % count($categories)];
        $countryIdx++;
        $categoryIdx++;

        $settings = getSettings();
        $db = Database::getConnection();
        $usdToInr = (float)($settings['usd_to_inr'] ?? 86.5);
        $dailyEmailLimit = (int)($settings['daily_email_limit'] ?? 500);
        $dailyLinkedInLimit = (int)($settings['daily_linkedin_limit'] ?? 20);

        // Check today's email count
        $stmt = $db->query("SELECT COUNT(*) FROM outreach_logs WHERE platform = 'Email' AND DATE(created_at) = DATE('now')");
        $emailsSentToday = (int)$stmt->fetchColumn();

        // Check today's LinkedIn count
        $stmt = $db->query("SELECT COUNT(*) FROM outreach_logs WHERE platform = 'LinkedIn' AND DATE(created_at) = DATE('now')");
        $linkedInSentToday = (int)$stmt->fetchColumn();

        daemonLog("--- Cycle #{$cycleCount} | Target: {$currentCountry} | Category: {$currentCategory} | Today: (Email: {$emailsSentToday}/{$dailyEmailLimit}, LI: {$linkedInSentToday}/{$dailyLinkedInLimit}) ---");

        // ----------------------------------------------------
        // ACTION 1: REAL AGENCY WEBSITE AUDIT & REAL SMTP DISPATCH
        // ----------------------------------------------------
        if ($emailsSentToday < $dailyEmailLimit && !empty($agencies)) {
            $agenciesCount = count($agencies);
            $foundAgency = null;
            
            // Find an agency not contacted in the last 30 days
            for ($k = 0; $k < $agenciesCount; $k++) {
                $candidateAgency = $agencies[($agencyIdx + $k) % $agenciesCount];
                $candDomain = preg_replace('/^www\./i', '', parse_url($candidateAgency['website'], PHP_URL_HOST));
                
                if (!isLeadAlreadyContacted($db, $candidateAgency['direct_email'] ?? null, $candDomain, $candidateAgency['name'])) {
                    $foundAgency = $candidateAgency;
                    $agencyIdx = ($agencyIdx + $k + 1) % $agenciesCount;
                    break;
                }
            }

            if ($foundAgency) {
                $targetAgency = $foundAgency;
                $agencyDomain = preg_replace('/^www\./i', '', parse_url($targetAgency['website'], PHP_URL_HOST));
                daemonLog("🔍 [LIVE AUDIT] Scanning {$targetAgency['name']} ({$targetAgency['website']})...");

                $audit = performSiteAudit($targetAgency['website']);
                $primaryIssue = $audit['issues'][0] ?? [
                    'type' => 'Optimization Opportunity',
                    'title' => 'Page Load Speed & Conversion Tracking',
                    'detail' => 'Opportunity to speed up assets and configure GTM purchase tracking.'
                ];
                $discoveredEmail = $audit['primary_email'] ?? ($targetAgency['direct_email'] ?? null);

                if (!empty($discoveredEmail)) {
                    if (isLeadAlreadyContacted($db, $discoveredEmail, $agencyDomain, $targetAgency['name'])) {
                        daemonLog("⏭️ [DUPLICATE GUARD] {$discoveredEmail} ({$agencyDomain}) was already contacted. Skipping.");
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
                            $totalSmtpSent++;
                            daemonLog("✉️ [VERIFIED REAL SMTP DELIVERED] Dispatched to {$targetAgency['name']} ({$emailCheck['email']})!");
                            
                            // Trigger Mac Notification
                            if (PHP_OS_FAMILY === 'Darwin') {
                                @exec("osascript -e 'display notification \"Real email sent to {$targetAgency['name']}!\" with title \"LeadForge AI Outbox\" sound name \"Glass\"' > /dev/null 2>&1 &");
                            }

                            // Save to CRM
                            $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, client_email, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                "Opportunity: {$targetAgency['name']} ({$primaryIssue['title']})",
                                '24/7 Autonomous Daemon',
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
                            
                            sleep(10);
                        }
                    }
                } else {
                    $socials = $audit['social_profiles'] ?? [];
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

                    daemonLog("🎯 [MULTI-CHANNEL EXTRACTED] {$targetAgency['name']} -> {$targetPlatform} ({$targetIdentifier}). Saved to CRM.");

                    $dmPitch = "Hi {$targetAgency['name']} Team,\n\nI was checking {$agencyDomain} and spotted a quick performance opportunity regarding {$primaryIssue['title']}.\n\nI specialize in fast turnaround web fixes, Laravel backend and speed optimizations for agencies.\n\nWould you like a quick 2-minute Loom/breakdown?\n\nBest,\nJay";
                    
                    // Save safely as Multi-Channel / Social lead to CRM
                    $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, client_email, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        "Opportunity: {$targetAgency['name']} ({$primaryIssue['title']})",
                        '24/7 Autonomous Daemon',
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
            } else {
                daemonLog("ℹ️ All agencies in current list have been processed recently. Rotating to next cycle.");
            }
        }

        // ----------------------------------------------------
        // ACTION 2: GOOGLE LINKEDIN SALES NAVIGATOR FOUNDER OUTREACH
        // ----------------------------------------------------
        $founders = scanGoogleSalesNavigatorDorks($currentCountry, $currentCategory, 2);
        foreach ($founders as $fProspect) {
            $fRes = processFounderProspect($fProspect, $db, $settings, $usdToInr);
            if ($fRes['smtp_delivered']) {
                $emailsSentToday++;
                $totalSmtpSent++;
                daemonLog("👔 [FOUNDER SMTP DELIVERED] Dispatched to {$fRes['founder']} ({$fRes['company']}) - [{$fRes['issue']}]");
            }
        }

        // ----------------------------------------------------
        // ACTION 3: AUTOMATED 3-STAGE SMART FOLLOW-UP SEQUENCE
        // ----------------------------------------------------
        $followupResults = runAutomatedFollowups($db, $settings);
        foreach ($followupResults as $fu) {
            if ($fu['success']) {
                $emailsSentToday++;
                $totalSmtpSent++;
                daemonLog("🔁 [AUTO FOLLOW-UP SENT] {$fu['stage']} -> {$fu['recipient']}");
            }
        }

        // ----------------------------------------------------
        // ACTION 4: MASS MULTIPLIER ROTATING BATCH
        // ----------------------------------------------------
        $batchSize = rand(10, 20);
        $massLeads = generateMassLeadsList($currentCategory, $currentCountry, $cities[$currentCountry] ?? $cities['United States'], $localNiches, $ecomNiches, $agencyNiches, $batchSize, $usdToInr);

        foreach ($massLeads as $lead) {
            $totalLeadsProcessed++;
            $res = processAndDispatchSingleLead($lead, $db, $settings, $usdToInr);
            if ($res['smtp_delivered']) {
                $totalSmtpSent++;
                $emailsSentToday++;
                daemonLog("✉️ [MASS SMTP DELIVERED] {$res['name']} ({$res['email']}) - \${$res['deal_usd']}");
            }
        }
        daemonLog("✅ Processed {$batchSize} mass opportunities for {$currentCountry} ({$currentCategory}).");

        // ----------------------------------------------------
        // ACTION 5: LINKEDIN ACTIVE PROSPECT AUTO-CONNECT
        // ----------------------------------------------------
        $prospectList = !empty($activeProspects) ? $activeProspects : ($prospects ?? []);
        if ($linkedInSentToday < $dailyLinkedInLimit && !empty($prospectList)) {
            $stmt = $db->query("SELECT client_name FROM leads WHERE platform = 'LinkedIn'");
            $alreadyContacted = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($prospectList as $prospect) {
                if ($linkedInSentToday >= $dailyLinkedInLimit) break;

                if (!in_array($prospect['name'], $alreadyContacted)) {
                    $dealUsd = (float)($prospect['deal_usd'] ?? 250);
                    $dealInr = $dealUsd * $usdToInr;

                    $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, company, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        "LinkedIn Outreach: {$prospect['name']} ({$prospect['company']})",
                        '24/7 Autonomous Daemon',
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
                    $totalLinkedInSent++;
                    daemonLog("🤖 [LINKEDIN AUTO-CONNECT] Dispatched: {$prospect['name']} ({$prospect['company']}) - \${$dealUsd}");
                    break;
                }
            }
        }

        // ----------------------------------------------------
        // ACTION 4: UPDATE METRICS & HEARTBEAT
        // ----------------------------------------------------
        $stmt = $db->query("SELECT SUM(CASE WHEN deal_value_inr > 0 THEN deal_value_inr ELSE deal_value_usd * {$usdToInr} END) FROM leads");
        $totalPipelineInr = (float)$stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM leads");
        $totalLeadsCount = (int)$stmt->fetchColumn();

        $uptimeSec = time() - $startTime;
        $uptimeHours = round($uptimeSec / 3600, 1);

        $daemonStatus = [
            'status' => 'running',
            'is_running' => true,
            'started_at' => date('Y-m-d H:i:s', $startTime),
            'last_heartbeat' => date('Y-m-d H:i:s'),
            'uptime_hours' => $uptimeHours,
            'cycle_count' => $cycleCount,
            'current_country' => $currentCountry,
            'current_category' => $currentCategory,
            'today_email_sent' => $emailsSentToday,
            'daily_email_limit' => $dailyEmailLimit,
            'today_linkedin_sent' => $linkedInSentToday,
            'daily_linkedin_limit' => $dailyLinkedInLimit,
            'total_leads_in_crm' => $totalLeadsCount,
            'total_pipeline_inr' => $totalPipelineInr,
            'total_pipeline_usd' => round($totalPipelineInr / $usdToInr, 2),
            'message' => "24/7 Autonomous Daemon is actively scanning & pitching leads across {$currentCountry}."
        ];

        file_put_contents($daemonStatusFile, json_encode($daemonStatus, JSON_PRETTY_PRINT));

        // ----------------------------------------------------
        // ACTION 5: SAFE HUMAN-LIKE DELAY (45-75 seconds)
        // ----------------------------------------------------
        $sleepSeconds = rand(45, 75);
        daemonLog("⏳ Sleeping for {$sleepSeconds}s before next auto-cycle...\n");
        sleep($sleepSeconds);

    } catch (Throwable $e) {
        daemonLog("⚠️ [SELF-HEALING ERROR HANDLER] Caught exception: " . $e->getMessage() . " on line " . $e->getLine());
        sleep(30);
    }
}
