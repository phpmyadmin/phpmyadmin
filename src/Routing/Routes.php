<?php

declare(strict_types=1);

namespace PhpMyAdmin\Routing;

use FastRoute\RouteCollector;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

use function assert;
use function class_exists;
use function is_string;
use function ksort;
use function realpath;
use function str_replace;
use function strlen;
use function substr;

final class Routes
{
    private static string $controllersPath = __DIR__ . '/../Controllers';
    private static string $controllersNamespace = 'PhpMyAdmin\Controllers';

    public static function collect(RouteCollector $collector): void
    {
        $basePath = realpath(self::$controllersPath);
        assert(is_string($basePath));
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS),
        );

        $routes = [];
        foreach ($iterator as $file) {
            assert($file instanceof SplFileInfo);
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $realPath = $file->getRealPath();
            assert(is_string($realPath));
            $pathFromControllersRoot = substr($realPath, strlen($basePath), -strlen('.php'));
            $className = self::$controllersNamespace . str_replace('/', '\\', $pathFromControllersRoot);
            if (! class_exists($className)) {
                continue;
            }

            $reflector = new ReflectionClass($className);
            foreach ($reflector->getAttributes(Route::class) as $attribute) {
                $route = $attribute->newInstance();
                $routes[$route->path] = [$route->methods, $route->path, $reflector->getName()];
            }
        }

        ksort($routes);
        foreach ($routes as $route) {
            $collector->addRoute(...$route);
        }
    }
}
