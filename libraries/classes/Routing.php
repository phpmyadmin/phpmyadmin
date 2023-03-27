<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use FastRoute\DataGenerator\GroupCountBased as DataGeneratorGroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as DispatcherGroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParserStd;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Controllers\Setup\MainController;
use PhpMyAdmin\Controllers\Setup\ShowConfigController;
use PhpMyAdmin\Controllers\Setup\ValidateController;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Container\ContainerInterface;

use function __;
use function array_pop;
use function explode;
use function file_exists;
use function file_put_contents;
use function htmlspecialchars;
use function implode;
use function is_array;
use function is_readable;
use function is_writable;
use function mb_strlen;
use function mb_strpos;
use function mb_strrpos;
use function mb_substr;
use function rawurldecode;
use function sprintf;
use function trigger_error;
use function urldecode;
use function var_export;

use const CACHE_DIR;
use const E_USER_WARNING;
use const ROOT_PATH;

/**
 * Class used to warm up the routing cache and manage routing.
 */
class Routing
{
    public const ROUTES_CACHE_FILE = CACHE_DIR . 'routes.cache.php';

    public static function getDispatcher(): Dispatcher
    {
        $routes = require ROOT_PATH . 'libraries/routes.php';

        return self::routesCachedDispatcher($routes);
    }

    public static function skipCache(): bool
    {
        return ($GLOBALS['cfg']['environment'] ?? '') === 'development';
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

    private static function routesCachedDispatcher(callable $routeDefinitionCallback): Dispatcher
    {
        $skipCache = self::skipCache();

        // If skip cache is enabled, do not try to read the file
        // If no cache skipping then read it and use it
        if (! $skipCache && file_exists(self::ROUTES_CACHE_FILE)) {
            /** @psalm-suppress MissingFile, UnresolvableInclude, MixedAssignment */
            $dispatchData = require self::ROUTES_CACHE_FILE;
            if (self::isRoutesCacheFileValid($dispatchData)) {
                return new DispatcherGroupCountBased($dispatchData);
            }
        }

        $routeCollector = new RouteCollector(
            new RouteParserStd(),
            new DataGeneratorGroupCountBased(),
        );
        $routeDefinitionCallback($routeCollector);

        $dispatchData = $routeCollector->getData();
        $canWriteCache = self::canWriteCache();

        // If skip cache is enabled, do not try to write it
        // If no skip cache then try to write if write is possible
        if (! $skipCache && $canWriteCache) {
            $writeWorks = self::writeCache(
                '<?php return ' . var_export($dispatchData, true) . ';',
            );
            if (! $writeWorks) {
                trigger_error(
                    sprintf(
                        __(
                            'The routing cache could not be written, '
                            . 'you need to adjust permissions on the folder/file "%s"',
                        ),
                        self::ROUTES_CACHE_FILE,
                    ),
                    E_USER_WARNING,
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
    ): void {
        $route = $request->getRoute();
        $routeInfo = $dispatcher->dispatch($request->getMethod(), rawurldecode($route));

        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            /** @var ResponseRenderer $response */
            $response = $container->get(ResponseRenderer::class);
            $response->setHttpResponseCode(404);
            echo Message::error(sprintf(
                __('Error 404! The page %s was not found.'),
                '<code>' . htmlspecialchars($route) . '</code>',
            ))->getDisplay();

            return;
        }

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            /** @var ResponseRenderer $response */
            $response = $container->get(ResponseRenderer::class);
            $response->setHttpResponseCode(405);
            echo Message::error(__('Error 405! Request method not allowed.'))->getDisplay();

            return;
        }

        if ($routeInfo[0] !== Dispatcher::FOUND) {
            return;
        }

        /** @psalm-var class-string $controllerName */
        $controllerName = $routeInfo[1];
        /** @var array<string, string> $vars */
        $vars = $routeInfo[2];

        /** @psalm-var callable(ServerRequest=, array<string, string>=):void $controller */
        $controller = $container->get($controllerName);
        $controller($request, $vars);
    }

    /** @psalm-assert-if-true array[] $dispatchData */
    private static function isRoutesCacheFileValid(mixed $dispatchData): bool
    {
        return is_array($dispatchData)
            && isset($dispatchData[1])
            && is_array($dispatchData[1])
            && isset($dispatchData[0]['GET']['/'])
            && $dispatchData[0]['GET']['/'] === HomeController::class;
    }

    public static function callSetupController(ServerRequest $request): void
    {
        $route = $request->getRoute();
        if ($route === '/setup' || $route === '/') {
            (new MainController())($request);

            return;
        }

        if ($route === '/setup/show-config') {
            (new ShowConfigController())($request);

            return;
        }

        if ($route === '/setup/validate') {
            (new ValidateController())($request);

            return;
        }

        echo (new Template())->render('error/generic', [
            'lang' => $GLOBALS['lang'] ?? 'en',
            'dir' => $GLOBALS['text_dir'] ?? 'ltr',
            'error_message' => Sanitize::sanitizeMessage(sprintf(
                __('Error 404! The page %s was not found.'),
                '[code]' . htmlspecialchars($route) . '[/code]',
            )),
        ]);
    }

    /**
     * PATH_INFO could be compromised if set, so remove it from PHP_SELF
     * and provide a clean PHP_SELF here
     */
    public static function getCleanPathInfo(): string
    {
        $pmaPhpSelf = Core::getenv('PHP_SELF');
        if ($pmaPhpSelf === '') {
            $pmaPhpSelf = urldecode(Core::getenv('REQUEST_URI'));
        }

        $pathInfo = Core::getenv('PATH_INFO');
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
