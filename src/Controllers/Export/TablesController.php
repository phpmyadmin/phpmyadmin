<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export;

use PhpMyAdmin\Controllers\Database\ExportController;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;

#[Route('/export/tables', ['POST'])]
final class TablesController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly ExportController $exportController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->hasBodyParam('selected_tbl')) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return $this->response->response();
        }

        return ($this->exportController)($request);
    }
}
