<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold the PMA\libraries\LanguageManager class
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

use PMA\libraries\Language;

/**
 * Language selection manager
 *
 * @package PhpMyAdmin
 */
class LanguageManager
{
    /**
     * @var array Definition data for languages
     *
     * Each member contains:
     * - English language name
     * - Native language name
     * - Match regullar expression
     */
    private static $_language_data = array(
        'af' => array(
            'Afrikaans',
            '',
            'af|afrikaans',
        ),
        'ar' => array(
            'Arabic',
            '&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;',
            'ar|arabic',
        ),
        'az' => array(
            'Azerbaijani',
            'Az&#601;rbaycanca',
            'az|azerbaijani',
        ),
        'bn' => array(
            'Bangla',
            'বাংলা',
            'bn|bangla',
        ),
        'be' => array(
            'Belarusian',
            '&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1072;&#1103;',
            'be|belarusian',
        ),
        'be@latin' => array(
            'Belarusian (latin)',
            'Bie&#0322;aruskaja',
            'be[-_]lat|be@latin|belarusian latin',
        ),
        'bg' => array(
            'Bulgarian',
            '&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;',
            'bg|bulgarian',
        ),
        'bs' => array(
            'Bosnian',
            'Bosanski',
            'bs|bosnian',
        ),
        'br' => array(
            'Breton',
            'Brezhoneg',
            'br|breton',
        ),
        'brx' => array(
            'Bodo',
            'बड़ो',
            'brx|bodo',
        ),
        'ca' => array(
            'Catalan',
            'Catal&agrave;',
            'ca|catalan',
        ),
        'ckb' => array(
            'Sorani',
            'سۆرانی',
            'ckb|sorani',
        ),
        'cs' => array(
            'Czech',
            'Čeština',
            'cs|czech',
        ),
        'cy' => array(
            'Welsh',
            'Cymraeg',
            'cy|welsh',
        ),
        'da' => array(
            'Danish',
            'Dansk',
            'da|danish',
        ),
        'de' => array(
            'German',
            'Deutsch',
            'de|german',
        ),
        'el' => array(
            'Greek',
            '&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;',
            'el|greek',
        ),
        'en' => array(
            'English',
            '',
            'en|english',
        ),
        'en_gb' => array(
            'English (United Kingdom)',
            '',
            'en[_-]gb|english (United Kingdom)',
        ),
        'eo' => array(
            'Esperanto',
            'Esperanto',
            'eo|esperanto',
        ),
        'es' => array(
            'Spanish',
            'Espa&ntilde;ol',
            'es|spanish',
        ),
        'et' => array(
            'Estonian',
            'Eesti',
            'et|estonian',
        ),
        'eu' => array(
            'Basque',
            'Euskara',
            'eu|basque',
        ),
        'fa' => array(
            'Persian',
            '&#1601;&#1575;&#1585;&#1587;&#1740;',
            'fa|persian',
        ),
        'fi' => array(
            'Finnish',
            'Suomi',
            'fi|finnish',
        ),
        'fr' => array(
            'French',
            'Fran&ccedil;ais',
            'fr|french',
        ),
        'fy' => array(
            'Frisian',
            'Frysk',
            'fy|frisian',
        ),
        'gl' => array(
            'Galician',
            'Galego',
            'gl|galician',
        ),
        'gu' => array(
            'Gujarati',
            'ગુજરાતી',
            'gu|gujarati',
        ),
        'he' => array(
            'Hebrew',
            '&#1506;&#1489;&#1512;&#1497;&#1514;',
            'he|hebrew',
        ),
        'hi' => array(
            'Hindi',
            '&#2361;&#2367;&#2344;&#2381;&#2342;&#2368;',
            'hi|hindi',
        ),
        'hr' => array(
            'Croatian',
            'Hrvatski',
            'hr|croatian',
        ),
        'hu' => array(
            'Hungarian',
            'Magyar',
            'hu|hungarian',
        ),
        'hy' => array(
            'Armenian',
            'Հայերէն',
            'hy|armenian',
        ),
        'ia' => array(
            'Interlingua',
            '',
            'ia|interlingua',
        ),
        'id' => array(
            'Indonesian',
            'Bahasa Indonesia',
            'id|indonesian',
        ),
        'it' => array(
            'Italian',
            'Italiano',
            'it|italian',
        ),
        'ja' => array(
            'Japanese',
            '&#26085;&#26412;&#35486;',
            'ja|japanese',
        ),
        'ko' => array(
            'Korean',
            '&#54620;&#44397;&#50612;',
            'ko|korean',
        ),
        'ka' => array(
            'Georgian',
            '&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;',
            'ka|georgian',
        ),
        'kk' => array(
            'Kazakh',
            'Қазақ',
            'kk|kazakh',
        ),
        'km' => array(
            'Khmer',
            'ខ្មែរ',
            'km|khmer',
        ),
        'kn' => array(
            'Kannada',
            'ಕನ್ನಡ',
            'kn|kannada',
        ),
        'ksh' => array(
            'Colognian',
            'Kölsch',
            'ksh|colognian',
        ),
        'ky' => array(
            'Kyrgyz',
            'Кыргызча',
            'ky|kyrgyz',
        ),
        'li' => array(
            'Limburgish',
            'Lèmbörgs',
            'li|limburgish',
        ),
        'lt' => array(
            'Lithuanian',
            'Lietuvi&#371;',
            'lt|lithuanian',
        ),
        'lv' => array(
            'Latvian',
            'Latvie&scaron;u',
            'lv|latvian',
        ),
        'mk' => array(
            'Macedonian',
            'Macedonian',
            'mk|macedonian',
        ),
        'ml' => array(
            'Malayalam',
            'Malayalam',
            'ml|malayalam',
        ),
        'mn' => array(
            'Mongolian',
            '&#1052;&#1086;&#1085;&#1075;&#1086;&#1083;',
            'mn|mongolian',
        ),
        'ms' => array(
            'Malay',
            'Bahasa Melayu',
            'ms|malay',
        ),
        'ne' => array(
            'Nepali',
            'नेपाली',
            'ne|nepali',
        ),
        'nl' => array(
            'Dutch',
            'Nederlands',
            'nl|dutch',
        ),
        'nb' => array(
            'Norwegian',
            'Norsk',
            'nb|norwegian',
        ),
        'pa' => array(
            'Punjabi',
            'ਪੰਜਾਬੀ',
            'pa|punjabi',
        ),
        'pl' => array(
            'Polish',
            'Polski',
            'pl|polish',
        ),
        'pt_br' => array(
            'Brazilian Portuguese',
            'Portugu&ecirc;s',
            'pt[-_]br|brazilian portuguese',
        ),
        'pt' => array(
            'Portuguese',
            'Portugu&ecirc;s',
            'pt|portuguese',
        ),
        'ro' => array(
            'Romanian',
            'Rom&acirc;n&#259;',
            'ro|romanian',
        ),
        'ru' => array(
            'Russian',
            '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;',
            'ru|russian',
        ),
        'si' => array(
            'Sinhala',
            '&#3523;&#3538;&#3458;&#3524;&#3517;',
            'si|sinhala',
        ),
        'sk' => array(
            'Slovak',
            'Sloven&#269;ina',
            'sk|slovak',
        ),
        'sl' => array(
            'Slovenian',
            'Sloven&scaron;&#269;ina',
            'sl|slovenian',
        ),
        'sq' => array(
            'Slbanian',
            'Shqip',
            'sq|albanian',
        ),
        'sr@latin' => array(
            'Serbian (latin)',
            'Srpski',
            'sr[-_]lat|sr@latin|serbian latin',
        ),
        'sr' => array(
            'Serbian',
            '&#1057;&#1088;&#1087;&#1089;&#1082;&#1080;',
            'sr|serbian',
        ),
        'sv' => array(
            'Swedish',
            'Svenska',
            'sv|swedish',
        ),
        'ta' => array(
            'Tamil',
            'தமிழ்',
            'ta|tamil',
        ),
        'te' => array(
            'Telugu',
            'తెలుగు',
            'te|telugu',
        ),
        'th' => array(
            'Thai',
            '&#3616;&#3634;&#3625;&#3634;&#3652;&#3607;&#3618;',
            'th|thai',
        ),
        'tk' => array(
            'Turkmen',
            'Türkmençe',
            'tk|turkmen',
        ),
        'tr' => array(
            'Turkish',
            'T&uuml;rk&ccedil;e',
            'tr|turkish',
        ),
        'tt' => array(
            'Tatarish',
            'Tatar&ccedil;a',
            'tt|tatarish',
        ),
        'ug' => array(
            'Uyghur',
            'ئۇيغۇرچە',
            'ug|uyghur',
        ),
        'uk' => array(
            'Ukrainian',
            '&#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;',
            'uk|ukrainian',
        ),
        'ur' => array(
            'Urdu',
            'اُردوُ',
            'ur|urdu',
        ),
        'uz@latin' => array(
            'Uzbek (latin)',
            'O&lsquo;zbekcha',
            'uz[-_]lat|uz@latin|uzbek-latin',
        ),
        'uz' => array(
            'Uzbek (cyrillic)',
            '&#1038;&#1079;&#1073;&#1077;&#1082;&#1095;&#1072;',
            'uz[-_]cyr|uz@cyrillic|uzbek-cyrillic',
        ),
        'vi' => array(
            'Vietnamese',
            'Tiếng Việt',
            'vi|vietnamese',
        ),
        'vls' => array(
            'Flemish',
            'West-Vlams',
            'vls|flemish',
        ),
        'zh_tw' => array(
            'Chinese traditional',
            '&#20013;&#25991;',
            'zh[-_](tw|hk)|chinese traditional',
        ),
        // only TW and HK use traditional Chinese while others (CN, SG, MY)
        // use simplified Chinese
        'zh_cn' => array(
            'Chinese simplified',
            '&#20013;&#25991;',
            'zh(?![-_](tw|hk))([-_][[:alpha:]]{2,3})?|chinese simplified',
        ),
    );

