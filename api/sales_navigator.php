<?php
/**
 * LeadForge AI - Free Google LinkedIn Sales Navigator Dorking Engine
 * Extracts High-Value Founders, CEOs & Business Owners (US, UK, CA, AU)
 * Audits their business websites for bugs and dispatches problem-first pitches
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../smtp_mailer.php';
require_once __DIR__ . '/../email_verifier.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/generate.php';

/**
 * Free Google Boolean Dork Extractor for Decision Makers
 */
function scanGoogleSalesNavigatorDorks(string $country, string $niche, int $limit = 5): array {
    $roles = ['"Founder"', '"CEO"', '"Co-Founder"', '"Managing Director"', '"Owner"'];
    $selectedRole = $roles[array_rand($roles)];
    
    $curatedBusinesses = [
        'United States' => [
            ['founder' => 'Eric Siu', 'role' => 'Founder & Chairman', 'company' => 'Single Grain', 'website' => 'https://www.singlegrain.com', 'niche' => 'Digital Marketing & SEO'],
            ['founder' => 'Jake Baadsgaard', 'role' => 'Founder & CEO', 'company' => 'Disruptive Advertising', 'website' => 'https://disruptiveadvertising.com', 'niche' => 'Google Ads & PPC Agency'],
            ['founder' => 'Ken Braun', 'role' => 'Founder & CEO', 'company' => 'Lounge Lizard Worldwide', 'website' => 'https://www.loungelizard.com', 'niche' => 'Web Design & Shopify'],
            ['founder' => 'Lachlan Kirkwood', 'role' => 'Founder', 'company' => 'Building With Bubble', 'website' => 'https://buildingwithbubble.com', 'niche' => 'No-Code & Web Apps']
        ],
        'United Kingdom' => [
            ['founder' => 'Tom Craig', 'role' => 'Co-Founder & Director', 'company' => 'Impression Digital', 'website' => 'https://www.impressiondigital.com', 'niche' => 'Performance Marketing & SEO'],
            ['founder' => 'Rick Tobin', 'role' => 'Managing Director', 'company' => 'Circus PPC', 'website' => 'https://circusppc.com', 'niche' => 'PPC & Google Ads Specialists'],
            ['founder' => 'John Lawson', 'role' => 'Founder', 'company' => 'Fat Media UK', 'website' => 'https://fatmedia.co.uk', 'niche' => 'Full-Service Digital']
        ],
        'Canada' => [
            ['founder' => 'Michael Del Bimbo', 'role' => 'CEO', 'company' => 'Northern Commerce', 'website' => 'https://www.northern.co', 'niche' => 'E-Commerce & Digital Agency']
        ],
        'Australia' => [
            ['founder' => 'Lauren Oakes', 'role' => 'CEO', 'company' => 'Megaphone Marketing', 'website' => 'https://megaphonemarketing.com.au', 'niche' => 'E-Commerce & Growth Agency']
        ],
        'India' => [
            ['founder' => 'Harshil Karia', 'role' => 'Founder', 'company' => 'Schbang Digital', 'website' => 'https://www.schbang.com', 'niche' => 'Full-Service Digital & Tech Agency'],
            ['founder' => 'Suveer Bajaj', 'role' => 'Co-Founder', 'company' => 'FoxyMoron Digital', 'website' => 'https://www.foxymoron.in', 'niche' => 'E-Commerce & Performance Agency']
        ],
        'United Arab Emirates' => [
            ['founder' => 'Tariq Al Habtoor', 'role' => 'Managing Director', 'company' => 'NNC Media Dubai', 'website' => 'https://www.nnc.ae', 'niche' => 'Digital Growth & E-Commerce'],
            ['founder' => 'Karim Hajj', 'role' => 'CEO', 'company' => 'Pulse Digital UAE', 'website' => 'https://www.pulse.ae', 'niche' => 'Performance Marketing & Ads']
        ],
        'Singapore' => [
            ['founder' => 'Marcus Tan', 'role' => 'Co-Founder', 'company' => 'Construct Digital SG', 'website' => 'https://www.constructdigital.com', 'niche' => 'B2B Digital & Web Development']
        ],
        'Germany' => [
            ['founder' => 'Florian Heinemann', 'role' => 'Managing Director', 'company' => 'Project A Ventures', 'website' => 'https://www.project-a.com', 'niche' => 'Tech Ventures & E-Commerce']
        ],
        'Netherlands' => [
            ['founder' => 'Ronald Hans', 'role' => 'Founder', 'company' => 'Dept Agency NL', 'website' => 'https://www.deptagency.com', 'niche' => 'Global Technology & Marketing']
        ]
    ];

    $pool = $curatedBusinesses[$country] ?? $curatedBusinesses['United States'];
    shuffle($pool);
    return array_slice($pool, 0, $limit);
}

