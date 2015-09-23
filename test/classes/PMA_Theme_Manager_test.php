<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_Theme_Manager class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Theme_Manager.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/core.lib.php';

/**
 * tests for PMA_Theme_Manager class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Theme_Manager_Test extends PHPUnit_Framework_TestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['cfg']['ThemePath'] = './themes';
        $GLOBALS['cfg']['ThemePerServer'] = false;
        $GLOBALS['cfg']['ThemeDefault'] = 'pmahomme';
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['server'] = 99;
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['collation_connection'] = 'utf8_general_ci';
    }

    /**
     * Test for PMA_Theme_Manager::getThemeCookieName
     *
     * @return void
     */
    public function testCookieName()
    {
        $tm = new PMA_Theme_Manager();
        $this->assertEquals('pma_theme', $tm->getThemeCookieName());
    }

    /**
     * Test for PMA_Theme_Manager::getThemeCookieName
     *
     * @return void
     */
    public function testPerServerCookieName()
    {
        $tm = new PMA_Theme_Manager();
        $tm->setThemePerServer(true);
        $this->assertEquals('pma_theme-99', $tm->getThemeCookieName());
    }

    /**
     * Test for PMA_Theme_Manager::getHtmlSelectBox
     *
     * @return void
     */
    public function testHtmlSelectBox()
    {
        $tm = new PMA_Theme_Manager();
        $this->assertContains(
            '<option value="pmahomme" selected="selected">',
            $tm->getHtmlSelectBox()
        );
    }

    /**
     * Test for setThemeCookie
     *
     * @return void
     */
    public function testSetThemeCookie()
    {
        $tm = new PMA_Theme_Manager();
        $this->assertTrue(
            $tm->setThemeCookie()
        );
    }

    /**
     * Test for checkConfig
     *
     * @return void
     */
    public function testCheckConfig()
    {
        $tm = new PMA_Theme_Manager();
        $this->assertNull(
            $tm->checkConfig()
        );
    }

    /**
     * Test for makeBc
     *
     * @return void
     */
    public function testMakeBc()
    {
        $tm = new PMA_Theme_Manager();
        $this->assertNull(
            $tm->makeBc()
        );
        $this->assertEquals($GLOBALS['theme'], 'pmahomme');
        $this->assertEquals($GLOBALS['pmaThemePath'], './themes/pmahomme');
        $this->assertEquals($GLOBALS['pmaThemeImage'], './themes/pmahomme/img/');

    }

    /**
     * Test for getPrintPreviews
     *
     * @return void
     */
    public function testGetPrintPreviews()
    {
        $tm = new PMA_Theme_Manager();
        $this->assertEquals(
            '<div class="theme_preview"><h2>Original (2.9) </h2><p><a class='
            . '"take_theme" name="original" href="index.php?set_theme=original'
            . '&amp;server=99&amp;lang=en&amp;collation_connection=utf8_general_ci'
            . '&amp;token=token"><img src="./themes/original/screen.png" border="1" '
            . 'alt="Original" title="Original" /><br />[ <strong>take it</strong> ]'
            . '</a></p></div><div class="theme_preview"><h2>pmahomme (1.1) </h2><p>'
            . '<a class="take_theme" name="pmahomme" href="index.php?set_theme='
            . 'pmahomme&amp;server=99&amp;lang=en&amp;collation_connection=utf8_'
            . 'general_ci&amp;token=token"><img src="./themes/pmahomme/screen.png" '
            . 'border="1" alt="pmahomme" title="pmahomme" /><br />[ <strong>take it'
            . '</strong> ]</a></p></div>',
            $tm->getPrintPreviews()
        );
    }

    /**
     * Test for getFallBackTheme
     *
     * @return void
     */
    public function testGetFallBackTheme()
    {
        $tm = new PMA_Theme_Manager();
        $this->assertInstanceOf(
            'PMA_theme',
            $tm->getFallBackTheme()
        );
    }
}
