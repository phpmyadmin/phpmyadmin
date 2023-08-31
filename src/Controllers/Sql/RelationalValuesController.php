<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;

use function strval;

final class RelationalValuesController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Sql $sql,
        private CheckUserPrivileges $checkUserPrivileges,
    ) {
        parent::__construct($response, $template);
    }

    /**
     * Get values for the relational columns
     *
     * During grid edit, if we have a relational field, show the dropdown for it.
     */
    public function __invoke(ServerRequest $request): void
    {
        $this->checkUserPrivileges->getPrivileges();

        $column = $request->getParsedBodyParam('column');
        $relationKeyOrDisplayColumn = $request->getParsedBodyParam('relation_key_or_display_column');

        if ($_SESSION['tmpval']['relational_display'] === 'D' && $relationKeyOrDisplayColumn !== null) {
            $currValue = $relationKeyOrDisplayColumn;
        } else {
            $currValue = $request->getParsedBodyParam('curr_value');
        }

        $dropdown = $this->sql->getHtmlForRelationalColumnDropdown(
            $GLOBALS['db'],
            $GLOBALS['table'],
            strval($column),
            strval($currValue),
        );
        $this->response->addJSON('dropdown', $dropdown);
    }
}
