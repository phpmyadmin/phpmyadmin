<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Bookmarks;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(BookmarkRepository::class)]
final class BookmarkRepositoryTest extends AbstractTestCase
{
    public function testCreateBookmark(): void
    {
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::BOOKMARK_WORK => true,
            RelationParameters::DATABASE => 'phpmyadmin',
            RelationParameters::BOOKMARK => 'pma_bookmark',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
        $dbi = $this->createDatabaseInterface();
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, new Config()));
        $bookmark = $bookmarkRepository->createBookmark('SELECT "phpmyadmin"', 'bookmark1', 'root', 'phpmyadmin');
        self::assertNotFalse($bookmark);
        self::assertSame(0, $bookmark->getId());
        self::assertSame('phpmyadmin', $bookmark->getDatabase());
        self::assertSame('root', $bookmark->getUser());
        self::assertSame('bookmark1', $bookmark->getLabel());
        self::assertSame('SELECT "phpmyadmin"', $bookmark->getQuery());
        self::assertSame(0, $bookmark->getVariableCount());
    }
}
