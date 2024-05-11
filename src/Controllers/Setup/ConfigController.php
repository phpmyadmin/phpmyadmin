<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Template;

use function is_string;

final class ConfigController implements InvocableController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly ResponseRenderer $responseRenderer,
        private readonly Template $template,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $response = $this->responseFactory->createResponse();
        foreach ($this->responseRenderer->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $pages = SetupHelper::getPages();

        static $hasCheckPageRefresh = false;
        if (! $hasCheckPageRefresh) {
            $hasCheckPageRefresh = true;
        }

        $configFile = SetupHelper::createConfigFile();

        $config = ConfigGenerator::getConfigFile($configFile);

        return $response->write($this->template->render('setup/config/index', [
            'formset' => $this->getFormSetParam($request->getQueryParam('formset')),
            'pages' => $pages,
            'eol' => $this->getEolParam($request->getQueryParam('eol')),
            'config' => $config,
            'has_check_page_refresh' => $hasCheckPageRefresh,
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
