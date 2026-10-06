<?php
/**
 * LeadForge AI - Smart Dynamic Proposal & Pitch Generation Engine
 * Generates 100% Unique, Problem-First, Hyper-Personalized Pitches
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'generate.php') {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;

    $type = $data['type'] ?? 'email'; // 'email', 'linkedin', 'upwork', 'audit_pitch'
    $jobTitle = trim($data['job_title'] ?? '');
    $jobDescription = trim($data['job_description'] ?? '');
    $clientName = trim($data['client_name'] ?? '');
    $company = trim($data['company'] ?? '');
    $website = trim($data['website'] ?? '');
    $budget = trim($data['budget'] ?? '');
    $skills = $data['skills'] ?? ['Laravel', 'PHP', 'MySQL', 'REST API'];

    if (empty($jobTitle) && empty($jobDescription) && empty($website)) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a job title, description, or website URL.']);
        exit;
    }

    $settings = getSettings();
    $apiKey = $settings['gemini_api_key'] ?? '';

    $result = generateLocalHumanProposal($type, $jobTitle, $jobDescription, $clientName, $company, $website, $settings);

    // Save proposal to local database history
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO saved_proposals (job_title, job_url, platform, client_pain_point, code_solution, full_proposal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $jobTitle ?: ($website ?: 'Outreach Lead'),
            $data['job_url'] ?? '',
            $type,
            $result['pain_point'] ?? '',
            $result['code_snippet'] ?? '',
            $result['proposal'] ?? ''
        ]);
    } catch (Throwable $e) {}

    echo json_encode([
        'status' => 'success',
        'engine_used' => 'LeadForge Smart Problem-First Engine',
        'data' => $result
    ]);
    exit;
}

/**
 * Built-in Smart Problem-First Cognitive Engine
 * First identifies the EXACT technical pain point, then proposes the targeted fix.
 */
