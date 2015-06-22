<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for bookmark.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/relation.lib.php';

/**
 * tests for bookmark.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_Bookmark_Test extends PHPUnit_Framework_TestCase
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

        include_once 'libraries/bookmark.lib.php';
    }

    /**
     * Test for PMA_Bookmark_getParams
     *
     * @return void
     */
    public function testGetParams()
    {
        $this->assertEquals(
            false,
            PMA_Bookmark_getParams()
        );
    }

    /**
     * Test for PMA_Bookmark_getList
     *
     * @return void
     */
    public function testGetList()
    {
        $this->assertEquals(
            array(),
            PMA_Bookmark_getList('phpmyadmin')
        );
    }

    /**
     * Test for PMA_Bookmark_get
     *
     * @return void
     */
    public function testGet()
    {
        $this->assertEquals(
            '',
            PMA_Bookmark_get('phpmyadmin', '1')
        );
    }

    /**
     * Test for PMA_Bookmark_save
     *
     * @return void
     */
    public function testSave()
    {
        $bookmark = array(
            'dbase' => 'phpmyadmin',
            'user' => 'phpmyadmin',
            'query' => 'SELECT "phpmyadmin"',
            'label' => 'phpmyadmin',
        );

        $this->assertfalse(PMA_Bookmark_save($bookmark));
    }

    /**
     * Test for PMA_Bookmark_delete
     *
     * @return void
     */
    public function testDelete()
    {
        $this->assertFalse(
            PMA_Bookmark_delete('1')
        );
    }
}
