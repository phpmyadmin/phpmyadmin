<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main loader script
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use FastRoute\Dispatcher;
use PhpMyAdmin\Core;
use PhpMyAdmin\Message;

use function FastRoute\simpleDispatcher;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

if (isset($_GET['route']) || isset($_POST['route'])) {
    $routes = require ROOT_PATH . 'libraries/routes.php';
    $dispatcher = simpleDispatcher($routes);
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

// If we have a valid target, let's load that script instead
if (! empty($_REQUEST['target'])
    && is_string($_REQUEST['target'])
    && 0 !== strpos($_REQUEST['target'], "index")
    && Core::checkPageValidity($_REQUEST['target'], [], true)
) {
    include ROOT_PATH . $_REQUEST['target'];
    exit;
}

require_once ROOT_PATH . 'libraries/entry_points/home.php';
