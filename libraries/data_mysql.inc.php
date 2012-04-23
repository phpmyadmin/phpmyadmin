<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Column types and functions supported by MySQL
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

// VARCHAR, TINYINT, TEXT and DATE are listed first, based on estimated popularity
$cfg['ColumnTypes'] = !empty($cfg['ColumnTypes']) ? $cfg['ColumnTypes'] : array(
    // most used
    'INT',
    'VARCHAR',
    'TEXT',
    'DATE',

    // numeric
    'NUMERIC' => array(
        'TINYINT',
        'SMALLINT',
        'MEDIUMINT',
        'INT',
        'BIGINT',
        '-',
        'DECIMAL',
        'FLOAT',
        'DOUBLE',
        'REAL',
        '-',
        'BIT',
        'BOOLEAN',
        'SERIAL',
    ),

    // Date/Time
    'DATE and TIME' => array(
        'DATE',
        'DATETIME',
        'TIMESTAMP',
        'TIME',
        'YEAR',
    ),

    // Text
    'STRING' => array(
        'CHAR',
        'VARCHAR',
        '-',
        'TINYTEXT',
        'TEXT',
        'MEDIUMTEXT',
        'LONGTEXT',
        '-',
        'BINARY',
        'VARBINARY',
        '-',
        'TINYBLOB',
        'MEDIUMBLOB',
        'BLOB',
        'LONGBLOB',
        '-',
        'ENUM',
        'SET',
    ),

    'SPATIAL' => array(
        'GEOMETRY',
        'POINT',
        'LINESTRING',
        'POLYGON',
        'MULTIPOINT',
        'MULTILINESTRING',
        'MULTIPOLYGON',
        'GEOMETRYCOLLECTION',
    ),
);

$cfg['AttributeTypes'] = !empty($cfg['AttributeTypes']) ? $cfg['AttributeTypes'] : array(
   '',
   'BINARY',
   'UNSIGNED',
   'UNSIGNED ZEROFILL',
   'on update CURRENT_TIMESTAMP',
);

if ($cfg['ShowFunctionFields']) {
    $cfg['RestrictColumnTypes'] = !empty($cfg['RestrictColumnTypes']) ? $cfg['RestrictColumnTypes'] : array(
        'TINYINT'   => 'FUNC_NUMBER',
        'SMALLINT'  => 'FUNC_NUMBER',
        'MEDIUMINT' => 'FUNC_NUMBER',
        'INT'       => 'FUNC_NUMBER',
        'BIGINT'    => 'FUNC_NUMBER',
        'DECIMAL'   => 'FUNC_NUMBER',
        'FLOAT'     => 'FUNC_NUMBER',
        'DOUBLE'    => 'FUNC_NUMBER',
        'REAL'      => 'FUNC_NUMBER',
        'BIT'       => 'FUNC_NUMBER',
        'BOOLEAN'   => 'FUNC_NUMBER',
        'SERIAL'    => 'FUNC_NUMBER',

        'DATE'      => 'FUNC_DATE',
        'DATETIME'  => 'FUNC_DATE',
        'TIMESTAMP' => 'FUNC_DATE',
        'TIME'      => 'FUNC_DATE',
        'YEAR'      => 'FUNC_DATE',

        'CHAR'       => 'FUNC_CHAR',
        'VARCHAR'    => 'FUNC_CHAR',
        'TINYTEXT'   => 'FUNC_CHAR',
        'TEXT'       => 'FUNC_CHAR',
        'MEDIUMTEXT' => 'FUNC_CHAR',
        'LONGTEXT'   => 'FUNC_CHAR',
        'BINARY'     => 'FUNC_CHAR',
        'VARBINARY'  => 'FUNC_CHAR',
        'TINYBLOB'   => 'FUNC_CHAR',
        'MEDIUMBLOB' => 'FUNC_CHAR',
        'BLOB'       => 'FUNC_CHAR',
        'LONGBLOB'   => 'FUNC_CHAR',
        'ENUM'       => '',
        'SET'        => '',

        'GEOMETRY'           => 'FUNC_SPATIAL',
        'POINT'              => 'FUNC_SPATIAL',
        'LINESTRING'         => 'FUNC_SPATIAL',
        'POLYGON'            => 'FUNC_SPATIAL',
        'MULTIPOINT'         => 'FUNC_SPATIAL',
        'MULTILINESTRING'    => 'FUNC_SPATIAL',
        'MULTIPOLYGON'       => 'FUNC_SPATIAL',
        'GEOMETRYCOLLECTION' => 'FUNC_SPATIAL',

    );

    $restrict_functions = array(
        'FUNC_CHAR' => array(
            'BIN',
            'CHAR',
            'CURRENT_USER',
            'COMPRESS',
            'DATABASE',
            'DAYNAME',
            'DES_DECRYPT',
            'DES_ENCRYPT',
            'ENCRYPT',
            'HEX',
            'INET_NTOA',
            'LOAD_FILE',
            'LOWER',
            'LTRIM',
            'MD5',
            'MONTHNAME',
            'OLD_PASSWORD',
            'PASSWORD',
            'QUOTE',
            'REVERSE',
            'RTRIM',
            'SHA1',
            'SOUNDEX',
            'SPACE',
            'TRIM',
            'UNCOMPRESS',
            'UNHEX',
            'UPPER',
            'USER',
            'UUID',
            'VERSION',
        ),

        'FUNC_DATE' => array(
            'CURRENT_DATE',
            'CURRENT_TIME',
            'DATE',
            'FROM_DAYS',
            'FROM_UNIXTIME',
            'LAST_DAY',
            'NOW',
            'SEC_TO_TIME',
            'SYSDATE',
            'TIME',
            'TIMESTAMP',
            'UTC_DATE',
            'UTC_TIME',
            'UTC_TIMESTAMP',
            'YEAR',
        ),

        'FUNC_NUMBER' => array(
            'ABS',
            'ACOS',
            'ASCII',
            'ASIN',
            'ATAN',
            'BIT_LENGTH',
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
            'INET_ATON',
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
            'TO_SECONDS',
            'TIME_TO_SEC',
            'UNCOMPRESSED_LENGTH',
            'UNIX_TIMESTAMP',
            'UUID_SHORT',
            'WEEK',
            'WEEKDAY',
            'WEEKOFYEAR',
            'YEARWEEK',
        ),

        'FUNC_SPATIAL' => array(
            'GeomFromText',
            'GeomFromWKB',

            'GeomCollFromText',
            'LineFromText',
            'MLineFromText',
            'PointFromText',
            'MPointFromText',
            'PolyFromText',
            'MPolyFromText',

            'GeomCollFromWKB',
            'LineFromWKB',
            'MLineFromWKB',
            'PointFromWKB',
            'MPointFromWKB',
            'PolyFromWKB',
            'MPolyFromWKB',
        ),
    );
    // $restrict_functions holds all known functions, remove these that are unavailable on current server
    if (PMA_MYSQL_INT_VERSION < 50500) {
        $restrict_functions['FUNC_NUMBER'] = array_diff($restrict_functions['FUNC_NUMBER'], array('TO_SECONDS'));
    }
    if (PMA_MYSQL_INT_VERSION < 50120) {
        $restrict_functions['FUNC_NUMBER'] = array_diff($restrict_functions['FUNC_NUMBER'], array('UUID_SHORT'));
    }

    if (empty($cfg['RestrictFunctions'])) {
        $cfg['RestrictFunctions'] = $restrict_functions;
    }

    if (empty($cfg['Functions'])) {
        // build a list of functions based on $restrict_functions
        $cfg['Functions'] = array();
        foreach ($restrict_functions as $cat => $functions) {
            if ($cat != 'FUNC_SPATIAL') {
                $cfg['Functions'] = array_merge($cfg['Functions'], $functions);
            }
        }
        sort($cfg['Functions']);
    }
    unset($restrict_functions);
} // end if

