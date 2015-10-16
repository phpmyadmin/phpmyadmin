<?php
/**
 * Tests for PMA_StorageEngine_binlog
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PMA\libraries\engines\Binlog;

require_once 'libraries/database_interface.inc.php';


/**
 * Tests for PMA\libraries\engines\Binlog
 *
 * @package PhpMyAdmin-test
 */
class BinlogTest extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['server'] = 0;
        $this->object = new Binlog('binlog');
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
     * Test for getMysqlHelpPage
     *
     * @return void
     */
    public function testGetMysqlHelpPage()
    {
        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'binary-log'
        );
    }
}
