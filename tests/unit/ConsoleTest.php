<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console\Console;
use PhpMyAdmin\Console\History;
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
        $history = new History($dbi, $relation, Config::getInstance());
        $console = new Console($relation, new Template(), $bookmarkRepository, $history);
        self::assertSame(['console.js'], $console->getScripts());
    }
}
