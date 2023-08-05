<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response implements ResponseInterface
{
    public function __construct(private ResponseInterface $response)
    {
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): Response
    {
        return new Response($this->response->withProtocolVersion($version));
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
    public function withHeader(string $name, $value): Response
    {
        return new Response($this->response->withHeader($name, $value));
    }

    /** @inheritDoc */
    public function withAddedHeader(string $name, $value): Response
    {
        return new Response($this->response->withAddedHeader($name, $value));
    }

    public function withoutHeader(string $name): Response
    {
        return new Response($this->response->withoutHeader($name));
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function withBody(StreamInterface $body): Response
    {
        return new Response($this->response->withBody($body));
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function withStatus(int $code, string $reasonPhrase = ''): Response
    {
        return new Response($this->response->withStatus($code, $reasonPhrase));
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * Write data to the body of the response.
     */
    public function write(string $string): Response
    {
        $this->response->getBody()->write($string);

        return $this;
    }
}
