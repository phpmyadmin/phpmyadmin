<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin Language Loading File
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Returns language name
 *
 * @param string $tmplang Language code
 *
 * @return string
 */
function PMA_languageName($tmplang)
{
    $lang_name = ucfirst(
        /*overload*/mb_substr(/*overload*/mb_strrchr($tmplang[0], '|'), 1)
    );

    // Include native name if non empty
    if (!empty($tmplang[2])) {
        $lang_name = $tmplang[2] . ' - ' . $lang_name;
    }

    return $lang_name;
}

/**
 * Tries to find the language to use
 *
 * @return bool  success if valid lang is found, otherwise false
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

    // Don't use REQUEST in following code as it might be confused by cookies
    // with same name. Check user requested language (POST)
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
        } elseif (!is_string($_COOKIE['pma_lang'])) {
            /* Faked request, don't care on localisation */
            $GLOBALS['lang_failed_cookie'] = 'Yes';
        } else {
            $GLOBALS['lang_failed_cookie'] = $_COOKIE['pma_lang'];
        }
    }

    // try to find out user's language by checking its HTTP_ACCEPT_LANGUAGE variable;
    // prevent XSS
    $accepted_languages = PMA_getenv('HTTP_ACCEPT_LANGUAGE');
    if ($accepted_languages
        && false === /*overload*/mb_strpos($accepted_languages, '<')
    ) {
        foreach (explode(',', $accepted_languages) as $lang) {
            if (PMA_langDetect($lang, 1)) {
                return true;
            }
        }
    }
    unset($accepted_languages);

    // try to find out user's language by checking its HTTP_USER_AGENT variable
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
 * returns true on success, otherwise false
 *
 * @param string &$lang language to set
 *
 * @return bool  success
 */
function PMA_langSet(&$lang)
{
    /* Partial backward compatibility with 3.3 and older branches */
    $lang = str_replace('-utf-8', '', $lang);

    if (!is_string($lang)
        || empty($lang)
        || empty($GLOBALS['available_languages'][$lang])
    ) {
        return false;
    }
    $GLOBALS['lang'] = $lang;
    return true;
}

/**
 * Analyzes some PHP environment variables to find the most probable language
 * that should be used
 *
 * @param string  $str     string to analyze
 * @param integer $envType type of the PHP environment variable which value is $str
 *
 * @return bool    true on success, otherwise false
 *
 * @access  private
 */
function PMA_langDetect($str, $envType)
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
        if (/*overload*/mb_strpos($expr, '[-_]') === false) {
            $expr = str_replace('|', '([-_][[:alpha:]]{2,3})?|', $expr);
        }
        $pattern1 = '/^(' . addcslashes($expr, '/') . ')(;q=[0-9]\\.[0-9])?$/i';
        $pattern2 = '/(\(|\[|;[[:space:]])(' . addcslashes($expr, '/')
            . ')(;|\]|\))/i';
        if (($envType == 1 && preg_match($pattern1, $str))
            || ($envType == 2 && preg_match($pattern2, $str))
        ) {
            if (PMA_langSet($lang)) {
                return true;
            }
        }
    }

    return false;
} // end of the 'PMA_langDetect()' function


/**
 * All the supported languages have to be listed in the array below.
 * 1. The key must be the "official" ISO 639 language code and, if required,
 *    the dialect code. It can also contain some information about the
 *    charset (see the Russian case).
 * 2. The first of the values associated to the key is used in a regular
 *    expression to find some keywords corresponding to the language inside two
 *    environment variables.
 *    These values contain:
 *    - the "official" ISO language code and, if required, the dialect code
 *      too ('bu' for Bulgarian, 'fr([-_][[:alpha:]]{2})?' for all French
 *      dialects, 'zh[-_]tw' for Chinese traditional...), the dialect has to
 *      be specified first;
 *    - the '|' character (it means 'OR');
 *    - the full language name.
 * 3. The second value associated to the key is the language code as defined by
 *    the RFC1766.
 * 4. The third value is its native name in html entities or UTF-8.
 *
 * Beware that the sorting order (first values associated to keys by
 * alphabetical reverse order in the array) is important: 'zh-tw' (chinese
 * traditional) must be detected before 'zh' (chinese simplified) for
 * example.
 *
 * @param string $lang language
 *
 * @return array
 */
