<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Exceptions\SessionHandlerException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Session;
use PhpMyAdmin\Template;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SessionHandling implements MiddlewareInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly ErrorHandler $errorHandler,
        private readonly Template $template,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('route') === '/messages') {
            return $handler->handle($request);
        }

        try {
            // Include session handling after the globals, to prevent overwriting.
            Session::setUp($this->config, $this->errorHandler);
        } catch (SessionHandlerException $exception) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

            return $response->write($this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => LanguageManager::$textDir,
                'error_message' => $exception->getMessage(),
            ]));
        }

        return $handler->handle($request);
    }
}
