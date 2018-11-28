<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Internal relations for mysql database.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Internal relations for mysql database.
 */
$GLOBALS['mysql_relations'] = [
    'columns_priv' => [
        'Db' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME',
        ],
    ],
    'db' => [
        'Db' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME',
        ],
    ],
    'event' => [
        'db' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME',
        ],
        'character_set_client' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME',
        ],
        'collation_connection' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME',
        ],
        'db_collation' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME',
        ],
    ],
    'help_category' => [
        'parent_category_id' => [
            'foreign_db'    => 'mysql',
            'foreign_table' => 'help_category',
            'foreign_field' => 'help_category_id',
        ],
    ],
    'help_relation' => [
        'help_topic_id' => [
            'foreign_db'    => 'mysql',
            'foreign_table' => 'help_topic',
            'foreign_field' => 'help_topic_id',
        ],
        'help_keyword_id' => [
            'foreign_db'    => 'mysql',
            'foreign_table' => 'help_keyword',
            'foreign_field' => 'help_keyword_id',
        ],
    ],
    'help_topic' => [
        'help_category_id' => [
            'foreign_db'    => 'mysql',
            'foreign_table' => 'help_category',
            'foreign_field' => 'help_category_id',
        ],
    ],
    'innodb_index_stats' => [
        'database_name' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME',
        ],
    ],
    'innodb_table_stats' => [
        'database_name' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME',
        ],
    ],
    'proc' => [
        'db' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME',
        ],
        'character_set_client' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME',
        ],
        'collation_connection' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME',
        ],
        'db_collation' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME',
        ],
    ],
    'proc_priv' => [
        'Db' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME',
        ],
    ],
    'servers' => [
        'Db' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME',
        ],
    ],
    'slow_log' => [
        'db' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME',
        ],
    ],
    'tables_priv' => [
        'Db' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME',
        ],
    ],
    'time_zone_name' => [
        'Time_zone_id' => [
            'foreign_db'    => 'mysql',
            'foreign_table' => 'time_zone',
            'foreign_field' => 'Time_zone_id',
        ],
    ],
    'time_zone_transition' => [
        'Time_zone_id' => [
            'foreign_db'    => 'mysql',
            'foreign_table' => 'time_zone',
            'foreign_field' => 'Time_zone_id',
        ],
        'Transition_time' => [
            'foreign_db'    => 'mysql',
            'foreign_table' => 'time_zone_leap_second',
            'foreign_field' => 'Transition_time',
        ],
    ],
    'time_zone_transition_type' => [
        'Time_zone_id' => [
            'foreign_db'    => 'mysql',
            'foreign_table' => 'time_zone',
            'foreign_field' => 'Time_zone_id',
        ],
    ],
];
