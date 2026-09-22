<?php declare(strict_types=1);
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;
if (!$USER->IsAdmin()) return;
$request=Context::getCurrent()->getRequest();
if ($request->isPost() && check_bitrix_sessid() && $request->getPost('Update')) {
 $email=trim((string)$request->getPost('admin_email'));
 if (filter_var($email,FILTER_VALIDATE_EMAIL)) Option::set('bxmax.booking','admin_email',$email);
 else CAdminMessage::ShowMessage('Некорректный e-mail');
}
$mid='bxmax.booking'; $module_id=$mid;
$tabControl=new CAdminTabControl('bxmax_options',[['DIV'=>'main','TAB'=>'Уведомления','TITLE'=>'Получатель заявок'],['DIV'=>'rights','TAB'=>'Доступ','TITLE'=>'Права на модуль']]);
$tabControl->Begin();
?><form method="post" action="<?=htmlspecialcharsbx($APPLICATION->GetCurPageParam())?>"><?=bitrix_sessid_post()?><?php $tabControl->BeginNextTab(); ?>
<tr><td><label for="admin_email">E-mail администратора:</label></td><td><input id="admin_email" type="email" name="admin_email" value="<?=htmlspecialcharsbx(Option::get($mid,'admin_email',''))?>" required></td></tr>
<?php $tabControl->BeginNextTab(); require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/admin/group_rights.php'; $tabControl->Buttons(); ?>
<input type="submit" name="Update" value="Сохранить" class="adm-btn-save"></form><?php $tabControl->End(); ?>
