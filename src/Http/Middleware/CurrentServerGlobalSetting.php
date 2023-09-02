<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function assert;

final class CurrentServerGlobalSetting implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $container = Core::getContainerBuilder();
        $this->setCurrentServerGlobal($container, $this->config, $request->getParam('server'));

        return $handler->handle($request);
    }

    private function setCurrentServerGlobal(
        ContainerInterface $container,
        Config $config,
        mixed $serverParamFromRequest,
    ): void {
        $server = $config->selectServer($serverParamFromRequest);
        $GLOBALS['server'] = $server;
        $GLOBALS['urlParams']['server'] = $server;
        $container->setParameter('server', $server);
        $container->setParameter('url_params', $GLOBALS['urlParams']);
    }
}
