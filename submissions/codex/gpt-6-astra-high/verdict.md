```yaml
visual: 73
backend: 51.5
balanced: 61.3
tier: C
```

## Баллы

| срез | id | модель | автор | итог | max |
| --- | --- | ---: | ---: | ---: | ---: |
| visual | reference | 27 | 29 | 28 | 35 |
| visual | composition | 20 | 20 | 20 | 25 |
| visual | content | 10 | 12 | 11 | 15 |
| visual | frontend-code | 7 | 5 | 6 | 15 |
| visual | solution-doc | 8 | 8 | 8 | 10 |
| backend | architecture-d7 | 20 | 15 | 17.5 | 30 |
| backend | data | 16 | 5 | 10.5 | 20 |
| backend | errors-security | 13 | 10 | 11.5 | 15 |
| backend | readability | 5 | 1 | 3 | 15 |
| backend | code-style | 4 | 1 | 2.5 | 10 |
| backend | solution-doc | 8 | 5 | 6.5 | 10 |

## Комментарий автора

Визуально получилось очень похоже на реф за исключением нескольких мелких недостатков, типа отсутствия маски на телефоне, поехавших кое-где текстов и иконок. JS не использует BX.

В коде же полный треш - форматирование вообще не используется. Читать невозможно. Из того, что смог усмотреть - это кривая установка данных (через модуль миграций, но вообще в отдельном классе модуля), захардкоженные коды инфоблоков, магические переменные.

Таким образом визуал - отлично получился, а весь код - просто ужас.

## Из ревью модели

