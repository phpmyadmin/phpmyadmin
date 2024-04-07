<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class CheckTimeOutController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Template $template)
    {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        if (! $request->isAjax()) {
            return null;
        }

        if (isset($_SESSION['pma_export_error'])) {
            unset($_SESSION['pma_export_error']);
            $this->response->addJSON('message', 'timeout');

            return null;
        }

        $this->response->addJSON('message', 'success');

        return null;
    }
}
