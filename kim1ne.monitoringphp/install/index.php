<?php

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\File;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Kim1ne\MonitoringPhp\Cli\Orm\PidActiveProcessTable;
use Kim1ne\MonitoringPhp\Cli\Orm\PidHistoryEventTable;
use Bitrix\Main\IO\Directory;
use Kim1ne\MonitoringPhp\Events\OnProlog;

Loc::loadMessages(__FILE__);

class kim1ne_monitoringphp extends CModule
{
    public $MODULE_ID = 'kim1ne.monitoringphp';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('KIM1NE_MONITORINGPHP_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('KIM1NE_MONITORINGPHP_MODULE_DESC');
        $this->PARTNER_NAME = 'Kim1ne';
        $this->PARTNER_URI = 'https://github.com/kim1ne';
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        \CModule::IncludeModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallEvents();
    }

    public function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'main',
            'OnProlog',
            $this->MODULE_ID,
            OnProlog::class,
            'init'
        );
    }

    public function DoUninstall()
    {
        \CModule::IncludeModule($this->MODULE_ID);
        $this->UninstallDB();
        $this->UninstallFiles();
        $this->UnInstallEvents();
        ModuleManager::unregisterModule($this->MODULE_ID);
    }

    public function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'main',
            'OnProlog',
            $this->MODULE_ID,
            OnProlog::class,
            'init'
        );
    }

    public function InstallDB()
    {
        $conn = Application::getConnection();

        foreach ([PidActiveProcessTable::getEntity(), PidHistoryEventTable::getEntity()] as $entity) {

            if (!$conn->isTableExists($entity->getDBTableName())) {
                $sql = implode(';', $entity->compileDbTableStructureDump());
                $conn->query($sql);
            }

        }
    }

    public function UninstallDB()
    {
        $conn = Application::getConnection();
        foreach ([PidActiveProcessTable::getTableName(), PidHistoryEventTable::getTableName()] as $tableName) {

            if ($conn->isTableExists($tableName)) {
                $conn->dropTable($tableName);
            }

        }
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components/',
            true,
            true
        );

        CopyDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/',
            true,
            true
        );
    }

    public function UninstallFiles()
    {
        $dirs = [
            '/local/components/kim1ne.monitoringphp',
        ];

        $files = [
            'bitrix/admin/kim1ne_monitoring.php',
        ];

        foreach ($dirs as $dir) {
            $dir = $_SERVER['DOCUMENT_ROOT'] . $dir;

            if (is_dir($dir)) {
                Directory::deleteDirectory($dir);
            }
        }

        foreach ($files as $file) {
            $file = $_SERVER['DOCUMENT_ROOT'] . $file;

            if (is_file($file)) {
                File::deleteFile($file);
            }
        }
    }
}
