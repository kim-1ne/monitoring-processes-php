<?php

namespace Kim1ne\MonitoringPhp\Cli\Script;

use Kim1ne\MonitoringPhp\Cli\DTO\HeartbeatSignal;
use Kim1ne\MonitoringPhp\Cli\ShellExec;
use Kim1ne\MonitoringPhp\Cli\Storage\ProcessStorageInterface;

class ShellExecHeartbeatSignal
{
    private string $signalPath;


    public function __construct(
        private readonly ProcessStorageInterface $storage,
    )
    {
        $this->signalPath = __DIR__ . '/signals/signal_heartbeat.php';
    }

    public function fetch(int $pid): ?HeartbeatSignal
    {
        $output = ShellExec::run('php', [
            $this->signalPath,
            '--pid=' . $pid
        ]);

        if ($output === null || $output === '') {
            return null;
        }

        $result = json_decode($output, true);

        if (!$result || isset($result['error'])) {
            return null;
        }

        return HeartbeatSignal::fromArray($result);
    }

    public function fetchOrDefault(int $pid): HeartbeatSignal
    {
        $signal = $this->fetch($pid);

        return $signal ?? HeartbeatSignal::default($pid);
    }

    public function fetchAndUpdateSession(int $pid, string $uuid): HeartbeatSignal
    {
        $signal = $this->fetchOrDefault($pid);

        if ($signal->isRunning()) {
            $this->storage->updateHeartbeat($pid, $uuid, $signal->heartbeat);
        } else {
            $this->storage->remove($pid, $uuid);
        }

        return $signal;
    }

    public function run(int $pid, string $uuid): HeartbeatSignal
    {
        return $this->fetchAndUpdateSession($pid, $uuid);
    }
}
