<?php
/**
 * Route definition file
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use FastRoute\RouteCollector;
use PhpMyAdmin\Controllers\AjaxController;
use PhpMyAdmin\Controllers\BrowseForeignersController;
use PhpMyAdmin\Controllers\ChangeLogController;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Controllers\Database\DataDictionaryController;
use PhpMyAdmin\Controllers\Database\DesignerController;
use PhpMyAdmin\Controllers\Database\EventsController;
use PhpMyAdmin\Controllers\Database\MultiTableQueryController;
use PhpMyAdmin\Controllers\Database\OperationsController;
use PhpMyAdmin\Controllers\Database\QueryByExampleController;
use PhpMyAdmin\Controllers\Database\RoutinesController;
use PhpMyAdmin\Controllers\Database\SearchController;
use PhpMyAdmin\Controllers\Database\SqlAutoCompleteController;
use PhpMyAdmin\Controllers\Database\SqlFormatController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Controllers\Database\TrackingController;
use PhpMyAdmin\Controllers\Database\TriggersController;
use PhpMyAdmin\Controllers\ErrorReportController;
use PhpMyAdmin\Controllers\GisDataEditorController;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Controllers\ImportStatusController;
use PhpMyAdmin\Controllers\LicenseController;
use PhpMyAdmin\Controllers\LintController;
use PhpMyAdmin\Controllers\LogoutController;
use PhpMyAdmin\Controllers\PhpInfoController;
use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Controllers\Server\DatabasesController;
use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\Controllers\Server\ReplicationController;
use PhpMyAdmin\Controllers\Server\SqlController;
use PhpMyAdmin\Controllers\Server\Status\AdvisorController;
use PhpMyAdmin\Controllers\Server\Status\MonitorController;
use PhpMyAdmin\Controllers\Server\Status\ProcessesController;
use PhpMyAdmin\Controllers\Server\Status\QueriesController;
use PhpMyAdmin\Controllers\Server\Status\StatusController;
use PhpMyAdmin\Controllers\Server\Status\VariablesController as StatusVariables;
use PhpMyAdmin\Controllers\Server\VariablesController;
use PhpMyAdmin\Controllers\Table\TriggersController as TableTriggersController;
use PhpMyAdmin\Controllers\ThemesController;
use PhpMyAdmin\Controllers\TransformationOverviewController;
use PhpMyAdmin\Controllers\TransformationWrapperController;
use PhpMyAdmin\Controllers\UserPasswordController;
use PhpMyAdmin\Controllers\VersionCheckController;
use PhpMyAdmin\Controllers\ViewCreateController;
use PhpMyAdmin\Controllers\ViewOperationsController;
use PhpMyAdmin\Response;

global $containerBuilder;

if (! defined('PHPMYADMIN')) {
    exit;
}

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

return function (RouteCollector $routes) use ($containerBuilder, $response) {
    $routes->addGroup('', function (RouteCollector $routes) use ($containerBuilder, $response) {
        /** @var HomeController $controller */
        $controller = $containerBuilder->get(HomeController::class);
        $routes->addRoute(['GET', 'POST'], '[/]', function () use ($response, $controller) {
            $response->addHTML($controller->index(['access_time' => $_REQUEST['access_time'] ?? null]));
        });
        $routes->post('/set-theme', function () use ($controller) {
            $controller->setTheme(['set_theme' => $_POST['set_theme']]);
        });
        $routes->post('/collation-connection', function () use ($controller) {
            $controller->setCollationConnection(['collation_connection' => $_POST['collation_connection']]);
        });
        $routes->addRoute(['GET', 'POST'], '/recent-table', function () use ($response, $controller) {
            $response->addJSON($controller->reloadRecentTablesList());
        });
        $routes->addRoute(['GET', 'POST'], '/git-revision', function () use ($response, $controller) {
            $response->addHTML($controller->gitRevision());
        });
    });
    $routes->addGroup('/ajax', function (RouteCollector $routes) use ($containerBuilder, $response) {
        /** @var AjaxController $controller */
        $controller = $containerBuilder->get(AjaxController::class);
        $routes->post('/list-databases', function () use ($response, $controller) {
            $response->addJSON($controller->databases());
        });
        $routes->post('/list-tables/{database}', function (array $vars) use ($response, $controller) {
            $response->addJSON($controller->tables($vars));
        });
        $routes->post('/list-columns/{database}/{table}', function (array $vars) use ($response, $controller) {
            $response->addJSON($controller->columns($vars));
        });
        $routes->post('/config-get', function () use ($response, $controller) {
            $response->addJSON($controller->getConfig([
                'key' => $_POST['key'] ?? null,
            ]));
        });
        $routes->post('/config-set', function () use ($response, $controller) {
            $response->addJSON($controller->setConfig([
                'key' => $_POST['key'] ?? null,
                'value' => $_POST['value'] ?? null,
            ]));
        });
    });
    $routes->addRoute(['GET', 'POST'], '/browse-foreigners', function () use ($containerBuilder, $response) {
        /** @var BrowseForeignersController $controller */
        $controller = $containerBuilder->get(BrowseForeignersController::class);
        $response->addHTML($controller->index([
            'db' => $_POST['db'] ?? null,
            'table' => $_POST['table'] ?? null,
            'field' => $_POST['field'] ?? null,
            'fieldkey' => $_POST['fieldkey'] ?? null,
            'data' => $_POST['data'] ?? null,
            'foreign_showAll' => $_POST['foreign_showAll'] ?? null,
            'foreign_filter' => $_POST['foreign_filter'] ?? null,
        ]));
    });
    $routes->get('/changelog', function () use ($containerBuilder) {
        /** @var ChangeLogController $controller */
        $controller = $containerBuilder->get(ChangeLogController::class);
        $controller->index();
    });
    $routes->addRoute(['GET', 'POST'], '/check-relations', function () use ($containerBuilder, $response) {
        /** @var CheckRelationsController $controller */
        $controller = $containerBuilder->get(CheckRelationsController::class);
        $response->addHTML($controller->index([
            'create_pmadb' => $_POST['create_pmadb'] ?? null,
            'fixall_pmadb' => $_POST['fixall_pmadb'] ?? null,
            'fix_pmadb' => $_POST['fix_pmadb'] ?? null,
        ]));
    });
    $routes->addGroup('/database', function (RouteCollector $routes) use ($containerBuilder, $response) {
        $routes->addRoute(['GET', 'POST'], '/central_columns', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/central_columns.php';
        });
        $routes->get('/data-dictionary/{database}', function (array $vars) use ($containerBuilder, $response) {
            /** @var DataDictionaryController $controller */
            $controller = $containerBuilder->get(DataDictionaryController::class);
            $response->addHTML($controller->index($vars));
        });
        $routes->addRoute(['GET', 'POST'], '/designer', function () use ($containerBuilder) {
            /** @var DesignerController $controller */
            $controller = $containerBuilder->get(DesignerController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/events', function () use ($containerBuilder) {
            /** @var EventsController $controller */
            $controller = $containerBuilder->get(EventsController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/export', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/export.php';
        });
        $routes->addRoute(['GET', 'POST'], '/import', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/import.php';
        });
        $routes->addGroup('/multi_table_query', function (RouteCollector $routes) use ($containerBuilder, $response) {
            /** @var MultiTableQueryController $controller */
            $controller = $containerBuilder->get(MultiTableQueryController::class);
            $routes->get('', function () use ($response, $controller) {
                $response->addHTML($controller->index());
            });
            $routes->get('/tables', function () use ($response, $controller) {
                $response->addJSON($controller->table([
                    'tables' => $_GET['tables'],
                    'db' => $_GET['db'] ?? null,
                ]));
            });
            $routes->post('/query', function () use ($controller) {
                $controller->displayResults([
                    'sql_query' => $_POST['sql_query'],
                    'db' => $_POST['db'] ?? $_GET['db'] ?? null,
                ]);
            });
        });
        $routes->addRoute(['GET', 'POST'], '/operations', function () use ($containerBuilder) {
            /** @var OperationsController $controller */
            $controller = $containerBuilder->get(OperationsController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/qbe', function () use ($containerBuilder) {
            /** @var QueryByExampleController $controller */
            $controller = $containerBuilder->get(QueryByExampleController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/routines', function () use ($containerBuilder) {
            /** @var RoutinesController $controller */
            $controller = $containerBuilder->get(RoutinesController::class);
            $controller->index([
                'type' => $_REQUEST['type'] ?? null,
            ]);
        });
        $routes->addRoute(['GET', 'POST'], '/search', function () use ($containerBuilder) {
            /** @var SearchController $controller */
            $controller = $containerBuilder->get(SearchController::class);
            $controller->index();
        });
        $routes->addGroup('/sql', function (RouteCollector $routes) use ($containerBuilder, $response) {
            $routes->addRoute(['GET', 'POST'], '', function () {
                require_once ROOT_PATH . 'libraries/entry_points/database/sql.php';
            });
            $routes->post('/autocomplete', function () use ($containerBuilder, $response) {
                /** @var SqlAutoCompleteController $controller */
                $controller = $containerBuilder->get(SqlAutoCompleteController::class);
                $response->addJSON($controller->index());
            });
            $routes->post('/format', function () use ($containerBuilder, $response) {
                /** @var SqlFormatController $controller */
                $controller = $containerBuilder->get(SqlFormatController::class);
                $response->addJSON($controller->index(['sql' => $_POST['sql'] ?? null]));
            });
        });
        $routes->addGroup('/structure', function (RouteCollector $routes) use ($containerBuilder, $response) {
            /** @var StructureController $controller */
            $controller = $containerBuilder->get(StructureController::class);
            $routes->addRoute(['GET', 'POST'], '', function () use ($response, $controller) {
                $response->addHTML($controller->index([
                    'submit_mult' => $_POST['submit_mult'] ?? null,
                    'selected_tbl' => $_POST['selected_tbl'] ?? null,
                    'mult_btn' => $_POST['mult_btn'] ?? null,
                    'sort' => $_REQUEST['sort'] ?? null,
                    'sort_order' => $_REQUEST['sort_order'] ?? null,
                ]));
            });
            $routes->addRoute(['GET', 'POST'], '/favorite-table', function () use ($response, $controller) {
                $response->addJSON($controller->addRemoveFavoriteTablesAction([
                    'favorite_table' => $_REQUEST['favorite_table'] ?? null,
                    'favoriteTables' => $_REQUEST['favoriteTables'] ?? null,
                    'sync_favorite_tables' => $_REQUEST['sync_favorite_tables'] ?? null,
                    'add_favorite' => $_REQUEST['add_favorite'] ?? null,
                    'remove_favorite' => $_REQUEST['remove_favorite'] ?? null,
                ]));
            });
            $routes->addRoute(['GET', 'POST'], '/real-row-count', function () use ($response, $controller) {
                $response->addJSON($controller->handleRealRowCountRequestAction([
                    'real_row_count_all' => $_REQUEST['real_row_count_all'] ?? null,
                    'table' => $_REQUEST['table'] ?? null,
                ]));
            });
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', function () use ($containerBuilder) {
            /** @var TrackingController $controller */
            $controller = $containerBuilder->get(TrackingController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/triggers', function () use ($containerBuilder) {
            /** @var TriggersController $controller */
            $controller = $containerBuilder->get(TriggersController::class);
            $controller->index();
        });
    });
    $routes->addRoute(['GET', 'POST'], '/error-report', function () use ($containerBuilder) {
        /** @var ErrorReportController $controller */
        $controller = $containerBuilder->get(ErrorReportController::class);
        $controller->index();
    });
    $routes->addRoute(['GET', 'POST'], '/export', function () {
        require_once ROOT_PATH . 'libraries/entry_points/export.php';
    });
    $routes->addRoute(['GET', 'POST'], '/gis-data-editor', function () use ($containerBuilder, $response) {
        /** @var GisDataEditorController $controller */
        $controller = $containerBuilder->get(GisDataEditorController::class);
        $response->addJSON($controller->index());
    });
    $routes->addRoute(['GET', 'POST'], '/import', function () {
        require_once ROOT_PATH . 'libraries/entry_points/import.php';
    });
    $routes->addRoute(['GET', 'POST'], '/import-status', function () use ($containerBuilder) {
        /** @var ImportStatusController $controller */
        $controller = $containerBuilder->get(ImportStatusController::class);
        $controller->index();
    });
    $routes->get('/license', function () use ($containerBuilder) {
        /** @var LicenseController $controller */
        $controller = $containerBuilder->get(LicenseController::class);
        $controller->index();
    });
    $routes->addRoute(['GET', 'POST'], '/lint', function () use ($containerBuilder) {
        /** @var LintController $controller */
        $controller = $containerBuilder->get(LintController::class);
        $controller->index([
            'sql_query' => $_POST['sql_query'] ?? null,
            'options' => $_POST['options'] ?? null,
        ]);
    });
    $routes->addRoute(['GET', 'POST'], '/logout', function () use ($containerBuilder) {
        /** @var LogoutController $controller */
        $controller = $containerBuilder->get(LogoutController::class);
        $controller->index();
    });
    $routes->addRoute(['GET', 'POST'], '/navigation', function () {
        require_once ROOT_PATH . 'libraries/entry_points/navigation.php';
    });
    $routes->addRoute(['GET', 'POST'], '/normalization', function () {
        require_once ROOT_PATH . 'libraries/entry_points/normalization.php';
    });
    $routes->get('/phpinfo', function () use ($containerBuilder) {
        /** @var PhpInfoController $controller */
        $controller = $containerBuilder->get(PhpInfoController::class);
        $controller->index();
    });
    $routes->addGroup('/preferences', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/forms', function () {
            require_once ROOT_PATH . 'libraries/entry_points/preferences/forms.php';
        });
        $routes->addRoute(['GET', 'POST'], '/manage', function () {
            require_once ROOT_PATH . 'libraries/entry_points/preferences/manage.php';
        });
        $routes->addRoute(['GET', 'POST'], '/twofactor', function () {
            require_once ROOT_PATH . 'libraries/entry_points/preferences/twofactor.php';
        });
    });
    $routes->addRoute(['GET', 'POST'], '/schema_export', function () {
        require_once ROOT_PATH . 'libraries/entry_points/schema_export.php';
    });
    $routes->addGroup('/server', function (RouteCollector $routes) use ($containerBuilder, $response) {
        $routes->addRoute(['GET', 'POST'], '/binlog', function () use ($containerBuilder, $response) {
            /** @var BinlogController $controller */
            $controller = $containerBuilder->get(BinlogController::class);
            $response->addHTML($controller->index([
                'log' => $_POST['log'] ?? null,
                'pos' => $_POST['pos'] ?? null,
                'is_full_query' => $_POST['is_full_query'] ?? null,
            ]));
        });
        $routes->get('/collations', function () use ($containerBuilder, $response) {
            /** @var CollationsController $controller */
            $controller = $containerBuilder->get(CollationsController::class);
            $response->addHTML($controller->index());
        });
        $routes->addGroup('/databases', function (RouteCollector $routes) use ($containerBuilder, $response) {
            /** @var DatabasesController $controller */
            $controller = $containerBuilder->get(DatabasesController::class);
            $routes->addRoute(['GET', 'POST'], '', function () use ($response, $controller) {
                $response->addHTML($controller->index([
                    'statistics' => $_REQUEST['statistics'] ?? null,
                    'pos' => $_REQUEST['pos'] ?? null,
                    'sort_by' => $_REQUEST['sort_by'] ?? null,
                    'sort_order' => $_REQUEST['sort_order'] ?? null,
                ]));
            });
            $routes->post('/create', function () use ($response, $controller) {
                $response->addJSON($controller->create([
                    'new_db' => $_POST['new_db'] ?? null,
                    'db_collation' => $_POST['db_collation'] ?? null,
                ]));
            });
            $routes->post('/destroy', function () use ($response, $controller) {
                $response->addJSON($controller->destroy([
                    'drop_selected_dbs' => $_POST['drop_selected_dbs'] ?? null,
                    'selected_dbs' => $_POST['selected_dbs'] ?? null,
                ]));
            });
        });
        $routes->addGroup('/engines', function (RouteCollector $routes) use ($containerBuilder, $response) {
            /** @var EnginesController $controller */
            $controller = $containerBuilder->get(EnginesController::class);
            $routes->get('', function () use ($response, $controller) {
                $response->addHTML($controller->index());
            });
            $routes->get('/{engine}[/{page}]', function (array $vars) use ($response, $controller) {
                $response->addHTML($controller->show($vars));
            });
        });
        $routes->addRoute(['GET', 'POST'], '/export', function () {
            require_once ROOT_PATH . 'libraries/entry_points/server/export.php';
        });
        $routes->addRoute(['GET', 'POST'], '/import', function () {
            require_once ROOT_PATH . 'libraries/entry_points/server/import.php';
        });
        $routes->get('/plugins', function () use ($containerBuilder, $response) {
            /** @var PluginsController $controller */
            $controller = $containerBuilder->get(PluginsController::class);
            $response->addHTML($controller->index());
        });
        $routes->addRoute(['GET', 'POST'], '/privileges', function () {
            require_once ROOT_PATH . 'libraries/entry_points/server/privileges.php';
        });
        $routes->addRoute(['GET', 'POST'], '/replication', function () use ($containerBuilder, $response) {
            /** @var ReplicationController $controller */
            $controller = $containerBuilder->get(ReplicationController::class);
            $response->addHTML($controller->index([
                'url_params' => $_POST['url_params'] ?? null,
                'mr_configure' => $_POST['mr_configure'] ?? null,
                'sl_configure' => $_POST['sl_configure'] ?? null,
                'repl_clear_scr' => $_POST['repl_clear_scr'] ?? null,
            ]));
        });
        $routes->addRoute(['GET', 'POST'], '/sql', function () use ($containerBuilder, $response) {
            /** @var SqlController $controller */
            $controller = $containerBuilder->get(SqlController::class);
            $response->addHTML($controller->index());
        });
        $routes->addGroup('/status', function (RouteCollector $routes) use ($containerBuilder, $response) {
            $routes->get('', function () use ($containerBuilder, $response) {
                /** @var StatusController $controller */
                $controller = $containerBuilder->get(StatusController::class);
                $response->addHTML($controller->index());
            });
            $routes->get('/advisor', function () use ($containerBuilder, $response) {
                /** @var AdvisorController $controller */
                $controller = $containerBuilder->get(AdvisorController::class);
                $response->addHTML($controller->index());
            });
            $routes->addGroup('/monitor', function (RouteCollector $routes) use ($containerBuilder, $response) {
                /** @var MonitorController $controller */
                $controller = $containerBuilder->get(MonitorController::class);
                $routes->get('', function () use ($response, $controller) {
                    $response->addHTML($controller->index());
                });
                $routes->post('/chart', function () use ($response, $controller) {
                    $response->addJSON($controller->chartingData([
                        'requiredData' => $_POST['requiredData'] ?? null,
                    ]));
                });
                $routes->post('/slow-log', function () use ($response, $controller) {
                    $response->addJSON($controller->logDataTypeSlow([
                        'time_start' => $_POST['time_start'] ?? null,
                        'time_end' => $_POST['time_end'] ?? null,
                    ]));
                });
                $routes->post('/general-log', function () use ($response, $controller) {
                    $response->addJSON($controller->logDataTypeGeneral([
                        'time_start' => $_POST['time_start'] ?? null,
                        'time_end' => $_POST['time_end'] ?? null,
                        'limitTypes' => $_POST['limitTypes'] ?? null,
                        'removeVariables' => $_POST['removeVariables'] ?? null,
                    ]));
                });
                $routes->post('/log-vars', function () use ($response, $controller) {
                    $response->addJSON($controller->loggingVars([
                        'varName' => $_POST['varName'] ?? null,
                        'varValue' => $_POST['varValue'] ?? null,
                    ]));
                });
                $routes->post('/query', function () use ($response, $controller) {
                    $response->addJSON($controller->queryAnalyzer([
                        'database' => $_POST['database'] ?? null,
                        'query' => $_POST['query'] ?? null,
                    ]));
                });
            });
            $routes->addGroup('/processes', function (RouteCollector $routes) use ($containerBuilder, $response) {
                /** @var ProcessesController $controller */
                $controller = $containerBuilder->get(ProcessesController::class);
                $routes->addRoute(['GET', 'POST'], '', function () use ($response, $controller) {
                    $response->addHTML($controller->index([
                        'showExecuting' => $_POST['showExecuting'] ?? null,
                        'full' => $_POST['full'] ?? null,
                        'column_name' => $_POST['column_name'] ?? null,
                        'order_by_field' => $_POST['order_by_field'] ?? null,
                        'sort_order' => $_POST['sort_order'] ?? null,
                    ]));
                });
                $routes->post('/refresh', function () use ($response, $controller) {
                    $response->addHTML($controller->refresh([
                        'showExecuting' => $_POST['showExecuting'] ?? null,
                        'full' => $_POST['full'] ?? null,
                        'column_name' => $_POST['column_name'] ?? null,
                        'order_by_field' => $_POST['order_by_field'] ?? null,
                        'sort_order' => $_POST['sort_order'] ?? null,
                    ]));
                });
                $routes->post('/kill/{id:\d+}', function (array $vars) use ($response, $controller) {
                    $response->addJSON($controller->kill($vars));
                });
            });
            $routes->get('/queries', function () use ($containerBuilder, $response) {
                /** @var QueriesController $controller */
                $controller = $containerBuilder->get(QueriesController::class);
                $response->addHTML($controller->index());
            });
            $routes->addRoute(['GET', 'POST'], '/variables', function () use ($containerBuilder, $response) {
                /** @var StatusVariables $controller */
                $controller = $containerBuilder->get(StatusVariables::class);
                $response->addHTML($controller->index([
                    'flush' => $_POST['flush'] ?? null,
                    'filterAlert' => $_POST['filterAlert'] ?? null,
                    'filterText' => $_POST['filterText'] ?? null,
                    'filterCategory' => $_POST['filterCategory'] ?? null,
                    'dontFormat' => $_POST['dontFormat'] ?? null,
                ]));
            });
        });
        $routes->addRoute(['GET', 'POST'], '/user_groups', function () {
            require_once ROOT_PATH . 'libraries/entry_points/server/user_groups.php';
        });
        $routes->addGroup('/variables', function (RouteCollector $routes) use ($containerBuilder, $response) {
            /** @var VariablesController $controller */
            $controller = $containerBuilder->get(VariablesController::class);
            $routes->get('', function () use ($response, $controller) {
                $response->addHTML($controller->index([
                    'filter' => $_GET['filter'] ?? null,
                ]));
            });
            $routes->get('/get/{name}', function (array $vars) use ($response, $controller) {
                $response->addJSON($controller->getValue($vars));
            });
            $routes->post('/set/{name}', function (array $vars) use ($response, $controller) {
                $response->addJSON($controller->setValue([
                    'varName' => $vars['name'],
                    'varValue' => $_POST['varValue'] ?? null,
                ]));
            });
        });
    });
    $routes->addRoute(['GET', 'POST'], '/sql', function () {
        require_once ROOT_PATH . 'libraries/entry_points/sql.php';
    });
    $routes->addGroup('/table', function (RouteCollector $routes) use ($containerBuilder) {
        $routes->addRoute(['GET', 'POST'], '/addfield', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/addfield.php';
        });
        $routes->addRoute(['GET', 'POST'], '/change', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/change.php';
        });
        $routes->addRoute(['GET', 'POST'], '/chart', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/chart.php';
        });
        $routes->addRoute(['GET', 'POST'], '/create', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/create.php';
        });
        $routes->addRoute(['GET', 'POST'], '/export', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/export.php';
        });
        $routes->addRoute(['GET', 'POST'], '/find_replace', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/find_replace.php';
        });
        $routes->addRoute(['GET', 'POST'], '/get_field', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/get_field.php';
        });
        $routes->addRoute(['GET', 'POST'], '/gis_visualization', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/gis_visualization.php';
        });
        $routes->addRoute(['GET', 'POST'], '/import', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/import.php';
        });
        $routes->addRoute(['GET', 'POST'], '/indexes', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/indexes.php';
        });
        $routes->addRoute(['GET', 'POST'], '/operations', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/operations.php';
        });
        $routes->addRoute(['GET', 'POST'], '/recent_favorite', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/recent_favorite.php';
        });
        $routes->addRoute(['GET', 'POST'], '/relation', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/relation.php';
        });
        $routes->addRoute(['GET', 'POST'], '/replace', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/replace.php';
        });
        $routes->addRoute(['GET', 'POST'], '/row_action', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/row_action.php';
        });
        $routes->addRoute(['GET', 'POST'], '/search', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/select.php';
        });
        $routes->addRoute(['GET', 'POST'], '/sql', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/sql.php';
        });
        $routes->addRoute(['GET', 'POST'], '/structure', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/structure.php';
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/tracking.php';
        });
        $routes->addRoute(['GET', 'POST'], '/triggers', function () use ($containerBuilder) {
            /** @var TableTriggersController $controller */
            $controller = $containerBuilder->get(TableTriggersController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/zoom_select', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/zoom_select.php';
        });
    });
    $routes->get('/themes', function () use ($containerBuilder, $response) {
        /** @var ThemesController $controller */
        $controller = $containerBuilder->get(ThemesController::class);
        $response->addHTML($controller->index());
    });
    $routes->addGroup('/transformation', function (RouteCollector $routes) use ($containerBuilder, $response) {
        $routes->addRoute(['GET', 'POST'], '/overview', function () use ($containerBuilder, $response) {
            /** @var TransformationOverviewController $controller */
            $controller = $containerBuilder->get(TransformationOverviewController::class);
            $response->addHTML($controller->index());
        });
        $routes->addRoute(['GET', 'POST'], '/wrapper', function () use ($containerBuilder) {
            /** @var TransformationWrapperController $controller */
            $controller = $containerBuilder->get(TransformationWrapperController::class);
            $controller->index();
        });
    });
    $routes->addRoute(['GET', 'POST'], '/user-password', function () use ($containerBuilder) {
        /** @var UserPasswordController $controller */
        $controller = $containerBuilder->get(UserPasswordController::class);
        $controller->index();
    });
    $routes->addRoute(['GET', 'POST'], '/version-check', function () use ($containerBuilder) {
        /** @var VersionCheckController $controller */
        $controller = $containerBuilder->get(VersionCheckController::class);
        $controller->index();
    });
    $routes->addGroup('/view', function (RouteCollector $routes) use ($containerBuilder) {
        $routes->addRoute(['GET', 'POST'], '/create', function () use ($containerBuilder) {
            /** @var ViewCreateController $controller */
            $controller = $containerBuilder->get(ViewCreateController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/operations', function () use ($containerBuilder) {
            /** @var ViewOperationsController $controller */
            $controller = $containerBuilder->get(ViewOperationsController::class);
            $controller->index();
        });
    });
};
