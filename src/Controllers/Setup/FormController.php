<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\FormProcessing;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Template;

use function __;
use function file_exists;
use function is_string;
use function ob_get_clean;
use function ob_start;

use const CONFIG_FILE;

final class FormController implements InvocableController
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

        $response = $this->responseFactory->createResponse();
        foreach ($this->responseRenderer->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $pages = SetupHelper::getPages();

        $formSet = $this->getFormSetParam($request->getQueryParam('formset'));

        $formClass = SetupFormList::get($formSet);
        if ($formClass === null) {
            return $response->write($this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => LanguageManager::$textDir,
                'error_message' => __('Incorrect form specified!'),
            ]));
        }

        $configFile = SetupHelper::createConfigFile();

        ob_start();
        $form = new $formClass($configFile);
        FormProcessing::process($form);
        $page = ob_get_clean();

        return $response->write($this->template->render('setup/form/index', [
            'formset' => $formSet,
            'pages' => $pages,
            'name' => $form::getName(),
            'page' => $page,
        ]));
    }

    private function getFormSetParam(mixed $formSetParam): string
    {
        return is_string($formSetParam) ? $formSetParam : '';
    }
}
