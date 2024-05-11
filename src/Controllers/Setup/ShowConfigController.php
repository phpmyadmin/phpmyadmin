<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Forms\Setup\ConfigForm;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function file_exists;
use function is_string;

use const CONFIG_FILE;

final class ShowConfigController implements InvocableController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly ResponseRenderer $responseRenderer,
        private readonly Template $template,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (@file_exists(CONFIG_FILE) && ! $this->config->config->debug->demo) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);

            return $response->write($this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => LanguageManager::$textDir,
                'error_message' => __('Configuration already exists, setup is disabled!'),
            ]));
        }

        $configFile = SetupHelper::createConfigFile();

        $formDisplay = new ConfigForm($configFile);
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
            $configFile->resetConfigData();
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
            echo ConfigGenerator::getConfigFile($configFile);

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
