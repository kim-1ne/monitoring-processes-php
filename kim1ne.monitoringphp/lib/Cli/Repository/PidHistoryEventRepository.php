<?php

namespace Kim1ne\MonitoringPhp\Cli\Repository;

use Kim1ne\MonitoringPhp\Cli\DTO\ProcessData;
use Kim1ne\MonitoringPhp\Cli\Orm\PidHistoryEventTable;
use Bitrix\Main\Type\DateTime;

readonly class PidHistoryEventRepository implements PidHistoryEventRepositoryInterface
{
    private function addEventInternal(ProcessData $processData, string $eventType, ?string $payload, int $userId): void
    {
        $this->addEvent(
            $processData->uuid,
            $processData->pid,
            $processData->script,
            $eventType,
            $payload,
            $userId
        );
    }

    public function addStartEvent(ProcessData $processData, ?int $userId = null): void
    {
        $this->addEventInternal($processData, 'start', null, $userId ?? 0);
    }

    public function addEndEvent(ProcessData $processData, ?int $userId = null): void
    {
        $this->addEventInternal($processData, 'end', null, $userId ?? 0);
    }

    public function addHandStartEvent(ProcessData $processData, int $userId): void
    {
        $this->addEventInternal($processData, 'hand_start', null, $userId);
    }

    public function addHandEndEvent(ProcessData $processData, int $userId): void
    {
        $this->addEventInternal($processData, 'hand_end', null, $userId);
    }

    public function addErrorEvent(ProcessData $processData, string $errorMessage, ?int $userId = null): void
    {
        $this->addEventInternal($processData, 'error', $errorMessage, $userId ?? 0);
    }

    /**
     * Добавить событие
     */
    public function addEvent(string $uuid, int $pid, string $scriptCode, string $eventType, ?string $payload, int $userId): void
    {
        $data = [
            PidHistoryEventTable::PROCESS_UUID => $uuid,
            PidHistoryEventTable::PID => $pid,
            PidHistoryEventTable::SCRIPT_CODE => $scriptCode,
            PidHistoryEventTable::EVENT_TYPE => $eventType,
            PidHistoryEventTable::INITIATOR_USER_ID => $userId,
        ];

        if ($payload !== null) {
            $data[PidHistoryEventTable::PAYLOAD] = $payload;
        }

        $result = PidHistoryEventTable::add($data);

        if (!$result->isSuccess()) {
            throw new \RuntimeException('Failed to add event: ' . implode(', ', $result->getErrorMessages()));
        }
    }

    /**
     * Получить события по UUID процесса
     */
    public function getEventsByUuid(string $uuid): array
    {
        $result = [];

        $rows = PidHistoryEventTable::getList([
            'filter' => [
                PidHistoryEventTable::PROCESS_UUID => $uuid,
            ],
            'order' => [
                PidHistoryEventTable::CREATED_AT => 'ASC',
            ],
        ]);

        while ($row = $rows->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Получить события по PID
     */
    public function getEventsByPid(int $pid): array
    {
        $result = [];

        $rows = PidHistoryEventTable::getList([
            'filter' => [
                PidHistoryEventTable::PID => $pid,
            ],
            'order' => [
                PidHistoryEventTable::CREATED_AT => 'ASC',
            ],
        ]);

        while ($row = $rows->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Получить последнее событие по UUID
     */
    public function getLastEventByUuid(string $uuid): ?array
    {
        $row = PidHistoryEventTable::getList([
            'filter' => [
                PidHistoryEventTable::PROCESS_UUID => $uuid,
            ],
            'order' => [
                PidHistoryEventTable::CREATED_AT => 'DESC',
            ],
            'limit' => 1,
        ])->fetch();

        return $row ?: null;
    }

    /**
     * Получить все активные процессы (без события end)
     */
    public function getActiveProcesses(): array
    {
        $result = [];

        // Получаем все UUID, у которых нет события 'end'
        $rows = PidHistoryEventTable::getList([
            'select' => [
                PidHistoryEventTable::PROCESS_UUID,
                PidHistoryEventTable::PID,
                PidHistoryEventTable::SCRIPT_CODE,
                PidHistoryEventTable::INITIATOR_USER_ID,
                'MAX_CREATED_AT' => PidHistoryEventTable::CREATED_AT,
            ],
            'group' => [PidHistoryEventTable::PROCESS_UUID],
            'order' => [
                'MAX_CREATED_AT' => 'DESC',
            ],
        ]);

        foreach ($rows as $row) {
            // Проверяем, есть ли событие 'end' для этого UUID
            $endEvent = PidHistoryEventTable::getList([
                'filter' => [
                    PidHistoryEventTable::PROCESS_UUID => $row[PidHistoryEventTable::PROCESS_UUID],
                    PidHistoryEventTable::EVENT_TYPE => 'end',
                ],
                'limit' => 1,
            ])->fetch();

            if (!$endEvent) {
                $result[] = [
                    'uuid' => $row[PidHistoryEventTable::PROCESS_UUID],
                    'pid' => $row[PidHistoryEventTable::PID],
                    'script' => $row[PidHistoryEventTable::SCRIPT_CODE],
                    'user_id' => $row[PidHistoryEventTable::INITIATOR_USER_ID],
                    'started_at' => $row['MAX_CREATED_AT'],
                ];
            }
        }

        return $result;
    }

    /**
     * Очистить историю старше N дней
     */
    public function cleanOldEvents(int $days = 30): void
    {
        $date = new DateTime();
        $date->add('-' . $days . ' days');

        PidHistoryEventTable::deleteList([
            '<' . PidHistoryEventTable::CREATED_AT => $date,
        ]);
    }
}
