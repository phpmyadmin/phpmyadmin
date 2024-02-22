<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Container;

use PhpMyAdmin\Container\ContainerBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContainerBuilder::class)]
final class ContainerBuilderTest extends TestCase
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
}
