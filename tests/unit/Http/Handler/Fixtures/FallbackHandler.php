<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Handler\Fixtures;

use PhpMyAdmin\Http\Factory\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function implode;

final readonly class FallbackHandler implements RequestHandlerInterface
{
    public function __construct(private ResponseFactory $responseFactory)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string[] $attribute */
        $attribute = $request->getAttribute('attribute');
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(implode(', ', $attribute) . ', ');
        $response->getBody()->write('Fallback, ');

        return $response;
    }
}
