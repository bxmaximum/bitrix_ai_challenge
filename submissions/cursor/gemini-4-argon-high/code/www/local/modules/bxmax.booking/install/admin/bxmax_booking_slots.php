<?php
$localFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/bxmax.booking/admin/bxmax_booking_slots.php';
$bitrixFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bxmax.booking/admin/bxmax_booking_slots.php';
if (is_file($localFile))
{
    require_once $localFile;
}
elseif (is_file($bitrixFile))
{
    require_once $bitrixFile;
}
