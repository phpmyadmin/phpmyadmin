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
    if (! empty($table) && in_array($table, PMA_DBI_get_tables($db))) {
        include_once './libraries/tbl_common.php';
        include_once './libraries/tbl_links.inc.php';
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
        PMA_DBI_select_db($db);
        if (! isset($url_query)) {
            $url_query = PMA_generate_common_url($db, $table);
        }
    }
}

/**
 * Generate the conditional classes that will
 * be used to attach jQuery events to links
 */
$ajax_class = array('add'    => '',
                    'edit'   => '',
                    'exec'   => '',
                    'drop'   => '',
                    'export' => '');
if ($GLOBALS['cfg']['AjaxEnable']) {
    $ajax_class = array('add'    => 'class="ajax_add_anchor"',
                        'edit'   => 'class="ajax_edit_anchor"',
                        'exec'   => 'class="ajax_exec_anchor"',
                        'drop'   => 'class="ajax_drop_anchor"',
                        'export' => 'class="ajax_export_anchor"');
}

/**
 * Create labels for the list
 */
$titles = PMA_buildActionTitles();

/**
 * Keep a list of errors that occured while
 * processing an 'Add' or 'Edit' operation.
 */
$errors = array();


/**
 * Call the appropriate main function
 */
switch ($_PMA_RTE) {
case 'RTN':
    PMA_RTN_main();
    break;
case 'TRI':
    PMA_TRI_main();
    break;
case 'EVN':
    PMA_EVN_main();
    break;
}

/**
 * Display the footer, if necessary
 */
if ($GLOBALS['is_ajax_request'] != true) {
    include './libraries/footer.inc.php';
}

?>
