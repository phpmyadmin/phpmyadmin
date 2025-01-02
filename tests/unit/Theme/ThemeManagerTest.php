<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Theme;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Theme\ThemeManager;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ThemeManager::class)]
class ThemeManagerTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setGlobalConfig();

        $config = Config::getInstance();
        $config->settings['ThemePerServer'] = false;
        $config->settings['ThemeDefault'] = 'pmahomme';
        $config->settings['ServerDefault'] = 0;
        Current::$server = 99;
    }

    /**
     * Test for ThemeManager::getThemeCookieName
     */
    public function testCookieName(): void
    {
        $tm = new ThemeManager();
        self::assertSame('pma_theme', $tm->getThemeCookieName());
    }

    /**
     * Test for ThemeManager::getThemeCookieName
     */
    public function testPerServerCookieName(): void
    {
        $tm = new ThemeManager();
        $tm->setThemePerServer(true);
        self::assertSame('pma_theme-99', $tm->getThemeCookieName());
    }

    public function testGetThemesArray(): void
    {
        $tm = new ThemeManager();
        $tm->initializeTheme();
        $themes = $tm->getThemesArray();
        self::assertArrayHasKey(0, $themes);
    }

    /**
     * Test for setThemeCookie
     */
    public function testSetThemeCookie(): void
    {
        $tm = new ThemeManager();
        $tm->setThemeCookie();
        self::assertNotFalse(
            $tm->getThemeCookie(),
        );
    }
}
