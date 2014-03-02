<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Internal relations for information schema.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
$GLOBALS['information_schema_relations'] = array(
    'CHARACTER_SETS' => array(
        'DEFAULT_COLLATE_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
        'CHARACTER_SET_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        )
    ),
    'COLLATIONS' => array(
        'CHARACTER_SET_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        )
    ),
    'COLLATION_CHARACTER_SET_APPLICABILITY' => array(
        'CHARACTER_SET_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'COLLATION_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'COLUMNS' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'CHARACTER_SET_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'COLLATION_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'COLUMN_PRIVILEGES' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'EVENTS' => array(
        'EVENT_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'CHARACTER_SET_CLIENT' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'COLLATION_CONNECTION' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
        'DATABASE_COLLATION' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'FILES' => array(
        'TABLESPACE_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'TABLESPACES',
            'foreign_field' => 'TABLESPACE_NAME'
        ),
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'COLLATION_CONNECTION' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
        'ENGINE' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'ENGINES',
            'foreign_field' => 'ENGINE'
        )
    ),
    'KEY_COLUMN_USAGE' => array(
        'CONSTRAINT_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'REFERENCED_TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'PARAMETERS' => array(
        'SPECIFIC_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'CHARACTER_SET_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'COLLATION_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'PARTITIONS' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'TABLESPACE_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'TABLESPACES',
            'foreign_field' => 'TABLESPACE_NAME'
        )
    ),
    'PROCESSLIST' => array(
        'DB' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'REFERENTIAL_CONSTRAINTS' => array(
        'CONSTRAINT_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'UNIQUE_CONSTRAINT_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'ROUTINES' => array(
        'ROUTINE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'CHARACTER_SET_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'COLLATION_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
        'CHARACTER_SET_CLIENT' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'COLLATION_CONNECTION' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
        'DATABASE_COLLATION' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'SCHEMATA' => array(
        'DEFAULT_CHARACTER_SET_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'DEFAULT_COLLATION_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'SCHEMA_PRIVILEGES' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'STATISTICS' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'INDEX_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'TABLES' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'TABLE_COLLATION' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
        'ENGINE' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'ENGINES',
            'foreign_field' => 'ENGINE'
        ),
    ),
    'TABLESAPCES' => array(
        'ENGINE' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'ENGINES',
            'foreign_field' => 'ENGINE'
        )
    ),
    'TABLE_CONSTRAINTS' => array(
        'CONSTRAINT_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'TABLE_PRIVILEGES' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'TRIGGERS' => array(
        'TRIGGER_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'EVENT_OBJECT_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'CHARACTER_SET_CLIENT' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'COLLATION_CONNECTION' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ),
        'DATABASE_COLLATION' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'VIEWS' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'CHARACTER_SET_CLIENT' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ),
        'COLLATION_CONNECTION' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    )
);

?>
