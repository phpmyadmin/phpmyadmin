<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Middleware;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Middleware\TokenRequestParamChecking;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(TokenRequestParamChecking::class)]
final class TokenRequestParamCheckingTest extends TestCase
{
    public function testCheckTokenRequestParam(): void
    {
        $_POST = [];

        $middleware = new TokenRequestParamChecking(new ResponseRenderer());

        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'token';
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['token' => 'token']);
        $response = $middleware->checkTokenRequestParam($request);
        self::assertTrue($response);
        self::assertArrayHasKey('test', $_POST);
        self::assertSame('test', $_POST['test']);
    }

    public function testCheckTokenRequestParamWithoutToken(): void
    {
        $middleware = new TokenRequestParamChecking(new ResponseRenderer());

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/');
        $response = $middleware->checkTokenRequestParam($request);
        self::assertFalse($response);
    }

    public function testProcessWithoutTokenStripsRequestAndRoutesToHome(): void
    {
        $_SESSION[' PMA_token '] = 'token';
        $_POST = ['test' => 'test'];

        $middleware = new TokenRequestParamChecking(new ResponseRenderer());

        $request = ServerRequestFactory::create()
            ->createServerRequest('POST', 'http://example.com/index.php?route=/database/structure/add-prefix-table')
            ->withAttribute('route', '/database/structure/add-prefix-table')
            ->withQueryParams(['db' => 'test_db'])
            ->withCookieParams(['pmaUser-1' => 'user'])
            ->withParsedBody(['selected' => ['test_table'], 'add_prefix' => 'prefix_']);

        $handler = new class implements RequestHandlerInterface {
            public ServerRequestInterface|null $request = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return ResponseFactory::create()->createResponse();
            }
        };

        $middleware->process($request, $handler);

        self::assertSame([], $_POST);
        self::assertInstanceOf(ServerRequest::class, $handler->request);
        self::assertSame([], $handler->request->getParsedBody());
        self::assertSame([], $handler->request->getQueryParams());
        self::assertSame([], $handler->request->getCookieParams());
        self::assertSame('/', $handler->request->getAttribute('route'));
    }

    public function testCheckTokenRequestParamWithTokenMismatch(): void
    {
        $responseRenderer = new ResponseRenderer();
        $middleware = new TokenRequestParamChecking($responseRenderer);

        $dbi = DatabaseInterface::getInstanceForTest(new DbiDummy());
        DatabaseInterface::$instance = $dbi;

        $_SESSION[' PMA_token '] = 'mismatch';

        $responseRenderer->setAjax(true);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withParsedBody(['token' => 'token']);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $response = $middleware->process($request, $handler);
        $response = $response->getBody()->__toString();
        self::assertStringContainsString('Error: Token mismatch', $response);
        self::assertFalse($responseRenderer->hasSuccessState());
        $json = $responseRenderer->getJSONResult();
        self::assertArrayHasKey('message', $json);
        self::assertIsString($json['message']);
        self::assertStringContainsString('Error: Token mismatch', $json['message']);
    }
}
