<?php

$bxmaxBookingAdminFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/bxmax.booking/admin/slots.php';
if (!is_file($bxmaxBookingAdminFile))
{
    $bxmaxBookingAdminFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bxmax.booking/admin/slots.php';
}
require $bxmaxBookingAdminFile;
