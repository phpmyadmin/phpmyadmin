<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Factory;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\HttpFactory;
use HttpSoft\Message\ServerRequestFactory as HttpSoftServerRequestFactory;
use Laminas\Diactoros\ServerRequestFactory as LaminasServerRequestFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Slim\Psr7\Factory\ServerRequestFactory as SlimServerRequestFactory;

use function class_exists;
use function current;
use function explode;
use function in_array;
use function is_callable;

final class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /** @psalm-var list<class-string<ServerRequestFactoryInterface>> */
    private static array $providers = [
        SlimServerRequestFactory::class,
        LaminasServerRequestFactory::class,
        Psr17Factory::class,
        HttpFactory::class,
        HttpSoftServerRequestFactory::class,
    ];

    private static mixed $getAllHeaders = 'getallheaders';

    public function __construct(private ServerRequestFactoryInterface $serverRequestFactory)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param string $method
     * @psalm-param UriInterface|string $uri
     * @psalm-param array<mixed> $serverParams
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequest
    {
        return new ServerRequest($this->serverRequestFactory->createServerRequest($method, $uri, $serverParams));
    }

    /** @throws RuntimeException When no {@see ServerRequestFactoryInterface} implementation is found. */
    public static function create(): self
    {
        foreach (self::$providers as $provider) {
            if (class_exists($provider)) {
                return new self(new $provider());
            }
        }

        throw new RuntimeException('No HTTP server request factories found.');
    }

    public function fromGlobals(): ServerRequest
    {
        $serverRequest = $this->createServerRequest(
            $_SERVER['REQUEST_METHOD'] ?? RequestMethodInterface::METHOD_GET,
            UriFactory::create()->fromGlobals($_SERVER),
            $_SERVER,
        );

        foreach ($this->getAllHeaders() as $name => $value) {
            $serverRequest = $serverRequest->withAddedHeader($name, $value);
        }

        $serverRequest = $serverRequest->withQueryParams($_GET);

        if (! $serverRequest->isPost()) {
            return $serverRequest;
        }

        $contentType = '';
        foreach ($serverRequest->getHeader('Content-Type') as $headerValue) {
            $contentType = current(explode(';', $headerValue));
        }

        if (in_array($contentType, ['application/x-www-form-urlencoded', 'multipart/form-data'], true)) {
            return $serverRequest->withParsedBody($_POST);
        }

        return $serverRequest;
    }

    /** @return array<string, string> */
    private function getAllHeaders(): array
    {
        /** @var array<string, string> $headers */
        $headers = is_callable(self::$getAllHeaders) ? (self::$getAllHeaders)() : [];

        return $headers;
    }
}
