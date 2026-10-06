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

    // Self-Healing Trigger: If last heartbeat > 5 minutes ago, trigger background cron cycle
    $lastHeartbeatTime = isset($statusData['last_heartbeat']) ? strtotime($statusData['last_heartbeat']) : 0;
    if (time() - $lastHeartbeatTime > 300) {
        $runnerPath = ROOT_PATH . '/cron_runner.php';
        if (file_exists($runnerPath)) {
            @exec("nohup /usr/local/bin/php " . escapeshellarg($runnerPath) . " > /dev/null 2>&1 &");
            @exec("nohup php " . escapeshellarg($runnerPath) . " > /dev/null 2>&1 &");
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $statusData
    ]);
    exit;
}

if ($action === 'run_cycle') {
    require_once __DIR__ . '/../cron_runner.php';
    exit;
}

if ($action === 'start_daemon') {
    $workerPath = ROOT_PATH . '/autonomous_worker.php';
    if (file_exists($workerPath)) {
        @exec("nohup php " . escapeshellarg($workerPath) . " > /dev/null 2>&1 &");
    }
    echo json_encode([
        'status' => 'success',
        'message' => '24/7 Perpetual Autonomous Master Daemon launched in background.'
    ]);
    exit;
}
