---
name: bitrix-console-commands
description: Covers Bitrix CLI tools — php bitrix/bitrix.php, generators make:module/make:controller/make:tablet/make:service/make:event/make:component/make:request, kernel commands (orm:annotate, messenger:consume, translate:index), creating custom commands on Symfony Console and registering them in the console section of .settings.php. Applied for scaffolding new code, cron tasks, writing custom CLI commands, and running queue workers. Key terms — bitrix.php, make command, Symfony Console, CLI, command, console namespace.
---

# Bitrix Console Commands

All CLI operations are performed via `bitrix.php` from the `/bitrix/` folder:

```bash
cd /path/to/document_root/bitrix
php bitrix.php list                   # list all commands
php bitrix.php help <command>         # help for a command
php bitrix.php <command> [args] -n    # -n = no-interaction
```

Requires configured Composer (usually `/local/composer.json` + `composer install` → `/local/vendor/`).

Configure Composer path in `.settings.php`:

```php
'composer' => [
    'value' => ['config_path' => '../composer.json'],
    'readonly' => true,
],
```

Keep `composer.json` outside `DOCUMENT_ROOT` when possible.

## Code Generators (`make:*`)

Commands are available from main **25.900.0**. All are interactive but support `-n` and mandatory parameters.

| Command | What It Creates |
| --- | --- |
| `make:module vendor.module` | Module skeleton with `install/`, `lang/`, `.settings.php`, `/lib/` |
| `make:controller <Name> -m vendor.module --actions=crud` | Controller in `/lib/Infrastructure/Controller/` |
| `make:controller <Name> -m vendor.module --actions=list,get -C Web` | Controller in `Web` subspace |
| `make:tablet my_post vendor.module` | ORM tablet in `/lib/Model/` |
| `make:entity post -m vendor.module --fields=title,description` | Domain entity |
| `make:service <Name> -m vendor.module` | Application layer service |
| `make:request <Name> -m vendor.module --fields=title,body` | Request DTO for parameter validation |
| `make:event <Name> -m vendor.module` | Event class `extends Event` |
| `make:eventhandler <Name> --event-module=... --handler-module=...` | Handler class |
| `make:message <Name> -m vendor.module` | Queue message (Messenger) |
| `make:messagehandler <Name> --event-module=... --handler-module=...` | Message handler |
| `make:agent <Name> -m vendor.module` | Agent + hint for `CAgent::AddAgent` |
| `make:component Vendor:Name --module=vendor.module` | Component inside a module |
| `make:component Vendor:Name --local` | Component in `/local/components/` |

**Placement Control Options:**

- `--prefix=V2` — subspace after module root, `lib/V2/Infrastructure/Controller/...`.
- `--context=FeatureName` — subfolder inside the layer, `lib/Infrastructure/Agent/FeatureName/...`.

**Non-interactive Call Example:**

```bash
php bitrix.php make:controller Post -m vendor.blog --actions=crud -n
php bitrix.php make:tablet blog_post vendor.blog -n
php bitrix.php orm:annotate -m vendor.blog
```

## Built-in Utility Commands

- `orm:annotate [-m modules] [--clean]` — generates PHPDoc annotations for ORM entities for IDE autocompletion.
- `messenger:consume [queues] [--sleep N] [--time-limit N]` — message queue processing. Can be run via cron or Supervisor.
- `translate:index [--path=...]` — indexing translations.
- `update:modules [-m modules]`, `update:versions <file.json>`, `update:languages [-l codes]` — updates.

## Custom Console Command

1. Inherit from `Symfony\Component\Console\Command\Command`, place files in `/lib/Cli/Command/<Domain>/`.

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

2. Register the command in `/local/modules/vendor.module/.settings.php`:

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

    > Section is named **`console`**, key is **`commands`**. Old name `cli` should not be used for new modules.

3. After this, the command will appear in `php bitrix.php list` and will be named by its namespace: `feature:rebuild`.

## Running via Cron

```cron
# Every 5 minutes — queue processing
*/5 * * * * php /var/www/site/bitrix/bitrix.php messenger:consume --sleep=1 --time-limit=270 --no-interaction

# Every hour — feature cache cleanup
0 * * * *   php /var/www/site/bitrix/bitrix.php feature:rebuild --no-interaction
```

Always use `--no-interaction` in cron.

## Checklist for a Good Command

- [ ] Descriptive name (`feature:rebuild`, not `do-stuff`).
- [ ] All parameters — via `InputArgument`/`InputOption`, not global variables.
- [ ] Returns `Command::SUCCESS`/`Command::FAILURE`/`Command::INVALID`.
- [ ] Logs and progress go to `OutputInterface`, errors — to stderr via `$output->getErrorOutput()`.
- [ ] Long logic lives in a service, command is a thin wrapper.
- [ ] In case of a fatal error, exception is logged and converted to `FAILURE`.
