<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;

use function __;
use function explode;

final class SetValuesController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Sql $sql,
    ) {
        parent::__construct($response, $template);
    }

    /**
     * Get possible values for SET fields during grid edit.
     */
    public function __invoke(ServerRequest $request): void
    {
        $column = $request->getParsedBodyParam('column');
        $currentValue = $request->getParsedBodyParam('curr_value');
        $whereClause = $request->getParsedBodyParam('where_clause');

        $values = $this->sql->getValuesForColumn(Current::$database, Current::$table, $column);

        if ($values === null) {
            $this->response->addJSON('message', __('Error in processing request'));
            $this->response->setRequestStatus(false);

            return;
        }

        // If the $currentValue was truncated, we should fetch the correct full values from the table.
        if ($request->hasBodyParam('get_full_values') && ! empty($whereClause)) {
            $currentValue = $this->sql->getFullValuesForSetColumn(
                Current::$database,
                Current::$table,
                $column,
                $whereClause,
            );
        }

        $select = $this->template->render('sql/set_column', [
            'values' => $values,
            'current_values' => explode(',', $currentValue),
        ]);

        $this->response->addJSON('select', $select);
    }
}
