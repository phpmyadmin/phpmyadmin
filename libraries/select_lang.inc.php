<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin Language Loading File
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/select_lang.lib.php';

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
    || $GLOBALS['lang_failed_request']
) {
    trigger_error(
        __('Ignoring unsupported language code.'),
        E_USER_ERROR
    );
}
unset(
    $line, $fall_back_lang, $GLOBALS['lang_failed_cfg'],
    $GLOBALS['lang_failed_cookie'], $GLOBALS['lang_failed_request']
);
