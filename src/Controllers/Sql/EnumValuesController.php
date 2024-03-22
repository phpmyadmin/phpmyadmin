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

final class EnumValuesController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Sql $sql,
    ) {
        parent::__construct($response, $template);
    }

    /**
     * Get possible values for enum fields during grid edit.
     */
    public function __invoke(ServerRequest $request): void
    {
        $column = $request->getParsedBodyParam('column');
        $currValue = $request->getParsedBodyParam('curr_value');
        $values = $this->sql->getValuesForColumn(Current::$database, Current::$table, (string) $column);

        if ($values === null) {
            $this->response->addJSON('message', __('Error in processing request'));
            $this->response->setRequestStatus(false);

            return;
        }

        $dropdown = $this->template->render('sql/enum_column_dropdown', [
            'values' => $values,
            'selected_values' => [(string) $currValue],
        ]);

        $this->response->addJSON('dropdown', $dropdown);
    }
}
