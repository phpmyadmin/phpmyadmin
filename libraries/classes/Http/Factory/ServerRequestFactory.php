<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Factory;

use GuzzleHttp\Psr7\HttpFactory;
use HttpSoft\Message\ServerRequestFactory as HttpSoftServerRequestFactory;
use Laminas\Diactoros\ServerRequestFactory as LaminasServerRequestFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory as SlimServerRequestFactory;

use function class_exists;
use function current;
use function explode;
use function function_exists;
use function getallheaders;
use function in_array;

class ServerRequestFactory
{
    private ServerRequestFactoryInterface $serverRequestFactory;

    public function __construct(ServerRequestFactoryInterface|null $serverRequestFactory = null)
    {
        $this->serverRequestFactory = $serverRequestFactory ?? $this->createServerRequestFactory();
    }

    private function createServerRequestFactory(): ServerRequestFactoryInterface
    {
        if (class_exists(Psr17Factory::class)) {
            /** @var ServerRequestFactoryInterface $factory */
            $factory = new Psr17Factory();
        } elseif (class_exists(HttpFactory::class)) {
            /** @var ServerRequestFactoryInterface $factory */
            $factory = new HttpFactory();
        } elseif (class_exists(LaminasServerRequestFactory::class)) {
            /** @var ServerRequestFactoryInterface $factory */
            $factory = new LaminasServerRequestFactory();
        } elseif (class_exists(HttpSoftServerRequestFactory::class)) {
            /** @var ServerRequestFactoryInterface $factory */
            $factory = new HttpSoftServerRequestFactory();
        } else {
            $factory = new SlimServerRequestFactory();
        }

        return $factory;
    }

    public static function createFromGlobals(): ServerRequest
    {
        if (class_exists(SlimServerRequestFactory::class)) {
            /** @psalm-suppress InternalMethod */
            $serverRequest = SlimServerRequestFactory::createFromGlobals();
        } elseif (class_exists(LaminasServerRequestFactory::class)) {
            /** @var ServerRequestInterface $serverRequest */
            $serverRequest = LaminasServerRequestFactory::fromGlobals();
        } else {
            $creator = new self();
            $serverRequest = self::createServerRequestFromGlobals($creator);
        }

        return new ServerRequest($serverRequest);
    }

    /** @return array<string, string> */
    protected function getallheaders(): array
    {
        /** @var array<string, string> $headers */
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        return $headers;
    }

    private static function createServerRequestFromGlobals(self $creator): ServerRequestInterface
    {
        $uriFactory = UriFactory::create();
        $serverRequest = $creator->serverRequestFactory->createServerRequest(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $uriFactory->fromGlobals($_SERVER),
            $_SERVER,
        );

        foreach ($creator->getallheaders() as $name => $value) {
            $serverRequest = $serverRequest->withAddedHeader($name, $value);
        }

        $serverRequest = $serverRequest->withQueryParams($_GET);

        if ($serverRequest->getMethod() !== 'POST') {
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
}
