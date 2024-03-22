<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config\Forms\Setup\ConfigForm;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Url;

use function is_string;

final class ShowConfigController
{
    public function __invoke(ServerRequest $request): Response
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
            $response->addHeader('Location', '../setup/index.php' . Url::getCommonRaw(['route' => '/setup']));
            $response->setStatusCode(StatusCodeInterface::STATUS_SEE_OTHER);

            return $response->response();
        }

        /** @var mixed $submitDownload */
        $submitDownload = $request->getParsedBodyParam('submit_download');
        if (is_string($submitDownload) && $submitDownload !== '') {
            // Output generated config file
            Core::downloadHeader('config.inc.php', 'text/plain');
            $response->disable();
            echo ConfigGenerator::getConfigFile($GLOBALS['ConfigFile']);

            return $response->response();
        }

        // Show generated config file in a <textarea>
        $response->addHeader(
            'Location',
            '../setup/index.php' . Url::getCommonRaw(['route' => '/setup', 'page' => 'config']),
        );
        $response->setStatusCode(StatusCodeInterface::STATUS_SEE_OTHER);

        return $response->response();
    }
}
