<?php

$bxmaxBookingAdminFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/bxmax.booking/admin/entries.php';
if (!is_file($bxmaxBookingAdminFile))
{
    $bxmaxBookingAdminFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bxmax.booking/admin/entries.php';
}
require $bxmaxBookingAdminFile;
