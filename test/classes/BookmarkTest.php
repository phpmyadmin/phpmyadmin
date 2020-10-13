<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmark;

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
        parent::defineVersionConstants();
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $GLOBALS['cfg']['Server']['bookmarktable'] = 'pma_bookmark';
        $GLOBALS['server'] = 1;
    }

    /**
     * Tests for Bookmark:getParams()
     */
    public function testGetParams(): void
    {
        $this->assertFalse(
            Bookmark::getParams($GLOBALS['cfg']['Server']['user'])
        );
    }

    /**
     * Tests for Bookmark::getList()
     */
    public function testGetList(): void
    {
        $this->assertEquals(
            [],
            Bookmark::getList(
                $GLOBALS['dbi'],
                $GLOBALS['cfg']['Server']['user'],
                'phpmyadmin'
            )
        );
    }

    /**
     * Tests for Bookmark::get()
     */
    public function testGet(): void
    {
        $this->assertNull(
            Bookmark::get(
                $GLOBALS['dbi'],
                $GLOBALS['cfg']['Server']['user'],
                'phpmyadmin',
                '1'
            )
        );
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

        $bookmark = Bookmark::createBookmark(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['user'],
            $bookmarkData
        );
        $this->assertFalse($bookmark->save());
    }
}
