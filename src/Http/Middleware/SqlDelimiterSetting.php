<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\SqlParser\Lexer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function is_string;

final class SqlDelimiterSetting implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->config->hasSelectedServer()) {
            assert($request instanceof ServerRequest);
            /** @var mixed $sqlDelimiter */
            $sqlDelimiter = $request->getParam('sql_delimiter', '');
            if (is_string($sqlDelimiter) && $sqlDelimiter !== '') {
                // Sets the default delimiter (if specified).
                Lexer::$defaultDelimiter = $sqlDelimiter;
            }
        }

        return $handler->handle($request);
    }
}
