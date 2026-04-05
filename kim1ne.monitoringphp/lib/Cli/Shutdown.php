<?php

namespace Kim1ne\MonitoringPhp\Cli;

use Kim1ne\MonitoringPhp\Cli\Repository\PidHistoryEventRepositoryInterface;
use Kim1ne\MonitoringPhp\Cli\Storage\ProcessStorageInterface;

readonly class Shutdown
{

    public function __construct(
        private ProcessStorageInterface            $storage,
        private PidHistoryEventRepositoryInterface $historyRepo,
        private ?\Throwable                        $exception = null,
    ) {}

    public function handle(): void
    {
        $error = error_get_last();
        $pid = getmypid();

        $processData = $this->storage->get($pid);

        if (!$processData) {
            return;
        }

        $this->storage->remove($processData->pid, $processData->uuid);

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->historyRepo->addErrorEvent(
                $processData,
                $error['message'] . ' in ' . $error['file'] . ':' . $error['line']
            );
        } elseif ($this->exception !== null) {
            $this->historyRepo->addErrorEvent(
                $processData,
                $this->exception->getMessage() . ' in ' . $this->exception->getFile() . ':' . $this->exception->getLine()
            );
        } else {
            $this->historyRepo->addEndEvent($processData);
        }
    }
}
