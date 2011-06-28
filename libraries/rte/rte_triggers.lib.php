<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Main function for the triggers functionality
 */
function PMA_TRI_main()
{
    global $db, $table, $header_arr, $human_name;

    /**
     * Here we define some data that will be used to create the list triggers
     */
    $human_name = __('trigger');
    $items      = PMA_DBI_get_triggers($db, $table);
    $cols       = array(array('label' => __('Name'),   'colspan' => 1, 'field'   => 'name'),
                        array('label' => __('Table'),  'colspan' => 1, 'field'   => 'table'),
                        array('label' => __('Action'), 'colspan' => 3, 'field'   => 'edit'),
                        array(                         'colspan' => 1, 'field'   => 'export'),
                        array(                         'colspan' => 1, 'field'   => 'drop'),
                        array('label' => __('Time'),   'colspan' => 1, 'field'   => 'time'),
                        array('label' => __('Event'),  'colspan' => 1, 'field'   => 'event'));
    $header_arr = array('title'   => __('Triggers'),
                        'docu'    => 'TRIGGERS',
                        'nothing' => __('There are no triggers to display.'),
                        'cols'    => $cols);
    if (! empty($table)) {
        // Remove the table header
        unset ($header_arr['cols']['1']);
    }
    /**
     * Process all requests
     */
    PMA_TRI_handleExport();
    /**
     * Display a list of available triggers
     */
    echo PMA_RTE_getList('trigger', $items);
    /**
     * Display a link for adding a new trigger,
     * if the user has the necessary privileges
     */
    echo PMA_TRI_getFooterLinks();
} // end PMA_TRI_main()

?>
