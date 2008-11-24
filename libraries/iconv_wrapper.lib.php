<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
# GNU iconv code set to IBM AIX libiconv code set table
# Keys of this table should be in lowercase, and searches should be performed using lowercase!
$gnu_iconv_to_aix_iconv_codepage_map = array (
    // "iso-8859-[1-9]" --> "ISO8859-[1-9]" according to http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/libs/basetrf2/setlocale.htm
    'iso-8859-1' => 'ISO8859-1',
    'iso-8859-2' => 'ISO8859-2',
    'iso-8859-3' => 'ISO8859-3',
    'iso-8859-4' => 'ISO8859-4',
    'iso-8859-5' => 'ISO8859-5',
    'iso-8859-6' => 'ISO8859-6',
    'iso-8859-7' => 'ISO8859-7',
    'iso-8859-8' => 'ISO8859-8',
    'iso-8859-9' => 'ISO8859-9',

    // "big5" --> "IBM-eucTW" according to http://kadesh.cepba.upc.es/mancpp/classref/ref/ITranscoder_DSC.htm
    'big5' => 'IBM-eucTW',

    // Other mappings corresponding to the phpMyAdmin dropdown box when using the AllowAnywhereRecoding feature
    'euc-jp' => 'IBM-eucJP',
    'koi8-r' => 'IBM-eucKR',
    'ks_c_5601-1987' => 'KSC5601.1987-0',
    'tis-620' => 'TIS-620',
    'utf-8' => 'UTF-8'
);

/**
 * Wrapper around IBM AIX iconv(), whose character set naming differs
 * from the GNU version of iconv().
 *
 * @param   string   input character set
 * @param   string   output character set
 * @param   string   the string to convert
 *
 * @return  mixed    converted string or FALSE on failure
 *
 * @access  public
 *
 * @author  bwiberg  Björn Wiberg <Bjorn.Wiberg@its.uu.se>
 */
function PMA_aix_iconv_wrapper($in_charset, $out_charset, $str) {

    global $gnu_iconv_to_aix_iconv_codepage_map;

    // Check for transliteration argument at the end of output character set name
    $translit_search = strpos(strtolower($out_charset), '//translit');
    $using_translit = (!($translit_search === FALSE));

    // Extract "plain" output character set name (without any transliteration argument)
    $out_charset_plain = ($using_translit ? substr($out_charset, 0, $translit_search) : $out_charset);

    // Transform name of input character set (if found)
    if (array_key_exists(strtolower($in_charset), $gnu_iconv_to_aix_iconv_codepage_map)) {
        $in_charset = $gnu_iconv_to_aix_iconv_codepage_map[strtolower($in_charset)];
    }

    // Transform name of "plain" output character set (if found)
    if (array_key_exists(strtolower($out_charset_plain), $gnu_iconv_to_aix_iconv_codepage_map)) {
        $out_charset_plain = $gnu_iconv_to_aix_iconv_codepage_map[strtolower($out_charset_plain)];
    }

    // Add transliteration argument again (exactly as specified by user) if used
    // Build the output character set name that we will use
    $out_charset = ($using_translit ? $out_charset_plain . substr($out_charset, $translit_search) : $out_charset_plain);

    // NOTE: Transliteration not supported; we will use the "plain" output character set name
    $out_charset = $out_charset_plain;

    // Call iconv() with the possibly modified parameters
    $result = iconv($in_charset, $out_charset, $str);
    return $result;
} //  end of the "PMA_aix_iconv_wrapper()" function

?>
