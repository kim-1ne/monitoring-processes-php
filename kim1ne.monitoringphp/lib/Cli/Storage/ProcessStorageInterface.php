<?php

namespace Kim1ne\MonitoringPhp\Cli\Storage;

use Kim1ne\MonitoringPhp\Cli\DTO\ProcessData;

interface ProcessStorageInterface
{
    public function add(int $pid, string $scriptName, array $metadata = []): ProcessData;

    public function remove(int $pid, ?string $uuid = null): void;

    public function get(int $pid, ?string $uuid = null): ?ProcessData;

    /**
     * @return ProcessData[]
     */
    public function getAll(): array;

    public function updateHeartbeat(int $pid, string $uuid, string $heartbeat): void;

    public function has(int $pid, string $uuid): bool;
}
