<?php

namespace Kim1ne\MonitoringPhp\Cli\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DateTimeField;
use Bitrix\Main\Type\DateTime;

class PidHistoryEventTable extends DataManager
{
    const PROCESS_UUID = 'PROCESS_UUID';
    const PID = 'PID';
    const SCRIPT_CODE = 'SCRIPT_CODE';
    const EVENT_TYPE = 'EVENT_TYPE';
    const PAYLOAD = 'PAYLOAD';
    const CREATED_AT = 'CREATED_AT';
    const INITIATOR_USER_ID = 'INITIATOR_USER_ID';

    public static function getTableName(): string
    {
        return 'custom_main_pid_process_history';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => 'ID',
                ]
            ),
            new StringField(self::PROCESS_UUID, [
                'required' => true,
            ]),
            new IntegerField(self::PID, [
                'required' => true,
            ]),
            new StringField(self::SCRIPT_CODE, [
                'required' => true,
            ]),
            new StringField(self::PAYLOAD, [
                'default_value' => NULL,
            ]),
            new DateTimeField(self::CREATED_AT, [
                'default_value' => new DateTime(),
            ]),
            new IntegerField(self::INITIATOR_USER_ID, [
                'required' => true,
            ]),
            (new EnumField(self::EVENT_TYPE))
                ->configureTitle(self::EVENT_TYPE)
                ->configureDefaultValue(NULL)
                ->configureValues([
                    'hand_start',
                    'start',
                    'end',
                    'hand_end',
                    'error'
                ])
        ];
    }
}
