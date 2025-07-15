<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Handler\Fixtures\Middleware;

use PhpMyAdmin\Http\Factory\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function implode;

final readonly class EarlyReturn implements MiddlewareInterface
{
    public function __construct(private ResponseFactory $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('early') !== true) {
            return $handler->handle($request);
        }

        /** @var string[] $attribute */
        $attribute = $request->getAttribute('attribute');
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(implode(', ', $attribute) . ', ');
        $response->getBody()->write('Early, ');

        return $response;
    }
}
