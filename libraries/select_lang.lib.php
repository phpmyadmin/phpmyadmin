<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin Language Loading File
 *
 * @version $Id$
 */

/**
 * tries to find the language to use
 *
 * @uses    $GLOBALS['cfg']['lang']
 * @uses    $GLOBALS['cfg']['DefaultLang']
 * @uses    $GLOBALS['lang_failed_cfg']
 * @uses    $GLOBALS['lang_failed_cookie']
 * @uses    $GLOBALS['lang_failed_request']
 * @uses    $GLOBALS['convcharset'] to set it if not set
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
$GLOBALS['lang'] = 'en-utf-8';
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
 */
/**
 * @global array supported languages
 */
$GLOBALS['available_languages'] = array(
    'af-utf-8'          => array('af|afrikaans', 'afrikaans-utf-8', 'af', ''),
    'ar-utf-8'          => array('ar|arabic', 'arabic-utf-8', 'ar', '&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;'),
    'az-utf-8'          => array('az|azerbaijani', 'azerbaijani-utf-8', 'az', 'Az&#601;rbaycanca'),

    'becyr-utf-8'       => array('be|belarusian', 'belarusian_cyrillic-utf-8', 'be', '&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1072;&#1103;'),
    'belat-utf-8'       => array('be[-_]lat|belarusian latin', 'belarusian_latin-utf-8', 'be-lat', 'Byelorussian'),
    'bg-utf-8'          => array('bg|bulgarian', 'bulgarian-utf-8', 'bg', '&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;'),
    'bs-utf-8'          => array('bs|bosnian', 'bosnian-utf-8', 'bs', 'Bosanski'),
    'ca-utf-8'          => array('ca|catalan', 'catalan-utf-8', 'ca', 'Catal&agrave;'),
    'cs-utf-8'          => array('cs|czech', 'czech-utf-8', 'cs', '&#268;esky'),
    'da-utf-8'          => array('da|danish', 'danish-utf-8', 'da', 'Dansk'),
    'de-utf-8'          => array('de|german', 'german-utf-8', 'de', 'Deutsch'),
    'el-utf-8'          => array('el|greek',  'greek-utf-8', 'el', '&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;'),
    'en-utf-8'          => array('en|english',  'english-utf-8', 'en', ''),
    'es-utf-8'          => array('es|spanish', 'spanish-utf-8', 'es', 'Espa&ntilde;ol'),
    'et-utf-8'          => array('et|estonian', 'estonian-utf-8', 'et', 'Eesti'),
    'eu-utf-8'          => array('eu|basque', 'basque-utf-8', 'eu', 'Euskara'),
    'fa-utf-8'          => array('fa|persian', 'persian-utf-8', 'fa', '&#1601;&#1575;&#1585;&#1587;&#1740;'),
    'fi-utf-8'          => array('fi|finnish', 'finnish-utf-8', 'fi', 'Suomi'),
    'fr-utf-8'          => array('fr|french', 'french-utf-8', 'fr', 'Fran&ccedil;ais'),
    'gl-utf-8'          => array('gl|galician', 'galician-utf-8', 'gl', 'Galego'),
    'he-utf-8'          => array('he|hebrew', 'hebrew-utf-8', 'he', '&#1506;&#1489;&#1512;&#1497;&#1514;'),
    'hi-utf-8'          => array('hi|hindi', 'hindi-utf-8', 'hi', '&#2361;&#2367;&#2344;&#2381;&#2342;&#2368;'),
    'hr-utf-8'          => array('hr|croatian', 'croatian-utf-8', 'hr', 'Hrvatski'),
    'hu-utf-8'          => array('hu|hungarian', 'hungarian-utf-8', 'hu', 'Magyar'),
    'id-utf-8'          => array('id|indonesian', 'indonesian-utf-8', 'id', 'Bahasa Indonesia'),
    'it-utf-8'          => array('it|italian', 'italian-utf-8', 'it', 'Italiano'),
    'ja-utf-8'          => array('ja|japanese', 'japanese-utf-8', 'ja', '&#26085;&#26412;&#35486;'),
    'ko-utf-8'          => array('ko|korean', 'korean-utf-8', 'ko', '&#54620;&#44397;&#50612;'),
    'ka-utf-8'          => array('ka|georgian', 'georgian-utf-8', 'ka', '&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;'),
    'lt-utf-8'          => array('lt|lithuanian', 'lithuanian-utf-8', 'lt', 'Lietuvi&#371;'),
    'lv-utf-8'          => array('lv|latvian', 'latvian-utf-8', 'lv', 'Latvie&scaron;u'),
    'mkcyr-utf-8'       => array('mk|macedonian', 'macedonian_cyrillic-utf-8', 'mk', 'Macedonian'),
    'mn-utf-8'          => array('mn|mongolian', 'mongolian-utf-8', 'mn', '&#1052;&#1086;&#1085;&#1075;&#1086;&#1083;'),
    'ms-utf-8'          => array('ms|malay', 'malay-utf-8', 'ms', 'Bahasa Melayu'),
    'nl-utf-8'          => array('nl|dutch', 'dutch-utf-8', 'nl', 'Nederlands'),
    'no-utf-8'          => array('no|norwegian', 'norwegian-utf-8', 'no', 'Norsk'),
    'pl-utf-8'          => array('pl|polish', 'polish-utf-8', 'pl', 'Polski'),
    'ptbr-utf-8'        => array('pt[-_]br|brazilian portuguese', 'brazilian_portuguese-utf-8', 'pt-BR', 'Portugu&ecirc;s'),
    'pt-utf-8'          => array('pt|portuguese', 'portuguese-utf-8', 'pt', 'Portugu&ecirc;s'),
    'ro-utf-8'          => array('ro|romanian', 'romanian-utf-8', 'ro', 'Rom&acirc;n&#259;'),
    'ru-utf-8'          => array('ru|russian', 'russian-utf-8', 'ru', '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;'),
    'si-utf-8'          => array('si|sinhala', 'sinhala-utf-8', 'si', '&#3523;&#3538;&#3458;&#3524;&#3517;'),
    'sk-utf-8'          => array('sk|slovak', 'slovak-utf-8', 'sk', 'Sloven&#269;ina'),
    'sl-utf-8'          => array('sl|slovenian', 'slovenian-utf-8', 'sl', 'Sloven&scaron;&#269;ina'),
    'sq-utf-8'          => array('sq|albanian', 'albanian-utf-8', 'sq', 'Shqip'),
    'srlat-utf-8'       => array('sr[-_]lat|serbian latin', 'serbian_latin-utf-8', 'sr-lat', 'Srpski'),
    'srcyr-utf-8'       => array('sr|serbian', 'serbian_cyrillic-utf-8', 'sr', '&#1057;&#1088;&#1087;&#1089;&#1082;&#1080;'),
    'sv-utf-8'          => array('sv|swedish', 'swedish-utf-8', 'sv', 'Svenska'),
    'th-utf-8'          => array('th|thai', 'thai-utf-8', 'th', '&#3616;&#3634;&#3625;&#3634;&#3652;&#3607;&#3618;'),
    'tr-utf-8'          => array('tr|turkish', 'turkish-utf-8', 'tr', 'T&uuml;rk&ccedil;e'),
    'tt-utf-8'          => array('tt|tatarish', 'tatarish-utf-8', 'tt', 'Tatar&ccedil;a'),
    'uk-utf-8'          => array('uk|ukrainian', 'ukrainian-utf-8', 'uk', '&#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;'),
    'zhtw-utf-8'        => array('zh[-_](tw|hk)|chinese traditional', 'chinese_traditional-utf-8', 'zh-TW', '&#20013;&#25991;'),
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
        $GLOBALS['convcharset'] = isset($GLOBALS['cfg']['DefaultCharset']) ? $GLOBALS['cfg']['DefaultCharset'] : 'utf-8';
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
    trigger_error(
        sprintf($GLOBALS['strLanguageUnknown'],
            htmlspecialchars($GLOBALS['lang_failed_cfg'])),
        E_USER_ERROR);
}
if ($GLOBALS['lang_failed_cookie']) {
    trigger_error(
        sprintf($GLOBALS['strLanguageUnknown'],
            htmlspecialchars($GLOBALS['lang_failed_cookie'])),
        E_USER_ERROR);
}
if ($GLOBALS['lang_failed_request']) {
    trigger_error(
        sprintf($GLOBALS['strLanguageUnknown'],
            htmlspecialchars($GLOBALS['lang_failed_request'])),
        E_USER_ERROR);
}

unset($line, $fall_back_lang,
    $GLOBALS['lang_failed_cfg'], $GLOBALS['lang_failed_cookie'], $GLOBALS['ang_failed_request'], $GLOBALS['strLanguageUnknown']);
?>
