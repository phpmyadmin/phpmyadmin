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
define('PMA_CHARSET_MB', 4);

if (! isset($GLOBALS['cfg']['RecodingEngine'])) {
    $GLOBALS['cfg']['RecodingEngine'] = '';
}
// Finally detect which function we will use:
if ($GLOBALS['cfg']['RecodingEngine'] == 'iconv') {
    if (@function_exists('iconv')) {
        $PMA_recoding_engine = PMA_getIconvRecodingEngine();
    } else {
        $PMA_recoding_engine = PMA_CHARSET_NONE;
        PMA_warnMissingExtension('iconv');
    }
} elseif ($GLOBALS['cfg']['RecodingEngine'] == 'recode') {
    if (@function_exists('recode_string')) {
        $PMA_recoding_engine = PMA_CHARSET_RECODE;
    } else {
        $PMA_recoding_engine = PMA_CHARSET_NONE;
        PMA_warnMissingExtension('recode');
    }
} elseif ($GLOBALS['cfg']['RecodingEngine'] == 'mb') {
    if (@function_exists('mb_convert_encoding')) {
        $PMA_recoding_engine = PMA_CHARSET_MB;
    } else {
        $PMA_recoding_engine = PMA_CHARSET_NONE;
        PMA_warnMissingExtension('mbstring');
    }
} elseif ($GLOBALS['cfg']['RecodingEngine'] == 'auto') {
    if (@function_exists('iconv')) {
        $PMA_recoding_engine = PMA_getIconvRecodingEngine();
    } elseif (@function_exists('recode_string')) {
        $PMA_recoding_engine = PMA_CHARSET_RECODE;
    } elseif (@function_exists('mb_convert_encoding')) {
        $PMA_recoding_engine = PMA_CHARSET_MB;
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
 * Determines the correct recoding engine to use 
 *
 * @return int $PMA_recoding_engine 
 *
 * @access  public
 *
 */
function PMA_getIconvRecodingEngine()
{
    if ((@stristr(PHP_OS, 'AIX'))
        && (@strcasecmp(ICONV_IMPL, 'unknown') == 0)
        && (@strcasecmp(ICONV_VERSION, 'unknown') == 0)
    ) {
        return PMA_CHARSET_ICONV_AIX;
    } else {
        return PMA_CHARSET_ICONV;
    }
}

/**
 * Converts encoding of text according to parameters with detected
 * conversion function.
 *
 * @param string $src_charset  source charset
 * @param string $dest_charset target charset
 * @param string $what         what to convert
 *
 * @return string   converted text
 *
 * @access  public
 *
 */
function PMA_convertString($src_charset, $dest_charset, $what)
{
    if ($src_charset == $dest_charset) {
        return $what;
    }
    switch ($GLOBALS['PMA_recoding_engine']) {
    case PMA_CHARSET_RECODE:
        return recode_string($src_charset . '..'  . $dest_charset, $what);
    case PMA_CHARSET_ICONV:
        return iconv(
            $src_charset, $dest_charset . $GLOBALS['cfg']['IconvExtraParams'], $what
        );
    case PMA_CHARSET_ICONV_AIX:
        return PMA_convertAIXIconv(
            $src_charset, $dest_charset . $GLOBALS['cfg']['IconvExtraParams'], $what
        );
    case PMA_CHARSET_MB:
        return mb_convert_encoding(
            $what, $dest_charset, $src_charset
        );
    default:
        return $what;
    }
} //  end of the "PMA_convertString()" function

?>
