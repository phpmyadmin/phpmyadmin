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
        global $db, $goto, $pmaThemeImage, $sql_query, $table, $disp_message, $disp_query;
        global $active_page, $url_query;

        $mult_btn = $_POST['mult_btn'] ?? '';
        $original_sql_query = $_POST['original_sql_query'] ?? '';
        $selected = $_POST['selected'] ?? [];

        $sql = new Sql();

        if ($mult_btn === __('Yes')) {
            $default_fk_check_value = Util::handleDisableFKCheckInit();
            $sql_query = '';

            foreach ($selected as $row) {
                $query = sprintf(
                    'DELETE FROM %s WHERE %s LIMIT 1;',
                    Util::backquote($table),
                    $row
                );
                $sql_query .= $query . "\n";
                $this->dbi->selectDb($db);
                $result = $this->dbi->query($query);
            }

            if (! empty($_REQUEST['pos'])) {
                $_REQUEST['pos'] = $sql->calculatePosForLastPage(
                    $db,
                    $table,
                    $_REQUEST['pos']
                );
            }

            Util::handleDisableFKCheckCleanup($default_fk_check_value);

            $disp_message = __('Your SQL query has been executed successfully.');
            $disp_query = $sql_query;
        }

        $_url_params = $GLOBALS['url_params'];
        $_url_params['goto'] = Url::getFromRoute('/table/sql');
        $url_query = Url::getCommon($_url_params);

        if (isset($original_sql_query)) {
            $sql_query = $original_sql_query;
        }

        $active_page = Url::getFromRoute('/sql');

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

        $this->render('table/row_action/confirm_delete', [
            'db' => $db,
            'table' => $table,
            'selected' => $selected,
            'sql_query' => $sql_query,
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
