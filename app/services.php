<?php

declare(strict_types=1);

use PhpMyAdmin\Advisory\Advisor;
use PhpMyAdmin\Application;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Console\Console;
use PhpMyAdmin\Console\History;
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
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Header;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Middleware;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Operations;
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
use PhpMyAdmin\Sql;
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
use PhpMyAdmin\Triggers\Triggers;
use PhpMyAdmin\UserPassword;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Utils\HttpRequest;
use PhpMyAdmin\VersionInformation;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

return [
    Advisor::class => ['class' => Advisor::class, 'arguments' => [DatabaseInterface::class, ExpressionLanguage::class]],
    Application::class => ['class' => Application::class, 'arguments' => [ResponseFactory::class]],
    BrowseForeigners::class => [
        'class' => BrowseForeigners::class,
        'arguments' => [Template::class, Config::class, ThemeManager::class],
    ],
    Config::class => ['class' => Config::class, 'factory' => [Config::class, 'getInstance']],
    Config\PageSettings::class => ['class' => Config\PageSettings::class, 'arguments' => [UserPreferences::class]],
    CentralColumns::class => ['class' => CentralColumns::class, 'arguments' => [DatabaseInterface::class]],
    CreateAddField::class => ['class' => CreateAddField::class, 'arguments' => [DatabaseInterface::class]],
    DatabaseInterface::class => [
        'class' => DatabaseInterface::class,
        'factory' => [DatabaseInterface::class, 'getInstance'],
        'arguments' => [Config::class],
    ],
    DbTableExists::class => ['class' => DbTableExists::class, 'arguments' => [DatabaseInterface::class]],
    Designer::class => [
        'class' => Designer::class,
        'arguments' => [DatabaseInterface::class, Relation::class, Template::class, Config::class],
    ],
    Common::class => [
        'class' => Common::class,
        'arguments' => [DatabaseInterface::class, Relation::class, Config::class],
    ],
    ErrorHandler::class => ['class' => ErrorHandler::class, 'factory' => [ErrorHandler::class, 'getInstance']],
    ErrorReport::class => [
        'class' => ErrorReport::class,
        'arguments' => [HttpRequest::class, Relation::class, Template::class, Config::class],
    ],
    Events::class => ['class' => Events::class, 'arguments' => [DatabaseInterface::class, Config::class]],
    Export::class => ['class' => Export::class, 'arguments' => [DatabaseInterface::class, OutputHandler::class]],
    Options::class => [
        'class' => Options::class,
        'arguments' => [Relation::class, TemplateModel::class, UserPreferencesHandler::class],
    ],
    TemplateModel::class => ['class' => TemplateModel::class, 'arguments' => [DatabaseInterface::class]],
    ExpressionLanguage::class => ['class' => ExpressionLanguage::class],
    FileListing::class => ['class' => FileListing::class],
    FlashMessenger::class => ['class' => FlashMessenger::class],
    Header::class => [
        'class' => Header::class,
        'arguments' => [
            Template::class,
            Console::class,
            Config::class,
            DatabaseInterface::class,
            Relation::class,
            UserPreferences::class,
            UserPreferencesHandler::class,
        ],
    ],
    HttpRequest::class => ['class' => HttpRequest::class],
    ResponseFactory::class => ['class' => ResponseFactory::class, 'factory' => [ResponseFactory::class, 'create']],
    Import::class => ['class' => Import::class],
    SimulateDml::class => ['class' => SimulateDml::class, 'arguments' => [DatabaseInterface::class]],
    InsertEdit::class => [
        'class' => InsertEdit::class,
        'arguments' => [
            DatabaseInterface::class,
            Relation::class,
            Transformations::class,
            FileListing::class,
            Template::class,
            Config::class,
        ],
    ],
    Middleware\ErrorHandling::class => [
        'class' => Middleware\ErrorHandling::class,
        'arguments' => [ErrorHandler::class],
    ],
    Middleware\OutputBuffering::class => ['class' => Middleware\OutputBuffering::class],
    Middleware\PhpExtensionsChecking::class => [
        'class' => Middleware\PhpExtensionsChecking::class,
        'arguments' => [Template::class, ResponseFactory::class],
    ],
    Middleware\ServerConfigurationChecking::class => [
        'class' => Middleware\ServerConfigurationChecking::class,
        'arguments' => [Template::class, ResponseFactory::class],
    ],
    Middleware\PhpSettingsConfiguration::class => ['class' => Middleware\PhpSettingsConfiguration::class],
    Middleware\RouteParsing::class => ['class' => Middleware\RouteParsing::class],
    Middleware\ConfigLoading::class => [
        'class' => Middleware\ConfigLoading::class,
        'arguments' => [Config::class, Template::class, ResponseFactory::class],
    ],
    Middleware\UriSchemeUpdating::class => [
        'class' => Middleware\UriSchemeUpdating::class,
        'arguments' => [Config::class],
    ],
    Middleware\SessionHandling::class => [
        'class' => Middleware\SessionHandling::class,
        'arguments' => [Config::class, ErrorHandler::class, Template::class, ResponseFactory::class],
    ],
    Middleware\EncryptedQueryParamsHandling::class => ['class' => Middleware\EncryptedQueryParamsHandling::class],
    Middleware\UrlParamsSetting::class => [
        'class' => Middleware\UrlParamsSetting::class,
        'arguments' => [Config::class],
    ],
    Middleware\TokenRequestParamChecking::class => ['class' => Middleware\TokenRequestParamChecking::class],
    Middleware\DatabaseAndTableSetting::class => ['class' => Middleware\DatabaseAndTableSetting::class],
    Middleware\SqlQueryGlobalSetting::class => ['class' => Middleware\SqlQueryGlobalSetting::class],
    Middleware\LanguageLoading::class => ['class' => Middleware\LanguageLoading::class],
    Middleware\ConfigErrorAndPermissionChecking::class => [
        'class' => Middleware\ConfigErrorAndPermissionChecking::class,
        'arguments' => [Config::class, Template::class, ResponseFactory::class],
    ],
    Middleware\RequestProblemChecking::class => [
        'class' => Middleware\RequestProblemChecking::class,
        'arguments' => [Template::class, ResponseFactory::class],
    ],
    Middleware\CurrentServerGlobalSetting::class => [
        'class' => Middleware\CurrentServerGlobalSetting::class,
        'arguments' => [Config::class],
    ],
    Middleware\ThemeInitialization::class => ['class' => Middleware\ThemeInitialization::class],
    Middleware\UrlRedirection::class => [
        'class' => Middleware\UrlRedirection::class,
        'arguments' => [Template::class, ResponseFactory::class, UserPreferencesHandler::class],
    ],
    Middleware\SetupPageRedirection::class => [
        'class' => Middleware\SetupPageRedirection::class,
        'arguments' => [Config::class, ResponseFactory::class, UserPreferencesHandler::class],
    ],
    Middleware\MinimumCommonRedirection::class => [
        'class' => Middleware\MinimumCommonRedirection::class,
        'arguments' => [ResponseFactory::class, UserPreferencesHandler::class],
    ],
    Middleware\LanguageAndThemeCookieSaving::class => [
        'class' => Middleware\LanguageAndThemeCookieSaving::class,
        'arguments' => [Config::class],
    ],
    Middleware\LoginCookieValiditySetting::class => [
        'class' => Middleware\LoginCookieValiditySetting::class,
        'arguments' => [Config::class, UserPreferencesHandler::class],
    ],
    Middleware\Authentication::class => [
        'class' => Middleware\Authentication::class,
        'arguments' => [
            Config::class,
            Template::class,
            ResponseFactory::class,
            AuthenticationPluginFactory::class,
            DatabaseInterface::class,
            Relation::class,
            ResponseRenderer::class,
        ],
    ],
    Middleware\DatabaseServerVersionChecking::class => [
        'class' => Middleware\DatabaseServerVersionChecking::class,
        'arguments' => [Config::class, Template::class, ResponseFactory::class],
    ],
    Middleware\SqlDelimiterSetting::class => [
        'class' => Middleware\SqlDelimiterSetting::class,
        'arguments' => [Config::class],
    ],
    Middleware\ResponseRendererLoading::class => [
        'class' => Middleware\ResponseRendererLoading::class,
        'arguments' => [Config::class],
    ],
    Middleware\ProfilingChecking::class => ['class' => Middleware\ProfilingChecking::class],
    Middleware\UserPreferencesLoading::class => [
        'class' => Middleware\UserPreferencesLoading::class,
        'arguments' => [UserPreferencesHandler::class],
    ],
    Middleware\RecentTableHandling::class => [
        'class' => Middleware\RecentTableHandling::class,
        'arguments' => [Config::class],
    ],
    Middleware\StatementHistory::class => [
        'class' => Middleware\StatementHistory::class,
        'arguments' => [Config::class, History::class],
    ],
    Navigation::class => [
        'class' => Navigation::class,
        'arguments' => [Template::class, Relation::class, DatabaseInterface::class, Config::class],
    ],
    Normalization::class => [
        'class' => Normalization::class,
        'arguments' => [DatabaseInterface::class, Relation::class, Transformations::class, Template::class],
    ],
    Operations::class => [
        'class' => Operations::class,
        'arguments' => [DatabaseInterface::class, Relation::class, TableMover::class],
    ],
    OutputHandler::class => ['class' => OutputHandler::class],
    Maintenance::class => ['class' => Maintenance::class, 'arguments' => [DatabaseInterface::class]],
    AuthenticationPluginFactory::class => ['class' => AuthenticationPluginFactory::class],
    Relation::class => ['class' => Relation::class, 'arguments' => [DatabaseInterface::class, Config::class]],
    RelationCleanup::class => [
        'class' => RelationCleanup::class,
        'arguments' => [DatabaseInterface::class, Relation::class],
    ],
    Replication::class => ['class' => Replication::class, 'arguments' => [DatabaseInterface::class]],
    ReplicationGui::class => ['class' => ReplicationGui::class, 'arguments' => [Replication::class, Template::class]],
    ResponseRenderer::class => [
        'class' => ResponseRenderer::class,
        'factory' => [ResponseRenderer::class, 'getInstance'],
    ],
    Routines::class => ['class' => Routines::class, 'arguments' => [DatabaseInterface::class, Config::class]],
    Plugins::class => ['class' => Plugins::class, 'arguments' => [DatabaseInterface::class]],
    Privileges::class => [
        'class' => Privileges::class,
        'arguments' => [
            Template::class,
            DatabaseInterface::class,
            Relation::class,
            RelationCleanup::class,
            Plugins::class,
            Config::class,
        ],
    ],
    AccountLocking::class => ['class' => AccountLocking::class, 'arguments' => [DatabaseInterface::class]],
    Sql::class => [
        'class' => Sql::class,
        'arguments' => [
            DatabaseInterface::class,
            Relation::class,
            RelationCleanup::class,
            Transformations::class,
            Template::class,
            BookmarkRepository::class,
            Config::class,
        ],
    ],
    SqlQueryForm::class => [
        'class' => SqlQueryForm::class,
        'arguments' => [Template::class, DatabaseInterface::class, BookmarkRepository::class],
    ],
    Data::class => ['class' => Data::class, 'arguments' => [DatabaseInterface::class, Config::class]],
    Monitor::class => ['class' => Monitor::class, 'arguments' => [DatabaseInterface::class]],
    Processes::class => ['class' => Processes::class, 'arguments' => [DatabaseInterface::class]],
    ColumnsDefinition::class => [
        'class' => ColumnsDefinition::class,
        'arguments' => [DatabaseInterface::class, Relation::class, Transformations::class],
    ],
    Indexes::class => ['class' => Indexes::class, 'arguments' => [DatabaseInterface::class]],
    \PhpMyAdmin\Table\Maintenance::class => [
        'class' => \PhpMyAdmin\Table\Maintenance::class,
        'arguments' => [DatabaseInterface::class],
    ],
    Search::class => ['class' => Search::class, 'arguments' => [DatabaseInterface::class]],
    Template::class => ['class' => Template::class, 'arguments' => [Config::class]],
    ThemeManager::class => ['class' => ThemeManager::class],
    Tracking::class => [
        'class' => Tracking::class,
        'arguments' => [
            SqlQueryForm::class,
            Template::class,
            Relation::class,
            DatabaseInterface::class,
            TrackingChecker::class,
        ],
    ],
    TrackingChecker::class => [
        'class' => TrackingChecker::class,
        'arguments' => [DatabaseInterface::class, Relation::class],
    ],
    Transformations::class => [
        'class' => Transformations::class,
        'arguments' => [DatabaseInterface::class, Relation::class],
    ],
    Triggers::class => ['class' => Triggers::class, 'arguments' => [DatabaseInterface::class]],
    UserPassword::class => [
        'class' => UserPassword::class,
        'arguments' => [Privileges::class, AuthenticationPluginFactory::class, DatabaseInterface::class],
    ],
    UserPreferences::class => [
        'class' => UserPreferences::class,
        'arguments' => [DatabaseInterface::class, Relation::class, Template::class, Config::class],
    ],
    UserPrivilegesFactory::class => [
        'class' => UserPrivilegesFactory::class,
        'arguments' => [DatabaseInterface::class],
    ],
    VersionInformation::class => ['class' => VersionInformation::class],
    BookmarkRepository::class => [
        'class' => BookmarkRepository::class,
        'arguments' => [DatabaseInterface::class, Relation::class],
    ],
    Console::class => [
        'class' => Console::class,
        'arguments' => [Relation::class, Template::class, BookmarkRepository::class, History::class],
    ],
    TableMover::class => ['class' => TableMover::class, 'arguments' => [DatabaseInterface::class, Relation::class]],
    History::class => [
        'class' => History::class,
        'arguments' => [DatabaseInterface::class, Relation::class, Config::class],
    ],
    UserPreferencesHandler::class => [
        'class' => UserPreferencesHandler::class,
        'arguments' => [
            Config::class,
            DatabaseInterface::class,
            UserPreferences::class,
            LanguageManager::class,
            ThemeManager::class,
        ],
    ],
    LanguageManager::class => ['class' => LanguageManager::class, 'factory' => [LanguageManager::class, 'getInstance']],
];
