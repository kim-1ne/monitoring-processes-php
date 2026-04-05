<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<div class="script-monitor">

    <div class="monitor-header">
        <h3>Мониторинг скриптов</h3>
        <button id="show-history-btn" class="ui-btn ui-btn-secondary">
            Показать историю
        </button>
    </div>

    <div class="running-processes">
        <h3>Запущенные процессы</h3>
        <button id="resume-monitoring" style="display: none;">Возобновить мониторинг</button>
        <table class="process-table" id="running-processes-table">
            <thead>
            <tr>
                <th>Скрипт</th>
                <th>PID</th>
                <th>Прогресс</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody id="running-processes-body">
            <?php foreach ($arResult['RUNNING_PROCESSES'] as $pid => $process): ?>
                <tr data-pid="<?= $pid ?>" id="process-<?= $pid ?>">
                    <td><?= htmlspecialchars($process['SCRIPT']) ?></td>
                    <td><?= $process['PID'] ?></td>
                    <td>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: <?= rtrim($process['HEARTBEAT'], '%') ?>%"></div>
                            <span class="progress-text"><?= $process['HEARTBEAT'] ?></span>
                        </div>
                    </td>
                    <td>
                        <button class="stop-process" data-pid="<?= $pid ?>">Остановить</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="available-scripts">
        <h3>Доступные скрипты</h3>
        <table class="scripts-table">
            <thead>
            <tr>
                <th>Скрипт</th>
                <th>Размер</th>
                <th>Изменен</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($arResult['SCRIPTS'] as $script): ?>
                <tr data-script-path="<?= $script['PATH'] ?>">
                    <td><?= htmlspecialchars($script['NAME']) ?></td>
                    <td><?= round($script['SIZE'] / 1024, 2) ?> KB</td>
                    <td><?= date('d.m.Y H:i:s', $script['MODIFIED']) ?></td>
                    <td>
                        <button class="start-script">Запустить</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>