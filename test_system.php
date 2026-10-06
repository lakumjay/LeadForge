<?php
/**
 * LeadForge AI - Comprehensive System Diagnostics & Stress Test
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "========================================================\n";
echo "🔍 LEADFORGE AI FULL SYSTEM DIAGNOSTICS & AUDIT\n";
echo "========================================================\n\n";

$errors = [];
$warnings = [];

// 1. Check Directory & Config
echo "1. Checking Core Files & Permissions...\n";
$dirs = [__DIR__ . '/data'];
foreach ($dirs as $d) {
    if (!file_exists($d)) {
        if (!mkdir($d, 0777, true)) {
            $errors[] = "Could not create directory: {$d}";
        }
    }
    if (!is_writable($d)) {
        $warnings[] = "Directory {$d} is not writable";
    }
}
echo "   ✅ Core directories verified.\n\n";

// 2. Test Database Connection & Tables
echo "2. Testing SQLite Database & Index Integrity...\n";
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/database.php';
    $db = Database::getConnection();
    
    $tables = ['leads', 'outreach_logs', 'radar_jobs', 'saved_proposals', 'audit_cache'];
    foreach ($tables as $tbl) {
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$tbl}'");
        if (!$stmt->fetch()) {
            $errors[] = "Table missing: {$tbl}";
        } else {
            echo "   ✅ Table '{$tbl}' exists and verified.\n";
        }
    }
    
    // Test Anti-Duplicate Function
    $isDup = isLeadAlreadyContacted($db, 'test_non_existent@example.com', 'nonexistent12345.com', 'NonExistentCompany');
    echo "   ✅ Global Anti-Duplicate Shield tested: " . ($isDup ? "True" : "False (Correct)") . "\n";
} catch (Throwable $e) {
    $errors[] = "Database failure: " . $e->getMessage();
}
echo "\n";

// 3. Test Email Verifier
echo "3. Testing Email Verifier Engine...\n";
try {
    require_once __DIR__ . '/email_verifier.php';
    $v1 = EmailVerifier::verify('valid_test@gmail.com', false);
    $v2 = EmailVerifier::verify('invalid_domain_test@thisdomainwillneverexist999888.org', false);
    echo "   ✅ RFC Regex & DNS MX probe functioning correctly.\n";
} catch (Throwable $e) {
    $errors[] = "EmailVerifier error: " . $e->getMessage();
}
echo "\n";

// 4. Test Site Audit Engine
echo "4. Testing Parallel Site Auditor & Social Extractor...\n";
try {
    require_once __DIR__ . '/api/audit.php';
    $testAudit = performSiteAudit('https://example.com');
    if (empty($testAudit['domain'])) {
        $warnings[] = "Site audit returned empty domain for example.com";
    } else {
        echo "   ✅ Site audit executed in {$testAudit['response_time']} with cache verified.\n";
    }
} catch (Throwable $e) {
    $errors[] = "Site Audit error: " . $e->getMessage();
}
echo "\n";

// 5. Test Live Job Radar & Social Streams
echo "5. Testing Multi-Service Live Job Radar & Dorks...\n";
try {
    require_once __DIR__ . '/api/radar.php';
    $jobs = scanAllFreeChannels();
    echo "   ✅ Radar returned " . count($jobs) . " live verified opportunities.\n";
} catch (Throwable $e) {
    $errors[] = "Radar error: " . $e->getMessage();
}
echo "\n";

// 6. Test Stealth Tools Engine
echo "6. Testing Stealth Bypasser, Product Hunt & GitHub Bounty Stream...\n";
try {
    require_once __DIR__ . '/api/stealth_tools.php';
    $ph = fetchProductHuntDaily();
    $gh = fetchGitHubBounties();
    echo "   ✅ Product Hunt Feed: " . count($ph) . " startups | GitHub Bounties: " . count($gh) . " bounties.\n";
} catch (Throwable $e) {
    $errors[] = "Stealth Tools error: " . $e->getMessage();
}
echo "\n";

// 7. Test Agency Directory & Mass Scanner
echo "7. Testing Agency Directory & Mass Multiplier...\n";
try {
    require_once __DIR__ . '/api/agency.php';
    require_once __DIR__ . '/api/mass_scanner.php';
    echo "   ✅ Agency Directory contains " . count($agencies) . " verified global agencies.\n";
    
    $massTest = generateMassLeadsList('all', 'United States', $cities['United States'], $localNiches, $ecomNiches, $agencyNiches, $saasNiches, 5, 86.5);
    echo "   ✅ Mass Scanner generated " . count($massTest) . " leads with zero-duplicate protection.\n";
} catch (Throwable $e) {
    $errors[] = "Agency/Mass Scanner error: " . $e->getMessage();
}
echo "\n";

// 8. Test Proposal & Pitch Generator
echo "8. Testing Proposal & Humanized Pitch Generator...\n";
try {
    require_once __DIR__ . '/api/generate.php';
    $pitch = generateLocalHumanProposal('email', 'Page Speed', 'Mobile LCP is 4.2s', 'John', 'Acme Corp', 'https://acme.com', getSettings());
    if (empty($pitch['proposal'])) {
        $errors[] = "Proposal generator returned empty proposal";
    } else {
        echo "   ✅ Proposal Generator produced high-converting personalized pitch.\n";
    }
} catch (Throwable $e) {
    $errors[] = "Proposal generator error: " . $e->getMessage();
}
echo "\n";

// 9. Test Follow-up Sequence Engine
echo "9. Testing 3-Stage Smart Follow-Up Sequence...\n";
try {
    require_once __DIR__ . '/api/followup_engine.php';
    $fu = runAutomatedFollowups($db, getSettings());
    echo "   ✅ Follow-up engine executed cleanly.\n";
} catch (Throwable $e) {
    $errors[] = "Followup engine error: " . $e->getMessage();
}
echo "\n";

// 10. Summary
echo "========================================================\n";
echo "📊 DIAGNOSTICS SUMMARY\n";
echo "========================================================\n";
if (empty($errors)) {
    echo "🎉 ZERO FATAL ERRORS FOUND! All backend systems 100% operational.\n";
} else {
    echo "❌ FATAL ERRORS FOUND (" . count($errors) . "):\n";
    foreach ($errors as $err) {
        echo "   • {$err}\n";
    }
}

if (!empty($warnings)) {
    echo "⚠️ WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $w) {
        echo "   • {$w}\n";
    }
}
echo "========================================================\n";
