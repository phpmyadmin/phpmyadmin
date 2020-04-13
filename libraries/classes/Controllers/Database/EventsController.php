<?php
/**
 * Holds the PhpMyAdmin\Controllers\Database\EventsController
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Common;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function in_array;
use function strlen;

/**
 * Events management.
 */
class EventsController extends AbstractController
{
    public function index(): void
    {
        global $table, $db, $tables, $num_tables, $total_num_tables, $sub_part, $errors, $titles;
        global $is_show_stats, $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos, $url_query;

        if (! $this->response->isAjax()) {
            /**
             * Displays the header and tabs
             */
            if (! empty($table) && in_array($table, $this->dbi->getTables($db))) {
                Common::table();
            } else {
                $table = '';
                Common::database();

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
                ) = Util::getDbInfo($db, $sub_part ?? '');
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

        $events = new Events($this->dbi, $this->template, $this->response);
        $events->main();
    }
}
