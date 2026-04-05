<?php

require_once __DIR__ . '/bootstrap.php';

$options = getopt('', ['pid:']);

$pid = (int)($options['pid'] ?? 0);

if (!$pid) {
    echo json_encode(['error' => 'PID not provided']);
    exit(1);
}

use Kim1ne\MonitoringPhp\Cli\Monitor\HeartbeatReader;
use Kim1ne\MonitoringPhp\Cli\Process\Process;

$process = new Process($pid);

usleep(500000);

if (!$process->isRunning()) {
    echo json_encode(['running' => false]);
    exit(0);
}

$heartbeatReader = new HeartbeatReader();

$heartbeat = $heartbeatReader->read($process);

echo json_encode([
    'running' => true,
    'heartbeat' => $heartbeat,
    'pid' => $pid
]);
