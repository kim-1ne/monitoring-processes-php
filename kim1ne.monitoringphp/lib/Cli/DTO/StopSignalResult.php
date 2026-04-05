<?php

namespace Kim1ne\MonitoringPhp\Cli\DTO;

readonly class StopSignalResult
{
    public function __construct(
        public int $pid,
        public ?string $error = null,
    ) {}

    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    public static function success(int $pid): self
    {
        return new self($pid);
    }

    public static function error(int $pid, string $error): self
    {
        return new self($pid, $error);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess(),
            'pid' => $this->pid,
            'error' => $this->error,
        ];
    }
}
