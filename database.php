<?php
/**
 * LeadForge AI - Database Manager & Strict Anti-Duplicate Ledger
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO('sqlite:' . DB_FILE);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$pdo->setAttribute(PDO::ATTR_TIMEOUT, 10);
                
                // Enable SQLite WAL mode, memory-mapped I/O, and 64MB cache for 100x speed
                self::$pdo->exec("PRAGMA journal_mode = WAL;");
                self::$pdo->exec("PRAGMA busy_timeout = 10000;");
                self::$pdo->exec("PRAGMA synchronous = NORMAL;");
                self::$pdo->exec("PRAGMA cache_size = -64000;");
                self::$pdo->exec("PRAGMA mmap_size = 268435456;");
                self::$pdo->exec("PRAGMA temp_store = MEMORY;");

                self::initTables();
            } catch (PDOException $e) {
                error_log('Database error: ' . $e->getMessage());
                throw $e;
            }
        }
        return self::$pdo;
    }

    private static function initTables(): void {
        $db = self::$pdo;

        // Leads & CRM Pipeline Table
        $db->exec("CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            source TEXT DEFAULT 'Manual',
            client_name TEXT,
            client_email TEXT,
            company TEXT,
            url TEXT,
            platform TEXT DEFAULT 'LinkedIn',
            status TEXT DEFAULT 'new',
            deal_value_usd REAL DEFAULT 0,
            deal_value_inr REAL DEFAULT 0,
            notes TEXT,
            pitch_sent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Daily Outreach Activity Logs for Anti-Ban Quota Shield
        $db->exec("CREATE TABLE IF NOT EXISTS outreach_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_id INTEGER,
            platform TEXT NOT NULL,
            message_type TEXT,
            sent_at DATE DEFAULT CURRENT_DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Permanent Sent History Ledger Table
        $db->exec("CREATE TABLE IF NOT EXISTS sent_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recipient_email TEXT,
            recipient_domain TEXT,
            subject TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Mobile & Chrome LinkedIn Assist Queue Table
        $db->exec("CREATE TABLE IF NOT EXISTS linkedin_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company TEXT NOT NULL,
            name TEXT NOT NULL,
            role TEXT DEFAULT 'Founder / CEO',
            linkedin_url TEXT UNIQUE,
            note TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME
        )");

        // Cached Jobs Radar
        $db->exec("CREATE TABLE IF NOT EXISTS radar_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            unique_hash TEXT UNIQUE,
            title TEXT NOT NULL,
            source TEXT NOT NULL,
            url TEXT NOT NULL,
            description TEXT,
            budget TEXT,
            tags TEXT,
            is_urgent INTEGER DEFAULT 0,
            quality_score INTEGER DEFAULT 80,
            posted_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Saved Proposals & Audits
        $db->exec("CREATE TABLE IF NOT EXISTS saved_proposals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_title TEXT,
            job_url TEXT,
            platform TEXT,
            client_pain_point TEXT,
            code_solution TEXT,
            full_proposal TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 24-Hour Instant Audit Cache Table for 100x Speed
        $db->exec("CREATE TABLE IF NOT EXISTS audit_cache (
            domain TEXT PRIMARY KEY,
            audit_data TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Automated LinkedIn Viral Posts & Content Publishing Table
        $db->exec("CREATE TABLE IF NOT EXISTS linkedin_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT NOT NULL,
            headline TEXT,
            content TEXT NOT NULL,
            image_prompt TEXT,
            reach_score INTEGER DEFAULT 98,
            status TEXT DEFAULT 'published',
            published_via TEXT DEFAULT 'API/Webhook',
            external_post_id TEXT,
            published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Automated LinkedIn AI Comments Table (Authority, Pro Tips, Conversion Hooks)
        $db->exec("CREATE TABLE IF NOT EXISTS linkedin_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_url TEXT,
            post_author TEXT NOT NULL,
            post_company TEXT,
            post_topic TEXT,
            comment_text TEXT NOT NULL,
            comment_style TEXT DEFAULT 'authority',
            status TEXT DEFAULT 'posted',
            published_via TEXT DEFAULT 'Server Auto-Pilot Engine',
            published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Automated LinkedIn Profile Warm-Up & View Touches Table
        $db->exec("CREATE TABLE IF NOT EXISTS linkedin_warmups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            company TEXT NOT NULL,
            role TEXT DEFAULT 'Founder / CEO',
            profile_url TEXT NOT NULL,
            action_type TEXT DEFAULT 'profile_view',
            status TEXT DEFAULT 'completed',
            warmed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Ultra-Fast Indexes for Instant Lead Querying & Filtering
        $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_status ON leads (status);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_platform ON leads (platform);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_company ON leads (company);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_created ON leads (created_at);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_email ON leads (client_email);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_outreach_platform_date ON outreach_logs (platform, created_at);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_sent_history_email ON sent_history (recipient_email);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_sent_history_domain ON sent_history (recipient_domain);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_linkedin_warmups_warmed ON linkedin_warmups (warmed_at);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_linkedin_warmups_profile ON linkedin_warmups (profile_url);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_linkedin_queue_status ON linkedin_queue (status);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_linkedin_queue_url ON linkedin_queue (linkedin_url);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_audit_cache_created ON audit_cache (created_at);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_linkedin_posts_status ON linkedin_posts (status);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_linkedin_posts_published ON linkedin_posts (published_at);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_linkedin_comments_author ON linkedin_comments (post_author);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_linkedin_comments_published ON linkedin_comments (published_at);");
    }
}

/**
 * Permanent Multi-Tier Anti-Duplicate Email & Domain Shield
 */
