BX.ready(function () {

    const showHistoryBtn = document.getElementById('show-history-btn');
    if (showHistoryBtn) {
        showHistoryBtn.addEventListener('click', function() {
            BX.ajax.runComponentAction('kim1ne.monitoringphp:script.monitor.history', 'getComponent', {
                mode: 'class'
            }).then(async (response) => {


                let data = await (new Promise(async (resolve) => {
                    let loadCss = response.data.assets ? response.data.assets.css : [];
                    let loadJs = response.data.assets ? response.data.assets.js : [];

                    BX.load(loadCss, () => {
                        BX.loadScript(loadJs, async () => {
                            BX.Runtime.html(null, response.data.html).then(resolve(response.data));
                        });
                    });
                }));

                let popup = new BX.PopupWindow(crypto.randomUUID(), null, {
                    autoHide: true,
                    titleBar: 'История',
                    width: 1000,
                    lightShadow : true,
                    overlay: {
                        backgroundColor: 'black', opacity: '10'
                    },
                    closeByEsc: true,
                    buttons: [],
                    events: {
                        onPopupClose: () => {
                            popup.setContent('');
                            popup.destroy();
                        }
                    }
                });

                popup.setContent(response.data.html);

                popup.show();

                console.log(response)
            }).catch((response) => {
                console.log(response)
            });
        });
    }



    const activeMonitors = {};
    let monitoringEnabled = true;

    // Проверяем, есть ли запущенные процессы
    const hasRunningProcesses = document.querySelectorAll('#running-processes-body tr').length > 0;

    // Проверяем сохраненное состояние
    const savedState = sessionStorage.getItem('monitoringEnabled');
    if (savedState === 'false' && hasRunningProcesses) {
        monitoringEnabled = false;
        const resumeBtn = document.getElementById('resume-monitoring');
        if (resumeBtn) {
            resumeBtn.style.display = 'inline-block';
        }
    }

    // Кнопка возобновления мониторинга
    const resumeBtn = document.getElementById('resume-monitoring');
    if (resumeBtn) {
        resumeBtn.addEventListener('click', function() {
            monitoringEnabled = true;
            sessionStorage.setItem('monitoringEnabled', 'true');
            this.style.display = 'none';

            // Перезапускаем мониторинг для всех процессов
            document.querySelectorAll('#running-processes-body tr').forEach(row => {
                const pid = row.dataset.pid;
                if (pid && !activeMonitors[pid]) {
                    startHeartbeatMonitor(pid);
                }
            });
        });
    }

    // При перезагрузке страницы отключаем мониторинг
    window.addEventListener('beforeunload', function() {
        // Останавливаем все интервалы
        Object.keys(activeMonitors).forEach(pid => {
            if (activeMonitors[pid]) {
                clearTimeout(activeMonitors[pid]);
                delete activeMonitors[pid];
            }
        });

        // Сохраняем состояние только если есть процессы
        if (document.querySelectorAll('#running-processes-body tr').length > 0) {
            sessionStorage.setItem('monitoringEnabled', 'false');
        }
    });

    // Запуск скрипта
    document.querySelectorAll('.start-script').forEach(btn => {
        btn.addEventListener('click', async function() {
            const row = this.closest('tr');
            const scriptPath = row.dataset.scriptPath;
            const btn = this;

            // Включаем мониторинг
            monitoringEnabled = true;
            sessionStorage.setItem('monitoringEnabled', 'true');
            const resumeBtn = document.getElementById('resume-monitoring');
            if (resumeBtn) {
                resumeBtn.style.display = 'none';
            }

            btn.disabled = true;
            btn.textContent = 'Запуск...';

            await BX.ajax.runComponentAction('kim1ne.monitoringphp:script.monitor', 'start', {
                mode: "class",
                data: {
                    script: scriptPath
                }
            }).then((response) => {
                console.log(response)
                let data = response.data;

                addProcessToTable(data.pid, data.uuid, data.script);
                startHeartbeatMonitor(data.pid, data.uuid);

                btn.disabled = false;
                btn.textContent = 'Запустить';
            }).catch((response) => {
                console.log(response);
                alert('Ошибка запуска скрипта');
                btn.disabled = false;
                btn.textContent = 'Запустить';
            });
        });
    });

    // Остановка процесса
    document.addEventListener('click', async function(e) {
        if (e.target.classList.contains('stop-process')) {
            const pid = e.target.dataset.pid;
            const uuid = e.target.dataset.uuid;
            const btn = e.target;

            btn.disabled = true;
            btn.textContent = 'Остановка...';

            // Останавливаем мониторинг
            if (activeMonitors[pid]) {
                clearTimeout(activeMonitors[pid]);
                delete activeMonitors[pid];
            }

            // Отправляем запрос на остановку
            BX.ajax.runComponentAction('kim1ne.monitoringphp:script.monitor', 'stop', {
                mode: "class",
                data: {
                    pid: pid,
                    uuid: uuid,
                }
            }).then((response) => {
                console.log('Stop response:', response);
                removeProcessFromTable(pid);

                // Если после удаления нет больше процессов, прячем кнопку
                const remainingProcesses = document.querySelectorAll('#running-processes-body tr').length;
                if (remainingProcesses === 0) {
                    const resumeBtn = document.getElementById('resume-monitoring');
                    if (resumeBtn) {
                        resumeBtn.style.display = 'none';
                    }
                    sessionStorage.removeItem('monitoringEnabled');
                }
            }).catch((response) => {
                console.log('Stop error:', response);
            }).finally(() => {
                btn.textContent = 'Остановить';
                btn.disabled = false;
            });
        }
    });

    function addProcessToTable(pid, uuid, scriptName) {
        const tbody = document.getElementById('running-processes-body');

        if (document.getElementById(`process-${pid}`)) {
            return;
        }

        const row = document.createElement('tr');
        row.id = `process-${pid}`;
        row.dataset.pid = pid;
        row.dataset.uuid = uuid;
        row.innerHTML = `
            <td>${escapeHtml(scriptName)}
            <td>${pid}
            <td>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: 0%"></div>
                    <span class="progress-text">0%</span>
                </div>
            <td>
                <button class="stop-process" data-pid="${pid}" data-uuid="${uuid}">Остановить</button>
        `;
        tbody.appendChild(row);
    }

    function removeProcessFromTable(pid) {
        const row = document.getElementById(`process-${pid}`);
        if (row) {
            row.remove();
        }
    }

    function startHeartbeatMonitor(pid, uuid) {
        if (!monitoringEnabled) {
            console.log('Monitoring disabled, skipping pid:', pid);
            return;
        }

        if (activeMonitors[pid]) {
            return;
        }

        const makeRequest = async () => {
            if (!activeMonitors[pid] || !monitoringEnabled) {
                return;
            }

            try {
                const response = await BX.ajax.runComponentAction('kim1ne.monitoringphp:script.monitor', 'heartbeat', {
                    mode: "class",
                    data: {
                        pid: pid,
                        uuid: uuid
                    },
                    timeout: 5000
                });

                const data = response.data;

                if (!activeMonitors[pid] || !monitoringEnabled) {
                    return;
                }

                if (data.running) {
                    updateHeartbeat(pid, data.heartbeat);

                    activeMonitors[pid] = setTimeout(() => {
                        makeRequest();
                    }, 2000);
                } else {
                    removeProcessFromTable(pid);
                    delete activeMonitors[pid];

                    // Если после удаления нет больше процессов, прячем кнопку
                    const remainingProcesses = document.querySelectorAll('#running-processes-body tr').length;
                    if (remainingProcesses === 0) {
                        const resumeBtn = document.getElementById('resume-monitoring');
                        if (resumeBtn) {
                            resumeBtn.style.display = 'none';
                        }
                        sessionStorage.removeItem('monitoringEnabled');
                    }
                }
            } catch (error) {
                console.log('Heartbeat error for pid', pid, error);

                if (!activeMonitors[pid] || !monitoringEnabled) {
                    return;
                }

                activeMonitors[pid] = setTimeout(() => {
                    makeRequest();
                }, 2000);
            }
        };

        activeMonitors[pid] = true;
        makeRequest();
    }

    function updateHeartbeat(pid, heartbeat) {
        const row = document.getElementById(`process-${pid}`);
        if (!row) return;

        const percent = parseInt(heartbeat);
        const progressBar = row.querySelector('.progress-bar');
        const progressText = row.querySelector('.progress-text');

        if (progressBar && progressText) {
            progressBar.style.width = percent + '%';
            progressText.textContent = heartbeat;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Инициализация — запускаем мониторинг только если есть процессы
    if (monitoringEnabled && hasRunningProcesses) {
        document.querySelectorAll('#running-processes-body tr').forEach(row => {
            const pid = row.dataset.pid;
            if (pid && !activeMonitors[pid]) {
                startHeartbeatMonitor(pid);
            }
        });
    }
});
