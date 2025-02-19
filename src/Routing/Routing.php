<?php

declare(strict_types=1);

namespace PhpMyAdmin\Routing;

use FastRoute\DataGenerator\GroupCountBased as DataGeneratorGroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as DispatcherGroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParserStd;
use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Setup\MainController;
use PhpMyAdmin\Controllers\Setup\ShowConfigController;
use PhpMyAdmin\Controllers\Setup\ValidateController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
use Psr\Container\ContainerInterface;

use function __;
use function array_pop;
use function assert;
use function explode;
use function file_exists;
use function file_put_contents;
use function htmlspecialchars;
use function implode;
use function is_readable;
use function is_writable;
use function mb_strlen;
use function mb_strpos;
use function mb_strrpos;
use function mb_substr;
use function rawurldecode;
use function sprintf;
use function urldecode;
use function var_export;

use const CACHE_DIR;

/**
 * Class used to warm up the routing cache and manage routing.
 */
class Routing
{
    /**
     * @deprecated Use {@see ServerRequest::getRoute()} instead.
     *
     * @psalm-var non-empty-string
     */
    public static string $route = '/';

    public const ROUTES_CACHE_FILE = CACHE_DIR . 'routes.cache.php';

    public static function skipCache(): bool
    {
        return Config::getInstance()->config->environment === 'development';
    }

    public static function canWriteCache(): bool
    {
        $cacheFileExists = file_exists(self::ROUTES_CACHE_FILE);
        $canWriteFile = is_writable(self::ROUTES_CACHE_FILE);
        if ($cacheFileExists && $canWriteFile) {
            return true;
        }

        // Write without read does not work, chmod 200 for example
        if (! $cacheFileExists && is_writable(CACHE_DIR) && is_readable(CACHE_DIR)) {
            return true;
        }

        return $canWriteFile;
    }

    public static function getDispatcher(): Dispatcher
    {
        $skipCache = self::skipCache();

        // If skip cache is enabled, do not try to read the file
        // If no cache skipping then read it and use it
        if (
            ! $skipCache
            && file_exists(self::ROUTES_CACHE_FILE)
            && isset($_SESSION['isRoutesCacheFileValid'])
            && $_SESSION['isRoutesCacheFileValid']
        ) {
            /** @psalm-suppress MissingFile, UnresolvableInclude, MixedAssignment */
            $dispatchData = require self::ROUTES_CACHE_FILE;

            return new DispatcherGroupCountBased($dispatchData);
        }

        $routeCollector = new RouteCollector(new RouteParserStd(), new DataGeneratorGroupCountBased());
        Routes::collect($routeCollector);

        $dispatchData = $routeCollector->getData();
        $canWriteCache = self::canWriteCache();

        // If skip cache is enabled, do not try to write it
        // If no skip cache then try to write if write is possible
        if (! $skipCache && $canWriteCache) {
            /** @psalm-suppress MissingFile, UnresolvableInclude, MixedAssignment */
            $cachedDispatchData = file_exists(self::ROUTES_CACHE_FILE) ? require self::ROUTES_CACHE_FILE : [];
            $_SESSION['isRoutesCacheFileValid'] = $dispatchData === $cachedDispatchData;
            if (
                ! $_SESSION['isRoutesCacheFileValid']
                && ! self::writeCache(sprintf('<?php return %s;', var_export($dispatchData, true)))
            ) {
                $_SESSION['isRoutesCacheFileValid'] = false;
                ErrorHandler::getInstance()->addUserError(
                    sprintf(
                        __(
                            'The routing cache could not be written, '
                            . 'you need to adjust permissions on the folder/file "%s"',
                        ),
                        self::ROUTES_CACHE_FILE,
                    ),
                );
            }
        }

        return new DispatcherGroupCountBased($dispatchData);
    }

    public static function writeCache(string $cacheContents): bool
    {
        return @file_put_contents(self::ROUTES_CACHE_FILE, $cacheContents) !== false;
    }

    /**
     * Call associated controller for a route using the dispatcher
     */
    public static function callControllerForRoute(
        ServerRequest $request,
        Dispatcher $dispatcher,
        ContainerInterface $container,
        ResponseFactory $responseFactory,
    ): Response {
        $route = $request->getRoute();
        $routeInfo = $dispatcher->dispatch($request->getMethod(), rawurldecode($route));

        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            $response = $responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);

            return $response->write(Message::error(sprintf(
                __('Error 404! The page %s was not found.'),
                '<code>' . htmlspecialchars($route) . '</code>',
            ))->getDisplay());
        }

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            $response = $responseFactory->createResponse(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);

            return $response->write(Message::error(__('Error 405! Request method not allowed.'))->getDisplay());
        }

        if ($routeInfo[0] !== Dispatcher::FOUND) {
            return $responseFactory->createResponse(StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        /** @psalm-var class-string<InvocableController> $controllerName */
        $controllerName = $routeInfo[1];

        $controller = $container->get($controllerName);
        assert($controller instanceof InvocableController);

        return $controller($request->withAttribute('routeVars', $routeInfo[2]));
    }

    public static function callSetupController(ServerRequest $request, ResponseFactory $responseFactory): Response
    {
        $route = $request->getRoute();
        $controllerName = match ($route) {
            '/', '/setup' => MainController::class,
            '/setup/show-config' => ShowConfigController::class,
            '/setup/validate' => ValidateController::class,
            default => null,
        };

        $container = ContainerBuilder::getContainer();
        if ($controllerName === null) {
            $template = $container->get('template');
            assert($template instanceof Template);
            $response = $responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);

            return $response->write($template->render('error/generic', [
                'lang' => Current::$lang,
                'error_message' => Sanitize::convertBBCode(sprintf(
                    __('Error 404! The page %s was not found.'),
                    '[code]' . htmlspecialchars($route) . '[/code]',
                )),
            ]));
        }

        $controller = $container->get($controllerName);
        assert($controller instanceof InvocableController);

        return $controller($request);
    }

    /**
     * PATH_INFO could be compromised if set, so remove it from PHP_SELF
     * and provide a clean PHP_SELF here
     */
    public static function getCleanPathInfo(): string
    {
        $pmaPhpSelf = Core::getEnv('PHP_SELF');
        if ($pmaPhpSelf === '') {
            $pmaPhpSelf = urldecode(Core::getEnv('REQUEST_URI'));
        }

        $pathInfo = Core::getEnv('PATH_INFO');
        if ($pathInfo !== '' && $pmaPhpSelf !== '') {
            $questionPos = mb_strpos($pmaPhpSelf, '?');
            if ($questionPos != false) {
                $pmaPhpSelf = mb_substr($pmaPhpSelf, 0, $questionPos);
            }

            $pathInfoPos = mb_strrpos($pmaPhpSelf, $pathInfo);
            if ($pathInfoPos !== false) {
                $pathInfoPart = mb_substr($pmaPhpSelf, $pathInfoPos, mb_strlen($pathInfo));
                if ($pathInfoPart === $pathInfo) {
                    $pmaPhpSelf = mb_substr($pmaPhpSelf, 0, $pathInfoPos);
                }
            }
        }

        $path = [];
        foreach (explode('/', $pmaPhpSelf) as $part) {
            // ignore parts that have no value
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part !== '..') {
                // cool, we found a new part
                $path[] = $part;
            } elseif ($path !== []) {
                // going back up? sure
                array_pop($path);
            }

            // Here we intentionall ignore case where we go too up
            // as there is nothing sane to do
        }

        /** TODO: Do we really need htmlspecialchars here? */
        return htmlspecialchars('/' . implode('/', $path));
    }
}
