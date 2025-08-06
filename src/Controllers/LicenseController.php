<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;
use function basename;
use function is_readable;
use function ob_get_clean;
use function ob_start;
use function readfile;
use function sprintf;

/**
 * Simple script to set correct charset for the license
 */
#[Route('/license', ['GET'])]
final class LicenseController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $response = $this->responseFactory->createResponse();
        foreach ($this->response->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $filename = LICENSE_FILE;

        // Check if the file is available, some distributions remove these.
        if (! @is_readable($filename)) {
            return $response->write(sprintf(
                __('The %s file is not available on this system, please visit %s for more information.'),
                basename($filename),
                '<a href="' . Core::linkURL('https://www.phpmyadmin.net/')
                . '" rel="noopener noreferrer" target="_blank">phpmyadmin.net</a>',
            ));
        }

        $response = $response->withHeader('Content-Type', 'text/plain; charset=utf-8');

        ob_start();
        readfile($filename);
        $license = (string) ob_get_clean();

        return $response->write($license);
    }
}
