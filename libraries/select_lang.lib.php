<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin Language Loading File
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * tries to find the language to use
 *
 * @uses    $GLOBALS['cfg']['lang']
 * @uses    $GLOBALS['cfg']['DefaultLang']
 * @uses    $GLOBALS['lang_failed_cfg']
 * @uses    $GLOBALS['lang_failed_cookie']
 * @uses    $GLOBALS['lang_failed_request']
 * @uses    $_REQUEST['lang']
 * @uses    $_COOKIE['pma_lang']
 * @uses    $_SERVER['HTTP_ACCEPT_LANGUAGE']
 * @uses    $_SERVER['HTTP_USER_AGENT']
 * @uses    PMA_langSet()
 * @uses    PMA_langDetect()
 * @uses    explode()
 * @return  bool    success if valid lang is found, otherwise false
 */
function PMA_langCheck()
{
    // check forced language
    if (! empty($GLOBALS['cfg']['Lang'])) {
        if (PMA_langSet($GLOBALS['cfg']['Lang'])) {
            return true;
        } else {
            $GLOBALS['lang_failed_cfg'] = $GLOBALS['cfg']['Lang'];
        }
    }

    // Don't use REQUEST in following code as it might be confused by cookies with same name
    // check user requested language (POST)
    if (! empty($_POST['lang'])) {
        if (PMA_langSet($_POST['lang'])) {
            return true;
        } elseif (!is_string($_POST['lang'])) {
            /* Faked request, don't care on localisation */
            $GLOBALS['lang_failed_request'] = 'Yes';
        } else {
            $GLOBALS['lang_failed_request'] = $_POST['lang'];
        }
    }

    // check user requested language (GET)
    if (! empty($_GET['lang'])) {
        if (PMA_langSet($_GET['lang'])) {
            return true;
        } elseif (!is_string($_GET['lang'])) {
            /* Faked request, don't care on localisation */
            $GLOBALS['lang_failed_request'] = 'Yes';
        } else {
            $GLOBALS['lang_failed_request'] = $_GET['lang'];
        }
    }

    // check previous set language
    if (! empty($_COOKIE['pma_lang'])) {
        if (PMA_langSet($_COOKIE['pma_lang'])) {
            return true;
        } elseif (!is_string($_COOKIE['lang'])) {
            /* Faked request, don't care on localisation */
            $GLOBALS['lang_failed_request'] = 'Yes';
        } else {
            $GLOBALS['lang_failed_cookie'] = $_COOKIE['pma_lang'];
        }
    }

    // try to findout user's language by checking its HTTP_ACCEPT_LANGUAGE variable
    if (PMA_getenv('HTTP_ACCEPT_LANGUAGE')) {
        foreach (explode(',', PMA_getenv('HTTP_ACCEPT_LANGUAGE')) as $lang) {
            if (PMA_langDetect($lang, 1)) {
                return true;
            }
        }
    }

    // try to findout user's language by checking its HTTP_USER_AGENT variable
    if (PMA_langDetect(PMA_getenv('HTTP_USER_AGENT'), 2)) {
        return true;
    }

    // Didn't catch any valid lang : we use the default settings
    if (PMA_langSet($GLOBALS['cfg']['DefaultLang'])) {
        return true;
    }

    return false;
}

/**
 * checks given lang and sets it if valid
 * returns true on success, otherwise flase
 *
 * @uses    $GLOBALS['available_languages'] to check $lang
 * @uses    $GLOBALS['lang']                to set it
 * @param   string  $lang   language to set
 * @return  bool    success
 */
function PMA_langSet(&$lang)
{
    if (!is_string($lang) || empty($lang) || empty($GLOBALS['available_languages'][$lang])) {
        return false;
    }
    $GLOBALS['lang'] = $lang;
    return true;
}

/**
 * Analyzes some PHP environment variables to find the most probable language
 * that should be used
 *
 * @param   string   string to analyze
 * @param   integer  type of the PHP environment variable which value is $str
 *
 * @return  bool    true on success, otherwise false
 *
 * @global  $available_languages
 *
 * @access  private
 */
