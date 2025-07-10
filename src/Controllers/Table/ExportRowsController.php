<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\UrlParams;

use function __;
use function array_values;
use function is_array;

#[Route('/table/export/rows', ['POST'])]
final class ExportRowsController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly ExportController $exportController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (UrlParams::$goto !== '' && (! isset($_POST['rows_to_delete']) || ! is_array($_POST['rows_to_delete']))) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return $this->response->response();
        }

        // Needed to allow SQL export
        Export::$singleTable = true;

        // As we got the rows to be exported from the
        // 'rows_to_delete' checkbox, we use the index of it as the
        // indicating WHERE clause. Then we build the array which is used
        // for the /table/change script.
        Current::$whereClause = [];
        if (isset($_POST['rows_to_delete']) && is_array($_POST['rows_to_delete'])) {
            Current::$whereClause = array_values($_POST['rows_to_delete']);
        }

        return ($this->exportController)($request);
    }
}