/**
 * This function returns datatypes descriptions.
 *
 * @return array MySQL datatypes descriptions.
 *
 */
function SupportedDataTypesDescriptions()
{
    return array(
        'TINYINT' => __('A very small integer. The signed range is -128 to 127. The unsigned range is 0 to 255.'),
        'SMALLINT' => __('A small integer. The signed range is -32768 to 32767. The unsigned range is 0 to 65535.'),
        'MEDIUMINT' => __('A medium-sized integer. The signed range is -8388608 to 8388607. The unsigned range is 0 to 16777215.'),
        'INT' => __('A normal-size integer. The signed range is -2147483648 to 2147483647. The unsigned range is 0 to 4294967295.'),
        'BIGINT' => __('A large integer. The signed range is -9223372036854775808 to 9223372036854775807. The unsigned range is 0 to 18446744073709551615.'),
        'DECIMAL' => __('A packed "exact" fixed-point number. The maximum number of digits (M) for DECIMAL is 65. The maximum number of supported decimals (D) is 30. If D is omitted, the default is 0. If M is omitted, the default is 10.'),
        'FLOAT' => __('A small (single-precision) floating-point number. Allowable values are -3.402823466E+38 to -1.175494351E-38, 0, and 1.175494351E-38 to 3.402823466E+38.'),
        'DOUBLE' => __('A normal-size (double-precision) floating-point number. Allowable values are -1.7976931348623157E+308 to -2.2250738585072014E-308, 0, and 2.2250738585072014E-308 to 1.7976931348623157E+308.'),
        'REAL' => __('Synonyms for DOUBLE. Exception: If the REAL_AS_FLOAT SQL mode is enabled, REAL is a synonym for FLOAT rather than DOUBLE.'),
        'BIT' => __('A bit-field type. M indicates the number of bits per value, from 1 to 64. The default is 1 if M is omitted.'),
        'BOOLEAN' => __('These types are synonyms for TINYINT(1). A value of zero is considered false. Nonzero values are considered true.'),
        'SERIAL' => __('An alias for BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE.'),
        'DATE' => __("A date. The supported range is '1000-01-01' to '9999-12-31'. MySQL displays DATE values in 'YYYY-MM-DD' format, but allows assignment of values to DATE columns using either strings or numbers."),
        'DATETIME' => __("A date and time combination. The supported range is '1000-01-01 00:00:00' to '9999-12-31 23:59:59'. MySQL displays DATETIME values in 'YYYY-MM-DD HH:MM:SS' format, but allows assignment of values to DATETIME columns using either strings or numbers."),
        'TIMESTAMP' => __("A timestamp. The range is '1970-01-01 00:00:01' UTC to '2038-01-09 03:14:07' UTC. TIMESTAMP values are stored as the number of seconds since the epoch ('1970-01-01 00:00:00' UTC)."),
        'TIME' => __("A time. The range is '-838:59:59' to '838:59:59'. MySQL displays TIME values in 'HH:MM:SS' format, but allows assignment of values to TIME columns using either strings or numbers."),
        'YEAR' => __("A year in two-digit or four-digit format. The default is four-digit format. In four-digit format, the allowable values are 1901 to 2155, and 0000. In two-digit format, the allowable values are 70 to 69, representing years from 1970 to 2069."),
        'CHAR' => __('A fixed-length string that is always right-padded with spaces to the specified length when stored. M represents the column length in characters. The range of M is 0 to 255. If M is omitted, the length is 1.'),
        'VARCHAR' => __('A variable-length string. M represents the maximum column length in characters. The range of M is 0 to 65,535.'),
        'TINYTEXT' => __('A TEXT column with a maximum length of 255 (28 - 1) characters. Each TINYTEXT value is stored using a one-byte length prefix that indicates the number of bytes in the value.'),
        'TEXT' => __('A TEXT column with a maximum length of 65,535 (216 - 1) characters. Each TEXT value is stored using a two-byte length prefix that indicates the number of bytes in the value.'),
        'MEDIUMTEXT' => __('A TEXT column with a maximum length of 16,777,215 (224 - 1) characters. Each MEDIUMTEXT value is stored using a three-byte length prefix that indicates the number of bytes in the value.'),
        'LONGTEXT' => __('A TEXT column with a maximum length of 4,294,967,295 or 4GB (232 - 1) characters. Each LONGTEXT value is stored using a four-byte length prefix that indicates the number of bytes in the value.'),
        'BINARY' => __('The BINARY type is similar to the CHAR type, but stores binary byte strings rather than non-binary character strings. M represents the column length in bytes.'),
        'VARBINARY' => __('The VARBINARY type is similar to the VARCHAR type, but stores binary byte strings rather than non-binary character strings. M represents the maximum column length in bytes.'),
        'TINYBLOB' => __('A BLOB column with a maximum length of 255 (28 - 1) bytes. Each TINYBLOB value is stored using a one-byte length prefix that indicates the number of bytes in the value.'),
        'MEDIUMBLOB' => __('A BLOB column with a maximum length of 16,777,215 (224 - 1) bytes. Each MEDIUMBLOB value is stored using a three-byte length prefix that indicates the number of bytes in the value.'),
        'BLOB' => __('A BLOB column with a maximum length of 65,535 (216 - 1) bytes. Each BLOB value is stored using a two-byte length prefix that indicates the number of bytes in the value.'),
        'LONGBLOB' => __('A BLOB column with a maximum length of 4,294,967,295 or 4GB (232 - 1) bytes. Each LONGBLOB value is stored using a four-byte length prefix that indicates the number of bytes in the value.'),
        'ENUM' => __("An enumeration. A string object that can have only one value, chosen from the list of values 'value1', 'value2', ..., NULL or the special '' error value. An ENUM column can have a maximum of 65,535 distinct values."),
        'SET' => __("A set. A string object that can have zero or more values, each of which must be chosen from the list of values 'value1', 'value2', ... A SET column can have a maximum of 64 members."),
        'GEOMETRY' => '',
        'POINT' => __('Constructs a WKB Point using its coordinates.'),
        'LINESTRING' => __('Constructs a WKB LineString value from a number of WKB Point arguments. If any argument is not a WKB Point, the return value is NULL. If the number of Point arguments is less than two, the return value is NULL.'),
        'POLYGON' => __('Constructs a WKB Polygon value from a number of WKB LineString arguments. If any argument does not represent the WKB of a LinearRing (that is, not a closed and simple LineString) the return value is NULL.'),
        'MULTIPOINT' => __('Constructs a WKB MultiPoint value using WKB Point arguments. If any argument is not a WKB Point, the return value is NULL.'),
        'MULTILINESTRING' => __('Constructs a WKB MultiLineString value using WKB LineString arguments. If any argument is not a WKB LineString, the return value is NULL.'),
        'MULTIPOLYGON' => __('Constructs a WKB MultiPolygon value from a set of WKB Polygon arguments. If any argument is not a WKB Polygon, the return value is NULL.'),
        'GEOMETRYCOLLECTION' => __('Constructs a WKB GeometryCollection. If any argument is not a well-formed WKB representation of a geometry, the return value is NULL.'),
    );
} // end SupportedDataTypesDescriptions()

?>
