<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Bookmarks;

use PhpMyAdmin\Bookmarks\Bookmark;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(Bookmark::class)]
class BookmarkTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
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

    /**
     * Tests for BookmarkRepository::getList()
     */
    public function testGetList(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE (`user` = \'root\' OR `user` = \'\')'
                . ' AND (dbase = \'sakila\' OR dbase = \'\') ORDER BY label ASC',
            [['1', 'sakila', 'root', 'label', 'SELECT * FROM `actor` WHERE `actor_id` < 10;']],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);
        $actual = $bookmarkRepository->getList($config->selectedServer['user'], 'sakila');
        self::assertContainsOnlyInstancesOf(Bookmark::class, $actual);
        $dbiDummy->assertAllSelectsConsumed();
    }

    /**
     * Tests for BookmarkRepository::get()
     */
    public function testGet(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE `id` = 1 AND (user = 'root' OR user = '') LIMIT 1",
            [],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);
        self::assertNull($bookmarkRepository->get($config->selectedServer['user'], 1));
    }

    /**
     * Tests for Bookmark::save()
     */
    public function testSave(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'INSERT INTO `phpmyadmin`.`pma_bookmark` (id, dbase, user, query, label)' .
            " VALUES (NULL, 'phpmyadmin', 'root', 'SELECT \\\"phpmyadmin\\\"', 'bookmark1')",
            true,
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $relation = new Relation($dbi, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation, $config);
        $bookmark = $bookmarkRepository->createBookmark('SELECT "phpmyadmin"', 'bookmark1', 'root', 'phpmyadmin');
        self::assertNotFalse($bookmark);
        self::assertTrue($bookmark->save());
    }
}
