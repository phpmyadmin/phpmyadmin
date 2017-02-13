<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common code for Routines, Triggers and Events.
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\URL;
use PMA\libraries\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Include all other files that are common
 * to routines, triggers and events.
 */
require_once './libraries/rte/rte_general.lib.php';
require_once './libraries/rte/rte_words.lib.php';
require_once './libraries/rte/rte_export.lib.php';
require_once './libraries/rte/rte_list.lib.php';
require_once './libraries/rte/rte_footer.lib.php';

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
        ) = PMA\libraries\Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');
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
            $url_query = URL::getCommon(
                array(
                    'db' => $db, 'table' => $table
                )
            );
        }
    }
}

/**
 * Generate the conditional classes that will
 * be used to attach jQuery events to links
 */
$ajax_class = array(
    'add'    => 'class="ajax add_anchor"',
    'edit'   => 'class="ajax edit_anchor"',
    'exec'   => 'class="ajax exec_anchor"',
    'drop'   => 'class="ajax drop_anchor"',
    'export' => 'class="ajax export_anchor"'
);

/**
 * Create labels for the list
 */
$titles = PMA\libraries\Util::buildActionTitles();

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
    PMA_RTN_main($type);
    break;
case 'TRI':
    PMA_TRI_main();
    break;
case 'EVN':
    PMA_EVN_main();
    break;
}

