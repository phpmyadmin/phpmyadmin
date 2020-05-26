<?php
/**
 * tests for ThemeManager class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ThemeManager;

/**
 * tests for ThemeManager class
 */
class ThemeManagerTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setGlobalConfig();
        $GLOBALS['cfg']['ThemePerServer'] = false;
        $GLOBALS['cfg']['ThemeDefault'] = 'pmahomme';
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['server'] = 99;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
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
        $this->assertStringContainsString(
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
        $this->assertStringContainsString('<div class="theme_preview"', $preview);
        $this->assertStringContainsString('Original', $preview);
        $this->assertStringContainsString('set_theme=original', $preview);
        $this->assertStringContainsString('pmahomme', $preview);
        $this->assertStringContainsString('set_theme=pmahomme', $preview);
    }
}
