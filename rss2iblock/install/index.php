<?

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

use Spsbk\RssToIblock\SourcesTable;

Loc::loadMessages(__FILE__);

class rss2iblock extends CModule {

    public function __construct() {

        $arModuleVersion = array();
        
        include __DIR__ . '/version.php';

        if(is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }
        
        $this->MODULE_ID = 'rss2iblock';
        $this->MODULE_NAME = Loc::getMessage('RSS2IBLOCK_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('RSS2IBLOCK_MODULE_DESCRIPTION');
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->MODULE_ROOT_DIR = dirname(__DIR__);
        $this->PARTNER_NAME = Loc::getMessage('RSS2IBLOCK_MODULE_PARTNER_NAME');
        $this->PARTNER_URI = 'http://spsbk.ru';

    }

    public function doInstall() {

        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();

        // Добавляем агента
        CAgent::AddAgent(
            "RssToIBlock::RssImport();",
            "rss2iblock",                                       // идентификатор модуля
            "N",                                                // агент не критичен к кол-ву запусков
            900,                                                // интервал запуска в секундах
            "",                                                 // дата первой проверки - текущее
            "Y",                                                // агент активен
            date("d.m.Y H:") . (date("i") + 1) . date(":s"),    // дата первого запуска - текущее + 1 мин
            30
        );

        if($this->installFiles()) {

            return true;

        }

    }

    public function doUninstall() {

        // Удаляем агенты модуля
        CAgent::RemoveModuleAgents("rss2iblock");

        $this->uninstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);

        if($this->uninstallFiles()) {

            return true;

        }

    }

    public function installDB() {

        if(Loader::includeModule($this->MODULE_ID)) {

            SourcesTable::getEntity()->createDbTable();

        }

    }

    public function uninstallDB() {

        if(Loader::includeModule($this->MODULE_ID)) {

            $connection = Application::getInstance()->getConnection();
            $connection->dropTable(SourcesTable::getTableName());

        }

    }

    public function installFiles() {

        copy($this->MODULE_ROOT_DIR . "/install/admin/rss2iblock_index.php", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin/rss2iblock_index.php");
        copy($this->MODULE_ROOT_DIR . "/install/admin/rss2iblock_source_edit.php", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin/rss2iblock_source_edit.php");

        return true;

    }

    public function uninstallFiles() {

        unlink($_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin/rss2iblock_index.php");
        unlink($_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin/rss2iblock_source_edit.php");

        return true;

    }

}
