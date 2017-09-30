<?php
/**
 * Tests for PhpMyAdmin\Engines\MrgMyisam
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Engines\MrgMyisam;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Engines\MrgMyisam
 *
 * @package PhpMyAdmin-test
 */
class MrgMyisamTest extends PmaTestCase
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
        $GLOBALS['server'] = 0;
        $this->object = new MrgMyisam('mrg_myisam');
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
