<?php

use Bxmax\Booking\Agent\ExtendScheduleAgent;

// раз в сутки поддерживает горизонт расписания в 14 дней
\Bitrix\Main\UpdateSystem\Migration::getInstance()
    ->agent()
    ->add([ExtendScheduleAgent::class, 'run'], 86400, isPeriod: false, execDelay: 3600);
