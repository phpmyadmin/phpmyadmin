<?php
/**
 * Main loader script
 */
declare(strict_types=1);

use FastRoute\Dispatcher;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use function FastRoute\cachedDispatcher;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

global $containerBuilder, $route, $cfg;

/** @var string $route */
$route = $_GET['route'] ?? $_POST['route'] ?? '/';

/**
 * See FAQ 1.34.
 *
 * @see https://docs.phpmyadmin.net/en/latest/faq.html#faq1-34
 */
if (($route === '/' || $route === '') && isset($_GET['db']) && mb_strlen($_GET['db']) !== 0) {
    $route = '/database/structure';
    if (isset($_GET['table']) && mb_strlen($_GET['table']) !== 0) {
        $route = '/sql';
    }
}

if ($route === '/import-status') {
    // phpcs:disable PSR1.Files.SideEffects
    define('PMA_MINIMUM_COMMON', true);
    // phpcs:enable
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$routes = require ROOT_PATH . 'libraries/routes.php';
/** @var \PhpMyAdmin\Config|null $config */
$config = $GLOBALS['PMA_Config'];
$dispatcher = cachedDispatcher($routes, [
    'cacheFile' => $config !== null ? $config->getTempDir('routing') . '/routes.cache' : null,
    'cacheDisabled' => ($cfg['environment'] ?? '') === 'development',
]);
$routeInfo = $dispatcher->dispatch(
    $_SERVER['REQUEST_METHOD'],
    rawurldecode($route)
);
if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
    /** @var Response $response */
    $response = $containerBuilder->get(Response::class);
    $response->setHttpResponseCode(404);
    Message::error(sprintf(
        __('Error 404! The page %s was not found.'),
        '<code>' . $route . '</code>'
    ))->display();
} elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
    /** @var Response $response */
    $response = $containerBuilder->get(Response::class);
    $response->setHttpResponseCode(405);
    Message::error(__('Error 405! Request method not allowed.'))->display();
} elseif ($routeInfo[0] === Dispatcher::FOUND) {
    [$controllerName, $action] = $routeInfo[1];
    $controller = $containerBuilder->get($controllerName);
    $controller->$action($routeInfo[2]);
}
