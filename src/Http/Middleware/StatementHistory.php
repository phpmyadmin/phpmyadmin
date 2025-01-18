<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final class StatementHistory implements MiddlewareInterface
{
    private readonly Relation $relation;
    private readonly DatabaseInterface $dbi;

    public function __construct(
        private readonly Config $config,
        DatabaseInterface|null $dbi = null,
        Relation|null $relation = null,
    ) {
        $this->dbi = $dbi ?? DatabaseInterface::getInstance();
        $this->relation = $relation ?? new Relation($this->dbi, $this->config);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $response = $handler->handle($request);

        if (
            ! $request->has('no_history')
            && Current::$sqlQuery !== ''
            && $this->dbi->isConnected()
        ) {
            $this->relation->setHistory(
                Current::$database,
                Current::$table,
                $this->config->selectedServer['user'],
                Current::$sqlQuery,
            );
        }

        return $response;
    }
}
