<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * phpMyAdmin Language Loading File
 */


/**
 * We need some elements of the superglobal $_SERVER array.
 */
require_once('./libraries/grab_globals.lib.php');


/**
 * Define the path to the translations directory and get some variables
 * from system arrays if 'register_globals' is set to 'off'
 */
$lang_path = 'lang/';


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
 *      dialects, 'zh[-_]tw' for Chinese traditional...);
 *    - the '|' character (it means 'OR');
 *    - the full language name.
 * 3. The second values associated to the key is the name of the file to load
 *    without the 'inc.php' extension.
 * 4. The last values associated to the key is the language code as defined by
 *    the RFC1766.
 *
 * Beware that the sorting order (first values associated to keys by
 * alphabetical reverse order in the array) is important: 'zh-tw' (chinese
 * traditional) must be detected before 'zh' (chinese simplified) for
 * example.
 *
 * When there are more than one charset for a language, we put the -utf-8
 * first.
 *
 * For Russian, we put 1251 first, because MSIE does not accept 866
 * and users would not see anything.
 */
$available_languages = array(
    'af-utf-8'     => array('af|afrikaans', 'afrikaans-utf-8', 'af'),
    'af-iso-8859-1'=> array('af|afrikaans', 'afrikaans-iso-8859-1', 'af'),
    'ar-utf-8'     => array('ar([-_][[:alpha:]]{2})?|arabic', 'arabic-utf-8', 'ar'),
    'ar-win1256'   => array('ar([-_][[:alpha:]]{2})?|arabic', 'arabic-windows-1256', 'ar'),
    'az-utf-8'     => array('az|azerbaijani', 'azerbaijani-utf-8', 'az'),
    'az-iso-8859-9'=> array('az|azerbaijani', 'azerbaijani-iso-8859-9', 'az'),
    'bg-utf-8'     => array('bg|bulgarian', 'bulgarian-utf-8', 'bg'),
    'bg-win1251'   => array('bg|bulgarian', 'bulgarian-windows-1251', 'bg'),
    'bg-koi8-r'    => array('bg|bulgarian', 'bulgarian-koi8-r', 'bg'),
    'bs-utf-8'     => array('bs|bosnian', 'bosnian-utf-8', 'bs'),
    'bs-win1250'   => array('bs|bosnian', 'bosnian-windows-1250', 'bs'),
    'ca-utf-8'     => array('ca|catalan', 'catalan-utf-8', 'ca'),
    'ca-iso-8859-1'=> array('ca|catalan', 'catalan-iso-8859-1', 'ca'),
    'cs-utf-8'     => array('cs|czech', 'czech-utf-8', 'cs'),
    'cs-iso-8859-2'=> array('cs|czech', 'czech-iso-8859-2', 'cs'),
    'cs-win1250'   => array('cs|czech', 'czech-windows-1250', 'cs'),
    'da-utf-8'     => array('da|danish', 'danish-utf-8', 'da'),
    'da-iso-8859-1'=> array('da|danish', 'danish-iso-8859-1', 'da'),
    'de-utf-8'     => array('de([-_][[:alpha:]]{2})?|german', 'german-utf-8', 'de'),
    'de-iso-8859-1'=> array('de([-_][[:alpha:]]{2})?|german', 'german-iso-8859-1', 'de'),
    'el-utf-8'     => array('el|greek',  'greek-utf-8', 'el'),
    'el-iso-8859-7'=> array('el|greek',  'greek-iso-8859-7', 'el'),
    'en-utf-8'     => array('en([-_][[:alpha:]]{2})?|english',  'english-utf-8', 'en'),
    'en-iso-8859-1'=> array('en([-_][[:alpha:]]{2})?|english',  'english-iso-8859-1', 'en'),
    'es-utf-8'     => array('es([-_][[:alpha:]]{2})?|spanish', 'spanish-utf-8', 'es'),
    'es-iso-8859-1'=> array('es([-_][[:alpha:]]{2})?|spanish', 'spanish-iso-8859-1', 'es'),
    'et-utf-8'     => array('et|estonian', 'estonian-utf-8', 'et'),
    'et-iso-8859-1'=> array('et|estonian', 'estonian-iso-8859-1', 'et'),
    'eu-utf-8'     => array('eu|basque', 'basque-utf-8', 'eu'),
    'eu-iso-8859-1'=> array('eu|basque', 'basque-iso-8859-1', 'eu'),
    'fa-utf-8'     => array('fa|persian', 'persian-utf-8', 'fa'),
    'fa-win1256'   => array('fa|persian', 'persian-windows-1256', 'fa'),
    'fi-utf-8'     => array('fi|finnish', 'finnish-utf-8', 'fi'),
    'fi-iso-8859-1'=> array('fi|finnish', 'finnish-iso-8859-1', 'fi'),
    'fr-utf-8'     => array('fr([-_][[:alpha:]]{2})?|french', 'french-utf-8', 'fr'),
    'fr-iso-8859-1'=> array('fr([-_][[:alpha:]]{2})?|french', 'french-iso-8859-1', 'fr'),
    'gl-utf-8'     => array('gl|galician', 'galician-utf-8', 'gl'),
    'gl-iso-8859-1'=> array('gl|galician', 'galician-iso-8859-1', 'gl'),
    'he-iso-8859-8-i'=> array('he|hebrew', 'hebrew-iso-8859-8-i', 'he'),
    'hi-utf-8'     => array('hi|hindi', 'hindi-utf-8', 'hi'),
    'hr-utf-8'     => array('hr|croatian', 'croatian-utf-8', 'hr'),
    'hr-win1250'   => array('hr|croatian', 'croatian-windows-1250', 'hr'),
    'hr-iso-8859-2'=> array('hr|croatian', 'croatian-iso-8859-2', 'hr'),
    'hu-utf-8'     => array('hu|hungarian', 'hungarian-utf-8', 'hu'),
    'hu-iso-8859-2'=> array('hu|hungarian', 'hungarian-iso-8859-2', 'hu'),
    'id-utf-8'     => array('id|indonesian', 'indonesian-utf-8', 'id'),
    'id-iso-8859-1'=> array('id|indonesian', 'indonesian-iso-8859-1', 'id'),
    'it-utf-8'     => array('it|italian', 'italian-utf-8', 'it'),
    'it-iso-8859-1'=> array('it|italian', 'italian-iso-8859-1', 'it'),
    'ja-utf-8'     => array('ja|japanese', 'japanese-utf-8', 'ja'),
    'ja-euc'       => array('ja|japanese', 'japanese-euc', 'ja'),
    'ja-sjis'      => array('ja|japanese', 'japanese-sjis', 'ja'),
    'ko-ks_c_5601-1987'=> array('ko|korean', 'korean-ks_c_5601-1987', 'ko'),
    'ka-utf-8'     => array('ka|georgian', 'georgian-utf-8', 'ka'),
    'lt-utf-8'     => array('lt|lithuanian', 'lithuanian-utf-8', 'lt'),
    'lt-win1257'   => array('lt|lithuanian', 'lithuanian-windows-1257', 'lt'),
    'lv-utf-8'     => array('lv|latvian', 'latvian-utf-8', 'lv'),
    'lv-win1257'   => array('lv|latvian', 'latvian-windows-1257', 'lv'),
    'ms-utf-8'     => array('ms|malay', 'malay-utf-8', 'ms'),
    'ms-iso-8859-1'=> array('ms|malay', 'malay-iso-8859-1', 'ms'),
    'nl-utf-8'     => array('nl([-_][[:alpha:]]{2})?|dutch', 'dutch-utf-8', 'nl'),
    'nl-iso-8859-1'=> array('nl([-_][[:alpha:]]{2})?|dutch', 'dutch-iso-8859-1', 'nl'),
    'no-utf-8'     => array('no|norwegian', 'norwegian-utf-8', 'no'),
    'no-iso-8859-1'=> array('no|norwegian', 'norwegian-iso-8859-1', 'no'),
    'pl-utf-8'     => array('pl|polish', 'polish-utf-8', 'pl'),
    'pl-iso-8859-2'=> array('pl|polish', 'polish-iso-8859-2', 'pl'),
    'pt-br-utf-8'  => array('pt[-_]br|brazilian portuguese', 'brazilian_portuguese-utf-8', 'pt-BR'),
    'pt-br-iso-8859-1' => array('pt[-_]br|brazilian portuguese', 'brazilian_portuguese-iso-8859-1', 'pt-BR'),
    'pt-utf-8'     => array('pt([-_][[:alpha:]]{2})?|portuguese', 'portuguese-utf-8', 'pt'),
    'pt-iso-8859-1'=> array('pt([-_][[:alpha:]]{2})?|portuguese', 'portuguese-iso-8859-1', 'pt'),
    'ro-utf-8'     => array('ro|romanian', 'romanian-utf-8', 'ro'),
    'ro-iso-8859-1'=> array('ro|romanian', 'romanian-iso-8859-1', 'ro'),
    'ru-win1251'   => array('ru|russian', 'russian-windows-1251', 'ru'),
    'ru-utf-8'     => array('ru|russian', 'russian-utf-8', 'ru'),
    'ru-dos-866'   => array('ru|russian', 'russian-dos-866', 'ru'),
    'ru-koi8-r'    => array('ru|russian', 'russian-koi8-r', 'ru'),
    'sk-utf-8'     => array('sk|slovak', 'slovak-utf-8', 'sk'),
    'sk-iso-8859-2'=> array('sk|slovak', 'slovak-iso-8859-2', 'sk'),
    'sk-win1250'   => array('sk|slovak', 'slovak-windows-1250', 'sk'),
    'sl-utf-8'     => array('sl|slovenian', 'slovenian-utf-8', 'sl'),
    'sl-iso-8859-2'=> array('sl|slovenian', 'slovenian-iso-8859-2', 'sl'),
    'sl-win1250'   => array('sl|slovenian', 'slovenian-windows-1250', 'sl'),
    'sq-utf-8'     => array('sq|albanian', 'albanian-utf-8', 'sq'),
    'sq-iso-8859-1'=> array('sq|albanian', 'albanian-iso-8859-1', 'sq'),
    'sr-utf-8'     => array('sr|serbian', 'serbian_cyrillic-utf-8', 'sr'),
    'sr-win1251'   => array('sr|serbian', 'serbian_cyrillic-windows-1251', 'sr'),
    'sr-lat-utf-8'  => array('sr[-_]lat|serbian latin', 'serbian_latin-utf-8', 'sr-lat'),
    'sr-lat-win1250'=> array('sr[-_]lat|serbian latin', 'serbian_latin-windows-1250', 'sr-lat'),
    'sv-utf-8'     => array('sv|swedish', 'swedish-utf-8', 'sv'),
    'sv-iso-8859-1'=> array('sv|swedish', 'swedish-iso-8859-1', 'sv'),
    'th-utf-8'     => array('th|thai', 'thai-utf-8', 'th'),
    'th-tis-620'   => array('th|thai', 'thai-tis-620', 'th'),
    'tr-utf-8'     => array('tr|turkish', 'turkish-utf-8', 'tr'),
    'tr-iso-8859-9'=> array('tr|turkish', 'turkish-iso-8859-9', 'tr'),
    'uk-utf-8'     => array('uk|ukrainian', 'ukrainian-utf-8', 'uk'),
    'uk-win1251'   => array('uk|ukrainian', 'ukrainian-windows-1251', 'uk'),
    'zh-tw-utf-8'  => array('zh[-_]tw|chinese traditional', 'chinese_big5-utf-8', 'zh-TW'),
    'zh-tw'        => array('zh[-_]tw|chinese traditional', 'chinese_big5', 'zh-TW'),
    'zh-utf-8'     => array('zh|chinese simplified', 'chinese_gb-utf-8', 'zh'),
    'zh'           => array('zh|chinese simplified', 'chinese_gb', 'zh')
);


