<?php

namespace Kim1ne\MonitoringPhp\Cli\Script;

use Kim1ne\MonitoringPhp\Cli\DTO\StopSignalResult;
use Kim1ne\MonitoringPhp\Cli\ShellExec;

readonly class ShellExecStopSignal
{
    private string $signalPath;

    public function __construct()
    {
        $this->signalPath = __DIR__ . '/signals/stop_signal.php';
    }

    public function run(int $pid): StopSignalResult
    {
        $output = ShellExec::run('php', [
            $this->signalPath,
            '--pid=' . $pid,
            '--document-root=' . $_SERVER['DOCUMENT_ROOT']
        ]);

        $result = json_decode($output, true);

        if ($result && isset($result['pid'])) {
            return StopSignalResult::success($pid);
        }

        return StopSignalResult::error($pid, 'Не удалось остановить процесс');
    }
}