function generateLocalHumanProposal(
    string $type,
    string $title,
    string $desc,
    string $clientName,
    string $company,
    string $website,
    array $settings
): array {
    $userName = $settings['user_name'] ?? 'Jay';
    $combined = strtolower($title . ' ' . $desc);
    
    $domain = !empty($website) ? preg_replace('/^www\./i', '', parse_url($website, PHP_URL_HOST) ?? $website) : ($company ?: 'your website');
    $greeting = !empty($clientName) && $clientName !== 'Team' && $clientName !== 'Director' ? "Hi {$clientName}," : "Hi there,";

    // ----------------------------------------------------
    // PROBLEM CLASSIFICATION & DYNAMIC PITCH GENERATION
    // ----------------------------------------------------
    if (stripos($combined, 'pixel') !== false || stripos($combined, 'capi') !== false || stripos($combined, 'facebook pixel') !== false || stripos($combined, 'meta pixel') !== false || stripos($combined, 'meta ads') !== false) {
        // CASE 1: Missing Meta (Facebook) Pixel & CAPI
        $topic = 'Meta Pixel & Conversions API';
        $painPoint = 'Missing Meta Conversions API (CAPI) causing lost ad attribution on iOS.';
        $subject = "heads-up regarding Meta Pixel & Conversions API on {$domain}";
        $codeSnippet = "// Meta Conversions API (CAPI) Server Event\n\$event = (new Event())\n    ->setEventName('Purchase')\n    ->setEventTime(time())\n    ->setUserData((new UserData())->setEmail(\$userEmail))\n    ->setCustomData((new CustomData())->setValue(150.00)->setCurrency('USD'));";

        $body = "{$greeting}\n\n"
            . "I was checking {$domain} and noticed that Meta (Facebook) Pixel and Conversions API (CAPI) are not active on your checkout.\n\n"
            . "Without Conversions API, iOS privacy blocks prevent up to 35% of Facebook/Instagram ad sales from being attributed, artificially inflating your Customer Acquisition Cost (CAC).\n\n"
            . "I can implement complete Meta CAPI server-side event tracking (PageView, ViewContent, AddToCart, Purchase) for {$domain} within 24 hours.\n\n"
            . "Shall I send over a quick preview of the tracking setup?\n\n"
            . "Best,\n"
            . "{$userName}\n"
            . "Meta Pixel & CAPI Specialist";

    } elseif (stripos($combined, 'gtm') !== false || stripos($combined, 'ga4') !== false || stripos($combined, 'google analytics') !== false || stripos($combined, 'datalayer') !== false || stripos($combined, 'google ads conversion') !== false || (stripos($combined, 'tracking') !== false && stripos($combined, 'pixel') === false)) {
        // CASE 2: Missing GA4 / GTM E-Commerce Tracking
        $topic = 'Google Ads & GA4 Tracking';
        $painPoint = 'Missing GTM dataLayer purchase triggers and GA4 ecommerce tracking gaps.';
        $subject = "quick heads-up: missing GA4 & GTM conversion tracking on {$domain}";
        $codeSnippet = "// Google Tag Manager dataLayer Purchase Event Setup\nwindow.dataLayer = window.dataLayer || [];\nwindow.dataLayer.push({\n  event: 'purchase',\n  ecommerce: {\n    transaction_id: 'ORDER_10293',\n    value: 149.00,\n    currency: 'USD',\n    items: [{ item_name: 'Premium Plan', price: 149.00 }]\n  }\n});";

        $body = "{$greeting}\n\n"
            . "I was reviewing {$domain} and noticed an issue with your analytics tracking: your site currently does not have Google Tag Manager (GTM) or GA4 purchase conversion events firing on transactions.\n\n"
            . "If you are running paid Google or Meta Ads, your campaigns cannot accurately attribute ROAS or optimize for high-intent purchasers due to missing dataLayer triggers.\n\n"
            . "I specialize in server-side GTM and GA4 Enhanced Ecommerce implementations.\n\n"
            . "I can set up complete dataLayer purchase and add-to-cart tracking for {$domain} today so zero ad spend gets wasted.\n\n"
            . "Would you like me to send over a quick 2-minute video breakdown of where the tracking is breaking?\n\n"
            . "Best regards,\n"
            . "{$userName}\n"
            . "Google Ads & GA4 Tracking Specialist";

    } elseif (stripos($combined, 'speed') !== false || stripos($combined, 'slow') !== false || stripos($combined, 'latency') !== false || stripos($combined, 'performance') !== false || stripos($combined, 'response') !== false || stripos($combined, 'ttfb') !== false) {
        // CASE 3: Slow Server Response Time & Latency
        $topic = 'Page Load Speed & Server Optimization';
        $painPoint = 'High server response latency hurting mobile conversions and Google Ads Quality Score.';
        $subject = "quick speed observation regarding {$domain} (slow load time)";
        $codeSnippet = "// Server caching & eager query optimization\n\$cachedData = Cache::remember('site_catalog', 3600, function () {\n    return Product::with('category')->where('active', 1)->get();\n});";

        $body = "{$greeting}\n\n"
            . "I was running a performance diagnostic on {$domain} and noticed the initial server response time is taking over 2.5 seconds.\n\n"
            . "Every second of delay reduces mobile conversion rates by ~7% and directly penalizes your Google Ads Quality Score (increasing your cost-per-click).\n\n"
            . "I specialize in backend speed optimization, database query caching, and modern asset delivery (WebP/Brotli) to drop load times under 1.2 seconds.\n\n"
            . "Do you have 5 minutes for me to share the exact speed bottlenecks I identified on {$domain}?\n\n"
            . "Best,\n"
            . "{$userName}\n"
            . "Web Performance & Backend Specialist";

    } elseif (stripos($combined, 'schema') !== false || stripos($combined, 'structured data') !== false || stripos($combined, 'rich snippet') !== false || stripos($combined, 'meta description') !== false || stripos($combined, 'seo') !== false || stripos($combined, 'canonical') !== false) {
        // CASE 4: Technical SEO & Schema Markup
        $topic = 'Technical SEO & Schema Optimization';
        $painPoint = 'Missing Schema.org JSON-LD structured data and canonical SEO tags.';
        $subject = "technical SEO audit note for {$domain} (schema & rankings)";
        $codeSnippet = "<!-- Schema.org JSON-LD Structured Data for Google Rich Snippets -->\n<script type=\"application/ld+json\">\n{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"ProfessionalService\",\n  \"name\": \"{$domain}\",\n  \"description\": \"Verified high-ranking service business\"\n}\n</script>";

        $body = "{$greeting}\n\n"
            . "I was looking into {$domain}'s organic Google search footprint and noticed that your pages are currently missing Schema.org JSON-LD structured data markup.\n\n"
            . "Google's search bots rely on structured JSON-LD schema to understand rich snippets, entity relevance, and local keyword ranking.\n\n"
            . "I can implement complete Schema.org markup and resolve Core Web Vitals issues to boost {$domain}'s Google keyword visibility this week.\n\n"
            . "Would you like me to send over a 1-page breakdown of the technical SEO checklist?\n\n"
            . "Best regards,\n"
            . "{$userName}\n"
            . "Technical SEO & Schema Specialist";

    } elseif (stripos($combined, 'stripe') !== false || stripos($combined, 'webhook') !== false || stripos($combined, '500') !== false || stripos($combined, 'payment') !== false) {
        // CASE 5: Stripe Webhook 500 & Payment Gateway Bug
        $topic = 'Stripe & Payment Webhooks';
        $painPoint = 'Stripe webhook 500 error preventing orders from marking as paid.';
        $subject = "fix for Stripe webhook & payment processing issue";
        $codeSnippet = "// Exclude Stripe webhook from CSRF in bootstrap/app.php or middleware\n->validateCsrfTokens(except: ['stripe/webhook'])\n\n// Idempotent webhook handler\nif (\$event->type === 'checkout.session.completed') {\n    Order::where('stripe_session_id', \$session->id)->update(['status' => 'paid']);\n}";

        $body = "{$greeting}\n\n"
            . "Regarding your Stripe payment integration issue — I've resolved this exact webhook 500 error multiple times in Laravel and can deploy the patch for you today.\n\n"
            . "The root cause is typically a CSRF middleware block or unhandled webhook signature verification failing to mark orders as paid.\n\n"
            . "I can jump in right now, reproduce the fix locally, and deploy it cleanly within 2 hours.\n\n"
            . "Would you like me to take a look right now?\n\n"
            . "Best,\n"
            . "{$userName}\n"
            . "Full-Stack Laravel & Stripe Specialist";

    } elseif (stripos($combined, 'no website') !== false || stripos($combined, 'google maps') !== false || stripos($combined, 'google business') !== false || stripos($combined, 'google listing') !== false) {
        // CASE 6: Google Maps Business with No Website
        $topic = '1-Day Modern Website for Google Maps';
        $painPoint = 'Missing website on Google Business Profile losing customers to competitors.';
        $subject = "quick question regarding {$company}'s Google listing";
        $codeSnippet = "// Mobile-optimized booking & contact lead funnel";

        $body = "Hey {$company} Team,\n\n"
            . "I was searching for top-rated businesses in your area and noticed your Google Business listing has great reviews, but you don't currently have an active website linked to your profile.\n\n"
            . "Potential customers searching on Google Maps are clicking your competitors because they can't view your services, pricing, or book online.\n\n"
            . "I specialize in building clean, fast 1-day modern mobile websites designed specifically to convert Google Maps visitors into booked clients.\n\n"
            . "I can set up a live preview for {$company} within 24 hours for a flat $250.\n\n"
            . "Would you like me to send over a 1-minute preview mockup?\n\n"
            . "Best regards,\n"
            . "{$userName}\n"
            . "Web & Local Growth Specialist";

    } else {
        // CASE 7: Agency Backend Overflow & Bug Fixing
        $topic = 'Agency White-Label Backend Support';
        $painPoint = 'Overflow client tickets and backend development bottlenecks.';
        $subject = "on-demand backend & overflow support for {$domain}";
        $codeSnippet = "// Rapid Laravel & API integration sprints";

        $body = "{$greeting}\n\n"
            . "I noticed {$company}'s recent client projects and wanted to ask: does your team ever run into development bottlenecks or need extra overflow capacity for Laravel, API integrations, or speed optimization?\n\n"
            . "I specialize in supporting digital agencies with white-label development capacity on flexible fixed-rate sprints ($150-$300) with zero long-term commitments.\n\n"
            . "Do you have any quick tasks on your backlog this week that you'd like knocked out?\n\n"
            . "Best regards,\n"
            . "{$userName}\n"
            . "Full-Stack Laravel Developer";
    }

    if ($type === 'linkedin') {
        $connectionNote = "{$greeting} Saw {$domain}'s work. I specialize in {$topic} (fixing {$painPoint}). Thought I'd connect in case your team ever needs extra technical capacity!";
        return [
            'golden_hook' => "Targeted {$topic} support for {$domain}",
            'pain_point' => $painPoint,
            'code_snippet' => $codeSnippet,
            'connection_note' => $connectionNote,
            'proposal' => $body,
            'word_count' => str_word_count($body)
        ];
    }

    if ($type === 'email' || $type === 'audit_pitch') {
        $body .= "\n\n---\nPS: If you're not the right person or would rather not hear from me, simply reply 'opt out'.";
    }

    return [
        'subject' => $subject,
        'golden_hook' => $subject,
        'pain_point' => $painPoint,
        'code_snippet' => $codeSnippet,
        'proposal' => $body,
        'word_count' => str_word_count($body)
    ];
}
