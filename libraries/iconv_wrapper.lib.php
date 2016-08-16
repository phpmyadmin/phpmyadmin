<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Iconv wrapper for AIX
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * GNU iconv code set to IBM AIX libiconv code set table
 * Keys of this table should be in lowercase,
 * and searches should be performed using lowercase!
 */
$gnu_iconv_to_aix_iconv_codepage_map = array (
    // "iso-8859-[1-9]" --> "ISO8859-[1-9]" according to
    // http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/libs/basetrf2/setlocale.htm
    'iso-8859-1' => 'ISO8859-1',
    'iso-8859-2' => 'ISO8859-2',
    'iso-8859-3' => 'ISO8859-3',
    'iso-8859-4' => 'ISO8859-4',
    'iso-8859-5' => 'ISO8859-5',
    'iso-8859-6' => 'ISO8859-6',
    'iso-8859-7' => 'ISO8859-7',
    'iso-8859-8' => 'ISO8859-8',
    'iso-8859-9' => 'ISO8859-9',

    // "big5" --> "IBM-eucTW" according to
    // http://kadesh.cepba.upc.es/mancpp/classref/ref/ITranscoder_DSC.htm
    'big5' => 'IBM-eucTW',

    // Other mappings corresponding to the phpMyAdmin dropdown box when using the
    // charset conversion feature
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
 * @param string $in_charset  input character set
 * @param string $out_charset output character set
 * @param string $str         the string to convert
 *
 * @return mixed    converted string or false on failure
 *
 * @access  public
 *
 */
function PMA_convertAIXIconv($in_charset, $out_charset, $str)
{
    list($in_charset, $out_charset) = PMA_convertAIXMapCharsets(
        $in_charset, $out_charset
    );
    // Call iconv() with the possibly modified parameters
    return iconv($in_charset, $out_charset, $str);
} //  end of the "PMA_convertAIXIconv()" function

/**
 * Maps input and output character set names to corresponding AIX ones
 *
 * @param string $in_charset  input character set
 * @param string $out_charset output character set
 *
 * @return array array of mapped input and output character set names
 */
function PMA_convertAIXMapCharsets($in_charset, $out_charset)
{
    global $gnu_iconv_to_aix_iconv_codepage_map;

    // Check for transliteration argument at the end of output character set name
    $translit_search = mb_strpos(
        mb_strtolower($out_charset),
        '//translit'
    );
    $using_translit = (!($translit_search === false));

    // Extract "plain" output character set name
    // (without any transliteration argument)
    $out_charset_plain = ($using_translit
        ? mb_substr($out_charset, 0, $translit_search)
        : $out_charset);

    // Transform name of input character set (if found)
    $in_charset_exisits = array_key_exists(
        mb_strtolower($in_charset),
        $gnu_iconv_to_aix_iconv_codepage_map
    );
    if ($in_charset_exisits) {
        $in_charset = $gnu_iconv_to_aix_iconv_codepage_map[
            mb_strtolower($in_charset)
        ];
    }

    // Transform name of "plain" output character set (if found)
    $out_charset_plain_exists = array_key_exists(
        mb_strtolower($out_charset_plain),
        $gnu_iconv_to_aix_iconv_codepage_map
    );
    if ($out_charset_plain_exists) {
        $out_charset_plain = $gnu_iconv_to_aix_iconv_codepage_map[
            mb_strtolower($out_charset_plain)
        ];
    }

    // Add transliteration argument again (exactly as specified by user) if used
    // Build the output character set name that we will use
    /* Not needed because always overwritten
    $out_charset = ($using_translit
        ? $out_charset_plain . mb_substr($out_charset, $translit_search)
        : $out_charset_plain);
    */

    // NOTE: Transliteration not supported; we will use the "plain"
    // output character set name
    $out_charset = $out_charset_plain;

    return array($in_charset, $out_charset);
}

