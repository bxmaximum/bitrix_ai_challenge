<?php

use Bitrix\Main\UpdateSystem\Migration;
use Bxmax\Booking\Agent\ScheduleAgent;

// Раз в сутки достраивает расписание на 14 дней вперёд.
Migration::getInstance()->agent()->add([ScheduleAgent::class, 'run'], 86400, isPeriod: false, execDelay: 3600);