function PMA_langDetect(&$str, $envType)
{
    if (empty($str)) {
        return false;
    }
    if (empty($GLOBALS['available_languages'])) {
        return false;
    }

    foreach ($GLOBALS['available_languages'] as $lang => $value) {
        // $envType =  1 for the 'HTTP_ACCEPT_LANGUAGE' environment variable,
        //             2 for the 'HTTP_USER_AGENT' one
        $expr = $value[0];
        if (strpos($expr, '[-_]') === FALSE) {
            $expr = str_replace('|', '([-_][[:alpha:]]{2,3})?|', $expr);
        }
        if (($envType == 1 && eregi('^(' . $expr . ')(;q=[0-9]\\.[0-9])?$', $str))
            || ($envType == 2 && eregi('(\(|\[|;[[:space:]])(' . $expr . ')(;|\]|\))', $str))) {
            if (PMA_langSet($lang)) {
                return true;
            }
        }
    }

    return false;
} // end of the 'PMA_langDetect()' function

/**
 * @global string  path to the translations directory
 */
$GLOBALS['lang_path'] = './lang/';

/**
 * @global string  interface language
 */
$GLOBALS['lang'] = 'en-iso-8859-1';
/**
 * @global boolean wether loading lang from cfg failed
 */
$GLOBALS['lang_failed_cfg'] = false;
/**
 * @global boolean wether loading lang from cookie failed
 */
$GLOBALS['lang_failed_cookie'] = false;
/**
 * @global boolean wether loading lang from user request failed
 */
$GLOBALS['lang_failed_request'] = false;
/**
 * @global string text direction ltr or rtl
 */
$GLOBALS['text_dir'] = 'ltr';

/**
 * All the supported languages have to be listed in the array below.
 * 1. The key must be the "official" ISO 639 language code and, if required,
 *    the dialect code. It can also contain some informations about the
 *    charset (see the Russian case).
 * 2. The first of the values associated to the key is used in a regular
 *    expression to find some keywords corresponding to the language inside two
 *    environment variables.
 *    These values contains:
 *    - the "official" ISO language code and, if required, the dialect code
 *      also ('bu' for Bulgarian, 'fr([-_][[:alpha:]]{2})?' for all French
 *      dialects, 'zh[-_]tw' for Chinese traditional...), the dialect has to
 *      be specified as first;
 *    - the '|' character (it means 'OR');
 *    - the full language name.
 * 3. The second values associated to the key is the name of the file to load
 *    without the 'inc.php' extension.
 * 4. The third values associated to the key is the language code as defined by
 *    the RFC1766.
 * 5. The fourth value is native name in html entities.
 *
 * Beware that the sorting order (first values associated to keys by
 * alphabetical reverse order in the array) is important: 'zh-tw' (chinese
 * traditional) must be detected before 'zh' (chinese simplified) for
 * example.
 *
 * When there are more than one charset for a language, we put the -utf-8
 * last because we need the default charset to be non-utf-8 to avoid
 * problems on MySQL < 4.1.x if AllowAnywhereRecoding is FALSE.
 *
 * For Russian, we put 1251 first, because MSIE does not accept 866
 * and users would not see anything.
 */
/**
 * @global array supported languages
 */
