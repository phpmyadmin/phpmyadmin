<?php
/**
 * Tests for PMA\libraries\engines\Mrg_Myisam
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PMA\libraries\engines\Mrg_Myisam;

require_once 'libraries/database_interface.inc.php';

/**
 * Tests for PMA\libraries\engines\Mrg_Myisam
 *
 * @package PhpMyAdmin-test
 */
class Mrg_MyisamTest extends PHPUnit_Framework_TestCase
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
        $this->object = new Mrg_Myisam('mrg_myisam');
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
            'merge-storage-engine'
        );

    }
}
