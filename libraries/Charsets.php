<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * MySQL charset metadata and manipulations
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

use PMA\libraries\Util;

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

    /**
     * Loads charset data from the MySQL server.
     *
     * @return void
     */
    public static function loadCharsets()
    {
        /* Cache already exists */
        if (Util::cacheExists('mysql_charsets')) {
            return;
        }

        $sql = 'SELECT * FROM information_schema.CHARACTER_SETS';
        $res = $GLOBALS['dbi']->query($sql);

        $mysql_charsets = array();
        while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
            $mysql_charsets[] = $row['CHARACTER_SET_NAME'];
            // never used
            //$mysql_charsets_maxlen[$row['Charset']] = $row['Maxlen'];
            $mysql_charsets_descriptions[$row['CHARACTER_SET_NAME']]
                = $row['DESCRIPTION'];
        }
        $GLOBALS['dbi']->freeResult($res);

        sort($mysql_charsets, SORT_STRING);

        $mysql_collations = array_flip($mysql_charsets);
        $mysql_default_collations = $mysql_charsets_available = $mysql_collations_available = array();

        $sql = 'SELECT * FROM information_schema.COLLATIONS';
        $res = $GLOBALS['dbi']->query($sql);
        while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
            $char_set_name = $row['CHARACTER_SET_NAME'];
            if (! is_array($mysql_collations[$char_set_name])) {
                $mysql_collations[$char_set_name] = array($row['COLLATION_NAME']);
            } else {
                $mysql_collations[$char_set_name][] = $row['COLLATION_NAME'];
            }
            if ($row['IS_DEFAULT'] == 'Yes' || $row['IS_DEFAULT'] == '1') {
                $mysql_default_collations[$char_set_name]
                    = $row['COLLATION_NAME'];
            }
            //$mysql_collations_available[$row['Collation']]
            //    = ! isset($row['Compiled']) || $row['Compiled'] == 'Yes';
            $mysql_collations_available[$row['COLLATION_NAME']] = true;
            $mysql_charsets_available[$char_set_name]
                = !empty($mysql_charsets_available[$char_set_name])
                || !empty($mysql_collations_available[$row['COLLATION_NAME']]);
        }
        $GLOBALS['dbi']->freeResult($res);
        unset($res, $row);

        foreach ($mysql_collations as $key => $value) {
            sort($mysql_collations[$key], SORT_STRING);
        }
        unset($key, $value);

        Util::cacheSet(
            'mysql_charsets', $mysql_charsets
        );
        Util::cacheSet(
            'mysql_charsets_descriptions', $mysql_charsets_descriptions
        );
        Util::cacheSet(
            'mysql_charsets_available', $mysql_charsets_available
        );
        Util::cacheSet(
            'mysql_collations', $mysql_collations
        );
        Util::cacheSet(
            'mysql_default_collations', $mysql_default_collations
        );
        Util::cacheSet(
            'mysql_collations_available', $mysql_collations_available
        );
    }

    public static function getMySQLCharsets()
    {
        self::loadCharsets();
        return Util::cacheGet('mysql_charsets');
    }

    public static function getMySQLCharsetsDescriptions()
    {
        self::loadCharsets();
        return Util::cacheGet('mysql_charsets_descriptions');
    }

    public static function getMySQLCharsetsAvailable()
    {
        self::loadCharsets();
        return Util::cacheGet('mysql_charsets_available');
    }

    public static function getMySQLCollations()
    {
        self::loadCharsets();
        return Util::cacheGet('mysql_collations');
    }

    public static function getMySQLCollationsDefault()
    {
        self::loadCharsets();
        return Util::cacheGet('mysql_default_collations');
    }

    public static function getMySQLCollationsAvailable()
    {
        self::loadCharsets();
        return Util::cacheGet('mysql_collations_available');
    }
}
