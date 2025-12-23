<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Middleware\Authentication;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(Authentication::class)]
final class AuthenticationTest extends AbstractTestCase
{
    public function testAuthenticationWithoutSelectServer(): void
    {
        $configMock = self::createMock(Config::class);
        $configMock->expects(self::once())->method('hasSelectedServer')->willReturn(false);

        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        $authentication = new Authentication(
            $configMock,
            new Template($config),
            ResponseFactory::create(),
            new AuthenticationPluginFactory(),
            $dbi,
            new Relation($dbi, $config),
            new ResponseRenderer(),
        );

        $response = self::createStub(ResponseInterface::class);
        $handler = self::createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');

        self::assertSame($response, $authentication->process($request, $handler));
    }
}
