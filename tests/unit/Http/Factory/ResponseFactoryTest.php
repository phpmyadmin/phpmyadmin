<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Factory;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\HttpFactory;
use HttpSoft\Message\ResponseFactory as HttpSoftResponseFactory;
use Laminas\Diactoros\ResponseFactory as LaminasResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionProperty;
use RuntimeException;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;

use function class_exists;

#[CoversClass(ResponseFactory::class)]
final class ResponseFactoryTest extends TestCase
{
    /**
     * @psalm-param class-string<ResponseFactoryInterface> $provider
     * @psalm-param class-string<ResponseInterface> $expectedResponse
     */
    #[DataProvider('providerForTestCreateResponse')]
    public function testCreateResponse(string $provider, string $expectedResponse): void
    {
        $this->skipIfNotAvailable($provider);
        $responseFactory = new ResponseFactory(new $provider());
        $response = $responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND, 'Not Found');
        self::assertSame(StatusCodeInterface::STATUS_NOT_FOUND, $response->getStatusCode());
        self::assertSame('Not Found', $response->getReasonPhrase());
        $actual = (new ReflectionProperty(Response::class, 'response'))->getValue($response);
        self::assertInstanceOf($expectedResponse, $actual);
    }

    /** @return iterable<string, array{class-string<ResponseFactoryInterface>, class-string<ResponseInterface>}> */
    public static function providerForTestCreateResponse(): iterable
    {
        yield 'slim/psr7' => [SlimResponseFactory::class, \Slim\Psr7\Response::class];
        yield 'laminas/laminas-diactoros' => [LaminasResponseFactory::class, \Laminas\Diactoros\Response::class];
        yield 'nyholm/psr7' => [Psr17Factory::class, \Nyholm\Psr7\Response::class];
        yield 'guzzlehttp/psr7' => [HttpFactory::class, \GuzzleHttp\Psr7\Response::class];
        yield 'httpsoft/http-message' => [HttpSoftResponseFactory::class, \HttpSoft\Message\Response::class];
    }

    /** @psalm-param class-string<ResponseFactoryInterface> $provider */
    #[DataProvider('providerForTestCreate')]
    #[BackupStaticProperties(true)]
    public function testCreate(string $provider): void
    {
        $this->skipIfNotAvailable($provider);
        (new ReflectionProperty(ResponseFactory::class, 'providers'))->setValue(null, [$provider]);
        $responseFactory = ResponseFactory::create();
        $actual = (new ReflectionProperty(ResponseFactory::class, 'responseFactory'))->getValue($responseFactory);
        self::assertInstanceOf($provider, $actual);
    }

    /** @return iterable<string, array{class-string<ResponseFactoryInterface>}> */
    public static function providerForTestCreate(): iterable
    {
        yield 'slim/psr7' => [SlimResponseFactory::class];
        yield 'laminas/laminas-diactoros' => [LaminasResponseFactory::class];
        yield 'nyholm/psr7' => [Psr17Factory::class];
        yield 'guzzlehttp/psr7' => [HttpFactory::class];
        yield 'httpsoft/http-message' => [HttpSoftResponseFactory::class];
    }

    #[BackupStaticProperties(true)]
    public function testCreateWithoutProvider(): void
    {
        (new ReflectionProperty(ResponseFactory::class, 'providers'))->setValue(null, ['InvalidResponseFactoryClass']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No HTTP response factories found.');
        ResponseFactory::create();
    }

    /** @psalm-param class-string<ResponseFactoryInterface> $provider */
    private function skipIfNotAvailable(string $provider): void
    {
        if (class_exists($provider)) {
            return;
        }

        // This can happen when testing without the development packages.
        self::markTestSkipped($provider . ' is not available.');
    }
}
