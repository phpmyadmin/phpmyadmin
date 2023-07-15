<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    final public function __construct(private ResponseInterface $response)
    {
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    /** @inheritDoc */
    public function withProtocolVersion(string $version)
    {
        $response = $this->response->withProtocolVersion($version);

        return new static($response);
    }

    /** @inheritDoc */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    /** @inheritDoc */
    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    /** @inheritDoc */
    public function withHeader(string $name, $value)
    {
        $response = $this->response->withHeader($name, $value);

        return new static($response);
    }

    /** @inheritDoc */
    public function withAddedHeader(string $name, $value)
    {
        $response = $this->response->withAddedHeader($name, $value);

        return new static($response);
    }

    /** @inheritDoc */
    public function withoutHeader(string $name)
    {
        $response = $this->response->withoutHeader($name);

        return new static($response);
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    /** @inheritDoc */
    public function withBody(StreamInterface $body)
    {
        $response = $this->response->withBody($body);

        return new static($response);
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /** @inheritDoc */
    public function withStatus(int $code, string $reasonPhrase = '')
    {
        $response = $this->response->withStatus($code, $reasonPhrase);

        return new static($response);
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }
}
