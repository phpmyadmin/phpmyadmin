<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Factory;

use GuzzleHttp\Psr7\HttpFactory;
use HttpSoft\Message\UriFactory as HttpSoftUriFactory;
use Laminas\Diactoros\UriFactory as LaminasUriFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Slim\Psr7\Factory\UriFactory as SlimUriFactory;

use function class_exists;
use function count;
use function explode;
use function filter_var;
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

final class UriFactory implements UriFactoryInterface
{
    /** @psalm-var list<class-string<UriFactoryInterface>> */
    private static array $providers = [
        SlimUriFactory::class,
        LaminasUriFactory::class,
        Psr17Factory::class,
        HttpFactory::class,
        HttpSoftUriFactory::class,
    ];

    public function __construct(private UriFactoryInterface $uriFactory)
    {
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return $this->uriFactory->createUri($uri);
    }

    /** @throws RuntimeException When no {@see UriFactoryInterface} implementation is found. */
    public static function create(): self
    {
        foreach (self::$providers as $provider) {
            if (class_exists($provider)) {
                return new self(new $provider());
            }
        }

        throw new RuntimeException('No URI factories found.');
    }

    /**
     * Create new Uri from environment.
     *
     * Initially based on the \Slim\Psr7\Factory\UriFactory::createFromGlobals() implementation.
     *
     * @param mixed[] $server
     */
    public function fromGlobals(array $server): UriInterface
    {
        $uri = $this->createUri();

        $uri = $uri->withScheme(! isset($server['HTTPS']) || $server['HTTPS'] === 'off' ? 'http' : 'https');

        if (isset($server['PHP_AUTH_USER']) && is_string($server['PHP_AUTH_USER']) && $server['PHP_AUTH_USER'] !== '') {
            $uri = $uri->withUserInfo(
                $server['PHP_AUTH_USER'],
                isset($server['PHP_AUTH_PW']) && is_string($server['PHP_AUTH_PW']) ? $server['PHP_AUTH_PW'] : null,
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
            $host = substr($host, 0, (strlen($matches[1]) + 1) * -1);
        }

        if ($host === '' || str_contains($host, ':') || str_contains($host, '[') || str_contains($host, ']')) {
            return ['', $serverPort];
        }

        return [$host, $port ?? $serverPort];
    }

    private function getPort(mixed $port): int|null
    {
        return is_numeric($port) && $port >= 1 && $port <= 65535 ? (int) $port : null;
    }
}
