window.ScriptMonitorHistory = class {
    init() {
        let currentFilter = {
            script: '',
            event_type: 'all',
            pid: '',
            user_id: ''
        };

        let currentOffset = 0;
        let hasMore = true;
        let isLoading = false;

        function waitForElement(selector, callback) {
            const element = document.querySelector(selector);
            if (element) {
                callback();
            } else {
                setTimeout(() => waitForElement(selector, callback), 100);
            }
        }

        function initFilter() {
            const scriptInput = document.querySelector('#filter-script');
            const eventSelect = document.querySelector('#filter-event-type');
            const pidInput = document.querySelector('#filter-pid');
            const userIdInput = document.querySelector('#filter-user-id');

            if (scriptInput) scriptInput.value = currentFilter.script;
            if (eventSelect) eventSelect.value = currentFilter.event_type;
            if (pidInput) pidInput.value = currentFilter.pid;
            if (userIdInput) userIdInput.value = currentFilter.user_id;
        }

        function initFormSubmit() {
            const applyBtn = document.querySelector('.apply-filter');
            const resetBtn = document.querySelector('.reset-filter');

            if (applyBtn) {
                applyBtn.addEventListener('click', function() {
                    const scriptInput = document.querySelector('#filter-script');
                    const eventSelect = document.querySelector('#filter-event-type');
                    const pidInput = document.querySelector('#filter-pid');
                    const userIdInput = document.querySelector('#filter-user-id');

                    currentFilter = {
                        script: scriptInput ? scriptInput.value : '',
                        event_type: eventSelect ? eventSelect.value : 'all',
                        pid: pidInput ? pidInput.value : '',
                        user_id: userIdInput ? userIdInput.value : ''
                    };

                    loadHistory(true);
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    currentFilter = {
                        script: '',
                        event_type: 'all',
                        pid: '',
                        user_id: ''
                    };

                    const scriptInput = document.querySelector('#filter-script');
                    const eventSelect = document.querySelector('#filter-event-type');
                    const pidInput = document.querySelector('#filter-pid');
                    const userIdInput = document.querySelector('#filter-user-id');

                    if (scriptInput) scriptInput.value = '';
                    if (eventSelect) eventSelect.value = 'all';
                    if (pidInput) pidInput.value = '';
                    if (userIdInput) userIdInput.value = '';

                    loadHistory(true);
                });
            }
        }

        function loadHistory(reset = true) {
            if (reset) {
                currentOffset = 0;
                hasMore = true;
                const tbody = document.getElementById('history-table-body');
                if (tbody) tbody.innerHTML = '';
            }

            if (isLoading || !hasMore) return;

            isLoading = true;

            const tbody = document.getElementById('history-table-body');
            if (reset && tbody) {
                tbody.innerHTML = '<tr><td colspan="7" class="loading-message">Загрузка...</td></tr>';
            }

            const params = {};
            if (currentFilter.script) params.script = currentFilter.script;
            if (currentFilter.event_type !== 'all') params.event_type = currentFilter.event_type;
            if (currentFilter.pid) params.pid = currentFilter.pid;
            if (currentFilter.user_id !== undefined && currentFilter.user_id !== '') {
                params.user_id = currentFilter.user_id;
            }
            params.offset = currentOffset;

            BX.ajax.runComponentAction('kim1ne.monitoringphp:script.monitor.history', 'getHistory', {
                mode: 'class',
                data: params
            }).then(function(response) {
                const data = response.data;

                if (reset) {
                    renderTable(data.items);
                } else {
                    appendTable(data.items);
                }

                hasMore = data.has_more;
                currentOffset = data.next_offset;
                isLoading = false;

                const loadMoreBtn = document.getElementById('load-more-btn');
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = hasMore ? 'inline-block' : 'none';
                }
            }).catch(function(error) {
                console.error('Error loading history:', error);
                if (reset) {
                    const tbody = document.getElementById('history-table-body');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="7" class="error-message">Ошибка загрузки данных</td></tr>';
                    }
                }
                isLoading = false;
            });
        }

        function appendTable(items) {
            const tbody = document.getElementById('history-table-body');
            if (!tbody) return;

            if (!items || items.length === 0) return;

            let html = tbody.innerHTML;
            for (const item of items) {
                html += renderRow(item);
            }
            tbody.innerHTML = html;

            document.querySelectorAll('.show-details').forEach(btn => {
                btn.removeEventListener('click', handleDetailsClick);
                btn.addEventListener('click', handleDetailsClick);
            });
        }

        function renderTable(items) {
            const tbody = document.getElementById('history-table-body');
            if (!tbody) return;

            if (!items || items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-message">Записей не найдено</td></tr>';
                return;
            }

            let html = '';
            for (const item of items) {
                html += renderRow(item);
            }
            tbody.innerHTML = html;

            document.querySelectorAll('.show-details').forEach(btn => {
                btn.addEventListener('click', handleDetailsClick);
            });
        }

        function renderRow(item) {
            return `
            <tr data-uuid="${escapeHtml(item.PROCESS_UUID)}">
                <td>${escapeHtml(item.ID)}</td>
                <td class="script-name">${escapeHtml(item.SCRIPT_CODE)}</td>
                <td>${escapeHtml(item.PID)}</td>
                <td>
                    <span class="event-badge" style="background: ${escapeHtml(item.EVENT_COLOR)}">
                        ${escapeHtml(item.EVENT_NAME)}
                    </span>
                </td>
                <td>${escapeHtml(item.USER_NAME)}</td>
                <td>${escapeHtml(item.CREATED_AT)}</td>
                <td class="details">
                    ${item.PAYLOAD ? `<div class="show-details" data-payload="${escapeHtml(item.PAYLOAD)}">Показать</div>` : '—'}
                </td>
            </tr>
        `;
        }

        function handleDetailsClick(e) {
            const payload = e.currentTarget.dataset.payload;
            alert(payload);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                loadHistory(false);
            });
        }

        // Инициализация с ожиданием появления таблицы
        waitForElement('#history-table-body', function() {
            initFilter();
            initFormSubmit();
            loadHistory(true);
            console.log('History component initialized');
        });
    }
}
