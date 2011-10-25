<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
$GLOBALS['data_dictionary_relations'] = array(
    'CHARACTER_SETS' => array(
        'DEFAULT_COLLATE_NAME' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'COLLATIONS' => array(
        'CHARACTER_SET_NAME' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        )
    ),
    'COLUMNS' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'SCHEMAS',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'COLLATION_NAME' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'INDEXES' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'SCHEMAS',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'INDEX_PARTS' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'SCHEMAS',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'INNODB_LOCKS' => array(
        'LOCK_TRX_ID' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'INNODB_TRX',
            'foreign_field' => 'TRX_ID'
        )
    ),
    'INNODB_LOCK_WAITS' => array(
        'REQUESTING_TRX_ID' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'INNODB_TRX',
            'foreign_field' => 'TRX_ID'
        ),
        'REQUESTED_LOCK_ID' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'INNODB_LOCKS',
            'foreign_field' => 'LOCK_ID'
        ),
        'BLOCKING_TRX_ID' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'INNODB_TRX',
            'foreign_field' => 'TRX_ID'
        ),
        'BLOCKING_LOCK_ID' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'INNODB_LOCKS',
            'foreign_field' => 'LOCK_ID'
        )
    ),
    'INNODB_SYS_COLUMNS' => array(
        'TABLE_ID' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'INNODB_SYS_TABLES',
            'foreign_field' => 'TABLE_ID'
        )
    ),
    'INNODB_SYS_FIELDS' => array(
        'INDEX_ID' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'INNODB_SYS_INDEXES',
            'foreign_field' => 'INDEX_ID'
        )
    ),
    'INNODB_SYS_INDEXES' => array(
        'TABLE_ID' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'INNODB_SYS_TABLES',
            'foreign_field' => 'TABLE_ID'
        )
    ),
    'INNODB_SYS_TABLESTATS' => array(
        'TABLE_ID' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'INNODB_SYS_TABLES',
            'foreign_field' => 'TABLE_ID'
        )
    ),
    'PLUGINS' => array(
        'MODULE_NAME' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'MODULES',
            'foreign_field' => 'MODULE_NAME'
        )
    ),
    'SCHEMAS' => array(
        'DEFAULT_COLLATION_NAME' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'TABLES' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'SCHEMAS',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'TABLE_COLLATION' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        )
    ),
    'TABLE_CACHE' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'SCHEMAS',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'TABLE_CONSTRAINTS' => array(
        'CONSTRAINT_SCHEMA' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'SCHEMAS',
            'foreign_field' => 'SCHEMA_NAME'
        ),
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'SCHEMAS',
            'foreign_field' => 'SCHEMA_NAME'
        )
    ),
    'TABLE_DEFINITION_CACHE' => array(
        'TABLE_SCHEMA' => array(
            'foreign_db'    => 'data_dictionary',
            'foreign_table' => 'SCHEMAS',
            'foreign_field' => 'SCHEMA_NAME'
        )
    )
);

?>