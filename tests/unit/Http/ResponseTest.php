<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\HttpFactory;
use HttpSoft\Message\ResponseFactory as HttpSoftResponseFactory;
use Laminas\Diactoros\ResponseFactory as LaminasResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;

use function class_exists;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    /** @psalm-param class-string<ResponseFactoryInterface> $provider */
    private function getResponse(string $provider): Response
    {
        if (! class_exists($provider)) {
            self::markTestSkipped($provider . ' is not available.');
        }

        return (new ResponseFactory(new $provider()))->createResponse();
    }

    /** @return iterable<string, array{class-string<ResponseFactoryInterface>}> */
    public static function responseFactoryProviders(): iterable
    {
        yield 'slim/psr7' => [SlimResponseFactory::class];
        yield 'laminas/laminas-diactoros' => [LaminasResponseFactory::class];
        yield 'nyholm/psr7' => [Psr17Factory::class];
        yield 'guzzlehttp/psr7' => [HttpFactory::class];
        yield 'httpsoft/http-message' => [HttpSoftResponseFactory::class];
    }

    /** @psalm-param class-string<ResponseFactoryInterface> $provider */
    #[DataProvider('responseFactoryProviders')]
    public function testProtocolVersion(string $provider): void
    {
        $response = $this->getResponse($provider)->withProtocolVersion('1.0');
        $newResponse = $response->withProtocolVersion('1.1');
        self::assertSame('1.0', $response->getProtocolVersion());
        self::assertSame('1.1', $newResponse->getProtocolVersion());
    }

    /** @psalm-param class-string<ResponseFactoryInterface> $provider */
    #[DataProvider('responseFactoryProviders')]
    public function testHeaders(string $provider): void
    {
        $response = $this->getResponse($provider);
        self::assertSame([], $response->getHeaders());
        $response = $response->withHeader('Content-Type', 'application/json');
        self::assertTrue($response->hasHeader('Content-Type'));
        self::assertSame(['application/json'], $response->getHeader('Content-Type'));
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $response = $response->withAddedHeader('Content-Type', 'text/html');
        $newResponse = $response->withHeader('Content-Length', '123');
        self::assertSame(['Content-Type' => ['application/json', 'text/html']], $response->getHeaders());
        self::assertSame(
            ['Content-Type' => ['application/json', 'text/html'], 'Content-Length' => ['123']],
            $newResponse->getHeaders(),
        );
        $newResponse = $newResponse->withoutHeader('Content-Type');
        self::assertSame(['Content-Length' => ['123']], $newResponse->getHeaders());
    }

    /** @psalm-param class-string<ResponseFactoryInterface> $provider */
    #[DataProvider('responseFactoryProviders')]
    public function testBody(string $provider): void
    {
        $response = $this->getResponse($provider);
        $response->getBody()->write('foo');
        $newBody = $this->getResponse($provider)->getBody();
        $newBody->write('bar');
        $newResponse = $response->withBody($newBody);
        self::assertSame('foo', (string) $response->getBody());
        self::assertSame('bar', (string) $newResponse->getBody());
    }

    /** @psalm-param class-string<ResponseFactoryInterface> $provider */
    #[DataProvider('responseFactoryProviders')]
    public function testStatus(string $provider): void
    {
        $response = $this->getResponse($provider)->withStatus(StatusCodeInterface::STATUS_OK, 'OK');
        $newResponse = $response->withStatus(StatusCodeInterface::STATUS_NOT_FOUND, 'Not Found');
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('OK', $response->getReasonPhrase());
        self::assertSame(StatusCodeInterface::STATUS_NOT_FOUND, $newResponse->getStatusCode());
        self::assertSame('Not Found', $newResponse->getReasonPhrase());
    }

    /** @psalm-param class-string<ResponseFactoryInterface> $provider */
    #[DataProvider('responseFactoryProviders')]
    public function testWrite(string $provider): void
    {
        $response = $this->getResponse($provider);
        $response->getBody()->write('foo');
        $sameResponse = $response->write('bar');
        self::assertSame('foobar', (string) $response->getBody());
        self::assertSame('foobar', (string) $sameResponse->getBody());
        self::assertSame($response, $sameResponse);
    }
}
