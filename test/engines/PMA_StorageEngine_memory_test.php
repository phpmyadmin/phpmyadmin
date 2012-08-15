<?php
/**
 * Tests for PMA_StorageEngine_memory
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/StorageEngine.class.php';
require_once 'libraries/engines/memory.lib.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_StorageEngine_memory_test extends PHPUnit_Framework_TestCase
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
        $this->object = $this->getMockForAbstractClass('PMA_StorageEngine_memory', array('dummy'));
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
     * Test for getVariables
     */
    public function testGetVariables(){
        $this->assertEquals(
            $this->object->getVariables(),
            array(
                'max_heap_table_size' => array(
                                            'type'  => 1,
                                         )
                )
        );
    }
}
