<?php
/**
 * Tests for PMA_DBI_Mysqli class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Index.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/dbi/DBIMysqli.class.php';

/**
 * Tests for PMA_DBI_Mysqli class
 *
 * @package PhpMyAdmin-test
 */
class PMA_DBI_Mysqli_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {    
        $this->object = new PMA_DBI_Mysqli();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }
    
    /**
     * Test for selectDb
     *
     * @return void
     *
     * @group medium
     */
    public function testSelectDb()
    {
        //$link is empty
        $GLOBALS['userlink'] = null;
        $this->assertEquals(
            false,
            $this->object->selectDb("PMA")
        ); 
    }
    
    /**
     * Test for getClientInfo
     *
     * @return void
     *
     * @group medium
     */
    public function testGetClientInfo()
    {
        $this->assertEquals(
            mysql_get_client_info(),
            $this->object->getClientInfo()
        );
    }

    /**
     * Test for numRows
     *
     * @return void
     *
     * @group medium
     */
    public function testNumRows()
    {
        $this->assertEquals(
            0,
            $this->object->numRows(true)
        );
    }
}
