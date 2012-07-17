<?php
/**
 * Tests for displaing results
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/Footer.class.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/Theme.class.php';

class PMA_Footer_test extends PHPUnit_Framework_TestCase
{

    /**
     * @var array store private attributes of PMA_Footer
     */
    public $privates = array();

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
        $GLOBALS['lang'] = 'en';
        $GLOBALS['collation_connection'] = 'utf8_general_ci';
        $GLOBALS['server'] = '1';
        $_SESSION[' PMA_token '] = 'token';
        $_GET['reload_left_frame'] = '1';
        $GLOBALS['focus_querywindow'] = 'main_pane_left';
        $this->object = $this->getMockForAbstractClass('PMA_Footer');
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
        $class = new ReflectionClass('PMA_Footer');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for _getDebugMessage
     *
     * @group medium
     */
    public function testGetDebugMessage(){

        $_SESSION['debug']['queries'] = array('SELECT * FROM `pma_bookmark` WHERE 1', 'SELECT * FROM `db` WHERE 1');

        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getDebugMessage',
                array()
            ),
            '<div>2 queries executed 0 times in 0 seconds<pre>Array
(
    [queries] => Array
        (
            [0] => SELECT * FROM `pma_bookmark` WHERE 1
            [1] => SELECT * FROM `db` WHERE 1
        )

)
</pre></div>'
        );
    }

    /**
     * Test for _getSelfLink
     */
    public function testGetSelfLink(){

        $GLOBALS['cfg']['NavigationBarIconic'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 1;

        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getSelfLink',
                array('db=mysql&token=1234')
            ),
            '<div id="selflink" class="print_ignore"><a href="index.phpdb=db%3Dmysql%26token%3D1234&amp;lang=en&amp;collation_connection=utf8_general_ci&amp;token=token" title="Open new phpMyAdmin window" target="_blank">Open new phpMyAdmin window</a></div>'
        );
    }

    /**
     * Test for _getSelfLink
     */
    public function testGetSelfLinkWithImage(){

        $GLOBALS['cfg']['NavigationBarIconic'] = true;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemeImage'] = 'image';

        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getSelfLink',
                array('db=mysql&token=1234')
            ),
            '<div id="selflink" class="print_ignore"><a href="index.phpdb=db%3Dmysql%26token%3D1234&amp;lang=en&amp;collation_connection=utf8_general_ci&amp;token=token" title="Open new phpMyAdmin window" target="_blank"><img src="imagewindow-new.png" title="Open new phpMyAdmin window" alt="Open new phpMyAdmin window" /></a></div>'
        );
    }

//    /**
//     * Test for disable
//     */
//    public function testDisable(){
//
//        $GLOBALS['lang'] = 'en';
//        $GLOBALS['collation_connection'] = 'utf8_general_ci';
//        $GLOBALS['server'] = '1';
//        $_SESSION[' PMA_token '] = 'token';
//        $GLOBALS['reload'] = '1';
//        $GLOBALS['focus_querywindow'] = 'main_pane_left';
//
//        $footer = new PMA_Footer();
//
//        $class         = get_class( $footer );
//        $reflection = new ReflectionClass( $class );
//        $priv_attr  = $reflection->getProperties( ReflectionProperty::IS_PRIVATE );
//        $privates   = array();
//        $parseable = unserialize(str_replace("\0$class\0", "\0*\0", serialize($footer)));
//        foreach($priv_attr as $attribute)
//        {
//            $aname = $attribute->name;
//            $privates[$aname] = $parseable->$aname;
//        }
//
//        $this->assertFalse($priv_attr[3]->_isEnable);
//    }
}
