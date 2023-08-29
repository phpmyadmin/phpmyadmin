<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Util;

use function in_array;
use function is_string;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;

/**
 * Handles data compatibility from SQL query results
 */
class Compatibility
{
    /**
     * @param mixed[] $eachTables
     *
     * @return mixed[][]
     */
    public static function getISCompatForGetTablesFull(array $eachTables, string $eachDatabase): array
    {
        foreach ($eachTables as $tableName => $tableInfo) {
            if (! isset($eachTables[$tableName]['Type']) && isset($eachTables[$tableName]['Engine'])) {
                // pma BC, same parts of PMA still uses 'Type'
                $eachTables[$tableName]['Type'] =& $eachTables[$tableName]['Engine'];
            } elseif (! isset($eachTables[$tableName]['Engine']) && isset($eachTables[$tableName]['Type'])) {
                // old MySQL reports Type, newer MySQL reports Engine
                $eachTables[$tableName]['Engine'] =& $eachTables[$tableName]['Type'];
            }

            // Compatibility with INFORMATION_SCHEMA output
            $eachTables[$tableName]['TABLE_SCHEMA'] = $eachDatabase;
            $eachTables[$tableName]['TABLE_NAME'] =& $eachTables[$tableName]['Name'];
            $eachTables[$tableName]['ENGINE'] =& $eachTables[$tableName]['Engine'];
            $eachTables[$tableName]['VERSION'] =& $eachTables[$tableName]['Version'];
            $eachTables[$tableName]['ROW_FORMAT'] =& $eachTables[$tableName]['Row_format'];
            $eachTables[$tableName]['TABLE_ROWS'] =& $eachTables[$tableName]['Rows'];
            $eachTables[$tableName]['AVG_ROW_LENGTH'] =& $eachTables[$tableName]['Avg_row_length'];
            $eachTables[$tableName]['DATA_LENGTH'] =& $eachTables[$tableName]['Data_length'];
            $eachTables[$tableName]['MAX_DATA_LENGTH'] =& $eachTables[$tableName]['Max_data_length'];
            $eachTables[$tableName]['INDEX_LENGTH'] =& $eachTables[$tableName]['Index_length'];
            $eachTables[$tableName]['DATA_FREE'] =& $eachTables[$tableName]['Data_free'];
            $eachTables[$tableName]['AUTO_INCREMENT'] =& $eachTables[$tableName]['Auto_increment'];
            $eachTables[$tableName]['CREATE_TIME'] =& $eachTables[$tableName]['Create_time'];
            $eachTables[$tableName]['UPDATE_TIME'] =& $eachTables[$tableName]['Update_time'];
            $eachTables[$tableName]['CHECK_TIME'] =& $eachTables[$tableName]['Check_time'];
            $eachTables[$tableName]['TABLE_COLLATION'] =& $eachTables[$tableName]['Collation'];
            $eachTables[$tableName]['CHECKSUM'] =& $eachTables[$tableName]['Checksum'];
            $eachTables[$tableName]['CREATE_OPTIONS'] =& $eachTables[$tableName]['Create_options'];
            $eachTables[$tableName]['TABLE_COMMENT'] =& $eachTables[$tableName]['Comment'];

            if (
                strtoupper($eachTables[$tableName]['Comment'] ?? '') === 'VIEW'
                && $eachTables[$tableName]['Engine'] == null
            ) {
                $eachTables[$tableName]['TABLE_TYPE'] = 'VIEW';
            } elseif ($eachDatabase === 'information_schema') {
                $eachTables[$tableName]['TABLE_TYPE'] = 'SYSTEM VIEW';
            } else {
                /**
                 * @todo difference between 'TEMPORARY' and 'BASE TABLE'
                 * but how to detect?
                 */
                $eachTables[$tableName]['TABLE_TYPE'] = 'BASE TABLE';
            }
        }

        return $eachTables;
    }

    /**
     * @param mixed[] $columns
     *
     * @return mixed[]
     */
    public static function getISCompatForGetColumnsFull(array $columns, string $database, string $table): array
    {
        $ordinalPosition = 1;
        foreach ($columns as $columnName => $columnInfo) {
            // Compatibility with INFORMATION_SCHEMA output
            $columns[$columnName]['COLUMN_NAME'] =& $columns[$columnName]['Field'];
            $columns[$columnName]['COLUMN_TYPE'] =& $columns[$columnName]['Type'];
            $columns[$columnName]['COLLATION_NAME'] =& $columns[$columnName]['Collation'];
            $columns[$columnName]['IS_NULLABLE'] =& $columns[$columnName]['Null'];
            $columns[$columnName]['COLUMN_KEY'] =& $columns[$columnName]['Key'];
            $columns[$columnName]['COLUMN_DEFAULT'] =& $columns[$columnName]['Default'];
            $columns[$columnName]['EXTRA'] =& $columns[$columnName]['Extra'];
            $columns[$columnName]['PRIVILEGES'] =& $columns[$columnName]['Privileges'];
            $columns[$columnName]['COLUMN_COMMENT'] =& $columns[$columnName]['Comment'];

            $columns[$columnName]['TABLE_CATALOG'] = null;
            $columns[$columnName]['TABLE_SCHEMA'] = $database;
            $columns[$columnName]['TABLE_NAME'] = $table;
            $columns[$columnName]['ORDINAL_POSITION'] = $ordinalPosition;
            $colType = $columns[$columnName]['COLUMN_TYPE'];
            $colType = is_string($colType) ? $colType : '';
            $colTypePosComa = strpos($colType, '(');
            $colTypePosComa = $colTypePosComa !== false ? $colTypePosComa : strlen($colType);
            $columns[$columnName]['DATA_TYPE'] = substr($colType, 0, $colTypePosComa);
            /** @todo guess CHARACTER_MAXIMUM_LENGTH from COLUMN_TYPE */
            $columns[$columnName]['CHARACTER_MAXIMUM_LENGTH'] = null;
            /** @todo guess CHARACTER_OCTET_LENGTH from CHARACTER_MAXIMUM_LENGTH */
            $columns[$columnName]['CHARACTER_OCTET_LENGTH'] = null;
            $columns[$columnName]['NUMERIC_PRECISION'] = null;
            $columns[$columnName]['NUMERIC_SCALE'] = null;
            $colCollation = $columns[$columnName]['COLLATION_NAME'];
            $colCollation = is_string($colCollation) ? $colCollation : '';
            $colCollationPosUnderscore = strpos($colCollation, '_');
            $colCollationPosUnderscore = $colCollationPosUnderscore !== false
                ? $colCollationPosUnderscore
                : strlen($colCollation);
            $columns[$columnName]['CHARACTER_SET_NAME'] = substr($colCollation, 0, $colCollationPosUnderscore);

            $ordinalPosition++;
        }

        return $columns;
    }

