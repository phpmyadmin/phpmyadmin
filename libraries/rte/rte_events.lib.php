<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Main function for the routines functionality
 */
function PMA_EVN_main()
{
    global $db, $header_arr, $human_name;

    /**
     * Here we define some data that will be used to create the list events
     */
    $human_name = __('event');
    $columns    = "`EVENT_NAME`, `EVENT_TYPE`";
    $where      = "EVENT_SCHEMA='" . PMA_sqlAddslashes($db) . "'";
    $items      = PMA_DBI_fetch_result("SELECT $columns FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE $where;");
    $cols       = array(array('label'   => __('Name'),   'colspan' => 1, 'field'   => 'name'),
                        array('label'   => __('Action'), 'colspan' => 3, 'field'   => 'edit'),
                        array(                           'colspan' => 1, 'field'   => 'export'),
                        array(                           'colspan' => 1, 'field'   => 'drop'),
                        array('label'   => __('Type'),   'colspan' => 1, 'field'   => 'type'));
    $header_arr = array('title'   => __('Events'),
                        'docu'    => 'EVENTS',
                        'nothing' => __('There are no events to display.'),
                        'cols'    => $cols);
    /**
     * Process all requests
     */
    PMA_EVN_handleExport();
    /**
     * Display a list of available events
     */
    echo PMA_RTE_getList('event', $items);
    /**
     * Display a link for adding a new event, if
     * the user has the privileges and a link to
     * toggle the state of the vent scheduler.
     */
    echo PMA_EVN_getFooterLinks();
} // end PMA_EVN_main()

?>
