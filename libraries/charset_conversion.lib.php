<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Charset conversion functions.
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Loads the recode or iconv extensions if any of it is not loaded yet
 */
if (isset($cfg['AllowAnywhereRecoding'])
    && $cfg['AllowAnywhereRecoding']) {

    if ($cfg['RecodingEngine'] == 'recode') {
        if (!@extension_loaded('recode')) {
            echo $strCantLoadRecodeIconv;
            exit;
        }
        $PMA_recoding_engine             = 'recode';
    } elseif ($cfg['RecodingEngine'] == 'iconv') {
        if (!@extension_loaded('iconv')) {
            echo $strCantLoadRecodeIconv;
            exit;
        }
        $PMA_recoding_engine             = 'iconv';
    } else {
        if (@extension_loaded('iconv')) {
            $PMA_recoding_engine         = 'iconv';
        } elseif (@extension_loaded('recode')) {
            $PMA_recoding_engine         = 'recode';
        } else {
            echo $strCantLoadRecodeIconv;
            exit;
        }
    }
} // end load recode/iconv extension

define('PMA_CHARSET_NONE', 0);
define('PMA_CHARSET_ICONV', 1);
define('PMA_CHARSET_LIBICONV', 2);
define('PMA_CHARSET_RECODE', 3);
define('PMA_CHARSET_ICONV_AIX', 4);

if (!isset($cfg['IconvExtraParams'])) {
    $cfg['IconvExtraParams'] = '';
}

// Finally detect which function we will use:
if (isset($cfg['AllowAnywhereRecoding'])
    && $cfg['AllowAnywhereRecoding']) {

    if (!isset($PMA_recoding_engine)) {
        $PMA_recoding_engine = $cfg['RecodingEngine'];
    }
    if ($PMA_recoding_engine == 'iconv') {
        if (@function_exists('iconv')) {
            if ((@stristr(PHP_OS, 'AIX')) && (@strcasecmp(ICONV_IMPL, 'unknown') == 0) && (@strcasecmp(ICONV_VERSION, 'unknown') == 0)) {
                $PMA_recoding_engine = PMA_CHARSET_ICONV_AIX;
            } else {
                $PMA_recoding_engine = PMA_CHARSET_ICONV;
            }
        } elseif (@function_exists('libiconv')) {
            $PMA_recoding_engine = PMA_CHARSET_LIBICONV;
        } else {
            $PMA_recoding_engine = PMA_CHARSET_NONE;

            if (!isset($GLOBALS['is_header_sent'])) {
                include './libraries/header.inc.php';
            }
            echo $strCantUseRecodeIconv;
            require_once './libraries/footer.inc.php';
            exit();
        }
    } elseif ($PMA_recoding_engine == 'recode') {
        if (@function_exists('recode_string')) {
            $PMA_recoding_engine = PMA_CHARSET_RECODE;
        } else {
            $PMA_recoding_engine = PMA_CHARSET_NONE;

            require_once './libraries/header.inc.php';
            echo $strCantUseRecodeIconv;
            require_once './libraries/footer.inc.php';
            exit;
        }
    } else {
        if (@function_exists('iconv')) {
            if ((@stristr(PHP_OS, 'AIX')) && (@strcasecmp(ICONV_IMPL, 'unknown') == 0) && (@strcasecmp(ICONV_VERSION, 'unknown') == 0)) {
                $PMA_recoding_engine = PMA_CHARSET_ICONV_AIX;
            } else {
                $PMA_recoding_engine = PMA_CHARSET_ICONV;
            }
        } elseif (@function_exists('libiconv')) {
            $PMA_recoding_engine = PMA_CHARSET_LIBICONV;
        } elseif (@function_exists('recode_string')) {
            $PMA_recoding_engine = PMA_CHARSET_RECODE;
        } else {
            $PMA_recoding_engine = PMA_CHARSET_NONE;

            require_once './libraries/header.inc.php';
            echo $strCantUseRecodeIconv;
            require_once './libraries/footer.inc.php';
            exit;
        }
    }
} else {
    $PMA_recoding_engine         = PMA_CHARSET_NONE;
}

