<?php

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
use PhpMyAdmin\Controllers\Preferences\ExportController as PreferencesExportController;
use PhpMyAdmin\Controllers\Preferences\FeaturesController;
use PhpMyAdmin\Controllers\Preferences\ImportController as PreferencesImportController;
use PhpMyAdmin\Controllers\Preferences\MainPanelController;
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
use PhpMyAdmin\Controllers\Table\DeleteController;
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

if (! defined('PHPMYADMIN')) {
    exit;
}

return function (RouteCollector $routes) {
    $routes->addGroup('', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '[/]', [HomeController::class, 'index']);
        $routes->post('/set-theme', [HomeController::class, 'setTheme']);
        $routes->post('/collation-connection', [HomeController::class, 'setCollationConnection']);
        $routes->addRoute(['GET', 'POST'], '/recent-table', [HomeController::class, 'reloadRecentTablesList']);
        $routes->addRoute(['GET', 'POST'], '/git-revision', [HomeController::class, 'gitRevision']);
    });
    $routes->addGroup('/ajax', function (RouteCollector $routes) {
        $routes->post('/list-databases', [AjaxController::class, 'databases']);
        $routes->post('/list-tables', [AjaxController::class, 'tables']);
        $routes->post('/list-columns', [AjaxController::class, 'columns']);
        $routes->post('/config-get', [AjaxController::class, 'getConfig']);
        $routes->post('/config-set', [AjaxController::class, 'setConfig']);
    });
    $routes->addRoute(['GET', 'POST'], '/browse-foreigners', [BrowseForeignersController::class, 'index']);
    $routes->get('/changelog', [ChangeLogController::class, 'index']);
    $routes->addRoute(['GET', 'POST'], '/check-relations', [CheckRelationsController::class, 'index']);
    $routes->addGroup('/database', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/central-columns', [CentralColumnsController::class, 'index']);
        $routes->get('/data-dictionary', [DataDictionaryController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/designer', [DesignerController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/events', [EventsController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/export', [DatabaseExportController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/import', [DatabaseImportController::class, 'index']);
        $routes->addGroup('/multi-table-query', function (RouteCollector $routes) {
            $routes->get('', [MultiTableQueryController::class, 'index']);
            $routes->get('/tables', [MultiTableQueryController::class, 'table']);
            $routes->post('/query', [MultiTableQueryController::class, 'displayResults']);
        });
        $routes->addRoute(['GET', 'POST'], '/operations', [OperationsController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/qbe', [QueryByExampleController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/routines', [RoutinesController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/search', [SearchController::class, 'index']);
        $routes->addGroup('/sql', function (RouteCollector $routes) {
            $routes->addRoute(['GET', 'POST'], '', [DatabaseSqlController::class, 'index']);
            $routes->post('/autocomplete', [SqlAutoCompleteController::class, 'index']);
            $routes->post('/format', [SqlFormatController::class, 'index']);
        });
        $routes->addGroup('/structure', function (RouteCollector $routes) {
            $routes->addRoute(['GET', 'POST'], '', [StructureController::class, 'index']);
            $routes->addRoute(['GET', 'POST'], '/favorite-table', [
                StructureController::class,
                'addRemoveFavoriteTablesAction',
            ]);
            $routes->addRoute(['GET', 'POST'], '/real-row-count', [
                StructureController::class,
                'handleRealRowCountRequestAction',
            ]);
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', [TrackingController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/triggers', [TriggersController::class, 'index']);
    });
    $routes->addRoute(['GET', 'POST'], '/error-report', [ErrorReportController::class, 'index']);
    $routes->addRoute(['GET', 'POST'], '/export', [ExportController::class, 'index']);
    $routes->addRoute(['GET', 'POST'], '/gis-data-editor', [GisDataEditorController::class, 'index']);
    $routes->addRoute(['GET', 'POST'], '/import', [ImportController::class, 'index']);
    $routes->addRoute(['GET', 'POST'], '/import-status', [ImportStatusController::class, 'index']);
    $routes->get('/license', [LicenseController::class, 'index']);
    $routes->addRoute(['GET', 'POST'], '/lint', [LintController::class, 'index']);
    $routes->addRoute(['GET', 'POST'], '/logout', [LogoutController::class, 'index']);
    $routes->addRoute(['GET', 'POST'], '/navigation', [NavigationController::class, 'index']);
    $routes->addRoute(['GET', 'POST'], '/normalization', [NormalizationController::class, 'index']);
    $routes->get('/phpinfo', [PhpInfoController::class, 'index']);
    $routes->addGroup('/preferences', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/export', [PreferencesExportController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/features', [FeaturesController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/import', [PreferencesImportController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/main-panel', [MainPanelController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/manage', [ManageController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/navigation', [PreferencesNavigationController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/sql', [PreferencesSqlController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/two-factor', [TwoFactorController::class, 'index']);
    });
    $routes->addRoute(['GET', 'POST'], '/schema-export', [SchemaExportController::class, 'index']);
    $routes->addGroup('/server', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/binlog', [BinlogController::class, 'index']);
        $routes->get('/collations', [CollationsController::class, 'index']);
        $routes->addGroup('/databases', function (RouteCollector $routes) {
            $routes->addRoute(['GET', 'POST'], '', [DatabasesController::class, 'index']);
            $routes->post('/create', [DatabasesController::class, 'create']);
            $routes->post('/destroy', [DatabasesController::class, 'destroy']);
        });
        $routes->addGroup('/engines', function (RouteCollector $routes) {
            $routes->get('', [EnginesController::class, 'index']);
            $routes->get('/{engine}[/{page}]', [EnginesController::class, 'show']);
        });
        $routes->addRoute(['GET', 'POST'], '/export', [ServerExportController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/import', [ServerImportController::class, 'index']);
        $routes->get('/plugins', [PluginsController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/privileges', [PrivilegesController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/replication', [ReplicationController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/sql', [ServerSqlController::class, 'index']);
        $routes->addGroup('/status', function (RouteCollector $routes) {
            $routes->get('', [StatusController::class, 'index']);
            $routes->get('/advisor', [AdvisorController::class, 'index']);
            $routes->addGroup('/monitor', function (RouteCollector $routes) {
                $routes->get('', [MonitorController::class, 'index']);
                $routes->post('/chart', [MonitorController::class, 'chartingData']);
                $routes->post('/slow-log', [MonitorController::class, 'logDataTypeSlow']);
                $routes->post('/general-log', [MonitorController::class, 'logDataTypeGeneral']);
                $routes->post('/log-vars', [MonitorController::class, 'loggingVars']);
                $routes->post('/query', [MonitorController::class, 'queryAnalyzer']);
            });
            $routes->addGroup('/processes', function (RouteCollector $routes) {
                $routes->addRoute(['GET', 'POST'], '', [ProcessesController::class, 'index']);
                $routes->post('/refresh', [ProcessesController::class, 'refresh']);
                $routes->post('/kill/{id:\d+}', [ProcessesController::class, 'kill']);
            });
            $routes->get('/queries', [QueriesController::class, 'index']);
            $routes->addRoute(['GET', 'POST'], '/variables', [StatusVariables::class, 'index']);
        });
        $routes->addRoute(['GET', 'POST'], '/user-groups', [UserGroupsController::class, 'index']);
        $routes->addGroup('/variables', function (RouteCollector $routes) {
            $routes->get('', [VariablesController::class, 'index']);
            $routes->get('/get/{name}', [VariablesController::class, 'getValue']);
            $routes->post('/set/{name}', [VariablesController::class, 'setValue']);
        });
    });
    $routes->addGroup('/sql', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '', [SqlController::class, 'index']);
        $routes->post('/get-relational-values', [SqlController::class, 'getRelationalValues']);
        $routes->post('/get-enum-values', [SqlController::class, 'getEnumValues']);
        $routes->post('/get-set-values', [SqlController::class, 'getSetValues']);
        $routes->get('/get-default-fk-check-value', [SqlController::class, 'getDefaultForeignKeyCheckValue']);
        $routes->post('/set-column-preferences', [SqlController::class, 'setColumnOrderOrVisibility']);
    });
    $routes->addGroup('/table', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/add-field', [AddFieldController::class, 'index']);
        $routes->addGroup('/change', function (RouteCollector $routes) {
            $routes->addRoute(['GET', 'POST'], '', [ChangeController::class, 'index']);
            $routes->post('/rows', [ChangeController::class, 'rows']);
        });
        $routes->addRoute(['GET', 'POST'], '/chart', [ChartController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/create', [CreateController::class, 'index']);
        $routes->addGroup('/delete', function (RouteCollector $routes) {
            $routes->post('/confirm', [DeleteController::class, 'confirm']);
            $routes->post('/rows', [DeleteController::class, 'rows']);
        });
        $routes->addGroup('/export', function (RouteCollector $routes) {
            $routes->addRoute(['GET', 'POST'], '', [TableExportController::class, 'index']);
            $routes->post('/rows', [TableExportController::class, 'rows']);
        });
        $routes->addRoute(['GET', 'POST'], '/find-replace', [FindReplaceController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/get-field', [GetFieldController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/gis-visualization', [GisVisualizationController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/import', [TableImportController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/indexes', [IndexesController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/operations', [TableOperationsController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/recent-favorite', [RecentFavoriteController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/relation', [RelationController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/replace', [ReplaceController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/search', [TableSearchController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/sql', [TableSqlController::class, 'index']);
        $routes->addGroup('/structure', function (RouteCollector $routes) {
            $routes->addRoute(['GET', 'POST'], '', [TableStructureController::class, 'index']);
            $routes->post('/drop', [TableStructureController::class, 'drop']);
            $routes->post('/drop-confirm', [TableStructureController::class, 'dropConfirm']);
            $routes->post('/fulltext', [TableStructureController::class, 'fulltext']);
            $routes->post('/index', [TableStructureController::class, 'addIndex']);
            $routes->post('/primary', [TableStructureController::class, 'primary']);
            $routes->post('/spatial', [TableStructureController::class, 'spatial']);
            $routes->post('/unique', [TableStructureController::class, 'unique']);
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', [TableTrackingController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/triggers', [TableTriggersController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/zoom-search', [ZoomSearchController::class, 'index']);
    });
    $routes->get('/themes', [ThemesController::class, 'index']);
    $routes->addGroup('/transformation', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/overview', [TransformationOverviewController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/wrapper', [TransformationWrapperController::class, 'index']);
    });
    $routes->addRoute(['GET', 'POST'], '/user-password', [UserPasswordController::class, 'index']);
    $routes->addRoute(['GET', 'POST'], '/version-check', [VersionCheckController::class, 'index']);
    $routes->addGroup('/view', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/create', [ViewCreateController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/operations', [ViewOperationsController::class, 'index']);
    });
};
