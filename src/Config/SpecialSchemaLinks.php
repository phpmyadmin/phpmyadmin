<?php
/**
 * Links configuration for MySQL system tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Url;

class SpecialSchemaLinks
{
    /** @var array<'mysql'|'information_schema',
     *   array<string,
     *    array<string,
     *     array{
     *      'link_param': string,
     *      'link_dependancy_params'?: array<
     *       int,
     *       array{'param_info': string, 'column_name': string}
     *      >,
     *      'default_page': string
     *     }
     *    >
     *   >
     *  > */
    private static array $specialSchemaLinks = [];

    /**
     * This array represent the details for generating links inside
     * special schemas like mysql, information_schema etc.
     * Major element represent a schema.
     * All the strings in this array represented in lower case
     *
     * @param 'mysql'|'information_schema' $database
     *
     * @return array{
     *   'link_param': string,
     *   'link_dependancy_params'?: array<
     *     int,
     *     array{'param_info': string, 'column_name': string}
     *   >,
     *   'default_page': string
     * }|null
     */
    public static function get(string $database, string $table, string $column): array|null
    {
        if (self::$specialSchemaLinks === []) {
            self::setSpecialSchemaLinks();
        }

        return self::$specialSchemaLinks[$database][$table][$column] ?? null;
    }

    private static function setSpecialSchemaLinks(): void
    {
        $config = Config::getInstance();
        $defaultPageDatabase = './' . Url::getFromRoute($config->config->DefaultTabDatabase);
        $defaultPageTable = './' . Url::getFromRoute($config->config->DefaultTabTable);

        self::$specialSchemaLinks = [
            'mysql' => [
                'columns_priv' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [['param_info' => 'hostname', 'column_name' => 'host']],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'Db']],
                        'default_page' => $defaultPageTable,
                    ],
                    'column_name' => [
                        'link_param' => 'field',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'Db'],
                            ['param_info' => 'table', 'column_name' => 'Table_name'],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure/change', ['change_column' => 1]),
                    ],
                ],
                'db' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [['param_info' => 'hostname', 'column_name' => 'host']],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                ],
                'event' => [
                    'name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'db']],
                        'default_page' => './' . Url::getFromRoute('/database/events', ['edit_item' => 1]),
                    ],

                ],
                'innodb_index_stats' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'database_name']],
                        'default_page' => $defaultPageTable,
                    ],
                    'index_name' => [
                        'link_param' => 'index',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'database_name'],
                            ['param_info' => 'table', 'column_name' => 'table_name'],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure'),
                    ],
                ],
                'innodb_table_stats' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'database_name']],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'proc' => [
                    'name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'db'],
                            ['param_info' => 'item_type', 'column_name' => 'type'],
                        ],
                        'default_page' => './' . Url::getFromRoute('/database/routines', ['edit_item' => 1]),
                    ],
                    'specific_name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'db'],
                            ['param_info' => 'item_type', 'column_name' => 'type'],
                        ],
                        'default_page' => './' . Url::getFromRoute('/database/routines', ['edit_item' => 1]),
                    ],
                ],
                'proc_priv' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [['param_info' => 'hostname', 'column_name' => 'Host']],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                    'routine_name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'Db'],
                            ['param_info' => 'item_type', 'column_name' => 'Routine_type'],
                        ],
                        'default_page' => './' . Url::getFromRoute('/database/routines', ['edit_item' => 1]),
                    ],
                ],
                'proxies_priv' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [['param_info' => 'hostname', 'column_name' => 'Host']],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                ],
                'tables_priv' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [['param_info' => 'hostname', 'column_name' => 'Host']],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'Db']],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'user' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [['param_info' => 'hostname', 'column_name' => 'host']],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                ],
            ],
            'information_schema' => [
                'columns' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'table_schema']],
                        'default_page' => $defaultPageTable,
                    ],
                    'column_name' => [
                        'link_param' => 'field',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'table_schema'],
                            ['param_info' => 'table', 'column_name' => 'table_name'],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure/change', ['change_column' => 1]),
                    ],
                ],
                'key_column_usage' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'constraint_schema']],
                        'default_page' => $defaultPageTable,
                    ],
                    'column_name' => [
                        'link_param' => 'field',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'table_schema'],
                            ['param_info' => 'table', 'column_name' => 'table_name'],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure/change', ['change_column' => 1]),
                    ],
                    'referenced_table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'referenced_table_schema'],
                        ],
                        'default_page' => $defaultPageTable,
                    ],
                    'referenced_column_name' => [
                        'link_param' => 'field',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'referenced_table_schema'],
                            ['param_info' => 'table', 'column_name' => 'referenced_table_name'],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure/change', ['change_column' => 1]),
                    ],
                ],
                'partitions' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'table_schema']],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'processlist' => [
                    'user' => [
                        'link_param' => 'username',
                        'link_dependancy_params' => [['param_info' => 'hostname', 'column_name' => 'host']],
                        'default_page' => './' . Url::getFromRoute('/server/privileges'),
                    ],
                ],
                'referential_constraints' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'constraint_schema']],
                        'default_page' => $defaultPageTable,
                    ],
                    'referenced_table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'constraint_schema']],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'routines' => [
                    'routine_name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'routine_schema'],
                            ['param_info' => 'item_type', 'column_name' => 'routine_type'],
                        ],
                        'default_page' => './' . Url::getFromRoute('/database/routines'),
                    ],
                ],
                'schemata' => ['schema_name' => ['link_param' => 'db', 'default_page' => $defaultPageDatabase]],
                'statistics' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'table_schema']],
                        'default_page' => $defaultPageTable,
                    ],
                    'column_name' => [
                        'link_param' => 'field',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'table_schema'],
                            ['param_info' => 'table', 'column_name' => 'table_name'],
                        ],
                        'default_page' => './' . Url::getFromRoute('/table/structure/change', ['change_column' => 1]),
                    ],
                ],
                'tables' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'table_schema']],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'table_constraints' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'table_schema']],
                        'default_page' => $defaultPageTable,
                    ],
                ],
                'views' => [
                    'table_name' => [
                        'link_param' => 'table',
                        'link_dependancy_params' => [['param_info' => 'db', 'column_name' => 'table_schema']],
                        'default_page' => $defaultPageTable,
                    ],
                ],
            ],
        ];
    }
}
