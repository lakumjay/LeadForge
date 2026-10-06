<?php
/**
 * LeadForge AI - Multi-Service Website, SEO, Google Ads & Bug Auditor Engine
 * Performs Real-Time Tech Stack, Vulnerability, SEO, and Tracking Diagnostics
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'audit.php') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_REQUEST;

    $targetUrl = trim($data['url'] ?? '');

    if (empty($targetUrl)) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid website URL to audit.']);
        exit;
    }

    // Ensure URL has scheme
    if (!preg_match('#^https?://#i', $targetUrl)) {
        $targetUrl = 'https://' . $targetUrl;
    }

    try {
        $auditResult = performSiteAudit($targetUrl);
        echo json_encode(['status' => 'success', 'data' => $auditResult]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Audit failed: ' . $e->getMessage()]);
    }
    exit;
}

function performSiteAudit(string $url): array {
    $domainKey = strtolower(preg_replace('/^www\./i', '', parse_url($url, PHP_URL_HOST) ?? $url));
    
    // 1. Instant 24-Hour Cache Check (Loads in 0.001s)
    if (!empty($domainKey)) {
        try {
            $db = Database::getConnection();
            $cacheStmt = $db->prepare("SELECT audit_data FROM audit_cache WHERE domain = ? AND created_at >= datetime('now', '-1 day')");
            $cacheStmt->execute([$domainKey]);
            $cachedRow = $cacheStmt->fetch(PDO::FETCH_ASSOC);
            if ($cachedRow && !empty($cachedRow['audit_data'])) {
                $cachedResult = json_decode($cachedRow['audit_data'], true);
                if (!empty($cachedResult) && isset($cachedResult['is_online'])) {
                    $cachedResult['from_cache'] = true;
                    return $cachedResult;
                }
            }
        } catch (Throwable $e) {}
    }

    $startTime = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');

    $response = curl_exec($ch);
    $totalTime = microtime(true) - $startTime;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
    curl_close($ch);

    if ($response === false || empty($response)) {
        return [
            'url' => $url,
            'is_online' => false,
            'http_code' => $httpCode,
            'error' => 'Could not connect to target host. Website might be down or blocking automated probes.'
        ];
    }

    $headerText = substr($response, 0, $headerSize);
    $htmlBody = substr($response, $headerSize);

    // Parse headers
    $headers = [];
    foreach (explode("\r\n", $headerText) as $line) {
        if (strpos($line, ': ') !== false) {
            [$k, $v] = explode(': ', $line, 2);
            $headers[strtolower(trim($k))] = trim($v);
        }
    }

    $issues = [];
    $techStack = [];
    $servicesDetected = [];

    // 1. Tech Stack Detection
    $serverHeader = $headers['server'] ?? '';
    $poweredBy = $headers['x-powered-by'] ?? '';

    if (stripos($serverHeader, 'nginx') !== false) $techStack[] = 'Nginx';
    if (stripos($serverHeader, 'apache') !== false) $techStack[] = 'Apache';
    if (stripos($serverHeader, 'cloudflare') !== false || isset($headers['cf-ray'])) $techStack[] = 'Cloudflare CDN';
    if (stripos($poweredBy, 'php') !== false) $techStack[] = 'PHP (' . $poweredBy . ')';

    // Frameworks & CMS
    if (stripos($headerText, 'laravel_session') !== false || stripos($htmlBody, 'csrf-token') !== false) {
        $techStack[] = 'Laravel';
        $servicesDetected[] = 'Web & Backend Development';
    }
    if (stripos($htmlBody, 'wp-content') !== false || stripos($htmlBody, 'wp-includes') !== false) {
        $techStack[] = 'WordPress';
        $servicesDetected[] = 'WordPress & Theme Optimization';
    }
    if (stripos($htmlBody, 'shopify') !== false || stripos($htmlBody, 'cdn.shopify.com') !== false) {
        $techStack[] = 'Shopify';
        $servicesDetected[] = 'Shopify E-Commerce';
    }
    if (stripos($htmlBody, 'vue') !== false || stripos($htmlBody, 'data-v-') !== false) {
        $techStack[] = 'Vue.js';
    }
    if (stripos($htmlBody, 'react') !== false || stripos($htmlBody, '_next') !== false) {
        $techStack[] = 'React / Next.js';
    }
    if (stripos($htmlBody, 'tailwind') !== false || preg_match('/class="[^"]*(flex|grid|p-|m-|text-)/', $htmlBody)) {
        $techStack[] = 'Tailwind CSS';
    }

    if (empty($techStack)) {
        $techStack = ['PHP / Custom Web', 'HTML5/CSS3'];
    }

    // 2. Google Ads, Meta Ads & Analytics Tracking Diagnostics
    $hasGtm = stripos($htmlBody, 'googletagmanager.com/gtm.js') !== false || stripos($htmlBody, 'GTM-') !== false;
    $hasGa4 = stripos($htmlBody, 'gtag/js?id=G-') !== false || stripos($htmlBody, 'google-analytics.com') !== false;
    $hasGoogleAds = stripos($htmlBody, 'gtag(\'config\', \'AW-') !== false || stripos($htmlBody, 'googleads') !== false || stripos($htmlBody, 'conversion_async.js') !== false;
    $hasMetaPixel = stripos($htmlBody, 'fbq(') !== false || stripos($htmlBody, 'connect.facebook.net') !== false || stripos($htmlBody, 'fbevents.js') !== false;

    // Check Google Ads / GA4 Tracking
    if (!$hasGtm && !$hasGa4) {
        $issues[] = [
            'type' => 'Google Ads & GA4 Tracking Gap',
            'severity' => 'High',
            'service' => 'Google Ads & Analytics',
            'title' => 'Missing Google Analytics (GA4) & GTM Conversion Tracking',
            'detail' => 'No Google Tag Manager or GA4 tracking found in HTML. Paid Google Ads traffic cannot accurately record purchases, ROAS, or lead submissions.',
            'solution' => 'Configure GTM container with GA4 enhanced e-commerce dataLayer purchase events.'
        ];
    } else {
        $techStack[] = $hasGtm ? 'Google Tag Manager' : 'Google Analytics 4';
        if ($hasGoogleAds) $techStack[] = 'Google Ads Conversion Tag';
    }

    // Check Meta (Facebook / Instagram) Ads Pixel
    if (!$hasMetaPixel) {
        $issues[] = [
            'type' => 'Meta Ads & Pixel Gap',
            'severity' => 'High',
            'service' => 'Meta Ads & Pixel Setup',
            'title' => 'Missing Meta (Facebook) Pixel & Conversions API (CAPI)',
            'detail' => 'No Meta Pixel or Conversions API detected. Facebook & Instagram paid ads cannot optimize for purchase conversions or build custom retargeting audiences.',
            'solution' => 'Install Meta Pixel with standard event tracking (PageView, ViewContent, AddToCart, Purchase) & Conversions API.'
        ];
    } else {
        $techStack[] = 'Meta Pixel (Facebook Ads)';
    }

    // 3. SEO & Organic Ranking Audit
    $hasMetaDesc = preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/i', $htmlBody, $descMatch);
    $hasSchema = stripos($htmlBody, 'application/ld+json') !== false;
    $hasCanonical = stripos($htmlBody, '<link rel="canonical"') !== false;

    if (!$hasMetaDesc || strlen(trim($descMatch[1] ?? '')) < 10) {
        $issues[] = [
            'type' => 'SEO Ranking Issue',
            'severity' => 'Medium',
            'service' => 'Technical SEO',
            'title' => 'Missing or Incomplete SEO Meta Description',
            'detail' => 'Google search result snippets will display random page text instead of optimized marketing copy, lowering organic CTR.',
            'solution' => 'Add targeted 155-character meta descriptions with primary buyer keywords.'
        ];
    }

    if (!$hasSchema) {
        $issues[] = [
            'type' => 'SEO Rich Snippet Gap',
            'severity' => 'Medium',
            'service' => 'Technical SEO',
            'title' => 'Missing JSON-LD Structured Data (Schema Markup)',
            'detail' => 'Search engines cannot identify Organization, Product, or Local Business schema to show rich star ratings or snippet badges in Google Search.',
            'solution' => 'Inject Schema.org JSON-LD structured data for business, reviews, and breadcrumbs.'
        ];
    }

    // 4. Speed & Google Ads Quality Score Performance Check
    $speedSeconds = round($totalTime, 2);
    if ($speedSeconds > 2.0) {
        $issues[] = [
            'type' => 'Performance & Ads Quality Score',
            'severity' => 'High',
            'service' => 'Speed Optimization',
            'title' => 'Slow Initial Response (' . $speedSeconds . 's)',
            'detail' => 'Page takes ' . $speedSeconds . 's to respond. This directly hurts Google Ads Quality Score (increasing cost-per-click) and mobile conversions.',
            'solution' => 'Implement server-level caching, optimize database queries, and enable WebP/Brotli assets.'
        ];
    }

    // 5. Security & Mobile Viewport Checks
    if (!isset($headers['strict-transport-security']) && stripos($effectiveUrl, 'https://') !== false) {
        $issues[] = [
            'type' => 'Security Notice',
            'severity' => 'Medium',
            'service' => 'Web Security',
            'title' => 'Missing HSTS (HTTP Strict Transport Security)',
            'detail' => 'Browsers might allow downgrade attacks if HSTS header is absent.',
            'solution' => 'Add Strict-Transport-Security: max-age=31536000 header in server config.'
        ];
    }

    if (stripos($htmlBody, '<meta name="viewport"') === false) {
        $issues[] = [
            'type' => 'Mobile UX & SEO Penalty',
            'severity' => 'High',
            'service' => 'Web Dev & Mobile SEO',
            'title' => 'Missing Mobile Viewport Meta Tag',
            'detail' => 'Site will fail Google Mobile-Friendly Indexing test and display broken on smartphones.',
            'solution' => 'Add `<meta name="viewport" content="width=device-width, initial-scale=1.0">`.'
        ];
    }

    // Probe Exposed .env Debug
    $envProbeUrl = rtrim($effectiveUrl, '/') . '/.env';
    if (probeExposedFile($envProbeUrl)) {
        $issues[] = [
            'type' => 'CRITICAL SECURITY LEAK',
            'severity' => 'Critical',
            'service' => 'Backend Security',
            'title' => 'Exposed .env Configuration File!',
            'detail' => 'Database passwords and application API keys are publicly exposed at /.env.',
            'solution' => 'Immediately restrict web access to .env (403 Forbidden).'
        ];
    }

    if (empty($issues)) {
        $issues[] = [
            'type' => 'Optimization Opportunity',
            'severity' => 'Low',
            'service' => 'General Optimization',
            'title' => 'Conversion Rate & Asset Optimization',
            'detail' => 'Site is healthy. Opportunity to run A/B conversion rate tests and improve Google Ads landing page speed.',
            'solution' => 'Implement A/B test funnels and WebP modern image pipelines.'
        ];
    }

    // Extract Real Emails from Website HTML
    $cleanHost = preg_replace('/^www\./i', '', parse_url($effectiveUrl, PHP_URL_HOST) ?? $url);
    
    // 5. Extract Social Profiles (LinkedIn, Instagram, Twitter, Facebook, Contact Page)
    $socialProfiles = extractSocialProfilesFromHtml($htmlBody, $effectiveUrl);

    // 6. Extract Emails from Homepage
    $discoveredEmails = extractEmailsFromHtml($htmlBody, $cleanHost, $effectiveUrl);

    // 7. Parallel Multi-cURL Probing for Contact Pages if no email was on homepage (0.3s)
    if (empty($discoveredEmails)) {
        $contactPaths = ['/contact', '/contact-us', '/about-us', '/about'];
        $mh = curl_multi_init();
        $curlHandles = [];

        foreach ($contactPaths as $cPath) {
            $probeUrl = rtrim($effectiveUrl, '/') . $cPath;
            $chProbe = curl_init();
            curl_setopt($chProbe, CURLOPT_URL, $probeUrl);
            curl_setopt($chProbe, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chProbe, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($chProbe, CURLOPT_MAXREDIRS, 2);
            curl_setopt($chProbe, CURLOPT_TIMEOUT, 2);
            curl_setopt($chProbe, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chProbe, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36');
            curl_multi_add_handle($mh, $chProbe);
            $curlHandles[$probeUrl] = $chProbe;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.2);
        } while ($running > 0);

        foreach ($curlHandles as $pUrl => $handle) {
            $cHtml = curl_multi_getcontent($handle);
            if (!empty($cHtml)) {
                $cEmails = extractEmailsFromHtml($cHtml, $cleanHost, $pUrl);
                if (!empty($cEmails)) {
                    $discoveredEmails = array_values(array_unique(array_merge($discoveredEmails, $cEmails)));
                }
                // Also update social profiles if discovered on contact page
                $cSocial = extractSocialProfilesFromHtml($cHtml, $pUrl);
                foreach ($cSocial as $sKey => $sVal) {
                    if (empty($socialProfiles[$sKey]) && !empty($sVal)) {
                        $socialProfiles[$sKey] = $sVal;
                    }
                }
            }
            curl_multi_remove_handle($mh, $handle);
            curl_close($handle);
        }
        curl_multi_close($mh);
    }

    // Generate Multi-Service Tailored Pitches
    $parsedHost = parse_url($effectiveUrl, PHP_URL_HOST) ?? $url;
    $primaryIssue = $issues[0];
    $multiPitches = generateMultiServicePitches($parsedHost, $primaryIssue, $techStack);

    $auditResult = [
        'url' => $effectiveUrl,
        'domain' => $parsedHost,
        'clean_domain' => $cleanHost,
        'is_online' => true,
        'http_code' => $httpCode,
        'response_time' => $speedSeconds . 's',
        'tech_stack' => array_values(array_unique($techStack)),
        'services_identified' => array_values(array_unique($servicesDetected)),
        'total_issues_found' => count($issues),
        'issues' => $issues,
        'discovered_emails' => $discoveredEmails,
        'primary_email' => $discoveredEmails[0] ?? null,
        'social_profiles' => $socialProfiles,
        'pitches' => $multiPitches,
        'pitch_preview' => $multiPitches['primary']
    ];

    // 8. Save Result to 24-Hour SQLite Cache
    if (!empty($domainKey)) {
        try {
            $db = Database::getConnection();
            $saveStmt = $db->prepare("INSERT OR REPLACE INTO audit_cache (domain, audit_data, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
            $saveStmt->execute([$domainKey, json_encode($auditResult)]);
        } catch (Throwable $e) {}
    }

    return $auditResult;
}

/**
 * Multi-Channel Social Profile Extractor (LinkedIn, Instagram, Twitter, Facebook, Contact Form)
 */
