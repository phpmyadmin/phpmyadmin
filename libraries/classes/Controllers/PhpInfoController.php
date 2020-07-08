<?php
/**
 * phpinfo() wrapper to allow displaying only when configured to do so.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use const INFO_CONFIGURATION;
use const INFO_GENERAL;
use const INFO_MODULES;
use function phpinfo;

/**
 * phpinfo() wrapper to allow displaying only when configured to do so.
 */
class PhpInfoController extends AbstractController
{
    public function index(Request $request, Response $response): Response
    {
        global $cfg;

        $this->response->disable();
        $this->response->getHeader()->sendHttpHeaders();

        if (! $cfg['ShowPhpInfo']) {
            return $response;
        }

        phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);

        return $response;
    }
}
