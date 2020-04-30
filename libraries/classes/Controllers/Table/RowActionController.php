<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Common;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function is_array;

/**
 * Handle row specific actions like edit, delete, export.
 */
class RowActionController extends AbstractController
{
    public function delete(): void
    {
        global $db, $goto, $pmaThemeImage, $sql_query, $table, $disp_message, $disp_query, $action;
        global $submit_mult, $active_page, $err_url, $original_sql_query, $url_query, $original_url_query;
        global $mult_btn, $query_type, $reload, $selected, $selected_fld;

        $submit_mult = $_POST['submit_mult'] ?? '';

        if ($submit_mult === 'delete' || isset($_POST['mult_btn'])) {
            $submit_mult = 'row_delete';
        }

        if (empty($submit_mult)) {
            return;
        }

        if (isset($_POST['goto']) && (! isset($_POST['rows_to_delete']) || ! is_array($_POST['rows_to_delete']))) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));
        }

        $action = Url::getFromRoute('/table/row-action');
        $err_url = Url::getFromRoute('/table/row-action', $GLOBALS['url_params']);
        if (! isset($_POST['mult_btn'])) {
            $original_sql_query = $sql_query;
            if (! empty($url_query)) {
                $original_url_query = $url_query;
            }
        }

        $goto = $_POST['goto'] ?? $goto ?? '';
        $mult_btn = $_POST['mult_btn'] ?? $mult_btn ?? null;
        $original_sql_query = $_POST['original_sql_query'] ?? $original_sql_query ?? null;
        $query_type = $_POST['query_type'] ?? $query_type ?? null;
        $reload = $_POST['reload'] ?? $reload ?? null;
        $selected = $_POST['selected'] ?? $selected ?? [];
        $selected_fld = $_POST['selected_fld'] ?? $selected_fld ?? null;
        $sql_query = $_POST['sql_query'] ?? $sql_query ?? '';
        $submit_mult = $_POST['submit_mult'] ?? $submit_mult ?? null;
        $url_query = $_POST['url_query'] ?? $url_query ?? null;

        if (! empty($submit_mult)
            && $submit_mult != __('With selected:')
            && (! empty($_POST['selected_tbl'])
                || ! empty($selected_fld)
                || ! empty($_POST['rows_to_delete']))
        ) {
            // phpcs:disable PSR1.Files.SideEffects
            define('PMA_SUBMIT_MULT', 1);
            // phpcs:enable

            if (! (isset($selected_fld) && ! empty($selected_fld))) {
                // coming from browsing - do something with selected rows
                $what = 'row_delete';
                $selected = $_REQUEST['rows_to_delete'];
            }
        }

        if (! empty($mult_btn) && $mult_btn == __('Yes')) {
            $default_fk_check_value = false;

            if ($query_type == 'drop_tbl'
                || $query_type == 'empty_tbl'
                || $query_type == 'row_delete'
            ) {
                $default_fk_check_value = Util::handleDisableFKCheckInit();
            }

            $aQuery = '';
            $sql_query = '';
            $sql_query_views = null;
            // whether to run query after each pass
            $run_parts = false;
            $result = null;
            $selectedCount = count($selected);
            $deletes = false;

            for ($i = 0; $i < $selectedCount; $i++) {
                switch ($query_type) {
                    case 'row_delete':
                        $deletes = true;
                        $aQuery = $selected[$i];
                        $run_parts = true;
                        break;
                }

                if ($run_parts) {
                    $sql_query .= $aQuery . ';' . "\n";
                    $this->dbi->selectDb($db);
                    $result = $this->dbi->query($aQuery);
                }
            }

            if ($deletes && ! empty($_REQUEST['pos'])) {
                $sql = new Sql();
                $_REQUEST['pos'] = $sql->calculatePosForLastPage(
                    $db,
                    $table,
                    $_REQUEST['pos'] ?? null
                );
            }

            if ($query_type == 'drop_tbl'
                || $query_type == 'empty_tbl'
                || $query_type == 'row_delete'
            ) {
                Util::handleDisableFKCheckCleanup($default_fk_check_value);
            }
        }

        $_url_params = $GLOBALS['url_params'];
        $_url_params['goto'] = Url::getFromRoute('/table/sql');
        $url_query = Url::getCommon($_url_params);

        /**
         * Show result of multi submit operation
         */
        // sql_query is not set when user does not confirm multi-delete
        if ((! empty($submit_mult) || isset($_POST['mult_btn']))
            && ! empty($sql_query)
        ) {
            $disp_message = __('Your SQL query has been executed successfully.');
            $disp_query = $sql_query;
        }

        if (isset($original_sql_query)) {
            $sql_query = $original_sql_query;
        }

        if (isset($original_url_query)) {
            $url_query = $original_url_query;
        }

        $active_page = Url::getFromRoute('/sql');

        $sql = new Sql();
        $sql->executeQueryAndSendQueryResponse(
            null,
            false,
            $db,
            $table,
            null,
            null,
            null,
            null,
            null,
            null,
            $goto,
            $pmaThemeImage,
            null,
            null,
            null,
            $sql_query,
            null,
            null
        );
    }

    public function confirmDelete(): void
    {
        global $db, $table, $sql_query;

        $selected = $_POST['rows_to_delete'] ?? null;

        if (! isset($selected) || ! is_array($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return;
        }

        Common::table();

        $rows = [];
        foreach ($selected as $row) {
            $rows[] = sprintf('DELETE FROM %s WHERE %s LIMIT 1;', Util::backquote($table), $row);
        }

        $hiddenInputs = [
            'db' => $db,
            'table' => $table,
            'selected' => $rows,
            'original_sql_query' => $sql_query,
            'query_type' => 'row_delete',
            'fk_checks' => '0',
        ];

        $this->render('table/row_action/confirm_delete', [
            'table' => $table,
            'selected' => $selected,
            'hidden_inputs' => $hiddenInputs,
            'is_foreign_key_check' => Util::isForeignKeyCheck(),
        ]);
    }

    public function edit(): void
    {
        global $containerBuilder, $submit_mult, $active_page, $where_clause;

        $submit_mult = $_POST['submit_mult'] ?? '';

        if (empty($submit_mult)) {
            return;
        }

        if ($submit_mult === 'edit') {
            $submit_mult = 'row_edit';
        } elseif ($submit_mult === 'copy') {
            $submit_mult = 'row_copy';
        }

        if ($submit_mult === 'row_copy') {
            $_POST['default_action'] = 'insert';
        }

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

        /** @var ChangeController $controller */
        $controller = $containerBuilder->get(ChangeController::class);
        $controller->index();
    }

    public function export(): void
    {
        global $containerBuilder, $active_page, $single_table, $where_clause, $submit_mult;

        $submit_mult = 'row_export';

        if (isset($_POST['goto']) && (! isset($_POST['rows_to_delete']) || ! is_array($_POST['rows_to_delete']))) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return;
        }

        // Needed to allow SQL export
        $single_table = true;

        // As we got the rows to be exported from the
        // 'rows_to_delete' checkbox, we use the index of it as the
        // indicating WHERE clause. Then we build the array which is used
        // for the /table/change script.
        $where_clause = [];
        if (isset($_POST['rows_to_delete']) && is_array($_POST['rows_to_delete'])) {
            foreach ($_POST['rows_to_delete'] as $i => $i_where_clause) {
                $where_clause[] = $i_where_clause;
            }
        }

        $active_page = Url::getFromRoute('/table/export');

        /** @var ExportController $controller */
        $controller = $containerBuilder->get(ExportController::class);
        $controller->index();
    }
}
