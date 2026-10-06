<?php
/**
 * LeadForge AI - Database Manager (SQLite + Auto Table Creation)
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

        // Ultra-Fast Indexes for Instant Lead Querying & Filtering
        $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_status ON leads (status);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_platform ON leads (platform);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_company ON leads (company);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_created ON leads (created_at);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_outreach_platform_date ON outreach_logs (platform, created_at);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_audit_cache_created ON audit_cache (created_at);");
    }
}
