<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for ThemeManager class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\ThemeManager;

/**
 * tests for ThemeManager class
 *
 * @package PhpMyAdmin-test
 */
class ThemeManagerTest extends PmaTestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['cfg']['ThemePerServer'] = false;
        $GLOBALS['cfg']['ThemeDefault'] = 'pmahomme';
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['server'] = 99;
        $GLOBALS['PMA_Config'] = new Config();

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $cfg['dbi'] = $dbi;
    }

    /**
     * Test for ThemeManager::getThemeCookieName
     *
     * @return void
     */
    public function testCookieName()
    {
        $tm = new ThemeManager();
        $this->assertEquals('pma_theme', $tm->getThemeCookieName());
    }

    /**
     * Test for ThemeManager::getThemeCookieName
     *
     * @return void
     */
    public function testPerServerCookieName()
    {
        $tm = new ThemeManager();
        $tm->setThemePerServer(true);
        $this->assertEquals('pma_theme-99', $tm->getThemeCookieName());
    }

    /**
     * Test for ThemeManager::getHtmlSelectBox
     *
     * @return void
     */
    public function testHtmlSelectBox()
    {
        $tm = new ThemeManager();
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
        $tm = new ThemeManager();
        $this->assertTrue(
            $tm->setThemeCookie()
        );
    }

    /**
     * Test for getPrintPreviews
     *
     * @return void
     */
    public function testGetPrintPreviews()
    {
        $tm = new ThemeManager();
        $preview = $tm->getPrintPreviews();
        $this->assertContains('<div class="theme_preview"', $preview);
        $this->assertContains('Original', $preview);
        $this->assertContains('set_theme=original', $preview);
        $this->assertContains('pmahomme', $preview);
        $this->assertContains('set_theme=pmahomme', $preview);
    }

    /**
     * Test for getFallBackTheme
     *
     * @return void
     */
    public function testGetFallBackTheme()
    {
        $tm = new ThemeManager();
        $this->assertInstanceOf(
            'PhpMyAdmin\Theme',
            $tm->getFallBackTheme()
        );
    }
}
