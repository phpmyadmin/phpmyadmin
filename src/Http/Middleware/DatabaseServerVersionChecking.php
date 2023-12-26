<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Template;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function __;
use function sprintf;

final class DatabaseServerVersionChecking implements MiddlewareInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Template $template,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $settings = $this->config->getSettings();
        if (
            $this->config->hasSelectedServer()
            && DatabaseInterface::getInstance()->getVersion() < $settings->mysqlMinVersion['internal']
        ) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

            return $response->write($this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => LanguageManager::$textDir,
                'error_message' => sprintf(
                    __('You should upgrade to %s %s or later.'),
                    'MySQL',
                    $settings->mysqlMinVersion['human'],
                ),
            ]));
        }

        return $handler->handle($request);
    }
}
