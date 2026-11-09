<?php

// Расписание «на 14 дней вперёд» со временем сдвигается: агент раз в сутки дописывает новые дни.
\Bitrix\Main\UpdateSystem\Migration::getInstance()->agent()
	->add([\Bxmax\Booking\Agent\ScheduleAgent::class, 'run'], 86400, isPeriod: false, execDelay: 3600);
