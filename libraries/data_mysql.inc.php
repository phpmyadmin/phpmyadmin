<?php
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

?>
