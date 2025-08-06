<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function ob_get_clean;
use function ob_start;
use function phpinfo;

use const INFO_CONFIGURATION;
use const INFO_GENERAL;
use const INFO_MODULES;

/**
 * phpinfo() wrapper to allow displaying only when configured to do so.
 */
#[Route('/phpinfo', ['GET'])]
final class PhpInfoController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly ResponseFactory $responseFactory,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $response = $this->responseFactory->createResponse();
        foreach ($this->response->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        if (! $this->config->settings['ShowPhpInfo']) {
            $response = $response->withHeader('Location', $this->response->fixRelativeUrlForRedirect('./'));

            return $response->withStatus(StatusCodeInterface::STATUS_FOUND);
        }

        ob_start();
        phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
        $phpInfo = (string) ob_get_clean();

        return $response->write($phpInfo);
    }
}
