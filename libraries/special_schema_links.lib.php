<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Links configuration for MySQL system tables
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
        'columns_priv' => array(
            'user' => array(
                'link_param' => 'username',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'hostname',
                        'column_name' => 'host'
                    )
                ),
                'default_page' => 'server_privileges.php'
            ),
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'Db'
                    ),
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
            'column_name' => array(
                'link_param' => 'field',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'Db'
                    ),
                    1 => array(
                        'param_info' => 'table',
                        'column_name' => 'Table_name'
                    )
                ),
                'default_page' => 'tbl_structure.php?change_column=1'
            ),
        ),
        'db' => array(
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
        'event' => array(
            'name' => array(
                'link_param' => 'item_name',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'db'
                    )
                ),
                'default_page' => 'db_events.php?edit_item=1'
            ),

        ),
        'innodb_index_stats' => array(
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'database_name'
                    ),
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
            'index_name' => array(
                'link_param' => 'index',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'database_name'
                    ),
                    1 => array(
                        'param_info' => 'table',
                        'column_name' => 'table_name'
                    )
                ),
                'default_page' => 'tbl_structure.php'
            ),
        ),
        'innodb_table_stats' => array(
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'database_name'
                    ),
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
        ),
        'proc' => array(
            'name' => array(
                'link_param' => 'item_name',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'db'
                    ),
                    1 => array(
                        'param_info' => 'item_type',
                        'column_name' => 'type'
                    )
                ),
                'default_page' => 'db_routines.php?edit_item=1'
            ),
            'specific_name' => array(
                'link_param' => 'item_name',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'db'
                    ),
                    1 => array(
                        'param_info' => 'item_type',
                        'column_name' => 'type'
                    )
                ),
                'default_page' => 'db_routines.php?edit_item=1'
            ),
        ),
        'proc_priv' => array(
            'user' => array(
                'link_param' => 'username',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'hostname',
                        'column_name' => 'Host'
                    )
                ),
                'default_page' => 'server_privileges.php'
            ),
            'routine_name' => array(
                'link_param' => 'item_name',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'Db'
                    ),
                    1 => array(
                        'param_info' => 'item_type',
                        'column_name' => 'Routine_type'
                    )
                ),
                'default_page' => 'db_routines.php?edit_item=1'
            ),
        ),
        'proxies_priv' => array(
            'user' => array(
                'link_param' => 'username',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'hostname',
                        'column_name' => 'Host'
                    )
                ),
                'default_page' => 'server_privileges.php'
            ),
        ),
        'tables_priv' => array(
            'user' => array(
                'link_param' => 'username',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'hostname',
                        'column_name' => 'Host'
                    )
                ),
                'default_page' => 'server_privileges.php'
            ),
            'table_name' => array(
                'link_param' => 'table',
                'link_dependancy_params' => array(
                    0 => array(
                        'param_info' => 'db',
                        'column_name' => 'Db'
                    ),
                ),
                'default_page' => $GLOBALS['cfg']['DefaultTabTable']
            ),
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
                'link_param' => 'field',
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
                'default_page' => 'tbl_structure.php?change_column=1'
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
                'link_param' => 'field',
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
                'default_page' => 'tbl_structure.php?change_column=1'
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
                'link_param' => 'field',
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
                'default_page' => 'tbl_structure.php?change_column=1'
            )
        ),
        'partitions' => array(
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
        ),
        'schemata' => array(
            'schema_name' => array(
                'link_param' => 'db',
                'default_page' => $GLOBALS['cfg']['DefaultTabDatabase']
            )
        ),
        'statistics' => array(
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
                'link_param' => 'field',
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
                'default_page' => 'tbl_structure.php?change_column=1'
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
        ),
        'views' => array(
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
        ),
    )
);

?>
