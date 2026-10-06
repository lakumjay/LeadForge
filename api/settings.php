<?php
/**
 * LeadForge AI - Settings, Profile & SMTP Dispatcher Manager
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../smtp_mailer.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = getSettings();
    $safeSettings = $settings;
    if (!empty($safeSettings['gemini_api_key'])) {
        $safeSettings['gemini_api_key_masked'] = substr($safeSettings['gemini_api_key'], 0, 4) . '••••••••' . substr($safeSettings['gemini_api_key'], -4);
        $safeSettings['gemini_api_key'] = $safeSettings['gemini_api_key_masked'];
        $safeSettings['has_gemini_key'] = true;
    } else {
        $safeSettings['has_gemini_key'] = false;
    }
    if (!empty($safeSettings['smtp_pass'])) {
        $safeSettings['smtp_pass_masked'] = '••••••••••••••••';
        $safeSettings['smtp_pass'] = '••••••••••••••••';
        $safeSettings['has_smtp_pass'] = true;
    } else {
        $safeSettings['has_smtp_pass'] = false;
    }
    if (!empty($safeSettings['telegram_bot_token'])) {
        $safeSettings['telegram_bot_token'] = substr($safeSettings['telegram_bot_token'], 0, 4) . '••••••••' . substr($safeSettings['telegram_bot_token'], -4);
        $safeSettings['has_telegram_token'] = true;
    } else {
        $safeSettings['has_telegram_token'] = false;
    }
    echo json_encode(['status' => 'success', 'settings' => $safeSettings]);
    exit;
}

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;

    $action = $data['action'] ?? 'save';

    if ($action === 'test_smtp') {
        $testTo = trim($data['test_email'] ?? '');
        if (empty($testTo)) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide an email address to send the test message.']);
            exit;
        }

        $current = getSettings();
        $testSubject = "LeadForge AI — Real SMTP Test Delivery Verification";
        $testBody = "Hi " . ($current['user_name'] ?? 'Jay') . ",\n\n"
            . "This is a real-time verification test from your LeadForge AI Autonomous Engine.\n\n"
            . "Status: 100% Verified\n"
            . "Timestamp: " . date('Y-m-d H:i:s') . "\n\n"
            . "Your SMTP configuration is active and ready to deliver zero-spam pitches directly to client Primary Inboxes!\n\n"
            . "Best regards,\nLeadForge System";

        $res = SmtpMailer::send($testTo, $testSubject, $testBody, $current);
        if ($res['success']) {
            echo json_encode(['status' => 'success', 'message' => $res['message']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $res['message']]);
        }
        exit;
    }

    $current = getSettings();
    
    // Update fields if present
    if (isset($data['user_name'])) $current['user_name'] = trim($data['user_name']);
    if (isset($data['title'])) $current['title'] = trim($data['title']);
    if (isset($data['hourly_rate'])) $current['hourly_rate'] = (float)$data['hourly_rate'];
    if (isset($data['monthly_goal_inr'])) $current['monthly_goal_inr'] = (float)$data['monthly_goal_inr'];
    if (isset($data['sound_alerts_enabled'])) $current['sound_alerts_enabled'] = (bool)$data['sound_alerts_enabled'];
    if (isset($data['daily_linkedin_limit'])) $current['daily_linkedin_limit'] = (int)$data['daily_linkedin_limit'];
    if (isset($data['daily_email_limit'])) $current['daily_email_limit'] = (int)$data['daily_email_limit'];
    if (isset($data['daily_upwork_limit'])) $current['daily_upwork_limit'] = (int)$data['daily_upwork_limit'];
    
    if (isset($data['gemini_api_key'])) {
        $submittedKey = trim($data['gemini_api_key']);
        if ($submittedKey !== '' && strpos($submittedKey, '••') === false) {
            $current['gemini_api_key'] = $submittedKey;
        }
    }

    // SMTP Settings
    if (isset($data['smtp_user'])) $current['smtp_user'] = trim($data['smtp_user']);
    if (isset($data['smtp_pass'])) {
        $submittedPass = trim($data['smtp_pass']);
        if ($submittedPass !== '' && strpos($submittedPass, '••') === false) {
            $current['smtp_pass'] = $submittedPass;
        }
    }
    if (isset($data['smtp_host'])) $current['smtp_host'] = trim($data['smtp_host']);
    if (isset($data['smtp_port'])) $current['smtp_port'] = (int)$data['smtp_port'];
    if (isset($data['smtp_from_name'])) $current['smtp_from_name'] = trim($data['smtp_from_name']);
    if (isset($data['smtp_from_email'])) $current['smtp_from_email'] = trim($data['smtp_from_email']);

    // Telegram Bot Settings
    if (isset($data['telegram_bot_token'])) {
        $subToken = trim($data['telegram_bot_token']);
        if ($subToken !== '' && strpos($subToken, '••') === false) {
            $current['telegram_bot_token'] = $subToken;
        }
    }
    if (isset($data['telegram_chat_id'])) $current['telegram_chat_id'] = trim($data['telegram_chat_id']);

    if (updateSettings($current)) {
        $safeResponse = $current;
        if (!empty($safeResponse['gemini_api_key'])) {
            $safeResponse['gemini_api_key'] = substr($safeResponse['gemini_api_key'], 0, 4) . '••••••••' . substr($safeResponse['gemini_api_key'], -4);
        }
        if (!empty($safeResponse['smtp_pass'])) {
            $safeResponse['smtp_pass'] = '••••••••••••••••';
        }
        echo json_encode(['status' => 'success', 'message' => 'Settings saved successfully', 'settings' => $safeResponse]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save settings file']);
    }
    exit;
}
