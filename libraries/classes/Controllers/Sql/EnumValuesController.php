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
use function strval;

use const ENT_COMPAT;

final class EnumValuesController extends AbstractController
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
     * Get possible values for enum fields during grid edit.
     */
    public function __invoke(ServerRequest $request): void
    {
        $this->checkUserPrivileges->getPrivileges();

        $column = $request->getParsedBodyParam('column');
        $currValue = $request->getParsedBodyParam('curr_value');
        $values = $this->sql->getValuesForColumn($GLOBALS['db'], $GLOBALS['table'], strval($column));

        if ($values === null) {
            $this->response->addJSON('message', __('Error in processing request'));
            $this->response->setRequestStatus(false);

            return;
        }

        // Converts characters of $curr_value to HTML entities.
        $convertedCurrentValue = htmlentities(strval($currValue), ENT_COMPAT, 'UTF-8');

        $dropdown = $this->template->render('sql/enum_column_dropdown', [
            'values' => $values,
            'selected_values' => [$convertedCurrentValue],
        ]);

        $this->response->addJSON('dropdown', $dropdown);
    }
}
