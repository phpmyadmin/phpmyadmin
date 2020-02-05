<?php
/**
 * Route definition file
 */
declare(strict_types=1);

use FastRoute\RouteCollector;
use PhpMyAdmin\Controllers\AjaxController;
use PhpMyAdmin\Controllers\BrowseForeignersController;
use PhpMyAdmin\Controllers\ChangeLogController;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Controllers\Database\CentralColumnsController;
use PhpMyAdmin\Controllers\Database\DataDictionaryController;
use PhpMyAdmin\Controllers\Database\DesignerController;
use PhpMyAdmin\Controllers\Database\EventsController;
use PhpMyAdmin\Controllers\Database\ExportController as DatabaseExportController;
use PhpMyAdmin\Controllers\Database\ImportController as DatabaseImportController;
use PhpMyAdmin\Controllers\Database\MultiTableQueryController;
use PhpMyAdmin\Controllers\Database\OperationsController;
use PhpMyAdmin\Controllers\Database\QueryByExampleController;
use PhpMyAdmin\Controllers\Database\RoutinesController;
use PhpMyAdmin\Controllers\Database\SearchController;
use PhpMyAdmin\Controllers\Database\SqlAutoCompleteController;
use PhpMyAdmin\Controllers\Database\SqlController as DatabaseSqlController;
use PhpMyAdmin\Controllers\Database\SqlFormatController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Controllers\Database\TrackingController;
use PhpMyAdmin\Controllers\Database\TriggersController;
use PhpMyAdmin\Controllers\ErrorReportController;
use PhpMyAdmin\Controllers\ExportController;
use PhpMyAdmin\Controllers\GisDataEditorController;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Controllers\ImportController;
use PhpMyAdmin\Controllers\ImportStatusController;
use PhpMyAdmin\Controllers\LicenseController;
use PhpMyAdmin\Controllers\LintController;
use PhpMyAdmin\Controllers\LogoutController;
use PhpMyAdmin\Controllers\NavigationController;
use PhpMyAdmin\Controllers\NormalizationController;
use PhpMyAdmin\Controllers\PhpInfoController;
use PhpMyAdmin\Controllers\Preferences\FeaturesController;
use PhpMyAdmin\Controllers\Preferences\FormsController;
use PhpMyAdmin\Controllers\Preferences\ManageController;
use PhpMyAdmin\Controllers\Preferences\NavigationController as PreferencesNavigationController;
use PhpMyAdmin\Controllers\Preferences\SqlController as PreferencesSqlController;
use PhpMyAdmin\Controllers\Preferences\TwoFactorController;
use PhpMyAdmin\Controllers\SchemaExportController;
use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Controllers\Server\DatabasesController;
use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\Controllers\Server\ExportController as ServerExportController;
use PhpMyAdmin\Controllers\Server\ImportController as ServerImportController;
use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\Controllers\Server\PrivilegesController;
use PhpMyAdmin\Controllers\Server\ReplicationController;
use PhpMyAdmin\Controllers\Server\SqlController as ServerSqlController;
use PhpMyAdmin\Controllers\Server\Status\AdvisorController;
use PhpMyAdmin\Controllers\Server\Status\MonitorController;
use PhpMyAdmin\Controllers\Server\Status\ProcessesController;
use PhpMyAdmin\Controllers\Server\Status\QueriesController;
use PhpMyAdmin\Controllers\Server\Status\StatusController;
use PhpMyAdmin\Controllers\Server\Status\VariablesController as StatusVariables;
use PhpMyAdmin\Controllers\Server\UserGroupsController;
use PhpMyAdmin\Controllers\Server\VariablesController;
use PhpMyAdmin\Controllers\SqlController;
use PhpMyAdmin\Controllers\Table\AddFieldController;
use PhpMyAdmin\Controllers\Table\ChangeController;
use PhpMyAdmin\Controllers\Table\ChartController;
use PhpMyAdmin\Controllers\Table\CreateController;
use PhpMyAdmin\Controllers\Table\ExportController as TableExportController;
use PhpMyAdmin\Controllers\Table\FindReplaceController;
use PhpMyAdmin\Controllers\Table\GetFieldController;
use PhpMyAdmin\Controllers\Table\GisVisualizationController;
use PhpMyAdmin\Controllers\Table\ImportController as TableImportController;
use PhpMyAdmin\Controllers\Table\IndexesController;
use PhpMyAdmin\Controllers\Table\OperationsController as TableOperationsController;
use PhpMyAdmin\Controllers\Table\RecentFavoriteController;
use PhpMyAdmin\Controllers\Table\RelationController;
use PhpMyAdmin\Controllers\Table\ReplaceController;
use PhpMyAdmin\Controllers\Table\RowActionController;
use PhpMyAdmin\Controllers\Table\SearchController as TableSearchController;
use PhpMyAdmin\Controllers\Table\SqlController as TableSqlController;
use PhpMyAdmin\Controllers\Table\StructureController as TableStructureController;
use PhpMyAdmin\Controllers\Table\TrackingController as TableTrackingController;
use PhpMyAdmin\Controllers\Table\TriggersController as TableTriggersController;
use PhpMyAdmin\Controllers\Table\ZoomSearchController;
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
        $routes->addRoute(['GET', 'POST'], '/central-columns', function () use ($containerBuilder) {
            /** @var CentralColumnsController $controller */
            $controller = $containerBuilder->get(CentralColumnsController::class);
            $controller->index();
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
        $routes->addRoute(['GET', 'POST'], '/export', function () use ($containerBuilder) {
            /** @var DatabaseExportController $controller */
            $controller = $containerBuilder->get(DatabaseExportController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/import', function () use ($containerBuilder) {
            /** @var DatabaseImportController $controller */
            $controller = $containerBuilder->get(DatabaseImportController::class);
            $controller->index();
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
            $routes->addRoute(['GET', 'POST'], '', function () use ($containerBuilder) {
                /** @var DatabaseSqlController $controller */
                $controller = $containerBuilder->get(DatabaseSqlController::class);
                $controller->index();
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
    $routes->addRoute(['GET', 'POST'], '/export', function () use ($containerBuilder) {
        /** @var ExportController $controller */
        $controller = $containerBuilder->get(ExportController::class);
        $controller->index();
    });
    $routes->addRoute(['GET', 'POST'], '/gis-data-editor', function () use ($containerBuilder, $response) {
        /** @var GisDataEditorController $controller */
        $controller = $containerBuilder->get(GisDataEditorController::class);
        $response->addJSON($controller->index());
    });
    $routes->addRoute(['GET', 'POST'], '/import', function () use ($containerBuilder) {
        /** @var ImportController $controller */
        $controller = $containerBuilder->get(ImportController::class);
        $controller->index();
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
    $routes->addRoute(['GET', 'POST'], '/navigation', function () use ($containerBuilder) {
        /** @var NavigationController $controller */
        $controller = $containerBuilder->get(NavigationController::class);
        $controller->index();
    });
    $routes->addRoute(['GET', 'POST'], '/normalization', function () use ($containerBuilder) {
        /** @var NormalizationController $controller */
        $controller = $containerBuilder->get(NormalizationController::class);
        $controller->index();
    });
    $routes->get('/phpinfo', function () use ($containerBuilder) {
        /** @var PhpInfoController $controller */
        $controller = $containerBuilder->get(PhpInfoController::class);
        $controller->index();
    });
    $routes->addGroup('/preferences', function (RouteCollector $routes) use ($containerBuilder) {
        $routes->addRoute(['GET', 'POST'], '/forms', function () use ($containerBuilder) {
            /** @var FormsController $controller */
            $controller = $containerBuilder->get(FormsController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/features', function () use ($containerBuilder) {
            /** @var FeaturesController $controller */
            $controller = $containerBuilder->get(FeaturesController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/manage', function () use ($containerBuilder) {
            /** @var ManageController $controller */
            $controller = $containerBuilder->get(ManageController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/navigation', function () use ($containerBuilder) {
            /** @var PreferencesNavigationController $controller */
            $controller = $containerBuilder->get(PreferencesNavigationController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/sql', function () use ($containerBuilder) {
            /** @var PreferencesSqlController $controller */
            $controller = $containerBuilder->get(PreferencesSqlController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/two-factor', function () use ($containerBuilder) {
            /** @var TwoFactorController $controller */
            $controller = $containerBuilder->get(TwoFactorController::class);
            $controller->index();
        });
    });
    $routes->addRoute(['GET', 'POST'], '/schema-export', function () use ($containerBuilder) {
        /** @var SchemaExportController $controller */
        $controller = $containerBuilder->get(SchemaExportController::class);
        $controller->index();
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
        $routes->addRoute(['GET', 'POST'], '/export', function () use ($containerBuilder) {
            /** @var ServerExportController $controller */
            $controller = $containerBuilder->get(ServerExportController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/import', function () use ($containerBuilder) {
            /** @var ServerImportController $controller */
            $controller = $containerBuilder->get(ServerImportController::class);
            $controller->index();
        });
        $routes->get('/plugins', function () use ($containerBuilder, $response) {
            /** @var PluginsController $controller */
            $controller = $containerBuilder->get(PluginsController::class);
            $response->addHTML($controller->index());
        });
        $routes->addRoute(['GET', 'POST'], '/privileges', function () use ($containerBuilder) {
            /** @var PrivilegesController $controller */
            $controller = $containerBuilder->get(PrivilegesController::class);
            $controller->index();
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
            /** @var ServerSqlController $controller */
            $controller = $containerBuilder->get(ServerSqlController::class);
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
        $routes->addRoute(['GET', 'POST'], '/user-groups', function () use ($containerBuilder) {
            /** @var UserGroupsController $controller */
            $controller = $containerBuilder->get(UserGroupsController::class);
            $controller->index();
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
    $routes->addGroup('/sql', function (RouteCollector $routes) use ($containerBuilder) {
        /** @var SqlController $controller */
        $controller = $containerBuilder->get(SqlController::class);
        $routes->addRoute(['GET', 'POST'], '', function () use ($controller) {
            $controller->index();
        });
        $routes->post('/get-relational-values', function () use ($controller) {
            $controller->getRelationalValues();
        });
        $routes->post('/get-enum-values', function () use ($controller) {
            $controller->getEnumValues();
        });
        $routes->post('/get-set-values', function () use ($controller) {
            $controller->getSetValues();
        });
        $routes->get('/get-default-fk-check-value', function () use ($controller) {
            $controller->getDefaultForeignKeyCheckValue();
        });
        $routes->post('/set-column-preferences', function () use ($controller) {
            $controller->setColumnOrderOrVisibility();
        });
    });
    $routes->addGroup('/table', function (RouteCollector $routes) use ($containerBuilder) {
        $routes->addRoute(['GET', 'POST'], '/add-field', function () use ($containerBuilder) {
            /** @var AddFieldController $controller */
            $controller = $containerBuilder->get(AddFieldController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/change', function () use ($containerBuilder) {
            /** @var ChangeController $controller */
            $controller = $containerBuilder->get(ChangeController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/chart', function () use ($containerBuilder) {
            /** @var ChartController $controller */
            $controller = $containerBuilder->get(ChartController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/create', function () use ($containerBuilder) {
            /** @var CreateController $controller */
            $controller = $containerBuilder->get(CreateController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/export', function () use ($containerBuilder) {
            /** @var TableExportController $controller */
            $controller = $containerBuilder->get(TableExportController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/find-replace', function () use ($containerBuilder) {
            /** @var FindReplaceController $controller */
            $controller = $containerBuilder->get(FindReplaceController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/get-field', function () use ($containerBuilder) {
            /** @var GetFieldController $controller */
            $controller = $containerBuilder->get(GetFieldController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/gis-visualization', function () use ($containerBuilder) {
            /** @var GisVisualizationController $controller */
            $controller = $containerBuilder->get(GisVisualizationController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/import', function () use ($containerBuilder) {
            /** @var TableImportController $controller */
            $controller = $containerBuilder->get(TableImportController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/indexes', function () use ($containerBuilder) {
            /** @var IndexesController $controller */
            $controller = $containerBuilder->get(IndexesController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/operations', function () use ($containerBuilder) {
            /** @var TableOperationsController $controller */
            $controller = $containerBuilder->get(TableOperationsController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/recent-favorite', function () use ($containerBuilder) {
            /** @var RecentFavoriteController $controller */
            $controller = $containerBuilder->get(RecentFavoriteController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/relation', function () use ($containerBuilder) {
            /** @var RelationController $controller */
            $controller = $containerBuilder->get(RelationController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/replace', function () use ($containerBuilder) {
            /** @var ReplaceController $controller */
            $controller = $containerBuilder->get(ReplaceController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/row-action', function () use ($containerBuilder) {
            /** @var RowActionController $controller */
            $controller = $containerBuilder->get(RowActionController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/search', function () use ($containerBuilder) {
            /** @var TableSearchController $controller */
            $controller = $containerBuilder->get(TableSearchController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/sql', function () use ($containerBuilder) {
            /** @var TableSqlController $controller */
            $controller = $containerBuilder->get(TableSqlController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/structure', function () use ($containerBuilder) {
            /** @var TableStructureController $controller */
            $controller = $containerBuilder->get(TableStructureController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', function () use ($containerBuilder) {
            /** @var TableTrackingController $controller */
            $controller = $containerBuilder->get(TableTrackingController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/triggers', function () use ($containerBuilder) {
            /** @var TableTriggersController $controller */
            $controller = $containerBuilder->get(TableTriggersController::class);
            $controller->index();
        });
        $routes->addRoute(['GET', 'POST'], '/zoom-search', function () use ($containerBuilder) {
            /** @var ZoomSearchController $controller */
            $controller = $containerBuilder->get(ZoomSearchController::class);
            $controller->index();
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
