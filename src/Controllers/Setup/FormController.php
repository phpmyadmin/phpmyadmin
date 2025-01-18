<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
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
use function in_array;
use function is_numeric;
use function is_string;

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
                'lang' => Current::$lang,
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
                'lang' => Current::$lang,
                'error_message' => __('Incorrect form specified!'),
            ]));
        }

        $configFile = SetupHelper::createConfigFile();
        $formDisplay = new $formClass($configFile);

        if ($request->getQueryParam('mode') === 'revert') {
            // revert erroneous fields to their default values
            $formDisplay->fixErrors();

            return $response->withStatus(StatusCodeInterface::STATUS_FOUND)
                ->withHeader('Location', '../setup/index.php' . Url::getCommonRaw(['route' => '/setup']));
        }

        if (! $formDisplay->process(false)) {
            // handle form view and failed POST
            return $response->write($this->template->render('setup/form/index', [
                'formset' => $formSet,
                'pages' => $pages,
                'name' => $formDisplay::getName(),
                'page' => $formDisplay->getDisplay(),
            ]));
        }

        // check for form errors
        if (! $formDisplay->hasErrors()) {
            return $response->withStatus(StatusCodeInterface::STATUS_FOUND)
                ->withHeader('Location', '../setup/index.php' . Url::getCommonRaw(['route' => '/setup']));
        }

        $page = $this->getPageParam($request->getQueryParam('page'));
        $id = $this->getIdParam($request->getQueryParam('id'));
        if ($id === 0 && $page === 'servers') {
            // we've just added a new server, get its id
            $id = $formDisplay->getConfigFile()->getServerCount();
        }

        $errors = $this->template->render('setup/error', [
            'url_params' => ['page' => $page, 'formset' => $formSet, 'id' => $id],
            'errors' => $formDisplay->displayErrors(),
        ]);

        return $response->write($this->template->render('setup/form/index', [
            'formset' => $formSet,
            'pages' => $pages,
            'name' => $formDisplay::getName(),
            'page' => $errors,
        ]));
    }

    private function getFormSetParam(mixed $formSetParam): string
    {
        return is_string($formSetParam) ? $formSetParam : '';
    }

    /** @psalm-return 'form'|'config'|'servers'|'index' */
    private function getPageParam(mixed $pageParam): string
    {
        return in_array($pageParam, ['form', 'config', 'servers'], true) ? $pageParam : 'index';
    }

    /** @psalm-return int<0, max> */
    private function getIdParam(mixed $idParam): int
    {
        if (! is_numeric($idParam)) {
            return 0;
        }

        $id = (int) $idParam;

        return $id >= 1 ? $id : 0;
    }
}
