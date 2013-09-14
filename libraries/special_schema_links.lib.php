<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This global variable represent the details for generating links inside
 * special schemas like mysql, information_schema etc.
 * Major element represent a schema.
 * All the strings in this array represented in lower case
 * This global variable has not modified anywhere
 *
 * Variable structure ex:
 * $GLOBALS['special_schema_links'] = array(
 *     // Database name is the major element
 *     'mysql' => array(
 *         // Table name
 *         'db' => array(
 *             // Column name
 *             'user' => array(
 *                 // Main url param (can be an array where represent sql)
 *                 'link_param' => 'username',
 *                 // Other url params
 *                 'link_dependancy_params' => array(
 *                     0 => array(
 *                         // URL parameter name
 *                         // (can be array where url param has static value)
 *                         'param_info' => 'hostname',
 *                         // Column name related to url param
 *                         'column_name' => 'host'
 *                     )
 *                 ),
 *                 // Page to link
 *                 'default_page' => 'server_privileges.php'
 *             )
 *         )
 *     )
 * );
 *
 */
$GLOBALS['special_schema_links'] = array(
    'mysql' => array(
        'db' => array(
            'db' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'user' => array(
                'link_param' => 'username',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'hostname',
                        'column_name' => 'host'
                    )
                ),
                'default_page' => 'server_privileges.php'
            )
        ),
        'proc' => array(
            'db' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'user' => array(
            'user' => array(
                'link_param' => 'username',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'hostname',
                        'column_name' => 'host'
                    )
                ),
                'default_page' => 'server_privileges.php'
            )
        )
    ),
    'information_schema' => array(
        'columns' => array(
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'table_schema'
                    )
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
            'column_name' => array(
                'link_param' => array(
                    'sql_query',
                    'table_schema',
                    'table_name'
                ),
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'table_schema'
                    ),
                    1 => array(
                        'param_info' => 'table',
                        'column_name' => 'table_name'
                    )
                ),
                'default_page' => 'sql.php'
            )
        ),
        'column_privileges' => array(
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'events' => array(
            'event_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'files' => array(
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'key_column_usage' => array(
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'constraint_schema'
                    )
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
            'column_name' => array(
                'link_param' => array(
                    'sql_query',
                    'table_schema',
                    'table_name'
                ),
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'table_schema'
                    ),
                    1 => array(
                        'param_info' => 'table',
                        'column_name' => 'table_name'
                    )
                ),
                'default_page' => 'sql.php'
            ),
            'constraint_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'referenced_table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'referenced_table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'referenced_table_schema'
                    )
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
            'referenced_column_name' => array(
                'link_param' => array(
                    'sql_query',
                    'referenced_table_schema',
                    'referenced_table_name'
                ),
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'referenced_table_schema'
                    ),
                    1 => array(
                        'param_info' => 'table',
                        'column_name' => 'referenced_table_name'
                    )
                ),
                'default_page' => 'sql.php'
            )
        ),
        'parameters' => array(
            'specific_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'partitions' => array(
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'table_schema'
                    )
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            )
        ),
        'processlist' => array(
            'db' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'user' => array(
                'link_param' => 'username',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'hostname',
                        'column_name' => 'host'
                    )
                ),
                'default_page' => 'server_privileges.php'
            )
        ),
        'referential_constraints' => array(
            'constraint_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'unique_constraint_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'constraint_schema'
                    )
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
            'referenced_table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'constraint_schema'
                    )
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            )
        ),
        'routines' => array(
            'routine_name' => array(
                'link_param' => 'item_name',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'routine_schema'
                    ),
                    1 => array(
                        'param_info' => 'item_type',
                        'column_name' => 'routine_type'
                    )
                ),
                'default_page' => 'db_routines.php'
            ),
            'routine_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
        ),
        'schemata' => array(
            'schema_name' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'schema_privileges' => array(
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'statistics' => array(
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'index_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'table_schema'
                    )
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
            'column_name' => array(
                'link_param' => array(
                    'sql_query',
                    'table_schema',
                    'table_name'
                ),
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'table_schema'
                    ),
                    1 => array(
                        'param_info' => 'table',
                        'column_name' => 'table_name'
                    )
                ),
                'default_page' => 'sql.php'
            )
        ),
        'tables' => array(
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'table_schema'
                    )
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'table_constraints' => array(
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'table_schema'
                    )
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
            'constraint_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'table_privileges' => array(
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'triggers' => array(
            'trigger_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            ),
            'event_object_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'views' => array(
            'table_schema' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        )
    )
);

?>
