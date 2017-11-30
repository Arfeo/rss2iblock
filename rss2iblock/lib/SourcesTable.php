<?

namespace Spsbk\RssToIblock;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SourcesTable extends DataManager {

    public static function getTableName() {

        return 'rss2iblock_sources';

    }

    public static function getMap() {

        return array(

            new IntegerField('ID', array(
                'autocomplete' => true,
                'primary' => true,
                'title' => Loc::getMessage('RSS2IBLOCK_ID'),
            )),

            new StringField('SHORTNAME', array(
                'required' => true,
                'title' => Loc::getMessage('RSS2IBLOCK_SHORTNAME'),
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 20),
                    );
                },
            )),

            new StringField('FULLNAME', array(
                'required' => true,
                'title' => Loc::getMessage('RSS2IBLOCK_FULLNAME'),
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),

            new StringField('URL', array(
                'required' => true,
                'title' => Loc::getMessage('RSS2IBLOCK_URL'),
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),

            new IntegerField('IBLOCK_ID', array(
                'required' => true,
                'title' => Loc::getMessage('RSS2IBLOCK_IBLOCK_ID'),
            )),

            new IntegerField('SECTION_ID', array(
                'required' => true,
                'title' => Loc::getMessage('RSS2IBLOCK_SECTION_ID'),
            )),

            new StringField('ACTIVE', array(
                'required' => true,
                'title' => Loc::getMessage('RSS2IBLOCK_ACTIVE'),
            )),

        );

    }

}
