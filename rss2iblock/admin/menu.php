<?

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if($APPLICATION->GetGroupRight("rss2iblock") > "D") {

	if(!CModule::IncludeModule('rss2iblock'))
		return false;

	$menu = array(

	    array(

	        'parent_menu' => 'global_menu_services',
	        'sort' => 400,
	        'text' => Loc::getMessage('RSS2IBLOCK_MENU_TITLE'),
	        'title' => Loc::getMessage('RSS2IBLOCK_MENU_TITLE'),
	        'items_id' => 'menu_references',
	        'items' => array(

	            array(

	                'text' => Loc::getMessage('RSS2IBLOCK_PREFS_MENU_TITLE'),
	                'url' => '/bitrix/admin/rss2iblock_index.php?lang=' . LANGUAGE_ID,
	                'more_url' => array('/bitrix/admin/rss2iblock_index.php?lang=' . LANGUAGE_ID),
	                'title' => Loc::getMessage('RSS2IBLOCK_PREFS_MENU_TITLE'),

	            ),

	        ),

	    ),

	);

	return $menu;

}

return false;