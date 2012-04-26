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

?>
