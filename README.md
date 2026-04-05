# Мониторинг PHP процессов для Битрикс24

Модуль для запуска, мониторинга и управления долгими CLI-скриптами из веб-интерфейса Битрикс24.

## Возможности

- 🚀 **Запуск скриптов** — запуск PHP-скриптов из веб-интерфейса
- 📊 **Мониторинг прогресса** — отслеживание выполнения через heartbeat
- 🛑 **Остановка процессов** — принудительная остановка по сигналу
- 📜 **История запусков** — журнал всех событий с фильтрацией
- 📁 **Множественные директории** — поддержка скриптов из разных папок
- 🔒 **Безопасность** — белый список разрешённых скриптов

```bash
git clone https://github.com/kim1ne/monitoringphp.git /local/modules/kim1ne.monitoringphp/
```

После установки модуля в Административной панели будет доступен пункт меню "Мониторинг PHP процессов (kim1ne.monitoringphp)" во вкладке "Настройки"

Изначально список пустой, чтобы расширить, нужно подписаться на событие модуля InitializeScriptPath.
Должен расширяться параметр `paths` объектами ScriptPath

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

## API

Чтобы скрипт умел обрабатывать `heartbeat` он должен насследовать трейт `Kim1ne\MonitoringPhp\Cli\Trait\HeartbeatCapableTrait`.
Трейт попросит обязательный метод `getSignal`. Его можно будет получить так:
```php
use Bitrix\Main\DI\ServiceLocator\ServiceLocator;
use Kim1ne\MonitoringPhp\Cli\Script\Signal;
$signal = ServiceLocator::getInstance()->get(Signal::class);
```

В самом начале выполнения скрипта нужно зарегистрировать обработку сигналов. чтобы скрипт умел обрабатывать heartbeat и правильно завершатся для статистики. Нужно вызвать метод `registerSignals`, который лежит в трейте.
Чтобы скрипт умел присылать понятный heartbeat-статус, он должен переопределить метод heartBeat из трейта.

[Пример реализации скрипта из директории /local/php_interface/scripts/](/kim-1ne/monitoring-processes-php/tree/master/kim1ne.monitoringphp/examples/my_script.php)

## Известные проблемы
- Когда скрипт php завершается с ошибкой `allowed memory size` его невозможно перехватить чтобы записать окончание работы скрипта с ошибкой. SIGKILL не перехватывается ничем.
