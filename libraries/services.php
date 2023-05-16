<?php

declare(strict_types=1);

use PhpMyAdmin\Advisory\Advisor;
use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Database\Designer\Common;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\Database\Triggers;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\ErrorReport;
use PhpMyAdmin\Export;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Import;
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
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\Tracking\Tracking;
use PhpMyAdmin\Tracking\TrackingChecker;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UserPassword;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\Utils\HttpRequest;
use PhpMyAdmin\VersionInformation;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

return [
    'services' => [
        'advisor' => [
            'class' => Advisor::class,
            'arguments' => ['$dbi' => '@dbi', '$expression' => '@expression_language'],
        ],
        'browse_foreigners' => [
            'class' => BrowseForeigners::class,
            'arguments' => ['@template', '@config'],
        ],
        'config' => ['class' => Config::class],
        'central_columns' => ['class' => CentralColumns::class, 'arguments' => ['@dbi']],
        'check_user_privileges' => ['class' => CheckUserPrivileges::class, 'arguments' => ['@dbi']],
        'create_add_field' => ['class' => CreateAddField::class, 'arguments' => ['@dbi']],
        'designer' => [
            'class' => Designer::class,
            'arguments' => ['$dbi' => '@dbi', '$relation' => '@relation', '$template' => '@template'],
        ],
        'designer_common' => [
            'class' => Common::class,
            'arguments' => ['$dbi' => '@dbi', '$relation' => '@relation'],
        ],
        'error_handler' => ['class' => ErrorHandler::class],
        'error_report' => [
            'class' => ErrorReport::class,
            'arguments' => ['@http_request', '@relation', '@template', '@config'],
        ],
        'events' => ['class' => Events::class, 'arguments' => ['@dbi','@template','@response']],
        'export' => ['class' => Export::class, 'arguments' => ['@dbi']],
        'export_options' => [
            'class' => Options::class,
            'arguments' => ['@relation', '@export_template_model'],
        ],
        'export_template_model' => ['class' => TemplateModel::class, 'arguments' => ['@dbi']],
        'expression_language' => ['class' => ExpressionLanguage::class],
        'file_listing' => ['class' => FileListing::class],
        'flash' => ['class' => FlashMessages::class],
        'http_request' => ['class' => HttpRequest::class],
        'import' => ['class' => Import::class],
        'import_simulate_dml' => ['class' => SimulateDml::class, 'arguments' => ['@dbi']],
        'insert_edit' => [
            'class' => InsertEdit::class,
            'arguments' => ['@dbi', '@relation', '@transformations', '@file_listing', '@template'],
        ],
        'navigation' => [
            'class' => Navigation::class,
            'arguments' => ['@template', '@relation', '@dbi'],
        ],
        'normalization' => [
            'class' => Normalization::class,
            'arguments' => [
                '$dbi' => '@dbi',
                '$relation' => '@relation',
                '$transformations' => '@transformations',
                '$template' => '@template',
            ],
        ],
        'operations' => [
            'class' => Operations::class,
            'arguments' => ['$dbi' => '@dbi', '$relation' => '@relation'],
        ],
        'partitioning_maintenance' => [
            'class' => Maintenance::class,
            'arguments' => ['$dbi' => '@dbi'],
        ],
        AuthenticationPluginFactory::class => ['class' => AuthenticationPluginFactory::class],
        'relation' => ['class' => Relation::class, 'arguments' => ['$dbi' => '@dbi']],
        'relation_cleanup' => ['class' => RelationCleanup::class, 'arguments' => ['@dbi', '@relation']],
        'replication' => ['class' => Replication::class, 'arguments' => ['$dbi' => '@dbi']],
        'replication_gui' => [
            'class' => ReplicationGui::class,
            'arguments' => ['$replication' => '@replication', '$template' => '@template'],
        ],
        'response' => [
            'class' => ResponseRenderer::class,
            'factory' => [PhpMyAdmin\ResponseRenderer::class, 'getInstance'],
        ],
        'routines' => [
            'class' => Routines::class,
            'arguments' => ['@dbi', '@template', '@response'],
        ],
        'server_plugins' => ['class' => Plugins::class, 'arguments' => ['@dbi']],
        'server_privileges' => [
            'class' => Privileges::class,
            'arguments' => ['@template', '@dbi', '@relation', '@relation_cleanup', '@server_plugins'],
        ],
        'server_privileges_account_locking' => [
            'class' => AccountLocking::class,
            'arguments' => ['@dbi'],
        ],
        'sql' => [
            'class' => Sql::class,
            'arguments' => ['@dbi', '@relation', '@relation_cleanup', '@operations', '@transformations', '@template'],
        ],
        'sql_query_form' => [
            'class' => SqlQueryForm::class,
            'arguments' => ['$template' => '@template', '$dbi' => '@dbi'],
        ],
        'status_data' => ['class' => Data::class, 'arguments' => ['@dbi','@config']],
        'status_monitor' => ['class' => Monitor::class, 'arguments' => ['@dbi']],
        'status_processes' => ['class' => Processes::class, 'arguments' => ['@dbi']],
        'table_columns_definition' => [
            'class' => ColumnsDefinition::class,
            'arguments' => ['$dbi' => '@dbi', '$relation' => '@relation', '$transformations' => '@transformations'],
        ],
        'table_indexes' => [
            'class' => Indexes::class,
            'arguments' => ['$response' => '@response', '$template' => '@template', '$dbi' => '@dbi'],
        ],
        'table_maintenance' => ['class' => PhpMyAdmin\Table\Maintenance::class, 'arguments' => ['$dbi' => '@dbi']],
        'table_search' => ['class' => Search::class, 'arguments' => ['$dbi' => '@dbi']],
        'template' => ['class' => Template::class, 'arguments' => ['$config' => '@config']],
        ThemeManager::class => ['class' => PhpMyAdmin\Theme\ThemeManager::class],
        'tracking' => [
            'class' => Tracking::class,
            'arguments' => [
                '$sqlQueryForm' => '@sql_query_form',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
                '$trackingChecker' => '@tracking_checker',
            ],
        ],
        'tracking_checker' => [
            'class' => TrackingChecker::class,
            'arguments' => ['$dbi' => '@dbi', '$relation' => '@relation'],
        ],
        'transformations' => ['class' => Transformations::class],
        'triggers' => [
            'class' => Triggers::class,
            'arguments' => ['@dbi', '@template', '@response'],
        ],
        'user_password' => [
            'class' => UserPassword::class,
            'arguments' => ['@server_privileges', '@' . AuthenticationPluginFactory::class, '@dbi'],
        ],
        'user_preferences' => ['class' => UserPreferences::class, 'arguments' => ['@dbi']],
        'version_information' => ['class' => VersionInformation::class],
        DatabaseInterface::class => 'dbi',
        PhpMyAdmin\FlashMessages::class => 'flash',
        PhpMyAdmin\ResponseRenderer::class => 'response',
    ],
];
