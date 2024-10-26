<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;

final class RelationalValuesController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Sql $sql)
    {
    }

    /**
     * Get values for the relational columns
     *
     * During grid edit, if we have a relational field, show the dropdown for it.
     */
    public function __invoke(ServerRequest $request): Response
    {
        $column = $request->getParsedBodyParamAsString('column', '');
        $relationKeyOrDisplayColumn = $request->getParsedBodyParamAsStringOrNull('relation_key_or_display_column');

        if ($_SESSION['tmpval']['relational_display'] === 'D' && $relationKeyOrDisplayColumn !== null) {
            $currValue = $relationKeyOrDisplayColumn;
        } else {
            $currValue = $request->getParsedBodyParamAsString('curr_value', '');
        }

        $dropdown = $this->sql->getHtmlForRelationalColumnDropdown(
            Current::$database,
            Current::$table,
            $column,
            $currValue,
        );
        $this->response->addJSON('dropdown', $dropdown);

        return $this->response->response();
    }
}
