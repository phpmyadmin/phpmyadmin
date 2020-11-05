<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function is_array;
use function sprintf;

class DeleteController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param string            $db       Database name.
     * @param string            $table    Table name.
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, $table, $dbi)
    {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;
    }

    public function rows(): void
    {
        global $db, $goto, $sql_query, $table, $disp_message, $disp_query, $PMA_Theme, $active_page;

        $mult_btn = $_POST['mult_btn'] ?? '';
        $original_sql_query = $_POST['original_sql_query'] ?? '';
        $selected = $_POST['selected'] ?? [];

        $relation = new Relation($this->dbi);
        $sql = new Sql(
            $this->dbi,
            $relation,
            new RelationCleanup($this->dbi, $relation),
            new Operations($this->dbi, $relation),
            new Transformations(),
            $this->template
        );

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
            $goto,
            $PMA_Theme->getImgPath(),
            null,
            null,
            $sql_query,
            null
        ));
    }

    public function confirm(): void
    {
        global $db, $table, $sql_query, $url_params, $err_url, $cfg;

        $selected = $_POST['rows_to_delete'] ?? null;

        if (! isset($selected) || ! is_array($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return;
        }

        Util::checkParameters(['db', 'table']);

        $url_params = ['db' => $db, 'table' => $table];
        $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $err_url .= Url::getCommon($url_params, '&');

        DbTableExists::check();

        $this->render('table/delete/confirm', [
            'db' => $db,
            'table' => $table,
            'selected' => $selected,
            'sql_query' => $sql_query,
            'is_foreign_key_check' => Util::isForeignKeyCheck(),
        ]);
    }
}
