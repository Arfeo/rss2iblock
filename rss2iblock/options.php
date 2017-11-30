<?

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'rss2iblock');

if(!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}

$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();

Loc::loadMessages($context->getServer()->getDocumentRoot() . "/bitrix/modules/main/options.php");
Loc::loadMessages(__FILE__);

$tabControl = new CAdminTabControl("tabControl", array(

    array(
        "DIV" => "rss2iblock_prefs",
        "TAB" => Loc::getMessage("MAIN_TAB_SET"),
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET"),
    ),

));

//// Сохранение настроек ////
if(!empty($save) && $request->isPost() && check_bitrix_sessid()) {

    if($request->getPost('period')) {

        Option::set(ADMIN_MODULE_NAME, "period", $request->getPost('period'));

        CAdminMessage::showMessage(array(

            "MESSAGE" => Loc::getMessage("PREFERENCES_OPTIONS_SAVED"),
            "TYPE" => "OK",

        ));

        // Удаляем агента
        CAgent::RemoveAgent("RssToIBlock::RssImport();", "rss2iblock");

        // Добавляем агента с новой периодичностью выполнения
        CAgent::AddAgent(
            "RssToIBlock::RssImport();",
            "rss2iblock",                                     // идентификатор модуля
            "N",                                              // агент не критичен к кол-ву запусков
            (int)$request->getPost('period'),                 // интервал запуска в секундах
            "",                                               // дата первой проверки - текущее
            "Y",                                              // агент активен
            date("d.m.Y H:") . (date("i") + 1) . date(":s"),  // дата первого запуска - текущее + 1 мин
            30
        );

    } else {

        CAdminMessage::showMessage(Loc::getMessage("PREFERENCES_INVALID_VALUE"));

    }

}

$tabControl->begin();
?>

<form method="post" action="<?=sprintf('%s?mid=%s&lang=%s', $request->getRequestedPage(), urlencode($mid), LANGUAGE_ID)?>">
    <?
      echo bitrix_sessid_post();
      $tabControl->beginNextTab();
    ?>
    <tr>
        <td width="40%">
            <label for="period">Периодичность опроса (секунд): <span style="color:red">*</span></label>
        <td width="60%">
            <input type="text"
                   size="5"
                   maxlength="10"
                   name="period"
                   value="<?= Option::get(ADMIN_MODULE_NAME, "period", 900) ?>"
                   />
        </td>
    </tr>
    <?php
      $tabControl->buttons();
    ?>
    <input type="submit"
           name="save"
           value="<?=Loc::getMessage("MAIN_SAVE") ?>"
           title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE") ?>"
           class="adm-btn-save"
           />
    <?
      $tabControl->end();
    ?>
</form>