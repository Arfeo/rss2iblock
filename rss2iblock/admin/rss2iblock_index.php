<?

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rss2iblock/include.php");

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("rss2iblock");
if($POST_RIGHT == "D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$sTableID = "rss2iblock_sources";
$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

// -----------------------------------------
// Обработка действий над элементами списка
// -----------------------------------------

// Сохранение
if($lAdmin->EditAction() && $POST_RIGHT == "W") {

  foreach($FIELDS as $ID => $arFields) {

    if(!$lAdmin->IsUpdated($ID)) continue;
    
    $DB->StartTransaction();
    $ID = IntVal($ID);
    $cData = new CSource;

    if(($rsData = $cData->GetByID($ID)) && ($arData = $rsData->Fetch())) {

      foreach($arFields as $key=>$value) {

        $arData[$key]=$value;

      }

      if(!$cData->Update($ID, $arData)) {

        $lAdmin->AddGroupError(GetMessage("RSS2IBLOCK_SAVE_ERROR")." ".$cData->LAST_ERROR, $ID);
        $DB->Rollback();

      }

    } else {

      $lAdmin->AddGroupError(GetMessage("RSS2IBLOCK_SAVE_ERROR")." ".GetMessage("RSS2IBLOCK_NO_SOURCE"), $ID);
      $DB->Rollback();

    }

    $DB->Commit();

  }

}

// Одиночные и групповые действия
if(($arID = $lAdmin->GroupAction()) && $POST_RIGHT == "W") {

  // Если выбрано "Для всех элементов"
  if($_REQUEST['action_target'] == 'selected') {

    $cData = new CSource;
    $rsData = $cData->GetList(array($by=>$order), $arFilter);

    while($arRes = $rsData->Fetch()) {

      $arID[] = $arRes['ID'];

    }

  }

  // Проходим по списку элементов
  foreach($arID as $ID) {

    if(strlen($ID) <= 0) continue;
    
    $ID = IntVal($ID);
    
    // Действие для каждого элемента
    switch($_REQUEST['action']) {

      case "delete":

        @set_time_limit(0);
        $DB->StartTransaction();

        if(!CSource::Delete($ID)) {

          $DB->Rollback();
          $lAdmin->AddGroupError(GetMessage("RSS2IBLOCK_DEL_ERROR"), $ID);

        }

        $DB->Commit();

        break;
    
    case "activate":
    case "deactivate":

      $cData = new CSource;

      if(($rsData = $cData->GetByID($ID)) && ($arFields = $rsData->Fetch())) {

        $arFields["ACTIVE"]=($_REQUEST['action']=="activate"?"Y":"N");

        if(!$cData->Update($ID, $arFields)) {

          $lAdmin->AddGroupError(GetMessage("RSS2IBLOCK_SAVE_ERROR").$cData->LAST_ERROR, $ID);

        }

      } else $lAdmin->AddGroupError(GetMessage("RSS2IBLOCK_SAVE_ERROR")." ".GetMessage("RSS2IBLOCK_NO_SOURCE"), $ID);

      break;

    }

  }

}

// -----------------------------------------
// Выборка элементов списка
// -----------------------------------------

$cData = new CSource;
$rsData = $cData->GetList(array($by=>$order), $arFilter);

$rsData = new CAdminResult($rsData, $sTableID);

$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("rub_nav")));

// -----------------------------------------
// Построение списка
// -----------------------------------------

$lAdmin->AddHeaders(

  array(

    array(
      "id" => "ID",
      "content" => "ID",
      "sort" => "id",
      "align" => "right",
      "default" => true,
    ),

    array(
      "id" => "SHORTNAME",
      "content" => GetMessage("RSS2IBLOCK_SOURCE_SHORTNAME"),
      "sort" => "shortname",
      "default" => true,
    ),

    array(
      "id" => "FULLNAME",
      "content" => GetMessage("RSS2IBLOCK_SOURCE_FULLNAME"),
      "sort" => "fullname",
      "default" => true,
    ),

    array(
      "id" => "URL",
      "content" => GetMessage("RSS2IBLOCK_SOURCE_URL"),
      "sort" => "url",
      "default" => true,
    ),

    array(
      "id" => "IBLOCK_ID",
      "content" => GetMessage("RSS2IBLOCK_SOURCE_IBLOCK_ID"),
      "sort" => "iblock_id",
      "default" => true,
    ),

    array(
      "id" => "SECTION_ID",
      "content" => GetMessage("RSS2IBLOCK_SOURCE_SECTION_ID"),
      "sort" => "section_id",
      "default" => true,
    ),

    array(
      "id" => "ACTIVE",
      "content" => GetMessage("RSS2IBLOCK_SOURCE_ACTIVE"),
      "sort" => "act",
      "default" => true,
    ),

  )

);

while($arRes = $rsData->NavNext(true, "f_")):
  
  $row =& $lAdmin->AddRow($f_ID, $arRes); 
  
  $row->AddInputField("SHORTNAME", array("size" => 20));
  $row->AddInputField("FULLNAME", array("size" => 40));
  $row->AddInputField("URL", array("size" => 40));
  $row->AddInputField("IBLOCK_ID", array("size" => 10));
  $row->AddInputField("SECTION_ID", array("size" => 10));
  $row->AddCheckField("ACTIVE");

  // Формируем контекстное меню
  $arActions = Array();

  //// Редактирование элемента ////
  $arActions[] = array(
    "ICON" => "edit",
    "DEFAULT" => true,
    "TEXT" => GetMessage("RSS2IBLOCK_SOURCE_EDIT"),
    "ACTION" => $lAdmin->ActionRedirect("rss2iblock_source_edit.php?ID=".$f_ID),
  );
  
  //// Удаление элемента ////
  if($POST_RIGHT>="W") {

    $arActions[] = array(
      "ICON" => "delete",
      "TEXT" => GetMessage("RSS2IBLOCK_SOURCE_DEL"),
      "ACTION" => "if(confirm('".GetMessage('RSS2IBLOCK_SOURCE_DEL_CONFIRM')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete")
    );

  }

  $arActions[] = array("SEPARATOR"=>true);
  
  $row->AddActions($arActions);

endwhile;

// -----------------------------------------
// Таблица с источниками
// -----------------------------------------

$lAdmin->AddFooter(
  array(
    array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
    array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
  )
);

$lAdmin->AddGroupActionTable(Array(
  "delete"=>GetMessage("MAIN_ADMIN_LIST_DELETE"),
  "activate"=>GetMessage("MAIN_ADMIN_LIST_ACTIVATE"),
  "deactivate"=>GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
));

$aContext = array(
  array(
    "TEXT"=>GetMessage("RSS2IBLOCK_SOURCE_ADD"),
    "LINK"=>"rss2iblock_source_edit.php?lang=".LANG,
    "ICON"=>"btn_new",
  ),
);

$lAdmin->AddAdminContextMenu($aContext);

// -----------------------------------------
// Отрисовка страницы
// -----------------------------------------

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("RSS2IBLOCK_SOURCES_PAGE_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$oFilter = new CAdminFilter(
  $sTableID."_filter",
  array(
    "ID",
    GetMessage("RSS2IBLOCK_SOURCES_SHORTNAME"),
    GetMessage("RSS2IBLOCK_SOURCES_URL"),
  )
);

$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");