    public static function isMySqlOrPerconaDb(): bool
    {
        $serverType = Util::getServerType();

        return $serverType === 'MySQL' || $serverType === 'Percona Server';
    }

    public static function isMariaDb(): bool
    {
        $serverType = Util::getServerType();

        return $serverType === 'MariaDB';
    }

    public static function isCompatibleRenameIndex(int $serverVersion): bool
    {
        if (self::isMySqlOrPerconaDb()) {
            return $serverVersion >= 50700;
        }

        // @see https://mariadb.com/kb/en/alter-table/#rename-indexkey
        if (self::isMariaDb()) {
            return $serverVersion >= 100502;
        }

        return false;
    }

    public static function isIntegersLengthRestricted(DatabaseInterface $dbi): bool
    {
        // MySQL made restrictions on the integer types' length from versions >= 8.0.18
        // See: https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-19.html
        $serverType = Util::getServerType();
        $serverVersion = $dbi->getVersion();

        return $serverType === 'MySQL' && $serverVersion >= 80018;
    }

    public static function supportsReferencesPrivilege(DatabaseInterface $dbi): bool
    {
        // See: https://mariadb.com/kb/en/grant/#table-privileges
        // Unused
        if ($dbi->isMariaDB()) {
            return false;
        }

        // https://dev.mysql.com/doc/refman/5.6/en/privileges-provided.html#priv_references
        // This privilege is unused before MySQL 5.6.22.
        // As of 5.6.22, creation of a foreign key constraint
        // requires at least one of the SELECT, INSERT, UPDATE, DELETE,
        // or REFERENCES privileges for the parent table.
        return $dbi->getVersion() >= 50622;
    }

    public static function isIntegersSupportLength(string $type, string $length, DatabaseInterface $dbi): bool
    {
        // MySQL Removed the Integer types' length from versions >= 8.0.18
        // except TINYINT(1).
        // See: https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-19.html
        $integerTypes = ['SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT'];
        $typeLengthNotAllowed = in_array($type, $integerTypes) || $type === 'TINYINT' && $length !== '1';

        return ! (self::isIntegersLengthRestricted($dbi) && $typeLengthNotAllowed);
    }

    /**
     * Returns whether the database server supports virtual columns
     */
    public static function isVirtualColumnsSupported(int $serverVersion): bool
    {
        // @see: https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-6.html
        if (self::isMySqlOrPerconaDb()) {
            return $serverVersion >= 50706;
        }

        // @see https://mariadb.com/kb/en/changes-improvements-in-mariadb-52/#new-features
        if (self::isMariaDb()) {
            return $serverVersion >= 50200;
        }

        return false;
    }

    /**
     * Check whether the database supports UUID data type
     * true if uuid is supported
     */
    public static function isUUIDSupported(DatabaseInterface $dbi): bool
    {
        // @see: https://mariadb.com/kb/en/mariadb-1070-release-notes/#uuid
        return $dbi->isMariaDB() && $dbi->getVersion() >= 100700; // 10.7.0
    }

    /**
     * Returns whether the database server supports virtual columns
     */
    public static function supportsStoredKeywordForVirtualColumns(int $serverVersion): bool
    {
        // @see: https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-6.html
        if (self::isMySqlOrPerconaDb()) {
            return $serverVersion >= 50706;
        }

        // @see https://mariadb.com/kb/en/generated-columns/#mysql-compatibility-support
        if (self::isMariaDb()) {
            return $serverVersion >= 100201;
        }

        return false;
    }

    /**
     * Returns whether the database server supports compressed columns
     */
    public static function supportsCompressedColumns(int $serverVersion): bool
    {
        // @see https://mariadb.com/kb/en/innodb-page-compression/#comment_1992
        // Comment: Page compression is only available in MariaDB >= 10.1. [...]
        if (self::isMariaDb()) {
            return $serverVersion >= 100100;
        }

        return false;
    }

    /**
     * @see https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-6.html#mysqld-5-7-6-account-management
     * @see https://mariadb.com/kb/en/mariadb-1042-release-notes/#notable-changes
     *
     * @psalm-pure
     */
    public static function hasAccountLocking(bool $isMariaDb, int $version): bool
    {
        return $isMariaDb && $version >= 100402 || ! $isMariaDb && $version >= 50706;
    }
}
