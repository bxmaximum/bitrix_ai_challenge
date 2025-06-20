# Критерии оценки

## Общие

1.  **Базовая функциональность** 
	- Создан модуль, а не просто классы через init.php
	- Модуль устанавливается, настройки присутствуют
	- Логирование ошибок 
	- Затребованный функционал реализован, работает и не падает
	- *Почему важно:* Без этого модуль бесполезен 
	
2.  **Корректность архитектуры**  
    - Установка через `install/index.php`  
    - Автозагрузка дополнительных пакетов (если используются) через `composer.json` (не ручные require)  
    - *Почему важно:* Без этого модуль нежизнеспособен в enterprise  

3. **Соблюдение требований системного промпта**  
    - Не игнорирует требования
    - Чётко следует инструкциям
    
4. **"Человечность"** 
	- Задаёт вопросы
	- Предлагает свои варианты, если считает, что есть лучше

5. **Качество кода**  
    - PSR-12 / Bitrix Standard  
    - Документация PHPDoc 
    - *Почему важно:* Поддержка и масштабируемость  

6. **Документация**  
	- Создана документация пользователя
	- Создана документация для разработчика
	
## Для текущего задания
	
7. **Асинхронная реализация**  
    - Использование RabbitMQ/SQS/брокера  
    - Graceful Shutdown воркеров  
    - *Почему важно:* Без этого нагрузка "уронит" сайт  

8. **Проверка на "Велосипедизм"**  
    - Использование `php-amqplib`  
    - Проверка на модуль `rabbitmq`  
    - *Почему важно:* Изобретение велосипедов = техдолг  
    
9. **Безопасность**  
    - Шифрование токена Telegram  
    - Валидация параметров  
    - *Почему важно:* Утечка токена = взлом бота  

10. **Корректный выбор ядра**  
    - Старое ядро (CEventLog) для чтения журнала  
    - D7 для настроек/событий  
    - *Почему важно:* Ошибка снизит производительность на 300%  
