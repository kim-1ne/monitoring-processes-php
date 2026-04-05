<?php

namespace Kim1ne\MonitoringPhp\Cli\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DateTimeField;
use Bitrix\Main\Type\DateTime;

class PidActiveProcessTable extends DataManager
{
    const PID = 'PID';
    const UUID = 'UUID';
    const SCRIPT_CODE = 'SCRIPT_CODE';
    const HEARTBEAT_VALUE = 'HEARTBEAT_VALUE';
    const HEARTBEAT_AT = 'HEARTBEAT_AT';
    const STARTED_AT = 'STARTED_AT';

    public static function getTableName(): string
    {
        return 'custom_main_pid_active_process';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField(self::PID, [
                'primary' => true,
                'required' => true,
            ]),
            new StringField(self::UUID, [
                'primary' => true,
                'required' => true,
            ]),
            new StringField(self::SCRIPT_CODE, [
                'required' => true,
            ]),
            new StringField(self::HEARTBEAT_VALUE, [
                'required' => true,
            ]),
            new DateTimeField(self::HEARTBEAT_AT, [
                'default_value' => new DateTime(),
            ]),
            new DateTimeField(self::STARTED_AT, [
                'default_value' => new DateTime(),
            ]),
        ];
    }
}
