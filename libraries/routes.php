<?php

declare(strict_types=1);

use FastRoute\RouteCollector;
use PhpMyAdmin\Controllers\BrowseForeignersController;
use PhpMyAdmin\Controllers\ChangeLogController;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Controllers\CollationConnectionController;
use PhpMyAdmin\Controllers\ColumnController;
use PhpMyAdmin\Controllers\Config\GetConfigController;
use PhpMyAdmin\Controllers\Config\SetConfigController;
use PhpMyAdmin\Controllers\Database\CentralColumns\PopulateColumnsController;
use PhpMyAdmin\Controllers\Database\CentralColumnsController;
use PhpMyAdmin\Controllers\Database\DataDictionaryController;
use PhpMyAdmin\Controllers\Database\DesignerController;
use PhpMyAdmin\Controllers\Database\EventsController;
use PhpMyAdmin\Controllers\Database\ExportController as DatabaseExportController;
use PhpMyAdmin\Controllers\Database\ImportController as DatabaseImportController;
use PhpMyAdmin\Controllers\Database\MultiTableQuery\QueryController;
use PhpMyAdmin\Controllers\Database\MultiTableQuery\TablesController as MultiTableQueryTablesController;
use PhpMyAdmin\Controllers\Database\MultiTableQueryController;
use PhpMyAdmin\Controllers\Database\Operations\CollationController;
use PhpMyAdmin\Controllers\Database\OperationsController;
use PhpMyAdmin\Controllers\Database\QueryByExampleController;
use PhpMyAdmin\Controllers\Database\RoutinesController;
use PhpMyAdmin\Controllers\Database\SearchController;
use PhpMyAdmin\Controllers\Database\SqlAutoCompleteController;
use PhpMyAdmin\Controllers\Database\SqlController as DatabaseSqlController;
use PhpMyAdmin\Controllers\Database\SqlFormatController;
use PhpMyAdmin\Controllers\Database\Structure;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Controllers\Database\TrackingController;
use PhpMyAdmin\Controllers\Database\TriggersController;
use PhpMyAdmin\Controllers\DatabaseController;
use PhpMyAdmin\Controllers\ErrorReportController;
use PhpMyAdmin\Controllers\Export\CheckTimeOutController;
use PhpMyAdmin\Controllers\Export\ExportController;
use PhpMyAdmin\Controllers\Export\TablesController;
use PhpMyAdmin\Controllers\Export\Template\CreateController as TemplateCreateController;
use PhpMyAdmin\Controllers\Export\Template\DeleteController as TemplateDeleteController;
use PhpMyAdmin\Controllers\Export\Template\LoadController as TemplateLoadController;
use PhpMyAdmin\Controllers\Export\Template\UpdateController as TemplateUpdateController;
use PhpMyAdmin\Controllers\GisDataEditorController;
use PhpMyAdmin\Controllers\GitInfoController;
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
use PhpMyAdmin\Controllers\RecentTablesListController;
use PhpMyAdmin\Controllers\SchemaExportController;
use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Controllers\Server\Databases\CreateController as DatabasesCreateController;
use PhpMyAdmin\Controllers\Server\Databases\DestroyController;
use PhpMyAdmin\Controllers\Server\DatabasesController;
use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\Controllers\Server\ExportController as ServerExportController;
use PhpMyAdmin\Controllers\Server\ImportController as ServerImportController;
use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\Controllers\Server\Privileges\AccountLockController;
use PhpMyAdmin\Controllers\Server\Privileges\AccountUnlockController;
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
use PhpMyAdmin\Controllers\Table;
use PhpMyAdmin\Controllers\Table\AddFieldController;
use PhpMyAdmin\Controllers\Table\ChangeController;
use PhpMyAdmin\Controllers\Table\ChartController;
use PhpMyAdmin\Controllers\Table\CreateController;
use PhpMyAdmin\Controllers\Table\DeleteController;
use PhpMyAdmin\Controllers\Table\DropColumnConfirmationController;
use PhpMyAdmin\Controllers\Table\DropColumnController;
use PhpMyAdmin\Controllers\Table\ExportController as TableExportController;
use PhpMyAdmin\Controllers\Table\FindReplaceController;
use PhpMyAdmin\Controllers\Table\GetFieldController;
use PhpMyAdmin\Controllers\Table\GisVisualizationController;
use PhpMyAdmin\Controllers\Table\ImportController as TableImportController;
use PhpMyAdmin\Controllers\Table\IndexesController;
use PhpMyAdmin\Controllers\Table\MaintenanceController;
use PhpMyAdmin\Controllers\Table\OperationsController as TableOperationsController;
use PhpMyAdmin\Controllers\Table\PartitionController;
use PhpMyAdmin\Controllers\Table\RecentFavoriteController;
use PhpMyAdmin\Controllers\Table\RelationController;
use PhpMyAdmin\Controllers\Table\ReplaceController;
use PhpMyAdmin\Controllers\Table\SearchController as TableSearchController;
use PhpMyAdmin\Controllers\Table\SqlController as TableSqlController;
use PhpMyAdmin\Controllers\Table\StructureController as TableStructureController;
use PhpMyAdmin\Controllers\Table\TrackingController as TableTrackingController;
use PhpMyAdmin\Controllers\Table\TriggersController as TableTriggersController;
use PhpMyAdmin\Controllers\Table\ZoomSearchController;
use PhpMyAdmin\Controllers\TableController;
use PhpMyAdmin\Controllers\ThemesController;
use PhpMyAdmin\Controllers\Transformation\OverviewController;
use PhpMyAdmin\Controllers\Transformation\WrapperController;
use PhpMyAdmin\Controllers\UserPasswordController;
use PhpMyAdmin\Controllers\VersionCheckController;
use PhpMyAdmin\Controllers\View\CreateController as ViewCreateController;
use PhpMyAdmin\Controllers\View\OperationsController as ViewOperationsController;

