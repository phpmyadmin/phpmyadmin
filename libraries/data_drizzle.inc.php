<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Column types and functions supported by Drizzle
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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

/**
 * This function returns datatypes descriptions.
 *
 * @return array Drizzle datatypes descriptions.
 *
 */
function PMA_supportedDataTypesDescriptions()
{
    // if possible, for easy translation these strings should be the same as for MySQL
    return array(
        'INTEGER' => __('A 4-byte integer, range is -2,147,483,648 to 2,147,483,647'),
        'BIGINT' => __('An 8-byte integer, range is -9,223,372,036,854,775,808 to 9,223,372,036,854,775,807'),
        'DECIMAL' => __('A fixed-point number (M, D) - the maximum number of digits (M) is 65 (default 10), the maximum number of decimals (D) is 30 (default 0)'),
        'DOUBLE' => __("A system's default double-precision floating-point number"),
        'BOOLEAN' => __('True or false'),
        // Drizzle doesn't have UNSIGNED types
        'SERIAL' => __('An alias for BIGINT NOT NULL AUTO_INCREMENT UNIQUE'),
        'UUID' => __('Stores a Universally Unique Identifier (UUID)'),
        'DATE' => __("A date, supported range is '0001-01-01' to '9999-12-31'"),
        'DATETIME' => __("A date and time combination, supported range is '0001-01-01 00:00:00' to '9999-12-31 23:59:59'"),
        'TIMESTAMP' => __("A timestamp, range is '0001-01-01 00:00:00' UTC to '9999-12-31 23:59:59' UTC, TIMESTAMP(6) can store microseconds"),
        'TIME' => __("A time, range is '00:00:00' to '23:59:59'"),
        'VARCHAR' => __('A variable-length (0-16,383) string'),
        'TEXT' => __('A TEXT column with a maximum length of 65,535 (2^16 - 1) characters, stored with a two-byte prefix indicating the length of the value in bytes'),
        'VARBINARY' => __('A variable-length (0-65,535) string, uses binary collation for all comparisons'),
        'BLOB' => __('A BLOB column with a maximum length of 65,535 (2^16 - 1) bytes, stored with a four-byte prefix indicating the length of the value'),
        // there is no limit on ENUM length
        'ENUM' => __("An enumeration, chosen from the list of defined values"),
    );
} // end PMA_supportedDataTypesDescriptions()

?>
