<?php

declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\BrowseForeignersController;
use PhpMyAdmin\Controllers\ChangeLogController;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Controllers\CollationConnectionController;
use PhpMyAdmin\Controllers\ColumnController;
use PhpMyAdmin\Controllers\Console;
use PhpMyAdmin\Controllers\Database;
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
use PhpMyAdmin\Controllers\Setup;
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
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\UserPrivilegesFactory;

return [
    'services' => [
        BrowseForeignersController::class => [
            'class' => BrowseForeignersController::class,
            'arguments' => [
                '@response',
                '@browse_foreigners',
                '@relation',
            ],
        ],
        ChangeLogController::class => [
            'class' => ChangeLogController::class,
            'arguments' => ['@response', '@config', '@' . ResponseFactory::class, '@template'],
        ],
        CheckRelationsController::class => [
            'class' => CheckRelationsController::class,
            'arguments' => ['@response', '@relation', '@config'],
        ],
        CollationConnectionController::class => [
            'class' => CollationConnectionController::class,
            'arguments' => ['@response', '@config'],
        ],
        ColumnController::class => [
            'class' => ColumnController::class,
            'arguments' => ['@response', '@dbi'],
        ],
        UpdateNavWidthConfigController::class => [
            'class' => UpdateNavWidthConfigController::class,
            'arguments' => ['@response', '@config'],
        ],
        Console\Bookmark\AddController::class => [
            'class' => Console\Bookmark\AddController::class,
            'arguments' => [
                '@response',
                '@bookmarkRepository',
                '@config',
            ],
        ],
        Console\Bookmark\RefreshController::class => [
            'class' => Console\Bookmark\RefreshController::class,
            'arguments' => ['@response', '@' . \PhpMyAdmin\Console\Console::class],
        ],
        Console\UpdateConfigController::class => [
            'class' => Console\UpdateConfigController::class,
            'arguments' => ['@response', '@config'],
        ],
        Database\CentralColumns\PopulateColumnsController::class => [
            'class' => Database\CentralColumns\PopulateColumnsController::class,
            'arguments' => ['@response', '@central_columns'],
        ],
        Database\CentralColumnsController::class => [
            'class' => Database\CentralColumnsController::class,
            'arguments' => [
                '@response',
                '@central_columns',
                '@config',
            ],
        ],
        Database\DataDictionaryController::class => [
            'class' => Database\DataDictionaryController::class,
            'arguments' => [
                '@response',
                '@relation',
                '@transformations',
                '@dbi',
            ],
        ],
        Database\DesignerController::class => [
            'class' => Database\DesignerController::class,
            'arguments' => [
                '@response',
                '@template',
                '@designer',
                '@designer_common',
                '@' . DbTableExists::class,
            ],
        ],
        Database\EventsController::class => [
            'class' => Database\EventsController::class,
            'arguments' => [
                '@response',
                '@template',
                '@events',
                '@dbi',
                '@' . DbTableExists::class,
            ],
        ],
        Database\ExportController::class => [
            'class' => Database\ExportController::class,
            'arguments' => [
                '@response',
                '@export',
                '@export_options',
                '@' . PageSettings::class,
                '@' . DbTableExists::class,
            ],
        ],
        Database\ImportController::class => [
            'class' => Database\ImportController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . PageSettings::class,
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Database\MultiTableQuery\QueryController::class => [
            'class' => Database\MultiTableQuery\QueryController::class,
            'arguments' => ['@response', '@sql'],
        ],
        Database\MultiTableQuery\TablesController::class => [
            'class' => Database\MultiTableQuery\TablesController::class,
            'arguments' => ['@response', '@dbi'],
        ],
        Database\MultiTableQueryController::class => [
            'class' => Database\MultiTableQueryController::class,
            'arguments' => ['@response', '@template', '@dbi'],
        ],
        Operations\Database\CollationController::class => [
            'class' => Operations\Database\CollationController::class,
            'arguments' => [
                '@response',
                '@operations',
                '@dbi',
                '@' . DbTableExists::class,
            ],
        ],
        Operations\DatabaseController::class => [
            'class' => Operations\DatabaseController::class,
            'arguments' => [
                '@response',
                '@operations',
                '@' . UserPrivilegesFactory::class,
                '@relation',
                '@relation_cleanup',
                '@dbi',
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Database\PrivilegesController::class => [
            'class' => Database\PrivilegesController::class,
            'arguments' => [
                '@response',
                '@server_privileges',
                '@dbi',
                '@config',
            ],
        ],
        Database\RoutinesController::class => [
            'class' => Database\RoutinesController::class,
            'arguments' => [
                '@response',
                '@template',
                '@' . UserPrivilegesFactory::class,
                '@dbi',
                '@routines',
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Database\SearchController::class => [
            'class' => Database\SearchController::class,
            'arguments' => [
                '@response',
                '@template',
                '@dbi',
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Database\SqlAutoCompleteController::class => [
            'class' => Database\SqlAutoCompleteController::class,
            'arguments' => ['@response', '@dbi', '@config'],
        ],
        Database\SqlController::class => [
            'class' => Database\SqlController::class,
            'arguments' => [
                '@response',
                '@sql_query_form',
                '@' . PageSettings::class,
                '@' . DbTableExists::class,
            ],
        ],
        Database\SqlFormatController::class => [
            'class' => Database\SqlFormatController::class,
            'arguments' => ['@response'],
        ],
        Database\Structure\AddPrefixController::class => [
            'class' => Database\Structure\AddPrefixController::class,
            'arguments' => ['@response', '@' . ResponseFactory::class, '@template'],
        ],
        Database\Structure\AddPrefixTableController::class => [
            'class' => Database\Structure\AddPrefixTableController::class,
            'arguments' => ['@dbi', '@' . Database\StructureController::class],
        ],
        Database\Structure\CentralColumns\AddController::class => [
            'class' => Database\Structure\CentralColumns\AddController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\CentralColumns\MakeConsistentController::class => [
            'class' => Database\Structure\CentralColumns\MakeConsistentController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\CentralColumns\RemoveController::class => [
            'class' => Database\Structure\CentralColumns\RemoveController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\ChangePrefixFormController::class => [
            'class' => Database\Structure\ChangePrefixFormController::class,
            'arguments' => ['@response', '@' . ResponseFactory::class, '@template'],
        ],
        Database\Structure\CopyFormController::class => [
            'class' => Database\Structure\CopyFormController::class,
            'arguments' => ['@response', '@' . ResponseFactory::class, '@template'],
        ],
        Database\Structure\CopyTableController::class => [
            'class' => Database\Structure\CopyTableController::class,
            'arguments' => [
                '@operations',
                '@' . Database\StructureController::class,
                '@' . UserPrivilegesFactory::class,
                '@table_mover',
            ],
        ],
        Database\Structure\CopyTableWithPrefixController::class => [
            'class' => Database\Structure\CopyTableWithPrefixController::class,
            'arguments' => [
                '@' . Database\StructureController::class,
                '@table_mover',
            ],
        ],
        Database\Structure\DropFormController::class => [
            'class' => Database\Structure\DropFormController::class,
            'arguments' => ['@response', '@dbi'],
        ],
        Database\Structure\DropTableController::class => [
            'class' => Database\Structure\DropTableController::class,
            'arguments' => [
                '@dbi',
                '@relation_cleanup',
                '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\EmptyFormController::class => [
            'class' => Database\Structure\EmptyFormController::class,
            'arguments' => ['@response'],
        ],
        Database\Structure\EmptyTableController::class => [
            'class' => Database\Structure\EmptyTableController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . FlashMessenger::class,
                '@' . Database\StructureController::class,
                '@sql',
            ],
        ],
        Database\Structure\FavoriteTableController::class => [
            'class' => Database\Structure\FavoriteTableController::class,
            'arguments' => [
                '@response',
                '@template',
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Database\Structure\RealRowCountController::class => [
            'class' => Database\Structure\RealRowCountController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . DbTableExists::class,
            ],
        ],
        Database\Structure\ReplacePrefixController::class => [
            'class' => Database\Structure\ReplacePrefixController::class,
            'arguments' => ['@dbi', '@' . ResponseFactory::class, '@' . FlashMessenger::class],
        ],
        Database\Structure\ShowCreateController::class => [
            'class' => Database\Structure\ShowCreateController::class,
            'arguments' => ['@response', '@template', '@dbi'],
        ],
        Database\StructureController::class => [
            'class' => Database\StructureController::class,
            'arguments' => [
                '@response',
                '@template',
                '@relation',
                '@replication',
                '@dbi',
                '@tracking_checker',
                '@' . PageSettings::class,
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Database\TrackingController::class => [
            'class' => Database\TrackingController::class,
            'arguments' => [
                '@response',
                '@tracking',
                '@dbi',
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        DatabaseController::class => [
            'class' => DatabaseController::class,
            'arguments' => ['@response', '@dbi'],
        ],
        ErrorReportController::class => [
            'class' => ErrorReportController::class,
            'arguments' => [
                '@response',
                '@template',
                '@error_report',
                '@error_handler',
                '@dbi',
                '@config',
            ],
        ],
        Export\CheckTimeOutController::class => [
            'class' => Export\CheckTimeOutController::class,
            'arguments' => ['@response'],
        ],
        Export\ExportController::class => [
            'class' => Export\ExportController::class,
            'arguments' => ['@response', '@export', '@' . ResponseFactory::class, '@config'],
        ],
        Export\TablesController::class => [
            'class' => Export\TablesController::class,
            'arguments' => [
                '@response',
                '@' . Database\ExportController::class,
            ],
        ],
        Export\Template\CreateController::class => [
            'class' => Export\Template\CreateController::class,
            'arguments' => [
                '@response',
                '@template',
                '@export_template_model',
                '@relation',
                '@config',
            ],
        ],
        Export\Template\DeleteController::class => [
            'class' => Export\Template\DeleteController::class,
            'arguments' => [
                '@response',
                '@export_template_model',
                '@relation',
                '@config',
            ],
        ],
        Export\Template\LoadController::class => [
            'class' => Export\Template\LoadController::class,
            'arguments' => [
                '@response',
                '@export_template_model',
                '@relation',
                '@config',
            ],
        ],
        Export\Template\UpdateController::class => [
            'class' => Export\Template\UpdateController::class,
            'arguments' => [
                '@response',
                '@export_template_model',
                '@relation',
                '@config',
            ],
        ],
        GisDataEditorController::class => [
            'class' => GisDataEditorController::class,
            'arguments' => ['@response', '@template'],
        ],
        GitInfoController::class => [
            'class' => GitInfoController::class,
            'arguments' => ['@response', '@config'],
        ],
        HomeController::class => [
            'class' => HomeController::class,
            'arguments' => [
                '@response',
                '@config',
                '@' . ThemeManager::class,
                '@dbi',
                '@' . ResponseFactory::class,
            ],
        ],
        Import\ImportController::class => [
            'class' => Import\ImportController::class,
            'arguments' => [
                '@response',
                '@import',
                '@sql',
                '@dbi',
                '@bookmarkRepository',
                '@config',
            ],
        ],
        Import\SimulateDmlController::class => [
            'class' => Import\SimulateDmlController::class,
            'arguments' => ['@response', '@import_simulate_dml'],
        ],
        Import\StatusController::class => [
            'class' => Import\StatusController::class,
            'arguments' => ['@template'],
        ],
        JavaScriptMessagesController::class => [
            'class' => JavaScriptMessagesController::class,
            'arguments' => ['@' . ResponseFactory::class],
        ],
        LicenseController::class => [
            'class' => LicenseController::class,
            'arguments' => ['@response', '@' . ResponseFactory::class],
        ],
        LintController::class => ['class' => LintController::class, 'arguments' => ['@' . ResponseFactory::class]],
        LogoutController::class => [
            'class' => LogoutController::class,
            'arguments' => ['@' . AuthenticationPluginFactory::class],
        ],
        NavigationController::class => [
            'class' => NavigationController::class,
            'arguments' => [
                '@response',
                '@navigation',
                '@relation',
                '@' . PageSettings::class,
            ],
        ],
        Normalization\FirstNormalForm\FirstStepController::class => [
            'class' => Normalization\FirstNormalForm\FirstStepController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\FirstNormalForm\FourthStepController::class => [
            'class' => Normalization\FirstNormalForm\FourthStepController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\FirstNormalForm\SecondStepController::class => [
            'class' => Normalization\FirstNormalForm\SecondStepController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\FirstNormalForm\ThirdStepController::class => [
            'class' => Normalization\FirstNormalForm\ThirdStepController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\SecondNormalForm\CreateNewTablesController::class => [
            'class' => Normalization\SecondNormalForm\CreateNewTablesController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\SecondNormalForm\FirstStepController::class => [
            'class' => Normalization\SecondNormalForm\FirstStepController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\SecondNormalForm\NewTablesController::class => [
            'class' => Normalization\SecondNormalForm\NewTablesController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\ThirdNormalForm\CreateNewTablesController::class => [
            'class' => Normalization\ThirdNormalForm\CreateNewTablesController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\ThirdNormalForm\FirstStepController::class => [
            'class' => Normalization\ThirdNormalForm\FirstStepController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\ThirdNormalForm\NewTablesController::class => [
            'class' => Normalization\ThirdNormalForm\NewTablesController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\AddNewPrimaryController::class => [
            'class' => Normalization\AddNewPrimaryController::class,
            'arguments' => [
                '@response',
                '@normalization',
                '@' . UserPrivilegesFactory::class,
            ],
        ],
        Normalization\CreateNewColumnController::class => [
            'class' => Normalization\CreateNewColumnController::class,
            'arguments' => [
                '@response',
                '@normalization',
                '@' . UserPrivilegesFactory::class,
            ],
        ],
        Normalization\GetColumnsController::class => [
            'class' => Normalization\GetColumnsController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\MainController::class => [
            'class' => Normalization\MainController::class,
            'arguments' => ['@response'],
        ],
        Normalization\MoveRepeatingGroup::class => [
            'class' => Normalization\MoveRepeatingGroup::class,
            'arguments' => ['@response', '@normalization'],
        ],
        Normalization\PartialDependenciesController::class => [
            'class' => Normalization\PartialDependenciesController::class,
            'arguments' => ['@response', '@normalization'],
        ],
        PhpInfoController::class => [
            'class' => PhpInfoController::class,
            'arguments' => ['@response', '@' . ResponseFactory::class, '@config'],
        ],
        Preferences\ExportController::class => [
            'class' => Preferences\ExportController::class,
            'arguments' => [
                '@response',
                '@user_preferences',
                '@relation',
                '@config',
                '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\FeaturesController::class => [
            'class' => Preferences\FeaturesController::class,
            'arguments' => [
                '@response',
                '@user_preferences',
                '@relation',
                '@config',
                '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\ImportController::class => [
            'class' => Preferences\ImportController::class,
            'arguments' => [
                '@response',
                '@user_preferences',
                '@relation',
                '@config',
                '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\MainPanelController::class => [
            'class' => Preferences\MainPanelController::class,
            'arguments' => [
                '@response',
                '@user_preferences',
                '@relation',
                '@config',
                '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\ManageController::class => [
            'class' => Preferences\ManageController::class,
            'arguments' => [
                '@response',
                '@user_preferences',
                '@relation',
                '@config',
                '@' . PhpMyAdmin\Theme\ThemeManager::class,
                '@' . ResponseFactory::class,
            ],
        ],
        Preferences\NavigationController::class => [
            'class' => Preferences\NavigationController::class,
            'arguments' => [
                '@response',
                '@user_preferences',
                '@relation',
                '@config',
                '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\SqlController::class => [
            'class' => Preferences\SqlController::class,
            'arguments' => [
                '@response',
                '@user_preferences',
                '@relation',
                '@config',
                '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\TwoFactorController::class => [
            'class' => Preferences\TwoFactorController::class,
            'arguments' => ['@response', '@relation', '@config'],
        ],
        SchemaExportController::class => [
            'class' => SchemaExportController::class,
            'arguments' => ['@export', '@response', '@' . ResponseFactory::class],
        ],
        Server\BinlogController::class => [
            'class' => Server\BinlogController::class,
            'arguments' => ['@response', '@dbi', '@config'],
        ],
        Server\CollationsController::class => [
            'class' => Server\CollationsController::class,
            'arguments' => ['@response', '@dbi', '@config'],
        ],
        Server\Databases\CreateController::class => [
            'class' => Server\Databases\CreateController::class,
            'arguments' => ['@response', '@dbi', '@config'],
        ],
        Server\Databases\DestroyController::class => [
            'class' => Server\Databases\DestroyController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@transformations',
                '@relation_cleanup',
                '@' . UserPrivilegesFactory::class,
                '@config',
            ],
        ],
        Server\DatabasesController::class => [
            'class' => Server\DatabasesController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . UserPrivilegesFactory::class,
                '@config',
            ],
        ],
        Server\EnginesController::class => [
            'class' => Server\EnginesController::class,
            'arguments' => ['@response', '@dbi'],
        ],
        Server\ExportController::class => [
            'class' => Server\ExportController::class,
            'arguments' => [
                '@response',
                '@export_options',
                '@dbi',
                '@' . PageSettings::class,
            ],
        ],
        Server\ImportController::class => [
            'class' => Server\ImportController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . PageSettings::class,
                '@config',
            ],
        ],
        Server\PluginsController::class => [
            'class' => Server\PluginsController::class,
            'arguments' => [
                '@response',
                '@server_plugins',
                '@dbi',
            ],
        ],
        Server\Privileges\AccountLockController::class => [
            'class' => Server\Privileges\AccountLockController::class,
            'arguments' => ['@response', '@server_privileges_account_locking'],
        ],
        Server\Privileges\AccountUnlockController::class => [
            'class' => Server\Privileges\AccountUnlockController::class,
            'arguments' => ['@response', '@server_privileges_account_locking'],
        ],
        Server\PrivilegesController::class => [
            'class' => Server\PrivilegesController::class,
            'arguments' => [
                '@response',
                '@template',
                '@relation',
                '@dbi',
                '@' . UserPrivilegesFactory::class,
                '@config',
            ],
        ],
        Server\ReplicationController::class => [
            'class' => Server\ReplicationController::class,
            'arguments' => ['@response', '@replication_gui', '@dbi'],
        ],
        Server\ShowEngineController::class => [
            'class' => Server\ShowEngineController::class,
            'arguments' => ['@response', '@dbi'],
        ],
        Server\SqlController::class => [
            'class' => Server\SqlController::class,
            'arguments' => [
                '@response',
                '@sql_query_form',
                '@dbi',
                '@' . PageSettings::class,
            ],
        ],
        Server\UserGroupsController::class => [
            'class' => Server\UserGroupsController::class,
            'arguments' => ['@response', '@relation', '@dbi'],
        ],
        Server\UserGroupsFormController::class => [
            'class' => Server\UserGroupsFormController::class,
            'arguments' => [
                '@response',
                '@template',
                '@relation',
                '@dbi',
            ],
        ],
        Server\Status\AdvisorController::class => [
            'class' => Server\Status\AdvisorController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@advisor',
            ],
        ],
        Server\Status\Monitor\ChartingDataController::class => [
            'class' => Server\Status\Monitor\ChartingDataController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@status_monitor',
                '@dbi',
            ],
        ],
        Server\Status\Monitor\GeneralLogController::class => [
            'class' => Server\Status\Monitor\GeneralLogController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@status_monitor',
                '@dbi',
            ],
        ],
        Server\Status\Monitor\LogVarsController::class => [
            'class' => Server\Status\Monitor\LogVarsController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@status_monitor',
                '@dbi',
            ],
        ],
        Server\Status\Monitor\QueryAnalyzerController::class => [
            'class' => Server\Status\Monitor\QueryAnalyzerController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@status_monitor',
                '@dbi',
            ],
        ],
        Server\Status\Monitor\SlowLogController::class => [
            'class' => Server\Status\Monitor\SlowLogController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@status_monitor',
                '@dbi',
            ],
        ],
        Server\Status\MonitorController::class => [
            'class' => Server\Status\MonitorController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@dbi',
            ],
        ],
        Server\Status\Processes\KillController::class => [
            'class' => Server\Status\Processes\KillController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@dbi',
            ],
        ],
        Server\Status\Processes\RefreshController::class => [
            'class' => Server\Status\Processes\RefreshController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@status_processes',
            ],
        ],
        Server\Status\ProcessesController::class => [
            'class' => Server\Status\ProcessesController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@dbi',
                '@status_processes',
            ],
        ],
        Server\Status\QueriesController::class => [
            'class' => Server\Status\QueriesController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@dbi',
            ],
        ],
        Server\Status\StatusController::class => [
            'class' => Server\Status\StatusController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@replication_gui',
                '@dbi',
            ],
        ],
        Server\Status\VariablesController::class => [
            'class' => Server\Status\VariablesController::class,
            'arguments' => [
                '@response',
                '@template',
                '@status_data',
                '@dbi',
            ],
        ],
        Server\Variables\GetVariableController::class => [
            'class' => Server\Variables\GetVariableController::class,
            'arguments' => ['@response', '@dbi'],
        ],
        Server\Variables\SetVariableController::class => [
            'class' => Server\Variables\SetVariableController::class,
            'arguments' => ['@response', '@template', '@dbi'],
        ],
        Server\VariablesController::class => [
            'class' => Server\VariablesController::class,
            'arguments' => ['@response', '@template', '@dbi'],
        ],
        Setup\MainController::class => [
            'class' => Setup\MainController::class,
            'arguments' => ['@' . ResponseFactory::class, '@response', '@template', '@config'],
        ],
        Setup\ShowConfigController::class => [
            'class' => Setup\ShowConfigController::class,
            'arguments' => ['@' . ResponseFactory::class, '@template', '@config'],
        ],
        Setup\ValidateController::class => [
            'class' => Setup\ValidateController::class,
            'arguments' => ['@' . ResponseFactory::class, '@template', '@config'],
        ],
        Sql\ColumnPreferencesController::class => [
            'class' => Sql\ColumnPreferencesController::class,
            'arguments' => ['@response', '@dbi'],
        ],
        Sql\DefaultForeignKeyCheckValueController::class => [
            'class' => Sql\DefaultForeignKeyCheckValueController::class,
            'arguments' => ['@response'],
        ],
        Sql\EnumValuesController::class => [
            'class' => Sql\EnumValuesController::class,
            'arguments' => ['@response', '@template', '@sql'],
        ],
        Sql\RelationalValuesController::class => [
            'class' => Sql\RelationalValuesController::class,
            'arguments' => ['@response', '@sql'],
        ],
        Sql\SetValuesController::class => [
            'class' => Sql\SetValuesController::class,
            'arguments' => ['@response', '@template', '@sql'],
        ],
        Sql\SqlController::class => [
            'class' => Sql\SqlController::class,
            'arguments' => [
                '@response',
                '@sql',
                '@' . PageSettings::class,
                '@bookmarkRepository',
                '@config',
            ],
        ],
        Table\AddFieldController::class => [
            'class' => Table\AddFieldController::class,
            'arguments' => [
                '@response',
                '@transformations',
                '@config',
                '@dbi',
                '@table_columns_definition',
                '@' . DbTableExists::class,
                '@' . UserPrivilegesFactory::class,
            ],
        ],
        Table\ChangeController::class => [
            'class' => Table\ChangeController::class,
            'arguments' => [
                '@response',
                '@template',
                '@insert_edit',
                '@relation',
                '@' . PageSettings::class,
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Table\ChangeRowsController::class => [
            'class' => Table\ChangeRowsController::class,
            'arguments' => [
                '@response',
                '@' . Table\ChangeController::class,
            ],
        ],
        Table\ChartController::class => [
            'class' => Table\ChartController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Table\CreateController::class => [
            'class' => Table\CreateController::class,
            'arguments' => [
                '@response',
                '@transformations',
                '@config',
                '@dbi',
                '@table_columns_definition',
                '@' . UserPrivilegesFactory::class,
            ],
        ],
        Table\DeleteConfirmController::class => [
            'class' => Table\DeleteConfirmController::class,
            'arguments' => ['@response', '@' . DbTableExists::class],
        ],
        Table\DeleteRowsController::class => [
            'class' => Table\DeleteRowsController::class,
            'arguments' => ['@response', '@dbi', '@sql'],
        ],
        Table\DropColumnConfirmationController::class => [
            'class' => Table\DropColumnConfirmationController::class,
            'arguments' => ['@response', '@' . DbTableExists::class],
        ],
        Table\DropColumnController::class => [
            'class' => Table\DropColumnController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . FlashMessenger::class,
                '@relation_cleanup',
            ],
        ],
        Table\ExportController::class => [
            'class' => Table\ExportController::class,
            'arguments' => [
                '@response',
                '@export_options',
                '@' . PageSettings::class,
            ],
        ],
        Table\ExportRowsController::class => [
            'class' => Table\ExportRowsController::class,
            'arguments' => ['@response', '@' . Table\ExportController::class],
        ],
        Table\FindReplaceController::class => [
            'class' => Table\FindReplaceController::class,
            'arguments' => [
                '@response',
                '@template',
                '@dbi',
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Table\GetFieldController::class => [
            'class' => Table\GetFieldController::class,
            'arguments' => ['@response', '@dbi', '@' . ResponseFactory::class],
        ],
        Table\GisVisualizationController::class => [
            'class' => Table\GisVisualizationController::class,
            'arguments' => [
                '@response',
                '@template',
                '@dbi',
                '@' . DbTableExists::class,
                '@' . ResponseFactory::class,
                '@config',
            ],
        ],
        Table\ImportController::class => [
            'class' => Table\ImportController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . PageSettings::class,
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Table\IndexesController::class => [
            'class' => Table\IndexesController::class,
            'arguments' => [
                '@response',
                '@template',
                '@dbi',
                '@table_indexes',
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Table\IndexRenameController::class => [
            'class' => Table\IndexRenameController::class,
            'arguments' => [
                '@response',
                '@template',
                '@dbi',
                '@table_indexes',
                '@' . DbTableExists::class,
            ],
        ],
        Table\Maintenance\AnalyzeController::class => [
            'class' => Table\Maintenance\AnalyzeController::class,
            'arguments' => ['@response', '@table_maintenance', '@config'],
        ],
        Table\Maintenance\CheckController::class => [
            'class' => Table\Maintenance\CheckController::class,
            'arguments' => ['@response', '@table_maintenance', '@config'],
        ],
        Table\Maintenance\ChecksumController::class => [
            'class' => Table\Maintenance\ChecksumController::class,
            'arguments' => ['@response', '@table_maintenance', '@config'],
        ],
        Table\Maintenance\OptimizeController::class => [
            'class' => Table\Maintenance\OptimizeController::class,
            'arguments' => ['@response', '@table_maintenance', '@config'],
        ],
        Table\Maintenance\RepairController::class => [
            'class' => Table\Maintenance\RepairController::class,
            'arguments' => ['@response', '@table_maintenance', '@config'],
        ],
        Table\Partition\AnalyzeController::class => [
            'class' => Table\Partition\AnalyzeController::class,
            'arguments' => ['@response', '@partitioning_maintenance'],
        ],
        Table\Partition\CheckController::class => [
            'class' => Table\Partition\CheckController::class,
            'arguments' => ['@response', '@partitioning_maintenance'],
        ],
        Table\Partition\DropController::class => [
            'class' => Table\Partition\DropController::class,
            'arguments' => ['@response', '@partitioning_maintenance'],
        ],
        Table\Partition\OptimizeController::class => [
            'class' => Table\Partition\OptimizeController::class,
            'arguments' => ['@response', '@partitioning_maintenance'],
        ],
        Table\Partition\RebuildController::class => [
            'class' => Table\Partition\RebuildController::class,
            'arguments' => ['@response', '@partitioning_maintenance'],
        ],
        Table\Partition\RepairController::class => [
            'class' => Table\Partition\RepairController::class,
            'arguments' => ['@response', '@partitioning_maintenance'],
        ],
        Table\Partition\TruncateController::class => [
            'class' => Table\Partition\TruncateController::class,
            'arguments' => ['@response', '@partitioning_maintenance'],
        ],
        Operations\TableController::class => [
            'class' => Operations\TableController::class,
            'arguments' => [
                '@response',
                '@operations',
                '@' . UserPrivilegesFactory::class,
                '@relation',
                '@dbi',
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Table\PrivilegesController::class => [
            'class' => Table\PrivilegesController::class,
            'arguments' => [
                '@response',
                '@server_privileges',
                '@dbi',
                '@config',
            ],
        ],
        Table\RecentFavoriteController::class => [
            'class' => Table\RecentFavoriteController::class,
            'arguments' => ['@response'],
        ],
        Table\RelationController::class => [
            'class' => Table\RelationController::class,
            'arguments' => [
                '@response',
                '@template',
                '@relation',
                '@dbi',
                '@config',
            ],
        ],
        Table\ReplaceController::class => [
            'class' => Table\ReplaceController::class,
            'arguments' => [
                '@response',
                '@insert_edit',
                '@transformations',
                '@relation',
                '@dbi',
                '@' . Sql\SqlController::class,
                '@' . Database\SqlController::class,
                '@' . Table\ChangeController::class,
                '@' . Table\SqlController::class,
            ],
        ],
        Table\SearchController::class => [
            'class' => Table\SearchController::class,
            'arguments' => [
                '@response',
                '@template',
                '@table_search',
                '@relation',
                '@dbi',
                '@' . DbTableExists::class,
                '@config',
                '@sql',
            ],
        ],
        Table\SqlController::class => [
            'class' => Table\SqlController::class,
            'arguments' => [
                '@response',
                '@sql_query_form',
                '@' . PageSettings::class,
                '@' . DbTableExists::class,
            ],
        ],
        Table\Structure\AddIndexController::class => [
            'class' => Table\Structure\AddIndexController::class,
            'arguments' => [
                '@response',
                '@' . Table\StructureController::class,
                '@table_indexes',
            ],
        ],
        Table\Structure\AddKeyController::class => [
            'class' => Table\Structure\AddKeyController::class,
            'arguments' => ['@response', '@' . Table\StructureController::class, '@table_indexes'],
        ],
        Table\Structure\BrowseController::class => [
            'class' => Table\Structure\BrowseController::class,
            'arguments' => ['@response', '@sql'],
        ],
        Table\Structure\CentralColumnsAddController::class => [
            'class' => Table\Structure\CentralColumnsAddController::class,
            'arguments' => [
                '@response',
                '@central_columns',
                '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\CentralColumnsRemoveController::class => [
            'class' => Table\Structure\CentralColumnsRemoveController::class,
            'arguments' => [
                '@response',
                '@central_columns',
                '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\ChangeController::class => [
            'class' => Table\Structure\ChangeController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@table_columns_definition',
                '@' . UserPrivilegesFactory::class,
            ],
        ],
        Table\Structure\FulltextController::class => [
            'class' => Table\Structure\FulltextController::class,
            'arguments' => [
                '@response',
                '@' . Table\StructureController::class,
                '@table_indexes',
            ],
        ],
        Table\Structure\MoveColumnsController::class => [
            'class' => Table\Structure\MoveColumnsController::class,
            'arguments' => ['@response', '@template', '@dbi'],
        ],
        Table\Structure\PartitioningController::class => [
            'class' => Table\Structure\PartitioningController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@create_add_field',
                '@' . Table\StructureController::class,
                '@' . PageSettings::class,
            ],
        ],
        Table\Structure\PrimaryController::class => [
            'class' => Table\Structure\PrimaryController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . Table\StructureController::class,
                '@' . DbTableExists::class,
            ],
        ],
        Table\Structure\ReservedWordCheckController::class => [
            'class' => Table\Structure\ReservedWordCheckController::class,
            'arguments' => ['@response', '@config'],
        ],
        Table\Structure\SaveController::class => [
            'class' => Table\Structure\SaveController::class,
            'arguments' => [
                '@response',
                '@relation',
                '@transformations',
                '@dbi',
                '@' . Table\StructureController::class,
                '@' . UserPrivilegesFactory::class,
                '@config',
            ],
        ],
        Table\Structure\SpatialController::class => [
            'class' => Table\Structure\SpatialController::class,
            'arguments' => [
                '@response',
                '@' . Table\StructureController::class,
                '@table_indexes',
            ],
        ],
        Table\Structure\UniqueController::class => [
            'class' => Table\Structure\UniqueController::class,
            'arguments' => [
                '@response',
                '@' . Table\StructureController::class,
                '@table_indexes',
            ],
        ],
        Table\StructureController::class => [
            'class' => Table\StructureController::class,
            'arguments' => [
                '@response',
                '@template',
                '@relation',
                '@transformations',
                '@dbi',
                '@' . PageSettings::class,
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        Table\TrackingController::class => [
            'class' => Table\TrackingController::class,
            'arguments' => [
                '@response',
                '@tracking',
                '@tracking_checker',
                '@' . DbTableExists::class,
                '@' . ResponseFactory::class,
            ],
        ],
        Triggers\IndexController::class => [
            'class' => Triggers\IndexController::class,
            'arguments' => [
                '@response',
                '@template',
                '@dbi',
                '@triggers',
                '@' . DbTableExists::class,
            ],
        ],
        Table\ZoomSearchController::class => [
            'class' => Table\ZoomSearchController::class,
            'arguments' => [
                '@response',
                '@template',
                '@table_search',
                '@relation',
                '@dbi',
                '@' . DbTableExists::class,
                '@config',
            ],
        ],
        TableController::class => [
            'class' => TableController::class,
            'arguments' => ['@response', '@dbi'],
        ],
        ThemesController::class => [
            'class' => ThemesController::class,
            'arguments' => [
                '@response',
                '@template',
                '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        ThemeSetController::class => [
            'class' => ThemeSetController::class,
            'arguments' => [
                '@response',
                '@' . PhpMyAdmin\Theme\ThemeManager::class,
                '@user_preferences',
                '@config',
            ],
        ],
        Transformation\OverviewController::class => [
            'class' => Transformation\OverviewController::class,
            'arguments' => ['@response', '@transformations'],
        ],
        Transformation\WrapperController::class => [
            'class' => Transformation\WrapperController::class,
            'arguments' => [
                '@response',
                '@transformations',
                '@relation',
                '@dbi',
                '@' . DbTableExists::class,
                '@' . ResponseFactory::class,
            ],
        ],
        UserPasswordController::class => [
            'class' => UserPasswordController::class,
            'arguments' => [
                '@response',
                '@user_password',
                '@dbi',
                '@config',
            ],
        ],
        VersionCheckController::class => [
            'class' => VersionCheckController::class,
            'arguments' => ['@version_information', '@' . ResponseFactory::class],
        ],
        View\CreateController::class => [
            'class' => View\CreateController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . DbTableExists::class,
            ],
        ],
        Operations\ViewController::class => [
            'class' => Operations\ViewController::class,
            'arguments' => [
                '@response',
                '@dbi',
                '@' . DbTableExists::class,
            ],
        ],
        SyncFavoriteTablesController::class => [
            'class' => SyncFavoriteTablesController::class,
            'arguments' => ['@response', '@relation', '@config'],
        ],
    ],
];
