<?php
/**
 * Tests for Error_Handler
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/Error_Handler.class.php';
require_once 'libraries/sanitizing.lib.php';

/**
 * Test for PMA_Error_Handler class.
 *
 * @package PhpMyAdmin-test
 */
class PMA_Error_Handler_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new PMA_Error_Handler();

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
     * Call protected functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the protected method.
     */
    private function _callProtectedFunction($name, $params)
    {
        $class = new ReflectionClass('PMA_Error_Handler');
        $method = $class->getMethod($name);
        if (! method_exists($method, 'setAccessible')) {
            $this->markTestSkipped('ReflectionClass::setAccessible not available');
        }
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Data provider for testHandleError
     *
     * @return array data for testHandleError
     */
    public function providerForTestHandleError()
    {
        return array(
            array(
                E_RECOVERABLE_ERROR,
                'Compile Error',
                'error.txt',
                12,
                'Compile Error',
                '',
            ),
            array(
                E_USER_NOTICE,
                'User notice',
                'error.txt',
                12,
                'User notice',
                'User notice',
            )
        );
    }

    /**
     * Test for getDispErrors when PHP errors are not shown
     *
     * @param integer $errno       error number
     * @param string  $errstr      error string
     * @param string  $errfile     error file
     * @param integer $errline     error line
     * @param string  $output_show expected output if showing of errors is
     *                             enabled
     * @param string  $output_hide expected output if showing of errors is
     *                             disabled
     *
     * @return void
     *
     * @dataProvider providerForTestHandleError
     */
    public function testGetDispErrorsForDisplayFalse(
        $errno, $errstr, $errfile, $errline, $output_show, $output_hide
    ) {
        $GLOBALS['cfg']['Error_Handler']['gather'] = true;
        $GLOBALS['cfg']['Error_Handler']['display'] = false;

        $this->object->handleError($errno, $errstr, $errfile, $errline);

        $output = $this->object->getDispErrors();

        if ($output_hide == '') {
            $this->assertEquals('', $output);
        } else {
            $this->assertContains($output_hide, $output);
        }
    }

    /**
     * Test for getDispErrors when PHP errors are shown
     *
     * @param integer $errno       error number
     * @param string  $errstr      error string
     * @param string  $errfile     error file
     * @param integer $errline     error line
     * @param string  $output_show expected output if showing of errors is
     *                             enabled
     * @param string  $output_hide expected output if showing of errors is
     *                             disabled
     *
     * @return void
     *
     * @dataProvider providerForTestHandleError
     */
    public function testGetDispErrorsForDisplayTrue(
        $errno, $errstr, $errfile, $errline, $output_show, $output_hide
    ) {
        $GLOBALS['cfg']['Error_Handler']['gather'] = true;
        $GLOBALS['cfg']['Error_Handler']['display'] = true;

        $this->object->handleError($errno, $errstr, $errfile, $errline);

        $this->assertContains(
            $output_show,
            $this->object->getDispErrors()
        );

    }

    /**
     * Test for checkSavedErrors
     *
     * @return void
     */
    public function testCheckSavedErrors()
    {

        $_SESSION['errors'] = array();

        $this->_callProtectedFunction(
            'checkSavedErrors',
            array()
        );
        $this->assertTrue(!isset($_SESSION['errors']));
    }

    /**
     * Test for countErrors
     *
     * @return void
     */
    public function testCountErrors()
    {

        $err = array();
        $err[] = new PMA_Error('256', 'Compile Error', 'error.txt', 15);
        $errHandler = $this->getMock('PMA_Error_Handler');
        $errHandler->expects($this->any())
            ->method('getErrors')
            ->will($this->returnValue($err));

        $this->assertEquals(
            0,
            $this->object->countErrors()
        );
    }

    /**
     * Test for countUserErrors
     *
     * @return void
     */
    public function testCountUserErrors()
    {

        $err = array();
        $err[] = new PMA_Error('256', 'Compile Error', 'error.txt', 15);
        $errHandler = $this->getMock('PMA_Error_Handler');
        $errHandler->expects($this->any())
            ->method('countErrors', 'getErrors')
            ->will($this->returnValue(1, $err));

        $this->assertEquals(
            0,
            $this->object->countUserErrors()
        );
    }

    /**
     * Test for hasUserErrors
     *
     * @return void
     */
    public function testHasUserErrors()
    {
        $this->assertFalse($this->object->hasUserErrors());
    }

    /**
     * Test for hasErrors
     *
     * @return void
     */
    public function testHasErrors()
    {
        $this->assertFalse($this->object->hasErrors());
    }

    /**
     * Test for countDisplayErrors
     *
     * @return void
     */
    public function testCountDisplayErrorsForDisplayTrue()
    {
        $GLOBALS['cfg']['Error_Handler']['display'] = true;
        $this->assertEquals(
            0,
            $this->object->countDisplayErrors()
        );
    }

    /**
     * Test for countDisplayErrors
     *
     * @return void
     */
    public function testCountDisplayErrorsForDisplayFalse()
    {
        $GLOBALS['cfg']['Error_Handler']['display'] = false;
        $this->assertEquals(
            0,
            $this->object->countDisplayErrors()
        );
    }

    /**
     * Test for hasDisplayErrors
     *
     * @return void
     */
    public function testHasDisplayErrors()
    {
        $this->assertFalse($this->object->hasDisplayErrors());
    }
}
