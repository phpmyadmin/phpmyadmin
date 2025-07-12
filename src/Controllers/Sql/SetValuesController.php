<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;

use function __;
use function explode;

#[Route('/sql/get-set-values', ['POST'])]
final class SetValuesController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly Sql $sql,
    ) {
    }

    /**
     * Get possible values for SET fields during grid edit.
     */
    public function __invoke(ServerRequest $request): Response
    {
        $column = $request->getParsedBodyParamAsString('column');
        $currentValue = $request->getParsedBodyParamAsString('curr_value');
        $whereClause = $request->getParsedBodyParamAsStringOrNull('where_clause');

        $values = $this->sql->getValuesForColumn(Current::$database, Current::$table, $column);

        if ($values === null) {
            $this->response->addJSON('message', __('Error in processing request'));
            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        // If the $currentValue was truncated, we should fetch the correct full values from the table.
        if ($request->hasBodyParam('get_full_values') && $whereClause !== null && $whereClause !== '') {
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

        return $this->response->response();
    }
}
