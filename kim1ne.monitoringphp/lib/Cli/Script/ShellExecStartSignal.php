<?php

namespace Kim1ne\MonitoringPhp\Cli\Script;

use Kim1ne\MonitoringPhp\Cli\DTO\ProcessData;
use Kim1ne\MonitoringPhp\Cli\Process\Process;
use Kim1ne\MonitoringPhp\Cli\ShellExec;
use Kim1ne\MonitoringPhp\Cli\Storage\ProcessStorageInterface;

readonly class ShellExecStartSignal
{
    public function __construct(
        private WhiteList $whiteList,
        private ProcessStorageInterface $storage,
    ) {}

    public function run(string $scriptPath): ProcessData
    {
        $scriptPath = $this->whiteList->resolveAllowedPath($scriptPath);

        if ($scriptPath === null) {
            throw new \RuntimeException("Script not found: {$scriptPath}");
        }

        $pid = ShellExec::runBackground('php', [$scriptPath]);

        if ($pid === null) {
            throw new \RuntimeException('Не удалось запустить скрипт');
        }

        $process = new Process($pid);

        if (!$process->isRunning()) {
            throw new \RuntimeException('Процесс не запустился');
        }

        return $this->storage->add($pid, basename($scriptPath));
    }
}
