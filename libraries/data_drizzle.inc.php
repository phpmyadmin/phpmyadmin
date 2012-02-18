<?php
/**
 * Column types and functions supported by Drizzle
 *
 * @package PhpMyAdmin
 */

$auto_column_types = empty($cfg['ColumnTypes']);

// VARCHAR, TINYINT, TEXT and DATE are listed first, based on estimated popularity
$cfg['ColumnTypes'] = !empty($cfg['ColumnTypes']) ? $cfg['ColumnTypes'] : array(
    // most used
    'INTEGER',
    'VARCHAR',
    'TEXT',
    'DATE',

    // numeric
    'NUMERIC' => array(
        'INTEGER',
        'BIGINT',
        '-',
        'DECIMAL',
        'DOUBLE',
        '-',
        'BOOLEAN',
        'SERIAL',
        'UUID',
    ),


    // Date/Time
    'DATE and TIME' => array(
        'DATE',
        'DATETIME',
        'TIMESTAMP',
        'TIME',
    ),

    // Text
    'STRING' => array(
        'VARCHAR',
        'TEXT',
        '-',
        'VARBINARY',
        'BLOB',
        '-',
        'ENUM',
    ),
);

if ($auto_column_types && PMA_MYSQL_INT_VERSION >= 20120130) {
    $cfg['ColumnTypes']['STRING'][] = '-';
    $cfg['ColumnTypes']['STRING'][] = 'IPV6';
}
unset($auto_column_types);

$cfg['AttributeTypes'] = !empty($cfg['AttributeTypes']) ? $cfg['AttributeTypes'] : array(
   '',
   'on update CURRENT_TIMESTAMP',
);

if ($cfg['ShowFunctionFields']) {
    $cfg['RestrictColumnTypes'] = !empty($cfg['RestrictColumnTypes']) ? $cfg['RestrictColumnTypes'] : array(
        'INTEGER'   => 'FUNC_NUMBER',
        'BIGINT'    => 'FUNC_NUMBER',
        'DECIMAL'   => 'FUNC_NUMBER',
        'DOUBLE'    => 'FUNC_NUMBER',
        'BOOLEAN'   => 'FUNC_NUMBER',
        'SERIAL'    => 'FUNC_NUMBER',

        'DATE'      => 'FUNC_DATE',
        'DATETIME'  => 'FUNC_DATE',
        'TIMESTAMP' => 'FUNC_DATE',
        'TIME'      => 'FUNC_DATE',

        'VARCHAR'    => 'FUNC_CHAR',
        'TEXT'       => 'FUNC_CHAR',
        'VARBINARY'  => 'FUNC_CHAR',
        'BLOB'       => 'FUNC_CHAR',
        'UUID'       => 'FUNC_UUID',
        'ENUM'       => '',
    );

    $restrict_functions = array(
        'FUNC_CHAR' => array(
            'BIN',
            'CHAR',
            'CURRENT_USER',
            'COMPRESS',
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
        ),

        'FUNC_UUID' => array(
            'UUID',
        ),

        'FUNC_DATE' => array(
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
        ),

        'FUNC_NUMBER' => array(
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
        ),
    );
    $cfg_default_restrict_funcs = empty($cfg['RestrictFunctions']);
    if ($cfg_default_restrict_funcs) {
        $cfg['RestrictFunctions'] = $restrict_functions;
    }

    if (empty($cfg['Functions'])) {
        // build a list of functions based on $restrict_functions
        $cfg['Functions'] = array();
        foreach ($restrict_functions as $cat => $functions) {
            $cfg['Functions'] = array_merge($cfg['Functions'], $functions);
        }

        // check for some functions known to be in modules
        $functions = array(
            'MYSQL_PASSWORD' => 'FUNC_CHAR',
            'ROT13' => 'FUNC_CHAR',
        );
        // add new functions
        $sql = "SELECT upper(plugin_name) f
            FROM data_dictionary.plugins
            WHERE plugin_name IN ('" . implode("','", array_keys($functions)) . "')
              AND plugin_type = 'Function'
              AND is_active";
        $drizzle_functions = PMA_DBI_fetch_result($sql, 'f', 'f');
        $cfg['Functions'] = array_merge($cfg['Functions'], $drizzle_functions);
        if ($cfg_default_restrict_funcs) {
            foreach ($drizzle_functions as $function) {
                $category = $functions[$function];
                $cfg['RestrictFunctions'][$category][] = $function;
            }
            foreach ($cfg['RestrictFunctions'] as &$v) {
                sort($v);
            }
            unset($v);
        }

        sort($cfg['Functions']);
    }
    unset($restrict_functions);
} // end if

?>