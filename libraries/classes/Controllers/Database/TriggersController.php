<?php
/**
 * Holds the PhpMyAdmin\Controllers\Database\TriggersController
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Common;
use PhpMyAdmin\Database\Triggers;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function in_array;
use function strlen;

/**
 * Triggers management.
 */
class TriggersController extends AbstractController
{
    public function index(): void
    {
        global $db, $table, $tables, $num_tables, $total_num_tables, $sub_part, $is_show_stats;
        global $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos, $url_query;
        global $errors, $titles;

        if (! $this->response->isAjax()) {
            /**
             * Displays the header and tabs
             */
            if (! empty($table) && in_array($table, $this->dbi->getTables($db))) {
                Common::table();
            } else {
                $table = '';
                Common::database();

                [
                    $tables,
                    $num_tables,
                    $total_num_tables,
                    $sub_part,
                    $is_show_stats,
                    $db_is_system_schema,
                    $tooltip_truename,
                    $tooltip_aliasname,
                    $pos,
                ] = Util::getDbInfo($db, $sub_part ?? '');
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

        $triggers = new Triggers($this->dbi, $this->template, $this->response);
        $triggers->main();
    }
}
