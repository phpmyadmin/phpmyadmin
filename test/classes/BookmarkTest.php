<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PHPUnit\Framework\Attributes\CoversClass;

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
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['server'] = 1;
    }

    /**
     * Tests for Bookmark::getList()
     */
    public function testGetList(): void
    {
        $this->dummyDbi->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE (`user` = \'root\' OR `user` = \'\')'
                . ' AND dbase = \'sakila\' ORDER BY label ASC',
            [['1', 'sakila', 'root', 'label', 'SELECT * FROM `actor` WHERE `actor_id` < 10;']],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $actual = Bookmark::getList(
            new BookmarkFeature(DatabaseName::from('phpmyadmin'), TableName::from('pma_bookmark')),
            DatabaseInterface::getInstance(),
            Config::getInstance()->selectedServer['user'],
            'sakila',
        );
        $this->assertContainsOnlyInstancesOf(Bookmark::class, $actual);
        $this->dummyDbi->assertAllSelectsConsumed();
    }

    /**
     * Tests for Bookmark::get()
     */
    public function testGet(): void
    {
        $this->dummyDbi->addSelectDb('phpmyadmin');
        $this->assertNull(
            Bookmark::get(
                DatabaseInterface::getInstance(),
                Config::getInstance()->selectedServer['user'],
                DatabaseName::from('phpmyadmin'),
                '1',
            ),
        );
        $this->dummyDbi->assertAllSelectsConsumed();
    }

    /**
     * Tests for Bookmark::save()
     */
    public function testSave(): void
    {
        $bookmarkData = [
            'bkm_database' => 'phpmyadmin',
            'bkm_user' => 'root',
            'bkm_sql_query' => 'SELECT "phpmyadmin"',
            'bkm_label' => 'bookmark1',
        ];

        $bookmark = Bookmark::createBookmark(DatabaseInterface::getInstance(), $bookmarkData);
        $this->assertNotFalse($bookmark);
        $this->dummyDbi->addSelectDb('phpmyadmin');
        $this->assertFalse($bookmark->save());
        $this->dummyDbi->assertAllSelectsConsumed();
    }
}
