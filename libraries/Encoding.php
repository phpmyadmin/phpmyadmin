<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold the PMA\libraries\Encoding class
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

use PMA\libraries\config\ConfigFile;

/**
 * Encoding conversion helper class
 *
 * @package PhpMyAdmin
 */
class Encoding
{
    /**
     * None encoding conversion engine
     *
     * @var int
     */

    const ENGINE_NONE = 0;
    /**
     * iconv encoding conversion engine
     *
     * @var int
     */
    const ENGINE_ICONV = 1;

    /**
     * recode encoding conversion engine
     *
     * @var int
     */
    const ENGINE_RECODE = 2;

    /**
     * mbstring encoding conversion engine
     *
     * @var int
     */
    const ENGINE_MB = 3;

    /**
     * Chosen encoding engine
     *
     * @var int
     */
    private static $_engine = null;

    /**
     * Map of conversion engine configurations
     *
     * Each entry contains:
     *
     * - function to detect
     * - engine contant
     * - extension name to warn when missing
     *
     * @var array
     */
    private static $_enginemap = array(
        'iconv' => array('iconv', self::ENGINE_ICONV, 'iconv'),
        'recode' => array('recode_string', self::ENGINE_RECODE, 'recode'),
        'mb' => array('mb_convert_encoding', self::ENGINE_MB, 'mbstring'),
        'none' => array('isset', self::ENGINE_NONE, ''),
    );

    /**
     * Order of automatic detection of engines
     *
     * @var array
     */
    private static $_engineorder = array(
        'mb', 'iconv', 'recode',
    );

    /**
     * Kanji encodings list
     *
     * @var string
     */
    private static $_kanji_encodings = 'ASCII,SJIS,EUC-JP,JIS';

    /**
     * Initializes encoding engine detecting available backends.
     *
     * @return void
     */
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

    /**
     * Setter for engine. Use with caution, mostly useful for testing.
     *
     * @return void
     */
    public static function setEngine($engine)
    {
        self::$_engine = $engine;
    }

    /**
     * Checks whether there is any charset conversion supported
     *
     * @return bool
     */
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
     *
     * @return bool
     */
    public static function canConvertKanji()
    {
        return (
            $GLOBALS['lang'] == 'ja' &&
            function_exists('mb_convert_encoding')
        );
    }

    /**
     * Setter for Kanji encodings. Use with caution, mostly useful for testing.
     *
     * @return string
     */
    public static function getKanjiEncodings()
    {
        return self::$_kanji_encodings;
    }

    /**
     * Setter for Kanji encodings. Use with caution, mostly useful for testing.
     *
     * @return void
     */
    public static function setKanjiEncodings($value)
    {
        self::$_kanji_encodings = $value;
    }

    /**
     * Reverses SJIS & EUC-JP position in the encoding codes list
     *
     * @return void
     */
    public static function kanjiChangeOrder()
    {
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
        $tmpfname = tempnam(ConfigFile::getDefaultTempDirectory(), $enc);
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
        return '<ul><li>'
            . '<input type="radio" name="knjenc" value="" checked="checked" '
            . 'id="kj-none" />'
            . '<label for="kj-none">'
            /* l10n: This is currently used only in Japanese locales */
            . _pgettext('None encoding conversion', 'None')
            . '</label>'
            . '<input type="radio" name="knjenc" value="EUC-JP" id="kj-euc" />'
            . '<label for="kj-euc">EUC</label>'
            . '<input type="radio" name="knjenc" value="SJIS" id="kj-sjis" />'
            . '<label for="kj-sjis">SJIS</label>'
            . '</li>'
            . '<li>'
            . '<input type="checkbox" name="xkana" value="kana" id="kj-kana" />'
            . '<label for="kj-kana">'
            /* l10n: This is currently used only in Japanese locales */
            . __('Convert to Kana')
            . '</label><br />'
            . '</li></ul>';
    }

    public static function listEncodings()
    {
        if (is_null(self::$_engine)) {
            self::initEngine();
        }
        /* Most engines do not support listing */
        if (self::$_engine != self::ENGINE_MB) {
            return $GLOBALS['cfg']['AvailableCharsets'];
        }

        return array_intersect(
            array_map('strtolower', mb_list_encodings()),
            $GLOBALS['cfg']['AvailableCharsets']
        );
    }
}
