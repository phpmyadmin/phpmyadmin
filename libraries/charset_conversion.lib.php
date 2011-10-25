<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Charset conversion functions.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

define('PMA_CHARSET_NONE', 0);
define('PMA_CHARSET_ICONV', 1);
define('PMA_CHARSET_RECODE', 2);
define('PMA_CHARSET_ICONV_AIX', 3);

// Finally detect which function we will use:
if ($cfg['RecodingEngine'] == 'iconv') {
    if (@function_exists('iconv')) {
        if ((@stristr(PHP_OS, 'AIX')) && (@strcasecmp(ICONV_IMPL, 'unknown') == 0) && (@strcasecmp(ICONV_VERSION, 'unknown') == 0)) {
            $PMA_recoding_engine = PMA_CHARSET_ICONV_AIX;
        } else {
            $PMA_recoding_engine = PMA_CHARSET_ICONV;
        }
    } else {
        $PMA_recoding_engine = PMA_CHARSET_NONE;
        PMA_warnMissingExtension('iconv');
    }
} elseif ($cfg['RecodingEngine'] == 'recode') {
    if (@function_exists('recode_string')) {
        $PMA_recoding_engine = PMA_CHARSET_RECODE;
    } else {
        $PMA_recoding_engine = PMA_CHARSET_NONE;
        PMA_warnMissingExtension('recode');
    }
} elseif ($cfg['RecodingEngine'] == 'auto') {
    if (@function_exists('iconv')) {
        if ((@stristr(PHP_OS, 'AIX')) && (@strcasecmp(ICONV_IMPL, 'unknown') == 0) && (@strcasecmp(ICONV_VERSION, 'unknown') == 0)) {
            $PMA_recoding_engine = PMA_CHARSET_ICONV_AIX;
        } else {
            $PMA_recoding_engine = PMA_CHARSET_ICONV;
        }
    } elseif (@function_exists('recode_string')) {
        $PMA_recoding_engine = PMA_CHARSET_RECODE;
    } else {
        $PMA_recoding_engine = PMA_CHARSET_NONE;
    }
} else {
    $PMA_recoding_engine = PMA_CHARSET_NONE;
}

/* Load AIX iconv wrapper if needed */
if ($PMA_recoding_engine == PMA_CHARSET_ICONV_AIX) {
    include_once './libraries/iconv_wrapper.lib.php';
}

/**
 * Converts encoding of text according to parameters with detected
 * conversion function.
 *
 * @param string   source charset
 * @param string   target charset
 * @param string   what to convert
 *
 * @return  string   converted text
 *
 * @access  public
 *
 */
function PMA_convert_string($src_charset, $dest_charset, $what)
{
    if ($src_charset == $dest_charset) {
        return $what;
    }
    switch ($GLOBALS['PMA_recoding_engine']) {
        case PMA_CHARSET_RECODE:
            return recode_string($src_charset . '..'  . $dest_charset, $what);
        case PMA_CHARSET_ICONV:
            return iconv($src_charset, $dest_charset . $GLOBALS['cfg']['IconvExtraParams'], $what);
        case PMA_CHARSET_ICONV_AIX:
            return PMA_aix_iconv_wrapper($src_charset, $dest_charset . $GLOBALS['cfg']['IconvExtraParams'], $what);
        default:
            return $what;
    }
} //  end of the "PMA_convert_string()" function

?>
