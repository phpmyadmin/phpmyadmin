<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function assert;
use function is_string;

final class SqlQueryGlobalSetting implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $container = Core::getContainerBuilder();
        $this->setSQLQueryGlobalFromRequest($container, $request);

        return $handler->handle($request);
    }

    private function setSQLQueryGlobalFromRequest(ContainerInterface $container, ServerRequest $request): void
    {
        $sqlQuery = '';
        if ($request->isPost()) {
            /** @var mixed $sqlQuery */
            $sqlQuery = $request->getParsedBodyParam('sql_query');
            if (! is_string($sqlQuery)) {
                $sqlQuery = '';
            }
        }

        $GLOBALS['sql_query'] = $sqlQuery;
        $container->setParameter('sql_query', $sqlQuery);
    }
}
