<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Handler\Fixtures\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Second implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var string[] $attribute */
        $attribute = $request->getAttribute('attribute');
        $attribute[] = 'Second before';
        $response = $handler->handle($request->withAttribute('attribute', $attribute));
        $response->getBody()->write('Second after, ');

        return $response;
    }
}
