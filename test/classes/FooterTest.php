<?php
/**
 * Tests for Footer class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PMA\libraries\Theme;

require_once 'libraries/js_escape.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/relation.lib.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for Footer class
 *
 * @package PhpMyAdmin-test
 */
class FooterTest extends PMATestCase
{

    /**
     * @var array store private attributes of PMA\libraries\Footer
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
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['PMA_Config'] = new PMA\libraries\Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['collation_connection'] = 'utf8_general_ci';
        $GLOBALS['cfg']['Server']['verbose'] = 'verbose host';
        $GLOBALS['server'] = '1';
        $_GET['reload_left_frame'] = '1';
        $GLOBALS['focus_querywindow'] = 'main_pane_left';
        $this->object = new PMA\libraries\Footer();
        unset($GLOBALS['error_message']);
        unset($GLOBALS['sql_query']);
        $GLOBALS['error_handler'] = new PMA\libraries\ErrorHandler();
        unset($_POST);

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
     * Call private functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the private method.
     */
    private function _callPrivateFunction($name, $params)
    {
        $class = new ReflectionClass('PMA\libraries\Footer');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for getDebugMessage
     *
     * @return void
     *
     * @group medium
     */
    public function testGetDebugMessage()
    {
        $GLOBALS['cfg']['DBG']['sql'] = true;
        $_SESSION['debug']['queries'] = array(
            array(
                'count' => 1,
                'time' => 0.2,
                'query' => 'SELECT * FROM `pma_bookmark` WHERE 1',
            ),
            array(
                'count' => 1,
                'time' => 2.5,
                'query' => 'SELECT * FROM `db` WHERE 1',
            ),
        );

        $this->assertEquals(
            '{"queries":[{"count":1,"time":0.2,"query":"SELECT * FROM `pma_bookmark` WHERE 1"},'
            . '{"count":1,"time":2.5,"query":"SELECT * FROM `db` WHERE 1"}]}',
            $this->object->getDebugMessage()
        );
    }

    /**
     * Test for _removeRecursion
     *
     * @return void
     */
    public function testRemoveRecursion()
    {
        $object = (object) array();
        $object->child = (object) array();
        $object->child->parent = $object;

        $this->_callPrivateFunction(
            '_removeRecursion',
            array(
                &$object
            )
        );

        $this->assertEquals(
            '{"child":{"parent":"***RECURSION***"}}',
            json_encode($object)
        );
    }

    /**
     * Test for _getSelfLink
     *
     * @return void
     */
    public function testGetSelfLink()
    {

        $GLOBALS['cfg']['TabsMode'] = 'text';
        $GLOBALS['cfg']['ServerDefault'] = 1;

        $this->assertEquals(
            '<div id="selflink" class="print_ignore"><a href="index.php?db=&amp;'
            . 'table=&amp;server=1&amp;target=&amp;lang=en&amp;collation_connection='
            . 'utf8_general_ci&amp;token=token" title="Open new phpMyAdmin window" '
            . 'target="_blank" rel="noopener noreferrer">Open new phpMyAdmin window</a></div>',
            $this->_callPrivateFunction(
                '_getSelfLink',
                array(
                    $this->object->getSelfUrl()
                )
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

        $GLOBALS['cfg']['TabsMode'] = 'icons';
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_SESSION['PMA_Theme'] = new Theme();
        $GLOBALS['pmaThemeImage'] = 'image';

        $this->assertEquals(
            '<div id="selflink" class="print_ignore"><a href="index.php?db=&amp;'
            . 'table=&amp;server=1&amp;target=&amp;lang=en&amp;collation_connection='
            . 'utf8_general_ci&amp;token=token" title="Open new phpMyAdmin window" '
            . 'target="_blank" rel="noopener noreferrer"><img src="imagewindow-new.png" title="Open new '
            . 'phpMyAdmin window" alt="Open new phpMyAdmin window" /></a></div>',
            $this->_callPrivateFunction(
                '_getSelfLink',
                array(
                    $this->object->getSelfUrl()
                )
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
        $footer = new PMA\libraries\Footer();
        $footer->disable();
        $this->assertEquals(
            '',
            $footer->getDisplay()
        );
    }

    /**
     * Test for footer when ajax enabled
     *
     * @return void
     */
    public function testAjax()
    {
        $footer = new PMA\libraries\Footer();
        $footer->setAjax(true);
        $this->assertEquals(
            '',
            $footer->getDisplay()
        );
    }

    /**
     * Test for footer get Scripts
     *
     * @return void
     */
    public function testGetScripts()
    {
        $footer = new PMA\libraries\Footer();
        $this->assertContains(
            '<script data-cfasync="false" type="text/javascript">',
            $footer->getScripts()->getDisplay()
        );
    }

    /**
     * Test for displaying footer
     *
     * @return void
     * @group medium
     */
    public function testDisplay()
    {
        $footer = new PMA\libraries\Footer();
        $this->assertContains(
            'Open new phpMyAdmin window',
            $footer->getDisplay()
        );
    }

    /**
     * Test for minimal footer
     *
     * @return void
     */
    public function testMinimal()
    {
        $footer = new PMA\libraries\Footer();
        $footer->setMinimal();
        $this->assertEquals(
            '</div></body></html>',
            $footer->getDisplay()
        );
    }
}
