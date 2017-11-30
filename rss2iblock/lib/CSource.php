<?

IncludeModuleLangFile(__FILE__);

class CSource
{

	var $LAST_ERROR = "";

	// ----------------------------------------------
	// Получение списка источников
	// ----------------------------------------------
	public static function GetList($aSort = array(), $aFilter = array())
	{

		global $DB;

		$arFilter = array();

		foreach($aFilter as $key=>$val)
		{

			if(strlen($val) <= 0)
				continue;

			$key = strtoupper($key);

			switch($key)
			{

				case "ID":
				case "IBLOCK_ID":
				case "SECTION_ID":
				case "ACTIVE":

					$arFilter[] = "R.".$key." = '".$DB->ForSql($val)."'";

					break;

				case "SHORTNAME":
				case "FULLNAME":
				case "URL":

					$arFilter[] = "R.NAME like '%".$DB->ForSql($val)."%'";

					break;

			}

		}

		$arOrder = array();

		foreach($aSort as $key=>$val)
		{

			$ord = (strtoupper($val) <> "ASC"? "DESC": "ASC");
			$key = strtoupper($key);

			switch($key)
			{

				case "ID":
				case "SHORTNAME":
				case "FULLNAME":
				case "URL":
				case "IBLOCK_ID":
				case "SECTION_ID":

					$arOrder[] = "R.".$key." ".$ord;

					break;

				case "ACTIVE":

					$arOrder[] = "R.ACTIVE ".$ord;

					break;

			}

		}

		if(count($arOrder) == 0)
			$arOrder[] = "R.ID DESC";
		$sOrder = "\nORDER BY ".implode(", ",$arOrder);

		if(count($arFilter) == 0)
			$sFilter = "";
		else
			$sFilter = "\nWHERE ".implode("\nAND ", $arFilter);

		$strSql = "
			SELECT
				R.ID
				,R.SHORTNAME
				,R.FULLNAME
				,R.URL
				,R.IBLOCK_ID
				,R.SECTION_ID
				,R.ACTIVE
			FROM
				rss2iblock_sources R
			".$sFilter.$sOrder;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

	}

	// ----------------------------------------------
	// Получение информации об источнике по его ID
	// ----------------------------------------------
	public static function GetByID($ID)
	{

		global $DB;

		$ID = intval($ID);

		$strSql = "
			SELECT
				R.*
			FROM rss2iblock_sources R
			WHERE R.ID = ".$ID."
		";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

	}

	// ----------------------------------------------
	// Удаление источника
	// ----------------------------------------------
	public static function Delete($ID)
	{

		global $DB;

		$ID = intval($ID);

		$DB->StartTransaction();

		$res = $DB->Query("DELETE FROM rss2iblock_sources WHERE ID=".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if($res)
			$DB->Commit();
		else
			$DB->Rollback();

		return $res;

	}

	// ----------------------------------------------
	// Проверка полей перед записью в БД
	// ----------------------------------------------
	function CheckFields($arFields)
	{

		global $DB;

		$this->LAST_ERROR = "";
		$aMsg = array();

		if(strlen($arFields["SHORTNAME"]) == 0)
			$aMsg[] = array(
				"id"	=> "SHORTNAME",
				"text"	=> GetMessage("RSS2IBLOCK_SHORTNAME_ERR")
			);

		if(strlen($arFields["FULLNAME"]) == 0)
			$aMsg[] = array(
				"id"	=> "FULLNAME",
				"text"	=> GetMessage("RSS2IBLOCK_FULLNAME_ERR")
			);

		if(strlen($arFields["URL"]) == 0)
			$aMsg[] = array(
				"id"	=> "URL",
				"text"	=> GetMessage("RSS2IBLOCK_URL_ERR")
			);

		if(strlen($arFields["IBLOCK_ID"]) == 0)
			$aMsg[] = array(
				"id"	=> "IBLOCK_ID",
				"text"	=> GetMessage("RSS2IBLOCK_IBLOCK_ID_ERR")
			);

		if(strlen($arFields["SECTION_ID"]) == 0)
			$aMsg[] = array(
				"id"	=> "SECTION_ID",
				"text"	=> GetMessage("RSS2IBLOCK_SECTION_ID_ERR")
			);

		if(!empty($aMsg))
		{

			$e = new CAdminException($aMsg);

			$GLOBALS["APPLICATION"]->ThrowException($e);
			$this->LAST_ERROR = $e->GetString();

			return false;
		}

		return true;

	}

	// ----------------------------------------------
	// Добавление источника
	// ----------------------------------------------
	function Add($arFields)
	{

		global $DB;

		if(!$this->CheckFields($arFields))
			return false;

		$ID = $DB->Add("rss2iblock_sources", $arFields);

		// Добавляем пользовательское поле `Автор RSS` в указанный инфоблок
		if($ID > 0) {

			$this->addUserField($arFields);

		}

		return $ID;

	}

	// ----------------------------------------------
	// Редактирование источника
	// ----------------------------------------------
	function Update($ID, $arFields)
	{

		global $DB;

		$ID = intval($ID);

		if(!$this->CheckFields($arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("rss2iblock_sources", $arFields);

		if($strUpdate != "")
		{

			$strSql = "UPDATE rss2iblock_sources SET ".$strUpdate." WHERE ID=".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			// Добавляем пользовательское поле `Автор RSS` в указанный инфоблок
			if($ID > 0) {

				$this->addUserField($arFields);

			}

		}

		return true;

	}

	// ----------------------------------------------
	// Добавление пользовательского поля `Автор RSS`
	// ----------------------------------------------
	function addUserField($arFields)
	{

		if(!CModule::IncludeModule("iblock"))
			return;

		$IBLOCK_ID = $arFields['IBLOCK_ID'];
		$is_field = false;

		$properties = CIBlockProperty::GetList(
			Array(
				"sort" => "asc",
				"name" => "asc"
			),
			Array(
				"ACTIVE" => "Y",
				"IBLOCK_ID" => $IBLOCK_ID
			)
		);

		while($prop_fields = $properties->GetNext())
		{

			if($prop_fields["CODE"] == "RSS2IBLOCK_AUTHOR") {

				$is_field = true;

				break;

			}

		}

		if(!$is_field) {

			// Если пользовательского поля `Автор (RSS)` не существует --
			// создаём его
	 		$aUserFields = Array(
		        "NAME" => "Автор (RSS)",
		        "ACTIVE" => "Y",
		        "SORT" => "500",
		        "CODE" => "RSS2IBLOCK_AUTHOR",
		        "PROPERTY_TYPE" => "S",
		        "USER_TYPE" => "TEXT", 
		        "IBLOCK_ID" => $IBLOCK_ID,
	        );

	        $ibp = new CIBlockProperty;
			
			return $ibp->Add($aUserFields);

		}

	}

}