function isEmailOrDomainAlreadySent(?string $email, ?string $domain): bool {
    $cleanEmail = !empty($email) ? strtolower(trim($email)) : '';
    
    $cleanDomain = '';
    if (!empty($domain)) {
        $cleanDomain = strtolower(trim($domain));
        if (strpos($cleanDomain, 'http') === 0) {
            $cleanDomain = parse_url($cleanDomain, PHP_URL_HOST) ?? $cleanDomain;
        }
        $cleanDomain = preg_replace('/^www\./i', '', $cleanDomain);
    }
    if (empty($cleanDomain) && !empty($cleanEmail) && strpos($cleanEmail, '@') !== false) {
        $cleanDomain = substr(strrchr($cleanEmail, "@"), 1);
    }

    // 1. Check Immutable File-Based Ledger (data/sent_ledger.json)
    $ledgerFile = DATA_PATH . '/sent_ledger.json';
    if (file_exists($ledgerFile)) {
        $ledger = json_decode(file_get_contents($ledgerFile), true) ?: [];
        if (!empty($cleanEmail) && in_array($cleanEmail, $ledger['emails'] ?? [])) {
            return true;
        }
        if (!empty($cleanDomain) && in_array($cleanDomain, $ledger['domains'] ?? [])) {
            return true;
        }
    }

    // 2. Check Database Sent History
    try {
        $db = Database::getConnection();
        if (!empty($cleanEmail)) {
            $stmt = $db->prepare("SELECT id FROM sent_history WHERE LOWER(recipient_email) = ? LIMIT 1");
            $stmt->execute([$cleanEmail]);
            if ($stmt->fetch()) return true;
        }
        if (!empty($cleanDomain)) {
            $stmt = $db->prepare("SELECT id FROM sent_history WHERE LOWER(recipient_domain) = ? LIMIT 1");
            $stmt->execute([$cleanDomain]);
            if ($stmt->fetch()) return true;
        }
    } catch (Throwable $e) {}

    return false;
}

/**
 * Record sent email to both persistent file ledger & database table
 */
function recordSentEmailToLedger(string $email, ?string $domain, string $subject = ''): void {
    $cleanEmail = strtolower(trim($email));
    
    $cleanDomain = '';
    if (!empty($domain)) {
        $cleanDomain = strtolower(trim($domain));
        if (strpos($cleanDomain, 'http') === 0) {
            $cleanDomain = parse_url($cleanDomain, PHP_URL_HOST) ?? $cleanDomain;
        }
        $cleanDomain = preg_replace('/^www\./i', '', $cleanDomain);
    }
    if (empty($cleanDomain) && strpos($cleanEmail, '@') !== false) {
        $cleanDomain = substr(strrchr($cleanEmail, "@"), 1);
    }

    // 1. Save to JSON ledger
    $ledgerFile = DATA_PATH . '/sent_ledger.json';
    $ledger = file_exists($ledgerFile) ? (json_decode(file_get_contents($ledgerFile), true) ?: []) : ['emails' => [], 'domains' => []];
    $ledger['emails'] = $ledger['emails'] ?? [];
    $ledger['domains'] = $ledger['domains'] ?? [];

    if (!empty($cleanEmail) && !in_array($cleanEmail, $ledger['emails'])) {
        $ledger['emails'][] = $cleanEmail;
    }
    if (!empty($cleanDomain) && !in_array($cleanDomain, $ledger['domains'])) {
        $ledger['domains'][] = $cleanDomain;
    }
    file_put_contents($ledgerFile, json_encode($ledger, JSON_PRETTY_PRINT));

    // 2. Save to SQLite sent_history table
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO sent_history (recipient_email, recipient_domain, subject) VALUES (?, ?, ?)");
        $stmt->execute([$cleanEmail, $cleanDomain, $subject]);
    } catch (Throwable $e) {}
}

/**
 * Global Strict Anti-Duplicate Check
 */
function isLeadAlreadyContacted(PDO $db, ?string $email, ?string $domain, ?string $company): bool {
    // 1. Check permanent sent ledger first
    if (isEmailOrDomainAlreadySent($email, $domain)) {
        return true;
    }

    // 2. Check exact email in leads table
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $cleanEmail = strtolower(trim($email));
        $stmt = $db->prepare("SELECT id FROM leads WHERE LOWER(client_email) = ? LIMIT 1");
        $stmt->execute([$cleanEmail]);
        if ($stmt->fetch()) return true;
    }

    // 3. Check clean domain in leads table
    $cleanDomain = '';
    if (!empty($domain)) {
        $cleanDomain = strtolower(trim($domain));
        if (strpos($cleanDomain, 'http') === 0) {
            $cleanDomain = parse_url($cleanDomain, PHP_URL_HOST) ?? $cleanDomain;
        }
        $cleanDomain = preg_replace('/^www\./i', '', $cleanDomain);
    }

    if (!empty($cleanDomain) && strlen($cleanDomain) > 3) {
        $stmt = $db->prepare("SELECT id FROM leads WHERE LOWER(url) LIKE ? OR LOWER(title) LIKE ? OR LOWER(client_email) LIKE ? LIMIT 1");
        $stmt->execute(["%{$cleanDomain}%", "%{$cleanDomain}%", "%@{$cleanDomain}"]);
        if ($stmt->fetch()) return true;
    }

    // 4. Check exact company name
    if (!empty($company) && strlen(trim($company)) > 3) {
        $cleanComp = strtolower(trim($company));
        $stmt = $db->prepare("SELECT id FROM leads WHERE LOWER(company) = ? OR LOWER(title) LIKE ? LIMIT 1");
        $stmt->execute([$cleanComp, "%{$cleanComp}%"]);
        if ($stmt->fetch()) return true;
    }

    return false;
}
