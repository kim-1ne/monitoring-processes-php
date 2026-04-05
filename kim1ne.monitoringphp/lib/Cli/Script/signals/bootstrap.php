<?php

if (php_sapi_name() !== 'cli') {
    die;
}

$cliDir = dirname(__DIR__, 2);

require_once $cliDir . '/Process/Process.php';
require_once $cliDir . '/Monitor/HeartbeatReader.php';
require_once $cliDir . '/Monitor/HeartBeat.php';
require_once $cliDir . '/ShellExec.php';