$GLOBALS['available_languages'] = array(
    'af-iso-8859-1'     => array('af|afrikaans', 'afrikaans-iso-8859-1', 'af', ''),
    'af-utf-8'          => array('af|afrikaans', 'afrikaans-utf-8', 'af', ''),
    'ar-win1256'        => array('ar|arabic', 'arabic-windows-1256', 'ar', '&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;'),
    'ar-utf-8'          => array('ar|arabic', 'arabic-utf-8', 'ar', '&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;'),
    'az-iso-8859-9'     => array('az|azerbaijani', 'azerbaijani-iso-8859-9', 'az', 'Az&#601;rbaycanca'),
    'az-utf-8'          => array('az|azerbaijani', 'azerbaijani-utf-8', 'az', 'Az&#601;rbaycanca'),

    'becyr-win1251'     => array('be|belarusian', 'belarusian_cyrillic-windows-1251', 'be', '&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1072;&#1103;'),
    'becyr-utf-8'       => array('be|belarusian', 'belarusian_cyrillic-utf-8', 'be', '&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1072;&#1103;'),
    'belat-utf-8'       => array('be[-_]lat|belarusian latin', 'belarusian_latin-utf-8', 'be-lat', 'Byelorussian'),
    'bg-win1251'        => array('bg|bulgarian', 'bulgarian-windows-1251', 'bg', '&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;'),
    'bg-koi8-r'         => array('bg|bulgarian', 'bulgarian-koi8-r', 'bg', '&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;'),
    'bg-utf-8'          => array('bg|bulgarian', 'bulgarian-utf-8', 'bg', '&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;'),
    'bs-win1250'        => array('bs|bosnian', 'bosnian-windows-1250', 'bs', 'Bosanski'),
    'bs-utf-8'          => array('bs|bosnian', 'bosnian-utf-8', 'bs', 'Bosanski'),
    'ca-iso-8859-1'     => array('ca|catalan', 'catalan-iso-8859-1', 'ca', 'Catal&agrave;'),
    'ca-utf-8'          => array('ca|catalan', 'catalan-utf-8', 'ca', 'Catal&agrave;'),
    'cs-iso-8859-2'     => array('cs|czech', 'czech-iso-8859-2', 'cs', '&#268;esky'),
    'cs-win1250'        => array('cs|czech', 'czech-windows-1250', 'cs', '&#268;esky'),
    'cs-utf-8'          => array('cs|czech', 'czech-utf-8', 'cs', '&#268;esky'),
    'da-iso-8859-1'     => array('da|danish', 'danish-iso-8859-1', 'da', 'Dansk'),
    'da-utf-8'          => array('da|danish', 'danish-utf-8', 'da', 'Dansk'),
    'de-iso-8859-1'     => array('de|german', 'german-iso-8859-1', 'de', 'Deutsch'),
    'de-iso-8859-15'    => array('de|german', 'german-iso-8859-15', 'de', 'Deutsch'),
    'de-utf-8'          => array('de|german', 'german-utf-8', 'de', 'Deutsch'),
    'el-iso-8859-7'     => array('el|greek',  'greek-iso-8859-7', 'el', '&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;'),
    'el-utf-8'          => array('el|greek',  'greek-utf-8', 'el', '&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;'),
    'en-iso-8859-1'     => array('en|english',  'english-iso-8859-1', 'en', ''),
    'en-iso-8859-15'    => array('en|english',  'english-iso-8859-15', 'en', ''),
    'en-utf-8'          => array('en|english',  'english-utf-8', 'en', ''),
    'es-iso-8859-1'     => array('es|spanish', 'spanish-iso-8859-1', 'es', 'Espa&ntilde;ol'),
    'es-iso-8859-15'    => array('es|spanish', 'spanish-iso-8859-15', 'es', 'Espa&ntilde;ol'),
    'es-utf-8'          => array('es|spanish', 'spanish-utf-8', 'es', 'Espa&ntilde;ol'),
    'et-iso-8859-1'     => array('et|estonian', 'estonian-iso-8859-1', 'et', 'Eesti'),
    'et-utf-8'          => array('et|estonian', 'estonian-utf-8', 'et', 'Eesti'),
    'eu-iso-8859-1'     => array('eu|basque', 'basque-iso-8859-1', 'eu', 'Euskara'),
    'eu-utf-8'          => array('eu|basque', 'basque-utf-8', 'eu', 'Euskara'),
    'fa-win1256'        => array('fa|persian', 'persian-windows-1256', 'fa', '&#1601;&#1575;&#1585;&#1587;&#1740;'),
    'fa-utf-8'          => array('fa|persian', 'persian-utf-8', 'fa', '&#1601;&#1575;&#1585;&#1587;&#1740;'),
    'fi-iso-8859-1'     => array('fi|finnish', 'finnish-iso-8859-1', 'fi', 'Suomi'),
    'fi-iso-8859-15'    => array('fi|finnish', 'finnish-iso-8859-15', 'fi', 'Suomi'),
    'fi-utf-8'          => array('fi|finnish', 'finnish-utf-8', 'fi', 'Suomi'),
    'fr-iso-8859-1'     => array('fr|french', 'french-iso-8859-1', 'fr', 'Fran&ccedil;ais'),
    'fr-iso-8859-15'    => array('fr|french', 'french-iso-8859-15', 'fr', 'Fran&ccedil;ais'),
    'fr-utf-8'          => array('fr|french', 'french-utf-8', 'fr', 'Fran&ccedil;ais'),
    'gl-iso-8859-1'     => array('gl|galician', 'galician-iso-8859-1', 'gl', 'Galego'),
    'gl-utf-8'          => array('gl|galician', 'galician-utf-8', 'gl', 'Galego'),
    'he-iso-8859-8-i'   => array('he|hebrew', 'hebrew-iso-8859-8-i', 'he', '&#1506;&#1489;&#1512;&#1497;&#1514;'),
    'he-utf-8'          => array('he|hebrew', 'hebrew-utf-8', 'he', '&#1506;&#1489;&#1512;&#1497;&#1514;'),
    'hi-utf-8'          => array('hi|hindi', 'hindi-utf-8', 'hi', '&#2361;&#2367;&#2344;&#2381;&#2342;&#2368;'),
    'hr-win1250'        => array('hr|croatian', 'croatian-windows-1250', 'hr', 'Hrvatski'),
    'hr-iso-8859-2'     => array('hr|croatian', 'croatian-iso-8859-2', 'hr', 'Hrvatski'),
    'hr-utf-8'          => array('hr|croatian', 'croatian-utf-8', 'hr', 'Hrvatski'),
    'hu-iso-8859-2'     => array('hu|hungarian', 'hungarian-iso-8859-2', 'hu', 'Magyar'),
    'hu-utf-8'          => array('hu|hungarian', 'hungarian-utf-8', 'hu', 'Magyar'),
    'id-iso-8859-1'     => array('id|indonesian', 'indonesian-iso-8859-1', 'id', 'Bahasa Indonesia'),
    'id-utf-8'          => array('id|indonesian', 'indonesian-utf-8', 'id', 'Bahasa Indonesia'),
    'it-iso-8859-1'     => array('it|italian', 'italian-iso-8859-1', 'it', 'Italiano'),
    'it-iso-8859-15'    => array('it|italian', 'italian-iso-8859-15', 'it', 'Italiano'),
    'it-utf-8'          => array('it|italian', 'italian-utf-8', 'it', 'Italiano'),
    'ja-euc'            => array('ja|japanese', 'japanese-euc', 'ja', '&#26085;&#26412;&#35486;'),
    'ja-sjis'           => array('ja|japanese', 'japanese-sjis', 'ja', '&#26085;&#26412;&#35486;'),
    'ja-utf-8'          => array('ja|japanese', 'japanese-utf-8', 'ja', '&#26085;&#26412;&#35486;'),
    'ko-euc-kr'         => array('ko|korean', 'korean-euc-kr', 'ko', '&#54620;&#44397;&#50612;'),
    'ko-utf-8'          => array('ko|korean', 'korean-utf-8', 'ko', '&#54620;&#44397;&#50612;'),
    'ka-utf-8'          => array('ka|georgian', 'georgian-utf-8', 'ka', '&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;'),
    'lt-win1257'        => array('lt|lithuanian', 'lithuanian-windows-1257', 'lt', 'Lietuvi&#371;'),
    'lt-utf-8'          => array('lt|lithuanian', 'lithuanian-utf-8', 'lt', 'Lietuvi&#371;'),
    'lv-win1257'        => array('lv|latvian', 'latvian-windows-1257', 'lv', 'Latvie&scaron;u'),
    'lv-utf-8'          => array('lv|latvian', 'latvian-utf-8', 'lv', 'Latvie&scaron;u'),
    'mkcyr-win1251'     => array('mk|macedonian', 'macedonian_cyrillic-windows-1251', 'mk', 'Macedonian'),
    'mkcyr-utf-8'       => array('mk|macedonian', 'macedonian_cyrillic-utf-8', 'mk', 'Macedonian'),
    'mn-utf-8'          => array('mn|mongolian', 'mongolian-utf-8', 'mn', '&#1052;&#1086;&#1085;&#1075;&#1086;&#1083;'),
    'ms-iso-8859-1'     => array('ms|malay', 'malay-iso-8859-1', 'ms', 'Bahasa Melayu'),
    'ms-utf-8'          => array('ms|malay', 'malay-utf-8', 'ms', 'Bahasa Melayu'),
    'nl-iso-8859-1'     => array('nl|dutch', 'dutch-iso-8859-1', 'nl', 'Nederlands'),
    'nl-iso-8859-15'    => array('nl|dutch', 'dutch-iso-8859-15', 'nl', 'Nederlands'),
    'nl-utf-8'          => array('nl|dutch', 'dutch-utf-8', 'nl', 'Nederlands'),
    'no-iso-8859-1'     => array('no|norwegian', 'norwegian-iso-8859-1', 'no', 'Norsk'),
    'no-utf-8'          => array('no|norwegian', 'norwegian-utf-8', 'no', 'Norsk'),
    'pl-iso-8859-2'     => array('pl|polish', 'polish-iso-8859-2', 'pl', 'Polski'),
    'pl-win1250'        => array('pl|polish', 'polish-windows-1250', 'pl', 'Polski'),
    'pl-utf-8'          => array('pl|polish', 'polish-utf-8', 'pl', 'Polski'),
    'ptbr-iso-8859-1'   => array('pt[-_]br|brazilian portuguese', 'brazilian_portuguese-iso-8859-1', 'pt-BR', 'Portugu&ecirc;s'),
    'ptbr-utf-8'        => array('pt[-_]br|brazilian portuguese', 'brazilian_portuguese-utf-8', 'pt-BR', 'Portugu&ecirc;s'),
    'pt-iso-8859-1'     => array('pt|portuguese', 'portuguese-iso-8859-1', 'pt', 'Portugu&ecirc;s'),
    'pt-iso-8859-15'    => array('pt|portuguese', 'portuguese-iso-8859-15', 'pt', 'Portugu&ecirc;s'),
    'pt-utf-8'          => array('pt|portuguese', 'portuguese-utf-8', 'pt', 'Portugu&ecirc;s'),
    'ro-iso-8859-1'     => array('ro|romanian', 'romanian-iso-8859-1', 'ro', 'Rom&acirc;n&#259;'),
    'ro-utf-8'          => array('ro|romanian', 'romanian-utf-8', 'ro', 'Rom&acirc;n&#259;'),
    'ru-win1251'        => array('ru|russian', 'russian-windows-1251', 'ru', '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;'),
    'ru-cp-866'         => array('ru|russian', 'russian-cp-866', 'ru', '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;'),
    'ru-koi8-r'         => array('ru|russian', 'russian-koi8-r', 'ru', '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;'),
    'ru-utf-8'          => array('ru|russian', 'russian-utf-8', 'ru', '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;'),
    'si-utf-8'          => array('si|sinhala', 'sinhala-utf-8', 'si', '&#3523;&#3538;&#3458;&#3524;&#3517;'),
    'sk-iso-8859-2'     => array('sk|slovak', 'slovak-iso-8859-2', 'sk', 'Sloven&#269;ina'),
    'sk-win1250'        => array('sk|slovak', 'slovak-windows-1250', 'sk', 'Sloven&#269;ina'),
    'sk-utf-8'          => array('sk|slovak', 'slovak-utf-8', 'sk', 'Sloven&#269;ina'),
    'sl-iso-8859-2'     => array('sl|slovenian', 'slovenian-iso-8859-2', 'sl', 'Sloven&scaron;&#269;ina'),
    'sl-win1250'        => array('sl|slovenian', 'slovenian-windows-1250', 'sl', 'Sloven&scaron;&#269;ina'),
    'sl-utf-8'          => array('sl|slovenian', 'slovenian-utf-8', 'sl', 'Sloven&scaron;&#269;ina'),
    'sq-iso-8859-1'     => array('sq|albanian', 'albanian-iso-8859-1', 'sq', 'Shqip'),
    'sq-utf-8'          => array('sq|albanian', 'albanian-utf-8', 'sq', 'Shqip'),
    'srlat-win1250'     => array('sr[-_]lat|serbian latin', 'serbian_latin-windows-1250', 'sr-lat', 'Srpski'),
    'srlat-utf-8'       => array('sr[-_]lat|serbian latin', 'serbian_latin-utf-8', 'sr-lat', 'Srpski'),
    'srcyr-win1251'     => array('sr|serbian', 'serbian_cyrillic-windows-1251', 'sr', '&#1057;&#1088;&#1087;&#1089;&#1082;&#1080;'),
    'srcyr-utf-8'       => array('sr|serbian', 'serbian_cyrillic-utf-8', 'sr', '&#1057;&#1088;&#1087;&#1089;&#1082;&#1080;'),
    'sv-iso-8859-1'     => array('sv|swedish', 'swedish-iso-8859-1', 'sv', 'Svenska'),
    'sv-utf-8'          => array('sv|swedish', 'swedish-utf-8', 'sv', 'Svenska'),
    'th-tis-620'        => array('th|thai', 'thai-tis-620', 'th', '&#3616;&#3634;&#3625;&#3634;&#3652;&#3607;&#3618;'),
    'th-utf-8'          => array('th|thai', 'thai-utf-8', 'th', '&#3616;&#3634;&#3625;&#3634;&#3652;&#3607;&#3618;'),
    'tr-iso-8859-9'     => array('tr|turkish', 'turkish-iso-8859-9', 'tr', 'T&uuml;rk&ccedil;e'),
    'tr-utf-8'          => array('tr|turkish', 'turkish-utf-8', 'tr', 'T&uuml;rk&ccedil;e'),
    'tt-iso-8859-9'     => array('tt|tatarish', 'tatarish-iso-8859-9', 'tt', 'Tatar&ccedil;a'),
    'tt-utf-8'          => array('tt|tatarish', 'tatarish-utf-8', 'tt', 'Tatar&ccedil;a'),
    'uk-win1251'        => array('uk|ukrainian', 'ukrainian-windows-1251', 'uk', '&#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;'),
    'uk-utf-8'          => array('uk|ukrainian', 'ukrainian-utf-8', 'uk', '&#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;'),
    'zhtw-big5'         => array('zh[-_](tw|hk)|chinese traditional', 'chinese_traditional-big5', 'zh-TW', '&#20013;&#25991;'),
    'zhtw-utf-8'        => array('zh[-_](tw|hk)|chinese traditional', 'chinese_traditional-utf-8', 'zh-TW', '&#20013;&#25991;'),
    'zh-gb2312'         => array('zh|chinese simplified', 'chinese_simplified-gb2312', 'zh', '&#20013;&#25991;'),
    'zh-utf-8'          => array('zh|chinese simplified', 'chinese_simplified-utf-8', 'zh', '&#20013;&#25991;'),
);

