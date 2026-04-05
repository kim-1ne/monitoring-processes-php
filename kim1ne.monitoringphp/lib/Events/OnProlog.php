<?php

namespace Kim1ne\MonitoringPhp\Events;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Kim1ne\MonitoringPhp\Cli\ProcessManager;
use Kim1ne\MonitoringPhp\Cli\Repository\PidHistoryEventRepository;
use Kim1ne\MonitoringPhp\Cli\Script\ScriptPath;
use Kim1ne\MonitoringPhp\Cli\Script\Signal;
use Kim1ne\MonitoringPhp\Cli\Script\WhiteList;
use Kim1ne\MonitoringPhp\Cli\Storage\Database\DatabaseStorage;

class OnProlog
{
    public static function init(): void
    {
        self::setAdminMenu();
        self::registerServices();
    }

    private static function setAdminMenu(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->addEventHandler(
            'main',
            'OnBuildGlobalMenu',
            function (array $tabs, array &$items) {
                $items[] = [
                    'parent_menu' => 'global_menu_settings',
                    'section' => 'kim1ne_monitoring_php',
                    'sort' => 2,
                    'text' => 'Мониторинг PHP процессов (kim1ne.monitoringphp)',
                    'title' => 'Мониторинг PHP процессов (kim1ne.monitoringphp)',
                    'url' => 'kim1ne_monitoring.php',
                    'more_url' => []
                ];
            }
        );
    }

    private static function registerServices(): void
    {
        ServiceLocator::getInstance()->addInstanceLazy(ProcessManager::class, [
            'constructor' => static function (): ProcessManager {

                $paths = [];
                $event = new Event("kim1ne.monitoringphp", "InitializeScriptPath", [
                    'paths' => &$paths
                ]);

                $event->send();

                foreach ($paths as $k => $path) {
                    if ($path instanceof ScriptPath === false) {
                        unset($paths[$k]);
                    }
                }

                return new ProcessManager(
                    new WhiteList(
                        ...$paths
                    ),
                    new DatabaseStorage(),
                    new PidHistoryEventRepository()
                );
            }
        ]);

        ServiceLocator::getInstance()->addInstanceLazy(Signal::class, [
            'constructor' => static function () {
                return ServiceLocator::getInstance()->get(ProcessManager::class)->signal;
            }
        ]);
    }
}