function extractSocialProfilesFromHtml(string $html, string $effectiveUrl): array {
    $profiles = [
        'linkedin' => null,
        'instagram' => null,
        'twitter' => null,
        'facebook' => null,
        'contact_page' => null
    ];

    // 1. LinkedIn (Company or personal profile)
    if (preg_match('/https?:\/\/(?:www\.)?linkedin\.com\/(?:company|in)\/[a-zA-Z0-9_\-\.\/]+/i', $html, $matches)) {
        $profiles['linkedin'] = rtrim($matches[0], '/"\'');
    }

    // 2. Instagram Profile
    if (preg_match('/https?:\/\/(?:www\.)?instagram\.com\/([a-zA-Z0-9_\.]+)\/?/i', $html, $matches)) {
        $igHandle = strtolower(trim($matches[1]));
        if (!in_array($igHandle, ['p', 'explore', 'reels', 'stories', 'about', 'legal', 'accounts', 'developer'])) {
            $profiles['instagram'] = "https://instagram.com/" . $igHandle;
        }
    }

    // 3. Twitter / X Profile
    if (preg_match('/https?:\/\/(?:www\.)?(?:twitter\.com|x\.com)\/([a-zA-Z0-9_]+)\/?/i', $html, $matches)) {
        $xHandle = strtolower(trim($matches[1]));
        if (!in_array($xHandle, ['intent', 'share', 'home', 'privacy', 'tos'])) {
            $profiles['twitter'] = "https://x.com/" . $xHandle;
        }
    }

    // 4. Facebook Profile
    if (preg_match('/https?:\/\/(?:www\.)?facebook\.com\/([a-zA-Z0-9_\-\.]+)\/?/i', $html, $matches)) {
        $fbHandle = strtolower(trim($matches[1]));
        if (!in_array($fbHandle, ['sharer', 'share', 'dialog', 'policies', 'groups'])) {
            $profiles['facebook'] = "https://facebook.com/" . $fbHandle;
        }
    }

    // 5. Contact page link in HTML
    if (preg_match('/href=["\']([^"\']*(?:contact|about)[^"\']*)["\']/i', $html, $matches)) {
        $contactHref = $matches[1];
        if (strpos($contactHref, 'http') === 0) {
            $profiles['contact_page'] = $contactHref;
        } else {
            $profiles['contact_page'] = rtrim($effectiveUrl, '/') . '/' . ltrim($contactHref, '/');
        }
    }

    return $profiles;
}