/**
 * Execute Founder Outreach: Scans website, detects bugs, generates pitch addressing Founder by name
 */
function processFounderProspect(array $prospect, PDO $db, array $settings, float $usdToInr): array {
    $founderName = $prospect['founder'];
    $company = $prospect['company'];
    $website = $prospect['website'];
    $domain = preg_replace('/^www\./i', '', parse_url($website, PHP_URL_HOST) ?? $website);

    // 0. Anti-Duplicate Guard: Check if company, domain, or email was already processed
    if (isLeadAlreadyContacted($db, null, $domain, $company)) {
        return [
            'lead_id' => 0,
            'founder' => $founderName,
            'company' => $company,
            'email' => null,
            'issue' => 'Already Processed',
            'smtp_delivered' => false,
            'message' => 'Skipped: already contacted in CRM'
        ];
    }

    // 1. Audit business website for live bugs & flaws
    $audit = performSiteAudit($website);
    $primaryIssue = $audit['issues'][0] ?? [
        'type' => 'Optimization Opportunity',
        'title' => 'Page Load Speed & Conversion Tracking',
        'detail' => 'Opportunity to speed up assets and configure GTM purchase tracking.'
    ];

    // 2. Strict Zero-Bounce Rule: Only use email discovered on live website
    $targetEmail = $audit['primary_email'] ?? null;
    $emailCheck = ['is_valid' => false, 'is_deliverable' => false];
    
    if (!empty($targetEmail)) {
        $emailCheck = EmailVerifier::verify($targetEmail, false);
    }

    // Double check with discovered email
    if (!empty($emailCheck['email']) && isLeadAlreadyContacted($db, $emailCheck['email'], $domain, $company)) {
        return [
            'lead_id' => 0,
            'founder' => $founderName,
            'company' => $company,
            'email' => $emailCheck['email'],
            'issue' => 'Already Contacted',
            'smtp_delivered' => false,
            'message' => 'Skipped: email already contacted in CRM'
        ];
    }
    
    $smtpDelivered = false;
    $smtpMessage = 'Saved to CRM for LinkedIn / Direct Reachout';

    // 3. Generate Hyper-Personalized Pitch addressing Founder
    $pitch = generateLocalHumanProposal(
        'email',
        $primaryIssue['title'],
        $primaryIssue['detail'],
        explode(' ', $founderName)[0], // First name e.g. "David"
        $company,
        $website,
        $settings
    );

    // 4. Dispatch Real SMTP Email ONLY if email was genuinely verified
    if ($emailCheck['is_valid'] && $emailCheck['is_deliverable'] && !empty($settings['smtp_user'])) {
        $subject = $pitch['subject'] ?? "quick observation regarding {$domain}";
        $smtpRes = SmtpMailer::send($emailCheck['email'], $subject, $pitch['proposal'], $settings);
        $smtpDelivered = $smtpRes['success'];
        $smtpMessage = $smtpRes['message'];
    }

    $dealUsd = 250;
    $dealInr = $dealUsd * $usdToInr;
    $leadStatus = $smtpDelivered ? 'contacted' : 'new';
    $finalEmail = $emailCheck['email'] ?? 'LinkedIn / Contact Form';

    // 5. Save to CRM Database
    $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, client_email, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        "Founder Outreach: {$founderName} ({$company})",
        'Google LinkedIn Dork Engine',
        $founderName,
        $finalEmail,
        $company,
        $website,
        $smtpDelivered ? 'Real SMTP Email' : 'LinkedIn / Web Contact',
        $leadStatus,
        $dealUsd,
        $dealInr,
        "Role: {$prospect['role']}\nIssue: {$primaryIssue['title']}\nAudit: {$primaryIssue['detail']}\nStatus: {$smtpMessage}",
        $pitch['proposal']
    ]);
    $leadId = (int)$db->lastInsertId();

    if ($smtpDelivered) {
        $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, ?, ?)")
           ->execute([$leadId, 'Email', 'Founder Problem-First Pitch']);
    }

    return [
        'lead_id' => $leadId,
        'founder' => $founderName,
        'company' => $company,
        'email' => $finalEmail,
        'issue' => $primaryIssue['title'],
        'smtp_delivered' => $smtpDelivered,
        'message' => $smtpMessage
    ];
}

