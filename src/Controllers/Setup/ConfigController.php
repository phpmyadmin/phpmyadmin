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
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Template;

use function __;
use function file_exists;
use function is_string;

use const CONFIG_FILE;

final class ConfigController implements InvocableController
{
    private static bool $hasCheckPageRefresh = false;

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
                'lang' => Current::$lang,
                'error_message' => __('Configuration already exists, setup is disabled!'),
            ]));
        }

        $response = $this->responseFactory->createResponse();
        foreach ($this->responseRenderer->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $pages = SetupHelper::getPages();

        if (! self::$hasCheckPageRefresh) {
            self::$hasCheckPageRefresh = true;
        }

        $configFile = SetupHelper::createConfigFile();

        $config = ConfigGenerator::getConfigFile($configFile);

        return $response->write($this->template->render('setup/config/index', [
            'formset' => $this->getFormSetParam($request->getQueryParam('formset')),
            'pages' => $pages,
            'eol' => $this->getEolParam($request->getQueryParam('eol')),
            'config' => $config,
            'has_check_page_refresh' => self::$hasCheckPageRefresh,
        ]));
    }

    private function getFormSetParam(mixed $formSetParam): string
    {
        return is_string($formSetParam) ? $formSetParam : '';
    }

    /** @psalm-return 'win'|'unix' */
    private function getEolParam(mixed $eolParam): string
    {
        return $eolParam === 'win' ? 'win' : 'unix';
    }
}
