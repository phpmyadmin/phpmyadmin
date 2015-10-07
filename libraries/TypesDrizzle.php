<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL data types definition
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * Class holding type definitions for Drizzle.
 *
 * @package PhpMyAdmin
 */
class TypesDrizzle extends Types
{
    /**
     * Returns the data type description.
     *
     * @param string $type The data type to get a description.
     *
     * @return string
     *
     */
    public function getTypeDescription($type)
    {
        $type = /*overload*/mb_strtoupper($type);
        switch ($type) {
        case 'INTEGER':
            return __('A 4-byte integer, range is -2,147,483,648 to 2,147,483,647');
        case 'BIGINT':
            return __(
                'An 8-byte integer, range is -9,223,372,036,854,775,808 to ' .
                '9,223,372,036,854,775,807'
            );
        case 'DECIMAL':
            return __(
                'A fixed-point number (M, D) - the maximum number of digits ' .
                '(M) is 65 (default 10), the maximum number of decimals (D) ' .
                'is 30 (default 0)'
            );
        case 'DOUBLE':
            return __("A system's default double-precision floating-point number");
        case 'BOOLEAN':
            return __('True or false');
        case 'SERIAL':
            return __('An alias for BIGINT NOT NULL AUTO_INCREMENT UNIQUE');
        case 'UUID':
            return __('Stores a Universally Unique Identifier (UUID)');
        case 'DATE':
            return sprintf(
                __('A date, supported range is %1$s to %2$s'), '0001-01-01',
                '9999-12-31'
            );
        case 'DATETIME':
            return sprintf(
                __(
                    'A date and time combination, supported range is %1$s to ' .
                    '%2$s'
                ), '0001-01-01 00:00:0', '9999-12-31 23:59:59'
            );
        case 'TIMESTAMP':
            return __(
                "A timestamp, range is '0001-01-01 00:00:00' UTC to " .
                "'9999-12-31 23:59:59' UTC; TIMESTAMP(6) can store microseconds"
            );
        case 'TIME':
            return sprintf(
                __('A time, range is %1$s to %2$s'), '00:00:00', '23:59:59'
            );
        case 'VARCHAR':
            return sprintf(
                __(
                    'A variable-length (%s) string, the effective ' .
                    'maximum length is subject to the maximum row size'
                ), '0-16,383'
            );
        case 'TEXT':
            return __(
                'A TEXT column with a maximum length of 65,535 (2^16 - 1) ' .
                'characters, stored with a two-byte prefix indicating the ' .
                'length of the value in bytes'
            );
        case 'VARBINARY':
            return __(
                'A variable-length (0-65,535) string, uses binary collation ' .
                'for all comparisons'
            );
        case 'BLOB':
            return __(
                'A BLOB column with a maximum length of 65,535 (2^16 - 1) ' .
                'bytes, stored with a two-byte prefix indicating the length of ' .
                'the value'
            );
        case 'ENUM':
            return __("An enumeration, chosen from the list of defined values");
        }

        return '';
    }

    /**
     * Returns class of a type, used for functions available for type
     * or default values.
     *
     * @param string $type The data type to get a class.
     *
     * @return string
     *
     */
    public function getTypeClass($type)
    {
        $type = /*overload*/mb_strtoupper($type);
        switch ($type) {
        case 'INTEGER':
        case 'BIGINT':
        case 'DECIMAL':
        case 'DOUBLE':
        case 'BOOLEAN':
        case 'SERIAL':
            return 'NUMBER';

        case 'DATE':
        case 'DATETIME':
        case 'TIMESTAMP':
        case 'TIME':
            return 'DATE';

        case 'VARCHAR':
        case 'TEXT':
        case 'VARBINARY':
        case 'BLOB':
        case 'ENUM':
            return 'CHAR';

        case 'UUID':
            return 'UUID';
        }
        return '';
    }

