<?php

declare(strict_types=1);

use PhpMyAdmin\Advisory\Advisor;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
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
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Database\Designer\Common;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Error\ErrorReport;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Partitioning\Maintenance;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Replication\Replication;
use PhpMyAdmin\Replication\ReplicationGui;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Server\Privileges\AccountLocking;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Table\TableMover;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\Tracking\Tracking;
use PhpMyAdmin\Tracking\TrackingChecker;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UserPassword;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\VersionInformation;

return [
    BrowseForeignersController::class => [
        'class' => BrowseForeignersController::class,
        'arguments' => [ResponseRenderer::class, BrowseForeigners::class, Relation::class],
    ],
    ChangeLogController::class => [
        'class' => ChangeLogController::class,
        'arguments' => [ResponseRenderer::class, Config::class, ResponseFactory::class, Template::class],
    ],
    CheckRelationsController::class => [
        'class' => CheckRelationsController::class,
        'arguments' => [ResponseRenderer::class, Relation::class, Config::class],
    ],
    CollationConnectionController::class => [
        'class' => CollationConnectionController::class,
        'arguments' => [ResponseRenderer::class, UserPreferencesHandler::class],
    ],
    ColumnController::class => [
        'class' => ColumnController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class],
    ],
    UpdateNavWidthConfigController::class => [
        'class' => UpdateNavWidthConfigController::class,
        'arguments' => [ResponseRenderer::class, UserPreferencesHandler::class],
    ],
    Console\Bookmark\AddController::class => [
        'class' => Console\Bookmark\AddController::class,
        'arguments' => [ResponseRenderer::class, BookmarkRepository::class, Config::class],
    ],
    Console\Bookmark\RefreshController::class => [
        'class' => Console\Bookmark\RefreshController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Console\Console::class],
    ],
    Console\UpdateConfigController::class => [
        'class' => Console\UpdateConfigController::class,
        'arguments' => [ResponseRenderer::class, UserPreferencesHandler::class],
    ],
    Database\CentralColumns\PopulateColumnsController::class => [
        'class' => Database\CentralColumns\PopulateColumnsController::class,
        'arguments' => [ResponseRenderer::class, CentralColumns::class],
    ],
    Database\CentralColumnsController::class => [
        'class' => Database\CentralColumnsController::class,
        'arguments' => [ResponseRenderer::class, CentralColumns::class, Config::class],
    ],
    Database\DataDictionaryController::class => [
        'class' => Database\DataDictionaryController::class,
        'arguments' => [ResponseRenderer::class, Relation::class, Transformations::class, DatabaseInterface::class],
    ],
    Database\DesignerController::class => [
        'class' => Database\DesignerController::class,
        'arguments' => [ResponseRenderer::class, Template::class, Designer::class, Common::class, DbTableExists::class],
    ],
    Database\EventsController::class => [
        'class' => Database\EventsController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Events::class,
            DatabaseInterface::class,
            DbTableExists::class,
        ],
    ],
    Database\ExportController::class => [
        'class' => Database\ExportController::class,
        'arguments' => [
            ResponseRenderer::class,
            \PhpMyAdmin\Export\Export::class,
            Options::class,
            PageSettings::class,
            DbTableExists::class,
        ],
    ],
    Database\ImportController::class => [
        'class' => Database\ImportController::class,
        'arguments' => [
            ResponseRenderer::class,
            DatabaseInterface::class,
            PageSettings::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Database\MultiTableQuery\QueryController::class => [
        'class' => Database\MultiTableQuery\QueryController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Sql::class],
    ],
    Database\MultiTableQuery\TablesController::class => [
        'class' => Database\MultiTableQuery\TablesController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class],
    ],
    Database\MultiTableQueryController::class => [
        'class' => Database\MultiTableQueryController::class,
        'arguments' => [ResponseRenderer::class, Template::class, DatabaseInterface::class],
    ],
    Operations\Database\CollationController::class => [
        'class' => Operations\Database\CollationController::class,
        'arguments' => [
            ResponseRenderer::class,
            \PhpMyAdmin\Operations::class,
            DatabaseInterface::class,
            DbTableExists::class,
        ],
    ],
    Operations\DatabaseController::class => [
        'class' => Operations\DatabaseController::class,
        'arguments' => [
            ResponseRenderer::class,
            \PhpMyAdmin\Operations::class,
            UserPrivilegesFactory::class,
            Relation::class,
            RelationCleanup::class,
            DatabaseInterface::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Database\PrivilegesController::class => [
        'class' => Database\PrivilegesController::class,
        'arguments' => [ResponseRenderer::class, Privileges::class, DatabaseInterface::class, Config::class],
    ],
    Database\RoutinesController::class => [
        'class' => Database\RoutinesController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            UserPrivilegesFactory::class,
            DatabaseInterface::class,
            Routines::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Database\SearchController::class => [
        'class' => Database\SearchController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            DatabaseInterface::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Database\SqlAutoCompleteController::class => [
        'class' => Database\SqlAutoCompleteController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, Config::class],
    ],
    Database\SqlController::class => [
        'class' => Database\SqlController::class,
        'arguments' => [ResponseRenderer::class, SqlQueryForm::class, PageSettings::class, DbTableExists::class],
    ],
    Database\SqlFormatController::class => [
        'class' => Database\SqlFormatController::class,
        'arguments' => [ResponseRenderer::class],
    ],
    Database\Structure\AddPrefixController::class => [
        'class' => Database\Structure\AddPrefixController::class,
        'arguments' => [ResponseRenderer::class, ResponseFactory::class, Template::class],
    ],
    Database\Structure\AddPrefixTableController::class => [
        'class' => Database\Structure\AddPrefixTableController::class,
        'arguments' => [DatabaseInterface::class, Database\StructureController::class],
    ],
    Database\Structure\CentralColumns\AddController::class => [
        'class' => Database\Structure\CentralColumns\AddController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, Database\StructureController::class],
    ],
    Database\Structure\CentralColumns\MakeConsistentController::class => [
        'class' => Database\Structure\CentralColumns\MakeConsistentController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, Database\StructureController::class],
    ],
    Database\Structure\CentralColumns\RemoveController::class => [
        'class' => Database\Structure\CentralColumns\RemoveController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, Database\StructureController::class],
    ],
    Database\Structure\ChangePrefixFormController::class => [
        'class' => Database\Structure\ChangePrefixFormController::class,
        'arguments' => [ResponseRenderer::class, ResponseFactory::class, Template::class],
    ],
    Database\Structure\CopyFormController::class => [
        'class' => Database\Structure\CopyFormController::class,
        'arguments' => [ResponseRenderer::class, ResponseFactory::class, Template::class],
    ],
    Database\Structure\CopyTableController::class => [
        'class' => Database\Structure\CopyTableController::class,
        'arguments' => [
            \PhpMyAdmin\Operations::class,
            Database\StructureController::class,
            UserPrivilegesFactory::class,
            TableMover::class,
        ],
    ],
    Database\Structure\CopyTableWithPrefixController::class => [
        'class' => Database\Structure\CopyTableWithPrefixController::class,
        'arguments' => [Database\StructureController::class, TableMover::class],
    ],
    Database\Structure\DropFormController::class => [
        'class' => Database\Structure\DropFormController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class],
    ],
    Database\Structure\DropTableController::class => [
        'class' => Database\Structure\DropTableController::class,
        'arguments' => [DatabaseInterface::class, RelationCleanup::class, Database\StructureController::class],
    ],
    Database\Structure\EmptyFormController::class => [
        'class' => Database\Structure\EmptyFormController::class,
        'arguments' => [ResponseRenderer::class],
    ],
    Database\Structure\EmptyTableController::class => [
        'class' => Database\Structure\EmptyTableController::class,
        'arguments' => [
            ResponseRenderer::class,
            DatabaseInterface::class,
            FlashMessenger::class,
            Database\StructureController::class,
            \PhpMyAdmin\Sql::class,
        ],
    ],
    Database\Structure\FavoriteTableController::class => [
        'class' => Database\Structure\FavoriteTableController::class,
        'arguments' => [ResponseRenderer::class, Template::class, DbTableExists::class, Config::class],
    ],
    Database\Structure\RealRowCountController::class => [
        'class' => Database\Structure\RealRowCountController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, DbTableExists::class],
    ],
    Database\Structure\ReplacePrefixController::class => [
        'class' => Database\Structure\ReplacePrefixController::class,
        'arguments' => [DatabaseInterface::class, ResponseFactory::class, FlashMessenger::class],
    ],
    Database\Structure\ShowCreateController::class => [
        'class' => Database\Structure\ShowCreateController::class,
        'arguments' => [ResponseRenderer::class, Template::class, DatabaseInterface::class],
    ],
    Database\StructureController::class => [
        'class' => Database\StructureController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Relation::class,
            Replication::class,
            DatabaseInterface::class,
            TrackingChecker::class,
            PageSettings::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Database\TrackingController::class => [
        'class' => Database\TrackingController::class,
        'arguments' => [
            ResponseRenderer::class,
            Tracking::class,
            DatabaseInterface::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    DatabaseController::class => [
        'class' => DatabaseController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class],
    ],
    ErrorReportController::class => [
        'class' => ErrorReportController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            ErrorReport::class,
            ErrorHandler::class,
            DatabaseInterface::class,
            Config::class,
        ],
    ],
    Export\CheckTimeOutController::class => [
        'class' => Export\CheckTimeOutController::class,
        'arguments' => [ResponseRenderer::class],
    ],
    Export\ExportController::class => [
        'class' => Export\ExportController::class,
        'arguments' => [
            ResponseRenderer::class,
            \PhpMyAdmin\Export\Export::class,
            ResponseFactory::class,
            Config::class,
            UserPreferencesHandler::class,
        ],
    ],
    Export\TablesController::class => [
        'class' => Export\TablesController::class,
        'arguments' => [ResponseRenderer::class, Database\ExportController::class],
    ],
    Export\Template\CreateController::class => [
        'class' => Export\Template\CreateController::class,
        'arguments' => [ResponseRenderer::class, Template::class, TemplateModel::class, Relation::class, Config::class],
    ],
    Export\Template\DeleteController::class => [
        'class' => Export\Template\DeleteController::class,
        'arguments' => [ResponseRenderer::class, TemplateModel::class, Relation::class, Config::class],
    ],
    Export\Template\LoadController::class => [
        'class' => Export\Template\LoadController::class,
        'arguments' => [ResponseRenderer::class, TemplateModel::class, Relation::class, Config::class],
    ],
    Export\Template\UpdateController::class => [
        'class' => Export\Template\UpdateController::class,
        'arguments' => [ResponseRenderer::class, TemplateModel::class, Relation::class, Config::class],
    ],
    GisDataEditorController::class => [
        'class' => GisDataEditorController::class,
        'arguments' => [ResponseRenderer::class, Template::class],
    ],
    GitInfoController::class => [
        'class' => GitInfoController::class,
        'arguments' => [ResponseRenderer::class, Config::class],
    ],
    HomeController::class => [
        'class' => HomeController::class,
        'arguments' => [
            ResponseRenderer::class,
            Config::class,
            ThemeManager::class,
            DatabaseInterface::class,
            ResponseFactory::class,
        ],
    ],
    Import\ImportController::class => [
        'class' => Import\ImportController::class,
        'arguments' => [
            ResponseRenderer::class,
            \PhpMyAdmin\Import\Import::class,
            \PhpMyAdmin\Sql::class,
            DatabaseInterface::class,
            BookmarkRepository::class,
            Config::class,
        ],
    ],
    Import\SimulateDmlController::class => [
        'class' => Import\SimulateDmlController::class,
        'arguments' => [ResponseRenderer::class, SimulateDml::class],
    ],
    Import\StatusController::class => ['class' => Import\StatusController::class, 'arguments' => [Template::class]],
    JavaScriptMessagesController::class => [
        'class' => JavaScriptMessagesController::class,
        'arguments' => [ResponseFactory::class],
    ],
    LicenseController::class => [
        'class' => LicenseController::class,
        'arguments' => [ResponseRenderer::class, ResponseFactory::class],
    ],
    LintController::class => ['class' => LintController::class, 'arguments' => [ResponseFactory::class]],
    LogoutController::class => [
        'class' => LogoutController::class,
        'arguments' => [AuthenticationPluginFactory::class],
    ],
    NavigationController::class => [
        'class' => NavigationController::class,
        'arguments' => [ResponseRenderer::class, Navigation::class, Relation::class, PageSettings::class],
    ],
    Normalization\FirstNormalForm\FirstStepController::class => [
        'class' => Normalization\FirstNormalForm\FirstStepController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\FirstNormalForm\FourthStepController::class => [
        'class' => Normalization\FirstNormalForm\FourthStepController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\FirstNormalForm\SecondStepController::class => [
        'class' => Normalization\FirstNormalForm\SecondStepController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\FirstNormalForm\ThirdStepController::class => [
        'class' => Normalization\FirstNormalForm\ThirdStepController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\SecondNormalForm\CreateNewTablesController::class => [
        'class' => Normalization\SecondNormalForm\CreateNewTablesController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\SecondNormalForm\FirstStepController::class => [
        'class' => Normalization\SecondNormalForm\FirstStepController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\SecondNormalForm\NewTablesController::class => [
        'class' => Normalization\SecondNormalForm\NewTablesController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\ThirdNormalForm\CreateNewTablesController::class => [
        'class' => Normalization\ThirdNormalForm\CreateNewTablesController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\ThirdNormalForm\FirstStepController::class => [
        'class' => Normalization\ThirdNormalForm\FirstStepController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\ThirdNormalForm\NewTablesController::class => [
        'class' => Normalization\ThirdNormalForm\NewTablesController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\AddNewPrimaryController::class => [
        'class' => Normalization\AddNewPrimaryController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class, UserPrivilegesFactory::class],
    ],
    Normalization\CreateNewColumnController::class => [
        'class' => Normalization\CreateNewColumnController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class, UserPrivilegesFactory::class],
    ],
    Normalization\GetColumnsController::class => [
        'class' => Normalization\GetColumnsController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\MainController::class => [
        'class' => Normalization\MainController::class,
        'arguments' => [ResponseRenderer::class],
    ],
    Normalization\MoveRepeatingGroup::class => [
        'class' => Normalization\MoveRepeatingGroup::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    Normalization\PartialDependenciesController::class => [
        'class' => Normalization\PartialDependenciesController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Normalization::class],
    ],
    PhpInfoController::class => [
        'class' => PhpInfoController::class,
        'arguments' => [ResponseRenderer::class, ResponseFactory::class, Config::class],
    ],
    Preferences\ExportController::class => [
        'class' => Preferences\ExportController::class,
        'arguments' => [
            ResponseRenderer::class,
            UserPreferences::class,
            Relation::class,
            Config::class,
            UserPreferencesHandler::class,
        ],
    ],
    Preferences\FeaturesController::class => [
        'class' => Preferences\FeaturesController::class,
        'arguments' => [
            ResponseRenderer::class,
            UserPreferences::class,
            Relation::class,
            Config::class,
            UserPreferencesHandler::class,
        ],
    ],
    Preferences\ImportController::class => [
        'class' => Preferences\ImportController::class,
        'arguments' => [
            ResponseRenderer::class,
            UserPreferences::class,
            Relation::class,
            Config::class,
            UserPreferencesHandler::class,
        ],
    ],
    Preferences\MainPanelController::class => [
        'class' => Preferences\MainPanelController::class,
        'arguments' => [
            ResponseRenderer::class,
            UserPreferences::class,
            Relation::class,
            Config::class,
            UserPreferencesHandler::class,
        ],
    ],
    Preferences\ManageController::class => [
        'class' => Preferences\ManageController::class,
        'arguments' => [
            ResponseRenderer::class,
            UserPreferences::class,
            Relation::class,
            Config::class,
            ThemeManager::class,
            ResponseFactory::class,
            UserPreferencesHandler::class,
        ],
    ],
    Preferences\NavigationController::class => [
        'class' => Preferences\NavigationController::class,
        'arguments' => [
            ResponseRenderer::class,
            UserPreferences::class,
            Relation::class,
            Config::class,
            UserPreferencesHandler::class,
        ],
    ],
    Preferences\SqlController::class => [
        'class' => Preferences\SqlController::class,
        'arguments' => [
            ResponseRenderer::class,
            UserPreferences::class,
            Relation::class,
            Config::class,
            UserPreferencesHandler::class,
        ],
    ],
    Preferences\TwoFactorController::class => [
        'class' => Preferences\TwoFactorController::class,
        'arguments' => [ResponseRenderer::class, Relation::class, Config::class],
    ],
    SchemaExportController::class => [
        'class' => SchemaExportController::class,
        'arguments' => [\PhpMyAdmin\Export\Export::class, ResponseRenderer::class, ResponseFactory::class],
    ],
    Server\BinlogController::class => [
        'class' => Server\BinlogController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, Config::class],
    ],
    Server\CollationsController::class => [
        'class' => Server\CollationsController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, Config::class],
    ],
    Server\Databases\CreateController::class => [
        'class' => Server\Databases\CreateController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, Config::class],
    ],
    Server\Databases\DestroyController::class => [
        'class' => Server\Databases\DestroyController::class,
        'arguments' => [
            ResponseRenderer::class,
            DatabaseInterface::class,
            Transformations::class,
            RelationCleanup::class,
            UserPrivilegesFactory::class,
            Config::class,
        ],
    ],
    Server\DatabasesController::class => [
        'class' => Server\DatabasesController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, UserPrivilegesFactory::class, Config::class],
    ],
    Server\EnginesController::class => [
        'class' => Server\EnginesController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class],
    ],
    Server\ExportController::class => [
        'class' => Server\ExportController::class,
        'arguments' => [ResponseRenderer::class, Options::class, DatabaseInterface::class, PageSettings::class],
    ],
    Server\ImportController::class => [
        'class' => Server\ImportController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, PageSettings::class, Config::class],
    ],
    Server\PluginsController::class => [
        'class' => Server\PluginsController::class,
        'arguments' => [ResponseRenderer::class, Plugins::class, DatabaseInterface::class],
    ],
    Server\Privileges\AccountLockController::class => [
        'class' => Server\Privileges\AccountLockController::class,
        'arguments' => [ResponseRenderer::class, AccountLocking::class],
    ],
    Server\Privileges\AccountUnlockController::class => [
        'class' => Server\Privileges\AccountUnlockController::class,
        'arguments' => [ResponseRenderer::class, AccountLocking::class],
    ],
    Server\PrivilegesController::class => [
        'class' => Server\PrivilegesController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Relation::class,
            DatabaseInterface::class,
            UserPrivilegesFactory::class,
            Config::class,
        ],
    ],
    Server\ReplicationController::class => [
        'class' => Server\ReplicationController::class,
        'arguments' => [ResponseRenderer::class, ReplicationGui::class, DatabaseInterface::class],
    ],
    Server\ShowEngineController::class => [
        'class' => Server\ShowEngineController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class],
    ],
    Server\SqlController::class => [
        'class' => Server\SqlController::class,
        'arguments' => [ResponseRenderer::class, SqlQueryForm::class, DatabaseInterface::class, PageSettings::class],
    ],
    Server\UserGroupsController::class => [
        'class' => Server\UserGroupsController::class,
        'arguments' => [ResponseRenderer::class, Relation::class, DatabaseInterface::class],
    ],
    Server\UserGroupsFormController::class => [
        'class' => Server\UserGroupsFormController::class,
        'arguments' => [ResponseRenderer::class, Template::class, Relation::class, DatabaseInterface::class],
    ],
    Server\Status\AdvisorController::class => [
        'class' => Server\Status\AdvisorController::class,
        'arguments' => [ResponseRenderer::class, Template::class, Data::class, Advisor::class],
    ],
    Server\Status\Monitor\ChartingDataController::class => [
        'class' => Server\Status\Monitor\ChartingDataController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Data::class,
            Monitor::class,
            DatabaseInterface::class,
        ],
    ],
    Server\Status\Monitor\GeneralLogController::class => [
        'class' => Server\Status\Monitor\GeneralLogController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Data::class,
            Monitor::class,
            DatabaseInterface::class,
        ],
    ],
    Server\Status\Monitor\LogVarsController::class => [
        'class' => Server\Status\Monitor\LogVarsController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Data::class,
            Monitor::class,
            DatabaseInterface::class,
        ],
    ],
    Server\Status\Monitor\QueryAnalyzerController::class => [
        'class' => Server\Status\Monitor\QueryAnalyzerController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Data::class,
            Monitor::class,
            DatabaseInterface::class,
        ],
    ],
    Server\Status\Monitor\SlowLogController::class => [
        'class' => Server\Status\Monitor\SlowLogController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Data::class,
            Monitor::class,
            DatabaseInterface::class,
        ],
    ],
    Server\Status\MonitorController::class => [
        'class' => Server\Status\MonitorController::class,
        'arguments' => [ResponseRenderer::class, Template::class, Data::class, DatabaseInterface::class],
    ],
    Server\Status\Processes\KillController::class => [
        'class' => Server\Status\Processes\KillController::class,
        'arguments' => [ResponseRenderer::class, Template::class, Data::class, DatabaseInterface::class],
    ],
    Server\Status\Processes\RefreshController::class => [
        'class' => Server\Status\Processes\RefreshController::class,
        'arguments' => [ResponseRenderer::class, Template::class, Data::class, Processes::class],
    ],
    Server\Status\ProcessesController::class => [
        'class' => Server\Status\ProcessesController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Data::class,
            DatabaseInterface::class,
            Processes::class,
        ],
    ],
    Server\Status\QueriesController::class => [
        'class' => Server\Status\QueriesController::class,
        'arguments' => [ResponseRenderer::class, Template::class, Data::class, DatabaseInterface::class],
    ],
    Server\Status\StatusController::class => [
        'class' => Server\Status\StatusController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Data::class,
            ReplicationGui::class,
            DatabaseInterface::class,
        ],
    ],
    Server\Status\VariablesController::class => [
        'class' => Server\Status\VariablesController::class,
        'arguments' => [ResponseRenderer::class, Template::class, Data::class, DatabaseInterface::class],
    ],
    Server\Variables\GetVariableController::class => [
        'class' => Server\Variables\GetVariableController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class],
    ],
    Server\Variables\SetVariableController::class => [
        'class' => Server\Variables\SetVariableController::class,
        'arguments' => [ResponseRenderer::class, Template::class, DatabaseInterface::class],
    ],
    Server\VariablesController::class => [
        'class' => Server\VariablesController::class,
        'arguments' => [ResponseRenderer::class, Template::class, DatabaseInterface::class],
    ],
    Setup\MainController::class => [
        'class' => Setup\MainController::class,
        'arguments' => [ResponseFactory::class, ResponseRenderer::class, Template::class, Config::class],
    ],
    Setup\ShowConfigController::class => [
        'class' => Setup\ShowConfigController::class,
        'arguments' => [ResponseFactory::class, Template::class, Config::class],
    ],
    Setup\ValidateController::class => [
        'class' => Setup\ValidateController::class,
        'arguments' => [ResponseFactory::class, Template::class, Config::class],
    ],
    Sql\ColumnPreferencesController::class => [
        'class' => Sql\ColumnPreferencesController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class],
    ],
    Sql\DefaultForeignKeyCheckValueController::class => [
        'class' => Sql\DefaultForeignKeyCheckValueController::class,
        'arguments' => [ResponseRenderer::class],
    ],
    Sql\EnumValuesController::class => [
        'class' => Sql\EnumValuesController::class,
        'arguments' => [ResponseRenderer::class, Template::class, \PhpMyAdmin\Sql::class],
    ],
    Sql\RelationalValuesController::class => [
        'class' => Sql\RelationalValuesController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Sql::class],
    ],
    Sql\SetValuesController::class => [
        'class' => Sql\SetValuesController::class,
        'arguments' => [ResponseRenderer::class, Template::class, \PhpMyAdmin\Sql::class],
    ],
    Sql\SqlController::class => [
        'class' => Sql\SqlController::class,
        'arguments' => [
            ResponseRenderer::class,
            \PhpMyAdmin\Sql::class,
            PageSettings::class,
            BookmarkRepository::class,
            Config::class,
        ],
    ],
    Table\AddFieldController::class => [
        'class' => Table\AddFieldController::class,
        'arguments' => [
            ResponseRenderer::class,
            Transformations::class,
            Config::class,
            DatabaseInterface::class,
            ColumnsDefinition::class,
            DbTableExists::class,
            UserPrivilegesFactory::class,
        ],
    ],
    Table\ChangeController::class => [
        'class' => Table\ChangeController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            InsertEdit::class,
            Relation::class,
            PageSettings::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Table\ChangeRowsController::class => [
        'class' => Table\ChangeRowsController::class,
        'arguments' => [ResponseRenderer::class, Table\ChangeController::class],
    ],
    Table\ChartController::class => [
        'class' => Table\ChartController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, DbTableExists::class, Config::class],
    ],
    Table\CreateController::class => [
        'class' => Table\CreateController::class,
        'arguments' => [
            ResponseRenderer::class,
            Transformations::class,
            Config::class,
            DatabaseInterface::class,
            ColumnsDefinition::class,
            UserPrivilegesFactory::class,
        ],
    ],
    Table\DeleteConfirmController::class => [
        'class' => Table\DeleteConfirmController::class,
        'arguments' => [ResponseRenderer::class, DbTableExists::class],
    ],
    Table\DeleteRowsController::class => [
        'class' => Table\DeleteRowsController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, \PhpMyAdmin\Sql::class],
    ],
    Table\DropColumnConfirmationController::class => [
        'class' => Table\DropColumnConfirmationController::class,
        'arguments' => [ResponseRenderer::class, DbTableExists::class],
    ],
    Table\DropColumnController::class => [
        'class' => Table\DropColumnController::class,
        'arguments' => [
            ResponseRenderer::class,
            DatabaseInterface::class,
            FlashMessenger::class,
            RelationCleanup::class,
        ],
    ],
    Table\ExportController::class => [
        'class' => Table\ExportController::class,
        'arguments' => [ResponseRenderer::class, Options::class, PageSettings::class],
    ],
    Table\ExportRowsController::class => [
        'class' => Table\ExportRowsController::class,
        'arguments' => [ResponseRenderer::class, Table\ExportController::class],
    ],
    Table\FindReplaceController::class => [
        'class' => Table\FindReplaceController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            DatabaseInterface::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Table\GetFieldController::class => [
        'class' => Table\GetFieldController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, ResponseFactory::class],
    ],
    Table\GisVisualizationController::class => [
        'class' => Table\GisVisualizationController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            DatabaseInterface::class,
            DbTableExists::class,
            ResponseFactory::class,
            Config::class,
        ],
    ],
    Table\ImportController::class => [
        'class' => Table\ImportController::class,
        'arguments' => [
            ResponseRenderer::class,
            DatabaseInterface::class,
            PageSettings::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Table\IndexesController::class => [
        'class' => Table\IndexesController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            DatabaseInterface::class,
            Indexes::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Table\IndexRenameController::class => [
        'class' => Table\IndexRenameController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            DatabaseInterface::class,
            Indexes::class,
            DbTableExists::class,
        ],
    ],
    Table\Maintenance\AnalyzeController::class => [
        'class' => Table\Maintenance\AnalyzeController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Table\Maintenance::class, Config::class],
    ],
    Table\Maintenance\CheckController::class => [
        'class' => Table\Maintenance\CheckController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Table\Maintenance::class, Config::class],
    ],
    Table\Maintenance\ChecksumController::class => [
        'class' => Table\Maintenance\ChecksumController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Table\Maintenance::class, Config::class],
    ],
    Table\Maintenance\OptimizeController::class => [
        'class' => Table\Maintenance\OptimizeController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Table\Maintenance::class, Config::class],
    ],
    Table\Maintenance\RepairController::class => [
        'class' => Table\Maintenance\RepairController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Table\Maintenance::class, Config::class],
    ],
    Table\Partition\AnalyzeController::class => [
        'class' => Table\Partition\AnalyzeController::class,
        'arguments' => [ResponseRenderer::class, Maintenance::class],
    ],
    Table\Partition\CheckController::class => [
        'class' => Table\Partition\CheckController::class,
        'arguments' => [ResponseRenderer::class, Maintenance::class],
    ],
    Table\Partition\DropController::class => [
        'class' => Table\Partition\DropController::class,
        'arguments' => [ResponseRenderer::class, Maintenance::class],
    ],
    Table\Partition\OptimizeController::class => [
        'class' => Table\Partition\OptimizeController::class,
        'arguments' => [ResponseRenderer::class, Maintenance::class],
    ],
    Table\Partition\RebuildController::class => [
        'class' => Table\Partition\RebuildController::class,
        'arguments' => [ResponseRenderer::class, Maintenance::class],
    ],
    Table\Partition\RepairController::class => [
        'class' => Table\Partition\RepairController::class,
        'arguments' => [ResponseRenderer::class, Maintenance::class],
    ],
    Table\Partition\TruncateController::class => [
        'class' => Table\Partition\TruncateController::class,
        'arguments' => [ResponseRenderer::class, Maintenance::class],
    ],
    Operations\TableController::class => [
        'class' => Operations\TableController::class,
        'arguments' => [
            ResponseRenderer::class,
            \PhpMyAdmin\Operations::class,
            UserPrivilegesFactory::class,
            Relation::class,
            DatabaseInterface::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Table\PrivilegesController::class => [
        'class' => Table\PrivilegesController::class,
        'arguments' => [ResponseRenderer::class, Privileges::class, DatabaseInterface::class, Config::class],
    ],
    Table\RecentFavoriteController::class => [
        'class' => Table\RecentFavoriteController::class,
        'arguments' => [ResponseRenderer::class],
    ],
    Table\RelationController::class => [
        'class' => Table\RelationController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Relation::class,
            DatabaseInterface::class,
            Config::class,
        ],
    ],
    Table\ReplaceController::class => [
        'class' => Table\ReplaceController::class,
        'arguments' => [
            ResponseRenderer::class,
            InsertEdit::class,
            Transformations::class,
            Relation::class,
            DatabaseInterface::class,
            Sql\SqlController::class,
            Database\SqlController::class,
            Table\ChangeController::class,
            Table\SqlController::class,
        ],
    ],
    Table\SearchController::class => [
        'class' => Table\SearchController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Search::class,
            Relation::class,
            DatabaseInterface::class,
            DbTableExists::class,
            Config::class,
            \PhpMyAdmin\Sql::class,
        ],
    ],
    Table\SqlController::class => [
        'class' => Table\SqlController::class,
        'arguments' => [ResponseRenderer::class, SqlQueryForm::class, PageSettings::class, DbTableExists::class],
    ],
    Table\Structure\AddIndexController::class => [
        'class' => Table\Structure\AddIndexController::class,
        'arguments' => [ResponseRenderer::class, Table\StructureController::class, Indexes::class],
    ],
    Table\Structure\AddKeyController::class => [
        'class' => Table\Structure\AddKeyController::class,
        'arguments' => [ResponseRenderer::class, Table\StructureController::class, Indexes::class],
    ],
    Table\Structure\BrowseController::class => [
        'class' => Table\Structure\BrowseController::class,
        'arguments' => [ResponseRenderer::class, \PhpMyAdmin\Sql::class],
    ],
    Table\Structure\CentralColumnsAddController::class => [
        'class' => Table\Structure\CentralColumnsAddController::class,
        'arguments' => [ResponseRenderer::class, CentralColumns::class, Table\StructureController::class],
    ],
    Table\Structure\CentralColumnsRemoveController::class => [
        'class' => Table\Structure\CentralColumnsRemoveController::class,
        'arguments' => [ResponseRenderer::class, CentralColumns::class, Table\StructureController::class],
    ],
    Table\Structure\ChangeController::class => [
        'class' => Table\Structure\ChangeController::class,
        'arguments' => [
            ResponseRenderer::class,
            DatabaseInterface::class,
            ColumnsDefinition::class,
            UserPrivilegesFactory::class,
        ],
    ],
    Table\Structure\FulltextController::class => [
        'class' => Table\Structure\FulltextController::class,
        'arguments' => [ResponseRenderer::class, Table\StructureController::class, Indexes::class],
    ],
    Table\Structure\MoveColumnsController::class => [
        'class' => Table\Structure\MoveColumnsController::class,
        'arguments' => [ResponseRenderer::class, Template::class, DatabaseInterface::class],
    ],
    Table\Structure\PartitioningController::class => [
        'class' => Table\Structure\PartitioningController::class,
        'arguments' => [
            ResponseRenderer::class,
            DatabaseInterface::class,
            CreateAddField::class,
            Table\StructureController::class,
            PageSettings::class,
        ],
    ],
    Table\Structure\PrimaryController::class => [
        'class' => Table\Structure\PrimaryController::class,
        'arguments' => [
            ResponseRenderer::class,
            DatabaseInterface::class,
            Table\StructureController::class,
            DbTableExists::class,
        ],
    ],
    Table\Structure\ReservedWordCheckController::class => [
        'class' => Table\Structure\ReservedWordCheckController::class,
        'arguments' => [ResponseRenderer::class, Config::class],
    ],
    Table\Structure\SaveController::class => [
        'class' => Table\Structure\SaveController::class,
        'arguments' => [
            ResponseRenderer::class,
            Relation::class,
            Transformations::class,
            DatabaseInterface::class,
            Table\StructureController::class,
            UserPrivilegesFactory::class,
            Config::class,
        ],
    ],
    Table\Structure\SpatialController::class => [
        'class' => Table\Structure\SpatialController::class,
        'arguments' => [ResponseRenderer::class, Table\StructureController::class, Indexes::class],
    ],
    Table\Structure\UniqueController::class => [
        'class' => Table\Structure\UniqueController::class,
        'arguments' => [ResponseRenderer::class, Table\StructureController::class, Indexes::class],
    ],
    Table\StructureController::class => [
        'class' => Table\StructureController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Relation::class,
            Transformations::class,
            DatabaseInterface::class,
            PageSettings::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    Table\TrackingController::class => [
        'class' => Table\TrackingController::class,
        'arguments' => [
            ResponseRenderer::class,
            Tracking::class,
            TrackingChecker::class,
            DbTableExists::class,
            ResponseFactory::class,
        ],
    ],
    Triggers\IndexController::class => [
        'class' => Triggers\IndexController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            DatabaseInterface::class,
            \PhpMyAdmin\Triggers\Triggers::class,
            DbTableExists::class,
        ],
    ],
    Table\ZoomSearchController::class => [
        'class' => Table\ZoomSearchController::class,
        'arguments' => [
            ResponseRenderer::class,
            Template::class,
            Search::class,
            Relation::class,
            DatabaseInterface::class,
            DbTableExists::class,
            Config::class,
        ],
    ],
    TableController::class => [
        'class' => TableController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class],
    ],
    ThemesController::class => [
        'class' => ThemesController::class,
        'arguments' => [ResponseRenderer::class, Template::class, ThemeManager::class],
    ],
    ThemeSetController::class => [
        'class' => ThemeSetController::class,
        'arguments' => [ResponseRenderer::class, ThemeManager::class, UserPreferences::class, Config::class],
    ],
    Transformation\OverviewController::class => [
        'class' => Transformation\OverviewController::class,
        'arguments' => [ResponseRenderer::class, Transformations::class],
    ],
    Transformation\WrapperController::class => [
        'class' => Transformation\WrapperController::class,
        'arguments' => [
            ResponseRenderer::class,
            Transformations::class,
            Relation::class,
            DatabaseInterface::class,
            DbTableExists::class,
            ResponseFactory::class,
        ],
    ],
    UserPasswordController::class => [
        'class' => UserPasswordController::class,
        'arguments' => [ResponseRenderer::class, UserPassword::class, DatabaseInterface::class, Config::class],
    ],
    VersionCheckController::class => [
        'class' => VersionCheckController::class,
        'arguments' => [VersionInformation::class, ResponseFactory::class],
    ],
    View\CreateController::class => [
        'class' => View\CreateController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, DbTableExists::class],
    ],
    Operations\ViewController::class => [
        'class' => Operations\ViewController::class,
        'arguments' => [ResponseRenderer::class, DatabaseInterface::class, DbTableExists::class],
    ],
    SyncFavoriteTablesController::class => [
        'class' => SyncFavoriteTablesController::class,
        'arguments' => [ResponseRenderer::class, Relation::class, Config::class],
    ],
];
