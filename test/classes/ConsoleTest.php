<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console;
use PhpMyAdmin\Template;

/** @covers \PhpMyAdmin\Console */
class ConsoleTest extends AbstractTestCase
{
    public function testGetScripts(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $console = new Console(new Relation($GLOBALS['dbi']), new Template());
        $this->assertEquals(['console.js'], $console->getScripts());
    }
}
