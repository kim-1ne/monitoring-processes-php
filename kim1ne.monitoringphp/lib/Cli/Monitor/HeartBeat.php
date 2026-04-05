<?php

namespace Kim1ne\MonitoringPhp\Cli\Monitor;

readonly class HeartBeat implements \JsonSerializable
{
    private int $pid;

    public function __construct(
        public string|int $status,
        ?int $pid = null,
    ) {
        $this->pid = $pid ?? getmypid();
    }

    public function jsonSerialize(): mixed
    {
        return [
            'heartbeat' => $this->status,
            'timestamp' => time(),
            'pid' => $this->pid,
        ];
    }
    public function getPid(): int
    {
        return $this->pid;
    }

    public function saveFile(): void
    {
        (new HeartbeatReader())->write($this);
    }
}
