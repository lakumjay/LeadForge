<?php
/**
 * LeadForge AI - Automated 3-Stage Smart Follow-Up Engine
 * Triples closing rate by automatically following up on pending leads after 48h / 96h
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

    // 1. Find leads contacted > 48h ago with status 'contacted' (Stage 1 -> Stage 2 Followup)
    $stmt = $db->query("
        SELECT * FROM leads 
        WHERE status = 'contacted' 
          AND client_email IS NOT NULL 
          AND client_email LIKE '%@%'
          AND created_at <= datetime('now', '-2 days')
        ORDER BY id DESC LIMIT 5
    ");
    $stage1Leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stage1Leads as $lead) {
        $email = $lead['client_email'];
        $clientName = explode(' ', $lead['client_name'] ?? 'there')[0];
        $company = $lead['company'] ?? 'your business';
        $domain = preg_replace('/^www\./i', '', parse_url($lead['url'] ?? '', PHP_URL_HOST) ?? $company);

        $subject = "quick follow-up regarding {$domain}";
        $body = "Hi {$clientName},\n\n"
            . "Just following up on my previous note in case it got buried in your inbox.\n\n"
            . "Were you or your team able to take a look at the tracking / optimization opportunity on {$domain}?\n\n"
            . "I'm happy to send over a quick 2-minute video breakdown of where the fix is needed whenever you have a moment.\n\n"
            . "Best regards,\n"
            . "{$userName}\n"
            . "Web & Growth Specialist";

        $res = SmtpMailer::send($email, $subject, $body, $settings);
        if ($res['success']) {
            $db->prepare("UPDATE leads SET status = 'followup_1', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$lead['id']]);
            $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, ?, ?)")
               ->execute([$lead['id'], 'Email', 'Stage 2 Follow-Up (48h)']);
            $results[] = [
                'lead_id' => $lead['id'],
                'recipient' => $email,
                'stage' => 'Follow-up #1 (48h)',
                'success' => true
            ];
        }
    }

    // 2. Find leads in 'followup_1' > 4 days ago (Stage 2 -> Stage 3 Final Breakup)
    $stmt2 = $db->query("
        SELECT * FROM leads 
        WHERE status = 'followup_1' 
          AND client_email IS NOT NULL 
          AND client_email LIKE '%@%'
          AND updated_at <= datetime('now', '-4 days')
        ORDER BY id DESC LIMIT 3
    ");
    $stage2Leads = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stage2Leads as $lead) {
        $email = $lead['client_email'];
        $clientName = explode(' ', $lead['client_name'] ?? 'there')[0];
        $company = $lead['company'] ?? 'your business';
        $domain = preg_replace('/^www\./i', '', parse_url($lead['url'] ?? '', PHP_URL_HOST) ?? $company);

        $subject = "final note & technical checklist for {$domain}";
        $body = "Hi {$clientName},\n\n"
            . "Closing the loop on this as I assume you're busy with other client priorities right now.\n\n"
            . "In case your development team tackles the {$domain} optimization sprint later, feel free to keep my contact info handy.\n\n"
            . "Wishing you and {$company} great continued growth this quarter!\n\n"
            . "Best,\n"
            . "{$userName}";

        $res = SmtpMailer::send($email, $subject, $body, $settings);
        if ($res['success']) {
            $db->prepare("UPDATE leads SET status = 'followup_2', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$lead['id']]);
            $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, ?, ?)")
               ->execute([$lead['id'], 'Email', 'Stage 3 Final Check (96h)']);
            $results[] = [
                'lead_id' => $lead['id'],
                'recipient' => $email,
                'stage' => 'Follow-up #2 (96h Final)',
                'success' => true
            ];
        }
    }

    return $results;
}
