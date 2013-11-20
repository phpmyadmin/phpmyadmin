<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common code for Routines, Triggers and Events.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Include all other files that are common
 * to routines, triggers and events.
 */
require_once './libraries/rte/rte_words.lib.php';
require_once './libraries/rte/rte_export.lib.php';
require_once './libraries/rte/rte_list.lib.php';
require_once './libraries/rte/rte_footer.lib.php';

if ($GLOBALS['is_ajax_request'] != true) {
    /**
     * Displays the header and tabs
     */
    if (! empty($table) && in_array($table, $GLOBALS['dbi']->getTables($db))) {
        include_once './libraries/tbl_common.inc.php';
    } else {
        $table = '';
        include_once './libraries/db_common.inc.php';
        include_once './libraries/db_info.inc.php';
    }
} else {
    /**
     * Since we did not include some libraries, we need
     * to manually select the required database and
     * create the missing $url_query variable
     */
    if (strlen($db)) {
        $GLOBALS['dbi']->selectDb($db);
        if (! isset($url_query)) {
            $url_query = PMA_URL_getCommon($db, $table);
        }
    }
}

/**
 * Generate the conditional classes that will
 * be used to attach jQuery events to links
 */
$ajax_class = array(
    'add'    => '',
    'edit'   => '',
    'exec'   => '',
    'drop'   => '',
    'export' => ''
);
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
$titles = PMA_Util::buildActionTitles();

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

?>
