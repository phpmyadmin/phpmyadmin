<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Table\SearchColumns;
use PhpMyAdmin\Table\SearchForm;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;

use function __;
use function array_keys;

/**
 * Handles table search tab.
 *
 * Display table search form, create SQL query from form data
 * and call Sql::executeQueryAndSendQueryResponse() to execute it.
 */
#[Route('/table/search', ['GET', 'POST'])]
final class SearchController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Search $search,
        private readonly SearchForm $searchForm,
        private readonly DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
        private readonly Config $config,
        private readonly Sql $sql,
    ) {
    }

    /**
     * Index action
     */
    public function __invoke(ServerRequest $request): Response
    {
        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        if (Current::$table === '') {
            return $this->response->missingParameterError('table');
        }

        UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            return $this->response->respondWithErrorMessage(__('No database selected.'));
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            return $this->response->respondWithErrorMessage(__('No table selected.'));
        }

        $columns = $this->searchForm->getColumns($databaseName->getName(), $tableName->getName());

        $this->response->addScriptFiles([
            'makegrid.js',
            'sql.js',
            'table/select.js',
            'table/change.js',
            'vendor/jquery/jquery.uitablefilter.js',
            'gis_data_editor.js',
        ]);

        if (isset($_POST['range_search'])) {
            $this->rangeSearchAction();

            return $this->response->response();
        }

        /**
         * No selection criteria received -> display the selection form
         */
        if (! isset($_POST['columnsToDisplay']) && ! isset($_POST['displayAllColumns'])) {
            $this->displaySelectionFormAction($columns);
        } else {
            $this->doSelectionAction($request);
        }

        return $this->response->response();
    }

    /**
     * Do selection action
     */
    private function doSelectionAction(ServerRequest $request): void
    {
        /**
         * Selection criteria have been submitted -> do the work
         */
        $sqlQuery = $this->search->buildSqlQuery();

        /**
         * Add this to ensure following procedures included running correctly.
         */
        $this->response->addHTML($this->sql->executeQueryAndSendQueryResponse(
            $request,
            null,
            false, // is_gotofile
            Current::$database, // db
            Current::$table, // table
            '', // sql_query_for_bookmark
            '', // message_to_show
            UrlParams::$goto, // goto
            null, // disp_query
            null, // disp_message
            $sqlQuery, // sql_query
            $sqlQuery, // complete_query
        ));
    }

    /**
     * Display selection form action
     */
    private function displaySelectionFormAction(SearchColumns $columns): void
    {
        if (UrlParams::$goto === '') {
            UrlParams::$goto = Url::getFromRoute($this->config->config->DefaultTabTable);
        }

        $properties = [];
        foreach (array_keys($columns->names) as $columnIndex) {
            $properties[$columnIndex] = $this->searchForm->getColumnProperties(
                $columns,
                $columnIndex,
                $columnIndex,
                'tbl_search_form',
            );
        }

        $this->response->render('table/search/index', [
            'db' => Current::$database,
            'table' => Current::$table,
            'goto' => UrlParams::$goto,
            'properties' => $properties,
            'geom_column_flag' => $columns->hasGeometry,
            'column_names' => $columns->names,
            'column_types' => $columns->types,
            'column_collations' => $columns->collations,
            'default_sliders_state' => $this->config->config->InitialSlidersState,
            'max_rows' => $this->config->config->maxRows,
        ]);
    }

    /**
     * Range search action
     */
    private function rangeSearchAction(): void
    {
        $minMax = $this->getColumnMinMax($_POST['column']);
        $this->response->addJSON('column_data', $minMax);
    }

    /**
     * Finds minimum and maximum value of a given column.
     *
     * @param string $column Column name
     *
     * @return array<string|null>
     */
    private function getColumnMinMax(string $column): array
    {
        $sqlQuery = 'SELECT MIN(' . Util::backquote($column) . ') AS `min`, '
            . 'MAX(' . Util::backquote($column) . ') AS `max` '
            . 'FROM ' . Util::backquote(Current::$database) . '.'
            . Util::backquote(Current::$table);

        return $this->dbi->fetchSingleRow($sqlQuery);
    }
}
