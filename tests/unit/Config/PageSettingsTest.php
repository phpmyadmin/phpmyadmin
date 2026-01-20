<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(PageSettings::class)]
class PageSettingsTest extends AbstractTestCase
{
    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$database = 'db';
        Current::$table = '';
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        Config::getInstance()->selectedServer['DisableIS'] = false;
    }

    /**
     * Test showGroup when group passed does not exist
     */
    public function testShowGroupNonExistent(): void
    {
        $config = Config::getInstance();
        $dbi = DatabaseInterface::getInstance();
        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        $object = new PageSettings($userPreferences);
        $object->init('NonExistent');

        self::assertSame('', $object->getHTML());
    }

    /**
     * Test showGroup with a known group name
     */
    #[BackupStaticProperties(true)]
    public function testShowGroupBrowse(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);

        $config = Config::getInstance();
        $dbi = DatabaseInterface::getInstance();
        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        $object = new PageSettings($userPreferences);
        $object->init('Browse');

        $html = $object->getHTML();

        // Test some sample parts
        self::assertStringContainsString(
            '<div id="page_settings_modal">'
            . '<div class="page_settings">'
            . '<form method="post" '
            . 'action="index.php&#x3F;route&#x3D;&#x25;2F&amp;db&#x3D;db&amp;server&#x3D;1&amp;lang&#x3D;en" '
            . 'class="config-form disableAjax">',
            $html,
        );

        self::assertStringContainsString('<input type="hidden" name="submit_save" value="Browse">', $html);

        self::assertStringContainsString(
            'data-field-validators="&#x5B;&#x7B;&quot;fieldId&quot;&#x3A;&quot;MaxRows&quot;,'
                . '&quot;name&quot;&#x3A;&quot;validatePositiveNumber&quot;,'
                . '&quot;args&quot;&#x3A;null&#x7D;,&#x7B;&quot;fieldId&quot;&#x3A;&quot;RepeatCells&quot;,'
                . '&quot;name&quot;&#x3A;&quot;validateNonNegativeNumber&quot;,'
                . '&quot;args&quot;&#x3A;null&#x7D;,&#x7B;&quot;fieldId&quot;&#x3A;&quot;LimitChars&quot;,'
                . '&quot;name&quot;&#x3A;&quot;validatePositiveNumber&quot;,&quot;args&quot;&#x3A;null&#x7D;&#x5D;"',
            $html,
        );
        self::assertStringContainsString(
            'data-default-values="&#x7B;&quot;TableNavigationLinksMode&quot;&#x3A;&#x5B;&quot;icons&quot;&#x5D;,'
                . '&quot;ActionLinksMode&quot;&#x3A;&#x5B;&quot;both&quot;&#x5D;,'
                . '&quot;ShowAll&quot;&#x3A;false,&quot;MaxRows&quot;&#x3A;&#x5B;25&#x5D;,'
                . '&quot;Order&quot;&#x3A;&#x5B;&quot;SMART&quot;&#x5D;,'
                . '&quot;BrowsePointerEnable&quot;&#x3A;true,&quot;BrowseMarkerEnable&quot;&#x3A;true,'
                . '&quot;GridEditing&quot;&#x3A;&#x5B;&quot;double-click&quot;&#x5D;,'
                . '&quot;SaveCellsAtOnce&quot;&#x3A;false,&quot;RepeatCells&quot;&#x3A;&quot;100&quot;,'
                . '&quot;LimitChars&quot;&#x3A;&quot;50&quot;,'
                . '&quot;RowActionLinks&quot;&#x3A;&#x5B;&quot;left&quot;&#x5D;,'
                . '&quot;RowActionLinksWithoutUnique&quot;&#x3A;false,'
                . '&quot;TablePrimaryKeyOrder&quot;&#x3A;&#x5B;&quot;NONE&quot;&#x5D;,'
                . '&quot;RememberSorting&quot;&#x3A;true,'
                . '&quot;RelationalDisplay&quot;&#x3A;&#x5B;&quot;K&quot;&#x5D;&#x7D;"',
            $html,
        );
    }

    /**
     * Test getNaviSettings
     */
    public function testGetNaviSettings(): void
    {
        $config = Config::getInstance();
        $dbi = DatabaseInterface::getInstance();
        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        $pageSettings = new PageSettings($userPreferences);
        $pageSettings->init('Navi', 'pma_navigation_settings');

        $html = $pageSettings->getHTML();

        // Test some sample parts
        self::assertStringContainsString('<div id="pma_navigation_settings">', $html);

        self::assertStringContainsString('<input type="hidden" name="submit_save" value="Navi">', $html);
    }
}
