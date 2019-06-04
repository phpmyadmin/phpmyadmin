<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common functions for the export functionality for Routines, Triggers and Events.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Rte;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\Rte\Export class
 *
 * @package PhpMyAdmin
 */
class Export
{
    /**
     * @var Words
     */
    private $words;

    /**
     * @var DatabaseInterface
     */
    private $dbi;

    /**
     * Export constructor.
     *
     * @param DatabaseInterface $dbi DatabaseInterface object
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
        $this->words = new Words();
    }

    /**
     * This function is called from one of the other functions in this file
     * and it completes the handling of the export functionality.
     *
     * @param string $export_data The SQL query to create the requested item
     *
     * @return void
     */
    private function handle($export_data)
    {
        global $db;

        $response = Response::getInstance();

        $item_name = htmlspecialchars(Util::backquote($_GET['item_name']));
        if ($export_data !== false) {
            $export_data = htmlspecialchars(trim($export_data));
            $title = sprintf($this->words->get('export'), $item_name);
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
                      . sprintf($this->words->get('no_view'), $item_name, $_db);
            $message = Message::error($message);

            if ($response->isAjax()) {
                $response->setRequestStatus(false);
                $response->addJSON('message', $message);
                exit;
            } else {
                $message->display();
            }
        }
    }

    /**
     * If necessary, prepares event information and passes
     * it to handle() for the actual export.
     *
     * @return void
     */
    public function events()
    {
        global $db;

        if (! empty($_GET['export_item']) && ! empty($_GET['item_name'])) {
            $item_name = $_GET['item_name'];
            $export_data = $this->dbi->getDefinition($db, 'EVENT', $item_name);
            if (! $export_data) {
                $export_data = false;
            }
            $this->handle($export_data);
        }
    }

    /**
     * If necessary, prepares routine information and passes
     * it to handle() for the actual export.
     *
     * @return void
     */
    public function routines()
    {
        global $db;

        if (! empty($_GET['export_item'])
            && ! empty($_GET['item_name'])
            && ! empty($_GET['item_type'])
        ) {
            if ($_GET['item_type'] == 'FUNCTION' || $_GET['item_type'] == 'PROCEDURE') {
                $rtn_definition
                    = $this->dbi->getDefinition(
                        $db,
                        $_GET['item_type'],
                        $_GET['item_name']
                    );
                if ($rtn_definition === null) {
                    $export_data = false;
                } else {
                    $export_data = "DELIMITER $$\n"
                        . $rtn_definition
                        . "$$\nDELIMITER ;\n";
                }

                $this->handle($export_data);
            }
        }
    }

    /**
     * If necessary, prepares trigger information and passes
     * it to handle() for the actual export.
     *
     * @return void
     */
    public function triggers()
    {
        global $db, $table;

        if (! empty($_GET['export_item']) && ! empty($_GET['item_name'])) {
            $item_name = $_GET['item_name'];
            $triggers = $this->dbi->getTriggers($db, $table, '');
            $export_data = false;
            foreach ($triggers as $trigger) {
                if ($trigger['name'] === $item_name) {
                    $export_data = $trigger['create'];
                    break;
                }
            }
            $this->handle($export_data);
        }
    }
}
