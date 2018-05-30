<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Internal relations for information schema.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
$GLOBALS['information_schema_relations'] = [
    'CHARACTER_SETS' => [
        'DEFAULT_COLLATE_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ]
    ],
    'COLLATIONS' => [
        'CHARACTER_SET_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ]
    ],
    'COLLATION_CHARACTER_SET_APPLICABILITY' => [
        'CHARACTER_SET_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ],
        'COLLATION_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ]
    ],
    'COLUMNS' => [
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'CHARACTER_SET_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ],
        'COLLATION_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ]
    ],
    'COLUMN_PRIVILEGES' => [
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ]
    ],
    'EVENTS' => [
        'EVENT_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'CHARACTER_SET_CLIENT' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ],
        'COLLATION_CONNECTION' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ],
        'DATABASE_COLLATION' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ]
    ],
    'FILES' => [
        'TABLESPACE_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'TABLESPACES',
            'foreign_field' => 'TABLESPACE_NAME'
        ],
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'COLLATION_CONNECTION' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ],
        'ENGINE' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'ENGINES',
            'foreign_field' => 'ENGINE'
        ]
    ],
    'KEY_COLUMN_USAGE' => [
        'CONSTRAINT_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'REFERENCED_TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ]
    ],
    'PARAMETERS' => [
        'SPECIFIC_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'CHARACTER_SET_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ],
        'COLLATION_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ]
    ],
    'PARTITIONS' => [
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'TABLESPACE_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'TABLESPACES',
            'foreign_field' => 'TABLESPACE_NAME'
        ]
    ],
    'PROCESSLIST' => [
        'DB' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ]
    ],
    'REFERENTIAL_CONSTRAINTS' => [
        'CONSTRAINT_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'UNIQUE_CONSTRAINT_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ]
    ],
    'ROUTINES' => [
        'ROUTINE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'CHARACTER_SET_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ],
        'COLLATION_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ],
        'CHARACTER_SET_CLIENT' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ],
        'COLLATION_CONNECTION' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ],
        'DATABASE_COLLATION' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ]
    ],
    'SCHEMATA' => [
        'DEFAULT_CHARACTER_SET_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ],
        'DEFAULT_COLLATION_NAME' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ]
    ],
    'SCHEMA_PRIVILEGES' => [
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ]
    ],
    'STATISTICS' => [
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'INDEX_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ]
    ],
    'TABLES' => [
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'TABLE_COLLATION' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ],
        'ENGINE' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'ENGINES',
            'foreign_field' => 'ENGINE'
        ],
    ],
    'TABLESAPCES' => [
        'ENGINE' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'ENGINES',
            'foreign_field' => 'ENGINE'
        ]
    ],
    'TABLE_CONSTRAINTS' => [
        'CONSTRAINT_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ]
    ],
    'TABLE_PRIVILEGES' => [
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ]
    ],
    'TRIGGERS' => [
        'TRIGGER_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'EVENT_OBJECT_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'CHARACTER_SET_CLIENT' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ],
        'COLLATION_CONNECTION' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ],
        'DATABASE_COLLATION' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ]
    ],
    'VIEWS' => [
        'TABLE_SCHEMA' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'SCHEMATA',
            'foreign_field' => 'SCHEMA_NAME'
        ],
        'CHARACTER_SET_CLIENT' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'CHARACTER_SETS',
            'foreign_field' => 'CHARACTER_SET_NAME'
        ],
        'COLLATION_CONNECTION' => [
            'foreign_db'    => 'information_schema',
            'foreign_table' => 'COLLATIONS',
            'foreign_field' => 'COLLATION_NAME'
        ]
    ]
];

