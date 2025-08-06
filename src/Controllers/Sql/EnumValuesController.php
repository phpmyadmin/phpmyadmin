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

#[Route('/sql/get-enum-values', ['POST'])]
final class EnumValuesController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly Sql $sql,
    ) {
    }

    /**
     * Get possible values for enum fields during grid edit.
     */
    public function __invoke(ServerRequest $request): Response
    {
        $column = $request->getParsedBodyParamAsString('column', '');
        $currValue = $request->getParsedBodyParamAsString('curr_value', '');
        $values = $this->sql->getValuesForColumn(Current::$database, Current::$table, $column);

        if ($values === null) {
            $this->response->addJSON('message', __('Error in processing request'));
            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        $dropdown = $this->template->render('sql/enum_column_dropdown', [
            'values' => $values,
            'selected_values' => [$currValue],
        ]);

        $this->response->addJSON('dropdown', $dropdown);

        return $this->response->response();
    }
}
