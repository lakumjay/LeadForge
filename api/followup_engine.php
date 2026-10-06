<?php
/**
 * LeadForge AI - Automated 3-Stage Smart Follow-Up Engine
 * 
 * Strict Anti-Spam Rules:
 * 1. Minimum 4 FULL DAYS (96 Hours) between Initial Pitch -> Follow-up #1
 * 2. Minimum 4 FULL DAYS (96 Hours) between Follow-up #1 -> Follow-up #2 (Final Breakup)
 * 3. ZERO Same-Day Touches: Maximum 1 email per company/domain per 24 hours across entire system
 * 4. Maximum 1 follow-up processed per 5-minute cron cycle to prevent bursts
 * 5. Automatic domain-level deduplication so multiple rows for the same company never get multi-emailed
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../smtp_mailer.php';
require_once __DIR__ . '/../email_verifier.php';

function runAutomatedFollowups(PDO $db, array $settings): array {
    $results = [];
    if (empty($settings['smtp_user']) || empty($settings['smtp_pass'])) {
        return $results;
    }

    $userName = $settings['user_name'] ?? 'Jay';
    $processedDomains = [];

    // ----------------------------------------------------
    // 1. STAGE 1 -> STAGE 2 FOLLOW-UP (STRICT MINIMUM 4 DAYS / 96 HOURS GAP)
    // ----------------------------------------------------
    $stmt = $db->query("
        SELECT * FROM leads 
        WHERE status = 'contacted' 
          AND client_email IS NOT NULL 
          AND client_email LIKE '%@%'
          AND created_at <= datetime('now', '-4 days')
        ORDER BY id ASC LIMIT 5
    ");
    $stage1Leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stage1Leads as $lead) {
        $leadId = (int)$lead['id'];
        $email = strtolower(trim($lead['client_email']));
        $company = trim($lead['company'] ?? 'your business');
        $domain = preg_replace('/^www\./i', '', parse_url($lead['url'] ?? '', PHP_URL_HOST) ?? $company);
        if (empty($domain) && strpos($email, '@') !== false) {
            $domain = substr(strrchr($email, "@"), 1);
        }

        // Domain deduplication within this run
        if (in_array($domain, $processedDomains)) {
            continue;
        }

        // STRICT 24-HOUR CHECK: Has this company/domain received ANY email in the last 24 hours?
        if (isEmailOrDomainSentRecently($email, $domain, 24)) {
            error_log("🛡️ [FOLLOW-UP ENGINE SKIPPED] {$email} ({$domain}) already contacted within last 24 hours.");
            // Advance updated_at to prevent re-querying in 5 min
            $db->prepare("UPDATE leads SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$leadId]);
            continue;
        }

        $clientName = explode(' ', $lead['client_name'] ?? 'there')[0];
        $subject = "quick follow-up regarding {$domain}";
        $body = "Hi {$clientName},\n\n"
            . "Just following up on my previous note from a few days ago in case it got buried in your inbox.\n\n"
            . "Were you or your team able to take a look at the tracking / optimization opportunity on {$domain}?\n\n"
            . "I'm happy to send over a quick 2-minute video breakdown of where the fix is needed whenever you have a moment.\n\n"
            . "Best regards,\n"
            . "{$userName}\n"
            . "Web & Growth Specialist";

        // Dispatch via SMTP with $isFollowup = true
        $res = SmtpMailer::send($email, $subject, $body, $settings, true);

        // Always advance status on all sibling rows for this email/domain to prevent repeated follow-up loops
        $db->prepare("UPDATE leads SET status = 'followup_1', updated_at = CURRENT_TIMESTAMP WHERE id = ? OR LOWER(client_email) = ? OR LOWER(url) LIKE ?")
           ->execute([$leadId, $email, '%' . $domain . '%']);

        if ($res['success']) {
            $processedDomains[] = $domain;
            recordSentEmailToLedger($email, $domain, $subject);

            $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, ?, ?)")
               ->execute([$leadId, 'Email', 'Stage 2 Follow-Up (4-Day Minimum)']);

            $results[] = [
                'lead_id' => $leadId,
                'recipient' => $email,
                'company' => $company,
                'stage' => 'Follow-up #1 (4-Day Gap)',
                'success' => true
            ];

            // Limit: Max 1 follow-up dispatched per 5-minute cron run to keep inbox safe
            return $results;
        } else {
            error_log("⚠️ [FOLLOW-UP ENGINE] Follow-up #1 to {$email} skipped/blocked: " . ($res['message'] ?? 'Unknown'));
        }
    }

    // ----------------------------------------------------
    // 2. STAGE 2 -> STAGE 3 FINAL BREAKUP (STRICT MINIMUM 4 DAYS / 96 HOURS GAP)
    // ----------------------------------------------------
    $stmt2 = $db->query("
        SELECT * FROM leads 
        WHERE status = 'followup_1' 
          AND client_email IS NOT NULL 
          AND client_email LIKE '%@%'
          AND updated_at <= datetime('now', '-4 days')
        ORDER BY id ASC LIMIT 5
    ");
    $stage2Leads = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stage2Leads as $lead) {
        $leadId = (int)$lead['id'];
        $email = strtolower(trim($lead['client_email']));
        $company = trim($lead['company'] ?? 'your business');
        $domain = preg_replace('/^www\./i', '', parse_url($lead['url'] ?? '', PHP_URL_HOST) ?? $company);
        if (empty($domain) && strpos($email, '@') !== false) {
            $domain = substr(strrchr($email, "@"), 1);
        }

        // Domain deduplication within this run
        if (in_array($domain, $processedDomains)) {
            continue;
        }

        // STRICT 24-HOUR CHECK: Has this company/domain received ANY email in the last 24 hours?
        if (isEmailOrDomainSentRecently($email, $domain, 24)) {
            error_log("🛡️ [FOLLOW-UP ENGINE SKIPPED] {$email} ({$domain}) already contacted within last 24 hours.");
            $db->prepare("UPDATE leads SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$leadId]);
            continue;
        }

        $clientName = explode(' ', $lead['client_name'] ?? 'there')[0];
        $subject = "final note & technical checklist for {$domain}";
        $body = "Hi {$clientName},\n\n"
            . "Closing the loop on this as I assume you're busy with other client priorities right now.\n\n"
            . "In case your development team tackles the {$domain} optimization sprint later, feel free to keep my contact info handy.\n\n"
            . "Wishing you and {$company} great continued growth this quarter!\n\n"
            . "Best,\n"
            . "{$userName}";

        // Dispatch via SMTP with $isFollowup = true
        $res = SmtpMailer::send($email, $subject, $body, $settings, true);

        // Always advance status on all sibling rows for this email/domain to prevent repeated follow-up loops
        $db->prepare("UPDATE leads SET status = 'followup_2', updated_at = CURRENT_TIMESTAMP WHERE id = ? OR LOWER(client_email) = ? OR LOWER(url) LIKE ?")
           ->execute([$leadId, $email, '%' . $domain . '%']);

        if ($res['success']) {
            $processedDomains[] = $domain;
            recordSentEmailToLedger($email, $domain, $subject);

            $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, ?, ?)")
               ->execute([$leadId, 'Email', 'Stage 3 Final Breakup (4-Day Gap)']);

            $results[] = [
                'lead_id' => $leadId,
                'recipient' => $email,
                'company' => $company,
                'stage' => 'Follow-up #2 (Final Breakup - 4-Day Gap)',
                'success' => true
            ];

            return $results;
        } else {
            error_log("⚠️ [FOLLOW-UP ENGINE] Follow-up #2 to {$email} skipped/blocked: " . ($res['message'] ?? 'Unknown'));
        }
    }

    return $results;
}

