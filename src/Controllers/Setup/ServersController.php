<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Forms\Setup\ServersForm;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Setup\FormProcessing;

use function in_array;
use function is_numeric;
use function is_string;
use function ob_get_clean;
use function ob_start;

class ServersController extends AbstractController
{
    public function index(ServerRequest $request): string
    {
        $id = $this->getIdParam($request->getQueryParam('id'));
        $mode = $this->getModeParam($request->getQueryParam('mode'));

        $pages = $this->getPages();

        $hasServer = $id >= 1 && $this->config->get('Servers/' . $id) !== null;

        if (! $hasServer && $mode !== 'revert' && $mode !== 'edit') {
            $id = 0;
        }

        ob_start();
        FormProcessing::process(new ServersForm($this->config, $id));
        $page = ob_get_clean();

        return $this->template->render('setup/servers/index', [
            'formset' => $this->getFormSetParam($request->getQueryParam('formset')),
            'pages' => $pages,
            'has_server' => $hasServer,
            'mode' => $mode,
            'server_id' => $id,
            'server_dsn' => $this->config->getServerDSN($id),
            'page' => $page,
        ]);
    }

    public function destroy(ServerRequest $request): void
    {
        $id = $this->getIdParam($request->getQueryParam('id'));
        $hasServer = $id >= 1 && $this->config->get('Servers/' . $id) !== null;
        if (! $hasServer) {
            return;
        }

        $this->config->removeServer($id);
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
