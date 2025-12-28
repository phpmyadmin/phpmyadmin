<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use Webmozart\Assert\Assert;

use function __;
use function array_fill;
use function count;
use function is_array;
use function is_numeric;
use function is_string;
use function str_contains;
use function str_starts_with;
use function trim;

/**
 * Displays form for editing and inserting new table rows.
 */
#[Route('/table/change', ['GET', 'POST'])]
class ChangeController implements InvocableController
{
    /** @var array<mixed> */
    public static array $unsavedValues = [];

    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly InsertEdit $insertEdit,
        private readonly Relation $relation,
        private readonly PageSettings $pageSettings,
        private readonly DbTableExists $dbTableExists,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $whereClauses = Current::$whereClause;

        $this->pageSettings->init('Edit');
        $this->response->addHTML($this->pageSettings->getErrorHTML());
        $this->response->addHTML($this->pageSettings->getHTML());

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No table selected.')]);
        }

        $this->setInsertRowsParam($request->getParsedBodyParam('insert_rows'));

        if ($request->hasQueryParam('where_clause') && $request->hasQueryParam('where_clause_signature')) {
            $whereClause = $request->getQueryParam('where_clause');
            Assert::string($whereClause);
            if (Core::checkSqlQuerySignature($whereClause, $request->getQueryParam('where_clause_signature'))) {
                $whereClauses = $whereClause;
            }
        }

        if ($request->hasBodyParam('ShowFunctionFields')) {
            $this->config->set(
                'ShowFunctionFields',
                $request->getParsedBodyParam('ShowFunctionFields') === '1',
            );
        }

        if ($request->hasBodyParam('ShowFieldTypesInDataEditView')) {
            $this->config->set(
                'ShowFieldTypesInDataEditView',
                $request->getParsedBodyParam('ShowFieldTypesInDataEditView') === '1',
            );
        }

        /**
         * Determine whether Insert or Edit and set global variables
         */
        [
            $insertMode,
            $whereClauses,
            $result,
            $rows,
            $foundUniqueIndex,
            $afterInsert,
        ] = $this->insertEdit->determineInsertOrEdit($whereClauses, Current::$database, Current::$table);
        // Increase number of rows if unsaved rows are more
        if (self::$unsavedValues !== [] && count($rows) < count(self::$unsavedValues)) {
            $rows = array_fill(0, count(self::$unsavedValues), []);
        }

        /**
         * Defines the url to return to in case of error in a sql statement
         * (at this point, UrlParams::$goto will be set but could be empty)
         */
        if (UrlParams::$goto === '') {
            if (Current::$table !== '') {
                // avoid a problem (see bug #2202709)
                UrlParams::$goto = Url::getFromRoute('/table/sql');
            } else {
                UrlParams::$goto = Url::getFromRoute('/database/sql');
            }
        }

        /** @var mixed $sqlQuery */
        $sqlQuery = $request->getParsedBodyParam('sql_query');
        UrlParams::$params = ['db' => Current::$database, 'sql_query' => is_string($sqlQuery) ? $sqlQuery : ''];

        if (str_starts_with(UrlParams::$goto, 'index.php?route=/table')) {
            UrlParams::$params['table'] = Current::$table;
        }

        $errorUrl = UrlParams::$goto . Url::getCommon(
            UrlParams::$params,
            ! str_contains(UrlParams::$goto, '?') ? '?' : '&',
        );
        UrlParams::$params = [];

        $commentsMap = $this->insertEdit->getCommentsMap(Current::$database, Current::$table);

        /**
         * START REGULAR OUTPUT
         */

        $this->response->addScriptFiles([
            'makegrid.js',
            'sql.js',
            'table/change.js',
            'vendor/jquery/additional-methods.min.js',
            'gis_data_editor.js',
        ]);

        /**
         * Displays the query submitted and its result
         *
         * $disp_message come from /table/replace
         */
        if (! empty(Current::$displayMessage)) {
            $this->response->addHTML(Generator::getMessage(Current::$displayMessage));
        }

        $tableColumns = $this->insertEdit->getTableColumns(Current::$database, Current::$table);

        // retrieve keys into foreign fields, if any
        $foreigners = $this->relation->getForeigners(Current::$database, Current::$table);

        // Retrieve form parameters for insert/edit form
        $formParams = $this->insertEdit->getFormParametersForInsertForm(
            Current::$database,
            Current::$table,
            (array) $whereClauses,
            $errorUrl,
        );

        /**
         * Displays the form
         */
        // Had to put the URI because when hosted on an https server,
        // some browsers send wrongly this form to the http server.

        $htmlOutput = '';

        UrlParams::$params['db'] = Current::$database;
        UrlParams::$params['table'] = Current::$table;
        UrlParams::$params = $this->urlParamsInEditMode($request, UrlParams::$params, (array) $whereClauses);

        $hasBlobField = false;
        foreach ($tableColumns as $tableColumn) {
            if ($this->insertEdit->isColumn($tableColumn->type, ['blob', 'tinyblob', 'mediumblob', 'longblob'])) {
                $hasBlobField = true;
                break;
            }
        }

        //Insert/Edit form
        //If table has blob fields we have to disable ajax.
        $isUpload = $this->config->isUploadEnabled();
        $htmlOutput .= $this->insertEdit->getHtmlForInsertEditFormHeader($hasBlobField, $isUpload);

        $htmlOutput .= Url::getHiddenInputs($formParams);

        // user can toggle the display of Function column and column types
        // (currently does not work for multi-edits)
        if (! $this->config->config->ShowFunctionFields || ! $this->config->config->ShowFieldTypesInDataEditView) {
            $htmlOutput .= __('Show');
        }

        if (! $this->config->config->ShowFunctionFields) {
            $htmlOutput .= $this->insertEdit->showTypeOrFunction('function', UrlParams::$params, false);
        }

        if (! $this->config->config->ShowFieldTypesInDataEditView) {
            $htmlOutput .= $this->insertEdit->showTypeOrFunction('type', UrlParams::$params, false);
        }

        InsertEdit::$pluginScripts = [];
        foreach ($rows as $rowId => $currentRow) {
            $currentResult = is_array($result) ? $result[$rowId] : $result;
            $repopulate = [];
            $checked = true;
            if (isset(self::$unsavedValues[$rowId])) {
                $repopulate = self::$unsavedValues[$rowId];
                $checked = false;
            }

            if ($insertMode && $rowId > 0) {
                $htmlOutput .= $this->insertEdit->getHtmlForIgnoreOption($rowId, $checked);
            }

            $htmlOutput .= $this->insertEdit->getHtmlForInsertEditRow(
                UrlParams::$params,
                $tableColumns,
                $commentsMap,
                $currentResult->getFieldsMeta(),
                $insertMode,
                $currentRow,
                $isUpload,
                $foreigners,
                Current::$table,
                Current::$database,
                $rowId,
                $repopulate,
                (array) $whereClauses,
            );
        }

        $this->response->addScriptFiles(InsertEdit::$pluginScripts);
        InsertEdit::$pluginScripts = [];
        self::$unsavedValues = [];

        $isNumeric = InsertEdit::isWhereClauseNumeric($whereClauses);
        $htmlOutput .= $this->template->render('table/insert/actions_panel', [
            'where_clause' => $whereClauses,
            'after_insert' => $afterInsert ?? 'back',
            'found_unique_key' => $foundUniqueIndex,
            'is_numeric' => $isNumeric,
        ]);

        $htmlOutput .= '</form>';

        $htmlOutput .= $this->template->render('modals/gis_editor');
        // end Insert/Edit form

        if ($insertMode) {
            //Continue insertion form
            $htmlOutput .= $this->insertEdit->getContinueInsertionForm(
                Current::$table,
                Current::$database,
                (array) $whereClauses,
                $errorUrl,
            );
        }

        $this->response->addHTML($htmlOutput);

        return $this->response->response();
    }

    /**
     * Add some url parameters
     *
     * @param array<string, bool|int|string> $urlParams        containing $db and $table as url parameters
     * @param string[]                       $whereClauseArray where clauses array
     *
     * @return array<string, bool|int|string> Add some url parameters to $url_params array and return it
     */
    public function urlParamsInEditMode(
        ServerRequest $request,
        array $urlParams,
        array $whereClauseArray,
    ): array {
        foreach ($whereClauseArray as $whereClause) {
            $urlParams['where_clause'] = trim($whereClause);
        }

        $sqlQuery = $request->getParsedBodyParamAsString('sql_query', '');
        if ($sqlQuery !== '') {
            $urlParams['sql_query'] = $sqlQuery;
        }

        return $urlParams;
    }

    private function setInsertRowsParam(mixed $insertRows): void
    {
        if (
            ! is_numeric($insertRows)
            || (int) $insertRows === $this->config->settings['InsertRows']
            || (int) $insertRows < 1
        ) {
            return;
        }

        $this->config->set('InsertRows', (int) $insertRows);
    }
}
