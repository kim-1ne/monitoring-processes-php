<?php

namespace Kim1ne\MonitoringPhp\Cli\Storage\Database;

use Kim1ne\MonitoringPhp\Cli\DTO\ProcessData;
use Kim1ne\MonitoringPhp\Cli\Orm\PidActiveProcessTable;
use Kim1ne\MonitoringPhp\Cli\Storage\ProcessStorageInterface;
use Kim1ne\MonitoringPhp\Tools\ProcessResult;
use Bitrix\Main\Type\DateTime;

readonly class DatabaseStorage implements ProcessStorageInterface
{
    public function add(int $pid, string $scriptName, array $metadata = []): ProcessData
    {
        $now = new DateTime();

        $data = [
            PidActiveProcessTable::PID => $pid,
            PidActiveProcessTable::UUID => $this->generateUuid(),
            PidActiveProcessTable::HEARTBEAT_VALUE => 0,
            PidActiveProcessTable::SCRIPT_CODE => $scriptName,
            PidActiveProcessTable::STARTED_AT => $now,
            PidActiveProcessTable::HEARTBEAT_AT => $now,
        ];

        $result = PidActiveProcessTable::add($data);
        ProcessResult::throwResultIfNotSuccess($result);

        return new ProcessData(
            pid: $data[PidActiveProcessTable::PID],
            uuid: $data[PidActiveProcessTable::UUID],
            script: $data[PidActiveProcessTable::SCRIPT_CODE],
            startedAt: $now->getTimestamp(),
            heartbeat: $data[PidActiveProcessTable::HEARTBEAT_VALUE],
            heartbeatAt: $now->getTimestamp(),
        );
    }

    private function generateUuid(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function remove(int $pid, ?string $uuid = null): void
    {
        $filter = [
            PidActiveProcessTable::PID => $pid,
        ];

        if ($uuid !== null) {
            $filter[PidActiveProcessTable::UUID] = $uuid;
        }

        $res = PidActiveProcessTable::getList([
            'filter' => $filter,
            'select' => [
                PidActiveProcessTable::PID,
                PidActiveProcessTable::UUID,
            ]
        ]);

        while ($row = $res->fetch()) {
            $result = PidActiveProcessTable::delete($row);
            ProcessResult::throwResultIfNotSuccess($result);
        }
    }

    public function get(int $pid, ?string $uuid = null): ?ProcessData
    {
        $filter = [
            PidActiveProcessTable::PID => $pid,
        ];

        if ($uuid !== null) {
            $filter[PidActiveProcessTable::UUID] = $uuid;
        }

        $res = PidActiveProcessTable::getList([
            'filter' => $filter,
            'order' => [
                PidActiveProcessTable::STARTED_AT => 'DESC',
            ],
            'limit' => 1,
        ])->fetch();

        if (!$res) {
            return null;
        }

        return new ProcessData(
            pid: (int)$res[PidActiveProcessTable::PID],
            uuid: $res[PidActiveProcessTable::UUID],
            script: $res[PidActiveProcessTable::SCRIPT_CODE],
            startedAt: $res[PidActiveProcessTable::STARTED_AT]->getTimestamp(),
            heartbeat: (int)$res[PidActiveProcessTable::HEARTBEAT_VALUE],
            heartbeatAt: $res[PidActiveProcessTable::HEARTBEAT_AT]->getTimestamp(),
        );
    }

    /**
     * @return ProcessData[]
     */
    public function getAll(): array
    {
        $data = [];

        $res = PidActiveProcessTable::getList();

        while ($row = $res->fetch()) {
            $pid = (int)$row[PidActiveProcessTable::PID];
            $data[$pid] = new ProcessData(
                pid: $pid,
                uuid: $row[PidActiveProcessTable::UUID],
                script: $row[PidActiveProcessTable::SCRIPT_CODE],
                startedAt: $row[PidActiveProcessTable::STARTED_AT]->getTimestamp(),
                heartbeat: (int)$row[PidActiveProcessTable::HEARTBEAT_VALUE],
                heartbeatAt: $row[PidActiveProcessTable::HEARTBEAT_AT]->getTimestamp(),
            );
        }

        return $data;
    }

    public function updateHeartbeat(int $pid, string $uuid, string $heartbeat): void
    {
        $result = PidActiveProcessTable::update(
            [
                PidActiveProcessTable::PID => $pid,
                PidActiveProcessTable::UUID => $uuid
            ],
            [
                PidActiveProcessTable::HEARTBEAT_VALUE => $heartbeat,
                PidActiveProcessTable::HEARTBEAT_AT => new DateTime(),
            ]
        );

        ProcessResult::throwResultIfNotSuccess($result);
    }

    public function has(int $pid, string $uuid): bool
    {
        $res = PidActiveProcessTable::getList([
            'filter' => [
                PidActiveProcessTable::PID => $pid,
                PidActiveProcessTable::UUID => $uuid
            ]
        ])->fetch();

        return $res !== false;
    }
}
