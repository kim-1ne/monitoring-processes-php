<?php

namespace Kim1ne\MonitoringPhp\Cli\Trait;

use Kim1ne\MonitoringPhp\Cli\Monitor\HeartbeatReader;
use Kim1ne\MonitoringPhp\Cli\Monitor\HeartBeat;
use Kim1ne\MonitoringPhp\Cli\Script\Signal;

trait HeartbeatCapableTrait
{
    /**
     * Зарегистрировать обработчик сигнала
     */
    final protected function registerSignals(): void
    {
        $this->getSignal()->registerSignals([$this, 'signalHeartBeat']);
    }

    /**
     * Обработчик сигнала SIGUSR1
     */
    final public function signalHeartBeat(): void
    {
        $heartbeatReader = new HeartbeatReader();
        $heartbeatReader->write($this->heartBeat());
    }

    /**
     * Получить текущее значение heartbeat
     */
    public function heartBeat(): HeartBeat
    {
        return new HeartBeat('Работает');
    }

    abstract protected function getSignal(): Signal;
}
