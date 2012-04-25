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
function PMA_supportedDataTypesDescriptions()
{
    // if possible, for easy translation these strings should be the same as for Drizzle
    return array(
        'TINYINT' => __('A 1-byte integer, signed range is -128 to 127, unsigned range is 0 to 255'),
        'SMALLINT' => __('A 2-byte integer, signed range is -32,768 to 32,767, unsigned range is 0 to 65,535'),
        'MEDIUMINT' => __('A 3-byte integer, signed range is -8,388,608 to 8,388,607, unsigned range is 0 to 16,777,215'),
        'INT' => __('A 4-byte integer, signed range is -2,147,483,648 to 2,147,483,647, unsigned range is 0 to 4,294,967,295.'),
        'BIGINT' => __('An 8-byte integer, signed range is -9,223,372,036,854,775,808 to 9,223,372,036,854,775,807, unsigned range is 0 to 18,446,744,073,709,551,615'),
        'DECIMAL' => __('A fixed-point number (M, D) - the maximum number of digits (M) is 65 (default 10), the maximum number of decimals (D) is 30 (default 0)'),
        'FLOAT' => __('A small floating-point number, allowable values are -3.402823466E+38 to -1.175494351E-38, 0, and 1.175494351E-38 to 3.402823466E+38'),
        'DOUBLE' => __('A double-precision floating-point number, allowable values are -1.7976931348623157E+308 to -2.2250738585072014E-308, 0, and 2.2250738585072014E-308 to 1.7976931348623157E+308'),
        'REAL' => __('Synonym for DOUBLE (exception: in REAL_AS_FLOAT SQL mode it is a synonym for FLOAT)'),
        'BIT' => __('A bit-field type (M), storing M of bits per value (default is 1, maximum is 64)'),
        'BOOLEAN' => __('A synonym for TINYINT(1), a value of zero is considered false, nonzero values are considered true'),
        'SERIAL' => __('An alias for BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE'),
        'DATE' => __("A date, supported range is '1000-01-01' to '9999-12-31'"),
        'DATETIME' => __("A date and time combination, supported range is '1000-01-01 00:00:00' to '9999-12-31 23:59:59'"),
        'TIMESTAMP' => __("A timestamp, range is '1970-01-01 00:00:01' UTC to '2038-01-09 03:14:07' UTC, stored as the number of seconds since the epoch ('1970-01-01 00:00:00' UTC)"),
        'TIME' => __("A time, range is '-838:59:59' to '838:59:59'"),
        'YEAR' => __("A year in four-digit (4, default) or two-digit (2) format, the allowable values are 70 (1970) to 69 (2069) or 1901 to 2155 and 0000"),
        'CHAR' => __('A fixed-length (0-255, default 1) string that is always right-padded with spaces to the specified length when stored'),
        'VARCHAR' => __('A variable-length (0-65,535) string, the effective maximum length is subject to the maximum row size (65,535 bytes)'),
        'TINYTEXT' => __('A TEXT column with a maximum length of 255 (2^8 - 1) characters, stored with a one-byte prefix indicating the length of the value in bytes'),
        'TEXT' => __('A TEXT column with a maximum length of 65,535 (2^16 - 1) characters, stored with a two-byte prefix indicating the length of the value in bytes'),
        'MEDIUMTEXT' => __('A TEXT column with a maximum length of 16,777,215 (2^24 - 1) characters, stored with a three-byte prefix indicating the length of the value in bytes'),
        'LONGTEXT' => __('A TEXT column with a maximum length of 4,294,967,295 or 4GB (2^32 - 1) characters, stored with a four-byte prefix indicating the length of the value in bytes'),
        'BINARY' => __('Similar to the CHAR type, but stores binary byte strings rather than non-binary character strings'),
        'VARBINARY' => __('Similar to the VARCHAR type, but stores binary byte strings rather than non-binary character strings'),
        'TINYBLOB' => __('A BLOB column with a maximum length of 255 (2^8 - 1) bytes, stored with a one-byte prefix indicating the length of the value'),
        'MEDIUMBLOB' => __('A BLOB column with a maximum length of 16,777,215 (2^24 - 1) bytes, stored with a three-byte prefix indicating the length of the value'),
        'BLOB' => __('A BLOB column with a maximum length of 65,535 (2^16 - 1) bytes, stored with a four-byte prefix indicating the length of the value'),
        'LONGBLOB' => __('A BLOB column with a maximum length of 4,294,967,295 or 4GB (2^32 - 1) bytes, stored with a two-byte prefix indicating the length of the value'),
        'ENUM' => __("An enumeration, chosen from the list of up to 65,535 values or the special '' error value"),
        'SET' => __("A single value chosen from a set of up to 64 members"),
        'GEOMETRY' => __('A type that can store a geometry of any type'),
        'POINT' => __('A point in 2-dimensional space'),
        'LINESTRING' => __('A curve with linear interpolation between points'),
        'POLYGON' => __('A polygon'),
        'MULTIPOINT' => __('A collection of points'),
        'MULTILINESTRING' => __('A collection of curves with linear interpolation between points'),
        'MULTIPOLYGON' => __('A collection of polygons'),
        'GEOMETRYCOLLECTION' => __('A collection of geometry objects of any type'),
    );
} // end PMA_supportedDataTypesDescriptions()

?>
