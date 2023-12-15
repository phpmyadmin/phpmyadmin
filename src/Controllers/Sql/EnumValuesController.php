<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;

use function __;
use function strval;

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
        $values = $this->sql->getValuesForColumn(Current::$database, Current::$table, strval($column));

        if ($values === null) {
            $this->response->addJSON('message', __('Error in processing request'));
            $this->response->setRequestStatus(false);

            return;
        }

        $dropdown = $this->template->render('sql/enum_column_dropdown', [
            'values' => $values,
            'selected_values' => [strval($currValue)],
        ]);

        $this->response->addJSON('dropdown', $dropdown);
    }
}
