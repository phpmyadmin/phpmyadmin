<?php

declare(strict_types=1);

namespace PhpMyAdmin\Middleware;

use PhpMyAdmin\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GlobalConfigSetting implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $GLOBALS['cfg'] = $this->config->settings;

        return $handler->handle($request);
    }
}