// Language filtering support
if (! empty($GLOBALS['cfg']['FilterLanguages'])) {
    $new_lang = array();
    foreach ($GLOBALS['available_languages'] as $key => $val) {
        if (preg_match('@' . $GLOBALS['cfg']['FilterLanguages'] . '@', $key)) {
            $new_lang[$key] = $val;
        }
    }
    if (count($new_lang) > 0) {
        $GLOBALS['available_languages'] = $new_lang;
    }
    unset($key, $val, $new_lang);
}

/**
 * first check for lang dir exists
 */
if (! is_dir($GLOBALS['lang_path'])) {
    // language directory not found
    trigger_error('phpMyAdmin-ERROR: path not found: '
        . $GLOBALS['lang_path'] . ', check your language directory.',
        E_USER_WARNING);
    // and tell the user
    PMA_fatalError('path to languages is invalid: ' . $GLOBALS['lang_path']);
}

/**
 * check for language files
 */
foreach ($GLOBALS['available_languages'] as $each_lang_key => $each_lang) {
    if (! file_exists($GLOBALS['lang_path'] . $each_lang[1] . '.inc.php')) {
        unset($GLOBALS['available_languages'][$each_lang_key]);
    }
}
unset($each_lang_key, $each_lang);

/**
 * @global array MySQL charsets map
 */
