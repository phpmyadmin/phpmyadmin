<?php

declare(strict_types=1);

namespace PhpMyAdmin\Routing;

use FastRoute\DataGenerator\GroupCountBased as DataGeneratorGroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as DispatcherGroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParserStd;
use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Controllers\Setup\MainController;
use PhpMyAdmin\Controllers\Setup\ShowConfigController;
use PhpMyAdmin\Controllers\Setup\ValidateController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
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
        return (Config::getInstance()->settings['environment'] ?? '') === 'development';
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
        if (! $skipCache && file_exists(self::ROUTES_CACHE_FILE)) {
            /** @psalm-suppress MissingFile, UnresolvableInclude, MixedAssignment */
            $dispatchData = require self::ROUTES_CACHE_FILE;
            if (self::isRoutesCacheFileValid($dispatchData)) {
                return new DispatcherGroupCountBased($dispatchData);
            }
        }

        $routeCollector = new RouteCollector(new RouteParserStd(), new DataGeneratorGroupCountBased());
        Routes::collect($routeCollector);

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
        ResponseFactory $responseFactory,
    ): Response|null {
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

        /** @psalm-var class-string $controllerName */
        $controllerName = $routeInfo[1];

        /** @psalm-var callable(ServerRequest): (Response|null) $controller */
        $controller = $container->get($controllerName);

        return $controller($request->withAttribute('routeVars', $routeInfo[2]));
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

    public static function callSetupController(ServerRequest $request, ResponseFactory $responseFactory): Response
    {
        $route = $request->getRoute();
        $template = new Template();
        if ($route === '/setup' || $route === '/') {
            $dbi = DatabaseInterface::getInstance();
            $relation = new Relation($dbi);
            $console = new Console($relation, $template, new BookmarkRepository($dbi, $relation));

            return (new MainController($responseFactory, $template, $console))($request);
        }

        if ($route === '/setup/show-config') {
            return (new ShowConfigController())($request);
        }

        if ($route === '/setup/validate') {
            return (new ValidateController($responseFactory))($request);
        }

        $response = $responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);

        return $response->write($template->render('error/generic', [
            'lang' => $GLOBALS['lang'] ?? 'en',
            'dir' => LanguageManager::$textDir,
            'error_message' => Sanitize::convertBBCode(sprintf(
                __('Error 404! The page %s was not found.'),
                '[code]' . htmlspecialchars($route) . '[/code]',
            )),
        ]));
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
