<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @covers \PhpMyAdmin\Bookmark
 */
class BookmarkTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
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
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE ( `user` = \'\' OR `user` = \'root\' )'
                . ' AND dbase = \'sakila\' ORDER BY label ASC',
            [['1', 'sakila', 'root', 'label', 'SELECT * FROM `actor` WHERE `actor_id` < 10;']],
            ['id', 'dbase', 'user', 'label', 'query']
        );
        $actual = Bookmark::getList(
            new BookmarkFeature(DatabaseName::fromValue('phpmyadmin'), TableName::fromValue('pma_bookmark')),
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['user'],
            'sakila'
        );
        $this->assertContainsOnlyInstancesOf(Bookmark::class, $actual);
        $this->assertAllSelectsConsumed();
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
                'phpmyadmin',
                '1'
            )
        );
        $this->assertAllSelectsConsumed();
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
        $this->assertAllSelectsConsumed();
    }
}
