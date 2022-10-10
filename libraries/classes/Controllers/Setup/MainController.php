<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Core;
use PhpMyAdmin\Header;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function file_exists;
use function header;
use function in_array;

final class MainController
{
    public function __invoke(ServerRequest $request): void
    {
        if (@file_exists(CONFIG_FILE) && ! $GLOBALS['cfg']['DBG']['demo']) {
            Core::fatalError(__('Configuration already exists, setup is disabled!'));
        }

        $params = $request->getQueryParams();

        $page = 'index';
        if (isset($params['page']) && in_array($params['page'], ['form', 'config', 'servers'], true)) {
            $page = $params['page'];
        }

        Core::noCacheHeader();

        // Sent security-related headers
        (new Header())->sendHttpHeaders();

        if ($page === 'form') {
            echo (new FormController($GLOBALS['ConfigFile'], new Template()))([
                'formset' => $params['formset'] ?? null,
            ]);

            return;
        }

        if ($page === 'config') {
            echo (new ConfigController($GLOBALS['ConfigFile'], new Template()))([
                'formset' => $params['formset'] ?? null,
                'eol' => $params['eol'] ?? null,
            ]);

            return;
        }

        if ($page === 'servers') {
            $controller = new ServersController($GLOBALS['ConfigFile'], new Template());
            if (isset($params['mode']) && $params['mode'] === 'remove' && $request->isPost()) {
                $controller->destroy([
                    'id' => $params['id'] ?? null,
                ]);
                header('Location: ../setup/index.php' . Url::getCommonRaw(['route' => '/setup']));

                return;
            }

            echo $controller->index([
                'formset' => $params['formset'] ?? null,
                'mode' => $params['mode'] ?? null,
                'id' => $params['id'] ?? null,
            ]);

            return;
        }

        echo (new HomeController($GLOBALS['ConfigFile'], new Template()))([
            'formset' => $params['formset'] ?? null,
            'version_check' => $params['version_check'] ?? null,
        ]);
    }
}
