<?php
/**
 * Tests for Footer class
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
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Error_Handler.class.php';
require_once 'libraries/vendor_config.php';

/**
 * Tests for Footer class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Footer_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['Error_Handler']['gather'] = false;
        $GLOBALS['cfg']['Error_Handler']['display'] = false;
        $GLOBALS['server'] = '1';
        $_SESSION[' PMA_token '] = 'token';
        $_GET['reload_left_frame'] = '1';
        $GLOBALS['focus_querywindow'] = 'main_pane_left';
        $this->object = new PMA_Footer();
        unset($GLOBALS['error_message']);
        unset($GLOBALS['sql_query']);
        $GLOBALS['error_handler'] = new PMA_Error_Handler();
        unset($_POST);
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
     * @return void
     *
     * @group medium
     */
    public function testGetDebugMessage()
    {

        $_SESSION['debug']['queries'] = array(
            'abc' => array(
                'count' => 1,
                'time' => 0.2,
                'query' => 'SELECT * FROM `pma_bookmark` WHERE 1',
            ),
            'def' => array(
                'count' => 1,
                'time' => 2.5,
                'query' => 'SELECT * FROM `db` WHERE 1',
            ),
        );

        $this->assertRegExp(
            '/<div>2 queries executed 2 times in 2.7 seconds<pre>/',
            $this->_callPrivateFunction(
                '_getDebugMessage',
                array()
            )
        );
    }

    /**
     * Test for _getSelfLink
     *
     * @return void
     */
    public function testGetSelfLink()
    {

        $GLOBALS['cfg']['NavigationBarIconic'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 1;

        $this->assertEquals(
            '<div id="selflink" class="print_ignore"><a href="index.phpdb=db%3Dmysql%26token%3D1234&amp;lang=en&amp;collation_connection=utf8_general_ci&amp;token=token" title="Open new phpMyAdmin window" target="_blank">Open new phpMyAdmin window</a></div>',
            $this->_callPrivateFunction(
                '_getSelfLink',
                array('db=mysql&token=1234')
            )
        );
    }

    /**
     * Test for _getSelfLink
     *
     * @return void
     */
    public function testGetSelfLinkWithImage()
    {

        $GLOBALS['cfg']['NavigationBarIconic'] = true;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemeImage'] = 'image';

        $this->assertEquals(
            '<div id="selflink" class="print_ignore"><a href="index.phpdb=db%3Dmysql%26token%3D1234&amp;lang=en&amp;collation_connection=utf8_general_ci&amp;token=token" title="Open new phpMyAdmin window" target="_blank"><img src="imagewindow-new.png" title="Open new phpMyAdmin window" alt="Open new phpMyAdmin window" /></a></div>',
            $this->_callPrivateFunction(
                '_getSelfLink',
                array('db=mysql&token=1234')
            )
        );
    }

    /**
     * Test for disable
     *
     * @return void
     */
    public function testDisable()
    {
        $footer = new PMA_Footer();
        $footer->disable();
        $this->assertEquals(
            '',
            $footer->getDisplay()
        );
    }

    /**
     * Test for displaying footer
     *
     * @return void
     */
    public function testDisplay()
    {
        $footer = new PMA_Footer();
        $this->assertContains(
            'Open new phpMyAdmin window',
            $footer->getDisplay()
        );
    }
}
