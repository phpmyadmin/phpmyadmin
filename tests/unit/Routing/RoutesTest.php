<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Routing;

use FastRoute\DataGenerator\GroupCountBased as DataGeneratorGroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParserStd;
use PhpMyAdmin\Controllers\BrowseForeignersController;
use PhpMyAdmin\Controllers\ChangeLogController;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Controllers\CollationConnectionController;
use PhpMyAdmin\Controllers\ColumnController;
use PhpMyAdmin\Controllers\Console\Bookmark;
use PhpMyAdmin\Controllers\Console\UpdateConfigController;
use PhpMyAdmin\Controllers\Database;
use PhpMyAdmin\Controllers\Database\Structure\CentralColumns;
use PhpMyAdmin\Controllers\DatabaseController;
use PhpMyAdmin\Controllers\ErrorReportController;
use PhpMyAdmin\Controllers\Export;
use PhpMyAdmin\Controllers\GisDataEditorController;
use PhpMyAdmin\Controllers\GitInfoController;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Controllers\Import;
use PhpMyAdmin\Controllers\JavaScriptMessagesController;
use PhpMyAdmin\Controllers\LicenseController;
use PhpMyAdmin\Controllers\LintController;
use PhpMyAdmin\Controllers\LogoutController;
use PhpMyAdmin\Controllers\Navigation\UpdateNavWidthConfigController;
use PhpMyAdmin\Controllers\NavigationController;
use PhpMyAdmin\Controllers\Normalization;
use PhpMyAdmin\Controllers\Operations;
use PhpMyAdmin\Controllers\PhpInfoController;
use PhpMyAdmin\Controllers\Preferences;
use PhpMyAdmin\Controllers\SchemaExportController;
use PhpMyAdmin\Controllers\Server;
use PhpMyAdmin\Controllers\Sql;
use PhpMyAdmin\Controllers\SyncFavoriteTablesController;
use PhpMyAdmin\Controllers\Table;
use PhpMyAdmin\Controllers\TableController;
use PhpMyAdmin\Controllers\ThemesController;
use PhpMyAdmin\Controllers\ThemeSetController;
use PhpMyAdmin\Controllers\Transformation;
use PhpMyAdmin\Controllers\Triggers;
use PhpMyAdmin\Controllers\UserPasswordController;
use PhpMyAdmin\Controllers\VersionCheckController;
use PhpMyAdmin\Controllers\View;
use PhpMyAdmin\Routing\Routes;
use PhpMyAdmin\Tests\Routing\Fixtures\Controllers\FooController;
use PhpMyAdmin\Tests\Routing\Fixtures\Controllers\One\BarController;
use PhpMyAdmin\Tests\Routing\Fixtures\Controllers\One\FooBarController;
use PhpMyAdmin\Tests\Routing\Fixtures\Controllers\One\Two\VariableController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(Routes::class)]
final class RoutesTest extends TestCase
{
    public function testCollect(): void
    {
        $reflectorPath = new ReflectionProperty(Routes::class, 'controllersPath');
        $reflectorPath->setValue(null, __DIR__ . '/Fixtures/Controllers');
        $reflectorNamespace = new ReflectionProperty(Routes::class, 'controllersNamespace');
        $reflectorNamespace->setValue(null, 'PhpMyAdmin\Tests\Routing\Fixtures\Controllers');

        $expectedGetRoutes = [
            '/another-route' => FooBarController::class,
            '/bar-route' => BarController::class,
            '/foo' => FooController::class,
            '/foo/route' => FooController::class,
            '' => FooBarController::class,
            '/' => FooBarController::class,
        ];
        $expectedPostRoutes = ['/foo' => FooController::class, '/foo/route' => FooController::class];
        $expectedRegexRoutes = [
            [
                'regex' => '~^(?|/route\-with\-var/([^/]+))$~',
                'routeMap' => [2 => [VariableController::class, ['variable' => 'variable']]],
            ],
        ];
        $expected = [
            ['GET' => $expectedGetRoutes, 'POST' => $expectedPostRoutes],
            ['GET' => $expectedRegexRoutes, 'POST' => $expectedRegexRoutes],
        ];

        $routeCollector = new RouteCollector(new RouteParserStd(), new DataGeneratorGroupCountBased());
        Routes::collect($routeCollector);
        self::assertSame($expected, $routeCollector->getData());

        $reflectorPath->setValue(null, $reflectorPath->getDefaultValue());
        $reflectorNamespace->setValue(null, $reflectorNamespace->getDefaultValue());
    }

