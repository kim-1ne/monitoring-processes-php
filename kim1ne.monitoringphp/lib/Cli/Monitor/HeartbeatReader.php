<?php

namespace Kim1ne\MonitoringPhp\Cli\Monitor;

use Kim1ne\MonitoringPhp\Cli\Process\Process;

readonly class HeartbeatReader
{
    private string $dir;

    public function __construct()
    {
        $this->dir = sys_get_temp_dir();
    }

    public function read(Process $process, int $timeout = 10): ?string
    {
        $pid = $process->getPid();

        if (!$process->sendSignal(SIGUSR1)) {
            return null;
        }

        $startTime = microtime(true);

        while (microtime(true) - $startTime < $timeout) {
            $file = $this->getHeartbeatFile($pid);

            if (file_exists($file)) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                $this->deleteFile($pid);
                return $data['heartbeat'] ?? null;
            }

            if (!$process->isRunning()) {
                return null;
            }

            usleep(100000);
        }

        return null;
    }

    public function write(HeartBeat $heartbeat): void
    {
        $pid = $heartbeat->getPid();
        $file = $this->getHeartbeatFile($pid);
        file_put_contents($file, json_encode($heartbeat));
    }

    public function deleteFile(int $pid): void
    {
        $file = $this->getHeartbeatFile($pid);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function getHeartbeatFile(int $pid): string
    {
        return $this->dir . '/heartbeat_' . $pid . '.json';
    }
}
