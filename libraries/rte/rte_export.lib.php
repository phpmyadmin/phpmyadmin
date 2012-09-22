<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common functions for the export functionality for Routines, Triggers and Events.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This function is called from one of the other functions in this file
 * and it completes the handling of the export functionality.
 *
 * @param string $item_name   The name of the item that we are exporting
 * @param string $export_data The SQL query to create the requested item
 *
 * @return void
 */
function PMA_RTE_handleExport($item_name, $export_data)
{
    global $db;

    $item_name = htmlspecialchars(PMA_Util::backquote($_GET['item_name']));
    if ($export_data !== false) {
        $export_data = '<textarea cols="40" rows="15" style="width: 100%;">'
                     . htmlspecialchars(trim($export_data)) . '</textarea>';
        $title = sprintf(PMA_RTE_getWord('export'), $item_name);
        if ($GLOBALS['is_ajax_request'] == true) {
            $response = PMA_Response::getInstance();
            $response->addJSON('message', $export_data);
            $response->addJSON('title', $title);
            exit;
        } else {
            echo "<fieldset>\n"
               . "<legend>$title</legend>\n"
               . $export_data
               . "</fieldset>\n";
        }
    } else {
        $_db = htmlspecialchars(PMA_Util::backquote($db));
        $response = __('Error in Processing Request') . ' : '
                  . sprintf(PMA_RTE_getWord('not_found'), $item_name, $_db);
        $response = PMA_message::error($response);
        if ($GLOBALS['is_ajax_request'] == true) {
            $response = PMA_Response::getInstance();
            $response->isSuccess(false);
            $response->addJSON('message', $response);
            exit;
        } else {
            $response->display();
        }
    }
} // end PMA_RTE_handleExport()

/**
 * If necessary, prepares event information and passes
 * it to PMA_RTE_handleExport() for the actual export.
 *
 * @return void
 */
function PMA_EVN_handleExport()
{
    global $_GET, $db;

    if (! empty($_GET['export_item']) && ! empty($_GET['item_name'])) {
        $item_name = $_GET['item_name'];
        $export_data = PMA_DBI_get_definition($db, 'EVENT', $item_name);
        PMA_RTE_handleExport($item_name, $export_data);
    }
} // end PMA_EVN_handleExport()

/**
 * If necessary, prepares routine information and passes
 * it to PMA_RTE_handleExport() for the actual export.
 *
 * @return void
 */
function PMA_RTN_handleExport()
{
    global $_GET, $db;

    if (   ! empty($_GET['export_item'])
        && ! empty($_GET['item_name'])
        && ! empty($_GET['item_type'])
    ) {
        if ($_GET['item_type'] == 'FUNCTION' || $_GET['item_type'] == 'PROCEDURE') {
            $export_data = PMA_DBI_get_definition(
                $db,
                $_GET['item_type'],
                $_GET['item_name']
            );
            PMA_RTE_handleExport($_GET['item_name'], $export_data);
        }
    }
} // end PMA_RTN_handleExport()

/**
 * If necessary, prepares trigger information and passes
 * it to PMA_RTE_handleExport() for the actual export.
 *
 * @return void
 */
function PMA_TRI_handleExport()
{
    global $_GET, $db, $table;

    if (! empty($_GET['export_item']) && ! empty($_GET['item_name'])) {
        $item_name = $_GET['item_name'];
        $triggers = PMA_DBI_get_triggers($db, $table, '');
        $export_data = false;
        foreach ($triggers as $trigger) {
            if ($trigger['name'] === $item_name) {
                $export_data = $trigger['create'];
                break;
            }
        }
        PMA_RTE_handleExport($item_name, $export_data);
    }
} // end PMA_TRI_handleExport()
?>
