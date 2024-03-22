<?php

declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\BrowseForeignersController;
use PhpMyAdmin\Controllers\ChangeLogController;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Controllers\CollationConnectionController;
use PhpMyAdmin\Controllers\ColumnController;
use PhpMyAdmin\Controllers\Config;
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
use PhpMyAdmin\Controllers\NavigationController;
use PhpMyAdmin\Controllers\Normalization;
use PhpMyAdmin\Controllers\Operations;
use PhpMyAdmin\Controllers\PhpInfoController;
use PhpMyAdmin\Controllers\Preferences;
use PhpMyAdmin\Controllers\RecentTablesListController;
use PhpMyAdmin\Controllers\SchemaExportController;
use PhpMyAdmin\Controllers\Server;
use PhpMyAdmin\Controllers\Sql;
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
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\UserPrivilegesFactory;

return [
    'services' => [
        BrowseForeignersController::class => [
            'class' => BrowseForeignersController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$browseForeigners' => '@browse_foreigners',
                '$relation' => '@relation',
            ],
        ],
        ChangeLogController::class => [
            'class' => ChangeLogController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$config' => '@config'],
        ],
        CheckRelationsController::class => [
            'class' => CheckRelationsController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$relation' => '@relation'],
        ],
        CollationConnectionController::class => [
            'class' => CollationConnectionController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$config' => '@config'],
        ],
        ColumnController::class => [
            'class' => ColumnController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Config\GetConfigController::class => [
            'class' => Config\GetConfigController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$config' => '@config'],
        ],
        Config\SetConfigController::class => [
            'class' => Config\SetConfigController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$config' => '@config'],
        ],
        Console\Bookmark\AddController::class => [
            'class' => Console\Bookmark\AddController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$bookmarkRepository' => '@bookmarkRepository',
            ],
        ],
        Console\Bookmark\RefreshController::class => [
            'class' => Console\Bookmark\RefreshController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$console' => '@console'],
        ],
        Database\CentralColumns\PopulateColumnsController::class => [
            'class' => Database\CentralColumns\PopulateColumnsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$centralColumns' => '@central_columns',
            ],
        ],
        Database\CentralColumnsController::class => [
            'class' => Database\CentralColumnsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$centralColumns' => '@central_columns',
            ],
        ],
        Database\DataDictionaryController::class => [
            'class' => Database\DataDictionaryController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$transformations' => '@transformations',
                '$dbi' => '@dbi',
            ],
        ],
        Database\DesignerController::class => [
            'class' => Database\DesignerController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$databaseDesigner' => '@designer',
                '$designerCommon' => '@designer_common',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\EventsController::class => [
            'class' => Database\EventsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$events' => '@events',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\ExportController::class => [
            'class' => Database\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$export' => '@export',
                '$exportOptions' => '@export_options',
                '$pageSettings' => '@' . PageSettings::class,
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\ImportController::class => [
            'class' => Database\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$pageSettings' => '@' . PageSettings::class,
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\MultiTableQuery\QueryController::class => [
            'class' => Database\MultiTableQuery\QueryController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Database\MultiTableQuery\TablesController::class => [
            'class' => Database\MultiTableQuery\TablesController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Database\MultiTableQueryController::class => [
            'class' => Database\MultiTableQueryController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Operations\Database\CollationController::class => [
            'class' => Operations\Database\CollationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$operations' => '@operations',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Operations\DatabaseController::class => [
            'class' => Operations\DatabaseController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$operations' => '@operations',
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
                '$relation' => '@relation',
                '$relationCleanup' => '@relation_cleanup',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\PrivilegesController::class => [
            'class' => Database\PrivilegesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$privileges' => '@server_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        Database\RoutinesController::class => [
            'class' => Database\RoutinesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
                '$dbi' => '@dbi',
                '$routines' => '@routines',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\SearchController::class => [
            'class' => Database\SearchController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\SqlAutoCompleteController::class => [
            'class' => Database\SqlAutoCompleteController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$config' => '@config',
            ],
        ],
        Database\SqlController::class => [
            'class' => Database\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sqlQueryForm' => '@sql_query_form',
                '$pageSettings' => '@' . PageSettings::class,
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\SqlFormatController::class => [
            'class' => Database\SqlFormatController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Database\Structure\AddPrefixController::class => [
            'class' => Database\Structure\AddPrefixController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Database\Structure\AddPrefixTableController::class => [
            'class' => Database\Structure\AddPrefixTableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\CentralColumns\AddController::class => [
            'class' => Database\Structure\CentralColumns\AddController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\CentralColumns\MakeConsistentController::class => [
            'class' => Database\Structure\CentralColumns\MakeConsistentController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\CentralColumns\RemoveController::class => [
            'class' => Database\Structure\CentralColumns\RemoveController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\ChangePrefixFormController::class => [
            'class' => Database\Structure\ChangePrefixFormController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Database\Structure\CopyFormController::class => [
            'class' => Database\Structure\CopyFormController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Database\Structure\CopyTableController::class => [
            'class' => Database\Structure\CopyTableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$operations' => '@operations',
                '$structureController' => '@' . Database\StructureController::class,
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
            ],
        ],
        Database\Structure\CopyTableWithPrefixController::class => [
            'class' => Database\Structure\CopyTableWithPrefixController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\DropFormController::class => [
            'class' => Database\Structure\DropFormController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Database\Structure\DropTableController::class => [
            'class' => Database\Structure\DropTableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$relationCleanup' => '@relation_cleanup',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\EmptyFormController::class => [
            'class' => Database\Structure\EmptyFormController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Database\Structure\EmptyTableController::class => [
            'class' => Database\Structure\EmptyTableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$relation' => '@relation',
                '$relationCleanup' => '@relation_cleanup',
                '$operations' => '@operations',
                '$flash' => '@flash',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\FavoriteTableController::class => [
            'class' => Database\Structure\FavoriteTableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\Structure\RealRowCountController::class => [
            'class' => Database\Structure\RealRowCountController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\Structure\ReplacePrefixController::class => [
            'class' => Database\Structure\ReplacePrefixController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Database\StructureController::class,
            ],
        ],
        Database\Structure\ShowCreateController::class => [
            'class' => Database\Structure\ShowCreateController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Database\StructureController::class => [
            'class' => Database\StructureController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$replication' => '@replication',
                '$dbi' => '@dbi',
                '$trackingChecker' => '@tracking_checker',
                '$pageSettings' => '@' . PageSettings::class,
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Database\TrackingController::class => [
            'class' => Database\TrackingController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$tracking' => '@tracking',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        DatabaseController::class => [
            'class' => DatabaseController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        ErrorReportController::class => [
            'class' => ErrorReportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$errorReport' => '@error_report',
                '$errorHandler' => '@error_handler',
                '$dbi' => '@dbi',
            ],
        ],
        Export\CheckTimeOutController::class => [
            'class' => Export\CheckTimeOutController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Export\ExportController::class => [
            'class' => Export\ExportController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$export' => '@export'],
        ],
        Export\TablesController::class => [
            'class' => Export\TablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$exportController' => '@' . Database\ExportController::class,
            ],
        ],
        Export\Template\CreateController::class => [
            'class' => Export\Template\CreateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@export_template_model',
                '$relation' => '@relation',
            ],
        ],
        Export\Template\DeleteController::class => [
            'class' => Export\Template\DeleteController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@export_template_model',
                '$relation' => '@relation',
            ],
        ],
        Export\Template\LoadController::class => [
            'class' => Export\Template\LoadController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@export_template_model',
                '$relation' => '@relation',
            ],
        ],
        Export\Template\UpdateController::class => [
            'class' => Export\Template\UpdateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@export_template_model',
                '$relation' => '@relation',
            ],
        ],
        GisDataEditorController::class => [
            'class' => GisDataEditorController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        GitInfoController::class => [
            'class' => GitInfoController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$config' => '@config'],
        ],
        HomeController::class => [
            'class' => HomeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$config' => '@config',
                '$themeManager' => '@' . ThemeManager::class,
                '$dbi' => '@dbi',
                '$responseFactory' => '@' . ResponseFactory::class,
            ],
        ],
        Import\ImportController::class => [
            'class' => Import\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$import' => '@import',
                '$sql' => '@sql',
                '$dbi' => '@dbi',
                '$bookmarkRepository' => '@bookmarkRepository',
            ],
        ],
        Import\SimulateDmlController::class => [
            'class' => Import\SimulateDmlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$simulateDml' => '@import_simulate_dml',
            ],
        ],
        Import\StatusController::class => [
            'class' => Import\StatusController::class,
            'arguments' => ['$template' => '@template'],
        ],
        JavaScriptMessagesController::class => [
            'class' => JavaScriptMessagesController::class,
            'arguments' => ['@' . ResponseFactory::class],
        ],
        LicenseController::class => [
            'class' => LicenseController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        LintController::class => [
            'class' => LintController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        LogoutController::class => [
            'class' => LogoutController::class,
            'arguments' => ['@' . AuthenticationPluginFactory::class],
        ],
        NavigationController::class => [
            'class' => NavigationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$navigation' => '@navigation',
                '$relation' => '@relation',
                '$pageSettings' => '@' . PageSettings::class,
            ],
        ],
        Normalization\FirstNormalForm\FirstStepController::class => [
            'class' => Normalization\FirstNormalForm\FirstStepController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\FirstNormalForm\FourthStepController::class => [
            'class' => Normalization\FirstNormalForm\FourthStepController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\FirstNormalForm\SecondStepController::class => [
            'class' => Normalization\FirstNormalForm\SecondStepController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\FirstNormalForm\ThirdStepController::class => [
            'class' => Normalization\FirstNormalForm\ThirdStepController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\SecondNormalForm\CreateNewTablesController::class => [
            'class' => Normalization\SecondNormalForm\CreateNewTablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\SecondNormalForm\FirstStepController::class => [
            'class' => Normalization\SecondNormalForm\FirstStepController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\SecondNormalForm\NewTablesController::class => [
            'class' => Normalization\SecondNormalForm\NewTablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\ThirdNormalForm\CreateNewTablesController::class => [
            'class' => Normalization\ThirdNormalForm\CreateNewTablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\ThirdNormalForm\FirstStepController::class => [
            'class' => Normalization\ThirdNormalForm\FirstStepController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\ThirdNormalForm\NewTablesController::class => [
            'class' => Normalization\ThirdNormalForm\NewTablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\AddNewPrimaryController::class => [
            'class' => Normalization\AddNewPrimaryController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
            ],
        ],
        Normalization\CreateNewColumnController::class => [
            'class' => Normalization\CreateNewColumnController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
            ],
        ],
        Normalization\GetColumnsController::class => [
            'class' => Normalization\GetColumnsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\MainController::class => [
            'class' => Normalization\MainController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Normalization\MoveRepeatingGroup::class => [
            'class' => Normalization\MoveRepeatingGroup::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        Normalization\PartialDependenciesController::class => [
            'class' => Normalization\PartialDependenciesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        PhpInfoController::class => [
            'class' => PhpInfoController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        RecentTablesListController::class => [
            'class' => RecentTablesListController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Preferences\ExportController::class => [
            'class' => Preferences\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
                '$themeManager' => '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\FeaturesController::class => [
            'class' => Preferences\FeaturesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
                '$themeManager' => '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\ImportController::class => [
            'class' => Preferences\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
                '$themeManager' => '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\MainPanelController::class => [
            'class' => Preferences\MainPanelController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
                '$themeManager' => '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\ManageController::class => [
            'class' => Preferences\ManageController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
                '$themeManager' => '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\NavigationController::class => [
            'class' => Preferences\NavigationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
                '$themeManager' => '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\SqlController::class => [
            'class' => Preferences\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
                '$config' => '@config',
                '$themeManager' => '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        Preferences\TwoFactorController::class => [
            'class' => Preferences\TwoFactorController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$relation' => '@relation'],
        ],
        SchemaExportController::class => [
            'class' => SchemaExportController::class,
            'arguments' => ['$export' => '@export', '$response' => '@response'],
        ],
        Server\BinlogController::class => [
            'class' => Server\BinlogController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Server\CollationsController::class => [
            'class' => Server\CollationsController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Server\Databases\CreateController::class => [
            'class' => Server\Databases\CreateController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Server\Databases\DestroyController::class => [
            'class' => Server\Databases\DestroyController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$transformations' => '@transformations',
                '$relationCleanup' => '@relation_cleanup',
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
            ],
        ],
        Server\DatabasesController::class => [
            'class' => Server\DatabasesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
            ],
        ],
        Server\EnginesController::class => [
            'class' => Server\EnginesController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Server\ExportController::class => [
            'class' => Server\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$export' => '@export_options',
                '$dbi' => '@dbi',
                '$pageSettings' => '@' . PageSettings::class,
            ],
        ],
        Server\ImportController::class => [
            'class' => Server\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$pageSettings' => '@' . PageSettings::class,
            ],
        ],
        Server\PluginsController::class => [
            'class' => Server\PluginsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$plugins' => '@server_plugins',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Privileges\AccountLockController::class => [
            'class' => Server\Privileges\AccountLockController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$accountLocking' => '@server_privileges_account_locking',
            ],
        ],
        Server\Privileges\AccountUnlockController::class => [
            'class' => Server\Privileges\AccountUnlockController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$accountLocking' => '@server_privileges_account_locking',
            ],
        ],
        Server\PrivilegesController::class => [
            'class' => Server\PrivilegesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
            ],
        ],
        Server\ReplicationController::class => [
            'class' => Server\ReplicationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$replicationGui' => '@replication_gui',
                '$dbi' => '@dbi',
            ],
        ],
        Server\ShowEngineController::class => [
            'class' => Server\ShowEngineController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Server\SqlController::class => [
            'class' => Server\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sqlQueryForm' => '@sql_query_form',
                '$dbi' => '@dbi',
                '$pageSettings' => '@' . PageSettings::class,
            ],
        ],
        Server\UserGroupsController::class => [
            'class' => Server\UserGroupsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Server\UserGroupsFormController::class => [
            'class' => Server\UserGroupsFormController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\AdvisorController::class => [
            'class' => Server\Status\AdvisorController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$advisor' => '@advisor',
            ],
        ],
        Server\Status\Monitor\ChartingDataController::class => [
            'class' => Server\Status\Monitor\ChartingDataController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Monitor\GeneralLogController::class => [
            'class' => Server\Status\Monitor\GeneralLogController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Monitor\LogVarsController::class => [
            'class' => Server\Status\Monitor\LogVarsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Monitor\QueryAnalyzerController::class => [
            'class' => Server\Status\Monitor\QueryAnalyzerController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Monitor\SlowLogController::class => [
            'class' => Server\Status\Monitor\SlowLogController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\MonitorController::class => [
            'class' => Server\Status\MonitorController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Processes\KillController::class => [
            'class' => Server\Status\Processes\KillController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\Processes\RefreshController::class => [
            'class' => Server\Status\Processes\RefreshController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$processes' => '@status_processes',
            ],
        ],
        Server\Status\ProcessesController::class => [
            'class' => Server\Status\ProcessesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
                '$processes' => '@status_processes',
            ],
        ],
        Server\Status\QueriesController::class => [
            'class' => Server\Status\QueriesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\StatusController::class => [
            'class' => Server\Status\StatusController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$replicationGui' => '@replication_gui',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Status\VariablesController::class => [
            'class' => Server\Status\VariablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        Server\Variables\GetVariableController::class => [
            'class' => Server\Variables\GetVariableController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Server\Variables\SetVariableController::class => [
            'class' => Server\Variables\SetVariableController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Server\VariablesController::class => [
            'class' => Server\VariablesController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Sql\ColumnPreferencesController::class => [
            'class' => Sql\ColumnPreferencesController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Sql\DefaultForeignKeyCheckValueController::class => [
            'class' => Sql\DefaultForeignKeyCheckValueController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Sql\EnumValuesController::class => [
            'class' => Sql\EnumValuesController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$sql' => '@sql'],
        ],
        Sql\RelationalValuesController::class => [
            'class' => Sql\RelationalValuesController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$sql' => '@sql'],
        ],
        Sql\SetValuesController::class => [
            'class' => Sql\SetValuesController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$sql' => '@sql'],
        ],
        Sql\SqlController::class => [
            'class' => Sql\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sql' => '@sql',
                '$dbi' => '@dbi',
                '$pageSettings' => '@' . PageSettings::class,
                '$bookmarkRepository' => '@bookmarkRepository',
            ],
        ],
        Table\AddFieldController::class => [
            'class' => Table\AddFieldController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$transformations' => '@transformations',
                '$config' => '@config',
                '$dbi' => '@dbi',
                '$columnsDefinition' => '@table_columns_definition',
                '$dbTableExists' => '@' . DbTableExists::class,
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
            ],
        ],
        Table\ChangeController::class => [
            'class' => Table\ChangeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$insertEdit' => '@insert_edit',
                '$relation' => '@relation',
                '$pageSettings' => '@' . PageSettings::class,
                '$dbTableExists' => '@' . DbTableExists::class,
                '$config' => '@config',
            ],
        ],
        Table\ChangeRowsController::class => [
            'class' => Table\ChangeRowsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$changeController' => '@' . Table\ChangeController::class,
            ],
        ],
        Table\ChartController::class => [
            'class' => Table\ChartController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\CreateController::class => [
            'class' => Table\CreateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$transformations' => '@transformations',
                '$config' => '@config',
                '$dbi' => '@dbi',
                '$columnsDefinition' => '@table_columns_definition',
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
            ],
        ],
        Table\DeleteConfirmController::class => [
            'class' => Table\DeleteConfirmController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\DeleteRowsController::class => [
            'class' => Table\DeleteRowsController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Table\DropColumnConfirmationController::class => [
            'class' => Table\DropColumnConfirmationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\DropColumnController::class => [
            'class' => Table\DropColumnController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$flash' => '@flash',
                '$relationCleanup' => '@relation_cleanup',
            ],
        ],
        Table\ExportController::class => [
            'class' => Table\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$export' => '@export_options',
                '$pageSettings' => '@' . PageSettings::class,
            ],
        ],
        Table\ExportRowsController::class => [
            'class' => Table\ExportRowsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$exportController' => '@' . Table\ExportController::class,
            ],
        ],
        Table\FindReplaceController::class => [
            'class' => Table\FindReplaceController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\GetFieldController::class => [
            'class' => Table\GetFieldController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Table\GisVisualizationController::class => [
            'class' => Table\GisVisualizationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\ImportController::class => [
            'class' => Table\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$pageSettings' => '@' . PageSettings::class,
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\IndexesController::class => [
            'class' => Table\IndexesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$indexes' => '@table_indexes',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\IndexRenameController::class => [
            'class' => Table\IndexRenameController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$indexes' => '@table_indexes',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\Maintenance\AnalyzeController::class => [
            'class' => Table\Maintenance\AnalyzeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@table_maintenance',
                '$config' => '@config',
            ],
        ],
        Table\Maintenance\CheckController::class => [
            'class' => Table\Maintenance\CheckController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@table_maintenance',
                '$config' => '@config',
            ],
        ],
        Table\Maintenance\ChecksumController::class => [
            'class' => Table\Maintenance\ChecksumController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@table_maintenance',
                '$config' => '@config',
            ],
        ],
        Table\Maintenance\OptimizeController::class => [
            'class' => Table\Maintenance\OptimizeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@table_maintenance',
                '$config' => '@config',
            ],
        ],
        Table\Maintenance\RepairController::class => [
            'class' => Table\Maintenance\RepairController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@table_maintenance',
                '$config' => '@config',
            ],
        ],
        Table\Partition\AnalyzeController::class => [
            'class' => Table\Partition\AnalyzeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\CheckController::class => [
            'class' => Table\Partition\CheckController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\DropController::class => [
            'class' => Table\Partition\DropController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\OptimizeController::class => [
            'class' => Table\Partition\OptimizeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\RebuildController::class => [
            'class' => Table\Partition\RebuildController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\RepairController::class => [
            'class' => Table\Partition\RepairController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@partitioning_maintenance',
            ],
        ],
        Table\Partition\TruncateController::class => [
            'class' => Table\Partition\TruncateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@partitioning_maintenance',
            ],
        ],
        Operations\TableController::class => [
            'class' => Operations\TableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$operations' => '@operations',
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
                '$relation' => '@relation',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\PrivilegesController::class => [
            'class' => Table\PrivilegesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$privileges' => '@server_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        Table\RecentFavoriteController::class => [
            'class' => Table\RecentFavoriteController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Table\RelationController::class => [
            'class' => Table\RelationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        Table\ReplaceController::class => [
            'class' => Table\ReplaceController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$insertEdit' => '@insert_edit',
                '$transformations' => '@transformations',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
                '$sqlController' => '@' . Sql\SqlController::class,
                '$databaseSqlController' => '@' . Database\SqlController::class,
                '$changeController' => '@' . Table\ChangeController::class,
                '$tableSqlController' => '@' . Table\SqlController::class,
            ],
        ],
        Table\SearchController::class => [
            'class' => Table\SearchController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$search' => '@table_search',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\SqlController::class => [
            'class' => Table\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sqlQueryForm' => '@sql_query_form',
                '$pageSettings' => '@' . PageSettings::class,
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\Structure\AddIndexController::class => [
            'class' => Table\Structure\AddIndexController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$structureController' => '@' . Table\StructureController::class,
                '$indexes' => '@table_indexes',
            ],
        ],
        Table\Structure\AddKeyController::class => [
            'class' => Table\Structure\AddKeyController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sqlController' => '@' . Sql\SqlController::class,
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\BrowseController::class => [
            'class' => Table\Structure\BrowseController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$sql' => '@sql'],
        ],
        Table\Structure\CentralColumnsAddController::class => [
            'class' => Table\Structure\CentralColumnsAddController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$centralColumns' => '@central_columns',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\CentralColumnsRemoveController::class => [
            'class' => Table\Structure\CentralColumnsRemoveController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$centralColumns' => '@central_columns',
                '$structureController' => '@' . Table\StructureController::class,
            ],
        ],
        Table\Structure\ChangeController::class => [
            'class' => Table\Structure\ChangeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$columnsDefinition' => '@table_columns_definition',
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
            ],
        ],
        Table\Structure\FulltextController::class => [
            'class' => Table\Structure\FulltextController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$structureController' => '@' . Table\StructureController::class,
                '$indexes' => '@table_indexes',
            ],
        ],
        Table\Structure\MoveColumnsController::class => [
            'class' => Table\Structure\MoveColumnsController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        Table\Structure\PartitioningController::class => [
            'class' => Table\Structure\PartitioningController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$createAddField' => '@create_add_field',
                '$structureController' => '@' . Table\StructureController::class,
                '$pageSettings' => '@' . PageSettings::class,
            ],
        ],
        Table\Structure\PrimaryController::class => [
            'class' => Table\Structure\PrimaryController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Table\StructureController::class,
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\Structure\ReservedWordCheckController::class => [
            'class' => Table\Structure\ReservedWordCheckController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template'],
        ],
        Table\Structure\SaveController::class => [
            'class' => Table\Structure\SaveController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$transformations' => '@transformations',
                '$dbi' => '@dbi',
                '$structureController' => '@' . Table\StructureController::class,
                '$userPrivilegesFactory' => '@' . UserPrivilegesFactory::class,
            ],
        ],
        Table\Structure\SpatialController::class => [
            'class' => Table\Structure\SpatialController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$structureController' => '@' . Table\StructureController::class,
                '$indexes' => '@table_indexes',
            ],
        ],
        Table\Structure\UniqueController::class => [
            'class' => Table\Structure\UniqueController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$structureController' => '@' . Table\StructureController::class,
                '$indexes' => '@table_indexes',
            ],
        ],
        Table\StructureController::class => [
            'class' => Table\StructureController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$transformations' => '@transformations',
                '$dbi' => '@dbi',
                '$pageSettings' => '@' . PageSettings::class,
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\TrackingController::class => [
            'class' => Table\TrackingController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$tracking' => '@tracking',
                '$trackingChecker' => '@tracking_checker',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Triggers\IndexController::class => [
            'class' => Triggers\IndexController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$triggers' => '@triggers',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Table\ZoomSearchController::class => [
            'class' => Table\ZoomSearchController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$search' => '@table_search',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        TableController::class => [
            'class' => TableController::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        ThemesController::class => [
            'class' => ThemesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$themeManager' => '@' . PhpMyAdmin\Theme\ThemeManager::class,
            ],
        ],
        ThemeSetController::class => [
            'class' => ThemeSetController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$themeManager' => '@' . PhpMyAdmin\Theme\ThemeManager::class,
                '$userPreferences' => '@user_preferences',
            ],
        ],
        Transformation\OverviewController::class => [
            'class' => Transformation\OverviewController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$transformations' => '@transformations',
            ],
        ],
        Transformation\WrapperController::class => [
            'class' => Transformation\WrapperController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$transformations' => '@transformations',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        UserPasswordController::class => [
            'class' => UserPasswordController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPassword' => '@user_password',
                '$dbi' => '@dbi',
            ],
        ],
        VersionCheckController::class => [
            'class' => VersionCheckController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$versionInformation' => '@version_information',
            ],
        ],
        View\CreateController::class => [
            'class' => View\CreateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
        Operations\ViewController::class => [
            'class' => Operations\ViewController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$operations' => '@operations',
                '$dbi' => '@dbi',
                '$dbTableExists' => '@' . DbTableExists::class,
            ],
        ],
    ],
];
