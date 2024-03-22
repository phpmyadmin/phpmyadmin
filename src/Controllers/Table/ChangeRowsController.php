<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;
use function array_values;
use function is_array;

final class ChangeRowsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private ChangeController $changeController,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['where_clause'] ??= null;

        $rowsToDelete = $request->getParsedBodyParam('rows_to_delete');

        if (
            (! $request->hasBodyParam('rows_to_delete') || ! is_array($rowsToDelete))
            && $request->hasBodyParam('goto')
        ) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return;
        }

        // As we got the rows to be edited from the
        // 'rows_to_delete' checkbox, we use the index of it as the
        // indicating WHERE clause. Then we build the array which is used
        // for the /table/change script.
        $GLOBALS['where_clause'] = [];
        if (is_array($rowsToDelete)) {
            $GLOBALS['where_clause'] = array_values($rowsToDelete);
        }

        ($this->changeController)($request);
    }
}
