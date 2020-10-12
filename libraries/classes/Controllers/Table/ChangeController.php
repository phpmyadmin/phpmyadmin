<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use function array_fill;
use function count;
use function is_array;
use function mb_strpos;
use function strlen;

/**
 * Displays form for editing and inserting new table rows.
 */
class ChangeController extends AbstractController
{
    /** @var InsertEdit */
    private $insertEdit;

    /** @var Relation */
    private $relation;

    /**
     * @param Response $response
     * @param string   $db       Database name.
     * @param string   $table    Table name.
     */
    public function __construct(
        $response,
        Template $template,
        $db,
        $table,
        InsertEdit $insertEdit,
        Relation $relation
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->insertEdit = $insertEdit;
        $this->relation = $relation;
    }

    public function index(): void
    {
        global $cfg, $is_upload, $db, $table, $text_dir, $disp_message, $url_params;
        global $err_url, $where_clause, $unsaved_values, $insert_mode, $where_clause_array, $where_clauses;
        global $result, $rows, $found_unique_key, $after_insert, $comments_map, $table_columns;
        global $chg_evt_handler, $timestamp_seen, $columns_cnt, $tabindex, $tabindex_for_function;
        global $tabindex_for_null, $tabindex_for_value, $o_rows, $biggest_max_file_size, $has_blob_field;
        global $jsvkey, $vkey, $current_result, $repopulate, $checked;

        $pageSettings = new PageSettings('Edit');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        DbTableExists::check();

        /**
         * Determine whether Insert or Edit and set global variables
         */
        [
            $insert_mode,
            $where_clause,
            $where_clause_array,
            $where_clauses,
            $result,
            $rows,
            $found_unique_key,
            $after_insert,
        ] = $this->insertEdit->determineInsertOrEdit(
            $where_clause ?? null,
            $db,
            $table
        );
        // Increase number of rows if unsaved rows are more
        if (! empty($unsaved_values) && count($rows) < count($unsaved_values)) {
            $rows = array_fill(0, count($unsaved_values), false);
        }

        /**
         * Defines the url to return to in case of error in a sql statement
         * (at this point, $GLOBALS['goto'] will be set but could be empty)
         */
        if (empty($GLOBALS['goto'])) {
            if (strlen($table) > 0) {
                // avoid a problem (see bug #2202709)
                $GLOBALS['goto'] = Url::getFromRoute('/table/sql');
            } else {
                $GLOBALS['goto'] = Url::getFromRoute('/database/sql');
            }
        }

        $_url_params = $this->insertEdit->getUrlParameters($db, $table);
        $err_url = $GLOBALS['goto'] . Url::getCommon(
            $_url_params,
            mb_strpos($GLOBALS['goto'], '?') === false ? '?' : '&'
        );
        unset($_url_params);

        $comments_map = $this->insertEdit->getCommentsMap($db, $table);

        /**
         * START REGULAR OUTPUT
         */

        $this->addScriptFiles([
            'makegrid.js',
            'vendor/stickyfill.min.js',
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
        if (! empty($disp_message)) {
            $this->response->addHTML(Generator::getMessage($disp_message, null));
        }

        $table_columns = $this->insertEdit->getTableColumns($db, $table);

        // retrieve keys into foreign fields, if any
        $foreigners = $this->relation->getForeigners($db, $table);

        // Retrieve form parameters for insert/edit form
        $_form_params = $this->insertEdit->getFormParametersForInsertForm(
            $db,
            $table,
            $where_clauses,
            $where_clause_array,
            $err_url
        );

        /**
         * Displays the form
         */
        // autocomplete feature of IE kills the "onchange" event handler and it
        //        must be replaced by the "onpropertychange" one in this case
        $chg_evt_handler =  'onchange';
        // Had to put the URI because when hosted on an https server,
        // some browsers send wrongly this form to the http server.

        $html_output = '';
        // Set if we passed the first timestamp field
        $timestamp_seen = false;
        $columns_cnt     = count($table_columns);

        $tabindex              = 0;
        $tabindex_for_function = +3000;
        $tabindex_for_null     = +6000;
        $tabindex_for_value    = 0;
        $o_rows                = 0;
        $biggest_max_file_size = 0;

        $url_params['db'] = $db;
        $url_params['table'] = $table;
        $url_params = $this->insertEdit->urlParamsInEditMode(
            $url_params,
            $where_clause_array
        );

        $has_blob_field = false;
        foreach ($table_columns as $column) {
            if ($this->insertEdit->isColumn(
                $column,
                [
                    'blob',
                    'tinyblob',
                    'mediumblob',
                    'longblob',
                ]
            )) {
                $has_blob_field = true;
                break;
            }
        }

        //Insert/Edit form
        //If table has blob fields we have to disable ajax.
        $html_output .= $this->insertEdit->getHtmlForInsertEditFormHeader($has_blob_field, $is_upload);

        $html_output .= Url::getHiddenInputs($_form_params);

        // user can toggle the display of Function column and column types
        // (currently does not work for multi-edits)
        if (! $cfg['ShowFunctionFields'] || ! $cfg['ShowFieldTypesInDataEditView']) {
            $html_output .= __('Show');
        }

        if (! $cfg['ShowFunctionFields']) {
            $html_output .= $this->insertEdit->showTypeOrFunction('function', $url_params, false);
        }

        if (! $cfg['ShowFieldTypesInDataEditView']) {
            $html_output .= $this->insertEdit->showTypeOrFunction('type', $url_params, false);
        }

        $GLOBALS['plugin_scripts'] = [];
        foreach ($rows as $row_id => $current_row) {
            if (empty($current_row)) {
                $current_row = [];
            }

            $jsvkey = $row_id;
            $vkey = '[multi_edit][' . $jsvkey . ']';

            $current_result = (isset($result) && is_array($result) && isset($result[$row_id])
                ? $result[$row_id]
                : $result);
            $repopulate = [];
            $checked = true;
            if (isset($unsaved_values[$row_id])) {
                $repopulate = $unsaved_values[$row_id];
                $checked = false;
            }
            if ($insert_mode && $row_id > 0) {
                $html_output .= $this->insertEdit->getHtmlForIgnoreOption($row_id, $checked);
            }

            $html_output .= $this->insertEdit->getHtmlForInsertEditRow(
                $url_params,
                $table_columns,
                $comments_map,
                $timestamp_seen,
                $current_result,
                $chg_evt_handler,
                $jsvkey,
                $vkey,
                $insert_mode,
                $current_row,
                $o_rows,
                $tabindex,
                $columns_cnt,
                $is_upload,
                $tabindex_for_function,
                $foreigners,
                $tabindex_for_null,
                $tabindex_for_value,
                $table,
                $db,
                $row_id,
                $biggest_max_file_size,
                $text_dir,
                $repopulate,
                $where_clause_array
            );
        }

        $this->addScriptFiles($GLOBALS['plugin_scripts']);

        unset($unsaved_values, $checked, $repopulate, $GLOBALS['plugin_scripts']);

        if (! isset($after_insert)) {
            $after_insert = 'back';
        }

        //action panel
        $html_output .= $this->insertEdit->getActionsPanel(
            $where_clause,
            $after_insert,
            $tabindex,
            $tabindex_for_value,
            $found_unique_key
        );

        if ($biggest_max_file_size > 0) {
            $html_output .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . $biggest_max_file_size . '">' . "\n";
        }
        $html_output .= '</form>';

        $html_output .= $this->insertEdit->getHtmlForGisEditor();
        // end Insert/Edit form

        if ($insert_mode) {
            //Continue insertion form
            $html_output .= $this->insertEdit->getContinueInsertionForm(
                $table,
                $db,
                $where_clause_array,
                $err_url
            );
        }

        $this->response->addHTML($html_output);
    }

    public function rows(): void
    {
        global $active_page, $where_clause;

        if (isset($_POST['goto']) && (! isset($_POST['rows_to_delete']) || ! is_array($_POST['rows_to_delete']))) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return;
        }

        // As we got the rows to be edited from the
        // 'rows_to_delete' checkbox, we use the index of it as the
        // indicating WHERE clause. Then we build the array which is used
        // for the /table/change script.
        $where_clause = [];
        if (isset($_POST['rows_to_delete']) && is_array($_POST['rows_to_delete'])) {
            foreach ($_POST['rows_to_delete'] as $i => $i_where_clause) {
                $where_clause[] = $i_where_clause;
            }
        }

        $active_page = Url::getFromRoute('/table/change');

        $this->index();
    }
}
