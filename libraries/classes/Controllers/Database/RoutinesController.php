<?php
/**
 * Holds the PhpMyAdmin\Controllers\Database\RoutinesController
 *
 * @package PhpMyAdmin\Controllers\Database
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Common;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Rte\Routines;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Routines management.
 *
 * @package PhpMyAdmin\Controllers\Database
 */
class RoutinesController extends AbstractController
{
    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    /**
     * @param Response            $response            Response object
     * @param DatabaseInterface   $dbi                 DatabaseInterface object
     * @param Template            $template            Template object
     * @param string              $db                  Database name
     * @param CheckUserPrivileges $checkUserPrivileges CheckUserPrivileges object
     */
    public function __construct($response, $dbi, Template $template, $db, CheckUserPrivileges $checkUserPrivileges)
    {
        parent::__construct($response, $dbi, $template, $db);
        $this->checkUserPrivileges = $checkUserPrivileges;
    }

    /**
     * @param array $params Request parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        global $_PMA_RTE, $db, $table, $tables, $num_tables, $total_num_tables, $sub_part, $is_show_stats;
        global $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos, $url_query;
        global $errors, $titles;

        $this->checkUserPrivileges->getPrivileges();

        $_PMA_RTE = 'RTN';

        if (! $this->response->isAjax()) {
            /**
             * Displays the header and tabs
             */
            if (! empty($table) && in_array($table, $this->dbi->getTables($db))) {
                include_once ROOT_PATH . 'libraries/tbl_common.inc.php';
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

        $routines = new Routines($this->dbi);
        $routines->main($params['type']);
    }
}
