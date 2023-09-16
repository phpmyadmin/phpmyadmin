<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Template;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(Console::class)]
class ConsoleTest extends AbstractTestCase
{
    public function testGetScripts(): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $dbi = DatabaseInterface::getInstance();
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $console = new Console($relation, new Template(), $bookmarkRepository);
        $this->assertEquals(['console.js'], $console->getScripts());
    }

    public function testSetAjax(): void
    {
        $isAjax = new ReflectionProperty(Console::class, 'isAjax');
        $dbi = $this->createDatabaseInterface();
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $console = new Console($relation, new Template(), $bookmarkRepository);

        $this->assertFalse($isAjax->getValue($console));
        $console->setAjax(true);
        $this->assertTrue($isAjax->getValue($console));
        $console->setAjax(false);
        $this->assertFalse($isAjax->getValue($console));
    }
}
