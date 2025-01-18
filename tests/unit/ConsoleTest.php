<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Template;
use PHPUnit\Framework\Attributes\CoversClass;

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
        self::assertSame(['console.js'], $console->getScripts());
    }
}