    public function testCollectFromControllers(): void
    {
        $routeCollector = new RouteCollector(new RouteParserStd(), new DataGeneratorGroupCountBased());
        Routes::collect($routeCollector);
        $expected = [
            ['GET' => $this->getExpectedGetRoutes(), 'POST' => $this->getExpectedPostRoutes()],
            ['GET' => $this->getExpectedRegexGetRoutes(), 'POST' => $this->getExpectedRegexPostRoutes()],
        ];
        self::assertSame($expected, $routeCollector->getData());
    }

    /** @return array<string, class-string> */
    private function getExpectedGetRoutes(): array
    {
        return [
            '/browse-foreigners' => BrowseForeignersController::class,
            '/changelog' => ChangeLogController::class,
            '/check-relations' => CheckRelationsController::class,
            '/console/bookmark/refresh' => Bookmark\RefreshController::class,
            '/database/central-columns' => Database\CentralColumnsController::class,
            '/database/data-dictionary' => Database\DataDictionaryController::class,
            '/database/designer' => Database\DesignerController::class,
            '/database/events' => Database\EventsController::class,
            '/database/export' => Database\ExportController::class,
            '/database/import' => Database\ImportController::class,
            '/database/multi-table-query' => Database\MultiTableQueryController::class,
            '/database/multi-table-query/tables' => Database\MultiTableQuery\TablesController::class,
            '/database/operations' => Operations\DatabaseController::class,
            '/database/privileges' => Database\PrivilegesController::class,
            '/database/routines' => Database\RoutinesController::class,
            '/database/search' => Database\SearchController::class,
            '/database/sql' => Database\SqlController::class,
            '/database/structure' => Database\StructureController::class,
            '/database/structure/real-row-count' => Database\Structure\RealRowCountController::class,
            '/database/tracking' => Database\TrackingController::class,
            '/error-report' => ErrorReportController::class,
            '/export' => Export\ExportController::class,
            '/export/check-time-out' => Export\CheckTimeOutController::class,
            '/gis-data-editor' => GisDataEditorController::class,
            '/git-revision' => GitInfoController::class,
            '/import' => Import\ImportController::class,
            '/import-status' => Import\StatusController::class,
            '/license' => LicenseController::class,
            '/lint' => LintController::class,
            '/logout' => LogoutController::class,
            '/messages' => JavaScriptMessagesController::class,
            '/navigation' => NavigationController::class,
            '/normalization' => Normalization\MainController::class,
            '/phpinfo' => PhpInfoController::class,
            '/preferences/export' => Preferences\ExportController::class,
            '/preferences/features' => Preferences\FeaturesController::class,
            '/preferences/import' => Preferences\ImportController::class,
            '/preferences/main-panel' => Preferences\MainPanelController::class,
            '/preferences/manage' => Preferences\ManageController::class,
            '/preferences/navigation' => Preferences\NavigationController::class,
            '/preferences/sql' => Preferences\SqlController::class,
            '/preferences/two-factor' => Preferences\TwoFactorController::class,
            '/schema-export' => SchemaExportController::class,
            '/server/binlog' => Server\BinlogController::class,
            '/server/collations' => Server\CollationsController::class,
            '/server/databases' => Server\DatabasesController::class,
            '/server/engines' => Server\EnginesController::class,
            '/server/export' => Server\ExportController::class,
            '/server/import' => Server\ImportController::class,
            '/server/plugins' => Server\PluginsController::class,
            '/server/privileges' => Server\PrivilegesController::class,
            '/server/replication' => Server\ReplicationController::class,
            '/server/sql' => Server\SqlController::class,
            '/server/status' => Server\Status\StatusController::class,
            '/server/status/advisor' => Server\Status\AdvisorController::class,
            '/server/status/monitor' => Server\Status\MonitorController::class,
            '/server/status/processes' => Server\Status\ProcessesController::class,
            '/server/status/queries' => Server\Status\QueriesController::class,
            '/server/status/variables' => Server\Status\VariablesController::class,
            '/server/user-groups' => Server\UserGroupsController::class,
            '/server/user-groups/edit-form' => Server\UserGroupsFormController::class,
            '/server/variables' => Server\VariablesController::class,
            '/sql' => Sql\SqlController::class,
            '/sql/get-default-fk-check-value' => Sql\DefaultForeignKeyCheckValueController::class,
            '/table/add-field' => Table\AddFieldController::class,
            '/table/change' => Table\ChangeController::class,
            '/table/chart' => Table\ChartController::class,
            '/table/create' => Table\CreateController::class,
            '/table/export' => Table\ExportController::class,
            '/table/find-replace' => Table\FindReplaceController::class,
            '/table/get-field' => Table\GetFieldController::class,
            '/table/gis-visualization' => Table\GisVisualizationController::class,
            '/table/import' => Table\ImportController::class,
            '/table/indexes' => Table\IndexesController::class,
            '/table/indexes/rename' => Table\IndexRenameController::class,
            '/table/operations' => Operations\TableController::class,
            '/table/privileges' => Table\PrivilegesController::class,
            '/table/recent-favorite' => Table\RecentFavoriteController::class,
            '/table/relation' => Table\RelationController::class,
            '/table/replace' => Table\ReplaceController::class,
            '/table/search' => Table\SearchController::class,
            '/table/sql' => Table\SqlController::class,
            '/table/structure' => Table\StructureController::class,
            '/table/structure/change' => Table\Structure\ChangeController::class,
            '/table/tracking' => Table\TrackingController::class,
            '/table/zoom-search' => Table\ZoomSearchController::class,
            '/themes' => ThemesController::class,
            '/transformation/overview' => Transformation\OverviewController::class,
            '/transformation/wrapper' => Transformation\WrapperController::class,
            '/triggers' => Triggers\IndexController::class,
            '/user-password' => UserPasswordController::class,
            '/version-check' => VersionCheckController::class,
            '/view/create' => View\CreateController::class,
            '/view/operations' => Operations\ViewController::class,
            '' => HomeController::class,
            '/' => HomeController::class,
        ];
    }