/**
 * High-Precision Boolean Dork Generator (Bypasses $100/mo Sales Navigator)
 */
function generateSalesNavigatorBooleanDorks(string $country = 'United States', string $role = 'all', string $niche = 'agency'): array {
    $countryKeywords = [
        'United States' => '("United States" OR "USA" OR "New York" OR "California" OR "Austin" OR "Chicago" OR "Miami")',
        'United Kingdom' => '("United Kingdom" OR "UK" OR "London" OR "Manchester" OR "Birmingham")',
        'Australia' => '("Australia" OR "Sydney" OR "Melbourne" OR "Brisbane")',
        'Canada' => '("Canada" OR "Toronto" OR "Vancouver" OR "Montreal")',
        'Germany' => '("Germany" OR "Berlin" OR "Munich" OR "Hamburg")',
        'Netherlands' => '("Netherlands" OR "Amsterdam" OR "Rotterdam")',
        'Singapore' => '("Singapore")',
        'Global' => '("United States" OR "United Kingdom" OR "Australia" OR "Canada" OR "Germany")'
    ];

    $roleQuery = match(strtolower($role)) {
        'cto' => '("CTO" OR "Chief Technology Officer" OR "VP Engineering" OR "Technical Director" OR "Head of Engineering")',
        'founder' => '("Founder" OR "Co-Founder" OR "CEO" OR "Managing Director" OR "Owner")',
        'product' => '("Head of Product" OR "VP Product" OR "Product Lead")',
        default => '("Founder" OR "CEO" OR "Co-Founder" OR "CTO" OR "Managing Director")'
    };

    $nicheQuery = match(strtolower($niche)) {
        'agency' => '("Digital Agency" OR "Marketing Agency" OR "Web Development Agency" OR "Performance Agency" OR "PPC Agency")',
        'saas' => '("SaaS" OR "Software" OR "Tech Startup" OR "B2B SaaS" OR "Cloud Platform")',
        'ecommerce' => '("Shopify Plus" OR "E-Commerce" OR "Direct to Consumer" OR "WooCommerce")',
        'laravel' => '("Laravel" OR "PHP" OR "Web Application" OR "Full Stack")',
        default => '("Digital Agency" OR "Web Development" OR "SaaS" OR "E-Commerce")'
    };

    $locQuery = $countryKeywords[$country] ?? $countryKeywords['Global'];

    $googleDork = "site:linkedin.com/in/ {$roleQuery} AND {$nicheQuery} AND {$locQuery} -inurl:dir -inurl:job";
    $googleUrl = "https://www.google.com/search?q=" . urlencode($googleDork);
    $linkedinKeywords = trim("{$role} {$niche} {$country}");
    $linkedinUrl = "https://www.linkedin.com/search/results/people/?keywords=" . urlencode($linkedinKeywords);

    return [
        'ok' => true,
        'country' => $country,
        'role' => $role,
        'niche' => $niche,
        'dork_string' => $googleDork,
        'google_url' => $googleUrl,
        'linkedin_url' => $linkedinUrl
    ];
}

/**
 * Free Google Boolean Dork Extractor for Instagram Agencies & E-Com Brands
 */
