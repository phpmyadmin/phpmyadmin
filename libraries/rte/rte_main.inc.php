<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Create labels for the list
 */
$titles = PMA_buildActionTitles();

if ($GLOBALS['is_ajax_request'] != true) {
    /**
     * Displays the header and tabs
     */
    if (! empty($table) && in_array($table, PMA_DBI_get_tables($db))) {
        require_once './libraries/tbl_common.php';
        require_once './libraries/tbl_links.inc.php';
    } else {
        $table = '';
        require_once './libraries/db_common.inc.php';
        require_once './libraries/db_info.inc.php';
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
 * Generate the conditional classes that will be used to attach jQuery events to links
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
 * Keep a list of errors that occured while processing an 'Add' or 'Edit' operation.
 */
$errors = array();

// Some definitions for triggers
$action_timings      = array('BEFORE',
                             'AFTER');
$event_manipulations = array('INSERT',
                             'UPDATE',
                             'DELETE');

// Some definitions for routines
$param_directions    = array('IN',
                             'OUT',
                             'INOUT');
$param_opts_num      = array('UNSIGNED',
                             'ZEROFILL',
                             'UNSIGNED ZEROFILL');
$param_sqldataaccess = array('NO SQL',
                             'CONTAINS SQL',
                             'READS SQL DATA',
                             'MODIFIES SQL DATA');

// Some definitions for events
$event_status        = array(
                           'query'   => array('ENABLE',
                                              'DISABLE',
                                              'DISABLE ON SLAVE'),
                           'display' => array('ENABLED',
                                              'DISABLED',
                                              'SLAVESIDE_DISABLED')
                       );
$event_type          = array('RECURRING',
                             'ONE TIME');
$event_interval      = array('YEAR',
                             'QUARTER',
                             'MONTH',
                             'DAY',
                             'HOUR',
                             'MINUTE',
                             'WEEK',
                             'SECOND',
                             'YEAR_MONTH',
                             'DAY_HOUR',
                             'DAY_MINUTE',
                             'DAY_SECOND',
                             'HOUR_MINUTE',
                             'HOUR_SECOND',
                             'MINUTE_SECOND');
/**
 * The below function is defined in rte_routines.lib.php,
 * rte_triggers.lib.php and rte_events.lib.php
 *
 * The appropriate function will now be called based on which one
 * of these files was included earlier in the top-level folder
 */
PMA_RTE_main();

/**
 * Display the footer, if necessary
 */
if ($GLOBALS['is_ajax_request'] != true) {
    require './libraries/footer.inc.php';
}

?>
