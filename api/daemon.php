<?php
/**
 * LeadForge AI - 24/7 Autonomous Daemon Status & Control API
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

$daemonStatusFile = DATA_PATH . '/daemon_status.json';
$logFile = DATA_PATH . '/daemon.log';

$action = $_GET['action'] ?? 'status';

if ($action === 'status') {
    $statusData = [];
    if (file_exists($daemonStatusFile)) {
        $statusData = json_decode(file_get_contents($daemonStatusFile), true) ?: [];
    }

    $recentLogs = [];
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recentLogs = array_slice($lines, -15);
    }

    $statusData['recent_logs'] = $recentLogs;

    echo json_encode([
        'status' => 'success',
        'data' => $statusData
    ]);
    exit;
}
