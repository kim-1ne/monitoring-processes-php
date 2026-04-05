<?php

namespace Kim1ne\MonitoringPhp\Cli;

use Kim1ne\MonitoringPhp\Cli\Repository\PidHistoryEventRepositoryInterface;
use Kim1ne\MonitoringPhp\Cli\Script\Signal;
use Kim1ne\MonitoringPhp\Cli\Script\WhiteList;
use Kim1ne\MonitoringPhp\Cli\Storage\ProcessStorageInterface;

readonly class ProcessManager
{
    public Signal $signal;

    public function __construct(
        public WhiteList $whiteList,
        private ProcessStorageInterface $storage,
        private PidHistoryEventRepositoryInterface $historyRepo,
    )
    {
        $this->signal = new Signal($whiteList, $storage, $historyRepo);
    }

    public function getRunningProcesses(): array
    {
        $storedProcesses = $this->storage->getAll();

        if (empty($storedProcesses)) {
            return [];
        }

        $allPids = array_keys($storedProcesses);
        $alivePids = Process\Process::alives($allPids);

        $processes = [];
        $deadPids = [];

        foreach ($storedProcesses as $pid => $data) {
            if (in_array($pid, $alivePids, true)) {
                $processes[] = $data;
            } else {
                $deadPids[] = $pid;
            }
        }

        foreach ($deadPids as $pid) {
            $this->storage->remove($pid);
        }

        return $processes;
    }
}
