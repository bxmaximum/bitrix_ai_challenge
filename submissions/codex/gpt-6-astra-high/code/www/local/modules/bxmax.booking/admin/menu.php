<?php declare(strict_types=1);
if ($GLOBALS['APPLICATION']->GetGroupRight('bxmax.booking')<'R') return false;
return ['parent_menu'=>'global_menu_services','section'=>'bxmax_booking','sort'=>100,'text'=>'Лак&Точка','title'=>'Онлайн-запись','icon'=>'form_menu_icon','items_id'=>'menu_bxmax_booking','items'=>[
 ['text'=>'Заявки','url'=>'/local/admin/bxmax_booking_entries.php?lang=ru','more_url'=>[]],
 ['text'=>'Расписание','url'=>'/local/admin/bxmax_booking_slots.php?lang=ru','more_url'=>[]],
]];