    /**
     * Returns array of functions available for a class.
     *
     * @param string $class The class to get function list.
     *
     * @return string[]
     *
     */
    public function getFunctionsClass($class)
    {
        switch ($class) {
        case 'CHAR':
            $ret = array(
                'BIN',
                'CHAR',
                'COMPRESS',
                'CURRENT_USER',
                'DATABASE',
                'DAYNAME',
                'HEX',
                'LOAD_FILE',
                'LOWER',
                'LTRIM',
                'MD5',
                'MONTHNAME',
                'QUOTE',
                'REVERSE',
                'RTRIM',
                'SCHEMA',
                'SPACE',
                'TRIM',
                'UNCOMPRESS',
                'UNHEX',
                'UPPER',
                'USER',
                'UUID',
                'VERSION',
            );

            // check for some functions known to be in modules
            $functions = array(
                'MYSQL_PASSWORD',
                'ROT13',
            );

            // add new functions
            $sql = "SELECT upper(plugin_name) f
                FROM data_dictionary.plugins
                WHERE plugin_name IN ('" . implode("','", $functions) . "')
                  AND plugin_type = 'Function'
                  AND is_active";
            $drizzle_functions = $GLOBALS['dbi']->fetchResult($sql, 'f', 'f');
            if (count($drizzle_functions) > 0) {
                $ret = array_merge($ret, $drizzle_functions);
                sort($ret);
            }

            return $ret;

        case 'UUID':
            return array(
                'UUID',
            );

        case 'DATE':
            return array(
                'CURRENT_DATE',
                'CURRENT_TIME',
                'DATE',
                'FROM_DAYS',
                'FROM_UNIXTIME',
                'LAST_DAY',
                'NOW',
                'SYSDATE',
                //'TIME', // https://bugs.launchpad.net/drizzle/+bug/804571
                'TIMESTAMP',
                'UTC_DATE',
                'UTC_TIME',
                'UTC_TIMESTAMP',
                'YEAR',
            );

        case 'NUMBER':
            return array(
                'ABS',
                'ACOS',
                'ASCII',
                'ASIN',
                'ATAN',
                'BIT_COUNT',
                'CEILING',
                'CHAR_LENGTH',
                'CONNECTION_ID',
                'COS',
                'COT',
                'CRC32',
                'DAYOFMONTH',
                'DAYOFWEEK',
                'DAYOFYEAR',
                'DEGREES',
                'EXP',
                'FLOOR',
                'HOUR',
                'LENGTH',
                'LN',
                'LOG',
                'LOG2',
                'LOG10',
                'MICROSECOND',
                'MINUTE',
                'MONTH',
                'OCT',
                'ORD',
                'PI',
                'QUARTER',
                'RADIANS',
                'RAND',
                'ROUND',
                'SECOND',
                'SIGN',
                'SIN',
                'SQRT',
                'TAN',
                'TO_DAYS',
                'TIME_TO_SEC',
                'UNCOMPRESSED_LENGTH',
                'UNIX_TIMESTAMP',
                //'WEEK', // same as TIME
                'WEEKDAY',
                'WEEKOFYEAR',
                'YEARWEEK',
            );
        }
        return array();
    }

    /**
     * Returns array of all attributes available.
     *
     * @return string[]
     *
     */
    public function getAttributes()
    {
        return array(
            '',
            'on update CURRENT_TIMESTAMP',
        );
    }

    /**
     * Returns array of all column types available.
     *
     * @return string[]
     *
     */
    public function getColumns()
    {
        $types_num = array(
            'INTEGER',
            'BIGINT',
            '-',
            'DECIMAL',
            'DOUBLE',
            '-',
            'BOOLEAN',
            'SERIAL',
            'UUID',
        );
        $types_date = array(
            'DATE',
            'DATETIME',
            'TIMESTAMP',
            'TIME',
        );
        $types_string = array(
            'VARCHAR',
            'TEXT',
            '-',
            'VARBINARY',
            'BLOB',
            '-',
            'ENUM',
        );
        if (PMA_MYSQL_INT_VERSION >= 70132) {
            $types_string[] = '-';
            $types_string[] = 'IPV6';
        }

        $ret = parent::getColumns();
        // numeric
        $ret[_pgettext('numeric types', 'Numeric')] = $types_num;

        // Date/Time
        $ret[_pgettext('date and time types', 'Date and time')] = $types_date;

        // Text
        $ret[_pgettext('string types', 'String')] = $types_string;

        return $ret;
    }

    /**
     * Returns an array of integer types
     *
     * @return string[] integer types
     */
    public function getIntegerTypes()
    {
        return array('integer', 'bigint');
    }

    /**
     * Returns the min and max values of a given integer type
     *
     * @param string  $type   integer type
     * @param boolean $signed whether signed (ignored for Drizzle)
     *
     * @return string[] min and max values
     */
    public function getIntegerRange($type, $signed = true)
    {
        static $min_max_data = array(
            'integer' => array('-2147483648', '2147483647'),
            'bigint'  => array('-9223372036854775808', '9223372036854775807')
        );
        return isset($min_max_data[$type]) ? $min_max_data[$type] : array('', '');
    }
}
