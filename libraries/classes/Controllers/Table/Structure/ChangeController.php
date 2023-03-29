<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Template;

use function __;
use function count;
use function is_array;

final class ChangeController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private ColumnsDefinition $columnsDefinition,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        if ($request->getParam('change_column') !== null) {
            $this->displayHtmlForColumnChange([$request->getParam('field')]);

            return;
        }

        $selected = $request->getParsedBodyParam('selected_fld', []);

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $this->displayHtmlForColumnChange($selected);
    }

    /**
     * Displays HTML for changing one or more columns
     *
     * @param string[] $selected the selected columns
     */
    private function displayHtmlForColumnChange(array $selected): void
    {
        $GLOBALS['num_fields'] ??= null;

        /** @todo optimize in case of multiple fields to modify */
        $fieldsMeta = [];
        foreach ($selected as $column) {
            $value = $this->dbi->getColumn($GLOBALS['db'], $GLOBALS['table'], $column, true);
            if ($value === []) {
                $message = Message::error(
                    __('Failed to get description of column %s!'),
                );
                $message->addParam($column);
                $this->response->addHTML($message->getDisplay());
            } else {
                $fieldsMeta[] = $value;
            }
        }

        $GLOBALS['num_fields'] = count($fieldsMeta);

        /**
         * Form for changing properties.
         */
        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['vendor/jquery/jquery.uitablefilter.js']);

        $this->checkParameters(['server', 'db', 'table', 'num_fields']);

        $templateData = $this->columnsDefinition->displayForm(
            '/table/structure/save',
            $GLOBALS['num_fields'],
            null,
            $selected,
            $fieldsMeta,
        );

        $this->render('columns_definitions/column_definitions_form', $templateData);
    }
}
