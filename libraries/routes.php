<?php

declare(strict_types=1);

use FastRoute\RouteCollector;
use PhpMyAdmin\Controllers\BrowseForeignersController;
use PhpMyAdmin\Controllers\ChangeLogController;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Controllers\CollationConnectionController;
use PhpMyAdmin\Controllers\ColumnController;
use PhpMyAdmin\Controllers\Config;
use PhpMyAdmin\Controllers\Database;
use PhpMyAdmin\Controllers\DatabaseController;
use PhpMyAdmin\Controllers\ErrorReportController;
use PhpMyAdmin\Controllers\Export;
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
use PhpMyAdmin\Controllers\Preferences;
use PhpMyAdmin\Controllers\RecentTablesListController;
use PhpMyAdmin\Controllers\SchemaExportController;
use PhpMyAdmin\Controllers\Server;
use PhpMyAdmin\Controllers\SqlController;
use PhpMyAdmin\Controllers\Table;
use PhpMyAdmin\Controllers\TableController;
use PhpMyAdmin\Controllers\ThemesController;
use PhpMyAdmin\Controllers\Transformation;
use PhpMyAdmin\Controllers\UserPasswordController;
use PhpMyAdmin\Controllers\VersionCheckController;
use PhpMyAdmin\Controllers\View;

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
        $routes->post('/get', Config\GetConfigController::class);
        $routes->post('/set', Config\SetConfigController::class);
    });
    $routes->addGroup('/database', static function (RouteCollector $routes): void {
        $routes->addGroup('/central-columns', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Database\CentralColumnsController::class);
            $routes->post('/populate', Database\CentralColumns\PopulateColumnsController::class);
        });
        $routes->get('/data-dictionary', Database\DataDictionaryController::class);
        $routes->addRoute(['GET', 'POST'], '/designer', Database\DesignerController::class);
        $routes->addRoute(['GET', 'POST'], '/events', Database\EventsController::class);
        $routes->addRoute(['GET', 'POST'], '/export', Database\ExportController::class);
        $routes->addRoute(['GET', 'POST'], '/import', Database\ImportController::class);
        $routes->addGroup('/multi-table-query', static function (RouteCollector $routes): void {
            $routes->get('', Database\MultiTableQueryController::class);
            $routes->get('/tables', Database\MultiTableQuery\TablesController::class);
            $routes->post('/query', Database\MultiTableQuery\QueryController::class);
        });
        $routes->addGroup('/operations', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Database\OperationsController::class);
            $routes->post('/collation', Database\Operations\CollationController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/qbe', Database\QueryByExampleController::class);
        $routes->addRoute(['GET', 'POST'], '/routines', Database\RoutinesController::class);
        $routes->addRoute(['GET', 'POST'], '/search', Database\SearchController::class);
        $routes->addGroup('/sql', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Database\SqlController::class);
            $routes->post('/autocomplete', Database\SqlAutoCompleteController::class);
            $routes->post('/format', Database\SqlFormatController::class);
        });
        $routes->addGroup('/structure', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Database\StructureController::class);
            $routes->post('/add-prefix', Database\Structure\AddPrefixController::class);
            $routes->post('/add-prefix-table', Database\Structure\AddPrefixTableController::class);
            $routes->addGroup('/central-columns', static function (RouteCollector $routes): void {
                $routes->post('/add', Database\Structure\CentralColumns\AddController::class);
                $routes->post('/make-consistent', Database\Structure\CentralColumns\MakeConsistentController::class);
                $routes->post('/remove', Database\Structure\CentralColumns\RemoveController::class);
            });
            $routes->post('/change-prefix-form', Database\Structure\ChangePrefixFormController::class);
            $routes->post('/copy-form', Database\Structure\CopyFormController::class);
            $routes->post('/copy-table', Database\Structure\CopyTableController::class);
            $routes->post('/copy-table-with-prefix', Database\Structure\CopyTableWithPrefixController::class);
            $routes->post('/drop-form', Database\Structure\DropFormController::class);
            $routes->post('/drop-table', Database\Structure\DropTableController::class);
            $routes->post('/empty-form', Database\Structure\EmptyFormController::class);
            $routes->post('/empty-table', Database\Structure\EmptyTableController::class);
            $routes->addRoute(['GET', 'POST'], '/favorite-table', Database\Structure\FavoriteTableController::class);
            $routes->addRoute(['GET', 'POST'], '/real-row-count', Database\Structure\RealRowCountController::class);
            $routes->post('/replace-prefix', Database\Structure\ReplacePrefixController::class);
            $routes->post('/show-create', Database\Structure\ShowCreateController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', Database\TrackingController::class);
        $routes->addRoute(['GET', 'POST'], '/triggers', Database\TriggersController::class);
    });
    $routes->post('/databases', DatabaseController::class);
    $routes->addRoute(['GET', 'POST'], '/error-report', ErrorReportController::class);
    $routes->addGroup('/export', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '', Export\ExportController::class);
        $routes->get('/check-time-out', Export\CheckTimeOutController::class);
        $routes->post('/tables', Export\TablesController::class);
        $routes->addGroup('/template', static function (RouteCollector $routes): void {
            $routes->post('/create', Export\Template\CreateController::class);
            $routes->post('/delete', Export\Template\DeleteController::class);
            $routes->post('/load', Export\Template\LoadController::class);
            $routes->post('/update', Export\Template\UpdateController::class);
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
        $routes->addRoute(['GET', 'POST'], '/export', Preferences\ExportController::class);
        $routes->addRoute(['GET', 'POST'], '/features', Preferences\FeaturesController::class);
        $routes->addRoute(['GET', 'POST'], '/import', Preferences\ImportController::class);
        $routes->addRoute(['GET', 'POST'], '/main-panel', Preferences\MainPanelController::class);
        $routes->addRoute(['GET', 'POST'], '/manage', Preferences\ManageController::class);
        $routes->addRoute(['GET', 'POST'], '/navigation', Preferences\NavigationController::class);
        $routes->addRoute(['GET', 'POST'], '/sql', Preferences\SqlController::class);
        $routes->addRoute(['GET', 'POST'], '/two-factor', Preferences\TwoFactorController::class);
    });
    $routes->addRoute(['GET', 'POST'], '/recent-table', RecentTablesListController::class);
    $routes->addRoute(['GET', 'POST'], '/schema-export', SchemaExportController::class);
    $routes->addGroup('/server', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/binlog', Server\BinlogController::class);
        $routes->get('/collations', Server\CollationsController::class);
        $routes->addGroup('/databases', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Server\DatabasesController::class);
            $routes->post('/create', Server\Databases\CreateController::class);
            $routes->post('/destroy', Server\Databases\DestroyController::class);
        });
        $routes->addGroup('/engines', static function (RouteCollector $routes): void {
            $routes->get('', Server\EnginesController::class);
            $routes->get('/{engine}[/{page}]', Server\ShowEngineController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/export', Server\ExportController::class);
        $routes->addRoute(['GET', 'POST'], '/import', Server\ImportController::class);
        $routes->get('/plugins', Server\PluginsController::class);
        $routes->addGroup('/privileges', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Server\PrivilegesController::class);
            $routes->post('/account-lock', Server\Privileges\AccountLockController::class);
            $routes->post('/account-unlock', Server\Privileges\AccountUnlockController::class);
        });
        $routes->addRoute(['GET', 'POST'], '/replication', Server\ReplicationController::class);
        $routes->addRoute(['GET', 'POST'], '/sql', Server\SqlController::class);
        $routes->addGroup('/status', static function (RouteCollector $routes): void {
            $routes->get('', Server\Status\StatusController::class);
            $routes->get('/advisor', Server\Status\AdvisorController::class);
            $routes->addGroup('/monitor', static function (RouteCollector $routes): void {
                $routes->get('', [Server\Status\MonitorController::class, 'index']);
                $routes->post('/chart', [Server\Status\MonitorController::class, 'chartingData']);
                $routes->post('/slow-log', [Server\Status\MonitorController::class, 'logDataTypeSlow']);
                $routes->post('/general-log', [Server\Status\MonitorController::class, 'logDataTypeGeneral']);
                $routes->post('/log-vars', [Server\Status\MonitorController::class, 'loggingVars']);
                $routes->post('/query', [Server\Status\MonitorController::class, 'queryAnalyzer']);
            });
            $routes->addGroup('/processes', static function (RouteCollector $routes): void {
                $routes->addRoute(['GET', 'POST'], '', [Server\Status\ProcessesController::class, 'index']);
                $routes->post('/refresh', [Server\Status\ProcessesController::class, 'refresh']);
                $routes->post('/kill/{id:\d+}', [Server\Status\ProcessesController::class, 'kill']);
            });
            $routes->get('/queries', Server\Status\QueriesController::class);
            $routes->addRoute(['GET', 'POST'], '/variables', Server\Status\VariablesController::class);
        });
        $routes->addGroup('/user-groups', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', [Server\UserGroupsController::class, 'index']);
            $routes->get('/edit-form', [Server\UserGroupsController::class, 'editUserGroupModalForm']);
        });
        $routes->addGroup('/variables', static function (RouteCollector $routes): void {
            $routes->get('', [Server\VariablesController::class, 'index']);
            $routes->get('/get/{name}', [Server\VariablesController::class, 'getValue']);
            $routes->post('/set/{name}', [Server\VariablesController::class, 'setValue']);
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
        $routes->addRoute(['GET', 'POST'], '/add-field', Table\AddFieldController::class);
        $routes->addGroup('/change', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', [Table\ChangeController::class, 'index']);
            $routes->post('/rows', [Table\ChangeController::class, 'rows']);
        });
        $routes->addRoute(['GET', 'POST'], '/chart', Table\ChartController::class);
        $routes->addRoute(['GET', 'POST'], '/create', Table\CreateController::class);
        $routes->addGroup('/delete', static function (RouteCollector $routes): void {
            $routes->post('/confirm', [Table\DeleteController::class, 'confirm']);
            $routes->post('/rows', [Table\DeleteController::class, 'rows']);
        });
        $routes->addGroup('/export', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', [Table\ExportController::class, 'index']);
            $routes->post('/rows', [Table\ExportController::class, 'rows']);
        });
        $routes->addRoute(['GET', 'POST'], '/find-replace', Table\FindReplaceController::class);
        $routes->addRoute(['GET', 'POST'], '/get-field', Table\GetFieldController::class);
        $routes->addRoute(['GET', 'POST'], '/gis-visualization', Table\GisVisualizationController::class);
        $routes->addRoute(['GET', 'POST'], '/import', Table\ImportController::class);
        $routes->addRoute(['GET', 'POST'], '/indexes', [Table\IndexesController::class, 'index']);
        $routes->addRoute(['GET', 'POST'], '/indexes/rename', [Table\IndexesController::class, 'indexRename']);
        $routes->addGroup('/maintenance', static function (RouteCollector $routes): void {
            $routes->post('/analyze', [Table\MaintenanceController::class, 'analyze']);
            $routes->post('/check', [Table\MaintenanceController::class, 'check']);
            $routes->post('/checksum', [Table\MaintenanceController::class, 'checksum']);
            $routes->post('/optimize', [Table\MaintenanceController::class, 'optimize']);
            $routes->post('/repair', [Table\MaintenanceController::class, 'repair']);
        });
        $routes->addGroup('/partition', static function (RouteCollector $routes): void {
            $routes->post('/analyze', [Table\PartitionController::class, 'analyze']);
            $routes->post('/check', [Table\PartitionController::class, 'check']);
            $routes->post('/drop', [Table\PartitionController::class, 'drop']);
            $routes->post('/optimize', [Table\PartitionController::class, 'optimize']);
            $routes->post('/rebuild', [Table\PartitionController::class, 'rebuild']);
            $routes->post('/repair', [Table\PartitionController::class, 'repair']);
            $routes->post('/truncate', [Table\PartitionController::class, 'truncate']);
        });
        $routes->addRoute(['GET', 'POST'], '/operations', Table\OperationsController::class);
        $routes->addRoute(['GET', 'POST'], '/recent-favorite', Table\RecentFavoriteController::class);
        $routes->addRoute(['GET', 'POST'], '/relation', Table\RelationController::class);
        $routes->addRoute(['GET', 'POST'], '/replace', Table\ReplaceController::class);
        $routes->addRoute(['GET', 'POST'], '/search', Table\SearchController::class);
        $routes->addRoute(['GET', 'POST'], '/sql', Table\SqlController::class);
        $routes->addGroup('/structure', static function (RouteCollector $routes): void {
            $routes->addRoute(['GET', 'POST'], '', Table\StructureController::class);
            $routes->post('/add-key', Table\Structure\AddKeyController::class);
            $routes->post('/browse', Table\Structure\BrowseController::class);
            $routes->post('/central-columns-add', Table\Structure\CentralColumnsAddController::class);
            $routes->post('/central-columns-remove', Table\Structure\CentralColumnsRemoveController::class);
            $routes->addRoute(['GET', 'POST'], '/change', Table\Structure\ChangeController::class);
            $routes->post('/drop', Table\DropColumnController::class);
            $routes->post('/drop-confirm', Table\DropColumnConfirmationController::class);
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
        $routes->addRoute(['GET', 'POST'], '/tracking', Table\TrackingController::class);
        $routes->addRoute(['GET', 'POST'], '/triggers', Table\TriggersController::class);
        $routes->addRoute(['GET', 'POST'], '/zoom-search', Table\ZoomSearchController::class);
    });
    $routes->post('/tables', TableController::class);
    $routes->addGroup('/themes', static function (RouteCollector $routes): void {
        $routes->get('', [ThemesController::class, 'index']);
        $routes->post('/set', [ThemesController::class, 'setTheme']);
    });
    $routes->addGroup('/transformation', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/overview', Transformation\OverviewController::class);
        $routes->addRoute(['GET', 'POST'], '/wrapper', Transformation\WrapperController::class);
    });
    $routes->addRoute(['GET', 'POST'], '/user-password', UserPasswordController::class);
    $routes->addRoute(['GET', 'POST'], '/version-check', VersionCheckController::class);
    $routes->addGroup('/view', static function (RouteCollector $routes): void {
        $routes->addRoute(['GET', 'POST'], '/create', View\CreateController::class);
        $routes->addRoute(['GET', 'POST'], '/operations', View\OperationsController::class);
    });
};
