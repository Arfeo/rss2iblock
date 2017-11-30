<?

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rss2iblock/include.php");

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("rss2iblock");
if($POST_RIGHT=="D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$aTabs = array(
	array("DIV" => "edit", "TAB" => GetMessage("RSS2IBLOCK_SOURCE_EDIT_TAB"), "ICON"=>"main_user_edit", "TITLE"=>GetMessage("RSS2IBLOCK_SOURCE_EDIT_TAB")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$ID = intval($ID);
$message = null;
$bVarsFromForm = false;

if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="") && $POST_RIGHT=="W" && check_bitrix_sessid())
{
	$source = new CSource;
	$arFields = Array(
		"SHORTNAME"		=> $SHORTNAME,
		"FULLNAME"		=> $FULLNAME,
		"URL"			=> $URL,
		"IBLOCK_ID"		=> $IBLOCK_ID,
		"SECTION_ID"	=> $SECTION_ID,
		"ACTIVE"		=> ($ACTIVE <> "Y"? "N":"Y"),
	);

	if($ID > 0)
	{
		$res = $source->Update($ID, $arFields);
	}
	else
	{
		$ID = $source->Add($arFields);
		$res = ($ID>0);
	}

	if($res)
	{
		if($apply!="")
			LocalRedirect("/bitrix/admin/rss2iblock_source_edit.php?ID=".$ID."&mess=ok&lang=".LANG."&".$tabControl->ActiveTabParam());
		else
			LocalRedirect("/bitrix/admin/rss2iblock_index.php?lang=".LANG);
	}
	else
	{
		if($e = $APPLICATION->GetException())
			$message = new CAdminMessage(GetMessage("RSS2IBLOCK_SAVE_ERROR"), $e);
		$bVarsFromForm = true;
	}

}

ClearVars();
$str_SHORTNAME = "";
$str_FULLNAME = "";
$str_URL = "";
$str_IBLOCK_ID = "";
$str_SECTION_ID = "";
$str_ACTIVE = "Y";

if($ID>0)
{
	$source = CSource::GetByID($ID);
	if(!$source->ExtractFields("str_"))
		$ID=0;
}

if($bVarsFromForm)
	$DB->InitTableVarsForEdit("rss2iblock_sources", "", "str_");

$APPLICATION->SetTitle(($ID>0? GetMessage("RSS2IBLOCK_EDIT_MODE").$ID : GetMessage("RSS2IBLOCK_ADD_MODE")));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$aMenu = array(
	array(
		"TEXT"=>GetMessage("RSS2IBLOCK_BACK_TO_LIST"),
		"TITLE"=>GetMessage("RSS2IBLOCK_BACK_TO_LIST"),
		"LINK"=>"rss2iblock_index.php?lang=".LANG,
		"ICON"=>"btn_list",
	)
);

$context = new CAdminContextMenu($aMenu);
$context->Show();
?>

<?
if($_REQUEST["mess"] == "ok" && $ID>0)
	CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("RSS2IBLOCK_SOURCE_SAVED"), "TYPE"=>"OK"));

if($message)
	echo $message->Show();
elseif($source->LAST_ERROR!="")
	CAdminMessage::ShowMessage($source->LAST_ERROR);
?>

<form method="POST" Action="<?echo $APPLICATION->GetCurPage()?>" ENCTYPE="multipart/form-data" name="post_form">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
	<tr>
		<td width="40%"><?echo GetMessage("RSS2IBLOCK_SOURCE_SHORTNAME")?> <span style="color:red">*</span></td>
		<td width="60%"><input type="text" name="SHORTNAME" value="<?echo $str_SHORTNAME;?>" size="20" maxlength="20"></td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("RSS2IBLOCK_SOURCE_FULLNAME")?> <span style="color:red">*</span></td>
		<td width="60%"><input type="text" name="FULLNAME" value="<?echo $str_FULLNAME;?>" size="50" maxlength="255"></td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("RSS2IBLOCK_SOURCE_URL")?> <span style="color:red">*</span></td>
		<td width="60%"><input type="text" name="URL" value="<?echo $str_URL;?>" size="50" maxlength="255"></td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("RSS2IBLOCK_SOURCE_IBLOCK_ID")?> <span style="color:red">*</span></td>
		<td width="60%"><input type="text" name="IBLOCK_ID" value="<?echo $str_IBLOCK_ID;?>" size="10" maxlength="10"></td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("RSS2IBLOCK_SOURCE_SECTION_ID")?> <span style="color:red">*</span></td>
		<td width="60%"><input type="text" name="SECTION_ID" value="<?echo $str_SECTION_ID;?>" size="10" maxlength="10"></td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("RSS2IBLOCK_SOURCE_ACTIVE")?></td>
		<td width="60%"><input type="checkbox" name="ACTIVE" value="Y"<?if($str_ACTIVE == "Y") echo " checked"?>></td>
	</tr>
<?
$tabControl->Buttons(
	array(
		"disabled"=>($POST_RIGHT<"W"),
		"back_url"=>"rss2iblock_index.php?lang=".LANG,

	)
);
?>
<?= bitrix_sessid_post(); ?>
<input type="hidden" name="lang" value="<?=LANG?>">
<?if($ID>0 && !$bCopy):?>
	<input type="hidden" name="ID" value="<?=$ID?>">
<?endif;?>

<?
$tabControl->End();
$tabControl->ShowWarnings("post_form", $message);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");