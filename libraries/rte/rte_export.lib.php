<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This function is called from one of the other functions in this file
 * and it completes the handling of the export functionality.
 *
 * @param  string  $item_name    The name of the item that we are exporting
 * @param  string  $export_data  The SQL query to create the requested item
 */
function PMA_RTE_handleExport($item_name, $export_data)
{
    global $db, $table, $human_name;

    $item_name = htmlspecialchars(PMA_backquote($_GET['item_name']));
    if ($export_data !== false) {
        $export_data = '<textarea cols="40" rows="15" style="width: 100%;">'
                     . htmlspecialchars($export_data) . '</textarea>';
        // l10n: Sample output: 'Export of event `testevent`' or 'Export of trigger `mytrigger`'
        $title = sprintf(__('Export of %1$s %2$s'), $human_name, $item_name);
        if ($GLOBALS['is_ajax_request'] == true) {
            $extra_data = array('title' => $title);
            PMA_ajaxResponse($export_data, true, $extra_data);
        } else {
            echo '<fieldset>' . "\n"
               . ' <legend>' . $title . '</legend>' . "\n"
               . $export_data
               . '</fieldset>';
        }
    } else {
        $_db = htmlspecialchars(PMA_backquote($db));
        $response = __('Error in Processing Request') . ' : '
        // l10n: Sample output: 'No event with name `myevent` found in database `mydb`'
                  . sprintf(__('No %1$s with name %2$s found in database %3$s'),
                            $human_name, $item_name, $_db);
        $response = PMA_message::error($response);
        if ($GLOBALS['is_ajax_request'] == true) {
            PMA_ajaxResponse($response, false);
        } else {
            $response->display();
        }
    }
} // end PMA_RTE_handleExport()

/**
 * If necessary, prepares event information and passes
 * it to PMA_RTE_handleExport() for the actual export.
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
 */
function PMA_RTN_handleExport()
{
    global $_GET, $db;

    if (! empty($_GET['export_item']) && ! empty($_GET['item_name'])) {
        $item_name = $_GET['item_name'];
        $type = PMA_DBI_fetch_value(
                    "SELECT ROUTINE_TYPE "
                  . "FROM INFORMATION_SCHEMA.ROUTINES "
                  . "WHERE ROUTINE_SCHEMA='" . PMA_sqlAddSlashes($db) . "' "
                  . "AND SPECIFIC_NAME='" . PMA_sqlAddSlashes($item_name) . "';"
                );
        $export_data = PMA_DBI_get_definition($db, $type, $item_name);
        PMA_RTE_handleExport($item_name, $export_data);
    }
} // end PMA_RTN_handleExport()

/**
 * If necessary, prepares trigger information and passes
 * it to PMA_RTE_handleExport() for the actual export.
 */
function PMA_TRI_handleExport()
{
    global $_GET, $db, $table;

    if (! empty($_GET['export_item']) && ! empty($_GET['item_name'])) {
        $item_name = $_GET['item_name'];
        $triggers = PMA_DBI_get_triggers($db, $table);
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
