<?php declare(strict_types=1);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin.php");

\CModule::IncludeModule("kim1ne.monitoringphp");

$APPLICATION->IncludeComponent("kim1ne.monitoringphp:script.monitor", ".default");
