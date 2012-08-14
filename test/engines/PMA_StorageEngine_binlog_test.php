<?php
/**
 * Tests for PMA_StorageEngine_binlog
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/StorageEngine.class.php';
require_once 'libraries/engines/binlog.lib.php';

class PMA_StorageEngine_binlog_test extends PHPUnit_Framework_TestCase
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
        if (! defined('PMA_DRIZZLE')) {
            define('PMA_DRIZZLE', 1);
        }
        if (! function_exists('PMA_DBI_fetch_result')) {
            function PMA_DBI_fetch_result($query)
            {
                return array(
                    'dummy' => 'table1',
                    'engine' => 'table`2'
                );
            }
        }
        $this->object = $this->getMockForAbstractClass('PMA_StorageEngine_binlog', array('dummy'));
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
     */
    public function testGetMysqlHelpPage(){
        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'binary-log'
        );
    }
}
