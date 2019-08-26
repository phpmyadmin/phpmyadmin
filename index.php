<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main loader script
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use FastRoute\Dispatcher;
use PhpMyAdmin\Message;

use function FastRoute\simpleDispatcher;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $route;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var string $route */
$route = $_GET['route'] ?? $_POST['route'] ?? '/';

/**
 * See FAQ 1.34.
 * @see https://docs.phpmyadmin.net/en/latest/faq.html#faq1-34
 */
if (($route === '/' || $route === '') && isset($_GET['db']) && mb_strlen($_GET['db']) !== 0) {
    $route = '/database/structure';
    if (isset($_GET['table']) && mb_strlen($_GET['table']) !== 0) {
        $route = '/sql';
    }
}

$routes = require ROOT_PATH . 'libraries/routes.php';
$dispatcher = simpleDispatcher($routes);
$routeInfo = $dispatcher->dispatch(
    $_SERVER['REQUEST_METHOD'],
    rawurldecode($route)
);
if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
    Message::error(sprintf(
        __('Error 404! The page %s was not found.'),
        '<code>' . ($route) . '</code>'
    ))->display();
} elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
    Message::error(__('Error 405! Request method not allowed.'))->display();
} elseif ($routeInfo[0] === Dispatcher::FOUND) {
    $handler = $routeInfo[1];
    $handler($routeInfo[2]);
}
