<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

/** @covers \PhpMyAdmin\Bookmark */
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
        $GLOBALS['dbi'] = $this->dbi;
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $GLOBALS['cfg']['Server']['bookmarktable'] = 'pma_bookmark';
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
            new BookmarkFeature(DatabaseName::fromValue('phpmyadmin'), TableName::fromValue('pma_bookmark')),
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['user'],
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
                $GLOBALS['dbi'],
                $GLOBALS['cfg']['Server']['user'],
                DatabaseName::fromValue('phpmyadmin'),
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

        $bookmark = Bookmark::createBookmark($GLOBALS['dbi'], $bookmarkData);
        $this->assertNotFalse($bookmark);
        $this->dummyDbi->addSelectDb('phpmyadmin');
        $this->assertFalse($bookmark->save());
        $this->dummyDbi->assertAllSelectsConsumed();
    }
}
