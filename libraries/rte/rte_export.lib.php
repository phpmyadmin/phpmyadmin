<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common functions for the export functionality for Routines, Triggers and Events.
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Message;
use PMA\libraries\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This function is called from one of the other functions in this file
 * and it completes the handling of the export functionality.
 *
 * @param string $export_data The SQL query to create the requested item
 *
 * @return void
 */
function PMA_RTE_handleExport($export_data)
{
    global $db;

    $item_name = htmlspecialchars(PMA\libraries\Util::backquote($_GET['item_name']));
    if ($export_data !== false) {
        $export_data = htmlspecialchars(trim($export_data));
        $title = sprintf(PMA_RTE_getWord('export'), $item_name);
        if ($GLOBALS['is_ajax_request'] == true) {
            $response = PMA\libraries\Response::getInstance();
            $response->addJSON('message', $export_data);
            $response->addJSON('title', $title);
            exit;
        } else {
            $export_data = '<textarea cols="40" rows="15" style="width: 100%;">'
               . $export_data . '</textarea>';
            echo "<fieldset>\n"
               , "<legend>$title</legend>\n"
               , $export_data
               , "</fieldset>\n";
        }
    } else {
        $_db = htmlspecialchars(PMA\libraries\Util::backquote($db));
        $message  = __('Error in processing request:') . ' '
                  . sprintf(PMA_RTE_getWord('no_view'), $item_name, $_db);
        $message = Message::error($message);

        if ($GLOBALS['is_ajax_request'] == true) {
            $response = PMA\libraries\Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $message);
            exit;
        } else {
            $message->display();
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
        $export_data = $GLOBALS['dbi']->getDefinition($db, 'EVENT', $item_name);
        if (! $export_data) {
            $export_data = false;
        }
        PMA_RTE_handleExport($export_data);
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

    if (! empty($_GET['export_item'])
        && ! empty($_GET['item_name'])
        && ! empty($_GET['item_type'])
    ) {
        if ($_GET['item_type'] == 'FUNCTION' || $_GET['item_type'] == 'PROCEDURE') {
            $rtn_definition
                = $GLOBALS['dbi']->getDefinition(
                    $db,
                    $_GET['item_type'],
                    $_GET['item_name']
                );
            if (! $rtn_definition) {
                $export_data = false;
            } else {
                $export_data = "DELIMITER $$\n"
                    . $rtn_definition
                    . "$$\nDELIMITER ;\n";
            }

            PMA_RTE_handleExport($export_data);
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
        $triggers = $GLOBALS['dbi']->getTriggers($db, $table, '');
        $export_data = false;
        foreach ($triggers as $trigger) {
            if ($trigger['name'] === $item_name) {
                $export_data = $trigger['create'];
                break;
            }
        }
        PMA_RTE_handleExport($export_data);
    }
} // end PMA_TRI_handleExport()
