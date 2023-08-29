<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;

use function __;
use function htmlentities;

use const ENT_COMPAT;

final class SetValuesController extends AbstractController
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
     * Get possible values for SET fields during grid edit.
     */
    public function __invoke(ServerRequest $request): void
    {
        $this->checkUserPrivileges->getPrivileges();

        $column = $request->getParsedBodyParam('column');
        $currentValue = $request->getParsedBodyParam('curr_value');
        $whereClause = $request->getParsedBodyParam('where_clause');

        $values = $this->sql->getValuesForColumn($GLOBALS['db'], $GLOBALS['table'], $column);

        if ($values === null) {
            $this->response->addJSON('message', __('Error in processing request'));
            $this->response->setRequestStatus(false);

            return;
        }

        // If the $currentValue was truncated, we should fetch the correct full values from the table.
        if ($request->hasBodyParam('get_full_values') && ! empty($whereClause)) {
            $currentValue = $this->sql->getFullValuesForSetColumn(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $column,
                $whereClause,
            );
        }

        // Converts characters of $currentValue to HTML entities.
        $convertedCurrentValue = htmlentities($currentValue, ENT_COMPAT, 'UTF-8');

        $select = $this->template->render('sql/set_column', [
            'values' => $values,
            'current_values' => $convertedCurrentValue,
        ]);

        $this->response->addJSON('select', $select);
    }
}
