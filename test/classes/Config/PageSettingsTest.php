<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Config\PageSettings
 */
class PageSettingsTest extends AbstractTestCase
{
    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalConfig();
        parent::setTheme();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = '';
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    /**
     * Test showGroup when group passed does not exist
     */
    public function testShowGroupNonExistent(): void
    {
        $object = new PageSettings('NonExistent');

        self::assertEquals('', $object->getHTML());
    }

    /**
     * Test showGroup with a known group name
     */
    public function testShowGroupBrowse(): void
    {
        $object = new PageSettings('Browse');

        $html = $object->getHTML();

        // Test some sample parts
        self::assertStringContainsString('<div id="page_settings_modal">'
        . '<div class="page_settings">'
        . '<form method="post" '
        . 'action="index.php&#x3F;db&#x3D;db&amp;server&#x3D;1&amp;lang&#x3D;en" '
        . 'class="config-form disableAjax">', $html);

        self::assertStringContainsString('<input type="hidden" name="submit_save" value="Browse">', $html);

        self::assertStringContainsString("registerFieldValidator('MaxRows', 'validatePositiveNumber', true);\n"
        . "registerFieldValidator('RepeatCells', 'validateNonNegativeNumber', true);\n"
        . "registerFieldValidator('LimitChars', 'validatePositiveNumber', true);\n", $html);
    }

    /**
     * Test getNaviSettings
     */
    public function testGetNaviSettings(): void
    {
        $pageSettings = new PageSettings('Navi', 'pma_navigation_settings');

        $html = $pageSettings->getHTML();

        // Test some sample parts
        self::assertStringContainsString('<div id="pma_navigation_settings">', $html);

        self::assertStringContainsString('<input type="hidden" name="submit_save" value="Navi">', $html);
    }
}
