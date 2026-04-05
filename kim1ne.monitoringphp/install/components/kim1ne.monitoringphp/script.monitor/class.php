<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI\Extension;
use Kim1ne\MonitoringPhp\Cli\ProcessManager;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Contract\Controllerable;

class ScriptMonitorComponent extends CBitrixComponent implements Controllerable
{
    private readonly ProcessManager $processManager;

    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->processManager = ServiceLocator::getInstance()->get(ProcessManager::class);
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['HEARTBEAT_INTERVAL'] = (int)($arParams['HEARTBEAT_INTERVAL'] ?? 3);

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!$this->checkRights()) {
            ShowError('Недостаточно прав');
            return;
        }

        \CJSCore::Init(['ajax']);
        Extension::load(['ui.fonts.opensans', 'ui.buttons', 'ui.forms']);

        $this->arResult['SCRIPTS'] = $this->getScripts();
        $this->arResult['RUNNING_PROCESSES'] = $this->getRunningProcesses();
        $this->arResult['HEARTBEAT_INTERVAL'] = $this->arParams['HEARTBEAT_INTERVAL'];
        $this->includeComponentTemplate();
    }

    private function getRunningProcesses(): array
    {
        $runningProcesses = $this->processManager->getRunningProcesses();

        $processes = [];

        foreach ($runningProcesses as $process) {
            $processes[$process->pid] = [
                'PID' => $process->pid,
                'SCRIPT' => $process->script,
                'STARTED' => date('Y-m-d H:i:s', $process->startedAt),
                'HEARTBEAT' => $process->heartbeat . '%',
                'UUID' => $process->uuid,
            ];
        }

        return $processes;
    }

    private function getScripts(): array
    {
        $scripts = [];

        $allScripts = $this->processManager->whiteList->getScripts();

        foreach ($allScripts as $script) {
            $scripts[] = [
                'NAME' => $script['name'],
                'PATH' => $script['path'],
                'SIZE' => $script['size'],
                'MODIFIED' => $script['modified'],
                'FILE_NAME' => $script['name'],
            ];
        }

        return $scripts;
    }

    private function checkRights(): bool
    {
        return CurrentUser::get()?->isAdmin() ?? false;
    }

    public function configureActions(): array
    {
        return [
            'start' => ['prefilters' => []],
            'heartbeat' => ['prefilters' => []],
            'stop' => ['prefilters' => []],
        ];
    }

    public function startAction(): array
    {
        if (!$this->checkRights()) {
            throw new \Exception('Недостаточно прав');
        }

        $script = $this->getRequiredParameter('script');

        $userId = CurrentUser::get()?->getId() ?? 0;

        return $this
            ->processManager
            ->signal
            ->start($script, $userId)
            ->toArray();
    }

    public function heartbeatAction(): array
    {
        if (!$this->checkRights()) {
            throw new \Exception('Недостаточно прав');
        }

        $pid = $this->getRequiredParameter('pid', true);
        $uuid = $this->getRequiredParameter('uuid');

        if ($pid <= 0) {
            throw new \Exception('Parameter pid is required');
        }

        if (empty($uuid)) {
            throw new \Exception('Parameter uuid is required');
        }

        return $this
            ->processManager
            ->signal
            ->heartbeat($pid, $uuid)
            ->toArray();
    }

    private function getRequiredParameter(string $name, bool $isInt = false): mixed
    {
        $value = $this->request->get($name);

        if ($isInt) {
            $value = (int)$value;
            if ($value <= 0) {
                throw new \Exception("Parameter {$name} is required and must be positive integer");
            }
        } elseif (empty($value)) {
            throw new \Exception("Parameter {$name} is required");
        }

        return $value;
    }

    public function stopAction(): array
    {
        if (!$this->checkRights()) {
            throw new \Exception('Недостаточно прав');
        }

        $pid = (int)$this->request->get('pid');
        $uuid = $this->request->get('uuid');

        if ($pid <= 0) {
            throw new \Exception('Parameter pid is required');
        }

        if (empty($uuid)) {
            throw new \Exception('Parameter uuid is required');
        }

        $userId = CurrentUser::get()?->getId() ?? 0;

        return $this
            ->processManager
            ->signal
            ->stop($pid, $uuid, $userId)
            ->toArray();
    }
}
