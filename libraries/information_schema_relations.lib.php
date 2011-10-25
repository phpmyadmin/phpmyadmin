<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
$GLOBALS['information_schema_relations'] = array(
    'CHARACTER_SETS' => array(
        'DEFAULT_COLLATE_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
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
        )
    ),
    'ROUTINES' => array(
        'ROUTINE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'SCHEMATA' => array(
        'DEFAULT_CHARACTER_SET_NAME' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        )
    ),
    'SCHEMA_PRIVILEGES' => array(
        'TABLE_SCHEMA' => array(
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
    'VIEWS' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        )
    )
);

?>
