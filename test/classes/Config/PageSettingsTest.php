<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Page-related settings
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Config\PageSettings
 *
 * @package PhpMyAdmin-test
 */
class PageSettingsTest extends PmaTestCase
{
    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = '';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    /**
     * Test showGroup when group passed does not exist
     *
     * @return void
     */
    public function testShowGroupNonExistent()
    {
        $object = PageSettings::showGroup('NonExistent');

        $this->assertEquals('', $object->getHTML());
    }

    /**
     * Test showGroup with a known group name
     *
     * @return void
     */
    public function testShowGroupBrowse()
    {
        $object = PageSettings::showGroup('Browse');

        $html = $object->getHTML();

        // Test some sample parts
        $this->assertStringContainsString(
            '<div id="page_settings_modal">'
            . '<div class="page_settings">'
            . '<form method="post" '
            . 'action="phpunit?db=db&amp;table=&amp;server=1&amp;target=&amp;lang=en" '
            . 'class="config-form disableAjax">',
            $html
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="submit_save" value="Browse">',
            $html
        );

        $this->assertStringContainsString(
            "registerFieldValidator('MaxRows', 'validatePositiveNumber', true);\n"
            . "registerFieldValidator('RepeatCells', 'validateNonNegativeNumber', true);\n"
            . "registerFieldValidator('LimitChars', 'validatePositiveNumber', true);\n",
            $html
        );
    }

    /**
     * Test getNaviSettings
     *
     * @return void
     */
    public function testGetNaviSettings()
    {
        $html = PageSettings::getNaviSettings();

        // Test some sample parts
        $this->assertStringContainsString(
            '<div id="pma_navigation_settings">',
            $html
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="submit_save" value="Navi">',
            $html
        );
    }
}
