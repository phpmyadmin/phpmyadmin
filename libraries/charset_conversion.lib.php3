<?php
/* $Id$ */


/**
 * Charset conversion functions.
 */


if (!defined('PMA_CHARSET_CONVERSION_LIB_INCLUDED')){
    define('PMA_CHARSET_CONVERSION_LIB_INCLUDED', 1);

    /**
     * Loads the recode or iconv extensions if any of it is not loaded yet
     */
    if ($cfg['AllowAnywhereRecoding'] && $allow_recoding && 
        ((PMA_PHP_INT_VERSION >= 40000 && !@ini_get('safe_mode') && @ini_get('enable_dl'))
         || (PMA_PHP_INT_VERSION > 30009 && !@get_cfg_var('safe_mode')))
        && @function_exists('dl')) {
        if (!(@extension_loaded('recode')||@extension_loaded('iconv'))) {
            if (PMA_IS_WINDOWS) {
                $suffix = '.dll';
            } else {
                $suffix = '.so';
            }
            @dl('recode'.$suffix);
            if (!@extension_loaded('recode')) {
                @dl('iconv'.$suffix);
                if (!@extension_loaded('iconv')) {
                    echo $strCantLoadRecodeIconv;
                    exit();
                }
            }
        }
    } // end load mysql extension
    // if allowed recoding, we should try to load extensions for it...

    /**
     * Converts encoding according to current settings.
     *
     * @param   mixed    what to convert (string or array of strings or object returned by mysql_fetch_field)
     *
     * @return  string   converted string or array of strings
     *
     * @access  public
     *
     * @author  nijel
     */
     function PMA_convert_display_charset($what) {
         global $cfg, $allow_recoding, $charset, $convcharset;
         if (!($cfg['AllowAnywhereRecoding'] && $allow_recoding)) {
             return $what;
         } else {
             if (is_array($what)) {
                 $result = array();
                 reset($what);
                 while(list($key,$val) = each($what)) {
//Debug:                     echo '['.$key.']='.$val.'<br>';
                      
                     if (is_string($val) || is_array($val)) {
                         if (is_string($key)) {
                             $result[PMA_convert_display_charset($key)] = PMA_convert_display_charset($val);
                         } else {
                             $result[$key] = PMA_convert_display_charset($val);
                         }
                     } else {
                         $result[$key] = $val;
                     }
                 }
                 return $result;
             } elseif (is_string($what)) {
                 if (@function_exists('iconv')) {
//Debug: echo 'PMA_convert_display_charset: '.$what.'->'.iconv($convcharset, $charset, $what)."\n<br>";
                     return iconv($convcharset, $charset, $what);
                 } else if (@function_exists('libiconv')) {
                     return iconv($convcharset, $charset, $what);
                 } else if (@function_exists('recode_string')) {
                     return recode_string($convcharset . '..'  . $charset, $what);
                 } else {
                     echo $strCantUseRecodeIconv;
                     return $what;
                 }
             } elseif (is_object($what)) {
                 // isn't it object returned from mysql_fetch_field ?
                 if (@is_string($what->name)) {
                     $what->name = PMA_convert_display_charset( $what->name );
                 }
                 if (@is_string($what->table)) {
                     $what->table = PMA_convert_display_charset( $what->table );
                 }
                 if (@is_string($what->Database)) {
                     $what->Database = PMA_convert_display_charset( $what->Database );
                 }
                 return $what;
             } else {
                 // when we don't know what it is we don't touch it...
                 return $what;
             }
         }
     }
     
    /**
     * Converts encoding of text according to current settings.
     *
     * @param   string   what to convert
     *
     * @return  string   converted text
     *
     * @access  public
     *
     * @author  nijel
     */
     function PMA_convert_charset($what) {
         global $cfg, $allow_recoding, $charset, $convcharset;
         if (!($cfg['AllowAnywhereRecoding'] && $allow_recoding)) {
            return $what;
         } else {
             if (@function_exists('iconv')) {
//Debug:                 echo 'PMA_convert_charset: '.$what.'->'.iconv($charset, $convcharset, $what)."\n<br>";
                 return iconv($charset, $convcharset, $what);
             } else if (@function_exists('libiconv')) {
                 return iconv($charset, $convcharset, $what);
             } else if (@function_exists('recode_string')) {
                 return recode_string($charset . '..'  . $convcharset, $what);
             } else {
                 echo $strCantUseRecodeIconv;
                 return $what;
             }
         }
     }
} // PMA_CHARSET_CONVERSION_LIB_INCLUDED
?>