    private $_available_locales;
    private $_available_languages;
    private $_lang_failed_cfg;
    private $_lang_failed_cookie;
    private $_lang_failed_request;
    private static $instance;

    /**
     * Returns LanguageManager singleton
     *
     * @return LanguageManager
     */
    public static function getInstance()
    {
        if (self::$instance === NULL) {
            self::$instance = new LanguageManager;
        }
        return self::$instance;
    }

    /**
     * Returns list of available locales
     *
     * @return array
     */
    public function listLocaleDir()
    {
        $result = array('en');

        /* Check for existing directory */
        if (!is_dir(LOCALE_PATH)) {
            return $result;
        }

        /* Open the directory */
        $handle = @opendir(LOCALE_PATH);
        /* This can happen if the kit is English-only */
        if ($handle === false) {
            return $result;
        }

        /* Process all files */
        while (false !== ($file = readdir($handle))) {
            $path = LOCALE_PATH
                . '/' . $file
                . '/LC_MESSAGES/phpmyadmin.mo';
            if ($file != "."
                && $file != ".."
                && file_exists($path)
            ) {
                $result[] = $file;
            }
        }
        /* Close the handle */
        closedir($handle);

        return $result;
    }

    /**
     * Returns (cached) list of all available locales
     *
     * @return array of strings
     */
    public function availableLocales()
    {
        if (! $this->_available_locales) {

            if (empty($GLOBALS['cfg']['FilterLanguages'])) {
                $this->_available_locales = $this->listLocaleDir();
            } else {
                $this->_available_locales = preg_grep(
                    '@' . $GLOBALS['cfg']['FilterLanguages'] . '@',
                    $this->listLocaleDir()
                );
            }
        }
        return $this->_available_locales;
    }

