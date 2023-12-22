<?php

declare(strict_types=1);

namespace PhpMyAdmin\Container;

use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

use const ROOT_PATH;

final class ContainerBuilder
{
    public static ContainerInterface|null $container = null;

    public static function getContainer(): ContainerInterface
    {
        if (self::$container !== null) {
            return self::$container;
        }

        self::$container = self::getSymfonyContainer();

        return self::$container;
    }

    private static function getSymfonyContainer(): ContainerInterface
    {
        $container = new SymfonyContainerBuilder();
        $loader = new PhpFileLoader($container, new FileLocator(ROOT_PATH . 'app'));
        $loader->load('services_loader.php');

        return $container;
    }
}
