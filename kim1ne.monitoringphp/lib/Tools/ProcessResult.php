<?php

namespace Kim1ne\MonitoringPhp\Tools;

use Bitrix\Main\Result;

class ProcessResult
{
    public static function implode(Result $result, string $prependMessage = ''): string
    {
        return $prependMessage . implode(", ", $result->getErrorMessages());
    }

    public static function throwResultIfNotSuccess(Result $result, string $prependMessage = ''): Result
    {
        if ($result->isSuccess() === false) {
            throw new \Exception(self::implode($result, $prependMessage));
        }

        return $result;
    }
}