    /**
     * Returns (cached) list of all available languages
     *
     * @return array of Language objects
     */
    public function availableLanguages()
    {
        if (! $this->_available_languages) {
            $this->_available_languages = array();

            foreach($this->availableLocales() as $lang) {
                $lang = strtolower($lang);
                if (isset($this::$_language_data[$lang])) {
                    $data = $this::$_language_data[$lang];
                    $this->_available_languages[$lang] = new Language(
                        $lang,
                        $data[0],
                        $data[1],
                        $data[2]
                    );
                } else {
                    $this->_available_languages[$lang] = new Language(
                        $lang,
                        ucfirst($lang),
                        ucfirst($lang),
                        $lang
                    );
                }
            }
        }
        return $this->_available_languages;
    }

    /**
     * Returns (cached) list of all available languages sorted
     * by name
     *
     * @return array of Language objects
     */
    public function sortedLanguages()
    {
        $this->availableLanguages();
        uasort($this->_available_languages, function($a, $b)
            {
                return $a->cmp($b);
            }
        );
        return $this->_available_languages;
    }

    /**
     * Return Language object for given code
     *
     * @param string $code Language code
     *
     * @return object|false Language object or false on failure
     */
    public function getLanguage($code)
    {
        $langs = $this->availableLanguages();
        if (isset($langs[$code])) {
            return $langs[$code];
        }
        return false;
    }

