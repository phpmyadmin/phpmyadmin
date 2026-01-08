<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Navigation;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Navigation\UpdateNavWidthConfigController;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Theme\ThemeManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(UpdateNavWidthConfigController::class)]
final class UpdateNavWidthConfigControllerTest extends AbstractTestCase
{
    #[DataProvider('validParamsProvider')]
    public function testValidParam(string $value, int $expected): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['value' => $value]);

        $config = new Config();
        $dbi = $this->createDatabaseInterface();
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            new UserPreferences($dbi, new Relation($dbi, $config), new Template($config), $config),
            new LanguageManager($config),
            new ThemeManager(),
        );
        $responseRenderer = new ResponseRenderer();
        $controller = new UpdateNavWidthConfigController($responseRenderer, $userPreferencesHandler);
        $controller($request);

        self::assertSame($expected, $config->settings['NavigationWidth']);
        self::assertSame([], $responseRenderer->getJSONResult());
        self::assertTrue($responseRenderer->hasSuccessState(), 'Should be a successful response.');
    }

    /** @return iterable<array{string, int}> */
    public static function validParamsProvider(): iterable
    {
        yield ['0', 0];
        yield ['1', 1];
        yield ['240', 240];
    }

    /** @param string|string[] $value */
    #[DataProvider('invalidParamsProvider')]
    public function testInvalidParams(array|string $value): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['value' => $value]);

        $config = new Config();
        $dbi = $this->createDatabaseInterface();
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            new UserPreferences($dbi, new Relation($dbi, $config), new Template($config), $config),
            new LanguageManager($config),
            new ThemeManager(),
        );
        $responseRenderer = new ResponseRenderer();
        $controller = new UpdateNavWidthConfigController($responseRenderer, $userPreferencesHandler);
        $controller($request);

        self::assertSame(
            ['message' => Message::error('Unexpected parameter value.')->getDisplay()],
            $responseRenderer->getJSONResult(),
        );
        self::assertFalse($responseRenderer->hasSuccessState(), 'Should be a failed response.');
    }

    /** @return iterable<array{string|string[]}> */
    public static function invalidParamsProvider(): iterable
    {
        yield [''];
        yield ['invalid'];
        yield [['invalid']];
        yield ['-1'];
    }

    public function testFailedConfigSaving(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['value' => '240']);

        $userPreferencesHandler = self::createStub(UserPreferencesHandler::class);
        $userPreferencesHandler->method('setUserValue')->willReturn(Message::error('Could not save configuration'));
        $responseRenderer = new ResponseRenderer();
        $controller = new UpdateNavWidthConfigController($responseRenderer, $userPreferencesHandler);
        $controller($request);

        self::assertSame(
            ['message' => Message::error('Could not save configuration')->getDisplay()],
            $responseRenderer->getJSONResult(),
        );
        self::assertFalse($responseRenderer->hasSuccessState(), 'Should be a failed response.');
    }
}
