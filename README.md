# Мониторинг PHP процессов для Битрикс24

Модуль для запуска, мониторинга и управления долгими CLI-скриптами из веб-интерфейса Битрикс24.

## Возможности

- 🚀 Запуск скриптов — запуск PHP-скриптов из веб-интерфейса
- 📊 Мониторинг прогресса — отслеживание выполнения через heartbeat
- 🛑 Остановка процессов — принудительная остановка по сигналу
- 📜 История запусков — журнал всех событий с фильтрацией
- 📁 Множественные директории — поддержка скриптов из разных папок
- 🔒 Безопасность — белый список разрешённых скриптов

## Установка

### 1. Скопировать модуль

```bash
git clone https://github.com/kim1ne/monitoringphp.git /local/modules/kim1ne.monitoringphp/
```

### 2. Установить модуль

Администрирование → Marketplace → Установленные решения и Установить модуль "Мониторинг PHP процессов".

### 3. Настройка доступа

После установки модуля в Административной панели будет доступен пункт меню "Мониторинг PHP процессов" в разделе "Настройки".

Изначально список скриптов пустой. Чтобы добавить свои директории, нужно подписаться на событие модуля InitializeScriptPath:
```php
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Kim1ne\MonitoringPhp\Cli\Script\ScriptPath;

$eventManager = EventManager::getInstance()->addEventHandler(
    'kim1ne.monitoringphp',
    'InitializeScriptPath',
    function (Event $event) {
        $root = \Bitrix\Main\Application::getInstance()->getContext()->getServer()->getDocumentRoot();
    
        $paths = $event->getParameter('paths');
        $paths[] = new ScriptPath($root . '/local/php_interface/scripts/');
    
        $event->setParameter('paths', $paths);
    }
);
```

## Принцип работы

### Подготовка скрипта к мониторингу

Чтобы скрипт умел обрабатывать heartbeat, он должен использовать трейт Kim1ne\MonitoringPhp\Cli\Trait\HeartbeatCapableTrait.

Трейт требует реализации метода getSignal. Получить объект Signal можно через DI-контейнер:

```php
use Bitrix\Main\DI\ServiceLocator;
use Kim1ne\MonitoringPhp\Cli\Script\Signal;

$signal = ServiceLocator::getInstance()->get(Signal::class);
```
В начале выполнения скрипта необходимо зарегистрировать обработку сигналов вызовом метода registerSignals (доступен из трейта).

Для отправки понятного heartbeat-статуса переопределите метод heartBeat из трейта.

Пример реализации скрипта: https://github.com/kim-1ne/monitoring-processes-php/tree/master/kim1ne.monitoringphp/examples/my_script.php

## API

### Kim1ne\MonitoringPhp\Cli\ProcessManager

Сердце модуля. Отвечает за всю логику работы.

```php
use Bitrix\Main\DI\ServiceLocator;
use Kim1ne\MonitoringPhp\Cli\ProcessManager;

$processManager = ServiceLocator::getInstance()->get(ProcessManager::class);

$processManager->signal;                    // Объект Signal
$processManager->whiteList;                 // Объект WhiteList
$processManager->getRunningProcesses();     // Возвращает запущенные процессы
```
### Kim1ne\MonitoringPhp\Cli\Script\Signal

Центральный класс для работы с сигналами. Отвечает за регистрацию обработчиков, запуск, остановку и heartbeat.

```php
use Bitrix\Main\DI\ServiceLocator;
use Kim1ne\MonitoringPhp\Cli\Script\Signal;

$signal = ServiceLocator::getInstance()->get(Signal::class);

$signal->stop($pid, $uuid, $userId);        // Остановка процесса
$heartbeatSignal = $signal->heartbeat($pid, $uuid); // Получение статуса
$signal->start('/local/php_interface/scripts/my_script.php', $userId); // Запуск
$signal->registerSignals();                 // Регистрация сигналов
```
### Kim1ne\MonitoringPhp\Cli\Script\WhiteList

Класс для поиска скрипта по белым спискам директорий.

```php
use Kim1ne\MonitoringPhp\Cli\Script\WhiteList;
use Kim1ne\MonitoringPhp\Cli\Script\ScriptPath;

$whiteList = new WhiteList(
    new ScriptPath('/local/php_interface/scripts/'),
    new ScriptPath('/local/modules/my.module/cron/'),
);

$path = $whiteList->resolveAllowedPath('/local/php_interface/scripts/my_script.php');
```

### Kim1ne\MonitoringPhp\Cli\Script\ScriptPath

Класс, содержащий путь к директории со скриптами и методы поиска.

```php
use Kim1ne\MonitoringPhp\Cli\Script\ScriptPath;

$scriptPath = new ScriptPath(
    '/local/php_interface/scripts/',
    excludeFiles: ['cli_prolog.php']
);

$scriptPath->getScriptPath('my_script.php'); // Полный путь к скрипту
$scripts = $scriptPath->getScripts();        // Список всех скриптов в директории
```

## Известные проблемы

При завершении скрипта с ошибкой allowed memory size невозможно перехватить событие для записи в историю, так как процесс получает сигнал SIGKILL, который не перехватывается.
