<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Error.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PMA\libraries\Theme;

require_once 'libraries/sanitizing.lib.php';
require_once 'test/PMATestCase.php';

/**
 * Error class testing.
 *
 * @package PhpMyAdmin-test
 */
class ErrorTest extends PMATestCase
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
        $this->object = new PMA\libraries\Error('2', 'Compile Error', 'error.txt', 15);

        $GLOBALS['pmaThemeImage'] = 'image';
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();
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
        $bt = array(array('file'=>'bt1','line'=>2, 'function'=>'bar', 'args'=>array('foo'=>$this)));
        $this->object->setBacktrace($bt);
        $bt[0]['args']['foo'] = '<Class:ErrorTest>';
        $this->assertEquals($bt, $this->object->getBacktrace());
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
     *
     * @dataProvider filePathProvider
     */
    public function testSetFile($file, $expected)
    {
        $this->object->setFile($file);
        $this->assertEquals($expected, $this->object->getFile());
    }

    /**
     * Data provider for setFile
     *
     * @return array
     */
    public function filePathProvider()
    {
        return array(
            array('./ChangeLog', './ChangeLog'),
            array(__FILE__, './test/classes/ErrorTest.php'),
            array('./NONEXISTING', './NONEXISTING'),
        );
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
            'PHPUnit_Framework_TestResult->run(<Class:ErrorTest>)<br />',
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

    /**
     * Test for getBacktrace
     *
     * @return void
     */
    public function testGetBacktrace()
    {
        $bt = array(
            array('file'=>'bt1','line'=>2, 'function'=>'bar', 'args'=>array('foo'=>1)),
            array('file'=>'bt2','line'=>2, 'function'=>'bar', 'args'=>array('foo'=>2)),
            array('file'=>'bt3','line'=>2, 'function'=>'bar', 'args'=>array('foo'=>3)),
            array('file'=>'bt4','line'=>2, 'function'=>'bar', 'args'=>array('foo'=>4)),
        );

        $this->object->setBacktrace($bt);

        // case: full backtrace
        $this->assertEquals(4, count($this->object->getBacktrace()));

        // case: first 2 frames
        $this->assertEquals(2, count($this->object->getBacktrace(2)));
    }
}
