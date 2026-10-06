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
    // --- UNITED STATES AGENCIES ---
    [
        'id' => 'us_seo_1',
        'name' => 'Single Grain Marketing',
        'website' => 'https://www.singlegrain.com',
        'direct_email' => 'contact@singlegrain.com',
        'country' => 'United States',
        'city' => 'Los Angeles, CA',
        'category' => 'SEO, Google Ads & Growth',
        'size' => '50-100 employees',
        'tech_gap' => 'Needs technical SEO, speed optimization, and GA4 tag tracking implementations.',
        'outreach_angle' => 'Technical SEO & GA4 Server-Side Tracking Partner.',
        'target_roles' => ['VP of SEO', 'Director of Paid Media']
    ],
    [
        'id' => 'us_ads_1',
        'name' => 'Disruptive Advertising',
        'website' => 'https://disruptiveadvertising.com',
        'direct_email' => null, // Scrape live contact page
        'country' => 'United States',
        'city' => 'Pleasant Grove, UT',
        'category' => 'Google Ads & PPC Performance',
        'size' => '100-150 employees',
        'tech_gap' => 'Needs landing page speed optimization and Google Ads conversion API fixes.',
        'outreach_angle' => 'PPC Landing Page Developer & Conversion Rate Optimizer.',
        'target_roles' => ['Head of Paid Search', 'Operations Lead']
    ],
    [
        'id' => 'us_1',
        'name' => 'Blue Fountain Media',
        'website' => 'https://www.bluefountainmedia.com',
        'direct_email' => null,
        'country' => 'United States',
        'city' => 'New York, NY',
        'category' => 'Full-Service Digital & Web',
        'size' => '50-100 employees',
        'tech_gap' => 'Outsources custom Laravel, API integrations, and technical audits.',
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
        'tech_gap' => 'Lacks development resources to execute backend code fixes from audits.',
        'outreach_angle' => 'Technical SEO Implementation Developer for client roadmaps.',
        'target_roles' => ['Head of Technical SEO', 'Client Success Director']
    ],
    [
        'id' => 'us_dev_3',
        'name' => 'SmartSites Digital Marketing',
        'website' => 'https://www.smartsites.com',
        'direct_email' => 'contact@smartsites.com',
        'country' => 'United States',
        'city' => 'Paramus, NJ',
        'category' => 'Web Development & PPC',
        'size' => '100-250 employees',
        'tech_gap' => 'High volume of small business websites needing fast PHP/WordPress optimizations.',
        'outreach_angle' => 'On-demand Web Dev & Speed Sprint Support.',
        'target_roles' => ['Managing Director', 'Head of Dev']
    ],
    [
        'id' => 'us_ecom_4',
        'name' => 'Coalition Technologies',
        'website' => 'https://coalitiontechnologies.com',
        'direct_email' => null,
        'country' => 'United States',
        'city' => 'Los Angeles, CA',
        'category' => 'SEO & Shopify E-Commerce',
        'size' => '200+ employees',
        'tech_gap' => 'Custom Shopify app integrations and Core Web Vitals speed tuning.',
        'outreach_angle' => 'Shopify Liquid & Full Stack Dev Partner.',
        'target_roles' => ['Director of SEO', 'Lead Developer']
    ],

    // --- UNITED KINGDOM AGENCIES ---
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
        'id' => 'uk_dev_2',
        'name' => 'Hallam Internet UK',
        'website' => 'https://www.hallaminternet.com',
        'direct_email' => null,
        'country' => 'United Kingdom',
        'city' => 'Nottingham / London',
        'category' => 'Strategic Digital Marketing',
        'size' => '50-80 employees',
        'tech_gap' => 'Backend database & tracking implementations.',
        'outreach_angle' => 'Technical Web & Analytics Specialist.',
        'target_roles' => ['Head of Operations', 'Technical SEO Lead']
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

    // --- CANADA AGENCIES ---
    [
        'id' => 'ca_1',
        'name' => 'Northern Commerce',
        'website' => 'https://www.northern.co',
        'direct_email' => null,
        'country' => 'Canada',
        'city' => 'London, ON / Toronto',
        'category' => 'E-Commerce, SEO & Ads',
        'size' => '100+ employees',
        'tech_gap' => 'High demand for custom ERP/CRM integrations, Google Ads tracking, and Laravel microservices.',
        'outreach_angle' => 'Custom API & database integration specialist.',
        'target_roles' => ['VP of Technology', 'Lead Architect']
    ],
    [
        'id' => 'ca_2',
        'name' => 'Major Tom Digital',
        'website' => 'https://www.majortom.com',
        'direct_email' => null,
        'country' => 'Canada',
        'city' => 'Vancouver / Toronto',
        'category' => 'Full-Service Digital Agency',
        'size' => '80-120 employees',
        'tech_gap' => 'Custom web development and marketing automation.',
        'outreach_angle' => 'Full-Stack Developer for Overflow Sprint Work.',
        'target_roles' => ['VP Technology', 'Director of Web']
    ],
    [
        'id' => 'ca_3',
        'name' => 'Search Engine People',
        'website' => 'https://www.searchenginepeople.com',
        'direct_email' => null,
        'country' => 'Canada',
        'city' => 'Pickering / Toronto, ON',
        'category' => 'SEO & Paid Search Agency',
        'size' => '50-100 employees',
        'tech_gap' => 'Implementation of schema markup, Core Web Vitals, and tracking.',
        'outreach_angle' => 'Technical Implementation Partner for SEO Audits.',
        'target_roles' => ['Director of SEO', 'Director of PPC']
    ],

    // --- AUSTRALIA AGENCIES ---
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
    ],
    [
        'id' => 'au_2',
        'name' => 'Reload Media Australia',
        'website' => 'https://www.reloadmedia.com.au',
        'direct_email' => null,
        'country' => 'Australia',
        'city' => 'Brisbane / Sydney',
        'category' => 'Digital Marketing & Growth',
        'size' => '40-70 employees',
        'tech_gap' => 'E-Commerce tracking and PHP/Shopify technical fixes.',
        'outreach_angle' => 'E-Commerce Developer & Tracking Specialist.',
        'target_roles' => ['Head of Digital', 'Technical Lead']
    ],
    [
        'id' => 'au_3',
        'name' => 'King Kong Digital Australia',
        'website' => 'https://kingkong.co',
        'direct_email' => null,
        'country' => 'Australia',
        'city' => 'Melbourne, VIC',
        'category' => 'High-Conversion Funnels & PPC',
        'size' => '70-120 employees',
        'tech_gap' => 'High-speed landing page development and custom funnel tracking scripts.',
        'outreach_angle' => 'High-Speed Landing Page & Conversion CAPI Developer.',
        'target_roles' => ['Head of Production', 'CRO Lead']
    ],

    // --- INDIA AGENCIES ---
    [
        'id' => 'in_1',
        'name' => 'Schbang Digital India',
        'website' => 'https://www.schbang.com',
        'direct_email' => null,
        'country' => 'India',
        'city' => 'Mumbai / Bangalore',
        'category' => 'Full-Service Digital & Tech Agency',
        'size' => '200+ employees',
        'tech_gap' => 'Custom enterprise web applications, tracking, and high-load backend integrations.',
        'outreach_angle' => 'White-Label Enterprise Laravel & API Specialist.',
        'target_roles' => ['Head of Technology', 'Vice President']
    ],
    [
        'id' => 'in_2',
        'name' => 'FoxyMoron Digital Media',
        'website' => 'https://www.foxymoron.in',
        'direct_email' => null,
        'country' => 'India',
        'city' => 'Gurugram / Mumbai',
        'category' => 'Performance Marketing & E-Commerce',
        'size' => '100+ employees',
        'tech_gap' => 'E-Commerce tracking and high-conversion landing page builds.',
        'outreach_angle' => 'GA4 Tracking & Shopify Performance Developer.',
        'target_roles' => ['Technical Director', 'Head of Media']
    ],

    // --- UAE (DUBAI) AGENCIES ---
    [
        'id' => 'uae_1',
        'name' => 'NNC Media Dubai',
        'website' => 'https://www.nnc.ae',
        'direct_email' => null,
        'country' => 'United Arab Emirates',
        'city' => 'Dubai, Business Bay',
        'category' => 'Luxury E-Commerce & Growth',
        'size' => '50-100 employees',
        'tech_gap' => 'High-ticket luxury Shopify & Magento technical speed optimization.',
        'outreach_angle' => 'Luxury E-Commerce Developer & Speed Specialist.',
        'target_roles' => ['Managing Director', 'Technical Lead']
    ],

    // --- SINGAPORE & EUROPE AGENCIES ---
    [
        'id' => 'sg_1',
        'name' => 'Construct Digital Singapore',
        'website' => 'https://www.constructdigital.com',
        'direct_email' => null,
        'country' => 'Singapore',
        'city' => 'Singapore Central',
        'category' => 'B2B Digital & Web Development',
        'size' => '40-80 employees',
        'tech_gap' => 'B2B portal development and custom API integrations.',
        'outreach_angle' => 'Custom Web Application & API Partner.',
        'target_roles' => ['Director of Technology', 'Operations Head']
    ],
    [
        'id' => 'de_1',
        'name' => 'Dept Agency Europe',
        'website' => 'https://www.deptagency.com',
        'direct_email' => null,
        'country' => 'Germany',
        'city' => 'Berlin / Amsterdam',
        'category' => 'Global Technology & Marketing',
        'size' => '500+ employees',
        'tech_gap' => 'Large scale PHP/Laravel platforms and cloud integrations.',
        'outreach_angle' => 'Full-Stack Developer for Overflow Sprint Work.',
        'target_roles' => ['Engineering Lead', 'Director of Delivery']
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
