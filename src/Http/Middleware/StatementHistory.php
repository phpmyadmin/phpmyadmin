<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Console\History;
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
    private readonly DatabaseInterface $dbi;

    public function __construct(
        private readonly Config $config,
        private readonly History $history,
        DatabaseInterface|null $dbi = null,
    ) {
        $this->dbi = $dbi ?? DatabaseInterface::getInstance();
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
            $this->history->setHistory(
                Current::$database,
                Current::$table,
                $this->config->selectedServer['user'],
                Current::$sqlQuery,
            );
        }

        return $response;
    }
}
