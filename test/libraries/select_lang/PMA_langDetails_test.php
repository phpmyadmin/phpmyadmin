<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_langDetails from select_lang.lib.php
 *
 * @package PhpMyAdmin-test
 * @group select_lang.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/select_lang.lib.php';

/**
 * Test for PMA_langDetails from select_lang.lib.php
 *
 * @package PhpMyAdmin-test
 * @group select_lang.lib-tests
 */
class PMA_LangDetails_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for testLangDetails
     *
     * @return array
     */
    function dataProvider()
    {
        return array(
            array('af|afrikaans', 'af', '', 'af'),
            array(
                'ar|arabic',
                'ar',
                '&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;',
                'ar'
            ),
            array('az|azerbaijani', 'az', 'Az&#601;rbaycanca', 'az'),
            array('bn|bangla', 'bn', 'বাংলা', 'bn'),
            array(
                'be|belarusian',
                'be',
                '&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1072;&#1103;',
                'be'
            ),
            array(
                'be[-_]lat|belarusian latin',
                'be-lat',
                'Bie&#0322;aruskaja',
                'be@latin'
            ),
            array(
                'bg|bulgarian',
                'bg',
                '&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;',
                'bg'
            ),
            array('bs|bosnian', 'bs', 'Bosanski', 'bs'),
            array('br|breton', 'br', 'Brezhoneg', 'br'),
            array('ca|catalan', 'ca', 'Catal&agrave;', 'ca'),
            array('cs|czech', 'cs', 'Čeština', 'cs'),
            array('cy|welsh', 'cy', 'Cymraeg', 'cy'),
            array('da|danish', 'da', 'Dansk', 'da'),
            array('de|german', 'de', 'Deutsch', 'de'),
            array(
                'el|greek',
                'el',
                '&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;',
                'el'
            ),
            array('en|english', 'en', '', 'en'),
            array('en[_-]gb|english (United Kingdom)', 'en-gb', '', 'en_GB'),
            array('es|spanish', 'es', 'Espa&ntilde;ol', 'es'),
            array('et|estonian', 'et', 'Eesti', 'et'),
            array('eu|basque', 'eu', 'Euskara', 'eu',),
            array('fa|persian', 'fa', '&#1601;&#1575;&#1585;&#1587;&#1740;', 'fa'),
            array('fi|finnish', 'fi', 'Suomi', 'fi'),
            array('fr|french', 'fr', 'Fran&ccedil;ais', 'fr'),
            array('gl|galician', 'gl', 'Galego', 'gl'),
            array('he|hebrew', 'he', '&#1506;&#1489;&#1512;&#1497;&#1514;', 'he'),
            array(
                'hi|hindi',
                'hi',
                '&#2361;&#2367;&#2344;&#2381;&#2342;&#2368;',
                'hi'
            ),
            array('hr|croatian', 'hr', 'Hrvatski', 'hr'),
            array('hu|hungarian', 'hu', 'Magyar', 'hu'),
            array('id|indonesian', 'id', 'Bahasa Indonesia', 'id'),
            array('it|italian', 'it', 'Italiano', 'it'),
            array('ja|japanese', 'ja', '&#26085;&#26412;&#35486;', 'ja'),
            array('ko|korean', 'ko', '&#54620;&#44397;&#50612;', 'ko'),
            array(
                'ka|georgian',
                'ka',
                '&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;',
                'ka'
            ),
            array('lt|lithuanian', 'lt', 'Lietuvi&#371;', 'lt'),
            array('lv|latvian', 'lv', 'Latvie&scaron;u', 'lv'),
            array('mk|macedonian', 'mk', 'Macedonian', 'mk'),
            array(
                'mn|mongolian',
                'mn',
                '&#1052;&#1086;&#1085;&#1075;&#1086;&#1083;',
                'mn'
            ),
            array('ms|malay', 'ms', 'Bahasa Melayu', 'ms'),
            array('nl|dutch', 'nl', 'Nederlands', 'nl'),
            array('nb|norwegian', 'nb', 'Norsk', 'nb'),
            array('pl|polish', 'pl', 'Polski', 'pl'),
            array(
                'pt[-_]br|brazilian portuguese',
                'pt-BR',
                'Portugu&ecirc;s',
                'pt_BR'
            ),
            array('pt|portuguese', 'pt', 'Portugu&ecirc;s', 'pt'),
            array('ro|romanian', 'ro', 'Rom&acirc;n&#259;', 'ro'),
            array(
                'ru|russian',
                'ru',
                '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;',
                'ru'
            ),
            array('si|sinhala', 'si', '&#3523;&#3538;&#3458;&#3524;&#3517;', 'si'),
            array('sk|slovak', 'sk', 'Sloven&#269;ina', 'sk'),
            array('sl|slovenian', 'sl', 'Sloven&scaron;&#269;ina', 'sl'),
            array('sq|albanian', 'sq', 'Shqip', 'sq'),
            array('sr[-_]lat|serbian latin', 'sr-lat', 'Srpski', 'sr@latin'),
            array(
                'sr|serbian',
                'sr',
                '&#1057;&#1088;&#1087;&#1089;&#1082;&#1080;',
                'sr'
            ),
            array('sv|swedish', 'sv', 'Svenska', 'sv'),
            array('ta|tamil', 'ta', 'தமிழ்', 'ta'),
            array('te|telugu', 'te', 'తెలుగు', 'te'),
            array(
                'th|thai',
                'th',
                '&#3616;&#3634;&#3625;&#3634;&#3652;&#3607;&#3618;',
                'th'
            ),
            array('tr|turkish', 'tr', 'T&uuml;rk&ccedil;e', 'tr'),
            array('tt|tatarish', 'tt', 'Tatar&ccedil;a', 'tt'),
            array('ug|uyghur', 'ug', 'ئۇيغۇرچە', 'ug'),
            array(
                'uk|ukrainian',
                'uk',
                '&#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;',
                'uk'
            ),
            array('ur|urdu', 'ur', 'اُردوُ', 'ur'),
            array('uz[-_]lat|uzbek-latin', 'uz-lat', 'O&lsquo;zbekcha', 'uz@latin'),
            array(
                'uz[-_]cyr|uzbek-cyrillic',
                'uz-cyr',
                '&#1038;&#1079;&#1073;&#1077;&#1082;&#1095;&#1072;',
                'uz'
            ),
            array(
                'zh[-_](tw|hk)|chinese traditional',
                'zh-TW',
                '&#20013;&#25991;',
                'zh_TW'
            ),
            array(
                'zh(?![-_](tw|hk))([-_][[:alpha:]]{2,3})?|chinese simplified',
                'zh',
                '&#20013;&#25991;',
                'zh_CN'
            ),
            array('test_lang|test_lang', 'test_lang', 'test_lang', 'test_lang')
        );
    }

    /**
     * Test for PMA_langDetails
     *
     * @param string $a Language
     * @param string $b Language code
     * @param string $c Language native name in html entities
     * @param string $d Language
     *
     * @return void
     *
     * @dataProvider dataProvider
     */
    function testLangDetails($a, $b, $c,$d)
    {
        $this->assertEquals(array($a, $b, $c), PMA_langDetails($d));
    }
}
