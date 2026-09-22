<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ai.bitrix — установлен</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f4f5f7;
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
            max-width: 480px;
            width: 100%;
            padding: 32px 28px;
        }
        .badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 12px;
        }
        h1 {
            font-size: 24px;
            line-height: 1.25;
            margin-bottom: 12px;
        }
        p {
            font-size: 15px;
            line-height: 1.55;
            color: #4b5563;
            margin-bottom: 12px;
        }
        .host {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            color: #111827;
        }
        .hint {
            font-size: 14px;
            margin-top: 8px;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 13px;
        }
        .actions {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
        .primary {
            background: #2563eb;
            color: #fff;
        }
        .secondary {
            background: #f3f4f6;
            color: #374151;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="badge">Omut</div>
        <h1>1С-Битрикс установлен</h1>
        <p>Сайт <span class="host">ai.bitrix</span> готов к работе. Ядро и админка на месте.</p>
        <p>Публичная часть ещё не развёрнута — выберите решение (магазин, корпоративный сайт и т.п.) или разверните свой проект.</p>
        <p class="hint">Логин администратора: <code>admin</code></p>
        <div class="actions">
            <a class="primary" href="/bitrix/admin/wizard_list.php?lang=ru">Перейти к выбору решения</a>
            <a class="secondary" href="/bitrix/admin/">Админка</a>
            <a class="secondary" href="/bitrix/admin/site_edit.php">Настроить сайт</a>
        </div>
        <p class="footer">Заглушка исчезнет после установки решения или замены index.php.</p>
    </main>
</body>
</html>
