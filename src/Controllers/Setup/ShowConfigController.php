<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config\Forms\Setup\ConfigForm;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Url;

use function is_string;

final class ShowConfigController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $responseRenderer)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $formDisplay = new ConfigForm($GLOBALS['ConfigFile']);
        $formDisplay->save(['Config']);

        $this->responseRenderer->disable();

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
            $this->responseRenderer->addHeader(
                'Location',
                '../setup/index.php' . Url::getCommonRaw(['route' => '/setup']),
            );
            $this->responseRenderer->setStatusCode(StatusCodeInterface::STATUS_SEE_OTHER);

            return $this->responseRenderer->response();
        }

        /** @var mixed $submitDownload */
        $submitDownload = $request->getParsedBodyParam('submit_download');
        if (is_string($submitDownload) && $submitDownload !== '') {
            // Output generated config file
            Core::downloadHeader('config.inc.php', 'text/plain');
            $this->responseRenderer->disable();
            echo ConfigGenerator::getConfigFile($GLOBALS['ConfigFile']);

            return $this->responseRenderer->response();
        }

        // Show generated config file in a <textarea>
        $this->responseRenderer->addHeader(
            'Location',
            '../setup/index.php' . Url::getCommonRaw(['route' => '/setup', 'page' => 'config']),
        );
        $this->responseRenderer->setStatusCode(StatusCodeInterface::STATUS_SEE_OTHER);

        return $this->responseRenderer->response();
    }
}
