<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Forms\Setup\ConfigForm;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Url;

use function is_string;

final class ShowConfigController
{
    public function __invoke(ServerRequest $request): void
    {
        $formDisplay = new ConfigForm($GLOBALS['ConfigFile']);
        $formDisplay->save(['Config']);

        $response = ResponseRenderer::getInstance();
        $response->disable();

        /** @var mixed $eol */
        $eol = $request->getParsedBodyParam('eol');
        if ($eol !== null) {
            $_SESSION['eol'] = $eol === 'unix' ? 'unix' : 'win';
        }

        /** @var mixed $submitClear */
        $submitClear = $request->getParsedBodyParam('submit_clear');
        if (is_string($submitClear) && $submitClear !== '') {
            // Clear current config and return to main page
            $GLOBALS['ConfigFile']->resetConfigData();
            // drop post data
            $response->generateHeader303('../setup/index.php' . Url::getCommonRaw(['route' => '/setup']));

            return;
        }

        /** @var mixed $submitDownload */
        $submitDownload = $request->getParsedBodyParam('submit_download');
        if (is_string($submitDownload) && $submitDownload !== '') {
            // Output generated config file
            Core::downloadHeader('config.inc.php', 'text/plain');
            $response->disable();
            echo ConfigGenerator::getConfigFile($GLOBALS['ConfigFile']);

            return;
        }

        // Show generated config file in a <textarea>
        $response->generateHeader303(
            '../setup/index.php' . Url::getCommonRaw(['route' => '/setup', 'page' => 'config']),
        );
    }
}
