<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Common;
use PhpMyAdmin\Database\Triggers;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function in_array;
use function strlen;

/**
 * Triggers management.
 */
class TriggersController extends AbstractController
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

    public function index(): void
    {
        global $db, $table, $tables, $num_tables, $total_num_tables, $sub_part;
        global $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos;
        global $errors, $url_params, $err_url, $cfg;

        if (! $this->response->isAjax()) {
            /**
             * Displays the header and tabs
             */
            if (! empty($table) && in_array($table, $this->dbi->getTables($db))) {
                Util::checkParameters(['db', 'table']);

                $db_is_system_schema = Utilities::isSystemSchema($db);
                $url_params = ['db' => $db, 'table' => $table];
                $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
                $err_url .= Url::getCommon($url_params, '&');

                DbTableExists::check();
            } else {
                $table = '';
                Common::database();

                [
                    $tables,
                    $num_tables,
                    $total_num_tables,
                    $sub_part,,
                    $db_is_system_schema,
                    $tooltip_truename,
                    $tooltip_aliasname,
                    $pos,
                ] = Util::getDbInfo($db, $sub_part ?? '');
            }
        } elseif (strlen($db) > 0) {
            $this->dbi->selectDb($db);
        }

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $errors = [];

        $triggers = new Triggers($this->dbi, $this->template, $this->response);
        $triggers->main();
    }
}
