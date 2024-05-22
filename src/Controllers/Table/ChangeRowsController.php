<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;

use function __;
use function array_values;
use function is_array;

final class ChangeRowsController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly ChangeController $changeController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['where_clause'] ??= null;

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
        $GLOBALS['where_clause'] = [];
        if (is_array($rowsToDelete)) {
            $GLOBALS['where_clause'] = array_values($rowsToDelete);
        }

        return ($this->changeController)($request);
    }
}
