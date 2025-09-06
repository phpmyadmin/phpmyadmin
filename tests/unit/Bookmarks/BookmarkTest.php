<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Bookmarks;

use PhpMyAdmin\Bookmarks\Bookmark;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(Bookmark::class)]
class BookmarkTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $config = Config::getInstance();
        $config->selectedServer['user'] = 'root';
        $config->selectedServer['pmadb'] = 'phpmyadmin';
        $config->selectedServer['bookmarktable'] = 'pma_bookmark';
        $config->settings['MaxCharactersInDisplayedSQL'] = 1000;
        $config->settings['ServerDefault'] = 1;

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
        $this->dummyDbi->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE (`user` = \'root\' OR `user` = \'\')'
                . ' AND dbase = \'sakila\' ORDER BY label ASC',
            [['1', 'sakila', 'root', 'label', 'SELECT * FROM `actor` WHERE `actor_id` < 10;']],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $dbi = DatabaseInterface::getInstance();
        $actual = (new BookmarkRepository($dbi, new Relation($dbi)))->getList(
            Config::getInstance()->selectedServer['user'],
            'sakila',
        );
        self::assertContainsOnlyInstancesOf(Bookmark::class, $actual);
        $this->dummyDbi->assertAllSelectsConsumed();
    }

    /**
     * Tests for BookmarkRepository::get()
     */
    public function testGet(): void
    {
        $this->dummyDbi->addResult(
            "SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE `id` = 1 AND (user = 'root' OR user = '') LIMIT 1",
            [],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $dbi = DatabaseInterface::getInstance();
        self::assertNull(
            (new BookmarkRepository($dbi, new Relation($dbi)))->get(
                Config::getInstance()->selectedServer['user'],
                1,
            ),
        );
    }

    /**
     * Tests for Bookmark::save()
     */
    public function testSave(): void
    {
        $this->dummyDbi->addResult(
            'INSERT INTO `phpmyadmin`.`pma_bookmark` (id, dbase, user, query, label)' .
            " VALUES (NULL, 'phpmyadmin', 'root', 'SELECT \\\"phpmyadmin\\\"', 'bookmark1')",
            true,
        );
        $dbi = DatabaseInterface::getInstance();
        $bookmark = (new BookmarkRepository($dbi, new Relation($dbi)))->createBookmark(
            'SELECT "phpmyadmin"',
            'bookmark1',
            'root',
            'phpmyadmin',
        );
        self::assertNotFalse($bookmark);
        self::assertTrue($bookmark->save());
    }
}
