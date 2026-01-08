<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Container;

use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

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

    /** @param class-string $service */
    #[DataProvider('servicesProvider')]
    public function testContainerEntries(string $service): void
    {
        Current::$lang = 'en';
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $container = ContainerBuilder::getContainer();
        self::assertInstanceOf($service, $container->get($service));
        ContainerBuilder::$container = null;
    }

    /** @return array<int, array<int, class-string>> */
    public static function servicesProvider(): array
    {
        /** @psalm-var array<class-string, mixed> $services */
        $services = include ROOT_PATH . 'app/services.php';
        /** @psalm-var array<class-string, mixed> $controllerServices */
        $controllerServices = include ROOT_PATH . 'app/services_controllers.php';

        return array_map(
            static fn ($service) => [$service],
            array_merge(array_keys($services), array_keys($controllerServices)),
        );
    }

    public function testLoadServices(): void
    {
        $container = new SymfonyContainerBuilder();
        $loader = self::createStub(PhpFileLoader::class);
        $instanceof = [];
        $servicesConfigurator = new ServicesConfigurator($container, $loader, $instanceof);

        $services = [
            Config::class => ['class' => Config::class, 'factory' => [Config::class, 'getInstance']],
            DatabaseInterface::class => [
                'class' => DatabaseInterface::class,
                'factory' => [DatabaseInterface::class, 'getInstance'],
                'arguments' => [Config::class],
            ],
            Events::class => ['class' => Events::class, 'arguments' => [DatabaseInterface::class, Config::class]],
            FlashMessenger::class => ['class' => FlashMessenger::class],
        ];

        ContainerBuilder::loadServices($services, $servicesConfigurator);

        $definitions = $container->getDefinitions();

        self::assertArrayHasKey(Config::class, $definitions);
        self::assertSame(Config::class, $definitions[Config::class]->getClass());
        self::assertSame([Config::class, 'getInstance'], $definitions[Config::class]->getFactory());
        self::assertSame([], $definitions[Config::class]->getArguments());

        self::assertArrayHasKey(DatabaseInterface::class, $definitions);
        self::assertSame(DatabaseInterface::class, $definitions[DatabaseInterface::class]->getClass());
        self::assertSame(
            [DatabaseInterface::class, 'getInstance'],
            $definitions[DatabaseInterface::class]->getFactory(),
        );
        self::assertEquals(
            [new Reference(Config::class)],
            $definitions[DatabaseInterface::class]->getArguments(),
        );

        self::assertArrayHasKey(Events::class, $definitions);
        self::assertSame(Events::class, $definitions[Events::class]->getClass());
        self::assertNull($definitions[Events::class]->getFactory());
        self::assertEquals(
            [new Reference(DatabaseInterface::class), new Reference(Config::class)],
            $definitions[Events::class]->getArguments(),
        );

        self::assertArrayHasKey(FlashMessenger::class, $definitions);
        self::assertSame(FlashMessenger::class, $definitions[FlashMessenger::class]->getClass());
        self::assertNull($definitions[FlashMessenger::class]->getFactory());
        self::assertSame([], $definitions[FlashMessenger::class]->getArguments());
    }
}
