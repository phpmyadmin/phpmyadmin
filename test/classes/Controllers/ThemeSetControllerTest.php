<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\ThemeSetController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\UserPreferences;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[CoversClass(ThemeSetController::class)]
class ThemeSetControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testSetTheme(): void
    {
        $GLOBALS['cfg']['ThemeManager'] = true;

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['set_theme', null, 'theme_name']]);

        $themeManager = $this->createMock(ThemeManager::class);
        $themeManager->expects($this->once())->method('setActiveTheme')->with($this->equalTo('theme_name'));
        $themeManager->expects($this->once())->method('setThemeCookie');

        $userPreferences = $this->createMock(UserPreferences::class);
        $userPreferences->expects($this->once())->method('load')
            ->willReturn(['config_data' => ['ThemeDefault' => 'pmahomme']]);
        $userPreferences->expects($this->once())->method('save')
            ->with($this->equalTo(['ThemeDefault' => 'theme_name']));

        (new ThemeSetController(new ResponseRenderer(), new Template(), $themeManager, $userPreferences))($request);
    }

    /** @param string[]|string|null $themeName */
    #[DataProvider('providerForTestWithoutTheme')]
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testWithoutTheme(bool $hasThemes, array|string|null $themeName): void
    {
        $GLOBALS['cfg']['ThemeManager'] = $hasThemes;

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['set_theme', null, $themeName]]);

        $themeManager = $this->createMock(ThemeManager::class);
        $themeManager->expects($this->never())->method('setActiveTheme');
        $themeManager->expects($this->never())->method('setThemeCookie');

        $userPreferences = $this->createMock(UserPreferences::class);
        $userPreferences->expects($this->never())->method('load');
        $userPreferences->expects($this->never())->method('save');

        (new ThemeSetController(new ResponseRenderer(), new Template(), $themeManager, $userPreferences))($request);
    }

    /**
     * @return iterable<int, array<int, bool|string|string[]|null>>
     * @psalm-return iterable<int, array{bool, string[]|string|null}>
     */
    public static function providerForTestWithoutTheme(): iterable
    {
        return [[true, null], [true, ''], [true, ['theme_name']], [false, null], [false, ''], [false, ['theme_name']]];
    }
}