function extractEmailsFromHtml(string $html, string $cleanDomain, string $baseUrl): array {
    $emails = [];

    // 1. mailto: links
    if (preg_match_all('/mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $html, $mMatches)) {
        foreach ($mMatches[1] as $e) {
            $emails[] = trim(strtolower($e));
        }
    }

    // 2. JSON-LD Schema emails
    if (preg_match_all('/"email"\s*:\s*"([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})"/i', $html, $sMatches)) {
        foreach ($sMatches[1] as $e) {
            $emails[] = trim(strtolower($e));
        }
    }

    // 3. Domain specific emails in text
    if (preg_match_all('/[a-zA-Z0-9._%+-]+@' . preg_quote($cleanDomain, '/') . '/i', $html, $dMatches)) {
        foreach ($dMatches[0] as $e) {
            $emails[] = trim(strtolower($e));
        }
    }

    // Filter out invalid/asset extensions
    $filtered = [];
    $ignorePatterns = ['sentry', 'wix', 'wordpress', 'cloudflare', 'schema.org', 'domain.com', 'example.com', '.png', '.jpg', '.jpeg', '.svg', '.webp', '.gif', 'github.com', 'google.com'];

    foreach (array_unique($emails) as $em) {
        $shouldIgnore = false;
        foreach ($ignorePatterns as $ign) {
            if (stripos($em, $ign) !== false) {
                $shouldIgnore = true;
                break;
            }
        }
        if (!$shouldIgnore && filter_var($em, FILTER_VALIDATE_EMAIL)) {
            $filtered[] = $em;
        }
    }

    return array_values(array_unique($filtered));
}

function probeExposedFile(string $url): bool {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && is_string($res) && (stripos($res, 'APP_KEY=') !== false || stripos($res, 'DB_PASSWORD=') !== false)) {
        return true;
    }
    return false;
}

