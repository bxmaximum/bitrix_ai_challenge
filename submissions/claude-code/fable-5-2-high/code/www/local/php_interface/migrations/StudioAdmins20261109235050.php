<?php

namespace Sprint\Migration;

use Bitrix\Main\ModuleManager;

class StudioAdmins20261109235050 extends Version
{
    protected $author = "admin";

    protected $description = "Группа администраторов студии с доступом к онлайн-записи";

    protected $moduleVersion = "5.16.1";

    private const GROUP_CODE = 'studio_admins';
    private const MODULE_ID = 'bxmax.booking';

    public function up()
    {
        global $APPLICATION;

        if (!ModuleManager::isModuleInstalled(self::MODULE_ID)) {
            $this->outError('Модуль %s не установлен', self::MODULE_ID);

            return false;
        }

        $helper = $this->getHelperManager();

        $groupId = $helper->UserGroup()->saveGroup(self::GROUP_CODE, [
            'ACTIVE' => 'Y',
            'C_SORT' => 200,
            'NAME' => 'Администраторы студии',
            'DESCRIPTION' => 'Работают с заявками и расписанием онлайн-записи. Доступ к остальным настройкам сайта не даётся.',
        ]);

        // Право «управление» на модуль: список заявок, закрытие и открытие слотов.
        $APPLICATION->SetGroupRight(self::MODULE_ID, $groupId, 'W');

        // Каталог /bitrix/admin по умолчанию закрыт всем, кроме администраторов сайта:
        // без права чтения страницы модуля не откроются даже при праве на модуль.
        // SetFileAccessPermission заменяет набор прав пути целиком, поэтому дописываем к текущим.
        $siteId = $helper->Site()->getDefaultSiteIdIfExists();
        $permissions = $this->getAdminDirPermissions();
        $permissions['G' . $groupId] = 'R';
        $APPLICATION->SetFileAccessPermission([$siteId, '/bitrix/admin'], $permissions);
    }

    public function down()
    {
        global $APPLICATION;

        $helper = $this->getHelperManager();
        $groupId = $helper->UserGroup()->getGroupId(self::GROUP_CODE);
        if (!$groupId) {
            return;
        }

        $siteId = $helper->Site()->getDefaultSiteIdIfExists();
        $APPLICATION->RemoveFileAccessPermission([$siteId, '/bitrix/admin'], ['G' . $groupId]);
        $APPLICATION->DelGroupRight(self::MODULE_ID, [$groupId]);
        $helper->UserGroup()->deleteGroup(self::GROUP_CODE);
    }

    /**
     * Текущие права на каталог /bitrix/admin из /bitrix/.access.php.
     */
    private function getAdminDirPermissions(): array
    {
        $PERM = [];
        $file = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/.access.php';
        if (is_file($file)) {
            include $file;
        }

        // По умолчанию каталог закрыт для всех групп.
        return is_array($PERM['admin'] ?? null) ? $PERM['admin'] : ['*' => 'D'];
    }
}
