<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Page-related settings
 *
 * @package PhpMyAdmin-test
 */
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
    public function setUp()
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = '';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
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
        $this->assertContains(
            '<div id="page_settings_modal">'
            . '<div class="page_settings">'
            . '<form method="post" '
            . 'action="phpunit?db=db&amp;table=&amp;server=1&amp;target=&amp;lang=en" '
            . 'class="config-form disableAjax">',
            $html
        );

        $this->assertContains(
            '<input type="hidden" name="submit_save" value="Browse" />',
            $html
        );

        $this->assertContains(
            "validateField('MaxRows', 'PMA_validatePositiveNumber', true);\n"
            . "validateField('RepeatCells', 'PMA_validateNonNegativeNumber', true);\n"
            . "validateField('LimitChars', 'PMA_validatePositiveNumber', true);\n",
            $html
        );
    }

    /**
     * Test getNaviSettings
     *
     * @return void
     */
    function testGetNaviSettings()
    {
        $html = PageSettings::getNaviSettings();

        // Test some sample parts
        $this->assertContains(
            '<div id="pma_navigation_settings">',
            $html
        );

        $this->assertContains(
            '<input type="hidden" name="submit_save" value="Navi" />',
            $html
        );
    }
}
