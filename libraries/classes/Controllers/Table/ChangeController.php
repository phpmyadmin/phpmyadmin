<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function array_fill;
use function count;
use function is_array;
use function is_string;
use function str_contains;
use function strlen;
use function strpos;

/**
 * Displays form for editing and inserting new table rows.
 */
class ChangeController extends AbstractController
{
    /** @var InsertEdit */
    private $insertEdit;

    /** @var Relation */
    private $relation;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        InsertEdit $insertEdit,
        Relation $relation
    ) {
        parent::__construct($response, $template);
        $this->insertEdit = $insertEdit;
        $this->relation = $relation;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['text_dir'] = $GLOBALS['text_dir'] ?? null;
        $GLOBALS['disp_message'] = $GLOBALS['disp_message'] ?? null;
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $GLOBALS['where_clause'] = $GLOBALS['where_clause'] ?? null;
        $GLOBALS['unsaved_values'] = $GLOBALS['unsaved_values'] ?? null;
        $GLOBALS['insert_mode'] = $GLOBALS['insert_mode'] ?? null;
        $GLOBALS['where_clause_array'] = $GLOBALS['where_clause_array'] ?? null;
        $GLOBALS['where_clauses'] = $GLOBALS['where_clauses'] ?? null;
        $GLOBALS['result'] = $GLOBALS['result'] ?? null;
        $GLOBALS['rows'] = $GLOBALS['rows'] ?? null;
        $GLOBALS['found_unique_key'] = $GLOBALS['found_unique_key'] ?? null;
        $GLOBALS['after_insert'] = $GLOBALS['after_insert'] ?? null;
        $GLOBALS['comments_map'] = $GLOBALS['comments_map'] ?? null;
        $GLOBALS['table_columns'] = $GLOBALS['table_columns'] ?? null;
        $GLOBALS['chg_evt_handler'] = $GLOBALS['chg_evt_handler'] ?? null;
        $GLOBALS['timestamp_seen'] = $GLOBALS['timestamp_seen'] ?? null;
        $GLOBALS['columns_cnt'] = $GLOBALS['columns_cnt'] ?? null;
        $GLOBALS['tabindex'] = $GLOBALS['tabindex'] ?? null;
        $GLOBALS['tabindex_for_value'] = $GLOBALS['tabindex_for_value'] ?? null;
        $GLOBALS['o_rows'] = $GLOBALS['o_rows'] ?? null;
        $GLOBALS['biggest_max_file_size'] = $GLOBALS['biggest_max_file_size'] ?? null;
        $GLOBALS['has_blob_field'] = $GLOBALS['has_blob_field'] ?? null;
        $GLOBALS['jsvkey'] = $GLOBALS['jsvkey'] ?? null;
        $GLOBALS['vkey'] = $GLOBALS['vkey'] ?? null;
        $GLOBALS['current_result'] = $GLOBALS['current_result'] ?? null;
        $GLOBALS['repopulate'] = $GLOBALS['repopulate'] ?? null;
        $GLOBALS['checked'] = $GLOBALS['checked'] ?? null;

        $pageSettings = new PageSettings('Edit');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

        /**
         * Determine whether Insert or Edit and set global variables
         */
        [
            $GLOBALS['insert_mode'],
            $GLOBALS['where_clause'],
            $GLOBALS['where_clause_array'],
            $GLOBALS['where_clauses'],
            $GLOBALS['result'],
            $GLOBALS['rows'],
            $GLOBALS['found_unique_key'],
            $GLOBALS['after_insert'],
        ] = $this->insertEdit->determineInsertOrEdit(
            $GLOBALS['where_clause'] ?? null,
            $GLOBALS['db'],
            $GLOBALS['table']
        );
        // Increase number of rows if unsaved rows are more
        if (! empty($GLOBALS['unsaved_values']) && count($GLOBALS['rows']) < count($GLOBALS['unsaved_values'])) {
            $GLOBALS['rows'] = array_fill(0, count($GLOBALS['unsaved_values']), false);
        }

        /**
         * Defines the url to return to in case of error in a sql statement
         * (at this point, $GLOBALS['goto'] will be set but could be empty)
         */
        if (empty($GLOBALS['goto'])) {
            if (strlen($GLOBALS['table']) > 0) {
                // avoid a problem (see bug #2202709)
                $GLOBALS['goto'] = Url::getFromRoute('/table/sql');
            } else {
                $GLOBALS['goto'] = Url::getFromRoute('/database/sql');
            }
        }

        /** @var mixed $sqlQuery */
        $sqlQuery = $request->getParsedBodyParam('sql_query');
        $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'sql_query' => is_string($sqlQuery) ? $sqlQuery : ''];

        if (strpos($GLOBALS['goto'] ?? '', 'index.php?route=/table') === 0) {
            $GLOBALS['urlParams']['table'] = $GLOBALS['table'];
        }

        $GLOBALS['errorUrl'] = $GLOBALS['goto'] . Url::getCommon(
            $GLOBALS['urlParams'],
            ! str_contains($GLOBALS['goto'], '?') ? '?' : '&'
        );
        unset($GLOBALS['urlParams']);

        $GLOBALS['comments_map'] = $this->insertEdit->getCommentsMap($GLOBALS['db'], $GLOBALS['table']);

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
            $this->response->addHTML(Generator::getMessage($GLOBALS['disp_message'], null));
        }

        $GLOBALS['table_columns'] = $this->insertEdit->getTableColumns($GLOBALS['db'], $GLOBALS['table']);

        // retrieve keys into foreign fields, if any
        $foreigners = $this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table']);

        // Retrieve form parameters for insert/edit form
        $_form_params = $this->insertEdit->getFormParametersForInsertForm(
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['where_clauses'],
            $GLOBALS['where_clause_array'],
            $GLOBALS['errorUrl']
        );

        /**
         * Displays the form
         */
        // autocomplete feature of IE kills the "onchange" event handler and it
        //        must be replaced by the "onpropertychange" one in this case
        $GLOBALS['chg_evt_handler'] = 'onchange';
        // Had to put the URI because when hosted on an https server,
        // some browsers send wrongly this form to the http server.

        $html_output = '';
        // Set if we passed the first timestamp field
        $GLOBALS['timestamp_seen'] = false;
        $GLOBALS['columns_cnt'] = count($GLOBALS['table_columns']);

        $GLOBALS['tabindex'] = 0;
        $GLOBALS['tabindex_for_value'] = 0;
        $GLOBALS['o_rows'] = 0;
        $GLOBALS['biggest_max_file_size'] = 0;

        $GLOBALS['urlParams']['db'] = $GLOBALS['db'];
        $GLOBALS['urlParams']['table'] = $GLOBALS['table'];
        $GLOBALS['urlParams'] = $this->insertEdit->urlParamsInEditMode(
            $GLOBALS['urlParams'],
            $GLOBALS['where_clause_array']
        );

        $GLOBALS['has_blob_field'] = false;
        foreach ($GLOBALS['table_columns'] as $column) {
            if ($this->insertEdit->isColumn($column, ['blob', 'tinyblob', 'mediumblob', 'longblob'])) {
                $GLOBALS['has_blob_field'] = true;
                break;
            }
        }

        //Insert/Edit form
        //If table has blob fields we have to disable ajax.
        $isUpload = $GLOBALS['config']->get('enable_upload');
        $html_output .= $this->insertEdit->getHtmlForInsertEditFormHeader($GLOBALS['has_blob_field'], $isUpload);

        $html_output .= Url::getHiddenInputs($_form_params);

        // user can toggle the display of Function column and column types
        // (currently does not work for multi-edits)
        if (! $GLOBALS['cfg']['ShowFunctionFields'] || ! $GLOBALS['cfg']['ShowFieldTypesInDataEditView']) {
            $html_output .= __('Show');
        }

        if (! $GLOBALS['cfg']['ShowFunctionFields']) {
            $html_output .= $this->insertEdit->showTypeOrFunction('function', $GLOBALS['urlParams'], false);
        }

        if (! $GLOBALS['cfg']['ShowFieldTypesInDataEditView']) {
            $html_output .= $this->insertEdit->showTypeOrFunction('type', $GLOBALS['urlParams'], false);
        }

        $GLOBALS['plugin_scripts'] = [];
        foreach ($GLOBALS['rows'] as $row_id => $current_row) {
            if (empty($current_row)) {
                $current_row = [];
            }

            $GLOBALS['jsvkey'] = $row_id;
            $GLOBALS['vkey'] = '[multi_edit][' . $GLOBALS['jsvkey'] . ']';

            $GLOBALS['current_result'] = (isset($GLOBALS['result'])
                && is_array($GLOBALS['result']) && isset($GLOBALS['result'][$row_id])
                ? $GLOBALS['result'][$row_id]
                : $GLOBALS['result']);
            $GLOBALS['repopulate'] = [];
            $GLOBALS['checked'] = true;
            if (isset($GLOBALS['unsaved_values'][$row_id])) {
                $GLOBALS['repopulate'] = $GLOBALS['unsaved_values'][$row_id];
                $GLOBALS['checked'] = false;
            }

            if ($GLOBALS['insert_mode'] && $row_id > 0) {
                $html_output .= $this->insertEdit->getHtmlForIgnoreOption($row_id, $GLOBALS['checked']);
            }

            $html_output .= $this->insertEdit->getHtmlForInsertEditRow(
                $GLOBALS['urlParams'],
                $GLOBALS['table_columns'],
                $GLOBALS['comments_map'],
                $GLOBALS['timestamp_seen'],
                $GLOBALS['current_result'],
                $GLOBALS['chg_evt_handler'],
                $GLOBALS['jsvkey'],
                $GLOBALS['vkey'],
                $GLOBALS['insert_mode'],
                $current_row,
                $GLOBALS['o_rows'],
                $GLOBALS['tabindex'],
                $GLOBALS['columns_cnt'],
                $isUpload,
                $foreigners,
                $GLOBALS['tabindex_for_value'],
                $GLOBALS['table'],
                $GLOBALS['db'],
                $row_id,
                $GLOBALS['biggest_max_file_size'],
                $GLOBALS['text_dir'],
                $GLOBALS['repopulate'],
                $GLOBALS['where_clause_array']
            );
        }

        $this->addScriptFiles($GLOBALS['plugin_scripts']);

        unset($GLOBALS['unsaved_values'], $GLOBALS['checked'], $GLOBALS['repopulate'], $GLOBALS['plugin_scripts']);

        if (! isset($GLOBALS['after_insert'])) {
            $GLOBALS['after_insert'] = 'back';
        }

        $isNumeric = InsertEdit::isWhereClauseNumeric($GLOBALS['where_clause']);
        $html_output .= $this->template->render('table/insert/actions_panel', [
            'where_clause' => $GLOBALS['where_clause'],
            'after_insert' => $GLOBALS['after_insert'],
            'found_unique_key' => $GLOBALS['found_unique_key'],
            'is_numeric' => $isNumeric,
        ]);

        if ($GLOBALS['biggest_max_file_size'] > 0) {
            $html_output .= '<input type="hidden" name="MAX_FILE_SIZE" value="'
                . $GLOBALS['biggest_max_file_size'] . '">' . "\n";
        }

        $html_output .= '</form>';

        $html_output .= $this->insertEdit->getHtmlForGisEditor();
        // end Insert/Edit form

        if ($GLOBALS['insert_mode']) {
            //Continue insertion form
            $html_output .= $this->insertEdit->getContinueInsertionForm(
                $GLOBALS['table'],
                $GLOBALS['db'],
                $GLOBALS['where_clause_array'],
                $GLOBALS['errorUrl']
            );
        }

        $this->response->addHTML($html_output);
    }
}
