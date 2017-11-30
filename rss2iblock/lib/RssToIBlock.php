<?

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rss2iblock/vendor/autoload.php");

use andreskrey\Readability\HTMLParser;

IncludeModuleLangFile(__FILE__);

class RssToIBlock
{

	function RssImport()
	{

		if(!CModule::IncludeModule("iblock"))
			return;

		$cData = new CSource;
		$readability = new HTMLParser();

		$sources = array();

		// Получаем перечень активных источников из БД
		$res = $cData->GetList(array($by=>$order), array("ACTIVE" => 'Y'));

		while($arRes = $res->Fetch()) {

		  $sources[] = array(
		  	"ID" => $arRes['ID'],
		  	"SHORTNAME" => $arRes['SHORTNAME'],
		  	"FULLNAME" => $arRes['FULLNAME'],
		  	"URL" => $arRes['URL'],
		  	"IBLOCK_ID" => $arRes['IBLOCK_ID'],
		  	"SECTION_ID" => $arRes['SECTION_ID'],
		  	"ACTIVE" => $arRes['ACTIVE'],
		  );

		}

		$i = 0;

		// Проходим по каждому источнику
		foreach($sources as $source) {

			$source_shortname = $source["SHORTNAME"];
			$source_fullname = $source["FULLNAME"];
			$source_url = $source["URL"];
			$target_block_id = $source["IBLOCK_ID"];
			$target_section_id = $source["SECTION_ID"];
			$images_folder = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rss2iblock/src/images/";
			$author_field_id = null;

			$target_content_list = array();
			$arFilter = array("IBLOCK_ID" => $target_block_id);

			// Получаем ID пользовательского поля `Автор (RSS)`
			$properties = CIBlockProperty::GetList(
				Array(
					"sort" => "asc",
					"name" => "asc"
				),
				Array(
					"ACTIVE" => "Y",
					"IBLOCK_ID" => $target_block_id,
				)
			);

			while($prop_fields = $properties->GetNext()) {

				if($prop_fields["CODE"] == "RSS2IBLOCK_AUTHOR") {

					$author_field_id = (int)$prop_fields["ID"];

				}

			}

			// Собираем названия элементов целевого инфоблока, уже присутствующих в базе
			$res = CIBlockElement::GetList(array(), $arFilter, array("NAME"));

			while($ar_fields = $res->GetNext()) {

				$target_content_list[] = $ar_fields['NAME'];

			}

			$el = new CIBlockElement;

			echo "Processing $source_shortname ($source_url)...\n";

			// Загружаем XML
			$xml = simplexml_load_file($source_url);

			if($xml === false) {

				echo "Error opening stream.\n";

				continue;

			}

			$root_element_name = $xml->getName();

			// Отрабатываем далее, если загруженный XML -- Atom или RSS 2.0
			if($root_element_name == 'feed' || $root_element_name == 'rss') {

				foreach((($root_element_name == 'rss') ? $xml->channel->item : $xml->entry) as $item) {

					// Приостанавливаем на рандомный промежуток времени выполнение скрипта,
					// чтобы исключить дублирование контента на разных серверах,
					// если используется веб-кластер
					usleep(mt_rand(1, 10), mt_rand(1000, 9999));

					// Если элемент в целевом инфоблоке с таким названием
					// уже существует в базе -- пропускаем его
					if(in_array($item->title, $target_content_list)) continue;

					// Считываем содержимое страницы элемента RSS
					$html = file_get_contents(($root_element_name == 'rss') ? (string)$item->link : (string)$item->id);
					$result = $readability->parse($html);
					$content_text = '';
					
					// Очищаем код от тегов
					if($result["article"]) {

						$xpath = new DOMXPath($result["article"]);
						$nodes = $xpath->query('//*/text()');
						
						foreach($nodes as $node)
							$content_text .= $node->nodeValue;

					}

					if($content_text != "") {

						// Если картинка для текущего источника присутствует --
						// выставляем её, иначе используем заглушку
						if(file_exists($images_folder . $source_shortname . ".png")) {

							$preview_picture = $images_folder . $source_shortname . ".png";

						} else $preview_picture = $images_folder . "untitled.png";

						// Добавляем ссылку на источник
						$content_text .= "\nИсточник: " . (($root_element_name == 'rss') ? (string)$item->link : (string)$item->id);

						$arLoadProductArray = Array(

							'MODIFIED_BY' => 1, 
							'IBLOCK_SECTION_ID' => false,
							'IBLOCK_ID' => $target_block_id,
							'PROPERTY_VALUES' => array($author_field_id => $source_shortname),
							'NAME' => (string)$item->title,
							'ACTIVE' => 'N',
							'ACTIVE_FROM' =>  date("d.m.Y H:i:s", strtotime(($root_element_name == 'rss') ? (string)$item->pubDate : (string)$item->published)),
							'PREVIEW_TEXT' => strip_tags(($root_element_name == 'rss') ? (string)$item->description : (string)$item->summary),
							'PREVIEW_PICTURE' => CFile::MakeFileArray($preview_picture),
							'DETAIL_TEXT' => $content_text,
							'DETAIL_PICTURE' => false,
							'IBLOCK_SECTION_ID' => $target_section_id

						);
						
						if($NEW_ELEMENT = $el->Add($arLoadProductArray)) {

							echo "Success (ID: $NEW_ELEMENT)\n";

							// Заносим заголовок вновь добавленного элемента целевого инфоблока
							// в массив с заголовками существующих в базе элементов
							$target_content_list[] = $arLoadProductArray["NAME"];

						} else {

							echo "Error: " . $el->LAST_ERROR . "\n";

						}

					}

				}

			}

		}

		return;

	}

}