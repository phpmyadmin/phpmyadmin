<?php

declare(strict_types=1);

namespace PhpMyAdmin\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Application;
use PhpMyAdmin\Exceptions\MissingExtensionException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Template;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PhpExtensionsChecking implements MiddlewareInterface
{
    public function __construct(
        private readonly Application $application,
        private readonly Template $template,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $this->application->checkRequiredPhpExtensions();
        } catch (MissingExtensionException $exception) {
            // Disables template caching because the cache directory is not known yet.
            $this->template->disableCache();
            $output = $this->template->render('error/generic', [
                'lang' => 'en',
                'dir' => 'ltr',
                'error_message' => $exception->getMessage(),
            ]);
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
            $response->getBody()->write($output);

            return $response;
        }

        return $handler->handle($request);
    }
}
