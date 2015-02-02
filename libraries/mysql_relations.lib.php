<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Internal relations for mysql database.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Internal relations for mysql database.
 */
$GLOBALS['mysql_relations'] = array(
    'columns_priv' => array(
        'Db' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
    ),
    'db' => array(
        'Db' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
    ),
    'event' => array(
        'db' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'character_set_client' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'collation_connection' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
        'db_collation' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
    ),
    'help_category' => array(
        'parent_category_id' => array(
            'foreign_db'    => 'mysql',
            'foreign_table' => 'help_category',
            'foreign_field' => 'help_category_id'
        ),
    ),
    'help_relation' => array(
        'help_topic_id' => array(
            'foreign_db'    => 'mysql',
            'foreign_table' => 'help_topic',
            'foreign_field' => 'help_topic_id'
        ),
        'help_keyword_id' => array(
            'foreign_db'    => 'mysql',
            'foreign_table' => 'help_keyword',
            'foreign_field' => 'help_keyword_id'
        ),
    ),
    'help_topic' => array(
        'help_category_id' => array(
            'foreign_db'    => 'mysql',
            'foreign_table' => 'help_category',
            'foreign_field' => 'help_category_id'
        ),
    ),
    'innodb_index_stats' => array(
        'database_name' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
    ),
    'innodb_table_stats' => array(
        'database_name' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
    ),
    'proc' => array(
        'db' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'character_set_client' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'collation_connection' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
        'db_collation' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
    ),
    'proc_priv' => array(
        'Db' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
    ),
    'servers' => array(
        'Db' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
    ),
    'slow_log' => array(
        'db' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
    ),
    'tables_priv' => array(
        'Db' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
    ),
    'time_zone_name' => array(
        'Time_zone_id' => array(
            'foreign_db'    => 'mysql',
            'foreign_table' => 'time_zone',
            'foreign_field' => 'Time_zone_id'
        ),
    ),
    'time_zone_transition' => array(
        'Time_zone_id' => array(
            'foreign_db'    => 'mysql',
            'foreign_table' => 'time_zone',
            'foreign_field' => 'Time_zone_id'
        ),
        'Transition_time' => array(
            'foreign_db'    => 'mysql',
            'foreign_table' => 'time_zone_leap_second',
            'foreign_field' => 'Transition_time'
        )
    ),
    'time_zone_transition_type' => array(
        'Time_zone_id' => array(
            'foreign_db'    => 'mysql',
            'foreign_table' => 'time_zone',
            'foreign_field' => 'Time_zone_id'
        ),
    ),
);
?>