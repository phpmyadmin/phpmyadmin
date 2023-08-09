<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Handler;

use PhpMyAdmin\Application;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final class ApplicationHandler implements RequestHandlerInterface
{
    public function __construct(private readonly Application $application)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        try {
            $response = $this->application->handle($request);
            if ($response === null) {
                throw new ExitException();
            }
        } catch (ExitException) {
            $response = ResponseRenderer::getInstance()->response();
        }

        return $response;
    }
}
