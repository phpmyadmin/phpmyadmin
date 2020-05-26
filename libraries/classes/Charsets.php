<?php
/**
 * MySQL charset metadata and manipulations
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;
use const SORT_STRING;
use function array_keys;
use function count;
use function explode;
use function is_string;
use function ksort;

/**
 * Class used to manage MySQL charsets
 */
class Charsets
{
    /**
     * MySQL charsets map
     *
     * @var array
     */
    public static $mysqlCharsetMap = [
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
    ];

    /**
     * The charset for the server
     *
     * @var Charset|null
     */
    private static $serverCharset = null;

    /** @var array<string, Charset> */
    private static $charsets = [];

    /** @var array<string, array<string, Collation>> */
    private static $collations = [];

    /**
     * Loads charset data from the server
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     */
    private static function loadCharsets(DatabaseInterface $dbi, bool $disableIs): void
    {
        /* Data already loaded */
        if (count(self::$charsets) > 0) {
            return;
        }

        if ($disableIs) {
            $sql = 'SHOW CHARACTER SET';
        } else {
            $sql = 'SELECT `CHARACTER_SET_NAME` AS `Charset`,'
                . ' `DEFAULT_COLLATE_NAME` AS `Default collation`,'
                . ' `DESCRIPTION` AS `Description`,'
                . ' `MAXLEN` AS `Maxlen`'
                . ' FROM `information_schema`.`CHARACTER_SETS`';
        }
        $res = $dbi->query($sql);

        self::$charsets = [];
        while ($row = $dbi->fetchAssoc($res)) {
            self::$charsets[$row['Charset']] = Charset::fromServer($row);
        }
        $dbi->freeResult($res);

        ksort(self::$charsets, SORT_STRING);
    }

    /**
     * Loads collation data from the server
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     */
    private static function loadCollations(DatabaseInterface $dbi, bool $disableIs): void
    {
        /* Data already loaded */
        if (count(self::$collations) > 0) {
            return;
        }

        if ($disableIs) {
            $sql = 'SHOW COLLATION';
        } else {
            $sql = 'SELECT `COLLATION_NAME` AS `Collation`,'
                . ' `CHARACTER_SET_NAME` AS `Charset`,'
                . ' `ID` AS `Id`,'
                . ' `IS_DEFAULT` AS `Default`,'
                . ' `IS_COMPILED` AS `Compiled`,'
                . ' `SORTLEN` AS `Sortlen`'
                . ' FROM `information_schema`.`COLLATIONS`';
        }
        $res = $dbi->query($sql);

        self::$collations = [];
        while ($row = $dbi->fetchAssoc($res)) {
            self::$collations[$row['Charset']][$row['Collation']] = Collation::fromServer($row);
        }
        $dbi->freeResult($res);

        foreach (array_keys(self::$collations) as $charset) {
            ksort(self::$collations[$charset], SORT_STRING);
        }
    }

     /**
      * Get current server charset
      *
      * @param DatabaseInterface $dbi       DatabaseInterface instance
      * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
      */
    public static function getServerCharset(DatabaseInterface $dbi, bool $disableIs): Charset
    {
        if (self::$serverCharset !== null) {
            return self::$serverCharset;
        }
        self::loadCharsets($dbi, $disableIs);
        $serverCharset = $dbi->getVariable('character_set_server');
        if (! is_string($serverCharset)) {// MySQL 5.7.8 fallback, issue #15614
            $serverCharset = $dbi->fetchValue('SELECT @@character_set_server;');
        }
        self::$serverCharset = self::$charsets[$serverCharset];

        return self::$serverCharset;
    }

    /**
     * Get all server charsets
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     *
     * @return array
     */
    public static function getCharsets(DatabaseInterface $dbi, bool $disableIs): array
    {
        self::loadCharsets($dbi, $disableIs);

        return self::$charsets;
    }

    /**
     * Get all server collations
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     *
     * @return array
     */
    public static function getCollations(DatabaseInterface $dbi, bool $disableIs): array
    {
        self::loadCollations($dbi, $disableIs);

        return self::$collations;
    }

    /**
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     * @param string|null       $name      Collation name
     */
    public static function findCollationByName(DatabaseInterface $dbi, bool $disableIs, ?string $name): ?Collation
    {
        $pieces = explode('_', (string) $name);
        if ($pieces === false || ! isset($pieces[0])) {
            return null;
        }
        $charset = $pieces[0];
        $collations = self::getCollations($dbi, $disableIs);

        return $collations[$charset][$name] ?? null;
    }
}
