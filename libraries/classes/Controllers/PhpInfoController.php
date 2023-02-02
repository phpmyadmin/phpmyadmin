<?php
/**
 * phpinfo() wrapper to allow displaying only when configured to do so.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\ServerRequest;

use function phpinfo;

use const INFO_CONFIGURATION;
use const INFO_GENERAL;
use const INFO_MODULES;

/**
 * phpinfo() wrapper to allow displaying only when configured to do so.
 */
class PhpInfoController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $this->response->disable();
        $this->response->getHeader()->sendHttpHeaders();

        if (! $GLOBALS['cfg']['ShowPhpInfo']) {
            return;
        }

        phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
    }
}
