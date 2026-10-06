<?php
/**
 * LeadForge AI - CRM Pipeline & ₹50,000/Month Target Tracker
 * High-Performance Aggregation, Pagination & Gzip Compression
 */

declare(strict_types=1);

if (!ob_get_level() && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;
$action = $_GET['action'] ?? $_POST['action'] ?? $data['action'] ?? 'list';
$db = Database::getConnection();
$settings = getSettings();
$usdRate = (float)($settings['usd_to_inr'] ?? 86.5);
$monthlyGoalInr = (float)($settings['monthly_goal_inr'] ?? 50000);

try {
    switch ($action) {
        case 'list':
        case 'get_all':
        case 'get':
        case 'all':
        case 'fetch':
            $status = $_GET['status'] ?? 'all';
            $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 100;
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $offset = ($page - 1) * $limit;

            // 1. Ultra-fast metrics calculation directly in SQLite
            $metricsSql = "SELECT 
                COALESCE(SUM(CASE WHEN status = 'won' THEN COALESCE(deal_value_inr, deal_value_usd * :rate1) ELSE 0 END), 0) AS total_won_inr,
                COALESCE(SUM(CASE WHEN status = 'won' THEN deal_value_usd ELSE 0 END), 0) AS total_won_usd,
                COALESCE(SUM(CASE WHEN status IN ('contacted', 'discussing', 'new') THEN COALESCE(deal_value_inr, deal_value_usd * :rate2) ELSE 0 END), 0) AS pipeline_inr,
                COALESCE(SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END), 0) AS won_count,
                COUNT(*) AS total_count
            FROM leads";

            $mStmt = $db->prepare($metricsSql);
            $mStmt->execute([':rate1' => $usdRate, ':rate2' => $usdRate]);
            $metrics = $mStmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total_won_inr' => 0,
                'total_won_usd' => 0,
                'pipeline_inr' => 0,
                'won_count' => 0,
                'total_count' => 0
            ];

            // 2. Fetch leads with limit and pagination
            $sql = "SELECT id, title, source, client_name, client_email, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent, created_at, updated_at FROM leads";
            $params = [];

            if ($status !== 'all') {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Normalize values
            foreach ($leads as &$l) {
                $valUsd = (float)($l['deal_value_usd'] ?? 0);
                $valInr = (float)($l['deal_value_inr'] ?? 0);
                if ($valInr == 0 && $valUsd > 0) {
                    $l['deal_value_inr'] = $valUsd * $usdRate;
                }
            }
            unset($l);

            // 3. Anti-ban daily quota usage
            $today = date('Y-m-d');
            $qStmt = $db->prepare("SELECT platform, COUNT(*) as count FROM outreach_logs WHERE DATE(created_at) = ? GROUP BY platform");
            $qStmt->execute([$today]);
            $quotaUsage = $qStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $totalWonInr = (float)$metrics['total_won_inr'];
            $progressPct = min(100, round(($totalWonInr / max(1, $monthlyGoalInr)) * 100, 1));

            echo json_encode([
                'status' => 'success',
                'stats' => [
                    'monthly_goal_inr' => $monthlyGoalInr,
                    'total_won_inr' => round($totalWonInr, 2),
                    'total_won_usd' => round((float)$metrics['total_won_usd'], 2),
                    'pipeline_inr' => round((float)$metrics['pipeline_inr'], 2),
                    'progress_percentage' => $progressPct,
                    'won_deals_count' => (int)$metrics['won_count'],
                    'total_leads_count' => (int)$metrics['total_count'],
                    'page' => $page,
                    'limit' => $limit,
                    'daily_quota' => [
                        'linkedin' => [
                            'used' => (int)($quotaUsage['LinkedIn'] ?? 0),
                            'limit' => (int)($settings['daily_linkedin_limit'] ?? 20)
                        ],
                        'email' => [
                            'used' => (int)($quotaUsage['Email'] ?? 0),
                            'limit' => (int)($settings['daily_email_limit'] ?? 30)
                        ],
                        'upwork' => [
                            'used' => (int)($quotaUsage['Upwork'] ?? 0),
                            'limit' => (int)($settings['daily_upwork_limit'] ?? 15)
                        ]
                    ]
                ],
                'leads' => $leads
            ]);
            break;

        case 'add':
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true) ?: $_POST;

            $title = trim($data['title'] ?? 'Untitled Lead');
            $source = trim($data['source'] ?? 'Radar');
            $clientName = trim($data['client_name'] ?? '');
            $clientEmail = trim($data['client_email'] ?? '');
            $company = trim($data['company'] ?? '');
            $url = trim($data['url'] ?? '');
            $platform = trim($data['platform'] ?? 'LinkedIn');
            $status = trim($data['status'] ?? 'new');
            $dealUsd = (float)($data['deal_value_usd'] ?? 50);
            $dealInr = (float)($data['deal_value_inr'] ?? ($dealUsd * $usdRate));
            $notes = trim($data['notes'] ?? '');
            $pitch = trim($data['pitch_sent'] ?? '');

            $stmt = $db->prepare("INSERT INTO leads (title, source, client_name, client_email, company, url, platform, status, deal_value_usd, deal_value_inr, notes, pitch_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $source, $clientName, $clientEmail, $company, $url, $platform, $status, $dealUsd, $dealInr, $notes, $pitch]);
            $leadId = (int)$db->lastInsertId();

            // Log outreach if marked contacted
            if ($status === 'contacted' || !empty($pitch)) {
                $lStmt = $db->prepare("INSERT INTO outreach_logs (lead_id, platform, message_type) VALUES (?, ?, ?)");
                $lStmt->execute([$leadId, $platform, 'Direct Pitch']);
            }

            echo json_encode(['status' => 'success', 'message' => 'Lead added successfully', 'id' => $leadId]);
            break;

        case 'update_status':
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true) ?: $_POST;

            $id = (int)($data['id'] ?? 0);
            $newStatus = trim($data['status'] ?? 'new');
            $dealUsd = isset($data['deal_value_usd']) ? (float)$data['deal_value_usd'] : null;

            if ($dealUsd !== null) {
                $dealInr = $dealUsd * $usdRate;
                $stmt = $db->prepare("UPDATE leads SET status = ?, deal_value_usd = ?, deal_value_inr = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$newStatus, $dealUsd, $dealInr, $id]);
            } else {
                $stmt = $db->prepare("UPDATE leads SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Lead updated successfully']);
            break;

        case 'delete':
            $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM leads WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Lead deleted']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
