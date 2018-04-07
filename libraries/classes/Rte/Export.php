<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common functions for the export functionality for Routines, Triggers and Events.
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Rte;

use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Rte\Words;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\Rte\Export class
 *
 * @package PhpMyAdmin
 */
class Export
{
    /**
     * This function is called from one of the other functions in this file
     * and it completes the handling of the export functionality.
     *
     * @param string $export_data The SQL query to create the requested item
     *
     * @return void
     */
    private static function handle($export_data)
    {
        global $db;

        $response = Response::getInstance();

        $item_name = htmlspecialchars(Util::backquote($_GET['item_name']));
        if ($export_data !== false) {
            $export_data = htmlspecialchars(trim($export_data));
            $title = sprintf(Words::get('export'), $item_name);
            if ($response->isAjax()) {
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
            $_db = htmlspecialchars(Util::backquote($db));
            $message  = __('Error in processing request:') . ' '
                      . sprintf(Words::get('no_view'), $item_name, $_db);
            $message = Message::error($message);

            if ($response->isAjax()) {
                $response->setRequestStatus(false);
                $response->addJSON('message', $message);
                exit;
            } else {
                $message->display();
            }
        }
    } // end self::handle()

    /**
     * If necessary, prepares event information and passes
     * it to self::handle() for the actual export.
     *
     * @return void
     */
    public static function events()
    {
        global $_GET, $db;

        if (! empty($_GET['export_item']) && ! empty($_GET['item_name'])) {
            $item_name = $_GET['item_name'];
            $export_data = $GLOBALS['dbi']->getDefinition($db, 'EVENT', $item_name);
            if (! $export_data) {
                $export_data = false;
            }
            self::handle($export_data);
        }
    } // end self::events()

    /**
     * If necessary, prepares routine information and passes
     * it to self::handle() for the actual export.
     *
     * @return void
     */
    public static function routines()
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

                self::handle($export_data);
            }
        }
    } // end self::routines()

    /**
     * If necessary, prepares trigger information and passes
     * it to self::handle() for the actual export.
     *
     * @return void
     */
    public static function triggers()
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
            self::handle($export_data);
        }
    } // end self::triggers()
}
