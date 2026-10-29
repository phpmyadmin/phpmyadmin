<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function file_exists;

use const CONFIG_FILE;

final readonly class ServerDestroyController implements InvocableController
{
    public function __construct(
        private ResponseFactory $responseFactory,
        private ResponseRenderer $responseRenderer,
        private Template $template,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (@file_exists(CONFIG_FILE) && ! $this->config->config->debug->demo) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);

            return $response->write($this->template->render('error/generic', [
                'lang' => Current::$lang,
                'error_message' => __('Configuration already exists, setup is disabled!'),
            ]));
        }

        $response = $this->responseFactory->createResponse();
        foreach ($this->responseRenderer->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $configFile = SetupHelper::createConfigFile();

        $id = SetupHelper::getServerId($request->getQueryParam('id'));
        $hasServer = $id >= 1 && $configFile->get('Servers/' . $id) !== null;
        if ($hasServer) {
            $configFile->removeServer($id);
        }

        return $response->withStatus(StatusCodeInterface::STATUS_FOUND)->withHeader(
            'Location',
            '../setup/index.php' . Url::getCommonRaw(['route' => '/setup']),
        );
    }
}
