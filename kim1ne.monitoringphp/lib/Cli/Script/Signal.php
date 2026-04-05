<?php

namespace Kim1ne\MonitoringPhp\Cli\Script;

use Kim1ne\MonitoringPhp\Cli\DTO\HeartbeatSignal;
use Kim1ne\MonitoringPhp\Cli\DTO\ProcessData;
use Kim1ne\MonitoringPhp\Cli\DTO\StopSignalResult;
use Kim1ne\MonitoringPhp\Cli\Repository\PidHistoryEventRepositoryInterface;
use Kim1ne\MonitoringPhp\Cli\Shutdown;
use Kim1ne\MonitoringPhp\Cli\Storage\ProcessStorageInterface;

class Signal
{
    private bool $isRegisterSignals = false;
    private ?\Throwable $exception = null;

    public function __construct(
        private readonly WhiteList $whiteList,
        private readonly ProcessStorageInterface $storage,
        private readonly PidHistoryEventRepositoryInterface $historyRepo,
    ) {}

    public function stop(int $pid, string $uuid, ?int $userId = null): StopSignalResult
    {
        $processData = $this->storage->get($pid, $uuid);

        if (!$processData) {
            return StopSignalResult::error($pid, 'Process not found');
        }

        $result = (new ShellExecStopSignal())->run($pid);

        if ($result->isSuccess()) {
            if ($userId !== null) {
                $this->historyRepo->addHandEndEvent($processData, $userId);
            } else {
                $this->historyRepo->addEndEvent($processData);
            }
        }

        return $result;
    }

    public function heartbeat(int $pid, string $uuid): HeartbeatSignal
    {
        $signal = (new ShellExecHeartbeatSignal($this->storage))->run($pid, $uuid);

        if ($signal->isRunning()) {
            $this->storage->updateHeartbeat($pid, $uuid, $signal->heartbeat);
        } else {
            $this->storage->remove($pid, $uuid);
        }

        return $signal;
    }

    public function start(string $scriptName, ?int $userId = null): ProcessData
    {
        $processData = (new ShellExecStartSignal($this->whiteList, $this->storage))->run($scriptName);

        if ($userId !== null) {
            $this->historyRepo->addHandStartEvent($processData, $userId);
        } else {
            $this->historyRepo->addStartEvent($processData);
        }

        return $processData;
    }

    private function setException(\Throwable $e): void
    {
        $this->exception = $e;
    }

    public function registerSignals(callable $callable): void
    {
        if ($this->isRegisterSignals) {
            return;
        }

        $this->isRegisterSignals = true;

        pcntl_async_signals(true);
        pcntl_signal(SIGUSR1, $callable);

        set_exception_handler(function(\Throwable $e): void {
            $this->setException($e);
        });

        register_shutdown_function(function(): void {
            $shutdown = new Shutdown($this->storage, $this->historyRepo, $this->exception);
            $shutdown->handle();
        });
    }
}
