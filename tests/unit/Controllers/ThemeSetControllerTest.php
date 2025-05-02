<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\ThemeSetController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\UserPreferences;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ThemeSetController::class)]
class ThemeSetControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testSetTheme(): void
    {
        $config = Config::getInstance();
        $config->settings['ThemeManager'] = true;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['set_theme' => 'theme_name', 'themeColorMode' => '']);

        $themeManager = self::createMock(ThemeManager::class);
        $themeManager->expects(self::once())->method('setActiveTheme')->with(self::equalTo('theme_name'));
        $themeManager->expects(self::once())->method('setThemeCookie');

        $userPreferences = self::createMock(UserPreferences::class);
        $userPreferences->expects(self::once())->method('load')
            ->willReturn(['config_data' => ['ThemeDefault' => 'pmahomme']]);
        $userPreferences->expects(self::once())->method('save')
            ->with(self::equalTo(['ThemeDefault' => 'theme_name']));

        (new ThemeSetController(new ResponseRenderer(), $themeManager, $userPreferences, $config))($request);
    }

    #[DataProvider('providerForTestWithoutTheme')]
    public function testWithoutTheme(bool $hasThemes, string $themeName): void
    {
        $config = Config::getInstance();
        $config->settings['ThemeManager'] = $hasThemes;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['set_theme' => $themeName]);

        $themeManager = self::createMock(ThemeManager::class);
        $themeManager->expects(self::never())->method('setActiveTheme');
        $themeManager->expects(self::never())->method('setThemeCookie');

        $userPreferences = self::createMock(UserPreferences::class);
        $userPreferences->expects(self::never())->method('load');
        $userPreferences->expects(self::never())->method('save');

        (new ThemeSetController(new ResponseRenderer(), $themeManager, $userPreferences, $config))($request);
    }

    /**
     * @return iterable<int, array<int, bool|string|string[]|null>>
     * @psalm-return iterable<int, array{bool, string[]|string|null}>
     */
    public static function providerForTestWithoutTheme(): iterable
    {
        return [[true, ''], [false, '']];
    }
}
