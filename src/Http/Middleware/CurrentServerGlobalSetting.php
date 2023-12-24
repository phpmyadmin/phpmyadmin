<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final class CurrentServerGlobalSetting implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $this->setCurrentServerGlobal($this->config, $request->getParam('server'));

        return $handler->handle($request);
    }

    private function setCurrentServerGlobal(
        Config $config,
        mixed $serverParamFromRequest,
    ): void {
        Current::$server = $config->selectServer($serverParamFromRequest);
        $GLOBALS['urlParams']['server'] = Current::$server;
    }
}
