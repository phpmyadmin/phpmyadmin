<?php
/**
 * Tests for PMA_DBI_Mysql class
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
require_once 'libraries/dbi/DBIMysql.class.php';

/**
 * Tests for PMA_DBI_Mysql class
 *
 * @package PhpMyAdmin-test
 */
class PMA_DBI_Mysql_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new PMA_DBI_Mysql();
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
     * Test for realMultiQuery
     *
     * @return void
     *
     * @group medium
     */
    public function testRealMultiQuery()
    {
        //PHP's 'mysql' extension does not support multi_queries
        $this->assertEquals(
            false,
            $this->object->realMultiQuery(null, "select * from PMA")
        ); 
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
     * Test for moreResults
     *
     * @return void
     *
     * @group medium
     */
    public function testMoreResults()
    {
        //PHP's 'mysql' extension does not support multi_queries
        $this->assertEquals(
            false,
            $this->object->moreResults()
        ); 
        //PHP's 'mysql' extension does not support multi_queries
        $this->assertEquals(
            false,
            $this->object->nextResult()
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
            false,
            $this->object->numRows(true)
        );
    }

    /**
     * Test for storeResult
     *
     * @return void
     *
     * @group medium
     */
    public function testStoreResult()
    {
        $this->assertEquals(
            false,
            $this->object->storeResult()
        );
    }
}
