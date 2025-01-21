<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Console;
use ReflectionProperty;

/**
 * @covers \PhpMyAdmin\Console
 */
class ConsoleTest extends AbstractTestCase
{
    public function testGetScripts(): void
    {
        $console = new Console();
        self::assertSame(['console.js'], $console->getScripts());
    }

    public function testSetAjax(): void
    {
        $isAjax = new ReflectionProperty(Console::class, 'isAjax');
        $isAjax->setAccessible(true);
        $console = new Console();

        self::assertFalse($isAjax->getValue($console));
        $console->setAjax(true);
        self::assertTrue($isAjax->getValue($console));
        $console->setAjax(false);
        self::assertFalse($isAjax->getValue($console));
    }
}
