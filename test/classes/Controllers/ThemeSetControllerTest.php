<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\ThemeSetController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\UserPreferences;

/**
 * @covers \PhpMyAdmin\Controllers\ThemeSetController
 */
class ThemeSetControllerTest extends AbstractTestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetTheme(): void
    {
        $GLOBALS['cfg']['ThemeManager'] = true;

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturn('theme_name');

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

    /**
     * @param string[]|string|null $themeName
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @dataProvider providerForTestWithoutTheme
     */
    public function testWithoutTheme(bool $hasThemes, $themeName): void
    {
        $GLOBALS['cfg']['ThemeManager'] = $hasThemes;

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturn($themeName);

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
    public function providerForTestWithoutTheme(): iterable
    {
        return [
            [true, null],
            [true, ''],
            [true, ['theme_name']],
            [false, null],
            [false, ''],
            [false, ['theme_name']],
        ];
    }
}
