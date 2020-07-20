<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use FastRoute\Dispatcher;
use Psr\Container\ContainerInterface;
use function FastRoute\cachedDispatcher;
use function htmlspecialchars;
use function mb_strlen;
use function rawurldecode;
use function sprintf;

/**
 * Class used to warm up the routing cache and manage routing.
 */
class Routing
{
    public static function getDispatcher(): Dispatcher
    {
        global $cfg;

        $routes = require ROOT_PATH . 'libraries/routes.php';

        return cachedDispatcher($routes, [
            'cacheFile' => CACHE_DIR . 'routes.cache.php',
            'cacheDisabled' => ($cfg['environment'] ?? '') === 'development',
        ]);
    }

    public static function getCurrentRoute(): string
    {
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

        return $route;
    }

    /**
     * Call associated controller for a route using the dispatcher
     */
    public static function callControllerForRoute(
        string $route,
        Dispatcher $dispatcher,
        ContainerInterface $container
    ): void {
        $routeInfo = $dispatcher->dispatch(
            $_SERVER['REQUEST_METHOD'],
            rawurldecode($route)
        );

        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            /** @var Response $response */
            $response = $container->get(Response::class);
            $response->setHttpResponseCode(404);
            echo Message::error(sprintf(
                __('Error 404! The page %s was not found.'),
                '<code>' . htmlspecialchars($route) . '</code>'
            ))->getDisplay();

            return;
        }

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            /** @var Response $response */
            $response = $container->get(Response::class);
            $response->setHttpResponseCode(405);
            echo Message::error(__('Error 405! Request method not allowed.'))->getDisplay();

            return;
        }

        if ($routeInfo[0] !== Dispatcher::FOUND) {
            return;
        }

        [$controllerName, $action] = $routeInfo[1];
        $controller = $container->get($controllerName);
        $controller->$action($routeInfo[2]);
    }
}
