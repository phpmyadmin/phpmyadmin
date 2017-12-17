<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common code for Routines, Triggers and Events.
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Response;
use PhpMyAdmin\Rte\Events;
use PhpMyAdmin\Rte\Routines;
use PhpMyAdmin\Rte\Triggers;
use PhpMyAdmin\Url;

if (! defined('PHPMYADMIN')) {
    exit;
}

$response = Response::getInstance();

if (! $response->isAjax()) {
    /**
     * Displays the header and tabs
     */
    if (! empty($table) && in_array($table, $GLOBALS['dbi']->getTables($db))) {
        include_once './libraries/tbl_common.inc.php';
    } else {
        $table = '';
        include_once './libraries/db_common.inc.php';

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
        ) = PhpMyAdmin\Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');
    }
} else {
    /**
     * Since we did not include some libraries, we need
     * to manually select the required database and
     * create the missing $url_query variable
     */
    if (strlen($db) > 0) {
        $GLOBALS['dbi']->selectDb($db);
        if (! isset($url_query)) {
            $url_query = Url::getCommon(
                array(
                    'db' => $db, 'table' => $table
                )
            );
        }
    }
}

/**
 * Create labels for the list
 */
$titles = PhpMyAdmin\Util::buildActionTitles();

/**
 * Keep a list of errors that occurred while
 * processing an 'Add' or 'Edit' operation.
 */
$errors = array();


/**
 * Call the appropriate main function
 */
switch ($_PMA_RTE) {
case 'RTN':
    $type = null;
    if (isset($_REQUEST['type'])) {
        $type = $_REQUEST['type'];
    }
    Routines::main($type);
    break;
case 'TRI':
    Triggers::main();
    break;
case 'EVN':
    Events::main();
    break;
}
