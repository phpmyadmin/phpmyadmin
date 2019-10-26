<?php
/**
 * Route definition file
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use FastRoute\RouteCollector;
use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Controllers\Server\DatabasesController;
use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\Controllers\Server\SqlController;
use PhpMyAdmin\Controllers\Server\Status\StatusController;
use PhpMyAdmin\Response;

global $containerBuilder;

if (! defined('PHPMYADMIN')) {
    exit;
}

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

return function (RouteCollector $routes) use ($containerBuilder, $response) {
    $routes->addRoute(['GET', 'POST'], '[/]', function () {
        require_once ROOT_PATH . 'libraries/entry_points/home.php';
    });
    $routes->addRoute(['GET', 'POST'], '/ajax', function () {
        require_once ROOT_PATH . 'libraries/entry_points/ajax.php';
    });
    $routes->addRoute(['GET', 'POST'], '/browse_foreigners', function () {
        require_once ROOT_PATH . 'libraries/entry_points/browse_foreigners.php';
    });
    $routes->addRoute('GET', '/changelog', function () {
        require_once ROOT_PATH . 'libraries/entry_points/changelog.php';
    });
    $routes->addRoute(['GET', 'POST'], '/check_relations', function () {
        require_once ROOT_PATH . 'libraries/entry_points/chk_rel.php';
    });
    $routes->addGroup('/database', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/central_columns', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/central_columns.php';
        });
        $routes->addRoute('GET', '/data_dictionary', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/datadict.php';
        });
        $routes->addRoute(['GET', 'POST'], '/designer', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/designer.php';
        });
        $routes->addRoute(['GET', 'POST'], '/events', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/events.php';
        });
        $routes->addRoute(['GET', 'POST'], '/export', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/export.php';
        });
        $routes->addRoute(['GET', 'POST'], '/import', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/import.php';
        });
        $routes->addRoute(['GET', 'POST'], '/multi_table_query', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/multi_table_query.php';
        });
        $routes->addRoute(['GET', 'POST'], '/operations', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/operations.php';
        });
        $routes->addRoute(['GET', 'POST'], '/qbe', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/qbe.php';
        });
        $routes->addRoute(['GET', 'POST'], '/routines', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/routines.php';
        });
        $routes->addRoute(['GET', 'POST'], '/search', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/search.php';
        });
        $routes->addGroup('/sql', function (RouteCollector $routes) {
            $routes->addRoute(['GET', 'POST'], '', function () {
                require_once ROOT_PATH . 'libraries/entry_points/database/sql.php';
            });
            $routes->addRoute('POST', '/autocomplete', function () {
                require_once ROOT_PATH . 'libraries/entry_points/database/sql/autocomplete.php';
            });
            $routes->addRoute('POST', '/format', function () {
                require_once ROOT_PATH . 'libraries/entry_points/database/sql/format.php';
            });
        });
        $routes->addRoute(['GET', 'POST'], '/structure', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/structure.php';
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/tracking.php';
        });
        $routes->addRoute(['GET', 'POST'], '/triggers', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/triggers.php';
        });
    });
    $routes->addRoute(['GET', 'POST'], '/error_report', function () {
        require_once ROOT_PATH . 'libraries/entry_points/error_report.php';
    });
    $routes->addRoute(['GET', 'POST'], '/export', function () {
        require_once ROOT_PATH . 'libraries/entry_points/export.php';
    });
    $routes->addRoute(['GET', 'POST'], '/gis_data_editor', function () {
        require_once ROOT_PATH . 'libraries/entry_points/gis_data_editor.php';
    });
    $routes->addRoute(['GET', 'POST'], '/import', function () {
        require_once ROOT_PATH . 'libraries/entry_points/import.php';
    });
    $routes->addRoute('GET', '/license', function () {
        require_once ROOT_PATH . 'libraries/entry_points/license.php';
    });
    $routes->addRoute(['GET', 'POST'], '/lint', function () {
        require_once ROOT_PATH . 'libraries/entry_points/lint.php';
    });
    $routes->addRoute(['GET', 'POST'], '/logout', function () {
        require_once ROOT_PATH . 'libraries/entry_points/logout.php';
    });
    $routes->addRoute(['GET', 'POST'], '/navigation', function () {
        require_once ROOT_PATH . 'libraries/entry_points/navigation.php';
    });
    $routes->addRoute(['GET', 'POST'], '/normalization', function () {
        require_once ROOT_PATH . 'libraries/entry_points/normalization.php';
    });
    $routes->addRoute('GET', '/phpinfo', function () {
        require_once ROOT_PATH . 'libraries/entry_points/phpinfo.php';
    });
    $routes->addGroup('/preferences', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/forms', function () {
            require_once ROOT_PATH . 'libraries/entry_points/preferences/forms.php';
        });
        $routes->addRoute(['GET', 'POST'], '/manage', function () {
            require_once ROOT_PATH . 'libraries/entry_points/preferences/manage.php';
        });
        $routes->addRoute(['GET', 'POST'], '/twofactor', function () {
            require_once ROOT_PATH . 'libraries/entry_points/preferences/twofactor.php';
        });
    });
    $routes->addRoute(['GET', 'POST'], '/schema_export', function () {
        require_once ROOT_PATH . 'libraries/entry_points/schema_export.php';
    });
    $routes->addGroup('/server', function (RouteCollector $routes) use ($containerBuilder, $response) {
        $routes->addRoute(['GET', 'POST'], '/binlog', function () use ($containerBuilder, $response) {
            /** @var BinlogController $controller */
            $controller = $containerBuilder->get(BinlogController::class);
            $response->addHTML($controller->index([
                'log' => $_POST['log'] ?? null,
                'pos' => $_POST['pos'] ?? null,
                'is_full_query' => $_POST['is_full_query'] ?? null,
            ]));
        });
        $routes->addRoute('GET', '/collations', function () use ($containerBuilder, $response) {
            /** @var CollationsController $controller */
            $controller = $containerBuilder->get(CollationsController::class);
            $response->addHTML($controller->index());
        });
        $routes->addGroup('/databases', function (RouteCollector $routes) use ($containerBuilder, $response) {
            /** @var DatabasesController $controller */
            $controller = $containerBuilder->get(DatabasesController::class);
            $routes->addRoute(['GET', 'POST'], '', function () use ($response, $controller) {
                $response->addHTML($controller->index([
                    'statistics' => $_REQUEST['statistics'] ?? null,
                    'pos' => $_REQUEST['pos'] ?? null,
                    'sort_by' => $_REQUEST['sort_by'] ?? null,
                    'sort_order' => $_REQUEST['sort_order'] ?? null,
                ]));
            });
            $routes->addRoute('POST', '/create', function () use ($response, $controller) {
                $response->addJSON($controller->create([
                    'new_db' => $_POST['new_db'] ?? null,
                    'db_collation' => $_POST['db_collation'] ?? null,
                ]));
            });
            $routes->addRoute('POST', '/destroy', function () use ($response, $controller) {
                $response->addJSON($controller->destroy([
                    'drop_selected_dbs' => $_POST['drop_selected_dbs'] ?? null,
                    'selected_dbs' => $_POST['selected_dbs'] ?? null,
                ]));
            });
        });
        $routes->addGroup('/engines', function (RouteCollector $routes) use ($containerBuilder, $response) {
            /** @var EnginesController $controller */
            $controller = $containerBuilder->get(EnginesController::class);
            $routes->addRoute('GET', '', function () use ($response, $controller) {
                $response->addHTML($controller->index());
            });
            $routes->addRoute('GET', '/{engine}[/{page}]', function (array $vars) use ($response, $controller) {
                $response->addHTML($controller->show($vars));
            });
        });
        $routes->addRoute(['GET', 'POST'], '/export', function () {
            require_once ROOT_PATH . 'libraries/entry_points/server/export.php';
        });
        $routes->addRoute(['GET', 'POST'], '/import', function () {
            require_once ROOT_PATH . 'libraries/entry_points/server/import.php';
        });
        $routes->addRoute('GET', '/plugins', function () use ($containerBuilder, $response) {
            /** @var PluginsController $controller */
            $controller = $containerBuilder->get(PluginsController::class);
            $response->addHTML($controller->index());
        });
        $routes->addRoute(['GET', 'POST'], '/privileges', function () {
            require_once ROOT_PATH . 'libraries/entry_points/server/privileges.php';
        });
        $routes->addRoute(['GET', 'POST'], '/replication', function () {
            require_once ROOT_PATH . 'libraries/entry_points/server/replication.php';
        });
        $routes->addRoute(['GET', 'POST'], '/sql', function () use ($containerBuilder, $response) {
            /** @var SqlController $controller */
            $controller = $containerBuilder->get(SqlController::class);
            $response->addHTML($controller->index());
        });
        $routes->addGroup('/status', function (RouteCollector $routes) use ($containerBuilder, $response) {
            $routes->addRoute('GET', '', function () use ($containerBuilder, $response) {
                /** @var StatusController $controller */
                $controller = $containerBuilder->get(StatusController::class);
                $response->addHTML($controller->index());
            });
            $routes->addRoute('GET', '/advisor', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/status/advisor.php';
            });
            $routes->addRoute(['GET', 'POST'], '/monitor', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/status/monitor.php';
            });
            $routes->addRoute(['GET', 'POST'], '/processes', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/status/processes.php';
            });
            $routes->addRoute('GET', '/queries', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/status/queries.php';
            });
            $routes->addRoute(['GET', 'POST'], '/variables', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/status/variables.php';
            });
        });
        $routes->addRoute(['GET', 'POST'], '/user_groups', function () {
            require_once ROOT_PATH . 'libraries/entry_points/server/user_groups.php';
        });
        $routes->addRoute(['GET', 'POST'], '/variables', function () {
            require_once ROOT_PATH . 'libraries/entry_points/server/variables.php';
        });
    });
    $routes->addRoute(['GET', 'POST'], '/sql', function () {
        require_once ROOT_PATH . 'libraries/entry_points/sql.php';
    });
    $routes->addGroup('/table', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/addfield', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/addfield.php';
        });
        $routes->addRoute(['GET', 'POST'], '/change', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/change.php';
        });
        $routes->addRoute(['GET', 'POST'], '/chart', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/chart.php';
        });
        $routes->addRoute(['GET', 'POST'], '/create', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/create.php';
        });
        $routes->addRoute(['GET', 'POST'], '/export', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/export.php';
        });
        $routes->addRoute(['GET', 'POST'], '/find_replace', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/find_replace.php';
        });
        $routes->addRoute(['GET', 'POST'], '/get_field', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/get_field.php';
        });
        $routes->addRoute(['GET', 'POST'], '/gis_visualization', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/gis_visualization.php';
        });
        $routes->addRoute(['GET', 'POST'], '/import', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/import.php';
        });
        $routes->addRoute(['GET', 'POST'], '/indexes', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/indexes.php';
        });
        $routes->addRoute(['GET', 'POST'], '/operations', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/operations.php';
        });
        $routes->addRoute(['GET', 'POST'], '/recent_favorite', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/recent_favorite.php';
        });
        $routes->addRoute(['GET', 'POST'], '/relation', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/relation.php';
        });
        $routes->addRoute(['GET', 'POST'], '/replace', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/replace.php';
        });
        $routes->addRoute(['GET', 'POST'], '/row_action', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/row_action.php';
        });
        $routes->addRoute(['GET', 'POST'], '/search', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/select.php';
        });
        $routes->addRoute(['GET', 'POST'], '/sql', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/sql.php';
        });
        $routes->addRoute(['GET', 'POST'], '/structure', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/structure.php';
        });
        $routes->addRoute(['GET', 'POST'], '/tracking', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/tracking.php';
        });
        $routes->addRoute(['GET', 'POST'], '/triggers', function () {
            require_once ROOT_PATH . 'libraries/entry_points/database/triggers.php';
        });
        $routes->addRoute(['GET', 'POST'], '/zoom_select', function () {
            require_once ROOT_PATH . 'libraries/entry_points/table/zoom_select.php';
        });
    });
    $routes->addRoute('GET', '/themes', function () {
        require_once ROOT_PATH . 'libraries/entry_points/themes.php';
    });
    $routes->addGroup('/transformation', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/overview', function () {
            require_once ROOT_PATH . 'libraries/entry_points/transformation/overview.php';
        });
        $routes->addRoute(['GET', 'POST'], '/wrapper', function () {
            require_once ROOT_PATH . 'libraries/entry_points/transformation/wrapper.php';
        });
    });
    $routes->addRoute(['GET', 'POST'], '/user_password', function () {
        require_once ROOT_PATH . 'libraries/entry_points/user_password.php';
    });
    $routes->addRoute(['GET', 'POST'], '/version_check', function () {
        require_once ROOT_PATH . 'libraries/entry_points/version_check.php';
    });
    $routes->addGroup('/view', function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '/create', function () {
            require_once ROOT_PATH . 'libraries/entry_points/view/create.php';
        });
        $routes->addRoute(['GET', 'POST'], '/operations', function () {
            require_once ROOT_PATH . 'libraries/entry_points/view/operations.php';
        });
    });
};
