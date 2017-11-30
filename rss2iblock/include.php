<?

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

global $DB;

$db_type = strtolower($DB->type);

CModule::AddAutoloadClasses(
	"rss2iblock",
	array(
		"RssToIBlock" => "lib/RssToIBlock.php",
		"CSource" => "lib/CSource.php",
		"Spsbk\RssToIblock\SourcesTable" => "lib/SourcesTable.php",
	)
);