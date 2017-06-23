<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Bookmark class
 *
 * @package PhpMyAdmin-test
 */
use PhpMyAdmin\Bookmark;

require_once 'libraries/database_interface.inc.php';
require_once 'libraries/relation.lib.php';

/**
 * Tests for Bookmark class
 *
 * @package PhpMyAdmin-test
 */
class BookmarkTest extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $GLOBALS['cfg']['Server']['bookmarktable'] = 'pma_bookmark';
        $GLOBALS['server'] = 1;
    }

    /**
     * Tests for Bookmark:getParams()
     *
     * @return void
     */
    public function testGetParams()
    {
        $this->assertEquals(
            false,
            Bookmark::getParams()
        );
    }

    /**
     * Tests for Bookmark::getList()
     *
     * @return void
     */
    public function testGetList()
    {
        $this->assertEquals(
            array(),
            Bookmark::getList('phpmyadmin')
        );
    }

    /**
     * Tests for Bookmark::get()
     *
     * @return void
     */
    public function testGet()
    {
        $this->assertNull(
            Bookmark::get('phpmyadmin', '1')
        );
    }

    /**
     * Tests for Bookmark::save()
     *
     * @return void
     */
    public function testSave()
    {
        $bookmarkData = array(
            'bkm_database' => 'phpmyadmin',
            'bkm_user' => 'root',
            'bkm_sql_query' => 'SELECT "phpmyadmin"',
            'bkm_label' => 'bookmark1',
        );

        $bookmark = Bookmark::createBookmark($bookmarkData);
        $this->assertfalse($bookmark->save());
    }
}
