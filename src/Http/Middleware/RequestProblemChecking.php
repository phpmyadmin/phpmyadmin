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
use function count;

/**
 * Checks request and fails with fatal error if something problematic is found
 */
final class RequestProblemChecking implements MiddlewareInterface
{
    public function __construct(private readonly Template $template, private readonly ResponseFactory $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

            return $response->write($this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'error_message' => __('GLOBALS overwrite attempt'),
            ]));
        }

        /**
         * protect against possible exploits - there is no need to have so many variables
         */
        if (count($_REQUEST) >= 1000) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

            return $response->write($this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'error_message' => __('possible exploit'),
            ]));
        }

        return $handler->handle($request);
    }
}
