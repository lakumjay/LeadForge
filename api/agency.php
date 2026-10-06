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
    ],
    [
        'id' => 'nl_1',
        'name' => 'TamTam Digital Netherlands',
        'website' => 'https://tamtam.nl',
        'direct_email' => null,
        'country' => 'Netherlands',
        'city' => 'Amsterdam / Utrecht',
        'category' => 'Digital Experience & E-Com',
        'size' => '80-150 employees',
        'tech_gap' => 'Headless e-commerce and high-conversion web performance.',
        'outreach_angle' => 'Headless Laravel & Performance Partner.',
        'target_roles' => ['Technical Lead', 'Head of Development']
    ],
    [
        'id' => 'fr_1',
        'name' => 'SensioLabs France',
        'website' => 'https://sensiolabs.com',
        'direct_email' => null,
        'country' => 'France',
        'city' => 'Paris / Lyon',
        'category' => 'Enterprise Web & Framework Architecture',
        'size' => '100+ employees',
        'tech_gap' => 'Modern PHP, Symfony/Laravel enterprise integrations and API microservices.',
        'outreach_angle' => 'Enterprise PHP & API Developer.',
        'target_roles' => ['CTO', 'Lead Architect']
    ],
    [
        'id' => 'sa_1',
        'name' => 'Bright Creation Saudi',
        'website' => 'https://brightcreation.com',
        'direct_email' => null,
        'country' => 'Saudi Arabia',
        'city' => 'Riyadh / Jeddah',
        'category' => 'E-Commerce & Digital Transformation',
        'size' => '50-100 employees',
        'tech_gap' => 'Payment gateway integrations (Mada/Stripe) and mobile responsiveness.',
        'outreach_angle' => 'Payment Gateway & E-Commerce Integration Specialist.',
        'target_roles' => ['Digital Transformation Lead', 'Managing Director']
    ],
    [
        'id' => 'nz_1',
        'name' => 'Springload New Zealand',
        'website' => 'https://springload.co.nz',
        'direct_email' => null,
        'country' => 'New Zealand',
        'city' => 'Wellington / Auckland',
        'category' => 'Digital Products & Web Strategy',
        'size' => '60-100 employees',
        'tech_gap' => 'Accessible frontend design, technical SEO, and custom backend apps.',
        'outreach_angle' => 'Full-Stack Performance & SEO Specialist.',
        'target_roles' => ['Head of Technology', 'Technical Director']
    ],
    [
        'id' => 'ie_1',
        'name' => 'Continuum Digital Ireland',
        'website' => 'https://continuum.ie',
        'direct_email' => null,
        'country' => 'Ireland',
        'city' => 'Dublin',
        'category' => 'Full-Service Digital Experience',
        'size' => '50-90 employees',
        'tech_gap' => 'CMS performance, API integrations, and GA4 tracking setups.',
        'outreach_angle' => 'White-Label CMS & Analytics Developer.',
        'target_roles' => ['Operations Director', 'Head of Tech']
    ],
    [
        'id' => 'us_tech_1',
        'name' => 'BairesDev Engineering',
        'website' => 'https://www.bairesdev.com',
        'direct_email' => null,
        'country' => 'United States',
        'city' => 'San Francisco, CA',
        'category' => 'Custom Software & Cloud Sprints',
        'size' => '500+ employees',
        'tech_gap' => 'Backend API performance tuning and query optimization.',
        'outreach_angle' => 'On-Demand Backend & Laravel Engineer.',
        'target_roles' => ['Director of Engineering', 'VP Delivery']
    ],
    [
        'id' => 'uk_tech_1',
        'name' => 'Tangent Digital UK',
        'website' => 'https://tangent.co.uk',
        'direct_email' => null,
        'country' => 'United Kingdom',
        'city' => 'London',
        'category' => 'Enterprise Digital Platforms',
        'size' => '80-120 employees',
        'tech_gap' => 'Modern PHP architecture and headless integration sprints.',
        'outreach_angle' => 'White-label Sprint Engineer for Agency Backlog.',
        'target_roles' => ['Tech Lead', 'Delivery Director']
    ],
    [
        'id' => 'us_klientboost',
        'name' => 'KlientBoost Marketing',
        'website' => 'https://klientboost.com',
        'direct_email' => 'friends@klientboost.com',
        'country' => 'United States',
        'city' => 'Costa Mesa, CA',
        'category' => 'Performance Ads & CRO',
        'size' => '120+ employees',
        'tech_gap' => 'Landing page speed & conversion tracking optimization.',
        'outreach_angle' => 'High-Converting Landing Page & Performance Developer.',
        'target_roles' => ['VP of Marketing', 'Head of CRO']
    ],
    [
        'id' => 'us_outerbox',
        'name' => 'OuterBox Design',
        'website' => 'https://www.outerboxdesign.com',
        'direct_email' => 'info@outerboxdesign.com',
        'country' => 'United States',
        'city' => 'Akron, OH',
        'category' => 'E-Commerce SEO & Web Design',
        'size' => '80+ employees',
        'tech_gap' => 'Shopify & WooCommerce speed tuning and custom API development.',
        'outreach_angle' => 'White-label E-Commerce & Backend Developer.',
        'target_roles' => ['Director of Web', 'Technical Lead']
    ],
    [
        'id' => 'us_intergrowth',
        'name' => 'Intergrowth SEO',
        'website' => 'https://intergrowth.com',
        'direct_email' => 'hello@intergrowth.com',
        'country' => 'United States',
        'city' => 'Denver, CO',
        'category' => 'Content & Technical SEO',
        'size' => '30+ employees',
        'tech_gap' => 'Technical Core Web Vitals audits and structured data schema.',
        'outreach_angle' => 'Technical SEO & Page Speed Implementation Partner.',
        'target_roles' => ['Founder', 'SEO Director']
    ],
    [
        'id' => 'us_webmechanix',
        'name' => 'WebMechanix Digital',
        'website' => 'https://www.webmechanix.com',
        'direct_email' => 'info@webmechanix.com',
        'country' => 'United States',
        'city' => 'Columbia, MD',
        'category' => 'Performance Marketing & SEO',
        'size' => '75+ employees',
        'tech_gap' => 'Custom tracking setups and marketing automation scripts.',
        'outreach_angle' => 'Full-Stack Developer for Marketing Automation.',
        'target_roles' => ['VP of Operations', 'Director of Engineering']
    ],
    [
        'id' => 'us_v9',
        'name' => 'Volume Nine Digital',
        'website' => 'https://www.v9digital.com',
        'direct_email' => 'hello@v9digital.com',
        'country' => 'United States',
        'city' => 'Denver, CO',
        'category' => 'SEO & Social Media Agency',
        'size' => '40+ employees',
        'tech_gap' => 'WordPress speed optimization and technical site health fixes.',
        'outreach_angle' => 'Fast Technical Site Health Fixer for Agencies.',
        'target_roles' => ['Director of Search', 'Operations Lead']
    ],
    [
        'id' => 'us_inflow',
        'name' => 'Inflow E-Commerce',
        'website' => 'https://www.goinflow.com',
        'direct_email' => 'info@goinflow.com',
        'country' => 'United States',
        'city' => 'Denver, CO',
        'category' => 'E-Commerce Marketing Agency',
        'size' => '50+ employees',
        'tech_gap' => 'Server-side conversion tracking and Shopify AJAX cart optimizations.',
        'outreach_angle' => 'Server-Side CAPI & Shopify Speed Developer.',
        'target_roles' => ['Head of Paid Media', 'VP of Services']
    ],
    [
        'id' => 'us_ironpaper',
        'name' => 'Ironpaper Growth Agency',
        'website' => 'https://www.ironpaper.com',
        'direct_email' => 'info@ironpaper.com',
        'country' => 'United States',
        'city' => 'New York, NY',
        'category' => 'B2B Growth & Lead Gen',
        'size' => '60+ employees',
        'tech_gap' => 'HubSpot/CRM integrations, custom backend APIs, and web speed.',
        'outreach_angle' => 'B2B Web App & API Integration Specialist.',
        'target_roles' => ['Managing Director', 'Technical Lead']
    ],
    [
        'id' => 'us_lyfe',
        'name' => 'LYFE Marketing',
        'website' => 'https://www.lyfemarketing.com',
        'direct_email' => 'contact@lyfemarketing.com',
        'country' => 'United States',
        'city' => 'Atlanta, GA',
        'category' => 'Social & PPC Management',
        'size' => '80+ employees',
        'tech_gap' => 'Ad landing page creation and Google Tag Manager conversion setups.',
        'outreach_angle' => 'High-Speed Landing Page & GTM Specialist.',
        'target_roles' => ['Director of Paid Media', 'Operations Manager']
    ],
    [
        'id' => 'us_digitalsilk',
        'name' => 'Digital Silk',
        'website' => 'https://www.digitalsilk.com',
        'direct_email' => 'info@digitalsilk.com',
        'country' => 'United States',
        'city' => 'Miami, FL',
        'category' => 'Custom Web & Brand Agency',
        'size' => '150+ employees',
        'tech_gap' => 'Laravel backend development, custom API microservices, and speed optimization.',
        'outreach_angle' => 'On-Demand Senior Laravel & Backend Developer.',
        'target_roles' => ['VP Technology', 'Head of Production']
    ],
    [
        'id' => 'uk_croud',
        'name' => 'Croud Global UK',
        'website' => 'https://croud.com',
        'direct_email' => 'hello@croud.com',
        'country' => 'United Kingdom',
        'city' => 'London',
        'category' => 'Global Performance Agency',
        'size' => '300+ employees',
        'tech_gap' => 'Global multi-currency tracking, Core Web Vitals, and custom APIs.',
        'outreach_angle' => 'White-Label Senior Full-Stack Overflow Partner.',
        'target_roles' => ['Head of Technology', 'Technical Director']
    ],
    [
        'id' => 'uk_click',
        'name' => 'Click Consult UK',
        'website' => 'https://www.click.co.uk',
        'direct_email' => 'hello@click.co.uk',
        'country' => 'United Kingdom',
        'city' => 'Chester / London',
        'category' => 'Search & Digital Marketing',
        'size' => '70+ employees',
        'tech_gap' => 'Technical SEO audits, schema injection, and backend crawl optimization.',
        'outreach_angle' => 'Technical SEO & Page Speed Implementer.',
        'target_roles' => ['Search Director', 'Technical Lead']
    ],
    [
        'id' => 'uk_passion',
        'name' => 'Passion Digital London',
        'website' => 'https://passion.digital',
        'direct_email' => 'info@passion.digital',
        'country' => 'United Kingdom',
        'city' => 'London',
        'category' => 'Performance Marketing & SEO',
        'size' => '50+ employees',
        'tech_gap' => 'Google Analytics 4 server-side setups and WordPress performance tuning.',
        'outreach_angle' => 'GA4 Tracking & WordPress Speed Specialist.',
        'target_roles' => ['Head of Paid Media', 'Managing Director']
    ],
    [
        'id' => 'ca_appnovation',
        'name' => 'Appnovation Canada',
        'website' => 'https://www.appnovation.com',
        'direct_email' => 'info@appnovation.com',
        'country' => 'Canada',
        'city' => 'Vancouver, BC / Toronto',
        'category' => 'Global Digital Consultancy',
        'size' => '350+ employees',
        'tech_gap' => 'Enterprise PHP, Laravel, headless CMS, and cloud backend microservices.',
        'outreach_angle' => 'Enterprise Laravel & Backend Integration Engineer.',
        'target_roles' => ['Director of Delivery', 'VP Technology']
    ],
    [
        'id' => 'ca_redstamp',
        'name' => 'Red Stamp Agency',
        'website' => 'https://redstamp.ca',
        'direct_email' => 'hello@redstamp.ca',
        'country' => 'Canada',
        'city' => 'Port Moody, BC / Vancouver',
        'category' => 'Tech & SaaS Growth Agency',
        'size' => '25+ employees',
        'tech_gap' => 'SaaS web app development, API connections, and conversion rate optimization.',
        'outreach_angle' => 'Full-Stack SaaS & Web App Developer.',
        'target_roles' => ['Founder', 'Head of Web']
    ],
    [
        'id' => 'au_omg',
        'name' => 'Online Marketing Gurus AU',
        'website' => 'https://www.onlinemarketinggurus.com.au',
        'direct_email' => 'hello@onlinemarketinggurus.com.au',
        'country' => 'Australia',
        'city' => 'Sydney, NSW',
        'category' => 'SEO & PPC Growth Agency',
        'size' => '150+ employees',
        'tech_gap' => 'Overnight technical SEO fixes and Core Web Vitals speed optimization.',
        'outreach_angle' => 'Overnight Timezone Developer Sprint Partner.',
        'target_roles' => ['Head of SEO', 'Director of Production']
    ],
    [
        'id' => 'au_firstpage',
        'name' => 'First Page Digital Australia',
        'website' => 'https://www.firstpage.com.au',
        'direct_email' => 'info@firstpage.com.au',
        'country' => 'Australia',
        'city' => 'Melbourne, VIC',
        'category' => 'SEO, Ads & Web Growth',
        'size' => '100+ employees',
        'tech_gap' => 'Rapid bug fixes, conversion tracking setups, and landing page development.',
        'outreach_angle' => 'Agile On-Demand Bug Fixer & Speed Developer.',
        'target_roles' => ['Technical Director', 'Head of Performance']
    ],
    [
        'id' => 'sg_firstpage',
        'name' => 'First Page Singapore',
        'website' => 'https://www.firstpagesingapore.com',
        'direct_email' => 'info@firstpagesingapore.com',
        'country' => 'Singapore',
        'city' => 'Singapore CBD',
        'category' => 'Digital Marketing & SEO',
        'size' => '60+ employees',
        'tech_gap' => 'Technical SEO audits, speed enhancement, and API connectivity.',
        'outreach_angle' => 'B2B Web Application & SEO Developer.',
        'target_roles' => ['Operations Director', 'Head of Tech']
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
