<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\ColumnFull;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Template;
use PhpMyAdmin\UserPrivilegesFactory;

use function __;
use function array_filter;
use function array_map;
use function array_values;
use function count;
use function in_array;
use function is_array;

final class ChangeController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private ColumnsDefinition $columnsDefinition,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        if (! $this->checkParameters(['server', 'db', 'table'])) {
            return;
        }

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
        $fieldsMeta = $this->dbi->getColumns(Current::$database, Current::$table, true);
        $fieldsMeta = array_values(array_filter(
            $fieldsMeta,
            static fn (ColumnFull $column): bool => in_array($column->field, $selected, true),
        ));
        // TODO: Refactor columnsDefinition->displayForm() method to avoid unwrapping DTO
        $fieldsMeta = array_map(static fn (ColumnFull $column): array => [
            'Field' => $column->field,
            'Type' => $column->type,
            'Collation' => $column->collation,
            'Null' => $column->isNull ? 'YES' : 'NO',
            'Key' => $column->key,
            'Default' => $column->default,
            'Extra' => $column->extra,
            'Privileges' => $column->privileges,
            'Comment' => $column->comment,
        ], $fieldsMeta);

        /**
         * Form for changing properties.
         */
        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $this->addScriptFiles(['vendor/jquery/jquery.uitablefilter.js']);

        $templateData = $this->columnsDefinition->displayForm(
            $userPrivileges,
            '/table/structure/save',
            count($fieldsMeta),
            $selected,
            $fieldsMeta,
        );

        $this->render('columns_definitions/column_definitions_form', $templateData);
    }
}
