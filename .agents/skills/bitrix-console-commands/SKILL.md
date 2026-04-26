---
name: bitrix-console-commands
description: Покрывает CLI-инструменты Bitrix — php bitrix/bitrix.php, генераторы make:module/make:controller/make:tablet/make:service/make:event/make:component/make:request, команды ядра (orm:annotate, messenger:consume, translate:index), создание собственных команд на Symfony Console и их регистрация в секции console файла .settings.php. Применяется при скаффолдинге нового кода, cron-задачах, написании своих CLI-команд и запуске воркеров очереди. Ключевые термины — bitrix.php, make command, Symfony Console, CLI, command, console namespace.
---

# Консольные команды Bitrix

Все CLI-операции выполняются через `bitrix.php` из папки `/bitrix/`:

```bash
cd /path/to/document_root/bitrix
php bitrix.php list                   # список всех команд
php bitrix.php help <command>         # справка по команде
php bitrix.php <command> [args] -n    # -n = no-interaction
```

Требуется настроенный Composer (обычно `/local/composer.json` + `composer install` → `/local/vendor/`).

## Генераторы кода (`make:*`)

Команды доступны с main **25.900.0**. Все они интерактивные, но поддерживают `-n` и обязательные параметры.

| Команда | Что создаёт |
| --- | --- |
| `make:module vendor.module` | Скелет модуля с `install/`, `lang/`, `.settings.php`, `/lib/` |
| `make:controller <Name> -m vendor.module --actions=crud` | Контроллер в `/lib/Infrastructure/Controller/` |
| `make:controller <Name> -m vendor.module --actions=list,get -C Web` | Контроллер в подпространстве `Web` |
| `make:tablet my_post vendor.module` | ORM-таблет в `/lib/Model/` |
| `make:entity post -m vendor.module --fields=title,description` | Доменная сущность |
| `make:service <Name> -m vendor.module` | Сервис прикладного слоя |
| `make:request <Name> -m vendor.module --fields=title,body` | Request-DTO для валидации параметров |
| `make:event <Name> -m vendor.module` | Класс события `extends Event` |
| `make:eventhandler <Name> --event-module=... --handler-module=...` | Класс обработчика |
| `make:message <Name> -m vendor.module` | Сообщение для очереди (Messenger) |
| `make:messagehandler <Name> --event-module=... --handler-module=...` | Обработчик сообщения |
| `make:agent <Name> -m vendor.module` | Агент + подсказка по `CAgent::AddAgent` |
| `make:component Vendor:Name --module=vendor.module` | Компонент внутри модуля |
| `make:component Vendor:Name --local` | Компонент в `/local/components/` |

**Опции управления размещением:**

- `--prefix=V2` — подпространство после корня модуля, `lib/V2/Infrastructure/Controller/...`.
- `--context=FeatureName` — подпапка внутри слоя, `lib/Infrastructure/Agent/FeatureName/...`.

**Пример неинтерактивного вызова:**

```bash
php bitrix.php make:controller Post -m vendor.blog --actions=crud -n
php bitrix.php make:tablet blog_post vendor.blog -n
php bitrix.php orm:annotate -m vendor.blog
```

## Встроенные служебные команды

- `orm:annotate [-m modules] [--clean]` — генерирует PHPDoc-аннотации ORM-сущностей для автодополнения в IDE.
- `messenger:consume [queues] [--sleep N] [--time-limit N]` — разбор очередей сообщений. Можно запускать по cron или Supervisor.
- `translate:index [--path=...]` — индексация переводов.
- `update:modules [-m modules]`, `update:versions <file.json>`, `update:languages [-l codes]` — обновления.

## Своя консольная команда

1. Унаследуйся от `Symfony\Component\Console\Command\Command`, разложи файлы в `/lib/Cli/Command/<Domain>/`.

    ```php
    namespace Vendor\Module\Cli\Command\Feature;

    use Symfony\Component\Console\Attribute\AsCommand;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;

    #[AsCommand(name: 'feature:rebuild', description: 'Rebuild feature cache')]
    final class RebuildCommand extends Command
    {
        protected function configure(): void
        {
            $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Batch size', 1000);
            $this->addOption('dry-run', null, InputOption::VALUE_NONE);
        }

        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            $limit = (int)$input->getOption('limit');
            $output->writeln("<info>Rebuilding, limit={$limit}</info>");

            try
            {
                // ...
                return Command::SUCCESS;
            }
            catch (\Throwable $e)
            {
                $output->writeln("<error>{$e->getMessage()}</error>");
                return Command::FAILURE;
            }
        }
    }
    ```

2. Зарегистрируй команду в `/local/modules/vendor.module/.settings.php`:

    ```php
    return [
        'console' => [
            'value' => [
                'commands' => [
                    \Vendor\Module\Cli\Command\Feature\RebuildCommand::class,
                ],
            ],
            'readonly' => true,
        ],
    ];
    ```

    > Секция называется **`console`**, ключ — **`commands`**. Старое имя `cli` для новых модулей использовать не нужно.

3. После этого команда появится в `php bitrix.php list` и будет называться по пространству имён: `feature:rebuild`.

## Запуск по cron

```cron
# Каждые 5 минут — обработка очереди
*/5 * * * * php /var/www/site/bitrix/bitrix.php messenger:consume --sleep=1 --time-limit=270 --no-interaction

# Каждый час — чистка кеша фичи
0 * * * *   php /var/www/site/bitrix/bitrix.php feature:rebuild --no-interaction
```

Всегда используй `--no-interaction` в cron.

## Чек-лист хорошей команды

- [ ] Описательное имя (`feature:rebuild`, а не `do-stuff`).
- [ ] Все параметры — через `InputArgument`/`InputOption`, а не через глобальные переменные.
- [ ] Возвращает `Command::SUCCESS`/`Command::FAILURE`/`Command::INVALID`.
- [ ] Логи и прогресс идут в `OutputInterface`, ошибки — на stderr через `$output->getErrorOutput()`.
- [ ] Длительная логика живёт в сервисе, команда — тонкая обёртка.
- [ ] При фатальной ошибке исключение логируется и преобразуется в `FAILURE`.
