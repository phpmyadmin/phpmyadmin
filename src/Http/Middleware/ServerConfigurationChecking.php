<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Template;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function __;
use function function_exists;

/**
 * Check whether PHP configuration matches our needs.
 */
final class ServerConfigurationChecking implements MiddlewareInterface
{
    public function __construct(
        private readonly Template $template,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * The ini_set and ini_get functions can be disabled using
         * disable_functions, but we're relying on them quite a lot.
         */
        if (function_exists('ini_get') && function_exists('ini_set')) {
            return $handler->handle($request);
        }

        // Disables template caching because the cache directory is not known yet.
        $this->template->disableCache();
        $message = __(
            'The ini_get and/or ini_set functions are disabled in php.ini. phpMyAdmin requires these functions!',
        );
        $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

        return $response->write($this->template->render('error/generic', [
            'lang' => 'en',
            'dir' => 'ltr',
            'error_message' => $message,
        ]));
    }
}