function PMA_langDetails($lang)
{
    switch ($lang) {
    case 'af':
        return array('af|afrikaans', 'af', '');
    case 'ar':
        return array(
            'ar|arabic',
            'ar',
            '&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;'
        );
    case 'az':
        return array('az|azerbaijani', 'az', 'Az&#601;rbaycanca');
    case 'bn':
        return array('bn|bangla', 'bn', 'বাংলা');
    case 'be':
        return array(
            'be|belarusian',
            'be',
            '&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1072;&#1103;'
        );
    case 'be@latin':
        return array('be[-_]lat|belarusian latin', 'be-lat', 'Bie&#0322;aruskaja');
    case 'bg':
        return array(
            'bg|bulgarian',
            'bg',
            '&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;'
        );
    case 'bs':
        return array('bs|bosnian', 'bs', 'Bosanski');
    case 'br':
        return array('br|breton', 'br', 'Brezhoneg');
    case 'ca':
        return array('ca|catalan', 'ca', 'Catal&agrave;');
    case 'ckb':
        return array('ckb', 'ckb', 'سۆرانی');
    case 'cs':
        return array('cs|czech', 'cs', 'Čeština');
    case 'cy':
        return array('cy|welsh', 'cy', 'Cymraeg');
    case 'da':
        return array('da|danish', 'da', 'Dansk');
    case 'de':
        return array('de|german', 'de', 'Deutsch');
    case 'el':
        return array(
            'el|greek',
            'el',
            '&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;'
        );
    case 'en':
        return array('en|english', 'en', '');
    case 'en_GB':
        return array('en[_-]gb|english (United Kingdom)', 'en-gb', '');
    case 'eo':
        return array('eo|esperanto', 'eo', 'Esperanto');
    case 'es':
        return array('es|spanish', 'es', 'Espa&ntilde;ol');
    case 'et':
        return array('et|estonian', 'et', 'Eesti');
    case 'eu':
        return array('eu|basque', 'eu', 'Euskara');
    case 'fa':
        return array('fa|persian', 'fa', '&#1601;&#1575;&#1585;&#1587;&#1740;');
    case 'fi':
        return array('fi|finnish', 'fi', 'Suomi');
    case 'fr':
        return array('fr|french', 'fr', 'Fran&ccedil;ais');
    case 'fy':
        return array('fy|frisian', 'fy', 'Frysk');
    case 'gl':
        return array('gl|galician', 'gl', 'Galego');
    case 'he':
        return array('he|hebrew', 'he', '&#1506;&#1489;&#1512;&#1497;&#1514;');
    case 'hi':
        return array('hi|hindi', 'hi', '&#2361;&#2367;&#2344;&#2381;&#2342;&#2368;');
    case 'hr':
        return array('hr|croatian', 'hr', 'Hrvatski');
    case 'hu':
        return array('hu|hungarian', 'hu', 'Magyar');
    case 'hy':
        return array('hy|armenian', 'hy', 'Հայերէն');
    case 'ia':
        return array('ia|interlingua', 'ia', 'Interlingua');
    case 'id':
        return array('id|indonesian', 'id', 'Bahasa Indonesia');
    case 'it':
        return array('it|italian', 'it', 'Italiano');
    case 'ja':
        return array('ja|japanese', 'ja', '&#26085;&#26412;&#35486;');
    case 'ko':
        return array('ko|korean', 'ko', '&#54620;&#44397;&#50612;');
    case 'ka':
        return array(
            'ka|georgian',
            'ka',
            '&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;'
        );
    case 'kk':
        return array('kk|kazakh', 'kk', 'Қазақ');
    case 'km':
        return array('km|khmer', 'km', 'ខ្មែរ');
    case 'kn':
        return array('kn|kannada', 'kn', 'ಕನ್ನಡ');
    case 'ksh':
        return array('ksh|colognian', 'ksh', 'Kölsch');
    case 'ky':
        return array('ky|kyrgyz', 'ky', 'Кыргызча');
    case 'li':
        return array('li|limburgish', 'li', 'Lèmbörgs');
    case 'lt':
        return array('lt|lithuanian', 'lt', 'Lietuvi&#371;');
    case 'lv':
        return array('lv|latvian', 'lv', 'Latvie&scaron;u');
    case 'mk':
        return array('mk|macedonian', 'mk', 'Macedonian');
    case 'ml':
        return array('ml|malayalam', 'ml', 'Malayalam');
    case 'mn':
        return array(
            'mn|mongolian',
            'mn',
            '&#1052;&#1086;&#1085;&#1075;&#1086;&#1083;'
        );
    case 'ms':
        return array('ms|malay', 'ms', 'Bahasa Melayu');
    case 'ne':
        return array('ne|nepali', 'ne', 'नेपाली');
    case 'nl':
        return array('nl|dutch', 'nl', 'Nederlands');
    case 'nb':
        return array('nb|norwegian', 'nb', 'Norsk');
    case 'pa':
        return array('pa|punjabi', 'pa', 'ਪੰਜਾਬੀ');
    case 'pl':
        return array('pl|polish', 'pl', 'Polski');
    case 'pt_BR':
        return array('pt[-_]br|brazilian portuguese', 'pt-BR', 'Portugu&ecirc;s');
    case 'pt':
        return array('pt|portuguese', 'pt', 'Portugu&ecirc;s');
    case 'ro':
        return array('ro|romanian', 'ro', 'Rom&acirc;n&#259;');
    case 'ru':
        return array(
            'ru|russian',
            'ru',
            '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;'
        );
    case 'si':
        return array('si|sinhala', 'si', '&#3523;&#3538;&#3458;&#3524;&#3517;');
    case 'sk':
        return array('sk|slovak', 'sk', 'Sloven&#269;ina');
    case 'sl':
        return array('sl|slovenian', 'sl', 'Sloven&scaron;&#269;ina');
    case 'sq':
        return array('sq|albanian', 'sq', 'Shqip');
    case 'sr@latin':
        return array('sr[-_]lat|serbian latin', 'sr-lat', 'Srpski');
    case 'sr':
        return array(
            'sr|serbian',
            'sr',
            '&#1057;&#1088;&#1087;&#1089;&#1082;&#1080;'
        );
    case 'sv':
        return array('sv|swedish', 'sv', 'Svenska');
    case 'ta':
        return array('ta|tamil', 'ta', 'தமிழ்');
    case 'te':
        return array('te|telugu', 'te', 'తెలుగు');
    case 'th':
        return array(
            'th|thai',
            'th',
            '&#3616;&#3634;&#3625;&#3634;&#3652;&#3607;&#3618;'
        );
    case 'tk':
        return array('tk|turkmen', 'tk', 'türkmençe');
    case 'tr':
        return array('tr|turkish', 'tr', 'T&uuml;rk&ccedil;e');
    case 'tt':
        return array('tt|tatarish', 'tt', 'Tatar&ccedil;a');
    case 'ug':
        return array('ug|uyghur', 'ug', 'ئۇيغۇرچە');
    case 'uk':
        return array(
            'uk|ukrainian',
            'uk',
            '&#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;'
        );
    case 'ur':
        return array('ur|urdu', 'ur', 'اُردوُ');
    case 'uz@latin':
        return array('uz[-_]lat|uzbek-latin', 'uz-lat', 'O&lsquo;zbekcha');
    case 'uz':
        return array(
            'uz[-_]cyr|uzbek-cyrillic',
            'uz-cyr',
            '&#1038;&#1079;&#1073;&#1077;&#1082;&#1095;&#1072;'
        );
    case 'vi':
        return array('vi|vietnamese', 'vi', 'Tiếng Việt');
    case 'vls':
        return array('vls|flemish', 'vls', 'West-Vlams');
    case 'zh_TW':
        return array(
            'zh[-_](tw|hk)|chinese traditional',
            'zh-TW',
            '&#20013;&#25991;'
        );
    case 'zh_CN':
        // only TW and HK use traditional Chinese while others (CN, SG, MY)
        // use simplified Chinese
        return array(
            'zh(?![-_](tw|hk))([-_][[:alpha:]]{2,3})?|chinese simplified',
            'zh',
            '&#20013;&#25991;'
        );
    }
    return array("$lang|$lang", $lang, $lang);
}

