<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

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
class ChangeController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private InsertEdit $insertEdit,
        private Relation $relation,
        private PageSettings $pageSettings,
        private readonly DbTableExists $dbTableExists,
        private readonly Config $config,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['disp_message'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['where_clause'] ??= null;
        $GLOBALS['unsaved_values'] ??= null;
        $GLOBALS['current_result'] ??= null;

        $this->pageSettings->init('Edit');
        $this->response->addHTML($this->pageSettings->getErrorHTML());
        $this->response->addHTML($this->pageSettings->getHTML());

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return;
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No table selected.')]);

            return;
        }

        $this->setInsertRowsParam($request->getParsedBodyParam('insert_rows'));

        if ($request->hasQueryParam('where_clause') && $request->hasQueryParam('where_clause_signature')) {
            $whereClause = $request->getQueryParam('where_clause');
            if (Core::checkSqlQuerySignature($whereClause, $request->getQueryParam('where_clause_signature'))) {
                $GLOBALS['where_clause'] = $whereClause;
            }
        }

        /**
         * Determine whether Insert or Edit and set global variables
         */
        [
            $insertMode,
            $GLOBALS['where_clause'],
            $whereClauseArray,
            $whereClauses,
            $result,
            $rows,
            $foundUniqueIndex,
            $afterInsert,
        ] = $this->insertEdit->determineInsertOrEdit(
            $GLOBALS['where_clause'] ?? null,
            Current::$database,
            Current::$table,
        );
        // Increase number of rows if unsaved rows are more
        if (! empty($GLOBALS['unsaved_values']) && count($rows) < count($GLOBALS['unsaved_values'])) {
            $rows = array_fill(0, count($GLOBALS['unsaved_values']), false);
        }

        /**
         * Defines the url to return to in case of error in a sql statement
         * (at this point, $GLOBALS['goto'] will be set but could be empty)
         */
        if (empty($GLOBALS['goto'])) {
            if (Current::$table !== '') {
                // avoid a problem (see bug #2202709)
                $GLOBALS['goto'] = Url::getFromRoute('/table/sql');
            } else {
                $GLOBALS['goto'] = Url::getFromRoute('/database/sql');
            }
        }

        /** @var mixed $sqlQuery */
        $sqlQuery = $request->getParsedBodyParam('sql_query');
        $GLOBALS['urlParams'] = ['db' => Current::$database, 'sql_query' => is_string($sqlQuery) ? $sqlQuery : ''];

        if (str_starts_with($GLOBALS['goto'] ?? '', 'index.php?route=/table')) {
            $GLOBALS['urlParams']['table'] = Current::$table;
        }

        $GLOBALS['errorUrl'] = $GLOBALS['goto'] . Url::getCommon(
            $GLOBALS['urlParams'],
            ! str_contains($GLOBALS['goto'], '?') ? '?' : '&',
        );
        unset($GLOBALS['urlParams']);

        $commentsMap = $this->insertEdit->getCommentsMap(Current::$database, Current::$table);

        /**
         * START REGULAR OUTPUT
         */

        $this->addScriptFiles([
            'makegrid.js',
            'sql.js',
            'table/change.js',
            'vendor/jquery/additional-methods.js',
            'gis_data_editor.js',
        ]);

        /**
         * Displays the query submitted and its result
         *
         * $disp_message come from /table/replace
         */
        if (! empty($GLOBALS['disp_message'])) {
            $this->response->addHTML(Generator::getMessage($GLOBALS['disp_message']));
        }

        $tableColumns = $this->insertEdit->getTableColumns(Current::$database, Current::$table);

        // retrieve keys into foreign fields, if any
        $foreigners = $this->relation->getForeigners(Current::$database, Current::$table);

        // Retrieve form parameters for insert/edit form
        $formParams = $this->insertEdit->getFormParametersForInsertForm(
            Current::$database,
            Current::$table,
            $whereClauses,
            $whereClauseArray,
            $GLOBALS['errorUrl'],
        );

        /**
         * Displays the form
         */
        // Had to put the URI because when hosted on an https server,
        // some browsers send wrongly this form to the http server.

        $htmlOutput = '';

        $GLOBALS['urlParams']['db'] = Current::$database;
        $GLOBALS['urlParams']['table'] = Current::$table;
        $GLOBALS['urlParams'] = $this->urlParamsInEditMode($GLOBALS['urlParams'], $whereClauseArray);

        $hasBlobField = false;
        foreach ($tableColumns as $tableColumn) {
            if ($this->insertEdit->isColumn($tableColumn->type, ['blob', 'tinyblob', 'mediumblob', 'longblob'])) {
                $hasBlobField = true;
                break;
            }
        }

        //Insert/Edit form
        //If table has blob fields we have to disable ajax.
        $isUpload = $this->config->get('enable_upload');
        $htmlOutput .= $this->insertEdit->getHtmlForInsertEditFormHeader($hasBlobField, $isUpload);

        $htmlOutput .= Url::getHiddenInputs($formParams);

        // user can toggle the display of Function column and column types
        // (currently does not work for multi-edits)
        if (
            ! $this->config->settings['ShowFunctionFields'] || ! $this->config->settings['ShowFieldTypesInDataEditView']
        ) {
            $htmlOutput .= __('Show');
        }

        if (! $this->config->settings['ShowFunctionFields']) {
            $htmlOutput .= $this->insertEdit->showTypeOrFunction('function', $GLOBALS['urlParams'], false);
        }

        if (! $this->config->settings['ShowFieldTypesInDataEditView']) {
            $htmlOutput .= $this->insertEdit->showTypeOrFunction('type', $GLOBALS['urlParams'], false);
        }

        $GLOBALS['plugin_scripts'] = [];
        foreach ($rows as $rowId => $currentRow) {
            $GLOBALS['current_result'] = is_array($result) && isset($result[$rowId])
                ? $result[$rowId]
                : $result;
            $repopulate = [];
            $checked = true;
            if (isset($GLOBALS['unsaved_values'][$rowId])) {
                $repopulate = $GLOBALS['unsaved_values'][$rowId];
                $checked = false;
            }

            if ($insertMode && $rowId > 0) {
                $htmlOutput .= $this->insertEdit->getHtmlForIgnoreOption($rowId, $checked);
            }

            $htmlOutput .= $this->insertEdit->getHtmlForInsertEditRow(
                $GLOBALS['urlParams'],
                $tableColumns,
                $commentsMap,
                $GLOBALS['current_result'],
                $insertMode,
                $currentRow ?: [],
                $isUpload,
                $foreigners,
                Current::$table,
                Current::$database,
                $rowId,
                LanguageManager::$textDir,
                $repopulate,
                $whereClauseArray,
            );
        }

        $this->addScriptFiles($GLOBALS['plugin_scripts']);

        unset($GLOBALS['unsaved_values'], $GLOBALS['plugin_scripts']);

        $isNumeric = InsertEdit::isWhereClauseNumeric($GLOBALS['where_clause']);
        $htmlOutput .= $this->template->render('table/insert/actions_panel', [
            'where_clause' => $GLOBALS['where_clause'],
            'after_insert' => $afterInsert ?? 'back',
            'found_unique_key' => $foundUniqueIndex,
            'is_numeric' => $isNumeric,
        ]);

        $htmlOutput .= '</form>';

        $htmlOutput .= $this->insertEdit->getHtmlForGisEditor();
        // end Insert/Edit form

        if ($insertMode) {
            //Continue insertion form
            $htmlOutput .= $this->insertEdit->getContinueInsertionForm(
                Current::$table,
                Current::$database,
                $whereClauseArray,
                $GLOBALS['errorUrl'],
            );
        }

        $this->response->addHTML($htmlOutput);
    }

    /**
     * Add some url parameters
     *
     * @param mixed[] $urlParams        containing $db and $table as url parameters
     * @param mixed[] $whereClauseArray where clauses array
     *
     * @return mixed[] Add some url parameters to $url_params array and return it
     */
    public function urlParamsInEditMode(
        array $urlParams,
        array $whereClauseArray,
    ): array {
        foreach ($whereClauseArray as $whereClause) {
            $urlParams['where_clause'] = trim($whereClause);
        }

        if (! empty($_POST['sql_query'])) {
            $urlParams['sql_query'] = $_POST['sql_query'];
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
