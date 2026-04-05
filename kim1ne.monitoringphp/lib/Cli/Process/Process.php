<?php

namespace Kim1ne\MonitoringPhp\Cli\Process;

use Kim1ne\MonitoringPhp\Cli\ShellExec;

readonly class Process
{
    public function __construct(
        public int $pid
    ) {}

    public function getPid(): int
    {
        return $this->pid;
    }

    public function isRunning(): bool
    {
        if ($this->pid <= 0) {
            return false;
        }

        return posix_kill($this->pid, 0);
    }

    public function sendSignal(int $signal): bool
    {
        if (!$this->isRunning()) {
            return false;
        }

        return posix_kill($this->pid, $signal);
    }

    public function stop(): bool
    {
        if (!$this->isRunning()) {
            return true;
        }

        return posix_kill($this->pid, SIGTERM);
    }

    public static function alives(array $pids): array
    {
        if (empty($pids)) {
            return [];
        }

        $pidList = implode(',', $pids);
        $output =  ShellExec::runString("ps -p {$pidList} -o pid= 2>/dev/null");

        if ($output === null || $output === '') {
            return [];
        }

        return array_map('intval', array_filter(explode("\n", trim($output))));
    }
}
