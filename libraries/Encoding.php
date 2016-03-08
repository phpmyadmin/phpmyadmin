<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold the PMA\libraries\Encoding class
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * Encoding conversion helper class
 *
 * @package PhpMyAdmin
 */
class Encoding
{
    const ENGINE_NONE = 0;
    const ENGINE_ICONV = 1;
    const ENGINE_RECODE = 2;
    const ENGINE_MB = 3;

    private static $_engine = null;

    private static $_enginemap = array(
        'iconv' => array('iconv', self::ENGINE_ICONV, 'iconv'),
        'recode' => array('recode_string', self::ENGINE_RECODE, 'recode'),
        'mb' => array('mb_convert_encoding', self::ENGINE_MB, 'mbstring'),
        'none' => array('isset', self::ENGINE_NONE, ''),
    );

    private static $_engineorder = array(
        'mb', 'iconv', 'recode',
    );

    private static $_kanji_encodings = null;

    public static function initEngine()
    {
        $engine = 'auto';
        if (isset($GLOBALS['cfg']['RecodingEngine'])) {
            $engine = $GLOBALS['cfg']['RecodingEngine'];
        }

        /* Use user configuration */
        if (isset(self::$_enginemap[$engine])) {
            if (@function_exists(self::$_enginemap[$engine][0])) {
                self::$_engine = self::$_enginemap[$engine][1];
                return;
            } else {
                PMA_warnMissingExtension(self::$_enginemap[$engine][2]);
            }
        }

        /* Autodetection */
        foreach (self::$_engineorder as $engine) {
            if (@function_exists(self::$_enginemap[$engine][0])) {
                self::$_engine = self::$_enginemap[$engine][1];
                return;
            }
        }

        /* Fallback to none conversion */
        self::$_engine = self::ENGINE_NONE;
    }

    public static function setEngine($engine)
    {
        self::$_engine = $engine;
    }

    public static function isSupported()
    {
        if (is_null(self::$_engine)) {
            self::initEngine();
        }
        return self::$_engine != self::ENGINE_NONE;
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
    public static function convertString($src_charset, $dest_charset, $what)
    {
        if ($src_charset == $dest_charset) {
            return $what;
        }
        if (is_null(self::$_engine)) {
            self::initEngine();
        }
        switch (self::$_engine) {
            case self::ENGINE_RECODE:
                return recode_string(
                    $src_charset . '..'  . $dest_charset,
                    $what
                );
            case self::ENGINE_ICONV:
                return iconv(
                    $src_charset,
                    $dest_charset .
                    (isset($GLOBALS['cfg']['IconvExtraParams']) ? $GLOBALS['cfg']['IconvExtraParams'] : ''),
                    $what
                );
            case self::ENGINE_MB:
                return mb_convert_encoding(
                    $what,
                    $dest_charset,
                    $src_charset
                );
            default:
                return $what;
        }
    }

    /**
     * Detects whether Kanji encoding is available
     */
    public static function canConvertKanji()
    {
        return (
            $GLOBALS['lang'] == 'ja' &&
            function_exists('mb_convert_encoding')
        );
    }

    public static function getKanjiEncodings()
    {
        return self::$_kanji_encodings;
    }

    public static function setKanjiEncodings($value)
    {
        self::$_kanji_encodings = $value;
    }

    /**
     * Gets the php internal encoding codes and sets the available encoding
     * codes list
     * 2002/1/4 by Y.Kawada
     *
     * @return void
     */
    public static function kanjiCheckEncoding()
    {
        if (mb_internal_encoding() == 'EUC-JP') {
            self::$_kanji_encodings = 'ASCII,EUC-JP,SJIS,JIS';
        } else {
            self::$_kanji_encodings = 'ASCII,SJIS,EUC-JP,JIS';
        }
    }

    /**
     * Reverses SJIS & EUC-JP position in the encoding codes list
     *
     * @return void
     */
    public static function kanjiChangeOrder()
    {
        if (is_null(self::$_kanji_encodings)) {
            self::kanjiCheckEncoding();
        }
        $parts = explode(',', self::$_kanji_encodings);
        if ($parts[1] == 'EUC-JP') {
            self::$_kanji_encodings = 'ASCII,SJIS,EUC-JP,JIS';
        } else {
            self::$_kanji_encodings = 'ASCII,EUC-JP,SJIS,JIS';
        }
    }

    /**
     * Kanji string encoding convert
     *
     * @param string $str  the string to convert
     * @param string $enc  the destination encoding code
     * @param string $kana set 'kana' convert to JIS-X208-kana
     *
     * @return string   the converted string
     */
    public static function kanjiStrConv($str, $enc, $kana)
    {
        if ($enc == '' && $kana == '') {
            return $str;
        }

        if (is_null(self::$_kanji_encodings)) {
            self::kanjiCheckEncoding();
        }

        $string_encoding = mb_detect_encoding($str, self::$_kanji_encodings);
        if ($string_encoding === false) {
            $string_encoding = 'utf-8';
        }

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
    }


    /**
     * Kanji file encoding convert
     *
     * @param string $file the name of the file to convert
     * @param string $enc  the destination encoding code
     * @param string $kana set 'kana' convert to JIS-X208-kana
     *
     * @return string   the name of the converted file
     */
    public static function kanjiFileConv($file, $enc, $kana)
    {
        if ($enc == '' && $kana == '') {
            return $file;
        }

        $tmpfname = tempnam('', $enc);
        $fpd      = fopen($tmpfname, 'wb');
        $fps      = fopen($file, 'r');
        self::kanjiChangeOrder();
        while (!feof($fps)) {
            $line = fgets($fps, 4096);
            $dist = self::kanjiStrConv($line, $enc, $kana);
            fputs($fpd, $dist);
        } // end while
        self::kanjiChangeOrder();
        fclose($fps);
        fclose($fpd);
        unlink($file);

        return $tmpfname;
    }

    /**
     * Defines radio form fields to switch between encoding modes
     *
     * @return string   xhtml code for the radio controls
     */
    public static function kanjiEncodingForm()
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
    }
}
