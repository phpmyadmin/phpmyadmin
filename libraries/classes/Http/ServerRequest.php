<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

use function is_array;
use function is_object;
use function is_string;
use function property_exists;

class ServerRequest implements ServerRequestInterface
{
    final public function __construct(private ServerRequestInterface $serverRequest)
    {
    }

    /** @inheritDoc */
    public function getProtocolVersion()
    {
        return $this->serverRequest->getProtocolVersion();
    }

    /** @inheritDoc */
    public function withProtocolVersion($version)
    {
        $serverRequest = $this->serverRequest->withProtocolVersion($version);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getHeaders()
    {
        return $this->serverRequest->getHeaders();
    }

    /** @inheritDoc */
    public function hasHeader($name)
    {
        return $this->serverRequest->hasHeader($name);
    }

    /** @inheritDoc */
    public function getHeader($name)
    {
        return $this->serverRequest->getHeader($name);
    }

    /** @inheritDoc */
    public function getHeaderLine($name)
    {
        return $this->serverRequest->getHeaderLine($name);
    }

    /** @inheritDoc */
    public function withHeader($name, $value)
    {
        $serverRequest = $this->serverRequest->withHeader($name, $value);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function withAddedHeader($name, $value)
    {
        $serverRequest = $this->serverRequest->withAddedHeader($name, $value);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function withoutHeader($name)
    {
        $serverRequest = $this->serverRequest->withoutHeader($name);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getBody()
    {
        return $this->serverRequest->getBody();
    }

    /** @inheritDoc */
    public function withBody(StreamInterface $body)
    {
        $serverRequest = $this->serverRequest->withBody($body);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getRequestTarget()
    {
        return $this->serverRequest->getRequestTarget();
    }

    /** @inheritDoc */
    public function withRequestTarget($requestTarget)
    {
        $serverRequest = $this->serverRequest->withRequestTarget($requestTarget);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getMethod()
    {
        return $this->serverRequest->getMethod();
    }

    /** @inheritDoc */
    public function withMethod($method)
    {
        $serverRequest = $this->serverRequest->withMethod($method);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getUri()
    {
        return $this->serverRequest->getUri();
    }

    /** @inheritDoc */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $serverRequest = $this->serverRequest->withUri($uri, $preserveHost);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getServerParams()
    {
        return $this->serverRequest->getServerParams();
    }

    /** @inheritDoc */
    public function getCookieParams()
    {
        return $this->serverRequest->getCookieParams();
    }

    /** @inheritDoc */
    public function withCookieParams(array $cookies)
    {
        $serverRequest = $this->serverRequest->withCookieParams($cookies);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getQueryParams()
    {
        return $this->serverRequest->getQueryParams();
    }

    /** @inheritDoc */
    public function withQueryParams(array $query)
    {
        $serverRequest = $this->serverRequest->withQueryParams($query);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getUploadedFiles()
    {
        return $this->serverRequest->getUploadedFiles();
    }

    /** @inheritDoc */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $serverRequest = $this->serverRequest->withUploadedFiles($uploadedFiles);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getParsedBody()
    {
        return $this->serverRequest->getParsedBody();
    }

    /** @inheritDoc */
    public function withParsedBody($data)
    {
        $serverRequest = $this->serverRequest->withParsedBody($data);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getAttributes()
    {
        return $this->serverRequest->getAttributes();
    }

    /** @inheritDoc */
    public function getAttribute($name, $default = null)
    {
        return $this->serverRequest->getAttribute($name, $default);
    }

    /** @inheritDoc */
    public function withAttribute($name, $value)
    {
        $serverRequest = $this->serverRequest->withAttribute($name, $value);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function withoutAttribute($name)
    {
        $serverRequest = $this->serverRequest->withoutAttribute($name);

        return new static($serverRequest);
    }

    public function getParam(string $param, mixed $default = null): mixed
    {
        $getParams = $this->getQueryParams();
        $postParams = $this->getParsedBody();

        if (is_array($postParams) && isset($postParams[$param])) {
            return $postParams[$param];
        }

        if (is_object($postParams) && property_exists($postParams, $param)) {
            return $postParams->$param;
        }

        return $getParams[$param] ?? $default;
    }

    public function getParsedBodyParam(string $param, mixed $default = null): mixed
    {
        $postParams = $this->getParsedBody();

        if (is_array($postParams) && isset($postParams[$param])) {
            return $postParams[$param];
        }

        if (is_object($postParams) && property_exists($postParams, $param)) {
            return $postParams->$param;
        }

        return $default;
    }

    public function getQueryParam(string $param, mixed $default = null): mixed
    {
        $getParams = $this->getQueryParams();

        return $getParams[$param] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /** @psalm-return non-empty-string */
    public function getRoute(): string
    {
        $getParams = $this->getQueryParams();
        $postParams = $this->getParsedBody();
        $route = '/';
        if (isset($getParams['route']) && is_string($getParams['route']) && $getParams['route'] !== '') {
            $route = $getParams['route'];
        } elseif (
            is_array($postParams)
            && isset($postParams['route'])
            && is_string($postParams['route'])
            && $postParams['route'] !== ''
        ) {
            $route = $postParams['route'];
        }

        /**
         * See FAQ 1.34.
         *
         * @see https://docs.phpmyadmin.net/en/latest/faq.html#faq1-34
         */
        $db = isset($getParams['db']) && is_string($getParams['db']) ? $getParams['db'] : '';
        if ($route === '/' && $db !== '') {
            $table = isset($getParams['table']) && is_string($getParams['table']) ? $getParams['table'] : '';
            $route = $table === '' ? '/database/structure' : '/sql';
        }

        return $route;
    }

    public function has(string $param): bool
    {
        return $this->hasBodyParam($param) || $this->hasQueryParam($param);
    }

    public function hasQueryParam(string $param): bool
    {
        $getParams = $this->getQueryParams();

        return isset($getParams[$param]);
    }

    public function hasBodyParam(string $param): bool
    {
        $postParams = $this->getParsedBody();

        return is_array($postParams) && isset($postParams[$param])
            || is_object($postParams) && property_exists($postParams, $param);
    }
}
