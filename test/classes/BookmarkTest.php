<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\Cache;

/**
 * @covers \PhpMyAdmin\Bookmark
 */
class BookmarkTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
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
     * Tests for Bookmark:getParams()
     */
    public function testGetParams(): void
    {
        $this->assertTrue(Cache::remove('Bookmark.params'), 'The cache needs to be clean');
        $this->dummyDbi->addSelectDb('phpmyadmin');
        $this->assertFalse(
            Bookmark::getParams($GLOBALS['cfg']['Server']['user'])
        );
        $this->assertAllSelectsConsumed();
    }

    /**
     * Tests for Bookmark:getParams()
     */
    public function testGetParamsFromCache(): void
    {
        $this->assertTrue(Cache::remove('Bookmark.params'), 'The cache needs to be clean');
        $this->dummyDbi->addSelectDb('phpmyadmin');
        $this->assertFalse(
            Bookmark::getParams($GLOBALS['cfg']['Server']['user'])
        );
        $this->assertAllSelectsConsumed();
        $this->assertTrue(Cache::set('Bookmark.params', ['cacheworks' => true]), 'The cache should to be filled');
        $this->assertSame(
            ['cacheworks' => true],
            Bookmark::getParams($GLOBALS['cfg']['Server']['user'])
        );
    }

    /**
     * Tests for Bookmark::getList()
     */
    public function testGetList(): void
    {
        $this->dummyDbi->addSelectDb('phpmyadmin');
        $this->assertEquals(
            [],
            Bookmark::getList(
                $GLOBALS['dbi'],
                $GLOBALS['cfg']['Server']['user'],
                'phpmyadmin'
            )
        );
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

        $bookmark = Bookmark::createBookmark($GLOBALS['dbi'], $GLOBALS['cfg']['Server']['user'], $bookmarkData);
        $this->assertNotFalse($bookmark);
        $this->dummyDbi->addSelectDb('phpmyadmin');
        $this->assertFalse($bookmark->save());
        $this->assertAllSelectsConsumed();
    }
}
