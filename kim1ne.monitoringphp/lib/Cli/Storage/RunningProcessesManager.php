<?php

namespace Kim1ne\MonitoringPhp\Cli\Storage;

use Kim1ne\MonitoringPhp\Cli\DTO\ProcessData;

readonly class RunningProcessesManager
{
    public function __construct(
        private ProcessStorageInterface $storage
    ) {}

    public function add(int $pid, string $scriptName): ProcessData
    {
        return $this->storage->add($pid, $scriptName);
    }

    public function remove(int $pid, ?string $uuid = null): void
    {
        $this->storage->remove($pid, $uuid);
    }

    public function get(int $pid, ?string $uuid = null): ?ProcessData
    {
        return $this->storage->get($pid, $uuid);
    }

    /**
     * @return ProcessData[]
     */
    public function getAll(): array
    {
        return $this->storage->getAll();
    }

    public function updateHeartbeat(int $pid, string $uuid, string $heartbeat): void
    {
        $this->storage->updateHeartbeat($pid, $uuid, $heartbeat);
    }

    public function has(int $pid, string $uuid): bool
    {
        return $this->storage->has($pid, $uuid);
    }
}
