<?php
/**
 * LeadForge AI - Multi-Service Agency Directory & Outreach Engine (US/UK/Global)
 * Covers: Web Development, SEO, Google Ads & Performance Marketing, E-Commerce
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

$country = $_GET['country'] ?? 'all';
$category = $_GET['category'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$agencies = [
    // --- UNITED STATES: SEO, ADS & WEB AGENCIES ---
    [
        'id' => 'us_seo_1',
        'name' => 'Single Grain Marketing',
        'website' => 'https://www.singlegrain.com',
        'direct_email' => 'contact@singlegrain.com',
        'country' => 'United States',
        'city' => 'Los Angeles, CA',
        'category' => 'SEO, Google Ads & Growth',
        'size' => '50-100 employees',
        'tech_gap' => 'Focuses on ad strategy; constantly needs technical SEO, speed optimization, and GA4 tag tracking implementations.',
        'outreach_angle' => 'Technical SEO & GA4 Server-Side Tracking Partner.',
        'target_roles' => ['VP of SEO', 'Director of Paid Media']
    ],
    [
        'id' => 'us_ads_1',
        'name' => 'Disruptive Advertising',
        'website' => 'https://disruptiveadvertising.com',
        'direct_email' => 'contact@disruptiveadvertising.com',
        'country' => 'United States',
        'city' => 'Pleasant Grove, UT',
        'category' => 'Google Ads & PPC Performance',
        'size' => '100-150 employees',
        'tech_gap' => 'Manages $100M+ in ad spend; needs landing page speed optimization and Google Ads conversion API fixes.',
        'outreach_angle' => 'PPC Landing Page Developer & Conversion Rate Optimizer.',
        'target_roles' => ['Head of Paid Search', 'Operations Lead']
    ],
    [
        'id' => 'us_1',
        'name' => 'Blue Fountain Media',
        'website' => 'https://www.bluefountainmedia.com',
        'direct_email' => 'info@bluefountainmedia.com',
        'country' => 'United States',
        'city' => 'New York, NY',
        'category' => 'Full-Service Digital & Web',
        'size' => '50-100 employees',
        'tech_gap' => 'Heavy on branding; outsources custom Laravel, API integrations, and technical audits.',
        'outreach_angle' => 'White-label Backend, SEO & API Support.',
        'target_roles' => ['Technical Director', 'Head of Production']
    ],
    [
        'id' => 'us_2',
        'name' => 'Lounge Lizard Worldwide',
        'website' => 'https://www.loungelizard.com',
        'direct_email' => 'sales@loungelizard.com',
        'country' => 'United States',
        'city' => 'New York / Miami',
        'category' => 'Web Design & E-Commerce',
        'size' => '30-60 employees',
        'tech_gap' => 'Builds high-end UI; needs fast turnarounds on Shopify/Laravel bug fixing and tracking.',
        'outreach_angle' => 'Emergency bug fixing & on-demand dev sprint capacity.',
        'target_roles' => ['Director of Web Development', 'VP of Operations']
    ],
    [
        'id' => 'us_seo_2',
        'name' => 'Victorious SEO',
        'website' => 'https://victoriousseo.com',
        'direct_email' => 'info@victoriousseo.com',
        'country' => 'United States',
        'city' => 'San Francisco, CA',
        'category' => 'Pure-Play Technical SEO',
        'size' => '60-120 employees',
        'tech_gap' => 'Provides SEO strategy audits but lacks development resources to execute backend code fixes.',
        'outreach_angle' => 'Technical SEO Implementation Developer for client roadmaps.',
        'target_roles' => ['Head of Technical SEO', 'Client Success Director']
    ],

    // --- UNITED KINGDOM: SEO, PPC & DEV AGENCIES ---
    [
        'id' => 'uk_seo_1',
        'name' => 'Impression Digital UK',
        'website' => 'https://www.impressiondigital.com',
        'direct_email' => 'hello@impressiondigital.com',
        'country' => 'United Kingdom',
        'city' => 'Nottingham / London',
        'category' => 'Performance Marketing & SEO',
        'size' => '80-140 employees',
        'tech_gap' => 'High volume of UK client audits needing Core Web Vitals speed fixes and tracking setups.',
        'outreach_angle' => 'Core Web Vitals & Technical Speed Optimization Partner.',
        'target_roles' => ['Head of SEO', 'Technical Director']
    ],
    [
        'id' => 'uk_ads_1',
        'name' => 'Circus PPC Agency UK',
        'website' => 'https://circusppc.com',
        'direct_email' => 'info@circusppc.com',
        'country' => 'United Kingdom',
        'city' => 'Leeds / London',
        'category' => 'PPC & Google Ads Specialists',
        'size' => '20-40 employees',
        'tech_gap' => 'PPC-only agency; frequently gets requests for conversion tracking, GTM, and landing page dev.',
        'outreach_angle' => 'White-label Tracking & Landing Page Dev Partner for PPC.',
        'target_roles' => ['Managing Director', 'Head of PPC']
    ],
    [
        'id' => 'uk_1',
        'name' => 'Cyber-Duck Digital Agency',
        'website' => 'https://www.cyber-duck.co.uk',
        'direct_email' => 'experience@caci.co.uk',
        'country' => 'United Kingdom',
        'city' => 'London / Elstree',
        'category' => 'Digital Transformation & Web',
        'size' => '50-90 employees',
        'tech_gap' => 'Extensive PHP/Laravel client projects with recurring maintenance contracts.',
        'outreach_angle' => 'Maintenance partner for SLA bug fixes and Laravel migrations.',
        'target_roles' => ['Head of Technology', 'Operations Manager']
    ],
    [
        'id' => 'uk_3',
        'name' => 'Fat Media UK',
        'website' => 'https://www.fatmedia.co.uk',
        'direct_email' => 'contact@fatmedia.co.uk',
        'country' => 'United Kingdom',
        'city' => 'Lancaster / London / Manchester',
        'category' => 'Full-Service Digital Marketing',
        'size' => '80-120 employees',
        'tech_gap' => 'Primarily marketing & SEO agency; regularly needs technical PHP/API integrations.',
        'outreach_angle' => 'White-label web development partner for marketing clients.',
        'target_roles' => ['Digital Strategy Director', 'Technical Lead']
    ],

    // --- CANADA & AUSTRALIA ---
    [
        'id' => 'ca_1',
        'name' => 'Northern Commerce',
        'website' => 'https://www.northern.co',
        'direct_email' => 'partners@northerncommerce.ca',
        'country' => 'Canada',
        'city' => 'London, ON / Toronto',
        'category' => 'E-Commerce, SEO & Ads',
        'size' => '100+ employees',
        'tech_gap' => 'High demand for custom ERP/CRM integrations, Google Ads tracking, and Laravel microservices.',
        'outreach_angle' => 'Custom API & database integration specialist.',
        'target_roles' => ['VP of Technology', 'Lead Architect']
    ],
    [
        'id' => 'au_1',
        'name' => 'Megaphone Marketing Australia',
        'website' => 'https://megaphonemarketing.com.au',
        'direct_email' => 'info@megaphonemarketing.com.au',
        'country' => 'Australia',
        'city' => 'Melbourne / Sydney',
        'category' => 'Google Ads, Meta Ads & E-Com',
        'size' => '90-150 employees',
        'tech_gap' => 'Heavy ad scaling agency requiring rapid Shopify/web fixes and overnight bug resolution.',
        'outreach_angle' => 'Overnight technical sprint completion (tasks resolved while Melbourne sleeps).',
        'target_roles' => ['Operations Director', 'Head of Paid Media']
    ]
];

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'agency.php') {
    // Filtering
    $filtered = array_filter($agencies, function ($agency) use ($country, $category, $search) {
        if ($country !== 'all' && $agency['country'] !== $country) return false;
        if ($category !== 'all' && stripos($agency['category'], $category) === false) return false;
        if (!empty($search)) {
            $q = strtolower($search);
            $match = stripos($agency['name'], $q) !== false ||
                     stripos($agency['city'], $q) !== false ||
                     stripos($agency['category'], $q) !== false ||
                     stripos($agency['tech_gap'], $q) !== false;
            if (!$match) return false;
        }
        return true;
    });

    echo json_encode([
        'status' => 'success',
        'total' => count($filtered),
        'agencies' => array_values($filtered)
    ]);
    exit;
}