    /** @return array<string, class-string> */
    private function getExpectedPostRoutes(): array
    {
        return [
            '/browse-foreigners' => BrowseForeignersController::class,
            '/check-relations' => CheckRelationsController::class,
            '/collation-connection' => CollationConnectionController::class,
            '/columns' => ColumnController::class,
            '/console/bookmark/add' => Bookmark\AddController::class,
            '/console/update-config' => UpdateConfigController::class,
            '/database/central-columns' => Database\CentralColumnsController::class,
            '/database/central-columns/populate' => Database\CentralColumns\PopulateColumnsController::class,
            '/database/designer' => Database\DesignerController::class,
            '/database/events' => Database\EventsController::class,
            '/database/export' => Database\ExportController::class,
            '/database/import' => Database\ImportController::class,
            '/database/multi-table-query/query' => Database\MultiTableQuery\QueryController::class,
            '/database/operations' => Operations\DatabaseController::class,
            '/database/operations/collation' => Operations\Database\CollationController::class,
            '/database/routines' => Database\RoutinesController::class,
            '/database/search' => Database\SearchController::class,
            '/database/sql' => Database\SqlController::class,
            '/database/sql/autocomplete' => Database\SqlAutoCompleteController::class,
            '/database/sql/format' => Database\SqlFormatController::class,
            '/database/structure' => Database\StructureController::class,
            '/database/structure/add-prefix' => Database\Structure\AddPrefixController::class,
            '/database/structure/add-prefix-table' => Database\Structure\AddPrefixTableController::class,
            '/database/structure/central-columns/add' => CentralColumns\AddController::class,
            '/database/structure/central-columns/make-consistent' => CentralColumns\MakeConsistentController::class,
            '/database/structure/central-columns/remove' => CentralColumns\RemoveController::class,
            '/database/structure/change-prefix-form' => Database\Structure\ChangePrefixFormController::class,
            '/database/structure/copy-form' => Database\Structure\CopyFormController::class,
            '/database/structure/copy-table' => Database\Structure\CopyTableController::class,
            '/database/structure/copy-table-with-prefix' => Database\Structure\CopyTableWithPrefixController::class,
            '/database/structure/drop-form' => Database\Structure\DropFormController::class,
            '/database/structure/drop-table' => Database\Structure\DropTableController::class,
            '/database/structure/empty-form' => Database\Structure\EmptyFormController::class,
            '/database/structure/empty-table' => Database\Structure\EmptyTableController::class,
            '/database/structure/favorite-table' => Database\Structure\FavoriteTableController::class,
            '/database/structure/real-row-count' => Database\Structure\RealRowCountController::class,
            '/database/structure/replace-prefix' => Database\Structure\ReplacePrefixController::class,
            '/database/structure/show-create' => Database\Structure\ShowCreateController::class,
            '/database/tracking' => Database\TrackingController::class,
            '/databases' => DatabaseController::class,
            '/error-report' => ErrorReportController::class,
            '/export' => Export\ExportController::class,
            '/export/tables' => Export\TablesController::class,
            '/export/template/create' => Export\Template\CreateController::class,
            '/export/template/delete' => Export\Template\DeleteController::class,
            '/export/template/load' => Export\Template\LoadController::class,
            '/export/template/update' => Export\Template\UpdateController::class,
            '/gis-data-editor' => GisDataEditorController::class,
            '/git-revision' => GitInfoController::class,
            '/import' => Import\ImportController::class,
            '/import-status' => Import\StatusController::class,
            '/import/simulate-dml' => Import\SimulateDmlController::class,
            '/lint' => LintController::class,
            '/logout' => LogoutController::class,
            '/navigation' => NavigationController::class,
            '/navigation/update-width' => UpdateNavWidthConfigController::class,
            '/normalization' => Normalization\MainController::class,
            '/normalization/1nf/step1' => Normalization\FirstNormalForm\FirstStepController::class,
            '/normalization/1nf/step2' => Normalization\FirstNormalForm\SecondStepController::class,
            '/normalization/1nf/step3' => Normalization\FirstNormalForm\ThirdStepController::class,
            '/normalization/1nf/step4' => Normalization\FirstNormalForm\FourthStepController::class,
            '/normalization/2nf/create-new-tables' => Normalization\SecondNormalForm\CreateNewTablesController::class,
            '/normalization/2nf/new-tables' => Normalization\SecondNormalForm\NewTablesController::class,
            '/normalization/2nf/step1' => Normalization\SecondNormalForm\FirstStepController::class,
            '/normalization/3nf/create-new-tables' => Normalization\ThirdNormalForm\CreateNewTablesController::class,
            '/normalization/3nf/new-tables' => Normalization\ThirdNormalForm\NewTablesController::class,
            '/normalization/3nf/step1' => Normalization\ThirdNormalForm\FirstStepController::class,
            '/normalization/add-new-primary' => Normalization\AddNewPrimaryController::class,
            '/normalization/create-new-column' => Normalization\CreateNewColumnController::class,
            '/normalization/get-columns' => Normalization\GetColumnsController::class,
            '/normalization/move-repeating-group' => Normalization\MoveRepeatingGroup::class,
            '/normalization/partial-dependencies' => Normalization\PartialDependenciesController::class,
            '/preferences/export' => Preferences\ExportController::class,
            '/preferences/features' => Preferences\FeaturesController::class,
            '/preferences/import' => Preferences\ImportController::class,
            '/preferences/main-panel' => Preferences\MainPanelController::class,
            '/preferences/manage' => Preferences\ManageController::class,
            '/preferences/navigation' => Preferences\NavigationController::class,
            '/preferences/sql' => Preferences\SqlController::class,
            '/preferences/two-factor' => Preferences\TwoFactorController::class,
            '/schema-export' => SchemaExportController::class,
            '/server/binlog' => Server\BinlogController::class,
            '/server/databases' => Server\DatabasesController::class,
            '/server/databases/create' => Server\Databases\CreateController::class,
            '/server/databases/destroy' => Server\Databases\DestroyController::class,
            '/server/export' => Server\ExportController::class,
            '/server/import' => Server\ImportController::class,
            '/server/privileges' => Server\PrivilegesController::class,
            '/server/privileges/account-lock' => Server\Privileges\AccountLockController::class,
            '/server/privileges/account-unlock' => Server\Privileges\AccountUnlockController::class,
            '/server/replication' => Server\ReplicationController::class,
            '/server/sql' => Server\SqlController::class,
            '/server/status/monitor/chart' => Server\Status\Monitor\ChartingDataController::class,
            '/server/status/monitor/general-log' => Server\Status\Monitor\GeneralLogController::class,
            '/server/status/monitor/log-vars' => Server\Status\Monitor\LogVarsController::class,
            '/server/status/monitor/query' => Server\Status\Monitor\QueryAnalyzerController::class,
            '/server/status/monitor/slow-log' => Server\Status\Monitor\SlowLogController::class,
            '/server/status/processes' => Server\Status\ProcessesController::class,
            '/server/status/processes/refresh' => Server\Status\Processes\RefreshController::class,
            '/server/status/variables' => Server\Status\VariablesController::class,
            '/server/user-groups' => Server\UserGroupsController::class,
            '/sql' => Sql\SqlController::class,
            '/sql/get-enum-values' => Sql\EnumValuesController::class,
            '/sql/get-relational-values' => Sql\RelationalValuesController::class,
            '/sql/get-set-values' => Sql\SetValuesController::class,
            '/sql/set-column-preferences' => Sql\ColumnPreferencesController::class,
            '/sync-favorite-tables' => SyncFavoriteTablesController::class,
            '/table/add-field' => Table\AddFieldController::class,
            '/table/change' => Table\ChangeController::class,
            '/table/change/rows' => Table\ChangeRowsController::class,
            '/table/chart' => Table\ChartController::class,
            '/table/create' => Table\CreateController::class,
            '/table/delete/confirm' => Table\DeleteConfirmController::class,
            '/table/delete/rows' => Table\DeleteRowsController::class,
            '/table/export' => Table\ExportController::class,
            '/table/export/rows' => Table\ExportRowsController::class,
            '/table/find-replace' => Table\FindReplaceController::class,
            '/table/get-field' => Table\GetFieldController::class,
            '/table/gis-visualization' => Table\GisVisualizationController::class,
            '/table/import' => Table\ImportController::class,
            '/table/indexes' => Table\IndexesController::class,
            '/table/indexes/rename' => Table\IndexRenameController::class,
            '/table/maintenance/analyze' => Table\Maintenance\AnalyzeController::class,
            '/table/maintenance/check' => Table\Maintenance\CheckController::class,
            '/table/maintenance/checksum' => Table\Maintenance\ChecksumController::class,
            '/table/maintenance/optimize' => Table\Maintenance\OptimizeController::class,
            '/table/maintenance/repair' => Table\Maintenance\RepairController::class,
            '/table/operations' => Operations\TableController::class,
            '/table/partition/analyze' => Table\Partition\AnalyzeController::class,
            '/table/partition/check' => Table\Partition\CheckController::class,
            '/table/partition/drop' => Table\Partition\DropController::class,
            '/table/partition/optimize' => Table\Partition\OptimizeController::class,
            '/table/partition/rebuild' => Table\Partition\RebuildController::class,
            '/table/partition/repair' => Table\Partition\RepairController::class,
            '/table/partition/truncate' => Table\Partition\TruncateController::class,
            '/table/recent-favorite' => Table\RecentFavoriteController::class,
            '/table/relation' => Table\RelationController::class,
            '/table/replace' => Table\ReplaceController::class,
            '/table/search' => Table\SearchController::class,
            '/table/sql' => Table\SqlController::class,
            '/table/structure' => Table\StructureController::class,
            '/table/structure/add-key' => Table\Structure\AddKeyController::class,
            '/table/structure/browse' => Table\Structure\BrowseController::class,
            '/table/structure/central-columns-add' => Table\Structure\CentralColumnsAddController::class,
            '/table/structure/central-columns-remove' => Table\Structure\CentralColumnsRemoveController::class,
            '/table/structure/change' => Table\Structure\ChangeController::class,
            '/table/structure/drop' => Table\DropColumnController::class,
            '/table/structure/drop-confirm' => Table\DropColumnConfirmationController::class,
            '/table/structure/fulltext' => Table\Structure\FulltextController::class,
            '/table/structure/index' => Table\Structure\AddIndexController::class,
            '/table/structure/move-columns' => Table\Structure\MoveColumnsController::class,
            '/table/structure/partitioning' => Table\Structure\PartitioningController::class,
            '/table/structure/primary' => Table\Structure\PrimaryController::class,
            '/table/structure/reserved-word-check' => Table\Structure\ReservedWordCheckController::class,
            '/table/structure/save' => Table\Structure\SaveController::class,
            '/table/structure/spatial' => Table\Structure\SpatialController::class,
            '/table/structure/unique' => Table\Structure\UniqueController::class,
            '/table/tracking' => Table\TrackingController::class,
            '/table/zoom-search' => Table\ZoomSearchController::class,
            '/tables' => TableController::class,
            '/themes/set' => ThemeSetController::class,
            '/transformation/overview' => Transformation\OverviewController::class,
            '/transformation/wrapper' => Transformation\WrapperController::class,
            '/triggers' => Triggers\IndexController::class,
            '/user-password' => UserPasswordController::class,
            '/version-check' => VersionCheckController::class,
            '/view/create' => View\CreateController::class,
            '/view/operations' => Operations\ViewController::class,
            '' => HomeController::class,
            '/' => HomeController::class,
        ];
    }

    /** @return array{array{regex: string, routeMap: array<int, array{class-string, array<string, string>}>}} */
    private function getExpectedRegexGetRoutes(): array
    {
        $regex = '~^(?|/server/engines/([^/]+)|/server/engines/([^/]+)/([^/]+)|/server/variables/get/([^/]+)()())$~';

        return [
            [
                'regex' => $regex,
                'routeMap' => [
                    2 => [Server\ShowEngineController::class, ['engine' => 'engine']],
                    3 => [Server\ShowEngineController::class, ['engine' => 'engine', 'page' => 'page']],
                    4 => [Server\Variables\GetVariableController::class, ['name' => 'name']],
                ],
            ],
        ];
    }

    /** @return array{array{regex: string, routeMap: array<int, array{class-string, array<string, string>}>}} */
    private function getExpectedRegexPostRoutes(): array
    {
        return [
            [
                'regex' => '~^(?|/server/status/processes/kill/(\d+)|/server/variables/set/([^/]+)())$~',
                'routeMap' => [
                    2 => [Server\Status\Processes\KillController::class, ['id' => 'id']],
                    3 => [Server\Variables\SetVariableController::class, ['name' => 'name']],
                ],
            ],
        ];
    }
}