function generateMultiServicePitches(string $domain, array $primaryIssue, array $techStack): array {
    $issueTitle = $primaryIssue['title'];
    $fix = $primaryIssue['solution'];
    $service = $primaryIssue['service'] ?? 'Technical Support';

    // 1. Primary Dynamic Pitch based on exact finding
    $primaryPitch = [
        'subject' => "Quick observation regarding {$domain} ({$service})",
        'linkedin_note' => "Hey! While checking {$domain}, I noticed a quick {$service} opportunity: {$issueTitle}. Quick fix: {$fix}. Just wanted to give you a friendly heads-up!",
        'email_body' => "Hi there,\n\nI was reviewing {$domain} and wanted to share a quick technical heads-up regarding {$service}:\n\nI noticed {$issueTitle}.\nDetail: {$primaryIssue['detail']}\n\nYou can easily resolve this by:\n> {$fix}\n\nIf your team is currently tied up with client work and you'd like an extra hand handling this (or running a complete audit across Web Dev, SEO, or Google Ads tracking), feel free to let me know!\n\nBest regards,\nJay",
        'direct_dm' => "Hey! Checked {$domain} and spotted {$issueTitle}. Recommended fix: {$fix}. Happy to help patch it if needed!"
    ];

    // 2. SEO Specific Pitch
    $seoPitch = "Hi there,\n\nI was looking at {$domain}'s organic search footprint and noticed some quick technical SEO gaps (missing schema markup & meta optimizations) that are holding back your Google keyword rankings.\n\nI can run a complete technical SEO audit and implement all on-page fixes to boost your Google visibility this week.\n\nWould you like me to send over a quick 2-minute video audit?\n\nBest,\nJay";

    // 3. Google Ads & Tracking Pitch
    $adsPitch = "Hi there,\n\nI was reviewing {$domain} and noticed that conversion tracking & GA4 event triggers might be incomplete, meaning your paid Google/Meta Ad campaigns could be spending budget without accurately attributing customer sales.\n\nI specialize in fixing Google Tag Manager (GTM), GA4 eCommerce tracking, and Google Ads conversion API setups.\n\nWould you like a quick check of your tracking setup today?\n\nBest,\nJay";

    return [
        'primary' => $primaryPitch,
        'seo_pitch' => $seoPitch,
        'ads_pitch' => $adsPitch
    ];
}
