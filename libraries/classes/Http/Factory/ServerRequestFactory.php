<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Factory;

use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ServerRequestFactory as LaminasServerRequestFactory;
use Laminas\Diactoros\UriFactory as LaminasUriFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Factory\ServerRequestFactory as SlimServerRequestFactory;
use Slim\Psr7\Factory\UriFactory as SlimUriFactory;

use function class_exists;
use function count;
use function current;
use function explode;
use function filter_var;
use function in_array;
use function is_callable;
use function is_numeric;
use function is_string;
use function parse_url;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strlen;
use function substr;

use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
use const PHP_URL_QUERY;

class ServerRequestFactory
{
    /** @var ServerRequestFactoryInterface */
    private $serverRequestFactory;

    /** @var UriFactoryInterface */
    private $uriFactory;

    /** @var mixed */
    private static $getAllHeaders = 'getallheaders';

    public function __construct(
        ?ServerRequestFactoryInterface $serverRequestFactory = null,
        ?UriFactoryInterface $uriFactory = null
    ) {
        $this->serverRequestFactory = $serverRequestFactory ?? $this->createServerRequestFactory();
        $this->uriFactory = $uriFactory ?? $this->createUriFactory();
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
        } else {
            $factory = new SlimServerRequestFactory();
        }

        return $factory;
    }

    private function createUriFactory(): UriFactoryInterface
    {
        if (class_exists(Psr17Factory::class)) {
            /** @var UriFactoryInterface $factory */
            $factory = new Psr17Factory();
        } elseif (class_exists(HttpFactory::class)) {
            /** @var UriFactoryInterface $factory */
            $factory = new HttpFactory();
        } elseif (class_exists(LaminasUriFactory::class)) {
            /** @var UriFactoryInterface $factory */
            $factory = new LaminasUriFactory();
        } else {
            $factory = new SlimUriFactory();
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

    /**
     * @return array<string, string>
     */
    protected function getallheaders(): array
    {
        /** @var array<string, string> $headers */
        $headers = is_callable(self::$getAllHeaders) ? (self::$getAllHeaders)() : [];

        return $headers;
    }

    private static function createServerRequestFromGlobals(self $creator): ServerRequestInterface
    {
        $serverRequest = $creator->serverRequestFactory->createServerRequest(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $creator->createUriFromGlobals($_SERVER),
            $_SERVER
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

    /**
     * Create new Uri from environment.
     *
     * Initially based on the \Slim\Psr7\Factory\UriFactory::createFromGlobals() implementation.
     *
     * @param mixed[] $server
     */
    private function createUriFromGlobals(array $server): UriInterface
    {
        $uri = $this->uriFactory->createUri('');

        $uri = $uri->withScheme(! isset($server['HTTPS']) || $server['HTTPS'] === 'off' ? 'http' : 'https');

        if (isset($server['PHP_AUTH_USER']) && is_string($server['PHP_AUTH_USER']) && $server['PHP_AUTH_USER'] !== '') {
            $uri = $uri->withUserInfo(
                $server['PHP_AUTH_USER'],
                isset($server['PHP_AUTH_PW']) && is_string($server['PHP_AUTH_PW']) ? $server['PHP_AUTH_PW'] : null
            );
        }

        [$host, $port] = $this->getHostAndPort($server);
        $uri = $uri->withHost($host !== '' ? $host : 'localhost');
        if ($port !== null) {
            $uri = $uri->withPort($port);
        }

        if (isset($server['QUERY_STRING']) && is_string($server['QUERY_STRING'])) {
            $uri = $uri->withQuery($server['QUERY_STRING']);
        }

        if (isset($server['REQUEST_URI']) && is_string($server['REQUEST_URI'])) {
            $uriFragments = explode('?', $server['REQUEST_URI']);
            $uri = $uri->withPath($uriFragments[0]);
            if ($uri->getQuery() === '' && count($uriFragments) > 1) {
                $query = parse_url('https://www.example.com' . $server['REQUEST_URI'], PHP_URL_QUERY);
                if (is_string($query) && $query !== '') {
                    $uri = $uri->withQuery($query);
                }
            }
        }

        $path = $uri->getPath();
        if (! str_starts_with($path, '/')) {
            $uri = $uri->withPath('/' . $path);
        }

        return $uri;
    }

    /**
     * @param array<mixed> $server
     *
     * @return array{string, int|null}
     */
    private function getHostAndPort(array $server): array
    {
        $host = '';
        if (isset($server['HTTP_HOST']) && is_string($server['HTTP_HOST'])) {
            $host = $server['HTTP_HOST'];
        } elseif (isset($server['SERVER_NAME']) && is_string($server['SERVER_NAME'])) {
            $host = $server['SERVER_NAME'];
        } elseif (isset($server['SERVER_ADDR']) && is_string($server['SERVER_ADDR'])) {
            $host = $server['SERVER_ADDR'];
        }

        $serverPort = $this->getPort($server['SERVER_PORT'] ?? null);
        if (preg_match('/[\x00-\x20\x7F\/\?#@\\\\,]/', $host) !== 0) {
            return ['', $serverPort];
        }

        if (
            preg_match('/^(\[[a-fA-F0-9:.]+])(:\d+)?\z/', $host, $matches) === 1
            && filter_var(substr($matches[1], 1, -1), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
        ) {
            $port = isset($matches[2]) ? $this->getPort(substr($matches[2], 1)) : null;

            return [$matches[1], $port ?? $serverPort];
        }

        $port = null;
        if (preg_match('/:(\d+)$/', $host, $matches) === 1) {
            $port = $this->getPort($matches[1]);
            $host = (string) substr($host, 0, (strlen($matches[1]) + 1) * -1);
        }

        if ($host === '' || str_contains($host, ':') || str_contains($host, '[') || str_contains($host, ']')) {
            return ['', $serverPort];
        }

        return [$host, $port ?? $serverPort];
    }

    /** @param mixed $port */
    private function getPort($port): ?int
    {
        return is_numeric($port) && $port >= 1 && $port <= 65535 ? (int) $port : null;
    }
}
