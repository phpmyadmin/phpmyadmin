<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Forms\Setup\ServersForm;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\FormProcessing;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Template;

use function in_array;
use function is_numeric;
use function is_string;
use function ob_get_clean;
use function ob_start;

final class ServersController implements InvocableController
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

        $configFile = SetupHelper::createConfigFile();

        $id = $this->getIdParam($request->getQueryParam('id'));
        $mode = $this->getModeParam($request->getQueryParam('mode'));

        $pages = SetupHelper::getPages();

        $hasServer = $id >= 1 && $configFile->get('Servers/' . $id) !== null;

        if (! $hasServer && $mode !== 'revert' && $mode !== 'edit') {
            $id = 0;
        }

        ob_start();
        FormProcessing::process(new ServersForm($configFile, $id));
        $page = ob_get_clean();

        return $response->write($this->template->render('setup/servers/index', [
            'formset' => $this->getFormSetParam($request->getQueryParam('formset')),
            'pages' => $pages,
            'has_server' => $hasServer,
            'mode' => $mode,
            'server_id' => $id,
            'server_dsn' => $configFile->getServerDSN($id),
            'page' => $page,
        ]));
    }

    private function getFormSetParam(mixed $formSetParam): string
    {
        return is_string($formSetParam) ? $formSetParam : '';
    }

    /** @psalm-return 'add'|'edit'|'revert'|'' */
    private function getModeParam(mixed $modeParam): string
    {
        return in_array($modeParam, ['add', 'edit', 'revert'], true) ? $modeParam : '';
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