**[reference] 27/35** — Узнаётся как та же страница: огромный золотой логотип над асимметричным коллажем с меню справа, центрированные заглавные антиквенные заголовки с рукописной золотой строкой поверх, услуги с фото и текстом через одну и крупной золотой ценой, прайс с тонкими линиями, аккордеон с плюсом, «ЧТОБЫ БЫТЬ / БЛИЖЕ + Ждём вас». На мобильной ширине хиро тоже совпадает. Минусы: вместо Playfair Display (он сам свободный) взят Cormorant, растянутый через scaleY; иконки преимуществ собраны из юникод-глифов, а не из линейных иллюстраций; мастера сделаны сеткой в три колонки, которой у референса нет. *(SOLUTION.md:5 «https://laser-studio68.ru/»; 1440px и 360px, хиро и контакты рядом с референсом; www/local/modules/bxmax.booking/assets/landing.css:13 «.hero h1{transform:scaleY(1.5)»)*

**[composition] 20/25** — Иерархия и воздух выдержаны, у фото осмысленные alt. Виджет слотов работает с клавиатуры: roving tabindex, стрелки, Enter, `aria-selected`/`aria-disabled`, у полей есть label. Мешают повторы фото: hero.jpg стоит в хиро, в первой услуге и в галерее, work-1…3 тоже по три раза (views/landing.php:16 «$photos=['hero','work-2','work-1','work-3']»). В подвале круглая кнопка перекрывает ссылку. *(360/768/1440: scrollWidth = clientWidth, горизонтального скролла нет; www/local/modules/bxmax.booking/assets/landing.css:5 «@media(prefers-reduced-motion:reduce)»; 1440, футер: фиксированная `.back-top` (landing.css:1 «position:fixed;bottom:25px;right:25px») закрывает «НАВЕРХ ↑»)*

**[content] 10/15** — Описания услуг конкретные и живые, отзывы различаются поводом, у каждого своя деталь. Но заголовки штампованные («Больше, чем маникюр», «Красота в каждом жесте», «В деталях — вся красота»), а подписи «демонстрационный адрес/телефон/отзывы» прямо на странице убивают продающий эффект. Портреты мастеров — случайные улыбающиеся стоковые лица, одно снято на улице. *(www/local/modules/bxmax.booking/lib/Install/Installer.php:49 «Снятие нашего покрытия уже включено»; www/local/modules/bxmax.booking/views/landing.php:21 «Примеры отзывов для демонстрационной студии»)*

**[frontend-code] 7/15** — JS формы явно ведёт состояния: fetching/posting, отмена запросов, «Повторить» при сбое, перезагрузка слотов на SLOT_TAKEN, опрос раз в 15 с. Всё локально. CSS без переменных, минифицирован руками, а в конце лежит слой заплаток, который перебивает ранние правила (`.hero-right`, отступы `.gallery-grid figure`) и оставляет их мёртвыми. Галерея переключается инлайн-стилями (landing.js:28 «figure.style.display»). *(www/local/modules/bxmax.booking/assets/landing.css:1 (весь CSS в одну строку, 0 `var(--…)`, около 55 разных hex, из них с десяток оттенков золота); landing.css:7 «/* Photo composition and paired gallery follow the reference's proportions. */»; www/local/modules/bxmax.booking/assets/landing.js:100 «controller?.abort(); controller=new AbortController();»)*

**[solution-doc] 8/10** — Разбор проверяемый: таблица секций, шрифты, палитра и 5 приёмов, и всё обещанное на странице есть (подъём при скролле, zoom фото, плюс в аккордеоне, мобильное меню, reduced-motion). Замены честно названы: сертификат стал мастерами, FAQ — отзывами. Неточность: заголовочный шрифт референса Playfair Display свободный, так что «аналог» не нужен; растяжение scaleY не упомянуто. *(SOLUTION.md:29 «**Проверяемые приёмы:**»; SOLUTION.md:27 «Cormorant Garamond как свободный аналог контрастной антиквы заголовков»)*

**[architecture-d7] 20/30** — Слои Controller → Service → Repository настоящие: тонкие контроллеры с атрибутами-фильтрами, Result/Error, DI через .settings.php модуля, почтовое событие, агент, админка на CAdminList/CAdminFilter с menu.php, правами D/R/W и group_rights. Но шаблона сайта нет: главная — модульный «view» с полным HTML после prolog_before, установщик подменяет /index.php, а услуги и мастера рисуются мимо компонентов через ServiceLocator прямо во view. Админка — статический god-class Lists с `echo` HTML и JS, страницы лежат в нестандартном /local/admin, Loc почти не используется. *(www/local/modules/bxmax.booking/lib/Controller/Api/Bookings.php:14 «public function createAction(BookingService $bookings,int $slotId=0,…»; www/local/modules/bxmax.booking/.settings.php:8 «'controllers'=>['value'=>['defaultNamespace'=>…»; www/index.php:4 «require …/local/modules/bxmax.booking/views/landing.php»; www/local/modules/bxmax.booking/lib/Install/Installer.php:33 «copy($module.'/install/home.php',$root.'/index.php')»)*

**[data] 16/20** — Гонка закрыта дважды, блокировкой строки и UNIQUE(SLOT_ID); на стенде 3 параллельных POST на один слот дали 1 success и 2 SLOT_TAKEN. Есть UNIQUE(MASTER_ID, STARTS_AT), генерация идемпотентна (420 слотов), колонки по контракту. Миграция sprint.migration — лишь обёртка над DoInstall: инфоблоки и контент сеет установщик модуля через HelperManager, а не версионные миграции. *(www/local/modules/bxmax.booking/lib/Repository/BookingRepository.php:15 «…WHERE ID = '.(int)$id.' FOR UPDATE'»; www/local/modules/bxmax.booking/lib/Install/Installer.php:18 «createIndex(…,'ux_bxmax_entry_slot',['SLOT_ID'],null,'UNIQUE')»; www/local/php_interface/migrations/Version20260922160000.php:9 «$module->DoInstall();»)*

**[errors-security] 13/15** — Проверил на стенде: MASTER_NOT_FOUND, SLOT_NOT_FOUND, SLOT_TAKEN, VALIDATION, CONSENT_REQUIRED (без consent и с consent=false), invalid_csrf без sessid, invalid_http_method на GET. slots.list отдаёт только id/startsAt/endsAt/status, в лог ПДн не пишутся, вывод экранирован. Слабые места: сбой БД маскируется под VALIDATION, а `slotId=abc` возвращает не контрактный код 100 от биндера вместо VALIDATION. *(www/local/modules/bxmax.booking/lib/Service/BookingService.php:16 «if (!in_array($consent,[true,1,'1','true','Y','on'],true))»; BookingService.php:40 «new Error('Не удалось сохранить запись. Попробуйте ещё раз.','VALIDATION')»; стенд: /local/admin/bxmax_booking_entries.php анониму отдаёт форму авторизации)*

**[readability] 5/15** — Три случайных файла с листа не читаются: отступ в один пробел, по несколько операторов в строке, секции HTML и весь CSS в одну строку. `Lists::prepare` на 50 строк смешивает обработку POST, фильтр, выборку, рендер ячеек и N+1 (`EntryTable::getCount` на каждую строку, Lists.php:53). `CAdminSorting` создан, но порядок жёстко `['ID'=>'DESC']` (Lists.php:46). Имена при этом понятные, мёртвого PHP мало. *(www/local/modules/bxmax.booking/lib/Service/BookingService.php:19 (одно условие валидации длиной ~330 символов); www/local/modules/bxmax.booking/lib/Admin/Lists.php:27 «$r=$isSlots&&in_array($op,['close','open'],true) ? $service->setClosed(…) : (!$isSlots&&$op==='delete' ? … : null);»; www/local/modules/bxmax.booking/views/landing.php:13 (весь хиро в одну строку))*

**[code-style] 4/10** — По AGENTS.md соблюдены strict_types, final, readonly-промоушен, типы параметров и возвратов, атрибуты фильтров, Request вместо суперглобалов, `<?=` в шаблонах, код в /local. PSR-12 нарушен повсеместно, match/enum не используются. Loc нет: все сообщения об ошибках, подписи админки и тексты шаблонов захардкожены по-русски. *(www/local/modules/bxmax.booking/lib/Service/BookingService.php:12 «public function __construct(private readonly BookingRepository $bookings,…»; BookingService.php:23 «return $r->addError(new Error('Слот не найден','SLOT_NOT_FOUND'));»; www/local/modules/bxmax.booking/install/index.php:15 (единственное место с Loc::getMessage))*

**[solution-doc] 8/10** — Гонка, воспроизводимость, админка, дополнительные поля, VALIDATION для weekStart и восстановление главной при удалении названы и совпадают с кодом. Блокировка FOR UPDATE упомянута только в README модуля. Не сказано, что «миграция» лишь вызывает DoInstall и главная живёт вне шаблона сайта. *(SOLUTION.md:47 «Страницы штатной админки размещены в `/local/admin/`, чтобы не писать в ядро. Права модуля D/R/W.»; SOLUTION.md:51 «миграция `Version20260922160000`»; SOLUTION.md:53 «8 конкурентных HTTP-запросов — 1 успех, 7 SLOT_TAKEN»)*

**Итого ревьювера.** Впечатление решил визуал: рядом с laser-studio68.ru лендинг читается как та же страница с другим содержимым, и на десктопе, и на 360px, запись при этом работает честно, с корректными ARIA и клавиатурой. Главный плюс — бэкенд-ядро: тонкие контроллеры с фильтрами, Result/Error, гонка закрыта блокировкой и уникальным индексом (подтверждено параллельными запросами), админка на штатных CAdminList с правами модуля. Главный минус — форма кода: PHP, HTML и CSS сжаты в однострочники без PSR-12, без Loc и без CSS-переменных. Главная при этом собрана мимо шаблона сайта и компонентов: модульный view, которым установщик подменяет /index.php.

## Вывод

Codex с GPT 6 Astra High сдал рабочий результат с первой итерации, без единого замечания. Но результат распадается на две очень разные половины.

Визуал держится на референсе. Рядом с laser-studio68.ru лендинг читается как та же страница с другим содержимым, и на десктопе, и на мобильной ширине: огромный золотой логотип над асимметричным коллажем, антиквенные заголовки с рукописной строкой поверх, услуги с фото через одну, прайс с тонкими линиями, аккордеон с плюсом. Разбор в SOLUTION.md проверяемый, обещанные приёмы на странице есть. Портят картину мелочи: на телефоне нет маски, кое-где поехали тексты и иконки, шрифт заголовков растянут через scaleY, одни и те же фото повторяются, в текстах штампованные заголовки и подписи «демонстрационный адрес». Запись работает с клавиатуры, с корректной ARIA-разметкой, но JS написан без BX, а CSS минифицирован руками, без переменных и со слоем заплаток в конце.

С бэкендом сложнее: устроен он лучше, чем выглядит, но выглядит так, что работать с ним трудно. Слои Controller → Service → Repository настоящие, ошибки идут через Result/Error, коды из контракта воспроизводятся, гонка за слот закрыта блокировкой строки и уникальным индексом, админка собрана на штатных CAdminList с правами модуля. При этом код не отформатирован: отступ в один пробел, по несколько операторов в строке, HTML и CSS в одну строку, нет PSR-12 и Loc. Данные ставятся криво: миграция sprint.migration только вызывает установщик модуля, а инфоблоки создаёт отдельный класс; коды инфоблоков захардкожены, в коде магические переменные. Шаблона сайта нет: главную подменяет модульный view, а админку рисует класс, который сам выводит HTML.

Главный плюс связки — визуал, в котором узнаётся референс. Главный минус — код, который невозможно читать и сопровождать: за читаемость и стиль автор поставил почти ноль, и реальные слои этого не спасают.

## Строка для лидерборда

Лендинг узнаётся как референс с первой итерации, но код в нём не отформатирован и не читается, хотя слои D7 в нём настоящие.