/* Load AIX iconv wrapper if needed */
if ($PMA_recoding_engine == PMA_CHARSET_ICONV_AIX) {
    require_once './libraries/iconv_wrapper.lib.php';
}

/**
 * Converts encoding of text according to current settings.
 *
 * @param   string   what to convert
 *
 * @return  string   converted text
 *
 * @global  array    the configuration array
 * @global  boolean  whether recoding is allowed or not
 * @global  string   the current charset
 * @global  array    the charset to convert to
 *
 * @access  public
 *
 * @author  nijel
 */
function PMA_convert_charset($what) {
    global $cfg, $charset, $convcharset;

    if (!(isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding'] )
        || $convcharset == $charset) { // rabus: if input and output charset are the same, we don't have to do anything...
        return $what;
    } else {
        switch ($GLOBALS['PMA_recoding_engine']) {
            case PMA_CHARSET_RECODE:
                return recode_string($charset . '..'  . $convcharset, $what);
            case PMA_CHARSET_ICONV:
                return iconv($charset, $convcharset . $cfg['IconvExtraParams'], $what);
            case PMA_CHARSET_ICONV_AIX:
                return PMA_aix_iconv_wrapper($charset, $convcharset . $cfg['IconvExtraParams'], $what);
            case PMA_CHARSET_LIBICONV:
                return libiconv($charset, $convcharset . $GLOBALS['cfg']['IconvExtraParams'], $what);
            default:
                return $what;
        }
    }
} //  end of the "PMA_convert_charset()" function

/**
 * Converts encoding of text according to parameters with detected
 * conversion function.
 *
 * @param   string   source charset
 * @param   string   target charset
 * @param   string   what to convert
 *
 * @return  string   converted text
 *
 * @access  public
 *
 * @author  nijel
 */
function PMA_convert_string($src_charset, $dest_charset, $what) {
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
        case PMA_CHARSET_LIBICONV:
            return libiconv($src_charset, $dest_charset, $what);
        default:
            return $what;
    }
} //  end of the "PMA_convert_string()" function


/**
 * Converts encoding of file according to parameters with detected
 * conversion function. The old file will be unlinked and new created and
 * its file name is returned.
 *
 * @param   string   source charset
 * @param   string   target charset
 * @param   string   file to convert
 *
 * @return  string   new temporay file
 *
 * @access  public
 *
 * @author  nijel
 */
function PMA_convert_file($src_charset, $dest_charset, $file) {
    switch ($GLOBALS['PMA_recoding_engine']) {
        case PMA_CHARSET_RECODE:
        case PMA_CHARSET_ICONV:
        case PMA_CHARSET_LIBICONV:
            $tmpfname = tempnam('', 'PMA_convert_file');
            $fin      = fopen($file, 'r');
            $fout     = fopen($tmpfname, 'w');
            if ($GLOBALS['PMA_recoding_engine'] == PMA_CHARSET_RECODE) {
                recode_file($src_charset . '..'  . $dest_charset, $fin, $fout);
            } else {
                while (!feof($fin)) {
                    $line = fgets($fin, 4096);
                    if ($GLOBALS['PMA_recoding_engine'] == PMA_CHARSET_ICONV) {
                        $dist = iconv($src_charset, $dest_charset . $GLOBALS['cfg']['IconvExtraParams'], $line);
                    } elseif ($GLOBALS['PMA_recoding_engine'] == PMA_CHARSET_ICONV_AIX) {
                        $dist = PMA_aix_iconv_wrapper($src_charset, $dest_charset . $GLOBALS['cfg']['IconvExtraParams'], $line);
                    } else {
                        $dist = libiconv($src_charset, $dest_charset . $GLOBALS['cfg']['IconvExtraParams'], $line);
                    }
                    fputs($fout, $dist);
                } // end while
            }
            fclose($fin);
            fclose($fout);
            unlink($file);

            return $tmpfname;
        default:
            return $file;
    }
} //  end of the "PMA_convert_file()" function

?>
