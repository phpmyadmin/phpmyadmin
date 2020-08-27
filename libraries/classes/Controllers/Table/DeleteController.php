<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Common;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function is_array;
use function sprintf;

class DeleteController extends AbstractController
{
    public function rows(): void
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
                $this->dbi->query($query);
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

        $this->response->addHTML($sql->executeQueryAndSendQueryResponse(
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
            $sql_query,
            null
        ));
    }

    public function confirm(): void
    {
        global $db, $table, $sql_query;

        $selected = $_POST['rows_to_delete'] ?? null;

        if (! isset($selected) || ! is_array($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return;
        }

        Common::table();

        $this->render('table/delete/confirm', [
            'db' => $db,
            'table' => $table,
            'selected' => $selected,
            'sql_query' => $sql_query,
            'is_foreign_key_check' => Util::isForeignKeyCheck(),
        ]);
    }
}