if (! defined('PHPMYADMIN')) {
    exit;
}

return static function (RouteCollector $routes): void {
    $routes->addRoute(['GET', 'POST'], '[/]', HomeController::class);
    $routes->addRoute(['GET', 'POST'], '/browse-foreigners', BrowseForeignersController::class);
    $routes->get('/changelog', ChangeLogController::class);
    $routes->addRoute(['GET', 'POST'], '/check-relations', CheckRelationsController::class);
    $routes->post('/collation-connection', CollationConnectionController::class);
    $routes->post('/columns', ColumnController::class);
    $routes->addGroup('/config', static function (RouteCollector $routes): void {
        $routes->post('/get', GetConfigController::class);
        $routes->post('/set', SetConfigController::class);
    });
    $routes->addGroup('/database', static function (RouteCollector $routes): void {
        $routes->addGroup('/central-columns', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', CentralColumnsController::class);
            $routes->post('/populate', PopulateColumnsController::class);
        });
        $routes->get('/data-dictionary', DataDictionaryController::class);
        $routes->addRoute(['GET', 'POST'], '/designer', DesignerController::class);
        $routes->addRoute(['GET', 'POST'], '/events', EventsController::class);
        $routes->addRoute(['GET', 'POST'], '/export', DatabaseExportController::class);
        $routes->addRoute(['GET', 'POST'], '/import', DatabaseImportController::class);
        $routes->addGroup('/multi-table-query', static function (RouteCollector $routes): void {
            $routes->get('', MultiTableQueryController::class);
            $routes->get('/tables', MultiTableQueryTablesController::class);
            $routes->post('/query', QueryController::class);
        });
        $routes->addGroup('/operations', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', OperationsController::class);
            $routes->post('/collation', CollationController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/qbe', QueryByExampleController::class);
        $routes->addRoute(['GET', 'POST'], '/routines', RoutinesController::class);
        $routes->addRoute(['GET', 'POST'], '/search', SearchController::class);
        $routes->addGroup('/sql', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', DatabaseSqlController::class);
            $routes->post('/autocomplete', SqlAutoCompleteController::class);
            $routes->post('/format', SqlFormatController::class);
        });
        $routes->addGroup('/structure', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', StructureController::class);
            $routes->post('/add-prefix', Structure\AddPrefixController::class);
            $routes->post('/add-prefix-table', Structure\AddPrefixTableController::class);
            $routes->post('/central-columns-add', Structure\CentralColumns\AddController::class);
            $routes->post('/central-columns-make-consistent', Structure\CentralColumns\MakeConsistentController::class);
            $routes->post('/central-columns-remove', Structure\CentralColumns\RemoveController::class);
            $routes->post('/change-prefix-form', Structure\ChangePrefixFormController::class);
            $routes->post('/copy-form', Structure\CopyFormController::class);
            $routes->post('/copy-table', Structure\CopyTableController::class);
            $routes->post('/copy-table-with-prefix', Structure\CopyTableWithPrefixController::class);
            $routes->post('/drop-form', Structure\DropFormController::class);
            $routes->post('/drop-table', Structure\DropTableController::class);
            $routes->post('/empty-form', Structure\EmptyFormController::class);
            $routes->post('/empty-table', Structure\EmptyTableController::class);
            $routes->addRoute(['GET', 'POST'], '/favorite-table', Structure\FavoriteTableController::class);
            $routes->addRoute(['GET', 'POST'], '/real-row-count', Structure\RealRowCountController::class);
            $routes->post('/replace-prefix', Structure\ReplacePrefixController::class);
            $routes->post('/show-create', Structure\ShowCreateController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', TrackingController::class);
        $routes->addRoute(['GET', 'POST'], '/triggers', TriggersController::class);
    });
    $routes->post('/databases', DatabaseController::class);
    $routes->addRoute(['GET', 'POST'], '/error-report', ErrorReportController::class);
    $routes->addGroup('/export', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '', ExportController::class);
        $routes->get('/check-time-out', CheckTimeOutController::class);
        $routes->post('/tables', TablesController::class);
        $routes->addGroup('/template', static function (RouteCollector $routes): void {
            $routes->post('/create', TemplateCreateController::class);
            $routes->post('/delete', TemplateDeleteController::class);
            $routes->post('/load', TemplateLoadController::class);
            $routes->post('/update', TemplateUpdateController::class);
        });
    });
    $routes->addRoute(['GET', 'POST'], '/gis-data-editor', GisDataEditorController::class);
    $routes->addRoute(['GET', 'POST'], '/git-revision', GitInfoController::class);
    $routes->addRoute(['GET', 'POST'], '/import', ImportController::class);
    $routes->addRoute(['GET', 'POST'], '/import-status', ImportStatusController::class);
    $routes->get('/license', LicenseController::class);
    $routes->addRoute(['GET', 'POST'], '/lint', LintController::class);
    $routes->addRoute(['GET', 'POST'], '/logout', LogoutController::class);
    $routes->addRoute(['GET', 'POST'], '/navigation', NavigationController::class);
    $routes->addRoute(['GET', 'POST'], '/normalization', NormalizationController::class);
    $routes->get('/phpinfo', PhpInfoController::class);
    $routes->addGroup('/preferences', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/export', PreferencesExportController::class);
        $routes->addRoute(['GET', 'POST'], '/features', FeaturesController::class);
        $routes->addRoute(['GET', 'POST'], '/import', PreferencesImportController::class);
        $routes->addRoute(['GET', 'POST'], '/main-panel', MainPanelController::class);
        $routes->addRoute(['GET', 'POST'], '/manage', ManageController::class);
        $routes->addRoute(['GET', 'POST'], '/navigation', PreferencesNavigationController::class);
        $routes->addRoute(['GET', 'POST'], '/sql', PreferencesSqlController::class);
        $routes->addRoute(['GET', 'POST'], '/two-factor', TwoFactorController::class);
    });
    $routes->addRoute(['GET', 'POST'], '/recent-table', RecentTablesListController::class);
    $routes->addRoute(['GET', 'POST'], '/schema-export', SchemaExportController::class);
    $routes->addGroup('/server', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/binlog', BinlogController::class);
        $routes->get('/collations', CollationsController::class);
        $routes->addGroup('/databases', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', DatabasesController::class);
            $routes->post('/create', DatabasesCreateController::class);
            $routes->post('/destroy', DestroyController::class);
        });
        $routes->addGroup('/engines', static function (RouteCollector $routes): void {
            $routes->get('', [EnginesController::class, 'index']);
            $routes->get('/{engine}[/{page}]', [EnginesController::class, 'show']);
        });
        $routes->addRoute(['GET', 'POST'], '/export', ServerExportController::class);
        $routes->addRoute(['GET', 'POST'], '/import', ServerImportController::class);
        $routes->get('/plugins', PluginsController::class);
        $routes->addGroup('/privileges', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', PrivilegesController::class);
            $routes->post('/account-lock', AccountLockController::class);
            $routes->post('/account-unlock', AccountUnlockController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/replication', ReplicationController::class);
        $routes->addRoute(['GET', 'POST'], '/sql', ServerSqlController::class);
        $routes->addGroup('/status', static function (RouteCollector $routes): void {
            $routes->get('', StatusController::class);
            $routes->get('/advisor', AdvisorController::class);
            $routes->addGroup('/monitor', static function (RouteCollector $routes): void {
                $routes->get('', [MonitorController::class, 'index']);
                $routes->post('/chart', [MonitorController::class, 'chartingData']);
                $routes->post('/slow-log', [MonitorController::class, 'logDataTypeSlow']);
                $routes->post('/general-log', [MonitorController::class, 'logDataTypeGeneral']);
                $routes->post('/log-vars', [MonitorController::class, 'loggingVars']);
                $routes->post('/query', [MonitorController::class, 'queryAnalyzer']);
            });
            $routes->addGroup('/processes', static function (RouteCollector $routes): void {
                $routes->addRoute(['GET', 'POST'], '', [ProcessesController::class, 'index']);
                $routes->post('/refresh', [ProcessesController::class, 'refresh']);
                $routes->post('/kill/{id:\d+}', [ProcessesController::class, 'kill']);
            });
            $routes->get('/queries', QueriesController::class);
            $routes->addRoute(['GET', 'POST'], '/variables', StatusVariables::class);
        });
        $routes->addGroup('/user-groups', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', [UserGroupsController::class, 'index']);
            $routes->get('/edit-form', [UserGroupsController::class, 'editUserGroupModalForm']);
        });
        $routes->addGroup('/variables', static function (RouteCollector $routes): void {
            $routes->get('', [VariablesController::class, 'index']);
            $routes->get('/get/{name}', [VariablesController::class, 'getValue']);
            $routes->post('/set/{name}', [VariablesController::class, 'setValue']);
        });
    });
    $routes->addGroup('/sql', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '', [SqlController::class, 'index']);
        $routes->post('/get-relational-values', [SqlController::class, 'getRelationalValues']);
        $routes->post('/get-enum-values', [SqlController::class, 'getEnumValues']);
        $routes->post('/get-set-values', [SqlController::class, 'getSetValues']);
        $routes->get('/get-default-fk-check-value', [SqlController::class, 'getDefaultForeignKeyCheckValue']);
        $routes->post('/set-column-preferences', [SqlController::class, 'setColumnOrderOrVisibility']);
    });
    $routes->addGroup('/table', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/add-field', AddFieldController::class);
        $routes->addGroup('/change', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', [ChangeController::class, 'index']);
            $routes->post('/rows', [ChangeController::class, 'rows']);
        });
        $routes->addRoute(['GET', 'POST'], '/chart', ChartController::class);
        $routes->addRoute(['GET', 'POST'], '/create', CreateController::class);
        $routes->addGroup('/delete', static function (RouteCollector $routes): void {
            $routes->post('/confirm', [DeleteController::class, 'confirm']);
            $routes->post('/rows', [DeleteController::class, 'rows']);
        });
        $routes->addGroup('/export', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', [TableExportController::class, 'index']);
            $routes->post('/rows', [TableExportController::class, 'rows']);
        });
        $routes->addRoute(['GET', 'POST'], '/find-replace', FindReplaceController::class);
        $routes->addRoute(['GET', 'POST'], '/get-field', GetFieldController::class);
        $routes->addRoute(['GET', 'POST'], '/gis-visualization', GisVisualizationController::class);
        $routes->addRoute(['GET', 'POST'], '/import', TableImportController::class);
        $routes->addRoute(['GET', 'POST'], '/indexes', [IndexesController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/indexes/rename', [IndexesController::class, 'indexRename']);
        $routes->addGroup('/maintenance', static function (RouteCollector $routes): void {
            $routes->post('/analyze', [MaintenanceController::class, 'analyze']);
            $routes->post('/check', [MaintenanceController::class, 'check']);
            $routes->post('/checksum', [MaintenanceController::class, 'checksum']);
            $routes->post('/optimize', [MaintenanceController::class, 'optimize']);
            $routes->post('/repair', [MaintenanceController::class, 'repair']);
        });
        $routes->addGroup('/partition', static function (RouteCollector $routes): void {
            $routes->post('/analyze', [PartitionController::class, 'analyze']);
            $routes->post('/check', [PartitionController::class, 'check']);
            $routes->post('/drop', [PartitionController::class, 'drop']);
            $routes->post('/optimize', [PartitionController::class, 'optimize']);
            $routes->post('/rebuild', [PartitionController::class, 'rebuild']);
            $routes->post('/repair', [PartitionController::class, 'repair']);
            $routes->post('/truncate', [PartitionController::class, 'truncate']);
        });
        $routes->addRoute(['GET', 'POST'], '/operations', TableOperationsController::class);
        $routes->addRoute(['GET', 'POST'], '/recent-favorite', RecentFavoriteController::class);
        $routes->addRoute(['GET', 'POST'], '/relation', RelationController::class);
        $routes->addRoute(['GET', 'POST'], '/replace', ReplaceController::class);
        $routes->addRoute(['GET', 'POST'], '/search', TableSearchController::class);
        $routes->addRoute(['GET', 'POST'], '/sql', TableSqlController::class);
        $routes->addGroup('/structure', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', TableStructureController::class);
            $routes->post('/add-key', Table\Structure\AddKeyController::class);
            $routes->post('/browse', Table\Structure\BrowseController::class);
            $routes->post('/central-columns-add', Table\Structure\CentralColumnsAddController::class);
            $routes->post('/central-columns-remove', Table\Structure\CentralColumnsRemoveController::class);
            $routes->addRoute(['GET', 'POST'], '/change', Table\Structure\ChangeController::class);
            $routes->post('/drop', DropColumnController::class);
            $routes->post('/drop-confirm', DropColumnConfirmationController::class);
            $routes->post('/fulltext', Table\Structure\FulltextController::class);
            $routes->post('/index', Table\Structure\AddIndexController::class);
            $routes->post('/move-columns', Table\Structure\MoveColumnsController::class);
            $routes->post('/partitioning', Table\Structure\PartitioningController::class);
            $routes->post('/primary', Table\Structure\PrimaryController::class);
            $routes->post('/reserved-word-check', Table\Structure\ReservedWordCheckController::class);
            $routes->post('/save', Table\Structure\SaveController::class);
            $routes->post('/spatial', Table\Structure\SpatialController::class);
            $routes->post('/unique', Table\Structure\UniqueController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', TableTrackingController::class);
        $routes->addRoute(['GET', 'POST'], '/triggers', TableTriggersController::class);
        $routes->addRoute(['GET', 'POST'], '/zoom-search', ZoomSearchController::class);
    });
    $routes->post('/tables', TableController::class);
    $routes->addGroup('/themes', static function (RouteCollector $routes): void {
        $routes->get('', [ThemesController::class, 'index']);
        $routes->post('/set', [ThemesController::class, 'setTheme']);
    });
    $routes->addGroup('/transformation', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/overview', OverviewController::class);
        $routes->addRoute(['GET', 'POST'], '/wrapper', WrapperController::class);
    });
    $routes->addRoute(['GET', 'POST'], '/user-password', UserPasswordController::class);
    $routes->addRoute(['GET', 'POST'], '/version-check', VersionCheckController::class);
    $routes->addGroup('/view', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/create', ViewCreateController::class);
        $routes->addRoute(['GET', 'POST'], '/operations', ViewOperationsController::class);
    });
};
