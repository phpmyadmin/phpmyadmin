<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console;
use PhpMyAdmin\Template;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(Console::class)]
class ConsoleTest extends AbstractTestCase
{
    public function testGetScripts(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $console = new Console(new Relation($GLOBALS['dbi']), new Template());
        $this->assertEquals(['console.js'], $console->getScripts());
    }

    public function testSetAjax(): void
    {
        $isAjax = new ReflectionProperty(Console::class, 'isAjax');
        $console = new Console(new Relation($this->createDatabaseInterface()), new Template());

        $this->assertFalse($isAjax->getValue($console));
        $console->setAjax(true);
        $this->assertTrue($isAjax->getValue($console));
        $console->setAjax(false);
        $this->assertFalse($isAjax->getValue($console));
    }
}
