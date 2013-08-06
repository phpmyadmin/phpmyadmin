<?php
/**
 * Tests for PMA_DBI_Drizzle class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test and mock Drizzle class.
 */
/**
 * Drizzle for Mock drizzle class
 *
 * this class is for Mock Drizzle
 *
 * @package PhpMyAdmin-test
 */
class Drizzle
{

}

/**
 * function to return drizzle_version
 *
 * @return string
 */
function drizzle_version()
{
    return "1.0.0";
}

require_once 'libraries/Util.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Index.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/dbi/DBIDrizzle.class.php';

/**
 * Tests for PMA_DBI_Drizzle class
 *
 * @package PhpMyAdmin-test
 */
class PMA_DBI_Drizzle_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new PMA_DBI_Drizzle();
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
        //does not support multi_queries
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
    public function testrSelectDb()
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
    public function testrMoreResults()
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
    public function testrGetClientInfo()
    {
        $drizzle_info = 'libdrizzle (Drizzle ' . drizzle_version() . ')';
        $this->assertEquals(
            $drizzle_info,
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
    public function testrNumRows()
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
    public function testrStoreResult()
    {
        $this->assertEquals(
            false,
            $this->object->storeResult()
        );
    }
}
