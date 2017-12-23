<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Page-related settings
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\config\PageSettings;

require_once 'test/PMATestCase.php';
require_once 'libraries/config/user_preferences.forms.php';
require_once 'libraries/config/page_settings.forms.php';

/**
 * Tests for PMA\libraries\config\PageSettings
 *
 * @package PhpMyAdmin-test
 */
class PageSettingsTest extends PMATestCase
{
    /**
     * Setup tests
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
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
            . 'action="phpunit?db=db&amp;table=&amp;server=1&amp;target=&amp;lang=en&amp;token=token" '
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
            '<input type="hidden" name="submit_save" value="Navi_panel" />',
            $html
        );
    }
}