/**
 * Analyzes some PHP environment variables to find the most probable language
 * that should be used
 *
 * @param   string   string to analyze
 * @param   integer  type of the PHP environment variable which value is $str
 *
 * @global  array    the list of available translations
 * @global  string   the retained translation keyword
 *
 * @access  private
 */
function PMA_langDetect($str = '', $envType = '')
{
    global $available_languages;
    global $lang;

    foreach($available_languages AS $key => $value) {
        // $envType =  1 for the 'HTTP_ACCEPT_LANGUAGE' environment variable,
        //             2 for the 'HTTP_USER_AGENT' one
        if (($envType == 1 && eregi('^(' . $value[0] . ')(;q=[0-9]\\.[0-9])?$', $str))
            || ($envType == 2 && eregi('(\(|\[|;[[:space:]])(' . $value[0] . ')(;|\]|\))', $str))) {
            $lang     = $key;
            break;
        }
    }
} // end of the 'PMA_langDetect()' function


if (!isset($lang)) {
    if (isset($_GET) && !empty($_GET['lang'])) {
        $lang = $_GET['lang'];
    }
    else if (isset($_POST) && !empty($_POST['lang'])) {
        $lang = $_POST['lang'];
    }
    else if (isset($_COOKIE) && !empty($_COOKIE['lang'])) {
        $lang = $_COOKIE['lang'];
    }
}


