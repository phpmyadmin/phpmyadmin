<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions for kanji-encoding convert (available only with japanese
 * language)
 *
 * PHP4 configure requirements:
 *     --enable-mbstring --enable-mbstr-enc-trans --enable-mbregex
 *
 * 2002/2/22 - by Yukihiro Kawada <kawada@den.fujifilm.co.jp>
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Gets the php internal encoding codes and sets the available encoding
 * codes list
 * 2002/1/4 by Y.Kawada
 *
 * @global  string $kanji_encoding_list the available encoding codes list
 *
 * @return boolean  always true
 */
function PMA_Kanji_checkEncoding()
{
    global $kanji_encoding_list;

    $internal_enc = mb_internal_encoding();
    if ($internal_enc == 'EUC-JP') {
        $kanji_encoding_list = 'ASCII,EUC-JP,SJIS,JIS';
    } else {
        $kanji_encoding_list = 'ASCII,SJIS,EUC-JP,JIS';
    }

    return true;
} // end of the 'PMA_Kanji_checkEncoding' function


/**
 * Reverses SJIS & EUC-JP position in the encoding codes list
 * 2002/1/4 by Y.Kawada
 *
 * @global  string $kanji_encoding_list the available encoding codes list
 *
 * @return boolean  always true
 */
function PMA_Kanji_changeOrder()
{
    global $kanji_encoding_list;

    $parts = explode(',', $kanji_encoding_list);
    if ($parts[1] == 'EUC-JP') {
        $kanji_encoding_list = 'ASCII,SJIS,EUC-JP,JIS';
    } else {
        $kanji_encoding_list = 'ASCII,EUC-JP,SJIS,JIS';
    }

    return true;
} // end of the 'PMA_Kanji_changeOrder' function


/**
 * Kanji string encoding convert
 * 2002/1/4 by Y.Kawada
 *
 * @param string $str  the string to convert
 * @param string $enc  the destination encoding code
 * @param string $kana set 'kana' convert to JIS-X208-kana
 *
 * @global  string $kanji_encoding_list the available encoding codes list
 *
 * @return string   the converted string
 */
function PMA_Kanji_strConv($str, $enc, $kana)
{
    global $kanji_encoding_list;

    if ($enc == '' && $kana == '') {
        return $str;
    }
    $string_encoding = mb_detect_encoding($str, $kanji_encoding_list);

    if ($kana == 'kana') {
        $dist = mb_convert_kana($str, 'KV', $string_encoding);
        $str  = $dist;
    }
    if ($string_encoding != $enc && $enc != '') {
        $dist = mb_convert_encoding($str, $enc, $string_encoding);
    } else {
        $dist = $str;
    }
    return $dist;
} // end of the 'PMA_Kanji_strConv' function


/**
 * Kanji file encoding convert
 * 2002/1/4 by Y.Kawada
 *
 * @param string $file the name of the file to convert
 * @param string $enc  the destination encoding code
 * @param string $kana set 'kana' convert to JIS-X208-kana
 *
 * @return string   the name of the converted file
 */
function PMA_Kanji_fileConv($file, $enc, $kana)
{
    if ($enc == '' && $kana == '') {
        return $file;
    }

    $tmpfname = tempnam('', $enc);
    $fpd      = fopen($tmpfname, 'wb');
    $fps      = fopen($file, 'r');
    PMA_Kanji_changeOrder();
    while (!feof($fps)) {
        $line = fgets($fps, 4096);
        $dist = PMA_Kanji_strConv($line, $enc, $kana);
        fputs($fpd, $dist);
    } // end while
    PMA_Kanji_changeOrder();
    fclose($fps);
    fclose($fpd);
    unlink($file);

    return $tmpfname;
} // end of the 'PMA_Kanji_fileConv' function


/**
 * Defines radio form fields to switch between encoding modes
 * 2002/1/4 by Y.Kawada
 *
 * @return string   xhtml code for the radio controls
 */
function PMA_Kanji_encodingForm()
{
    return "\n"
        . '<ul>' . "\n" . '<li>'
        . '<input type="radio" name="knjenc" value="" checked="checked" '
        . 'id="kj-none" />'
        . '<label for="kj-none">'
        /* l10n: This is currently used only in Japanese locales */
        . _pgettext('None encoding conversion', 'None')
        . "</label>\n"
        . '<input type="radio" name="knjenc" value="EUC-JP" id="kj-euc" />'
        . '<label for="kj-euc">EUC</label>' . "\n"
        . '<input type="radio" name="knjenc" value="SJIS" id="kj-sjis" />'
        . '<label for="kj-sjis">SJIS</label>' . "\n"
        . '</li>' . "\n" . '<li>'
        . '<input type="checkbox" name="xkana" value="kana" id="kj-kana" />'
        . "\n"
        . '<label for="kj-kana">'
        /* l10n: This is currently used only in Japanese locales */
        . __('Convert to Kana')
        . '</label><br />'
        . "\n"
        . '</li>' . "\n" . '</ul>'
        ;
} // end of the 'PMA_Kanji_encodingForm' function


PMA_Kanji_checkEncoding();

?>
