<?php
/**
 * Common functions for the export functionality for Routines, Triggers and Events.
 */
declare(strict_types=1);

namespace PhpMyAdmin\Rte;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;
use function htmlspecialchars;
use function sprintf;
use function trim;

/**
 * PhpMyAdmin\Rte\Export class
 */
class Export
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param DatabaseInterface $dbi DatabaseInterface object
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    /**
     * This function is called from one of the other functions in this file
     * and it completes the handling of the export functionality.
     *
     * @param string|false $export_data The SQL query to create the requested item
     * @param string       $type        RTE type (routine|trigger|event).
     *
     * @return void
     */
    private function handle($export_data, string $type): void
    {
        global $db;

        $response = Response::getInstance();

        $exportMessage = '';
        $noViewMessage = '';
        if ($type === 'routine') {
            $exportMessage = __('Export of routine %s');
            $noViewMessage = __(
                'No routine with name %1$s found in database %2$s. '
                . 'You might be lacking the necessary privileges to view/export this routine.'
            );
        } elseif ($type === 'event') {
            $exportMessage = __('Export of event %s');
        } elseif ($type === 'trigger') {
            $exportMessage = __('Export of trigger %s');
        }

        $item_name = htmlspecialchars(Util::backquote($_GET['item_name']));
        if ($export_data !== false) {
            $export_data = htmlspecialchars(trim($export_data));
            $title = sprintf($exportMessage, $item_name);

            if ($response->isAjax()) {
                $response->addJSON('message', $export_data);
                $response->addJSON('title', $title);

                exit;
            }

            $export_data = '<textarea cols="40" rows="15" style="width: 100%;">'
               . $export_data . '</textarea>';
            echo "<fieldset>\n"
               , '<legend>' . $title . "</legend>\n"
               , $export_data
               , "</fieldset>\n";

            return;
        }

        $_db = htmlspecialchars(Util::backquote($db));
        $message  = __('Error in processing request:') . ' '
                  . sprintf($noViewMessage, $item_name, $_db);
        $message = Message::error($message);

        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            $response->addJSON('message', $message);

            exit;
        }

        $message->display();
    }

    /**
     * If necessary, prepares event information and passes
     * it to handle() for the actual export.
     *
     * @return void
     */
    public function events(): void
    {
        global $db;

        if (empty($_GET['export_item']) || empty($_GET['item_name'])) {
            return;
        }

        $item_name = $_GET['item_name'];
        $export_data = $this->dbi->getDefinition($db, 'EVENT', $item_name);

        if (! $export_data) {
            $export_data = false;
        }

        $this->handle($export_data, 'event');
    }

    /**
     * If necessary, prepares routine information and passes
     * it to handle() for the actual export.
     *
     * @return void
     */
    public function routines(): void
    {
        global $db;

        if (empty($_GET['export_item']) || empty($_GET['item_name']) || empty($_GET['item_type'])) {
            return;
        }

        if ($_GET['item_type'] !== 'FUNCTION' && $_GET['item_type'] !== 'PROCEDURE') {
            return;
        }

        $rtn_definition = $this->dbi->getDefinition($db, $_GET['item_type'], $_GET['item_name']);
        $export_data = false;

        if ($rtn_definition !== null) {
            $export_data = "DELIMITER $$\n" . $rtn_definition . "$$\nDELIMITER ;\n";
        }

        $this->handle($export_data, 'routine');
    }

    /**
     * If necessary, prepares trigger information and passes
     * it to handle() for the actual export.
     *
     * @return void
     */
    public function triggers(): void
    {
        global $db, $table;

        if (empty($_GET['export_item']) || empty($_GET['item_name'])) {
            return;
        }

        $item_name = $_GET['item_name'];
        $triggers = $this->dbi->getTriggers($db, $table, '');
        $export_data = false;

        foreach ($triggers as $trigger) {
            if ($trigger['name'] === $item_name) {
                $export_data = $trigger['create'];
                break;
            }
        }

        $this->handle($export_data, 'trigger');
    }
}
