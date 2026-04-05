<?php

namespace Kim1ne\MonitoringPhp\Cli\DTO;

use Kim1ne\MonitoringPhp\Cli\Process\Process;

readonly class ProcessData
{
    public function __construct(
        public int $pid,
        public string $uuid,
        public string $script,
        public int $startedAt,     // timestamp
        public int $heartbeat,
        public int $heartbeatAt,   // timestamp
    ) {}

    public function getProcess(): Process
    {
        return new Process($this->pid);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            pid: (int)$data['pid'],
            uuid: (string)$data['uuid'],
            script: (string)$data['script'],
            startedAt: self::normalizeTimestamp($data['started_at']),
            heartbeat: (int)$data['heartbeat'],
            heartbeatAt: self::normalizeTimestamp($data['heartbeat_at']),
        );
    }

    private static function normalizeTimestamp(mixed $value): int
    {
        return match(true) {
            $value instanceof \DateTimeInterface => $value->getTimestamp(),
            is_numeric($value) => (int)$value,
            is_string($value) => (new \DateTimeImmutable($value))->getTimestamp(),
            default => throw new \InvalidArgumentException('Invalid datetime value'),
        };
    }

    public function toArray(): array
    {
        return [
            'pid' => $this->pid,
            'uuid' => $this->uuid,
            'script' => $this->script,
            'started_at' => $this->startedAt,
            'heartbeat' => $this->heartbeat,
            'heartbeat_at' => $this->heartbeatAt,
        ];
    }

    public function getStartedAtDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('@' . $this->startedAt);
    }

    public function getHeartbeatAtDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('@' . $this->heartbeatAt);
    }
}
