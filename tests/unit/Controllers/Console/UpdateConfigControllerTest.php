<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Console;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Console\UpdateConfigController;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(UpdateConfigController::class)]
final class UpdateConfigControllerTest extends AbstractTestCase
{
    #[DataProvider('validParamsProvider')]
    public function testValidParams(string $key, string $value, bool|int|string $expected): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['key' => $key, 'value' => $value]);

        $config = new Config();
        $responseRenderer = new ResponseRenderer();
        $controller = new UpdateConfigController($responseRenderer, new Template($config), $config);
        $controller($request);

        self::assertSame($expected, $config->settings['Console'][$key]);
        self::assertSame([], $responseRenderer->getJSONResult());
        self::assertTrue($responseRenderer->hasSuccessState(), 'Should be a successful response.');
    }

    /** @return iterable<array{string, string, bool|int|string}> */
    public static function validParamsProvider(): iterable
    {
        yield ['StartHistory', 'true', true];
        yield ['StartHistory', 'false', false];
        yield ['AlwaysExpand', 'true', true];
        yield ['AlwaysExpand', 'false', false];
        yield ['CurrentQuery', 'true', true];
        yield ['CurrentQuery', 'false', false];
        yield ['EnterExecutes', 'true', true];
        yield ['EnterExecutes', 'false', false];
        yield ['DarkTheme', 'true', true];
        yield ['DarkTheme', 'false', false];
        yield ['Mode', 'show', 'show'];
        yield ['Mode', 'collapse', 'collapse'];
        yield ['Mode', 'info', 'info'];
        yield ['Height', '1', 1];
        yield ['Height', '92', 92];
        yield ['GroupQueries', 'true', true];
        yield ['GroupQueries', 'false', false];
        yield ['OrderBy', 'exec', 'exec'];
        yield ['OrderBy', 'time', 'time'];
        yield ['OrderBy', 'count', 'count'];
        yield ['Order', 'asc', 'asc'];
        yield ['Order', 'desc', 'desc'];
    }

    /**
     * @param string|string[] $key
     * @param string|string[] $value
     */
    #[DataProvider('invalidParamsProvider')]
    public function testInvalidParams(array|string $key, array|string $value): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['key' => $key, 'value' => $value]);

        $config = new Config();
        $responseRenderer = new ResponseRenderer();
        $controller = new UpdateConfigController($responseRenderer, new Template($config), $config);
        $controller($request);

        self::assertSame(
            ['message' => Message::error('Unexpected parameter value.')->getDisplay()],
            $responseRenderer->getJSONResult(),
        );
        self::assertFalse($responseRenderer->hasSuccessState(), 'Should be a failed response.');
    }

    /** @return iterable<array{string|string[], string|string[]}> */
    public static function invalidParamsProvider(): iterable
    {
        yield ['StartHistory', ''];
        yield ['StartHistory', 'invalid'];
        yield ['StartHistory', ['invalid']];
        yield ['AlwaysExpand', ''];
        yield ['AlwaysExpand', 'invalid'];
        yield ['AlwaysExpand', ['invalid']];
        yield ['CurrentQuery', ''];
        yield ['CurrentQuery', 'invalid'];
        yield ['CurrentQuery', ['invalid']];
        yield ['EnterExecutes', ''];
        yield ['EnterExecutes', 'invalid'];
        yield ['EnterExecutes', ['invalid']];
        yield ['DarkTheme', ''];
        yield ['DarkTheme', 'invalid'];
        yield ['DarkTheme', ['invalid']];
        yield ['Mode', ''];
        yield ['Mode', 'invalid'];
        yield ['Mode', ['invalid']];
        yield ['Height', ''];
        yield ['Height', 'invalid'];
        yield ['Height', ['invalid']];
        yield ['Height', '0'];
        yield ['Height', '-1'];
        yield ['GroupQueries', ''];
        yield ['GroupQueries', 'invalid'];
        yield ['GroupQueries', ['invalid']];
        yield ['OrderBy', ''];
        yield ['OrderBy', 'invalid'];
        yield ['OrderBy', ['invalid']];
        yield ['Order', ''];
        yield ['Order', 'invalid'];
        yield ['Order', ['invalid']];
        yield ['', 'invalid'];
        yield ['invalid', 'invalid'];
        yield [['invalid'], 'invalid'];
    }

    public function testFailedConfigSaving(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['key' => 'StartHistory', 'value' => 'true']);

        $config = self::createStub(Config::class);
        $config->method('setUserValue')->willReturn(Message::error('Could not save configuration'));
        $responseRenderer = new ResponseRenderer();
        $controller = new UpdateConfigController($responseRenderer, new Template($config), $config);
        $controller($request);

        self::assertSame(
            ['message' => Message::error('Could not save configuration')->getDisplay()],
            $responseRenderer->getJSONResult(),
        );
        self::assertFalse($responseRenderer->hasSuccessState(), 'Should be a failed response.');
    }
}
