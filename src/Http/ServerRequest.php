<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Webmozart\Assert\Assert;

use function is_array;
use function is_object;
use function is_string;
use function property_exists;

class ServerRequest implements ServerRequestInterface
{
    final public function __construct(private ServerRequestInterface $serverRequest)
    {
    }

    public function getProtocolVersion(): string
    {
        return $this->serverRequest->getProtocolVersion();
    }

    /** @inheritDoc */
    public function withProtocolVersion($version): MessageInterface
    {
        $serverRequest = $this->serverRequest->withProtocolVersion($version);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getHeaders(): array
    {
        return $this->serverRequest->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->serverRequest->hasHeader($name);
    }

    /** @inheritDoc */
    public function getHeader(string $name): array
    {
        return $this->serverRequest->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->serverRequest->getHeaderLine($name);
    }

    /** @inheritDoc */
    public function withHeader(string $name, $value): MessageInterface
    {
        $serverRequest = $this->serverRequest->withHeader($name, $value);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $serverRequest = $this->serverRequest->withAddedHeader($name, $value);

        return new static($serverRequest);
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $serverRequest = $this->serverRequest->withoutHeader($name);

        return new static($serverRequest);
    }

    public function getBody(): StreamInterface
    {
        return $this->serverRequest->getBody();
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $serverRequest = $this->serverRequest->withBody($body);

        return new static($serverRequest);
    }

    public function getRequestTarget(): string
    {
        return $this->serverRequest->getRequestTarget();
    }

    /** @inheritDoc */
    public function withRequestTarget($requestTarget): ServerRequestInterface
    {
        $serverRequest = $this->serverRequest->withRequestTarget($requestTarget);

        return new static($serverRequest);
    }

    public function getMethod(): string
    {
        return $this->serverRequest->getMethod();
    }

    /** @inheritDoc */
    public function withMethod($method): ServerRequestInterface
    {
        $serverRequest = $this->serverRequest->withMethod($method);

        return new static($serverRequest);
    }

    public function getUri(): UriInterface
    {
        return $this->serverRequest->getUri();
    }

    /** @inheritDoc */
    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface
    {
        $serverRequest = $this->serverRequest->withUri($uri, $preserveHost);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getServerParams(): array
    {
        return $this->serverRequest->getServerParams();
    }

    /** @inheritDoc */
    public function getCookieParams(): array
    {
        return $this->serverRequest->getCookieParams();
    }

    /** @inheritDoc */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $serverRequest = $this->serverRequest->withCookieParams($cookies);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getQueryParams(): array
    {
        return $this->serverRequest->getQueryParams();
    }

    /** @inheritDoc */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $serverRequest = $this->serverRequest->withQueryParams($query);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getUploadedFiles(): array
    {
        return $this->serverRequest->getUploadedFiles();
    }

    /** @inheritDoc */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
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
    public function withParsedBody($data): ServerRequestInterface
    {
        $serverRequest = $this->serverRequest->withParsedBody($data);

        return new static($serverRequest);
    }

    /** @inheritDoc */
    public function getAttributes(): array
    {
        return $this->serverRequest->getAttributes();
    }

    /** @inheritDoc */
    public function getAttribute($name, $default = null)
    {
        return $this->serverRequest->getAttribute($name, $default);
    }

    /** @inheritDoc */
    public function withAttribute($name, $value): ServerRequestInterface
    {
        $serverRequest = $this->serverRequest->withAttribute($name, $value);

        return new static($serverRequest);
    }

    public function withoutAttribute(string $name): ServerRequestInterface
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
        return $this->getMethod() === RequestMethodInterface::METHOD_POST;
    }

    /** @psalm-return non-empty-string */
    public function getRoute(): string
    {
        $route = $this->getAttribute('route') ?? $this->getQueryParam('route') ?? $this->getParsedBodyParam('route');
        if (! is_string($route) || $route === '') {
            return '/';
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

    public function isAjax(): bool
    {
        return $this->serverRequest->getHeaderLine('X-Requested-With') === 'XMLHttpRequest'
            || $this->has('ajax_request');
    }

    public function getParsedBodyParamAsString(string $param, string|null $default = null): string
    {
        $value = $this->getParsedBodyParam($param, $default);
        Assert::string($value);

        return $value;
    }

    public function getParsedBodyParamAsStringOrNull(string $param, string|null $default = null): string|null
    {
        $value = $this->getParsedBodyParam($param, $default);
        Assert::nullOrString($value);

        return $value;
    }
}