    /**
     * Return currently active Language object
     *
     * @return object Language object
     */
    public function getCurrentLanguage()
    {
        return $this->_available_languages[$GLOBALS['lang']];
    }

    /**
     * Activates language based on configuration, user preferences or
     * browser
     *
     * @return Language
     */
    public function selectLanguage()
    {
        $langs = $this->availableLanguages();

        // check forced language
        if (! empty($GLOBALS['cfg']['Lang'])) {
            if (isset($langs[$GLOBALS['cfg']['Lang']])) {
                return $langs[$GLOBALS['cfg']['Lang']];
            }
            $this->_lang_failed_cfg = true;
        }

        // Don't use REQUEST in following code as it might be confused by cookies
        // with same name. Check user requested language (POST)
        if (! empty($_POST['lang'])) {
            if (isset($langs[$_POST['lang']])) {
                return $langs[$_POST['lang']];
            }
            $this->_lang_failed_request = true;
        }

        // check user requested language (GET)
        if (! empty($_GET['lang'])) {
            if (isset($langs[$_GET['lang']])) {
                return $langs[$_GET['lang']];
            }
            $this->_lang_failed_request = true;
        }

        // check previous set language
        if (! empty($_COOKIE['pma_lang'])) {
            if (isset($langs[$_COOKIE['pma_lang']])) {
                return $langs[$_COOKIE['pma_lang']];
            }
            $this->_lang_failed_cookie = true;
        }

        // try to find out user's language by checking its HTTP_ACCEPT_LANGUAGE variable;
        // prevent XSS
        $accepted_languages = PMA_getenv('HTTP_ACCEPT_LANGUAGE');
        if ($accepted_languages && false === mb_strpos($accepted_languages, '<')) {
            foreach (explode(',', $accepted_languages) as $header) {
                foreach ($langs as $language) {
                    if ($language->matchesAcceptLanguage($header)) {
                        return $language;
                    }
                }
            }
        }

        // try to find out user's language by checking its HTTP_USER_AGENT variable
        $user_agent = PMA_getenv('HTTP_USER_AGENT');
        if (! empty($user_agent)) {
            foreach ($langs as $language) {
                if ($language->matchesUserAgent($user_agent)) {
                    return $language;
                }
            }
        }

        // Didn't catch any valid lang : we use the default settings
        if (isset($langs[$GLOBALS['cfg']['DefaultLang']])) {
            return $langs[$GLOBALS['cfg']['DefaultLang']];
        }

        // Fallback to English
        return $langs['en'];
    }

    /**
     * Displays warnings about invalid languages. This needs to be postponed
     * to show messages at time when language is initialized.
     *
     * @return void
     */
    public function showWarnings()
    {
        // now, that we have loaded the language strings we can send the errors
        if ($this->_lang_failed_cfg
            || $this->_lang_failed_cookie
            || $this->_lang_failed_request
        ) {
            trigger_error(
                __('Ignoring unsupported language code.'),
                E_USER_ERROR
            );
        }
    }
}
