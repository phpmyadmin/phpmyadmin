<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ThemeManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @covers \PhpMyAdmin\ThemeManager
 */
#[CoversClass(ThemeManager::class)]
#[AllowMockObjectsWithoutExpectations]
class ThemeManagerTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        $GLOBALS['cfg']['ThemePerServer'] = false;
        $GLOBALS['cfg']['ThemeDefault'] = 'pmahomme';
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['server'] = 99;

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->method('escapeString')->willReturnArgument(0);
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
        $themes = $tm->getThemesArray();
        self::assertIsArray($themes);
        self::assertArrayHasKey(0, $themes);
        self::assertIsArray($themes[0]);
        self::assertArrayHasKey('id', $themes[0]);
        self::assertArrayHasKey('name', $themes[0]);
        self::assertArrayHasKey('version', $themes[0]);
        self::assertArrayHasKey('is_active', $themes[0]);
    }

    /**
     * Test for setThemeCookie
     */
    public function testSetThemeCookie(): void
    {
        $tm = new ThemeManager();
        self::assertTrue($tm->setThemeCookie());
    }
}
