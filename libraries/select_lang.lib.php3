<?php
/* $Id$ */


/**
 * phpMyAdmin Language Loading File
 */



/**
 * Define the path to the translations directory and get some variables
 * from system arrays if 'register_globals' is set to 'off'
 */
$lang_path = 'lang/';


/**
 * All the supported languages have to be listed in the array below.
 * 1. The key must be the "official" ISO 639 language code and, if required,
 *    the dialect code. It can also contains some informations about the
 *    charset (see the Russian case).
 *    These code are displayed at the starting page of phpMyAdmin.
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
 *    without the 'inc.php3' extension.
 * 4. The last values associated to the key is the language code as defined by
 *    the RFC1766.
 *
 * Beware that the sorting order (first values associated to keys by
 * alphabetical reverse order in the array) is important: 'zh-tw' (chinese
 * traditional) must be detected before 'zh' (chinese simplified) for
 * example.
 */
$available_languages = array(
    'ar'         => array('ar([-_][[:alpha:]]{2})?|arabic', 'arabic', 'ar'),
    'bg-koi8r'   => array('bg|bulgarian', 'bulgarian-koi8', 'bg'),
    'bg-win1251' => array('bg|bulgarian', 'bulgarian-win1251', 'bg'),
    'ca'         => array('ca|catalan', 'catala', 'ca'),
    'cs-iso'     => array('cs|czech', 'czech-iso', 'cs'),
    'cs-win1250' => array('cs|czech', 'czech-win1250', 'cs'),
    'da'         => array('da|danish', 'danish', 'da'),
    'de'         => array('de([-_][[:alpha:]]{2})?|german', 'german', 'de'),
    'el'         => array('el|greek',  'greek', 'el'),
    'en'         => array('en([-_][[:alpha:]]{2})?|english',  'english', 'en'),
    'es'         => array('es([-_][[:alpha:]]{2})?|spanish', 'spanish', 'es'),
    'fi'         => array('fi|finnish', 'finnish', 'fi'),
    'fr'         => array('fr([-_][[:alpha:]]{2})?|french', 'french', 'fr'),
    'gl'         => array('gl|galician', 'galician', 'gl'),
    'hu'         => array('hu|hungarian', 'hungarian', 'hu'),
    'it'         => array('it|italian', 'italian', 'it'),
    'ja'         => array('ja|japanese', 'japanese', 'ja'),
    'ko'         => array('ko|korean', 'korean', 'ko'),
    'nl'         => array('nl([-_][[:alpha:]]{2})?|dutch', 'dutch', 'nl'),
    'no'         => array('no|norwegian', 'norwegian', 'no'),
    'pl'         => array('pl|polish', 'polish', 'pl'),
    'pt-br'      => array('pt[-_]br|brazilian portuguese', 'brazilian_portuguese', 'pt-BR'),
    'pt'         => array('pt([-_][[:alpha:]]{2})?|portuguese', 'portuguese', 'pt'),
    'ro'         => array('ro|romanian', 'romanian', 'ro'),
    'ru-koi8r'   => array('ru|russian', 'russian-koi8', 'ru'),
    'ru-win1251' => array('ru|russian', 'russian-win1251', 'ru'),
    'sk'         => array('sk|slovak', 'slovak-iso', 'sk'),
    'sk-win1250' => array('sk|slovak', 'slovak-win1250', 'sk'),
    'sv'         => array('sv|swedish', 'swedish', 'sv'),
    'th'         => array('th|thai', 'thai', 'th'),
    'tr'         => array('tr|turkish', 'turkish', 'tr'),
    'uk-win1251' => array('uk|ukrainian', 'ukrainian-win1251', 'uk'),
    'zh-tw'      => array('zh[-_]tw|chinese traditional', 'chinese_big5', 'zh-TW'),
    'zh'         => array('zh|chinese simplified', 'chinese_gb', 'zh')
);


if (!defined('PMA_IS_LANG_DETECT_FUNCTION')) {
    define('PMA_IS_LANG_DETECT_FUNCTION', 1);

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

        reset($available_languages);
        while (list($key, $value) = each($available_languages)) {
            // $envType =  1 for the 'HTTP_ACCEPT_LANGUAGE' environment variable,
            //             2 for the 'HTTP_USER_AGENT' one
            if (($envType == 1 && eregi('^(' . $value[0] . ')(;q=[0-9]\\.[0-9])?$', $str))
                || ($envType == 2 && eregi('(\(|\[|;[[:space:]])(' . $value[0] . ')(;|\]|\))', $str))) {
                $lang     = $key;
                break;
            }
        }
    } // end of the 'PMA_langDetect()' function

} // end if


/**
 * Get some global variables if 'register_globals' is set to 'off'
 * loic1 - 2001/25/11: use the new globals arrays defined with php 4.1+
 */
if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $HTTP_ACCEPT_LANGUAGE = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
}
else if (!empty($HTTP_SERVER_VARS['HTTP_ACCEPT_LANGUAGE'])) {
    $HTTP_ACCEPT_LANGUAGE = $HTTP_SERVER_VARS['HTTP_ACCEPT_LANGUAGE'];
}

if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
}
else if (!empty($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) {
    $HTTP_USER_AGENT = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
}

if (!isset($lang)) {
    if (isset($_GET) && !empty($_GET['lang'])) {
        $lang = $_GET['lang'];
    }
    else if (isset($HTTP_GET_VARS) && !empty($HTTP_GET_VARS['lang'])) {
        $lang = $HTTP_GET_VARS['lang'];
    }
    else if (isset($_POST) && !empty($_POST['lang'])) {
        $lang = $_POST['lang'];
    }
    else if (isset($HTTP_POST_VARS) && !empty($HTTP_POST_VARS['lang'])) {
        $lang = $HTTP_POST_VARS['lang'];
    }
    else if (isset($_COOKIE) && !empty($_COOKIE['lang'])) {
        $lang = $_COOKIE['lang'];
    }
    else if (isset($HTTP_COOKIE_VARS) && !empty($HTTP_COOKIE_VARS['lang'])) {
        $lang = $HTTP_COOKIE_VARS['lang'];
    }
}


/**
 * Do the work!
 */
// Lang forced
if (!empty($cfgLang)) {
    $lang = $cfgLang;
}

// If '$lang' is defined, ensure this is a valid translation
if (!empty($lang) && empty($available_languages[$lang])) {
    $lang = '';
}

// Language is not defined yet :
// 1. try to findout users language by checking it's HTTP_ACCEPT_LANGUAGE
//    variable
if (empty($lang) && !empty($HTTP_ACCEPT_LANGUAGE)) {
    $accepted    = explode(',', $HTTP_ACCEPT_LANGUAGE);
    $acceptedCnt = count($accepted);
    reset($accepted);
    for ($i = 0; $i < $acceptedCnt && empty($lang); $i++) {
        PMA_langDetect($accepted[$i], 1);
    }
}
// 2. try to findout users language by checking it's HTTP_USER_AGENT variable
if (empty($lang) && !empty($HTTP_USER_AGENT)) {
    PMA_langDetect($HTTP_USER_AGENT, 2);
}

// 3. Didn't catch any valid lang : we use the default settings
if (empty($lang)) {
    $lang = $cfgDefaultLang;
}

// 4. Defines the associated filename and load the translation
$lang_file = $lang_path . $available_languages[$lang][1] . '.inc.php3';
require('./' . $lang_file);


 // $__PMA_SELECT_LANG_LIB__
?>
