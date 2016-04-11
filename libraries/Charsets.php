<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * MySQL charset metadata and manipulations
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * Class used to manage MySQL charsets
 *
 * @package PhpMyAdmin
 */
class Charsets
{

    /**
     * MySQL charsets map
     *
     * @var array
     */
    public static $mysql_charset_map = array(
        'big5'         => 'big5',
        'cp-866'       => 'cp866',
        'euc-jp'       => 'ujis',
        'euc-kr'       => 'euckr',
        'gb2312'       => 'gb2312',
        'gbk'          => 'gbk',
        'iso-8859-1'   => 'latin1',
        'iso-8859-2'   => 'latin2',
        'iso-8859-7'   => 'greek',
        'iso-8859-8'   => 'hebrew',
        'iso-8859-8-i' => 'hebrew',
        'iso-8859-9'   => 'latin5',
        'iso-8859-13'  => 'latin7',
        'iso-8859-15'  => 'latin1',
        'koi8-r'       => 'koi8r',
        'shift_jis'    => 'sjis',
        'tis-620'      => 'tis620',
        'utf-8'        => 'utf8',
        'windows-1250' => 'cp1250',
        'windows-1251' => 'cp1251',
        'windows-1252' => 'latin1',
        'windows-1256' => 'cp1256',
        'windows-1257' => 'cp1257',
    );

    private static $_loaded = false;
    private static $_mysql_charsets = array();
    private static $_mysql_charsets_descriptions = array();
    private static $_mysql_charsets_available = array();
    private static $_mysql_collations = array();
    private static $_mysql_default_collations = array();
    private static $_mysql_collations_available = array();

    /**
     * Loads charset data from the MySQL server.
     *
     * @return void
     */
    public static function loadCharsets()
    {
        /* Data already loaded */
        if (self::$_loaded) {
            return;
        }

        $sql = 'SELECT * FROM information_schema.CHARACTER_SETS';
        $res = $GLOBALS['dbi']->query($sql);

        self::$_mysql_charsets = array();
        while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
            self::$_mysql_charsets[] = $row['CHARACTER_SET_NAME'];
            self::$_mysql_charsets_descriptions[$row['CHARACTER_SET_NAME']]
                = $row['DESCRIPTION'];
        }
        $GLOBALS['dbi']->freeResult($res);

        sort(self::$_mysql_charsets, SORT_STRING);

        self::$_mysql_collations = array_flip(self::$_mysql_charsets);

        $sql = 'SELECT * FROM information_schema.COLLATIONS';
        $res = $GLOBALS['dbi']->query($sql);
        while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
            $char_set_name = $row['CHARACTER_SET_NAME'];
            if (! is_array(self::$_mysql_collations[$char_set_name])) {
                self::$_mysql_collations[$char_set_name] = array($row['COLLATION_NAME']);
            } else {
                self::$_mysql_collations[$char_set_name][] = $row['COLLATION_NAME'];
            }
            if ($row['IS_DEFAULT'] == 'Yes' || $row['IS_DEFAULT'] == '1') {
                self::$_mysql_default_collations[$char_set_name]
                    = $row['COLLATION_NAME'];
            }
            self::$_mysql_collations_available[$row['COLLATION_NAME']] = true;
            self::$_mysql_charsets_available[$char_set_name]
                = !empty(self::$_mysql_charsets_available[$char_set_name])
                || !empty(self::$_mysql_collations_available[$row['COLLATION_NAME']]);
        }
        $GLOBALS['dbi']->freeResult($res);

        foreach (self::$_mysql_collations as $key => $value) {
            sort(self::$_mysql_collations[$key], SORT_STRING);
        }

        self::$_loaded = true;
    }

    public static function getMySQLCharsets()
    {
        self::loadCharsets();
        return self::$_mysql_charsets;
    }

    public static function getMySQLCharsetsDescriptions()
    {
        self::loadCharsets();
        return self::$_mysql_charsets_descriptions;
    }

    public static function getMySQLCharsetsAvailable()
    {
        self::loadCharsets();
        return self::$_mysql_charsets_available;
    }

    public static function getMySQLCollations()
    {
        self::loadCharsets();
        return self::$_mysql_collations;
    }

    public static function getMySQLCollationsDefault()
    {
        self::loadCharsets();
        return self::$_mysql_default_collations;
    }

    public static function getMySQLCollationsAvailable()
    {
        self::loadCharsets();
        return self::$_mysql_collations_available;
    }
}
