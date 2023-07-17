<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Factory;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\HttpFactory;
use HttpSoft\Message\ResponseFactory as HttpSoftResponseFactory;
use Laminas\Diactoros\ResponseFactory as LaminasResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpMyAdmin\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use RuntimeException;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;

use function class_exists;

final class ResponseFactory implements ResponseFactoryInterface
{
    /** @psalm-var list<class-string<ResponseFactoryInterface>> */
    private static array $providers = [
        SlimResponseFactory::class,
        LaminasResponseFactory::class,
        Psr17Factory::class,
        HttpFactory::class,
        HttpSoftResponseFactory::class,
    ];

    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function createResponse(int $code = StatusCodeInterface::STATUS_OK, string $reasonPhrase = ''): Response
    {
        return new Response($this->responseFactory->createResponse($code, $reasonPhrase));
    }

    /** @throws RuntimeException When no {@see ResponseFactoryInterface} implementation is found. */
    public static function create(): self
    {
        foreach (self::$providers as $provider) {
            if (class_exists($provider)) {
                return new self(new $provider());
            }
        }

        throw new RuntimeException('No HTTP response factories found.');
    }
}
