<?php
/**
 * @package PhpMyAdmin\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Rte\Triggers;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Triggers management.
 * @package PhpMyAdmin\Controllers\Table
 */
class TriggersController extends AbstractController
{
    /**
     * @return void
     */
    public function index(): void
    {
        global $_PMA_RTE, $db, $table, $tables, $num_tables, $total_num_tables, $sub_part, $is_show_stats;
        global $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos, $url_query;
        global $errors, $titles;

        $_PMA_RTE = 'TRI';

        if (! $this->response->isAjax()) {
            /**
             * Displays the header and tabs
             */
            if (! empty($table) && in_array($table, $this->dbi->getTables($db))) {
                include_once ROOT_PATH . 'libraries/tbl_common.inc.php';
            } else {
                $table = '';
                include_once ROOT_PATH . 'libraries/db_common.inc.php';

                list(
                    $tables,
                    $num_tables,
                    $total_num_tables,
                    $sub_part,
                    $is_show_stats,
                    $db_is_system_schema,
                    $tooltip_truename,
                    $tooltip_aliasname,
                    $pos
                ) = Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');
            }
        } else {
            /**
             * Since we did not include some libraries, we need
             * to manually select the required database and
             * create the missing $url_query variable
             */
            if (strlen($db) > 0) {
                $this->dbi->selectDb($db);
                if (! isset($url_query)) {
                    $url_query = Url::getCommon(
                        [
                            'db' => $db,
                            'table' => $table,
                        ]
                    );
                }
            }
        }

        /**
         * Create labels for the list
         */
        $titles = Util::buildActionTitles();

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $errors = [];

        $triggers = new Triggers($this->dbi);
        $triggers->main();
    }
}
