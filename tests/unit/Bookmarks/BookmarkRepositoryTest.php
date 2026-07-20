<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Bookmarks;

use PhpMyAdmin\Bookmarks\Bookmark;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

use function array_map;

#[CoversClass(BookmarkRepository::class)]
final class BookmarkRepositoryTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::BOOKMARK_WORK => true,
            RelationParameters::DATABASE => 'phpmyadmin',
            RelationParameters::BOOKMARK => 'pma_bookmark',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
    }

    public function testCreateBookmark(): void
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface();
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);
        $bookmark = $bookmarkRepository->createBookmark('SELECT "phpmyadmin"', 'bookmark1', 'root', 'phpmyadmin');
        self::assertNotFalse($bookmark);
        self::assertSame(0, $bookmark->getId());
        self::assertSame('phpmyadmin', $bookmark->getDatabase());
        self::assertSame('root', $bookmark->getUser());
        self::assertSame('bookmark1', $bookmark->getLabel());
        self::assertSame('SELECT "phpmyadmin"', $bookmark->getQuery());
        self::assertSame(0, $bookmark->getVariableCount());
    }

    /**
     * A bookmark created without a database context (dbase = '', e.g. via the
     * Console, which does not require a database to be selected) must still
     * show up when browsing bookmarks for a specific database — the query
     * filter has to match the requested database OR an empty dbase.
     */
    public function testGetListIncludesBookmarksWithoutDatabase(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE (`user` = \'root\' OR `user` = \'\')'
                . ' AND (dbase = \'sakila\' OR dbase = \'\') ORDER BY label ASC',
            [
                ['1', 'sakila', 'root', 'per-db', 'SELECT 1;'],
                ['2', '', 'root', 'global', 'SELECT 2;'],
            ],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);

        $actual = $bookmarkRepository->getList('root', 'sakila');

        self::assertSame(['per-db', 'global'], array_map(
            static fn (Bookmark $bookmark): string => $bookmark->getLabel(),
            $actual,
        ));
        $dbiDummy->assertAllSelectsConsumed();
    }

    public function testGetListWithoutDatabaseFilter(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE (`user` = \'root\' OR `user` = \'\') ORDER BY label ASC',
            [['1', 'sakila', 'root', 'label', 'SELECT 1;']],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);

        $actual = $bookmarkRepository->getList('root');

        self::assertCount(1, $actual);
        $dbiDummy->assertAllSelectsConsumed();
    }

    public function testGetListWithSharedBookmarksDisallowedUsesExactUserMatch(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE (`user` = \'root\')'
                . ' AND (dbase = \'sakila\' OR dbase = \'\') ORDER BY label ASC',
            [],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $config->config = new Settings(['AllowSharedBookmarks' => false]);
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);

        $actual = $bookmarkRepository->getList('root', 'sakila');

        self::assertSame([], $actual);
        $dbiDummy->assertAllSelectsConsumed();
    }

    /**
     * get() must also match bookmarks shared by another user (user = ''),
     * the same fallback behavior as getList()/getByLabel() — this is the
     * positive-match case that BookmarkTest::testGet() (empty result) does
     * not exercise.
     */
    public function testGetMatchesSharedBookmark(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE `id` = 1 AND (user = 'root' OR user = '') LIMIT 1",
            [['1', 'sakila', '', 'shared', 'SELECT * FROM `actor` LIMIT 10;']],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);

        $bookmark = $bookmarkRepository->get('root', 1);

        self::assertNotNull($bookmark);
        self::assertSame('', $bookmark->getUser());
        self::assertSame('SELECT * FROM `actor` LIMIT 10;', $bookmark->getQuery());
        $dbiDummy->assertAllSelectsConsumed();
    }

    public function testGetWithSharedBookmarksDisallowedUsesExactUserMatch(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE `id` = 1 AND (user = 'root') LIMIT 1",
            [],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $config->config = new Settings(['AllowSharedBookmarks' => false]);
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);

        $bookmark = $bookmarkRepository->get('root', 1);

        self::assertNull($bookmark);
        $dbiDummy->assertAllSelectsConsumed();
    }

    /**
     * getByLabel() backs the "default browse query" feature (bookmark named
     * after a table). It must honor AllowSharedBookmarks the same way
     * get()/getList() do — a bookmark shared by another user (user = '')
     * has to match, not just the exact current user.
     */
    public function testGetByLabelMatchesSharedBookmark(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE `label` = \'actor\''
                . ' AND dbase = \'sakila\' AND (user = \'root\' OR user = \'\') LIMIT 1',
            [['1', 'sakila', '', 'actor', 'SELECT * FROM `actor` LIMIT 10;']],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);

        $bookmark = $bookmarkRepository->getByLabel('root', DatabaseName::from('sakila'), 'actor');

        self::assertNotNull($bookmark);
        self::assertSame('', $bookmark->getUser());
        self::assertSame('SELECT * FROM `actor` LIMIT 10;', $bookmark->getQuery());
        $dbiDummy->assertAllSelectsConsumed();
    }

    public function testGetByLabelWithSharedBookmarksDisallowedUsesExactUserMatch(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE `label` = \'actor\''
                . ' AND dbase = \'sakila\' AND (user = \'root\') LIMIT 1',
            [],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $config->config = new Settings(['AllowSharedBookmarks' => false]);
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);

        $bookmark = $bookmarkRepository->getByLabel('root', DatabaseName::from('sakila'), 'actor');

        self::assertNull($bookmark);
        $dbiDummy->assertAllSelectsConsumed();
    }

    public function testGetByLabelReturnsNullWhenNotFound(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE `label` = \'missing\''
                . ' AND dbase = \'sakila\' AND (user = \'root\' OR user = \'\') LIMIT 1',
            [],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);

        $bookmark = $bookmarkRepository->getByLabel('root', DatabaseName::from('sakila'), 'missing');

        self::assertNull($bookmark);
        $dbiDummy->assertAllSelectsConsumed();
    }
}
