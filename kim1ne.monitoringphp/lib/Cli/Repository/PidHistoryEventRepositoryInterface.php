<?php

namespace Kim1ne\MonitoringPhp\Cli\Repository;

use Kim1ne\MonitoringPhp\Cli\DTO\ProcessData;

interface PidHistoryEventRepositoryInterface
{
    public function addStartEvent(ProcessData $processData, ?int $userId = null): void;

    public function addEndEvent(ProcessData $processData, ?int $userId = null): void;

    public function addHandStartEvent(ProcessData $processData, int $userId): void;

    public function addHandEndEvent(ProcessData $processData, int $userId): void;

    public function addErrorEvent(ProcessData $processData, string $errorMessage, ?int $userId = null): void;

    /**
     * @return array<array>
     */
    public function getEventsByUuid(string $uuid): array;

    /**
     * @return array<array>
     */
    public function getEventsByPid(int $pid): array;

    public function getLastEventByUuid(string $uuid): ?array;

    /**
     * @return array<array>
     */
    public function getActiveProcesses(): array;

    public function cleanOldEvents(int $days = 30): void;
}
