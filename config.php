<?php
/**
 * LeadForge AI - Configuration & Environment Setup
 * 100% Free & Zero-Cost Architecture
 */

declare(strict_types=1);

// Error handling for maximum stability
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

// Base directories
define('ROOT_PATH', __DIR__);
define('DATA_PATH', ROOT_PATH . '/data');
define('DB_FILE', DATA_PATH . '/leadforge.sqlite');
define('SETTINGS_FILE', DATA_PATH . '/settings.json');
define('LEADS_CACHE_FILE', DATA_PATH . '/jobs_cache.json');

// Ensure data directory exists and is writable
if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0777, true);
}

// Default settings
$defaultSettings = [
    'user_name' => 'Jay',
    'title' => 'Laravel, SEO & Full-Stack Growth Architect',
    'skills' => ['Laravel', 'PHP', 'MySQL', 'Technical SEO', 'Google Ads', 'GA4 Tracking', 'Shopify', 'REST API', 'Bug Fixing'],
    'hourly_rate' => 20,
    'monthly_goal_inr' => 50000,
    'usd_to_inr' => 86.5,
    'gemini_api_key' => '',
    'groq_api_key' => '',
    'sound_alerts_enabled' => true,
    'radar_refresh_interval_sec' => 30,
    'daily_linkedin_limit' => 999999,
    'daily_email_limit' => 999999,
    'daily_upwork_limit' => 999999,
    'auto_filter_scams' => true,
    'target_countries' => ['United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'Netherlands'],
    // SMTP Credentials for REAL live email delivery
    'smtp_enabled' => true,
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_encryption' => 'tls',
    'smtp_user' => 'lakumjay2000@gmail.com',
    'smtp_pass' => 'bqhwsyctoonfrjmf',
    'smtp_from_email' => 'lakumjay2000@gmail.com',
    'smtp_from_name' => 'Jay'
];

if (!file_exists(SETTINGS_FILE)) {
    file_put_contents(SETTINGS_FILE, json_encode($defaultSettings, JSON_PRETTY_PRINT));
}

function getSettings(): array {
    global $defaultSettings;
    if (file_exists(SETTINGS_FILE)) {
        $data = json_decode(file_get_contents(SETTINGS_FILE), true);
        if (is_array($data)) {
            return array_merge($defaultSettings, $data);
        }
    }
    return $defaultSettings;
}

function updateSettings(array $newSettings): bool {
    $current = getSettings();
    $updated = array_merge($current, $newSettings);
    return file_put_contents(SETTINGS_FILE, json_encode($updated, JSON_PRETTY_PRINT)) !== false;
}
