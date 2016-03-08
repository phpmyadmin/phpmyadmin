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
        'iconv', 'recode', 'mb'
    );

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
}
