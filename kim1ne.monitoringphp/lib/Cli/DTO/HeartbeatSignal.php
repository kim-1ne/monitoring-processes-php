<?php

namespace Kim1ne\MonitoringPhp\Cli\DTO;

readonly class HeartbeatSignal
{
    public function __construct(
        public bool    $running,
        public string  $heartbeat,
        public int     $pid,
        public ?string $error = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            running: $data['running'] ?? false,
            heartbeat: $data['heartbeat'] ?? '0%',
            pid: $data['pid'] ?? 0,
            error: $data['error'] ?? null
        );
    }

    public static function success(int $pid, string $heartbeat): self
    {
        return new self(true, $heartbeat, $pid);
    }

    public static function failure(int $pid, ?string $error = null): self
    {
        return new self(false, '0%', $pid, $error);
    }

    public static function default(int $pid): self
    {
        return new self(true, '0%', $pid);
    }

    public function toArray(): array
    {
        return [
            'running' => $this->running,
            'heartbeat' => $this->heartbeat,
            'pid' => $this->pid,
            'error' => $this->error
        ];
    }

    public function isRunning(): bool
    {
        return $this->running;
    }
}
