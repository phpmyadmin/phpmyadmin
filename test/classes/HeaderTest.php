<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA\libraries\Header class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\Theme;

require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'test/PMATestCase.php';

/**
 * Test for PMA\libraries\Header class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class HeaderTest extends PMATestCase
{
    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        if (!defined('PMA_IS_WINDOWS')) {
            define('PMA_IS_WINDOWS', false);
        }
        $GLOBALS['server'] = 0;
        $GLOBALS['message'] = 'phpmyadminmessage';
        $GLOBALS['is_ajax_request'] = false;
        $_SESSION['PMA_Theme'] = new Theme();
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['server'] = 'server';
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = 'table1';
        $GLOBALS['PMA_Config'] = new PMA\libraries\Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['verbose'] = 'verbose host';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['user'] = '';
    }

    /**
     * Test for disable
     *
     * @return void
     */
    public function testDisable()
    {
        $header = new PMA\libraries\Header();
        $header->disable();
        $this->assertEquals(
            '',
            $header->getDisplay()
        );
    }

    /**
     * Test for Set BodyId
     *
     * @return void
     */
    public function testSetBodyId()
    {
        $header = new PMA\libraries\Header();
        $header->setBodyId('PMA_header_id');
        $this->assertContains(
            'PMA_header_id',
            $header->getDisplay()
        );
    }

    /**
     * Test for print view
     *
     * @return void
     */
    public function testPrintView()
    {
        $header = new PMA\libraries\Header();
        $header->enablePrintView();
        $this->assertContains(
            'Print view',
            $header->getDisplay()
        );
    }

    /**
     * Test for Get JsParams
     *
     * @return void
     */
    public function testGetJsParams()
    {
        $header = new PMA\libraries\Header();
        $this->assertArrayHasKey(
            'common_query',
            $header->getJsParams()
        );
    }

    /**
     * Test for Get JsParamsCode
     *
     * @return void
     */
    public function testGetJsParamsCode()
    {
        $header = new PMA\libraries\Header();
        $this->assertContains(
            'PMA_commonParams.setAll',
            $header->getJsParamsCode()
        );
    }

    /**
     * Test for Get Message
     *
     * @return void
     */
    public function testGetMessage()
    {
        $header = new PMA\libraries\Header();
        $this->assertContains(
            'phpmyadminmessage',
            $header->getMessage()
        );
    }

    /**
     * Test for Disable Warnings
     *
     * @return void
     * @test
     */
    public function testDisableWarnings()
    {
        $header = new PMA\libraries\Header();
        $header->disableWarnings();
        $this->assertAttributeEquals(
            false,
            '_warningsEnabled',
            $header
        );
    }

    /**
     * Tests private method _getWarnings when warnings are disabled
     *
     * @return void
     * @test
     */
    public function testGetWarningsWithWarningsDisabled()
    {
        $method = new ReflectionMethod(
            'PMA\libraries\Header', '_getWarnings'
        );
        $method->setAccessible(true);

        $header = new PMA\libraries\Header();
        $header->disableWarnings();
        $this->assertEmpty($method->invoke($header));
    }
}
