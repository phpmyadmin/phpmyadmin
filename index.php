<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main loader script
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use PhpMyAdmin\Core;
use PhpMyAdmin\Message;

use function FastRoute\simpleDispatcher;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

if (isset($_GET['route']) || isset($_POST['route'])) {
    $dispatcher = simpleDispatcher(function (RouteCollector $routes) {
        $routes->addRoute(['GET', 'POST'], '[/]', function () {
            require_once ROOT_PATH . 'libraries/entry_points/home.php';
        });
        $routes->addRoute(['GET', 'POST'], '/ajax', function () {
            require_once ROOT_PATH . 'libraries/entry_points/ajax.php';
        });
        $routes->addRoute(['GET', 'POST'], '/browse_foreigners', function () {
            require_once ROOT_PATH . 'libraries/entry_points/browse_foreigners.php';
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
        $routes->addGroup('/server', function (RouteCollector $routes) {
            $routes->addRoute(['GET', 'POST'], '/binlog', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/binlog.php';
            });
            $routes->addRoute('GET', '/collations', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/collations.php';
            });
            $routes->addRoute(['GET', 'POST'], '/databases', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/databases.php';
            });
            $routes->addRoute('GET', '/engines', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/engines.php';
            });
            $routes->addRoute('GET', '/plugins', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/plugins.php';
            });
            $routes->addRoute(['GET', 'POST'], '/privileges', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/privileges.php';
            });
            $routes->addRoute(['GET', 'POST'], '/replication', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/replication.php';
            });
            $routes->addRoute(['GET', 'POST'], '/sql', function () {
                require_once ROOT_PATH . 'libraries/entry_points/server/sql.php';
            });
            $routes->addGroup('/status', function (RouteCollector $routes) {
                $routes->addRoute('GET', '', function () {
                    require_once ROOT_PATH . 'libraries/entry_points/server/status.php';
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
            $routes->addRoute(['GET', 'POST'], '/change', function () {
                require_once ROOT_PATH . 'libraries/entry_points/table/change.php';
            });
            $routes->addRoute(['GET', 'POST'], '/find_replace', function () {
                require_once ROOT_PATH . 'libraries/entry_points/table/find_replace.php';
            });
            $routes->addRoute(['GET', 'POST'], '/operations', function () {
                require_once ROOT_PATH . 'libraries/entry_points/table/operations.php';
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
    });
    $routeInfo = $dispatcher->dispatch(
        $_SERVER['REQUEST_METHOD'],
        rawurldecode($_GET['route'] ?? $_POST['route'])
    );
    if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
        Message::error(sprintf(
            __('Error 404! The page %s was not found.'),
            '<code>' . ($_GET['route'] ?? $_POST['route']) . '</code>'
        ))->display();
        exit;
    } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
        Message::error(__('Error 405! Request method not allowed.'))->display();
        exit;
    } elseif ($routeInfo[0] === Dispatcher::FOUND) {
        $handler = $routeInfo[1];
        $handler($routeInfo[2]);
        exit;
    }
}

/**
 * pass variables to child pages
 */
$drops = [
    'lang',
    'server',
    'collation_connection',
    'db',
    'table',
];
foreach ($drops as $each_drop) {
    if (array_key_exists($each_drop, $_GET)) {
        unset($_GET[$each_drop]);
    }
}
unset($drops, $each_drop);

/**
 * Black list of all scripts to which front-end must submit data.
 * Such scripts must not be loaded on home page.
 */
$target_blacklist =  [
    'import.php',
    'export.php',
];

// If we have a valid target, let's load that script instead
if (! empty($_REQUEST['target'])
    && is_string($_REQUEST['target'])
    && 0 !== strpos($_REQUEST['target'], "index")
    && ! in_array($_REQUEST['target'], $target_blacklist)
    && Core::checkPageValidity($_REQUEST['target'], [], true)
) {
    include ROOT_PATH . $_REQUEST['target'];
    exit;
}

require_once ROOT_PATH . 'libraries/entry_points/home.php';