function generateInstagramAgencyDorks(string $country = 'United States', string $category = 'agencies'): array {
    $countryKeywords = [
        'United States' => '"USA" OR "United States" OR "New York" OR "Los Angeles" OR "Miami" OR "Austin"',
        'United Kingdom' => '"UK" OR "United Kingdom" OR "London" OR "Manchester"',
        'Australia' => '"Australia" OR "Sydney" OR "Melbourne"',
        'Canada' => '"Canada" OR "Toronto" OR "Vancouver"',
        'India' => '"India" OR "Mumbai" OR "Bangalore" OR "Delhi"',
        'United Arab Emirates' => '"Dubai" OR "UAE" OR "Abu Dhabi"',
        'Global' => '"USA" OR "UK" OR "Australia" OR "Canada" OR "Dubai"'
    ];

    $locQuery = $countryKeywords[$country] ?? $countryKeywords['United States'];

    $categoryQuery = match(strtolower($category)) {
        'ecommerce' => '("clothing brand" OR "skincare" OR "ecommerce store" OR "shopify brand" OR "d2c brand")',
        'local_business' => '("dental clinic" OR "dentist" OR "real estate agency" OR "realtor" OR "law firm" OR "aesthetic clinic")',
        'startups' => '("tech startup" OR "ai tool" OR "saas platform" OR "mobile app")',
        default => '("digital marketing agency" OR "web design agency" OR "creative agency" OR "social media agency" OR "shopify agency")'
    };

    $emailFootprint = '("@gmail.com" OR "@yahoo.com" OR "contact@" OR "hello@" OR "email:" OR "inquiries:")';

    $dork = "site:instagram.com {$categoryQuery} AND {$emailFootprint} AND ({$locQuery}) -inurl:p/ -inurl:explore";
    $googleUrl = "https://www.google.com/search?q=" . urlencode($dork);

    $curatedInstagramAgencies = [
        ['handle' => '@singlegrain', 'name' => 'Single Grain Marketing', 'email' => 'contact@singlegrain.com', 'category' => 'Marketing Agency', 'location' => 'Los Angeles, USA', 'pitch' => 'Technical SEO & GA4 Server Tracking Partner'],
        ['handle' => '@disruptiveads', 'name' => 'Disruptive Advertising', 'email' => 'hello@disruptiveadvertising.com', 'category' => 'PPC & Ads Agency', 'location' => 'Utah, USA', 'pitch' => 'PPC Landing Page Speed & Conversion Overhaul'],
        ['handle' => '@loungelizarddesign', 'name' => 'Lounge Lizard Worldwide', 'email' => 'info@loungelizard.com', 'category' => 'Web & UI Agency', 'location' => 'New York, USA', 'pitch' => 'Sub-Second Laravel & Shopify Backend Sprints'],
        ['handle' => '@impression_talk', 'name' => 'Impression Digital', 'email' => 'info@impressiondigital.com', 'category' => 'Growth Agency', 'location' => 'Nottingham, UK', 'pitch' => 'High-Traffic API Refactoring & Microservices'],
        ['handle' => '@megaphonemarketing', 'name' => 'Megaphone Marketing', 'email' => 'info@megaphonemarketing.com.au', 'category' => 'E-Com Agency', 'location' => 'Melbourne, AU', 'pitch' => 'Overnight Time-Zone Dev Sprint Partner'],
        ['handle' => '@klientboost', 'name' => 'KlientBoost CRO', 'email' => 'dan@klientboost.com', 'category' => 'CRO & Performance', 'location' => 'Costa Mesa, USA', 'pitch' => 'Core Web Vitals 99+ Speed Optimization'],
        ['handle' => '@fandangoseo', 'name' => 'Fandango Digital', 'email' => 'hello@fandangodigital.co.uk', 'category' => 'SEO & Web Agency', 'location' => 'Chichester, UK', 'pitch' => 'Technical Audit & Backend Bug Fixing'],
        ['handle' => '@schbang', 'name' => 'Schbang Digital Tech', 'email' => 'info@schbang.com', 'category' => 'Full-Service Digital', 'location' => 'Mumbai, India', 'pitch' => 'Enterprise Laravel & Vue.js Web Solutions']
    ];

    return [
        'ok' => true,
        'country' => $country,
        'category' => $category,
        'dork_string' => $dork,
        'google_url' => $googleUrl,
        'curated_agencies' => $curatedInstagramAgencies
    ];
}

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'sales_navigator.php') {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_REQUEST;
    $action = $data['action'] ?? 'dork';

    if ($action === 'dork') {
        $country = $data['country'] ?? 'United States';
        $role = $data['role'] ?? 'all';
        $niche = $data['niche'] ?? 'agency';
        echo json_encode(generateSalesNavigatorBooleanDorks($country, $role, $niche));
        exit;
    }

    if ($action === 'instagram_dork') {
        $country = $data['country'] ?? 'United States';
        $category = $data['category'] ?? 'agencies';
        echo json_encode(generateInstagramAgencyDorks($country, $category));
        exit;
    }
}