$GLOBALS['mysql_charset_map'] = array(
    'big5'         => 'big5',
    'cp-866'       => 'cp866',
    'euc-jp'       => 'ujis',
    'euc-kr'       => 'euckr',
    'gb2312'       => 'gb2312',
    'gbk'          => 'gbk',
    'iso-8859-1'   => 'latin1',
    'iso-8859-2'   => 'latin2',
    'iso-8859-7'   => 'greek',
    'iso-8859-8'   => 'hebrew',
    'iso-8859-8-i' => 'hebrew',
    'iso-8859-9'   => 'latin5',
    'iso-8859-13'  => 'latin7',
    'iso-8859-15'  => 'latin1',
    'koi8-r'       => 'koi8r',
    'shift_jis'    => 'sjis',
    'tis-620'      => 'tis620',
    'utf-8'        => 'utf8',
    'windows-1250' => 'cp1250',
    'windows-1251' => 'cp1251',
    'windows-1252' => 'latin1',
    'windows-1256' => 'cp1256',
    'windows-1257' => 'cp1257',
);

/*
 * Do the work!
 */

/**
 * @global boolean whether charset recoding should be allowed or not
 */
$GLOBALS['allow_recoding'] = false;
if (empty($GLOBALS['convcharset'])) {
    if (isset($_COOKIE['pma_charset'])) {
        $GLOBALS['convcharset'] = $_COOKIE['pma_charset'];
    } else {
        // session.save_path might point to a bad folder, in which case
        // $GLOBALS['cfg'] would not exist
        $convcharset = isset($GLOBALS['cfg']['DefaultCharset']) ? $GLOBALS['cfg']['DefaultCharset'] : 'utf-8';
    }
}

