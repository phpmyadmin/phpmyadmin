<?php
/**
 * Tests for PMA_StorageEngine_memory
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PMA\libraries\engines\Memory;

require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';


/**
 * Tests for PMA\libraries\engines\Memory
 *
 * @package PhpMyAdmin-test
 */
class MemoryTest extends PHPUnit_Framework_TestCase
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
        $this->object = new Memory('memory');
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
     *
     * @return void
     */
    public function testGetVariables()
    {
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
