<?php

declare(strict_types=1);

return [
    'services' => [
        PhpMyAdmin\Controllers\BrowseForeignersController::class => [
            'class' => PhpMyAdmin\Controllers\BrowseForeignersController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$browseForeigners' => '@browse_foreigners',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\ChangeLogController::class => [
            'class' => PhpMyAdmin\Controllers\ChangeLogController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        PhpMyAdmin\Controllers\CheckRelationsController::class => [
            'class' => PhpMyAdmin\Controllers\CheckRelationsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\ColumnController::class => [
            'class' => PhpMyAdmin\Controllers\ColumnController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\ConfigController::class => [
            'class' => PhpMyAdmin\Controllers\ConfigController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$config' => '@config',
            ],
        ],
        PhpMyAdmin\Controllers\Database\CentralColumnsController::class => [
            'class' => PhpMyAdmin\Controllers\Database\CentralColumnsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$centralColumns' => '@central_columns',
            ],
        ],
        PhpMyAdmin\Controllers\Database\DataDictionaryController::class => [
            'class' => PhpMyAdmin\Controllers\Database\DataDictionaryController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$relation' => '@relation',
                '$transformations' => '@transformations',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\DesignerController::class => [
            'class' => PhpMyAdmin\Controllers\Database\DesignerController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$databaseDesigner' => '@designer',
                '$designerCommon' => '@designer_common',
            ],
        ],
        PhpMyAdmin\Controllers\Database\EventsController::class => [
            'class' => PhpMyAdmin\Controllers\Database\EventsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$events' => '@events',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\ExportController::class => [
            'class' => PhpMyAdmin\Controllers\Database\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$export' => '@export',
                '$exportOptions' => '@export_options',
            ],
        ],
        PhpMyAdmin\Controllers\Database\ImportController::class => [
            'class' => PhpMyAdmin\Controllers\Database\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\MultiTableQueryController::class => [
            'class' => PhpMyAdmin\Controllers\Database\MultiTableQueryController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\OperationsController::class => [
            'class' => PhpMyAdmin\Controllers\Database\OperationsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$operations' => '@operations',
                '$checkUserPrivileges' => '@check_user_privileges',
                '$relation' => '@relation',
                '$relationCleanup' => '@relation_cleanup',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\PrivilegesController::class => [
            'class' => PhpMyAdmin\Controllers\Database\PrivilegesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$privileges' => '@server_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\QueryByExampleController::class => [
            'class' => PhpMyAdmin\Controllers\Database\QueryByExampleController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\RoutinesController::class => [
            'class' => PhpMyAdmin\Controllers\Database\RoutinesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$checkUserPrivileges' => '@check_user_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\SearchController::class => [
            'class' => PhpMyAdmin\Controllers\Database\SearchController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\SqlAutoCompleteController::class => [
            'class' => PhpMyAdmin\Controllers\Database\SqlAutoCompleteController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\SqlController::class => [
            'class' => PhpMyAdmin\Controllers\Database\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$sqlQueryForm' => '@sql_query_form',
            ],
        ],
        PhpMyAdmin\Controllers\Database\SqlFormatController::class => [
            'class' => PhpMyAdmin\Controllers\Database\SqlFormatController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
            ],
        ],
        PhpMyAdmin\Controllers\Database\StructureController::class => [
            'class' => PhpMyAdmin\Controllers\Database\StructureController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$relation' => '@relation',
                '$replication' => '@replication',
                '$relationCleanup' => '@relation_cleanup',
                '$operations' => '@operations',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\TrackingController::class => [
            'class' => PhpMyAdmin\Controllers\Database\TrackingController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$tracking' => '@tracking',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Database\TriggersController::class => [
            'class' => PhpMyAdmin\Controllers\Database\TriggersController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\DatabaseController::class => [
            'class' => PhpMyAdmin\Controllers\DatabaseController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        PhpMyAdmin\Controllers\ErrorReportController::class => [
            'class' => PhpMyAdmin\Controllers\ErrorReportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$errorReport' => '@error_report',
                '$errorHandler' => '@error_handler',
            ],
        ],
        PhpMyAdmin\Controllers\ExportController::class => [
            'class' => PhpMyAdmin\Controllers\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$export' => '@export',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\ExportTemplateController::class => [
            'class' => PhpMyAdmin\Controllers\ExportTemplateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$model' => '@export_template_model',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\GisDataEditorController::class => [
            'class' => PhpMyAdmin\Controllers\GisDataEditorController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        PhpMyAdmin\Controllers\HomeController::class => [
            'class' => PhpMyAdmin\Controllers\HomeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$config' => '@config',
                '$themeManager' => '@theme_manager',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\ImportController::class => [
            'class' => PhpMyAdmin\Controllers\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$import' => '@import',
                '$sql' => '@sql',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\ImportStatusController::class => [
            'class' => PhpMyAdmin\Controllers\ImportStatusController::class,
            'arguments' => ['$template' => '@template'],
        ],
        PhpMyAdmin\Controllers\JavaScriptMessagesController::class => [
            'class' => PhpMyAdmin\Controllers\JavaScriptMessagesController::class,
        ],
        PhpMyAdmin\Controllers\LicenseController::class => [
            'class' => PhpMyAdmin\Controllers\LicenseController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        PhpMyAdmin\Controllers\LintController::class => [
            'class' => PhpMyAdmin\Controllers\LintController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        PhpMyAdmin\Controllers\LogoutController::class => [
            'class' => PhpMyAdmin\Controllers\LogoutController::class,
        ],
        PhpMyAdmin\Controllers\NavigationController::class => [
            'class' => PhpMyAdmin\Controllers\NavigationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$navigation' => '@navigation',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\NormalizationController::class => [
            'class' => PhpMyAdmin\Controllers\NormalizationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$normalization' => '@normalization',
            ],
        ],
        PhpMyAdmin\Controllers\PhpInfoController::class => [
            'class' => PhpMyAdmin\Controllers\PhpInfoController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        PhpMyAdmin\Controllers\Preferences\ExportController::class => [
            'class' => PhpMyAdmin\Controllers\Preferences\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\Preferences\FeaturesController::class => [
            'class' => PhpMyAdmin\Controllers\Preferences\FeaturesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\Preferences\ImportController::class => [
            'class' => PhpMyAdmin\Controllers\Preferences\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\Preferences\MainPanelController::class => [
            'class' => PhpMyAdmin\Controllers\Preferences\MainPanelController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\Preferences\ManageController::class => [
            'class' => PhpMyAdmin\Controllers\Preferences\ManageController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\Preferences\NavigationController::class => [
            'class' => PhpMyAdmin\Controllers\Preferences\NavigationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\Preferences\SqlController::class => [
            'class' => PhpMyAdmin\Controllers\Preferences\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPreferences' => '@user_preferences',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\Preferences\TwoFactorController::class => [
            'class' => PhpMyAdmin\Controllers\Preferences\TwoFactorController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\SchemaExportController::class => [
            'class' => PhpMyAdmin\Controllers\SchemaExportController::class,
            'arguments' => [
                '$export' => '@export',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\Server\BinlogController::class => [
            'class' => PhpMyAdmin\Controllers\Server\BinlogController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\CollationsController::class => [
            'class' => PhpMyAdmin\Controllers\Server\CollationsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\DatabasesController::class => [
            'class' => PhpMyAdmin\Controllers\Server\DatabasesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$transformations' => '@transformations',
                '$relationCleanup' => '@relation_cleanup',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\EnginesController::class => [
            'class' => PhpMyAdmin\Controllers\Server\EnginesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\ExportController::class => [
            'class' => PhpMyAdmin\Controllers\Server\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$export' => '@export_options',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\ImportController::class => [
            'class' => PhpMyAdmin\Controllers\Server\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\PluginsController::class => [
            'class' => PhpMyAdmin\Controllers\Server\PluginsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$plugins' => '@server_plugins',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\PrivilegesController::class => [
            'class' => PhpMyAdmin\Controllers\Server\PrivilegesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\ReplicationController::class => [
            'class' => PhpMyAdmin\Controllers\Server\ReplicationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$replicationGui' => '@replication_gui',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\SqlController::class => [
            'class' => PhpMyAdmin\Controllers\Server\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sqlQueryForm' => '@sql_query_form',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\UserGroupsController::class => [
            'class' => PhpMyAdmin\Controllers\Server\UserGroupsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\Status\AdvisorController::class => [
            'class' => PhpMyAdmin\Controllers\Server\Status\AdvisorController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$advisor' => '@advisor',
            ],
        ],
        PhpMyAdmin\Controllers\Server\Status\MonitorController::class => [
            'class' => PhpMyAdmin\Controllers\Server\Status\MonitorController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$monitor' => '@status_monitor',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\Status\ProcessesController::class => [
            'class' => PhpMyAdmin\Controllers\Server\Status\ProcessesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\Status\QueriesController::class => [
            'class' => PhpMyAdmin\Controllers\Server\Status\QueriesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\Status\StatusController::class => [
            'class' => PhpMyAdmin\Controllers\Server\Status\StatusController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$replicationGui' => '@replication_gui',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\Status\VariablesController::class => [
            'class' => PhpMyAdmin\Controllers\Server\Status\VariablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$data' => '@status_data',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Server\VariablesController::class => [
            'class' => PhpMyAdmin\Controllers\Server\VariablesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\SqlController::class => [
            'class' => PhpMyAdmin\Controllers\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$sql' => '@sql',
                '$checkUserPrivileges' => '@check_user_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\AddFieldController::class => [
            'class' => PhpMyAdmin\Controllers\Table\AddFieldController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$transformations' => '@transformations',
                '$config' => '@config',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\ChangeController::class => [
            'class' => PhpMyAdmin\Controllers\Table\ChangeController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$insertEdit' => '@insert_edit',
                '$relation' => '@relation',
            ],
        ],
        PhpMyAdmin\Controllers\Table\ChartController::class => [
            'class' => PhpMyAdmin\Controllers\Table\ChartController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\CreateController::class => [
            'class' => PhpMyAdmin\Controllers\Table\CreateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$transformations' => '@transformations',
                '$config' => '@config',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\DeleteController::class => [
            'class' => PhpMyAdmin\Controllers\Table\DeleteController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\ExportController::class => [
            'class' => PhpMyAdmin\Controllers\Table\ExportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$export' => '@export_options',
            ],
        ],
        PhpMyAdmin\Controllers\Table\FindReplaceController::class => [
            'class' => PhpMyAdmin\Controllers\Table\FindReplaceController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\GetFieldController::class => [
            'class' => PhpMyAdmin\Controllers\Table\GetFieldController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\GisVisualizationController::class => [
            'class' => PhpMyAdmin\Controllers\Table\GisVisualizationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\ImportController::class => [
            'class' => PhpMyAdmin\Controllers\Table\ImportController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\IndexesController::class => [
            'class' => PhpMyAdmin\Controllers\Table\IndexesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\MaintenanceController::class => [
            'class' => PhpMyAdmin\Controllers\Table\MaintenanceController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$model' => '@table_maintenance',
            ],
        ],
        PhpMyAdmin\Controllers\Table\PartitionController::class => [
            'class' => PhpMyAdmin\Controllers\Table\PartitionController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$partition' => '@table_partition',
            ],
        ],
        PhpMyAdmin\Controllers\Table\OperationsController::class => [
            'class' => PhpMyAdmin\Controllers\Table\OperationsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$operations' => '@operations',
                '$checkUserPrivileges' => '@check_user_privileges',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\PrivilegesController::class => [
            'class' => PhpMyAdmin\Controllers\Table\PrivilegesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$privileges' => '@server_privileges',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\RecentFavoriteController::class => [
            'class' => PhpMyAdmin\Controllers\Table\RecentFavoriteController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
            ],
        ],
        PhpMyAdmin\Controllers\Table\RelationController::class => [
            'class' => PhpMyAdmin\Controllers\Table\RelationController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\ReplaceController::class => [
            'class' => PhpMyAdmin\Controllers\Table\ReplaceController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$insertEdit' => '@insert_edit',
                '$transformations' => '@transformations',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\SearchController::class => [
            'class' => PhpMyAdmin\Controllers\Table\SearchController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$search' => '@table_search',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\SqlController::class => [
            'class' => PhpMyAdmin\Controllers\Table\SqlController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$sqlQueryForm' => '@sql_query_form',
            ],
        ],
        PhpMyAdmin\Controllers\Table\StructureController::class => [
            'class' => PhpMyAdmin\Controllers\Table\StructureController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$relation' => '@relation',
                '$transformations' => '@transformations',
                '$createAddField' => '@create_add_field',
                '$relationCleanup' => '@relation_cleanup',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\TrackingController::class => [
            'class' => PhpMyAdmin\Controllers\Table\TrackingController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$tracking' => '@tracking',
            ],
        ],
        PhpMyAdmin\Controllers\Table\TriggersController::class => [
            'class' => PhpMyAdmin\Controllers\Table\TriggersController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\Table\ZoomSearchController::class => [
            'class' => PhpMyAdmin\Controllers\Table\ZoomSearchController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$db' => '%db%',
                '$table' => '%table%',
                '$search' => '@table_search',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\TableController::class => [
            'class' => PhpMyAdmin\Controllers\TableController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\ThemesController::class => [
            'class' => PhpMyAdmin\Controllers\ThemesController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        PhpMyAdmin\Controllers\TransformationOverviewController::class => [
            'class' => PhpMyAdmin\Controllers\TransformationOverviewController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$transformations' => '@transformations',
            ],
        ],
        PhpMyAdmin\Controllers\TransformationWrapperController::class => [
            'class' => PhpMyAdmin\Controllers\TransformationWrapperController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$transformations' => '@transformations',
                '$relation' => '@relation',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\UserPasswordController::class => [
            'class' => PhpMyAdmin\Controllers\UserPasswordController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$userPassword' => '@user_password',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\VersionCheckController::class => [
            'class' => PhpMyAdmin\Controllers\VersionCheckController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
            ],
        ],
        PhpMyAdmin\Controllers\ViewCreateController::class => [
            'class' => PhpMyAdmin\Controllers\ViewCreateController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$dbi' => '@dbi',
            ],
        ],
        PhpMyAdmin\Controllers\ViewOperationsController::class => [
            'class' => PhpMyAdmin\Controllers\ViewOperationsController::class,
            'arguments' => [
                '$response' => '@response',
                '$template' => '@template',
                '$operations' => '@operations',
                '$dbi' => '@dbi',
            ],
        ],
    ],
];