if (! PMA_langCheck()) {
    // fallback language
    $fall_back_lang = 'en-utf-8';
    $line = __LINE__;
    if (! PMA_langSet($fall_back_lang)) {
        trigger_error('phpMyAdmin-ERROR: invalid lang code: '
            . __FILE__ . '#' . $line . ', check hard coded fall back language.',
            E_USER_WARNING);
        // stop execution
        // and tell the user that his choosen language is invalid
        PMA_fatalError('Could not load any language, please check your language settings and folder.');
    }
}

// Defines the associated filename and load the translation
$lang_file = $GLOBALS['lang_path'] . $GLOBALS['available_languages'][$GLOBALS['lang']][1] . '.inc.php';
require_once $lang_file;

// now, that we have loaded the language strings we can send the errors
if ($GLOBALS['lang_failed_cfg']) {
    $GLOBALS['PMA_errors'][] = sprintf($GLOBALS['strLanguageUnknown'], htmlspecialchars($GLOBALS['lang_failed_cfg']));
}
if ($GLOBALS['lang_failed_cookie']) {
    $GLOBALS['PMA_errors'][] = sprintf($GLOBALS['strLanguageUnknown'], htmlspecialchars($GLOBALS['lang_failed_cookie']));
}
if ($GLOBALS['lang_failed_request']) {
    $GLOBALS['PMA_errors'][] = sprintf($GLOBALS['strLanguageUnknown'], htmlspecialchars($GLOBALS['lang_failed_request']));
}

unset($line, $fall_back_lang,
    $GLOBALS['lang_failed_cfg'], $GLOBALS['lang_failed_cookie'], $GLOBALS['ang_failed_request'], $GLOBALS['strLanguageUnknown']);
?>
