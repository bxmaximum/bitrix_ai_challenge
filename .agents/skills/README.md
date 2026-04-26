## Skills (Bitrix Framework) — оглавление

Эти файлы — «подгружаемые знания» по ключевым темам Bitrix Framework, конденсированные из актуальной документации в `pages/` и лучших практик.

### Список навыков

| Область | Скилл (путь к SKILL.md) | Краткое описание |
| :--- | :--- | :--- |
| **Проект** | `bitrix-project-structure` | Папки проекта, `/local`, `/bitrix`, автозагрузка, `.settings.php` |
| **Модули** | `bitrix-modules` | Создание модуля, `install/index.php`, регистрация в админке |
| **CLI** | `bitrix-console-commands` | CLI, `bitrix.php`, генераторы `make:*`, cron, своя команда |
| **Контроллеры** | `bitrix-controllers` | AJAX/REST-контроллеры, actions, фильтры, autowire, render |
| **Роутинг** | `bitrix-routing` | Маршруты, группы, префиксы, генерация URL, миграция с urlrewrite |
| **ORM** | `bitrix-orm` | `DataManager`, `getMap`, `query()`, `fetchObject`, события ORM |
| **События** | `bitrix-events` | Новые события (`Event`, `EventResult`) и старые `OnBefore*` |
| **Валидация** | `bitrix-validation` | Валидация объектов/DTO, атрибуты, `ValidationService` |
| **DI** | `bitrix-service-locator` | `ServiceLocator`, DI, регистрация сервисов |
| **Кеширование** | `bitrix-caching` | `Cache`, `ManagedCache`, `TaggedCache`, сброс по таблице |
| **Безопасность** | `bitrix-security` | CSRF, SQLi-риски ORM, XSS, права |
| **Фон/Задачи** | `bitrix-background-jobs` | Агенты, `addBackgroundJob`, очереди `Messenger` |
| **Результаты** | `bitrix-result-and-errors` | `Result`, `Error`, `ErrorCollection`, типизированные результаты |
| **Компоненты** | `bitrix-components` | Компоненты: `class.php`, шаблоны, кеш, `Controllerable`, AJAX |
| **Инфоблоки** | `bitrix-iblocks` | Инфоблоки: `IblockTable`, свойства, SEO, `CIBlock*`-API |
| **HTTP-клиент** | `bitrix-http-client` | `HttpClient`: legacy/PSR-18, async, прокси, SSRF |
| **Логирование** | `bitrix-logger` | PSR-3 логи: `FileLogger`, `LogFormatter`, секция `loggers` |
| **Локализация** | `bitrix-localization` | `Loc::getMessage`, lang-файлы, `Culture`, `BX.message` |
| **Дата/Время** | `bitrix-datetime` | `Bitrix\Main\Type\Date/DateTime`, таймзоны, `toUserTime` |
| **HTTP-слой** | `bitrix-request-response` | `Application`, `Context`, `HttpRequest`, `HttpResponse`, `Json`/`Redirect` |
| **Сессии** | `bitrix-sessions` | Сессии: `getSession`, `getKernelSession`, `getLocalSession`, режимы |
| **Прямой SQL** | `bitrix-database` | `Connection`, `SqlHelper`, `SqlExpression`, транзакции, DDL |
| **Спец. задание** | `bitrix-ai-challenge` | Требования и ограничения модуля «Избранное» (ТЗ) |
