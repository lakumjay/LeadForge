<?php
/**
 * LeadForge AI — LinkedIn OAuth 2.0 1-Click Authenticator & Callback Handler
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

$settings = getSettings();
$defaultClientId = '7780gb3k51bhcv';

$clientId = trim($settings['linkedin_client_id'] ?? $_GET['client_id'] ?? '');
if (empty($clientId)) {
    $clientId = $defaultClientId;
    $settings['linkedin_client_id'] = $clientId;
}

$clientSecret = trim($settings['linkedin_client_secret'] ?? $_GET['client_secret'] ?? '');
if (!empty($_GET['client_id'])) {
    $settings['linkedin_client_id'] = trim($_GET['client_id']);
}
if (!empty($_GET['client_secret'])) {
    $settings['linkedin_client_secret'] = trim($_GET['client_secret']);
}
if (!empty($settings['linkedin_client_id']) || !empty($settings['linkedin_client_secret'])) {
    updateSettings($settings);
}

// Determine redirect URI based on current host
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
$protocol = $isHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'leadsflow.snwebkarma.in';

// Check if running on snwebkarma domain
if (strpos($host, 'snwebkarma.in') !== false) {
    $redirectUri = 'https://leadsflow.snwebkarma.in/api/linkedin_oauth_callback.php';
} else {
    $basePath = rtrim(dirname($_SERVER['REQUEST_URI'] ?? '/api'), '/\\');
    $redirectUri = $protocol . $host . $basePath . '/linkedin_oauth_callback.php';
}

$action = $_GET['action'] ?? '';

// Step 1: Initiate 1-Click Authorization
if ($action === 'connect') {
    $scopes = urlencode('openid profile w_member_social email');
    $state = bin2hex(random_bytes(16));
    $_SESSION['li_oauth_state'] = $state;

    $authUrl = "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id={$clientId}&redirect_uri=" . urlencode($redirectUri) . "&scope={$scopes}&state={$state}";
    header("Location: {$authUrl}");
    exit;
}

// Step 2: Handle OAuth Callback
if (isset($_GET['code'])) {
    $code = trim($_GET['code']);

    $tokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';
    $postFields = http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri
    ]);

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $tokenData = json_decode($response ?: '', true);

    if (!empty($tokenData['access_token'])) {
        $accessToken = $tokenData['access_token'];
        $expiresIn = (int)($tokenData['expires_in'] ?? 5184000); // 60 days default

        // Fetch User Info (sub = member ID)
        $userinfoCh = curl_init('https://api.linkedin.com/v2/userinfo');
        curl_setopt($userinfoCh, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($userinfoCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($userinfoCh, CURLOPT_TIMEOUT, 10);
        curl_setopt($userinfoCh, CURLOPT_SSL_VERIFYPEER, false);

        $userinfoRes = curl_exec($userinfoCh);
        curl_close($userinfoCh);

        $userInfo = json_decode($userinfoRes ?: '', true);
        $personSub = $userInfo['sub'] ?? '';
        $personUrn = !empty($personSub) ? "urn:li:person:{$personSub}" : '';
        $userName = $userInfo['name'] ?? ($settings['user_name'] ?? 'Jay');

        // Update settings
        $settings['linkedin_access_token'] = $accessToken;
        if (!empty($personUrn)) {
            $settings['linkedin_person_urn'] = $personUrn;
        }
        $settings['linkedin_token_expires_at'] = date('Y-m-d H:i:s', time() + $expiresIn);
        $settings['linkedin_autopost_enabled'] = true;
        updateSettings($settings);

        // Send Telegram confirmation
        require_once __DIR__ . '/telegram.php';
        try {
            TelegramNotifier::send("✅ <b>LinkedIn Official API 100% Connected!</b>\n\n👤 <b>User:</b> {$userName}\n⚡ <b>Status:</b> 60-Day Official Token Active\n🚀 <b>Auto-Pilot:</b> Ready for 24/7 background posting and outreach.");
        } catch (Throwable $e) {}

        header('Location: ../linkedin.php?oauth=success');
        exit;
    } else {
        $errorMsg = $tokenData['error_description'] ?? ($tokenData['error'] ?? 'Unknown token exchange error');
        header('Location: ../linkedin.php?oauth=error&msg=' . urlencode($errorMsg));
        exit;
    }
}

// Fallback
header('Location: ../linkedin.php');
exit;
