<?php
/**
 * Tests for Error.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/Error.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';

class PMA_Error_test extends PHPUnit_Framework_TestCase
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
        $this->object = new PMA_Error('2', 'Compile Error', 'error.txt', 15);
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
     * Test for setBacktrace
     *
     * @return void
     */
    public function testSetBacktrace()
    {
        $this->object->setBacktrace(array('bt1','bt2'));
        $this->assertEquals(array('bt1','bt2'), $this->object->getBacktrace());
    }

    /**
     * Test for setLine
     *
     * @return void
     */
    public function testSetLine()
    {
        $this->object->setLine(15);
        $this->assertEquals(15, $this->object->getLine());
    }

    /**
     * Test for setFile
     *
     * @return void
     */
    public function testSetFile()
    {
        $this->object->setFile('./pma.txt');
        $this->assertStringStartsWith('./../../', $this->object->getFile());
    }

    /**
     * Test for getHash
     *
     * @return void
     */
    public function testGetHash()
    {
        $this->assertEquals(
            1,
            preg_match('/^([a-z0-9]*)$/', $this->object->getHash())
        );
    }

    /**
     * Test for getBacktraceDisplay
     *
     * @return void
     */
    public function testGetBacktraceDisplay()
    {
        $this->assertContains(
            'PHPUnit_Framework_TestResult->run(object)<br />',
            $this->object->getBacktraceDisplay()
        );
    }

    /**
     * Test for getDisplay
     *
     * @return void
     */
    public function testGetDisplay()
    {
        $this->assertContains(
            '<div class="error"><strong>Warning</strong>',
            $this->object->getDisplay()
        );
    }

    /**
     * Test for getHtmlTitle
     *
     * @return void
     */
    public function testGetHtmlTitle()
    {
        $this->assertEquals('Warning: Compile Error', $this->object->getHtmlTitle());
    }

    /**
     * Test for getTitle
     *
     * @return void
     */
    public function testGetTitle()
    {
        $this->assertEquals('Warning: Compile Error', $this->object->getTitle());
    }
}
