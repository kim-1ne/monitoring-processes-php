<?php

use Bitrix\Main\DI\ServiceLocator;
use Kim1ne\MonitoringPhp\Cli\Monitor\HeartBeat;
use Kim1ne\MonitoringPhp\Cli\Script\Signal;
use Kim1ne\MonitoringPhp\Cli\Trait\HeartbeatCapableTrait;

if (php_sapi_name() !== 'cli') {
    die;
}

const NOT_CHECK_PERMISSIONS = true;

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 3);

require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

\CModule::IncludeModule('kim1ne.monitoringphp');

class MyScript
{
    use HeartbeatCapableTrait;

    private int $heartbeat = 0;

    public function run(): void
    {
        $this->registerSignals();

        while (true) {
            ++$this->heartbeat;

            if ($this->heartbeat === 101) {
                break;
            }

            usleep(500000);
        }
    }

    protected function getSignal(): Signal
    {
        return ServiceLocator::getInstance()->get(Signal::class);
    }

    public function heartBeat(): HeartBeat
    {
        return new HeartBeat(sprintf('%d/100', $this->heartbeat));
    }
}

(new MyScript())->run();
