<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Container;

use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_keys;
use function array_map;
use function array_merge;

#[CoversClass(ContainerBuilder::class)]
final class ContainerBuilderTest extends AbstractTestCase
{
    public function testGetContainer(): void
    {
        ContainerBuilder::$container = null;
        $container = ContainerBuilder::getContainer();
        self::assertSame($container, ContainerBuilder::getContainer());
        ContainerBuilder::$container = null;
        self::assertNotSame($container, ContainerBuilder::getContainer());
        ContainerBuilder::$container = null;
    }

    #[DataProvider('servicesProvider')]
    public function testContainerEntries(string $service): void
    {
        Current::$lang = 'en';
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $container = ContainerBuilder::getContainer();
        self::assertNotNull($container->get($service));
        ContainerBuilder::$container = null;
    }

    /** @return array<int, array<int, string>> */
    public static function servicesProvider(): array
    {
        /** @psalm-var array{services: array<string, mixed>} $services */
        $services = include ROOT_PATH . 'app/services.php';
        /** @psalm-var array{services: array<string, mixed>} $controllerServices */
        $controllerServices = include ROOT_PATH . 'app/services_controllers.php';

        return array_map(
            static fn ($service) => [$service],
            array_merge(array_keys($services['services']), array_keys($controllerServices['services'])),
        );
    }
}
