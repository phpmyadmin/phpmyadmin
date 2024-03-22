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
use function is_numeric;
use function is_string;
use function parse_url;
use function preg_match;
use function strpos;
use function strstr;
use function substr;

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

        if (isset($server['HTTP_HOST']) && is_string($server['HTTP_HOST'])) {
            $uri = $uri->withHost($server['HTTP_HOST']);
        } elseif (isset($server['SERVER_NAME']) && is_string($server['SERVER_NAME'])) {
            $uri = $uri->withHost($server['SERVER_NAME']);
        }

        if (isset($server['SERVER_PORT']) && is_numeric($server['SERVER_PORT']) && $server['SERVER_PORT'] >= 1) {
            $uri = $uri->withPort((int) $server['SERVER_PORT']);
        } else {
            $uri = $uri->withPort($uri->getScheme() === 'https' ? 443 : 80);
        }

        if (preg_match('/^(\[[a-fA-F0-9:.]+])(:\d+)?\z/', $uri->getHost(), $matches)) {
            $uri = $uri->withHost($matches[1]);
            if (isset($matches[2])) {
                $uri = $uri->withPort((int) substr($matches[2], 1));
            }
        } else {
            $pos = strpos($uri->getHost(), ':');
            if ($pos !== false) {
                $port = (int) substr($uri->getHost(), $pos + 1);
                $host = (string) strstr($uri->getHost(), ':', true);
                $uri = $uri->withHost($host)->withPort($port);
            }
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

        return $uri;
    }
}
