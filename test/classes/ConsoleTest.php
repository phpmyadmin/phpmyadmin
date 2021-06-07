<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Console;

/**
 * @covers \PhpMyAdmin\Console
 */
class ConsoleTest extends AbstractTestCase
{
    public function testGetScripts(): void
    {
        $console = new Console();
        $this->assertEquals(['console.js'], $console->getScripts());
    }
}
