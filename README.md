# Битрикс AI Челлендж: Сравнение моделей в разработке на 1С-Битрикс

## О проекте

Репозиторий для исследования и сравнения различных AI моделей при решении задач разработки на 1С-Битрикс. Каждая модель получает одинаковое техническое задание и оценивается по единым критериям.

## Цель исследования

Определить сильные и слабые стороны современных AI моделей при:
- Разработке модулей для 1С-Битрикс
- Соблюдении архитектурных принципов
- Работе с enterprise-требованиями
- Понимании специфики CMS

## Методология

### Базовый промпт
Все модели получают единый системный промпт с базовыми знаниями о разработке на 1С-Битрикс, включающий:
- Архитектурные принципы и лучшие практики
- Стандарты кодирования PSR-12/Bitrix Standard
- Работу с D7 API и старым ядром
- Enterprise-требования и безопасность

**[Текст базового промпта →](bitrix_prompt.md)**

### Тестовое задание
Разработка модуля для автоматической отправки критических событий из журнала Битрикс в Telegram с требованиями:
- Высокая производительность (100+ событий/мин)
- Асинхронная обработка через очереди
- Безопасность и шифрование
- Enterprise-архитектура

**[Полное техническое задание →](bitrix_ai_challenge.md)**

### Критерии оценки (10-балльная шкала по каждому пункту)
1. **Базовая функциональность** - создание модуля, установка, логирование, работоспособность
2. **Корректность архитектуры** - install/index.php, composer.json, автозагрузка
3. **Соблюдение требований** - следование системному промпту и инструкциям
4. **"Человечность"** - задает вопросы, предлагает варианты
5. **Качество кода** - PSR-12/Bitrix Standard, PHPDoc документация
6. **Документация** - для пользователей и разработчиков
7. **Асинхронная реализация** - RabbitMQ/SQS/брокеры, Graceful Shutdown
8. **Проверка на "Велосипедизм"** - использование php-amqplib, проверка модуля rabbitmq
9. **Безопасность** - шифрование токена Telegram, валидация параметров
10. **Корректный выбор ядра** - старое ядро (CEventLog) для журнала, D7 для настроек


**[Подробные критерии оценки →](criteria.md)**

## Структура репозитория

```
/models/
  ├── claude-3.5-sonnet/     # Результаты выполнения задания Claude 3.5 Sonnet
  ├── gpt-4/                 # Результаты выполнения задания GPT-4
  ├── gemini-pro/            # Результаты выполнения задания Gemini Pro
  └── ...
/results/
  ├── comparison-table.md    # Сводная таблица результатов
  └── detailed-analysis/     # Детальный анализ по моделям
      ├── claude-3.5-sonnet.md
      ├── gpt-4.md
      ├── gemini-pro.md
      └── ...
```

## Обобщённые результаты на 11.06.2025

| Место | Модель | Тип | Общая оценка |
|-------|--------|-----|--------------|
| 🥇 1 | [Anthropic: Claude Sonnet 4 Thinking](results/detailed-analysis/claude-sonnet-4-thinking.md) | Рассуждающая | **75/100** |
| 🥈 2 | [Google: Gemini 2.5 Pro Preview 06-05](results/detailed-analysis/gemini-2.5-pro-preview-0605.md) | Рассуждающая | **66/100** |
| 🥉 3 | [OpenAI: GPT-4.1](results/detailed-analysis/gpt-4.1-analysis.md) | Генеративная | **58/100** |

**[📊 Сводная таблица результатов →](results/comparison-table.md)** детальное сравнение по всем критериям

## Как участвовать

1. Предложите новую модель для тестирования
2. Дополните критерии оценки
3. Улучшите базовый промпт для Битрикс
4. Поделитесь результатами своих экспериментов

## Уведомления

Следите за обновлениями в [Telegram канале](https://t.me/bxmaximum) - краткие анонсы о новых исследованиях и результатах.

## Контакты

**Кирилл Новожилов**  
Telegram: [@kirk_novozhilov](https://t.me/kirk_novozhilov)  
Email: novozhilov.kirk@gmail.com

## Лицензия

MIT License - результаты исследований доступны для всех разработчиков Битрикс-сообщества. 