/**
 * Returns list of languages supported by phpMyAdmin
 *
 * @return array
 */
function PMA_langList()
{
    /* We can always speak English */
    $result = array('en' => PMA_langDetails('en'));

    /* Check for existing directory */
    if (!is_dir($GLOBALS['lang_path'])) {
        return $result;
    }

    /* Open the directory */
    $handle = @opendir($GLOBALS['lang_path']);
    /* This can happen if the kit is English-only */
    if ($handle === false) {
        return $result;
    }

    /* Process all files */
    while (false !== ($file = readdir($handle))) {
        $path = $GLOBALS['lang_path'] . '/' . $file . '/LC_MESSAGES/phpmyadmin.mo';
        if ($file != "."
            && $file != ".."
            && file_exists($path)
        ) {
            $result[$file] = PMA_langDetails($file);
        }
    }
    /* Close the handle */
    closedir($handle);

    return $result;
}

/**
 * @global string  path to the translations directory;
 *                 may be absent if the kit is English-only
 */
$GLOBALS['lang_path'] = './locale/';

/**
 * Load gettext functions.
 */
require_once GETTEXT_INC;

/**
 * @global string  interface language
 */
$GLOBALS['lang'] = 'en';
/**
 * @global boolean whether loading lang from cfg failed
 */
