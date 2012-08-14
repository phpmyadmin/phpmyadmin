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

class PMA_Error_Handler_test extends PHPUnit_Framework_TestCase
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
        $this->object = $this->getMockForAbstractClass('PMA_Error_Handler');

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
     * Call protected functions by making the visibitlity to public.
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
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * @param integer $errno   error number
     * @param string  $errstr  error string
     * @param string  $errfile error file
     * @param integer $errline error line
     * @param $output output from the handleError method
     *
     * @dataProvider providerForTestHandleError
     */
    public function testHandleError($errno, $errstr, $errfile, $errline, $output)
    {

        $GLOBALS['cfg']['Error_Handler']['gather'] = true;

        $this->assertEquals($this->object->handleError($errno, $errstr, $errfile, $errline), $output);
    }

    /**
     * @return array data for testHandleError
     */
    public function providerForTestHandleError()
    {
        return array(
            array(
                '1024',
                'Compile Error',
                'error.txt',
                12,
                ''
            )
        );
    }

    /**
     * Test for logError
     */
//    public function testLogError(){
//
//        $error = new PMA_Error('2', 'Compile Error', 'error.txt', 15);
//
//        $this->assertTrue(
//            $this->_callProtectedFunction(
//                'logError',
//                array($error)
//            )
//        );
//    }

    /**
     * Test for getDispUserErrors
     */
    public function testGetDispUserErrors()
    {

        $this->assertEquals($this->object->getDispUserErrors(),
            '<div class="notice">Compile Error</div>'
        );
    }

    /**
     * Test for getDispErrors
     */
    public function testGetDispErrorsForDisplayFalse()
    {

        $GLOBALS['cfg']['Error_Handler']['display'] = false;
        $this->assertEquals($this->object->getDispUserErrors(),
            ''
        );
    }

    /**
     * Test for getDispErrors
     */
    public function testGetDispErrorsForDisplayTrue()
    {

        $GLOBALS['cfg']['Error_Handler']['display'] = true;

        $this->assertEquals($this->object->getDispErrors(),
            ''
        );

    }

    /**
     * Test for checkSavedErrors
     */
    public function testCheckSavedErrors()
    {

        $_SESSION['errors'] = true;

        $this->_callProtectedFunction(
            'checkSavedErrors',
            array()
        );
        $this->assertTrue(!isset($_SESSION['errors']));
    }

    /**
     * Test for countErrors
     */
    public function testCountErrors()
    {

        $err = array();
        $err[] = new PMA_Error('256', 'Compile Error', 'error.txt', 15);
        $errHandler = $this->getMock('PMA_Error_Handler');
        $errHandler->expects($this->any())
            ->method('getErrors')
            ->will($this->returnValue($err));

        $this->assertEquals($this->object->countErrors(),
            0
        );
    }

    /**
     * Test for countUserErrors
     */
    public function testCountUserErrors()
    {

        $err = array();
        $err[] = new PMA_Error('256', 'Compile Error', 'error.txt', 15);
        $errHandler = $this->getMock('PMA_Error_Handler');
        $errHandler->expects($this->any())
            ->method('countErrors', 'getErrors')
            ->will($this->returnValue(1, $err));

        $this->assertEquals($this->object->countUserErrors(),
            0
        );
    }

    /**
     * Test for hasUserErrors
     */
    public function testHasUserErrors()
    {
        $this->assertFalse($this->object->hasUserErrors());
    }

    /**
     * Test for hasErrors
     */
    public function testHasErrors()
    {
        $this->assertFalse($this->object->hasErrors());
    }

    /**
     * Test for countDisplayErrors
     */
    public function testCountDisplayErrorsForDisplayTrue()
    {
        $GLOBALS['cfg']['Error_Handler']['display'] = true;
        $this->assertEquals($this->object->countDisplayErrors(),
            0
        );
    }

    /**
     * Test for countDisplayErrors
     */
    public function testCountDisplayErrorsForDisplayFalse()
    {
        $GLOBALS['cfg']['Error_Handler']['display'] = false;
        $this->assertEquals($this->object->countDisplayErrors(),
            0
        );
    }

    /**
     * Test for hasDisplayErrors
     */
    public function testHasDisplayErrors()
    {
        $this->assertFalse($this->object->hasDisplayErrors());
    }

}
