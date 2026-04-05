<?php

require_once __DIR__ . '/bootstrap.php';

$options = getopt('', ['pid:']);

$pid = (int)($options['pid'] ?? 0);

if (!$pid) {
    echo json_encode(['error' => 'PID not provided']);
    exit(1);
}

use Kim1ne\MonitoringPhp\Cli\Process\Process;

$process = new Process($pid);

if ($process->isRunning()) {
    $process->stop();
}

echo json_encode([
    'pid' => $pid,
    'running' => false,
]);
