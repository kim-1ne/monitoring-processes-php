<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Kim1ne\MonitoringPhp\Cli\Orm\PidHistoryEventTable;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Engine\Response\Component as ResponseComponent;

class ScriptMonitorHistoryComponent extends CBitrixComponent implements Controllerable
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['PAGE_SIZE'] = (int)($arParams['PAGE_SIZE'] ?? 50);
        $arParams['AJAX_MODE'] = $arParams['AJAX_MODE'] ?? 'N';

        return $arParams;
    }

    public function executeComponent()
    {
        $this->checkRights();

        \CJSCore::Init(['ajax']);
        Extension::load(['ui.fonts.opensans', 'ui.buttons', 'ui.forms']);

        if ($this->arParams['AJAX_MODE'] === 'Y') {
            // Для AJAX запроса возвращаем только HTML
            $this->arResult['ITEMS'] = $this->getHistoryItems([]);
            $this->includeComponentTemplate('ajax');
        } else {
            // Для обычного вызова
            $this->includeComponentTemplate();
        }
    }

    private function checkRights(): void
    {
        global $USER;
        if (!$USER->isAdmin()) {
            ShowError('Недостаточно прав');
            die();
        }
    }

    public function configureActions(): array
    {
        return [
            'getHistory' => [
                'prefilters' => []
            ],
            'getComponent' => [
                'prefilters' => []
            ]
        ];
    }

    public function getHistoryAction(): array
    {
        $this->checkRights();

        $filter = [];

        $script = $this->request->get('script');
        if ($script) {
            $filter['SCRIPT_CODE'] = $script;
        }

        $eventType = $this->request->get('event_type');
        if ($eventType && $eventType !== 'all') {
            $filter['EVENT_TYPE'] = $eventType;
        }

        $pid = (int)$this->request->get('pid');
        if ($pid > 0) {
            $filter['PID'] = $pid;
        }

        $userId = $this->request->get('user_id');
        if ($userId !== null && $userId !== '') {
            $filter['INITIATOR_USER_ID'] = (int)$userId;
        }

        $offset = (int)$this->request->get('offset', 0);

        $items = $this->getHistoryItems($filter, $offset, 100);

        return [
            'items' => $items,
            'has_more' => count($items) === 100,
            'next_offset' => $offset + 100,
        ];
    }

    private function getHistoryItems(array $filter, int $offset = 0, int $limit = 100): array
    {
        $items = [];

        $query = PidHistoryEventTable::getList([
            'select' => [
                'ID',
                'PROCESS_UUID',
                'PID',
                'SCRIPT_CODE',
                'EVENT_TYPE',
                'PAYLOAD',
                'CREATED_AT',
                'INITIATOR_USER_ID',
                'USER_NAME' => 'USER.NAME',
                'USER_LAST_NAME' => 'USER.LAST_NAME',
                'USER_LOGIN' => 'USER.LOGIN',
            ],
            'filter' => $filter,
            'order' => ['ID' => 'DESC'],
            'limit' => $limit,
            'offset' => $offset,
            'runtime' => [
                new \Bitrix\Main\Entity\ReferenceField(
                    'USER',
                    \Bitrix\Main\UserTable::class,
                    ['=this.INITIATOR_USER_ID' => 'ref.ID'],
                    ['join_type' => 'LEFT']
                ),
            ],
        ]);

        while ($row = $query->fetch()) {
            $userId = $row['INITIATOR_USER_ID'];

            if ($userId <= 0) {
                $userName = 'Система';
            } else {
                $name = trim($row['USER_NAME'] . ' ' . $row['USER_LAST_NAME']);
                if ($name) {
                    $userName = $name . ' [' . $userId . ']';
                } else {
                    $userName = ($row['USER_LOGIN'] ?: 'Пользователь') . ' [' . $userId . ']';
                }
            }

            $items[] = [
                'ID' => $row['ID'],
                'PROCESS_UUID' => $row['PROCESS_UUID'],
                'PID' => $row['PID'],
                'SCRIPT_CODE' => $row['SCRIPT_CODE'],
                'EVENT_TYPE' => $row['EVENT_TYPE'],
                'EVENT_NAME' => $this->getEventTypeName($row['EVENT_TYPE']),
                'EVENT_COLOR' => $this->getEventTypeColor($row['EVENT_TYPE']),
                'PAYLOAD' => $row['PAYLOAD'],
                'CREATED_AT' => $row['CREATED_AT']->format('d.m.Y H:i:s'),
                'INITIATOR_USER_ID' => $userId,
                'USER_NAME' => $userName,
            ];
        }

        return $items;
    }

    private function getUserName(int $userId): string
    {
        if ($userId <= 0) {
            return 'Система';
        }

        $user = \Bitrix\Main\UserTable::getById($userId)->fetch();
        return $user ? trim($user['NAME'] . ' ' . $user['LAST_NAME']) : "Пользователь {$userId}";
    }

    public function getEventTypeName(string $type): string
    {
        return match($type) {
            'hand_start' => 'Ручной запуск',
            'start' => 'Автоматический запуск',
            'hand_end' => 'Ручная остановка',
            'end' => 'Нормальное завершение',
            'error' => 'Ошибка',
            default => $type,
        };
    }

    public function getEventTypeColor(string $type): string
    {
        return match($type) {
            'hand_start' => '#2196f3',
            'start' => '#4caf50',
            'hand_end' => '#ff9800',
            'end' => '#8bc34a',
            'error' => '#f44336',
            default => '#9e9e9e',
        };
    }

    public function getComponentAction(): ResponseComponent
    {
        $componentName = 'kim1ne.monitoringphp:script.monitor.history';
        $response = new ResponseComponent($componentName, '', []);
        $scriptFile = $this->getScriptPathComponent($componentName);
        $fullScriptFilePath = $_SERVER['DOCUMENT_ROOT'] . $scriptFile;

        $content = json_decode($response->getContent(), true);

        if (file_exists($fullScriptFilePath)) {
            $content['data']['assets']['js'][] = $scriptFile;
        }

        $content = json_encode($content);
        $response->setContent($content);
        return $response;

    }

    private function getScriptPathComponent(string $componentName): string
    {
        return '/local/components/' . str_replace(':', '/', $componentName) . '/templates/.default/script.js';
    }
}
