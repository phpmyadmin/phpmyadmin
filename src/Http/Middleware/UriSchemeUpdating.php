<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UriSchemeUpdating implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uriScheme = $this->config->isHttps() ? 'https' : 'http';
        $uri = $request->getUri();
        if ($uri->getScheme() !== $uriScheme) {
            $request = $request->withUri($uri->withScheme($uriScheme));
        }

        return $handler->handle($request);
    }
}
