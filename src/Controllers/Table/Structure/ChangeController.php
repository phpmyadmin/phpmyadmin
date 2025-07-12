<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Column;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\UserPrivilegesFactory;

use function __;
use function array_filter;
use function array_map;
use function array_values;
use function count;
use function in_array;
use function is_array;

#[Route('/table/structure/change', ['GET', 'POST'])]
final class ChangeController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly ColumnsDefinition $columnsDefinition,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (Current::$server === 0) {
            return $this->response->missingParameterError('server');
        }

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        if (Current::$table === '') {
            return $this->response->missingParameterError('table');
        }

        if ($request->getParam('change_column') !== null) {
            $this->displayHtmlForColumnChange([$request->getParam('field')]);

            return $this->response->response();
        }

        $selected = $request->getParsedBodyParam('selected_fld', []);

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return $this->response->response();
        }

        $this->displayHtmlForColumnChange($selected);

        return $this->response->response();
    }

    /**
     * Displays HTML for changing one or more columns
     *
     * @param string[] $selected the selected columns
     */
    private function displayHtmlForColumnChange(array $selected): void
    {
        $fieldsMeta = $this->dbi->getColumns(Current::$database, Current::$table);
        $fieldsMeta = array_values(array_filter(
            $fieldsMeta,
            static fn (Column $column): bool => in_array($column->field, $selected, true),
        ));
        // TODO: Refactor columnsDefinition->displayForm() method to avoid unwrapping DTO
        $fieldsMeta = array_map(static fn (Column $column): array => [
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

        $this->response->addScriptFiles(['vendor/jquery/jquery.uitablefilter.js']);

        $templateData = $this->columnsDefinition->displayForm(
            $userPrivileges,
            '/table/structure/save',
            count($fieldsMeta),
            $selected,
            $fieldsMeta,
        );

        $this->response->render('columns_definitions/column_definitions_form', $templateData);
    }
}
