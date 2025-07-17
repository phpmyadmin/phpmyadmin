<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;
use function array_values;
use function is_array;

#[Route('/table/change/rows', ['POST'])]
final class ChangeRowsController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly ChangeController $changeController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $rowsToDelete = $request->getParsedBodyParam('rows_to_delete');

        if (
            (! $request->hasBodyParam('rows_to_delete') || ! is_array($rowsToDelete))
            && $request->hasBodyParam('goto')
        ) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return $this->response->response();
        }

        // As we got the rows to be edited from the
        // 'rows_to_delete' checkbox, we use the index of it as the
        // indicating WHERE clause. Then we build the array which is used
        // for the /table/change script.
        Current::$whereClause = [];
        if (is_array($rowsToDelete)) {
            Current::$whereClause = array_values($rowsToDelete);
        }

        return ($this->changeController)($request);
    }
}
