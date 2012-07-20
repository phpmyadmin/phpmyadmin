<?php
/**
 * Tests for Script.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/Scripts.class.php';

class PMA_Scripts_test extends PHPUnit_Framework_TestCase
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
        $this->object = $this->getMockForAbstractClass('PMA_Scripts');
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
     * Call private functions by making the visibitlity to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the private method.
     */
    private function _callPrivateFunction($name, $params)
    {
        $class = new ReflectionClass('PMA_Scripts');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for _includeFile
     *
     * @param tring $url            Location of javascript, relative to js/ folder.
     * @param int    $timestamp     The date when the file was last modified
     * @param string $ie_conditional true - wrap with IE conditional comment
     *                               'lt 9' etc. - wrap for specific IE version
     * @param $output output from the _includeFile method
     *
     * @dataProvider providerForTestIncludeFile
     */
    public function testIncludeFile($url, $timestamp, $ie_conditional, $output){
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_includeFile',
                array($url, $timestamp, $ie_conditional)
            ),
            $output
        );
    }

    /**
     * @return array data for testIncludeFile
     */
    public function providerForTestIncludeFile(){
                return array(
                    array(
                        'common.js',
                        null,
                        true,
                        '<!--[if IE]>
    <script src="common.js" type="text/javascript"></script>
<![endif]-->
'
                    ),
                    array(
                        'common.js',
                        null,
                        false,
                        '<script src="common.js" type="text/javascript"></script>
'
                    )
                );
    }

    /**
     * Test for getDisplay
     */
    public function testGetDisplay(){

        $this->object->addFile('common.js');
        $this->object->addEvent('onClick', 'doSomething');


        $this->assertRegExp(
            '@<script src="js/common.js\\?ts=[0-9]*" type="text/javascript"></script>
<script type="text/javascript">// <!\\[CDATA\\[
\\$\\(window.parent\\).bind\\(\'onClick\', doSomething\\);
// ]]></script>@',
            $this->object->getDisplay()
        );

    }

    /**
     * test for addCode
     */
    public function testAddCode(){

        $this->object->addCode('alert(\'CodeAdded\')');

        $this->assertEquals(
            $this->object->getDisplay(),
            '<script type="text/javascript">// <![CDATA[
alert(\'CodeAdded\')
// ]]></script>'
        );
    }
}
