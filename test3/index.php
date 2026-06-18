<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выгрузка пользователей</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: system-ui, -apple-system, sans-serif;
            background: #f4f6f8;
            color: #1a1a1a;
        }

        .card {
            width: min(480px, calc(100vw - 32px));
            padding: 32px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.5rem;
        }

        p {
            margin: 0 0 24px;
            color: #5c6670;
            line-height: 1.5;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: #2563eb;
            cursor: pointer;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .status {
            margin-top: 20px;
            padding: 14px 16px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 0.95rem;
            line-height: 1.5;
            display: none;
        }

        .status.visible { display: block; }

        .status.error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .progress {
            margin-top: 12px;
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            width: 0;
            background: #2563eb;
            transition: width 0.3s ease;
        }

        a.download-link {
            display: inline-block;
            margin-top: 12px;
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        a.download-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Выгрузка пользователей</h1>
        <p>Асинхронная выгрузка всех пользователей в CSV. Страница не перезагружается.</p>

        <button type="button" id="export-btn">Выгрузить пользователей</button>

        <div class="status" id="status" aria-live="polite"></div>
    </div>

    <script>
        const exportBtn = document.getElementById('export-btn');
        const statusEl = document.getElementById('status');
        let pollTimer = null;

        function showStatus(html, isError = false) {
            statusEl.className = 'status visible' + (isError ? ' error' : '');
            statusEl.innerHTML = html;
        }

        function renderProgress(data) {
            const rows = data.rows ?? 0;
            const total = data.total ?? 0;
            const progress = data.progress ?? 0;

            return `
                <div>Выгрузка выполняется… ${progress}%</div>
                <div>Обработано: ${rows.toLocaleString('ru-RU')} из ${total.toLocaleString('ru-RU')}</div>
                <div class="progress"><div class="progress-bar" style="width: ${progress}%"></div></div>
            `;
        }

        async function pollStatus(jobId) {
            const response = await fetch(`api/status.php?job_id=${encodeURIComponent(jobId)}`);

            if (!response.ok) {
                throw new Error('Не удалось получить статус выгрузки.');
            }

            const data = await response.json();

            if (data.status === 'processing') {
                showStatus(renderProgress(data));
                return;
            }

            clearInterval(pollTimer);
            pollTimer = null;
            exportBtn.disabled = false;

            if (data.status === 'ready') {
                showStatus(`
                    <div>Готово. Выгружено ${data.rows.toLocaleString('ru-RU')} пользователей.</div>
                    <a class="download-link" href="${data.download_url}">Скачать CSV</a>
                `);
                window.location.href = data.download_url;
                return;
            }

            if (data.status === 'error') {
                showStatus(data.error || 'Произошла ошибка при выгрузке.', true);
                return;
            }

            showStatus('Неизвестный статус задачи.', true);
        }

        exportBtn.addEventListener('click', async () => {
            exportBtn.disabled = true;

            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }

            showStatus('Запуск выгрузки…');

            try {
                const response = await fetch('api/start.php', { method: 'POST' });

                if (!response.ok) {
                    throw new Error('Не удалось запустить выгрузку.');
                }

                const data = await response.json();
                showStatus(renderProgress(data));

                pollTimer = setInterval(() => {
                    pollStatus(data.job_id).catch((error) => {
                        clearInterval(pollTimer);
                        pollTimer = null;
                        exportBtn.disabled = false;
                        showStatus(error.message, true);
                    });
                }, 1500);

                await pollStatus(data.job_id);
            } catch (error) {
                exportBtn.disabled = false;
                showStatus(error.message, true);
            }
        });
    </script>
</body>
</html>