$GLOBALS['lang_failed_cfg'] = false;
/**
 * @global boolean whether loading lang from cookie failed
 */
$GLOBALS['lang_failed_cookie'] = false;
/**
 * @global boolean whether loading lang from user request failed
 */
$GLOBALS['lang_failed_request'] = false;
/**
 * @global string text direction ltr or rtl
 */
$GLOBALS['text_dir'] = 'ltr';

/**
 * @global array supported languages
 */
$GLOBALS['available_languages'] = PMA_langList();

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

if (! PMA_langCheck()) {
    // fallback language
    $fall_back_lang = 'en';
    $line = __LINE__;
    if (! PMA_langSet($fall_back_lang)) {
        trigger_error(
            'phpMyAdmin-ERROR: invalid lang code: '
            . __FILE__ . '#' . $line . ', check hard coded fall back language.',
            E_USER_WARNING
        );
        // stop execution
        // and tell the user that his chosen language is invalid
        PMA_fatalError(
            'Could not load any language, '
            . 'please check your language settings and folder.'
        );
    }
}

// Set locale
_setlocale(LC_MESSAGES, $GLOBALS['lang']);
_bindtextdomain('phpmyadmin', $GLOBALS['lang_path']);
_bind_textdomain_codeset('phpmyadmin', 'UTF-8');
_textdomain('phpmyadmin');

/**
 * Messages for phpMyAdmin.
 *
 * These messages are here for easy transition to Gettext.
 * You should not add any messages here, use instead gettext directly
 * in your template/PHP file.
 */

if (! function_exists('__')) {
    PMA_fatalError('Bad invocation!');
}

/* Text direction for language */
if (in_array($GLOBALS['lang'], array('ar', 'fa', 'he', 'ur'))) {
    $GLOBALS['text_dir'] = 'rtl';
} else {
    $GLOBALS['text_dir'] = 'ltr';
}

/* TCPDF */
$GLOBALS['l'] = array();

/* TCPDF settings */
$GLOBALS['l']['a_meta_charset'] = 'UTF-8';
$GLOBALS['l']['a_meta_dir'] = $GLOBALS['text_dir'];
$GLOBALS['l']['a_meta_language'] = $GLOBALS['lang'];

/* TCPDF translations */
$GLOBALS['l']['w_page'] = __('Page number:');


// now, that we have loaded the language strings we can send the errors
if ($GLOBALS['lang_failed_cfg']
    || $GLOBALS['lang_failed_cookie']
    || $GLOBALS['lang_failed_request']) {
    trigger_error(
        __('Ignoring unsupported language code.'),
        E_USER_ERROR
    );
}
unset(
    $line, $fall_back_lang, $GLOBALS['lang_failed_cfg'],
    $GLOBALS['lang_failed_cookie'], $GLOBALS['lang_failed_request']
);
?>
