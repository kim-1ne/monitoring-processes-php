<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<div class="script-history">
    <h2>История запусков скриптов</h2>

    <div class="history-filter">
        <div class="filter-row">
            <div class="filter-field">
                <label>Скрипт:</label>
                <input type="text" name="script" placeholder="Название скрипта" id="filter-script">
            </div>
            <div class="filter-field">
                <label>Тип события:</label>
                <select name="event_type" id="filter-event-type">
                    <option value="all">Все</option>
                    <?php foreach (['hand_start', 'start', 'hand_end', 'end', 'error'] as $type): ?>
                        <option value="<?= $type ?>"><?= $component->getEventTypeName($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label>PID:</label>
                <input type="number" name="pid" placeholder="PID" id="filter-pid">
            </div>
            <div class="filter-field">
                <label>ID пользователя (0 = Система):</label>
                <input type="number" name="user_id" placeholder="0 - Система, или ID" id="filter-user-id">
            </div>
            <div class="filter-field filter-buttons">
                <div class="apply-filter ui-btn ui-btn-primary">Применить</div>
                <div class="reset-filter ui-btn ui-btn-light-border">Сбросить</div>
            </div>
        </div>
    </div>

    <div class="history-table">
        <table class="bx-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Скрипт</th>
                <th>PID</th>
                <th>Событие</th>
                <th>Пользователь</th>
                <th>Дата/время</th>
                <th>Детали</th>
            </tr>
            </thead>
            <tbody id="history-table-body">
            <tr><td colspan="7" class="loading-message">Загрузка...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="load-more-container" style="text-align: center; margin-top: 20px;">
        <div id="load-more-btn" class="ui-btn ui-btn-secondary" style="display: none;">Загрузить ещё</div>
    </div>
</div>

<script>
    new ScriptMonitorHistory().init();
</script>
