<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\DatabaseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ZeroConfPostConnection implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $settings = $this->config->getSettings();
        if (! empty($GLOBALS['server']) && $settings->zeroConf) {
            /** @var Relation $relation */
            $relation = ContainerBuilder::getContainer()->get('relation');
            DatabaseInterface::getInstance()->postConnectControl($relation);
        }

        return $handler->handle($request);
    }
}