/**
 * Do the work!
 */

// compatibility with config.inc.php <= v1.80
if (!isset($cfg['Lang']) && isset($cfgLang)) {
    $cfg['Lang']        = $cfgLang;
    unset($cfgLang);
}
if (!isset($cfg['DefaultLang']) && isset($cfgDefaultLang)) {
    $cfg['DefaultLang'] = $cfgDefaultLang;
    unset($cfgLang);
}

// Disable UTF-8 if $cfg['AllowAnywhereRecoding'] has been set to FALSE.
if (!isset($cfg['AllowAnywhereRecoding']) || !$cfg['AllowAnywhereRecoding']) {
    $available_language_files               = $available_languages;
    $available_languages                    = array();
    foreach($available_language_files AS $tmp_lang => $tmp_lang_data) {
        if (substr($tmp_lang, -5) != 'utf-8') {
            $available_languages[$tmp_lang] = $tmp_lang_data;
        }
    } // end while
    unset($tmp_lang, $tmp_lang_data, $available_language_files);
} // end if

// Lang forced
if (!empty($cfg['Lang'])) {
    $lang = $cfg['Lang'];
}

// If '$lang' is defined, ensure this is a valid translation
if (!empty($lang) && empty($available_languages[$lang])) {
    $lang = '';
}

// Language is not defined yet :
// 1. try to findout user's language by checking its HTTP_ACCEPT_LANGUAGE
//    variable
if (empty($lang) && !empty($HTTP_ACCEPT_LANGUAGE)) {
    $accepted    = explode(',', $HTTP_ACCEPT_LANGUAGE);
    $acceptedCnt = count($accepted);
    for ($i = 0; $i < $acceptedCnt && empty($lang); $i++) {
        PMA_langDetect($accepted[$i], 1);
    }
}
// 2. try to findout user's language by checking its HTTP_USER_AGENT variable
if (empty($lang) && !empty($HTTP_USER_AGENT)) {
    PMA_langDetect($HTTP_USER_AGENT, 2);
}

// 3. Didn't catch any valid lang : we use the default settings
if (empty($lang)) {
    $lang = $cfg['DefaultLang'];
}

// 4. Checks whether charset recoding should be allowed or not
$allow_recoding = FALSE; // Default fallback value
if (!isset($convcharset) || empty($convcharset)) {
    $convcharset = $cfg['DefaultCharset'];
}

// 5. Defines the associated filename and load the translation
$lang_file = $lang_path . $available_languages[$lang][1] . '.inc.php';
require_once('./' . $lang_file);

?>
